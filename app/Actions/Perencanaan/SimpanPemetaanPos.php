<?php

namespace App\Actions\Perencanaan;

use App\Models\PosAnggaranPemetaan;
use Illuminate\Validation\Rule;

class SimpanPemetaanPos extends PerencanaanAction
{
    public function jalankan(array $data): PosAnggaranPemetaan
    {
        $this->pastikanAksi('update', 'Anda tidak memiliki izin untuk mengelola pemetaan kegiatan pos anggaran.');

        $tervalidasi = validator($data, [
            'pos_anggaran_id' => ['required', 'integer', 'exists:pos_anggaran,id'],
            'bidang_id' => ['required', 'integer', 'exists:bidang,id'],
            'sumber_transaksi' => ['required', 'string', Rule::in(PosAnggaranPemetaan::SUMBER_TRANSAKSI)],
            'jenis_dana_id' => ['nullable', 'integer', 'exists:jenis_dana,id'],
            'sumber_dana_id' => ['nullable', 'integer', 'exists:sumber_dana,id'],
            'asnaf_id' => ['nullable', 'integer', 'exists:asnaf,id'],
            'program_id' => ['nullable', 'integer', 'exists:program,id'],
            'filter_via_upz' => ['nullable', 'boolean'],
            'filter_jenis_muzakki' => ['nullable', 'string', Rule::in(['perorangan', 'lembaga'])],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'aktif' => ['boolean'],
        ])->validate();

        $pemetaan = PosAnggaranPemetaan::firstOrNew(['pos_anggaran_id' => $tervalidasi['pos_anggaran_id']]);
        $sebelumnya = $pemetaan->exists ? $pemetaan->toArray() : null;

        if (! $pemetaan->exists) {
            $tervalidasi = $this->denganPembuat($tervalidasi);
        }

        $pemetaan->fill($tervalidasi);
        $pemetaan->save();

        $this->catatAudit(
            $sebelumnya ? 'ubah' : 'tambah',
            PosAnggaranPemetaan::class,
            $pemetaan->id,
            $sebelumnya,
            $pemetaan->fresh()->toArray()
        );

        return $pemetaan;
    }
}
