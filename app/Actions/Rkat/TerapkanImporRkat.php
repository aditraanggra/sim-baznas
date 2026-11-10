<?php

namespace App\Actions\Rkat;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use App\Services\Rkat\HitungRkat;
use App\Services\Rkat\PemetaanPos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * FR-R-03 langkah 3: terapkan baris valid dari pratinjau ke rkat_values (upsert pada (versi, kode_asal, tipe): idempoten).
 * Baris pos induk/rumus tidak disimpan (BR-03), hanya dibandingkan dengan hitungan server sebagai peringatan.
 * Kode tak terpetakan disimpan dengan pos_anggaran_id null dan tidak ikut total (BR-10).
 */
class TerapkanImporRkat extends RkatAction
{
    /** @return array{versi: RkatVersion, diterapkan: int, tak_terpetakan: list<string>, peringatan_subtotal: list<array>} */
    public function jalankan(array $data): array
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'import_job_id' => ['required', 'integer', 'exists:import_jobs,id'],
            'rkat_version_id' => ['nullable', 'integer', 'exists:rkat_versions,id'],
        ])->validate();

        $job = ImportJob::findOrFail($data['import_job_id']);
        if ($job->jenis !== 'rkat') {
            $this->gagal('import_job_id', 'Job impor bukan jenis RKAT.');
        }

        $baris = ImportRow::where('import_job_id', $job->id)->where('jenis', 'rkat')->get();
        if ($baris->count() !== $job->total_baris) {
            $this->gagal('import_job_id', 'Pratinjau sudah digantikan impor yang lebih baru; unggah ulang berkas.');
        }
        $tahun = (int) ($baris->first()?->data['tahun'] ?? 0);
        $valid = $baris->where('status', 'valid');
        if ($valid->isEmpty()) {
            $this->gagal('import_job_id', 'Tidak ada baris valid untuk diterapkan.');
        }

        return DB::transaction(function () use ($data, $tahun, $valid) {
            if (isset($data['rkat_version_id'])) {
                $versi = $this->versiTerkunci($data['rkat_version_id']);
                if ($versi->tahun !== $tahun) {
                    $this->gagal('rkat_version_id', "Berkas tahun $tahun tidak dapat dimasukkan ke versi tahun {$versi->tahun}.");
                }
                $this->pastikanDraft($versi);
                $sumber = $versi->sumber === 'impor' || ! $versi->nilai()->exists() ? 'impor' : 'manual';
                $versi->update(['sumber' => $sumber]);
            } else {
                if (RkatVersion::where(['tahun' => $tahun, 'jenis' => 'awal'])->exists()) {
                    $this->gagal('rkat_version_id', "RKAT Awal tahun $tahun sudah ada; pilih versi Draft tujuan.");
                }
                $versi = RkatVersion::create([
                    'tahun' => $tahun, 'jenis' => 'awal', 'nomor_perubahan' => 0, 'status' => 'draft',
                    'sumber' => 'impor', 'dibuat_oleh' => Auth::id(),
                ]);
            }

            $peta = PemetaanPos::untuk($tahun, $valid->pluck('data.kode_pos')->all());
            $diterapkan = 0;
            $takTerpetakan = [];
            $subtotal = [];

            foreach ($valid as $row) {
                $d = $row->data;
                $pos = $peta[$d['kode_pos']];

                if ($d['subtotal'] || ($pos && $pos->agregasi !== 'input_langsung')) {
                    if ($pos) {
                        $subtotal[$pos->kode] = (int) $d['nilai'];
                    }

                    continue;
                }

                RkatValue::updateOrCreate(
                    ['rkat_version_id' => $versi->id, 'kode_asal' => $d['kode_pos'], 'tipe_nilai' => $d['tipe_nilai']],
                    ['pos_anggaran_id' => $pos?->id, 'nama_asal' => $d['nama'] ?? null, 'nilai' => (int) $d['nilai']],
                );
                $diterapkan++;
                if (! $pos) {
                    $takTerpetakan[] = $d['kode_pos'];
                }
            }

            $hitung = HitungRkat::untukVersi($versi)['per_kode'];
            $peringatan = [];
            foreach ($subtotal as $kode => $nilaiBerkas) {
                if (($hitung[$kode] ?? null) !== $nilaiBerkas) {
                    $peringatan[] = ['kode' => $kode, 'berkas' => $nilaiBerkas, 'hitung' => $hitung[$kode] ?? null];
                }
            }

            $this->catatAudit('impor_rkat', RkatVersion::class, $versi->id, null, [
                'import_job_id' => $data['import_job_id'], 'tahun' => $tahun, 'diterapkan' => $diterapkan,
                'tak_terpetakan' => $takTerpetakan, 'peringatan_subtotal' => count($peringatan),
            ]);

            return ['versi' => $versi, 'diterapkan' => $diterapkan, 'tak_terpetakan' => $takTerpetakan, 'peringatan_subtotal' => $peringatan];
        });
    }
}
