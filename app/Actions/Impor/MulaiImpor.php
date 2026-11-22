<?php

namespace App\Actions\Impor;

use App\Actions\Action;
use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Actions\Upz\SimpanUpz;
use App\Models\Asnaf;
use App\Models\Concerns\MencatatAudit;
use App\Models\Desa;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\JenisUpz;
use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Upz;
use App\Services\IdentitasPribadiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Membuat job impor lalu memproses baris secara sinkron (dedaunan kecil).
 * Antrean database dipakai bila baris > 500 via ProcessImportJob (modul berikut).
 */
class MulaiImpor extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): ImportJob
    {
        $data = validator($data, [
            'jenis' => ['required', 'in:rkat,realisasi,pos,muzakki,mustahik,upz'],
            'nama_berkas' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:255'],
            'baris' => ['required', 'array', 'min:1'],
            'baris.*.nomor' => ['required', 'integer', 'min:1'],
            'baris.*.kunci' => ['required', 'string', 'max:128'],
            'baris.*.data' => ['required', 'array'],
        ])->validate();

        $job = ImportJob::create([
            'user_id' => Auth::id(),
            'jenis' => $data['jenis'],
            'nama_berkas' => $data['nama_berkas'],
            'path' => $data['path'],
            'status' => 'proses',
            'total_baris' => count($data['baris']),
            'mulai_pada' => now(),
        ]);

        $sukses = 0;
        foreach ($data['baris'] as $baris) {
            $galat = $this->validasiBaris($data['jenis'], $baris['data']);

            if ($galat === [] && in_array($data['jenis'], ['muzakki', 'mustahik', 'upz'], true)) {
                $galat = $this->upsertEntitas($data['jenis'], $baris['data']);
            }

            $status = $galat === [] ? 'valid' : 'galat';

            ImportRow::updateOrCreate(
                ['jenis' => $data['jenis'], 'kunci' => $baris['kunci']],
                [
                    'jenis' => $data['jenis'],
                    'import_job_id' => $job->id,
                    'nomor_baris' => $baris['nomor'],
                    'status' => $status,
                    'galat' => $galat ?: null,
                    'data' => $baris['data'],
                ],
            );

            $sukses += $galat === [] ? 1 : 0;
        }

        $job->update([
            'status' => 'selesai',
            'baris_sukses' => $sukses,
            'baris_gagal' => $job->total_baris - $sukses,
            'ringkasan' => ['jenis' => $data['jenis'], 'total' => $job->total_baris],
            'selesai_pada' => now(),
        ]);

        $this->catatAudit('impor', ImportJob::class, $job->id, null, $job->only(['jenis', 'nama_berkas', 'total_baris']));

        return $job->fresh('rows');
    }

    private function validasiBaris(string $jenis, array $data): array
    {
        $aturan = match ($jenis) {
            'rkat', 'pos' => [
                'kode_pos' => ['required', 'string'],
                'tipe_nilai' => ['required', 'in:rupiah,orang,badan,jiwa,unit,persen'],
                'nilai' => ['required', 'integer'],
            ],
            'realisasi' => [
                'tahun' => ['required', 'integer', 'between:2000,2100'],
                'bulan' => ['required', 'integer', 'between:1,12'],
                'kode_pos' => ['required', 'string'],
                'tipe_nilai' => ['required', 'in:rupiah,orang,badan,jiwa,unit,persen'],
                'nilai' => ['required', 'integer'],
            ],
            'muzakki' => [
                'kode' => ['nullable', 'string', 'max:32'],
                'jenis' => ['nullable', 'in:perorangan,lembaga'],
                'nama' => ['required', 'string', 'max:160'],
                'nik' => ['nullable', 'digits:16'],
                'telepon' => ['nullable', 'string', 'max:32'],
                'npwp' => ['nullable', 'string', 'max:32'],
                'email' => ['nullable', 'email', 'max:160'],
                'alamat' => ['nullable', 'string', 'max:500'],
                'desa' => ['nullable', 'string', 'max:120'],
            ],
            'mustahik' => [
                'kode' => ['nullable', 'string', 'max:32'],
                'jenis' => ['nullable', 'in:perorangan,lembaga'],
                'nama' => ['required', 'string', 'max:160'],
                'nik' => ['nullable', 'digits:16'],
                'telepon' => ['nullable', 'string', 'max:32'],
                'alamat' => ['nullable', 'string', 'max:500'],
                'desa' => ['nullable', 'string', 'max:120'],
                'asnaf' => ['nullable', 'string', 'max:255'],
            ],
            'upz' => [
                'kode' => ['nullable', 'string', 'max:32'],
                'nama' => ['required', 'string', 'max:160'],
                'nomor_sk' => ['nullable', 'string', 'max:128'],
                'jenis_upz' => ['nullable', 'string', 'max:64'],
                'desa' => ['nullable', 'string', 'max:120'],
                'ketua' => ['nullable', 'string', 'max:160'],
                'telepon' => ['nullable', 'string', 'max:32'],
                'alamat' => ['nullable', 'string', 'max:500'],
            ],
        };

        $validator = validator($data, $aturan);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }

    /**
     * Melakukan upsert idempoten data lama (muzakki, mustahik, upz) pada kunci alami.
     *
     * @return array<string, list<string>>
     */
    private function upsertEntitas(string $jenis, array $row): array
    {
        try {
            $identitas = app(IdentitasPribadiService::class);

            if ($jenis === 'muzakki') {
                $nikHash = $identitas->hashPencarian($row['nik'] ?? null);
                $telpNorm = $identitas->normalisasiTelepon($row['telepon'] ?? null);
                $kode = ! empty($row['kode']) ? strtoupper(trim((string) $row['kode'])) : null;
                $desa = ! empty($row['desa'])
                    ? Desa::where('nama', 'ilike', trim((string) $row['desa']))->first()
                    : null;

                $eksisting = Muzakki::query()
                    ->when($kode, fn ($q) => $q->orWhere('kode', $kode))
                    ->when($nikHash, fn ($q) => $q->orWhere('nik_hash', $nikHash))
                    ->when($telpNorm && empty($kode) && empty($nikHash), fn ($q) => $q->where('telepon_norm', $telpNorm)->where('nama', 'ilike', trim((string) $row['nama'])))
                    ->first();

                app(SimpanMuzakki::class)->jalankan([
                    'id' => $eksisting?->id,
                    'kode' => $kode ?? $eksisting?->kode,
                    'jenis' => ($row['jenis'] ?? '') !== '' ? $row['jenis'] : 'perorangan',
                    'nama' => $row['nama'],
                    'nik' => $row['nik'] ?? null,
                    'npwp' => $row['npwp'] ?? null,
                    'telepon' => $row['telepon'] ?? null,
                    'email' => $row['email'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'desa_id' => $desa?->id,
                ]);

                return [];
            }

            if ($jenis === 'mustahik') {
                $nikHash = $identitas->hashPencarian($row['nik'] ?? null);
                $telpNorm = $identitas->normalisasiTelepon($row['telepon'] ?? null);
                $kode = ! empty($row['kode']) ? strtoupper(trim((string) $row['kode'])) : null;
                $desa = ! empty($row['desa'])
                    ? Desa::where('nama', 'ilike', trim((string) $row['desa']))->first()
                    : null;

                $asnafIds = [];
                if (! empty($row['asnaf'])) {
                    $token = array_values(array_filter(array_map('trim', explode(',', (string) $row['asnaf']))));
                    if ($token !== []) {
                        $asnafIds = Asnaf::query()
                            ->where(function ($q) use ($token): void {
                                foreach ($token as $t) {
                                    $q->orWhere('kode', 'ilike', $t)->orWhere('nama', 'ilike', $t);
                                }
                            })
                            ->pluck('id')
                            ->all();
                    }
                }

                $eksisting = Mustahik::query()
                    ->when($kode, fn ($q) => $q->orWhere('kode', $kode))
                    ->when($nikHash, fn ($q) => $q->orWhere('nik_hash', $nikHash))
                    ->when($telpNorm && empty($kode) && empty($nikHash), fn ($q) => $q->where('telepon_norm', $telpNorm)->where('nama', 'ilike', trim((string) $row['nama'])))
                    ->first();

                app(SimpanMustahik::class)->jalankan([
                    'id' => $eksisting?->id,
                    'kode' => $kode ?? $eksisting?->kode,
                    'jenis' => ($row['jenis'] ?? '') !== '' ? $row['jenis'] : 'perorangan',
                    'nama' => $row['nama'],
                    'nik' => $row['nik'] ?? null,
                    'telepon' => $row['telepon'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'desa_id' => $desa?->id,
                    'asnaf_ids' => $asnafIds,
                ]);

                return [];
            }

            $jenisUpz = ! empty($row['jenis_upz'])
                ? JenisUpz::where('kode', 'ilike', trim((string) $row['jenis_upz']))
                    ->orWhere('nama', 'ilike', trim((string) $row['jenis_upz']))
                    ->first()
                : JenisUpz::where('aktif', true)->orderBy('id')->first();

            if (! $jenisUpz) {
                return ['jenis_upz' => ['Jenis UPZ tidak ditemukan pada master data.']];
            }

            $desa = ! empty($row['desa'])
                ? Desa::where('nama', 'ilike', trim((string) $row['desa']))->first()
                : Desa::where('aktif', true)->orderBy('id')->first();

            if (! $desa) {
                return ['desa' => ['Desa/kelurahan tidak ditemukan pada master wilayah Kabupaten Cianjur.']];
            }

            $kode = ! empty($row['kode']) ? strtoupper(trim((string) $row['kode'])) : null;
            $eksisting = Upz::query()
                ->when($kode, fn ($q) => $q->orWhere('kode', $kode))
                ->when(! $kode, fn ($q) => $q->where('nama', 'ilike', trim((string) $row['nama']))->where('desa_id', $desa->id))
                ->first();

            app(SimpanUpz::class)->jalankan([
                'id' => $eksisting?->id,
                'kode' => $kode ?? $eksisting?->kode,
                'nama' => $row['nama'],
                'nomor_sk' => $row['nomor_sk'] ?? $eksisting?->nomor_sk,
                'jenis_upz_id' => $jenisUpz->id,
                'desa_id' => $desa->id,
                'ketua' => ! empty($row['ketua']) ? $row['ketua'] : $row['nama'],
                'pic_nama' => ! empty($row['pic_nama']) ? $row['pic_nama'] : (! empty($row['ketua']) ? $row['ketua'] : $row['nama']),
                'pic_telepon' => ! empty($row['pic_telepon']) ? $row['pic_telepon'] : (! empty($row['telepon']) ? $row['telepon'] : '-'),
                'alamat' => ! empty($row['alamat']) ? $row['alamat'] : "Desa {$desa->nama}",
            ]);

            return [];
        } catch (ValidationException $e) {
            return $e->errors();
        }
    }
}
