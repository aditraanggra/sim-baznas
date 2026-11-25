<?php

namespace App\Actions\Perencanaan;

use App\Models\RkatVersion;
use App\Models\TahunAnggaran;
use Illuminate\Validation\Rule;

class SimpanTahunAnggaran extends PerencanaanAction
{
    public function jalankan(array $data): TahunAnggaran
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $this->pastikanAksi($id ? 'update' : 'create', 'Anda tidak memiliki izin untuk mengelola tahun anggaran.');

        $tervalidasi = validator($data, [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100', Rule::unique('tahun_anggaran', 'tahun')->ignore($id)],
            'status' => ['required', 'string', Rule::in(TahunAnggaran::STATUS)],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $tahunAnggaran = $id ? TahunAnggaran::findOrFail($id) : new TahunAnggaran;
        $sebelumnya = $tahunAnggaran->exists ? $tahunAnggaran->toArray() : null;

        if ($tervalidasi['status'] === TahunAnggaran::STATUS_BERJALAN) {
            TahunAnggaran::where('status', TahunAnggaran::STATUS_BERJALAN)
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->update(['status' => TahunAnggaran::STATUS_DRAFT]);
        }

        if (! $tahunAnggaran->exists) {
            $tervalidasi = $this->denganPembuat($tervalidasi);
        }

        $tahunAnggaran->fill($tervalidasi);
        $tahunAnggaran->save();

        RkatVersion::where('tahun', $tahunAnggaran->tahun)
            ->whereNull('tahun_anggaran_id')
            ->update(['tahun_anggaran_id' => $tahunAnggaran->id]);

        $this->catatAudit(
            $sebelumnya ? 'ubah' : 'tambah',
            TahunAnggaran::class,
            $tahunAnggaran->id,
            $sebelumnya,
            $tahunAnggaran->fresh()->toArray()
        );

        return $tahunAnggaran;
    }
}
