<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Desa;
use App\Models\Mustahik;
use App\Models\MustahikAsnaf;
use App\Services\IdentitasPribadiService;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SimpanMustahik extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly IdentitasPribadiService $identitas,
        private readonly PenomoranService $penomoran,
    ) {}

    /**
     * @return list<array{id: int, kode: string, nama: string, nik_masked: string|null, telepon: string|null, alasan: string}>
     */
    public function cekKemungkinanDuplikat(?string $nik, ?string $telepon, ?int $ignoreId = null): array
    {
        $nikHash = $this->identitas->hashPencarian($nik);
        $telpNorm = $this->identitas->normalisasiTelepon($telepon);

        if ($nikHash === null && $telpNorm === null) {
            return [];
        }

        return Mustahik::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($nikHash, $telpNorm): void {
                if ($nikHash !== null) {
                    $q->orWhere('nik_hash', $nikHash);
                }
                if ($telpNorm !== null) {
                    $q->orWhere('telepon_norm', $telpNorm);
                }
            })
            ->limit(5)
            ->get()
            ->map(function (Mustahik $m) use ($nikHash, $telpNorm): array {
                $alasan = [];
                if ($nikHash !== null && $m->nik_hash === $nikHash) {
                    $alasan[] = 'NIK sama';
                }
                if ($telpNorm !== null && $m->telepon_norm === $telpNorm) {
                    $alasan[] = 'Telepon sama';
                }

                return [
                    'id' => $m->id,
                    'kode' => $m->kode,
                    'nama' => $m->nama,
                    'nik_masked' => $m->nik_masked,
                    'telepon' => $m->telepon,
                    'alasan' => implode(' & ', $alasan),
                ];
            })
            ->all();
    }

    public function jalankan(array $data): Mustahik
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:mustahik,id'],
            'kode' => ['nullable', 'string', 'max:32', Rule::unique('mustahik', 'kode')->ignore($id)],
            'jenis' => ['required', Rule::in(['perorangan', 'lembaga'])],
            'nama' => ['required', 'string', 'max:160'],
            'nik' => ['nullable', 'digits:16'],
            'telepon' => ['nullable', 'string', 'max:32'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kecamatan_id' => ['nullable', 'integer', 'exists:kecamatan,id'],
            'desa_id' => ['nullable', 'integer', 'exists:desa,id'],
            'penanggung_jawab' => ['nullable', 'string', 'max:160'],
            'asnaf_ids' => ['nullable', 'array'],
            'asnaf_ids.*' => ['integer', 'exists:asnaf,id'],
            'asnaf_utama_id' => ['nullable', 'integer', 'exists:asnaf,id'],
            'aktif' => ['nullable', 'boolean'],
        ])->validate();

        $nikBaru = $this->identitas->bersihkanDigit($tervalidasi['nik'] ?? null);
        $nikHash = $nikBaru !== '' ? $this->identitas->hashPencarian($nikBaru) : null;

        if ($nikHash !== null) {
            $duplikatNik = Mustahik::query()
                ->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
                ->where('nik_hash', $nikHash)
                ->first();

            if ($duplikatNik) {
                throw ValidationException::withMessages([
                    'nik' => "NIK sudah terdaftar atas nama mustahik {$duplikatNik->nama} ({$duplikatNik->kode}).",
                ]);
            }
        }

        return DB::transaction(function () use ($id, $tervalidasi, $nikBaru, $nikHash): Mustahik {
            $mustahik = $id ? Mustahik::lockForUpdate()->findOrFail($id) : new Mustahik;
            $lama = $id ? $mustahik->only(['kode', 'jenis', 'nama', 'nik_masked', 'telepon', 'kecamatan_id', 'desa_id', 'aktif']) : null;

            $desaId = $tervalidasi['desa_id'] ?? null;
            $kecamatanId = $tervalidasi['kecamatan_id'] ?? null;
            if ($desaId && ! $kecamatanId) {
                $kecamatanId = Desa::find($desaId)?->kecamatan_id;
            }

            $kode = ! empty($tervalidasi['kode'])
                ? strtoupper(trim((string) $tervalidasi['kode']))
                : ($mustahik->kode ?: $this->penomoran->berikutnya('mustahik'));

            $payload = [
                'kode' => $kode,
                'jenis' => $tervalidasi['jenis'],
                'nama' => trim((string) $tervalidasi['nama']),
                'telepon' => $tervalidasi['telepon'] ?? null,
                'telepon_norm' => $this->identitas->normalisasiTelepon($tervalidasi['telepon'] ?? null),
                'alamat' => $tervalidasi['alamat'] ?? null,
                'kecamatan_id' => $kecamatanId,
                'desa_id' => $desaId,
                'penanggung_jawab' => $tervalidasi['penanggung_jawab'] ?? null,
                'aktif' => $tervalidasi['aktif'] ?? ($mustahik->exists ? $mustahik->aktif : true),
            ];

            if ($nikBaru !== '') {
                $payload['nik_encrypted'] = $this->identitas->enkripsi($nikBaru);
                $payload['nik_hash'] = $nikHash;
                $payload['nik_masked'] = $this->identitas->maskNik($nikBaru);
            }

            $mustahik->fill($payload);
            $mustahik->save();

            if (array_key_exists('asnaf_ids', $tervalidasi)) {
                $ids = collect($tervalidasi['asnaf_ids'] ?? [])->map(fn ($v) => (int) $v)->unique()->values();
                $utamaId = isset($tervalidasi['asnaf_utama_id']) ? (int) $tervalidasi['asnaf_utama_id'] : $ids->first();

                MustahikAsnaf::where('mustahik_id', $mustahik->id)->delete();
                foreach ($ids as $aid) {
                    MustahikAsnaf::create([
                        'mustahik_id' => $mustahik->id,
                        'asnaf_id' => $aid,
                        'utama' => $aid === $utamaId,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $this->catatAudit(
                $id ? 'ubah' : 'buat',
                Mustahik::class,
                $mustahik->id,
                $lama,
                $mustahik->only(['kode', 'jenis', 'nama', 'nik_masked', 'telepon', 'kecamatan_id', 'desa_id', 'aktif']),
            );

            return $mustahik->fresh(['asnaf', 'kecamatan', 'desa']);
        });
    }
}
