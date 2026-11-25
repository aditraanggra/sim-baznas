<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Desa;
use App\Models\Muzakki;
use App\Services\IdentitasPribadiService;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SimpanMuzakki extends Action
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

        return Muzakki::query()
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
            ->map(function (Muzakki $m) use ($nikHash, $telpNorm): array {
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

    public function jalankan(array $data): Muzakki
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $data['npwz'] = isset($data['npwz']) && trim((string) $data['npwz']) !== ''
            ? trim((string) $data['npwz'])
            : null;
        $data['jenis_kelamin'] = isset($data['jenis_kelamin']) && trim((string) $data['jenis_kelamin']) !== ''
            ? strtoupper(trim((string) $data['jenis_kelamin']))
            : null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:muzakki,id'],
            'kode' => ['nullable', 'string', 'max:32', Rule::unique('muzakki', 'kode')->ignore($id)],
            'npwz' => ['nullable', 'string', 'max:32', Rule::unique('muzakki', 'npwz')->ignore($id)],
            'jenis' => ['required', Rule::in(['perorangan', 'lembaga'])],
            'nama' => ['required', 'string', 'max:160'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'nik' => ['nullable', 'digits:16'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'telepon' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:160'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kecamatan_id' => ['nullable', 'integer', 'exists:kecamatan,id'],
            'desa_id' => ['nullable', 'integer', 'exists:desa,id'],
            'aktif' => ['nullable', 'boolean'],
        ])->validate();

        $nikBaru = $this->identitas->bersihkanDigit($tervalidasi['nik'] ?? null);
        $nikHash = $nikBaru !== '' ? $this->identitas->hashPencarian($nikBaru) : null;

        if ($nikHash !== null) {
            $duplikatNik = Muzakki::query()
                ->where('nik_hash', $nikHash)
                ->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
                ->first();

            if ($duplikatNik) {
                throw ValidationException::withMessages([
                    'nik' => "NIK sudah terdaftar atas nama {$duplikatNik->nama} ({$duplikatNik->kode}).",
                ]);
            }
        }

        return DB::transaction(function () use ($tervalidasi, $id, $nikBaru, $nikHash): Muzakki {
            $muzakki = $id ? Muzakki::findOrFail($id) : new Muzakki;
            $lama = $muzakki->exists ? $muzakki->only(['kode', 'npwz', 'jenis', 'nama', 'jenis_kelamin', 'nik_masked', 'telepon', 'desa_id', 'aktif']) : null;

            $kecamatanId = $tervalidasi['kecamatan_id'] ?? null;
            if ($kecamatanId === null && ! empty($tervalidasi['desa_id'])) {
                $kecamatanId = Desa::whereKey($tervalidasi['desa_id'])->value('kecamatan_id');
            }

            $kode = ! empty($tervalidasi['kode'])
                ? strtoupper(trim((string) $tervalidasi['kode']))
                : ($muzakki->kode ?: $this->penomoran->berikutnya('muzakki'));

            $telpNorm = $this->identitas->normalisasiTelepon($tervalidasi['telepon'] ?? null);

            $atribut = [
                'kode' => $kode,
                'npwz' => $tervalidasi['npwz'] ?? null,
                'jenis' => $tervalidasi['jenis'],
                'nama' => trim((string) $tervalidasi['nama']),
                'jenis_kelamin' => $tervalidasi['jenis'] === 'lembaga' ? null : ($tervalidasi['jenis_kelamin'] ?? null),
                'npwp' => $tervalidasi['npwp'] ?? null,
                'telepon' => $tervalidasi['telepon'] ?? null,
                'telepon_norm' => $telpNorm,
                'email' => $tervalidasi['email'] ?? null,
                'alamat' => $tervalidasi['alamat'] ?? null,
                'kecamatan_id' => $kecamatanId,
                'desa_id' => $tervalidasi['desa_id'] ?? null,
                'aktif' => array_key_exists('aktif', $tervalidasi) ? (bool) $tervalidasi['aktif'] : ($muzakki->exists ? $muzakki->aktif : true),
            ];

            if ($nikBaru !== '') {
                $atribut['nik_encrypted'] = $this->identitas->enkripsi($nikBaru);
                $atribut['nik_hash'] = $nikHash;
                $atribut['nik_masked'] = $this->identitas->maskNik($nikBaru);
            }

            $muzakki->fill($atribut);
            if (! $muzakki->exists) {
                $muzakki->created_by ??= auth()->id();
            }
            $muzakki->save();

            $this->catatAudit(
                $muzakki->wasRecentlyCreated ? 'buat' : 'ubah',
                Muzakki::class,
                $muzakki->id,
                $lama,
                $muzakki->only(['kode', 'npwz', 'jenis', 'nama', 'jenis_kelamin', 'nik_masked', 'telepon', 'desa_id', 'aktif']),
            );

            return $muzakki;
        });
    }
}
