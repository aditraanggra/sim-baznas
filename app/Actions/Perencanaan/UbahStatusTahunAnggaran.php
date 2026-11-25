<?php

namespace App\Actions\Perencanaan;

use App\Models\TahunAnggaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UbahStatusTahunAnggaran extends PerencanaanAction
{
    public function jalankan(array $data): TahunAnggaran
    {
        $this->pastikanAksi('approve', 'Anda tidak memiliki izin untuk mengubah status tahun anggaran.');

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:tahun_anggaran,id'],
            'status' => ['required', 'string', Rule::in(TahunAnggaran::STATUS)],
            'alasan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $th = TahunAnggaran::findOrFail($tervalidasi['id']);
        $sebelumnya = $th->toArray();

        if ($tervalidasi['status'] === TahunAnggaran::STATUS_TUTUP) {
            $th->ditutup_pada = now();
            $th->ditutup_oleh = Auth::id();
        } else {
            $th->ditutup_pada = null;
            $th->ditutup_oleh = null;
        }

        if ($tervalidasi['status'] === TahunAnggaran::STATUS_BERJALAN) {
            TahunAnggaran::where('status', TahunAnggaran::STATUS_BERJALAN)
                ->where('id', '!=', $th->id)
                ->update(['status' => TahunAnggaran::STATUS_DRAFT]);
        }

        $th->status = $tervalidasi['status'];
        if (! empty($tervalidasi['alasan'])) {
            $th->catatan = ($th->catatan ? $th->catatan."\n" : '').'['.now()->format('Y-m-d H:i').'] Status '.$th->status.': '.$tervalidasi['alasan'];
        }
        $th->save();

        $this->catatAudit(
            'ubah_status',
            TahunAnggaran::class,
            $th->id,
            $sebelumnya,
            $th->fresh()->toArray(),
            $tervalidasi['alasan'] ?? null
        );

        return $th;
    }
}
