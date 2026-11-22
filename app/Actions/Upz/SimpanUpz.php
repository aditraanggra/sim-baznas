<?php

namespace App\Actions\Upz;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Upz;
use App\Services\IdentitasPribadiService;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimpanUpz extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly PenomoranService $penomoran,
        private readonly IdentitasPribadiService $identitas,
    ) {}

    public function jalankan(array $data): Upz
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $data['nomor_sk'] ??= $data['sk'] ?? null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:upz,id'],
            'kode' => ['nullable', 'string', 'max:32', Rule::unique('upz', 'kode')->ignore($id)],
            'nama' => ['required', 'string', 'max:160'],
            'nomor_sk' => ['nullable', 'string', 'max:128'],
            'jenis_upz_id' => ['required', 'integer', 'exists:jenis_upz,id'],
            'desa_id' => ['required', 'integer', 'exists:desa,id'],
            'ketua' => ['required', 'string', 'max:128'],
            'sekretaris' => ['nullable', 'string', 'max:128'],
            'bendahara' => ['nullable', 'string', 'max:128'],
            'alamat' => ['required', 'string', 'max:500'],
            'pic_nama' => ['required', 'string', 'max:128'],
            'pic_telepon' => ['required', 'string', 'max:32'],
            'aktif' => ['nullable', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $id): Upz {
            $upz = $id ? Upz::findOrFail($id) : new Upz;
            $lama = $upz->exists ? $upz->toArray() : null;

            $kode = ! empty($tervalidasi['kode'])
                ? strtoupper(trim((string) $tervalidasi['kode']))
                : ($upz->kode ?: $this->penomoran->berikutnya('upz'));

            $upz->fill([
                ...$tervalidasi,
                'kode' => $kode,
                'nomor_sk' => ! empty($tervalidasi['nomor_sk']) ? trim((string) $tervalidasi['nomor_sk']) : null,
                'pic_telepon' => $this->identitas->normalisasiTelepon($tervalidasi['pic_telepon']) ?? $tervalidasi['pic_telepon'],
                'aktif' => array_key_exists('aktif', $tervalidasi) ? (bool) $tervalidasi['aktif'] : ($upz->exists ? $upz->aktif : true),
            ]);

            if (! $upz->exists) {
                $upz->created_by ??= auth()->id();
            }

            $upz->save();

            $this->catatAudit(
                $upz->wasRecentlyCreated ? 'buat' : 'ubah',
                Upz::class,
                $upz->id,
                $lama,
                $upz->fresh()->toArray(),
            );

            return $upz;
        });
    }
}
