<?php

namespace App\Actions\Upz;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Upz;
use Illuminate\Support\Facades\DB;

class ToggleAktifUpz extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): Upz
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:upz,id'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): Upz {
            $upz = Upz::findOrFail($tervalidasi['id']);
            $lama = ['aktif' => $upz->aktif];
            $statusBaru = ! $upz->aktif;

            $upz->forceFill(['aktif' => $statusBaru])->save();

            $this->catatAudit(
                $statusBaru ? 'aktifkan' : 'nonaktifkan',
                Upz::class,
                $upz->id,
                $lama,
                ['aktif' => $statusBaru],
                $tervalidasi['alasan'] ?? null,
            );

            return $upz;
        });
    }
}
