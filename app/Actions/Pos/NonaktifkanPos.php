<?php

namespace App\Actions\Pos;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\PosAnggaran;

class NonaktifkanPos extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): PosAnggaran
    {
        $data = validator($data, [
            'id' => ['required', 'integer', 'exists:pos_anggaran,id'],
            'alasan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $pos = PosAnggaran::findOrFail($data['id']);
        $lama = ['aktif' => $pos->aktif];

        $pos->update(['aktif' => false]);

        $this->catatAudit('nonaktif', PosAnggaran::class, $pos->id, $lama, ['aktif' => false], $data['alasan'] ?? null);

        return $pos;
    }
}
