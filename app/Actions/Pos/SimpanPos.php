<?php

namespace App\Actions\Pos;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\PosAnggaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimpanPos extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): PosAnggaran
    {
        $data = validator($data, [
            'id' => ['nullable', 'integer', 'exists:pos_anggaran,id'],
            'kode' => ['required', 'string', 'max:32', 'regex:/^L\d\//'],
            'tipe_nilai' => ['required', Rule::in(['rupiah', 'orang', 'badan', 'jiwa', 'unit', 'persen'])],
            'nama' => ['required', 'string', 'max:255'],
            'induk_id' => ['nullable', 'integer', 'exists:pos_anggaran,id'],
            'lampiran' => ['required', 'string', 'max:2'],
            'tingkat' => ['required', 'integer', 'between:2,7'],
            'agregasi' => ['required', Rule::in(['jumlah_anak', 'input_langsung', 'hitung'])],
            'sifat_waktu' => ['required', Rule::in(['aliran', 'stok'])],
            'rumus' => ['nullable', 'string', 'max:255'],
            'berlaku_dari_tahun' => ['required', 'integer', 'between:2000,2100'],
            'berlaku_sampai_tahun' => ['nullable', 'integer', 'gte:berlaku_dari_tahun'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $pos = isset($data['id']) ? PosAnggaran::findOrFail($data['id']) : new PosAnggaran;
            $lama = $pos->exists ? $pos->only((new PosAnggaran)->getFillable()) : null;

            $pos->fill($data);
            $pos->path = $this->hitungPath($pos);
            $pos->urutan ??= 0;
            $pos->save();

            $this->catatAudit(
                $pos->wasRecentlyCreated ? 'buat' : 'ubah',
                PosAnggaran::class,
                $pos->id,
                $lama,
                $pos->fresh()->only((new PosAnggaran)->getFillable()),
            );

            return $pos;
        });
    }

    private function hitungPath(PosAnggaran $pos): string
    {
        if (! $pos->induk_id) {
            return $pos->kode;
        }

        return PosAnggaran::findOrFail($pos->induk_id)->path.'/'.$pos->kode;
    }
}
