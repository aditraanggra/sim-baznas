<?php

namespace App\Actions\Rkat;

use App\Models\RkatNote;
use App\Models\RkatVersion;
use Illuminate\Support\Facades\DB;

/** FR-R-06: catatan pengesahan sebagai data (batas hak amil dalam basis poin, target). */
class SimpanCatatanRkat extends RkatAction
{
    public function jalankan(array $data): RkatNote
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'batas_amil_zakat_bp' => ['nullable', 'integer', 'between:0,10000'],
            'batas_amil_infak_bp' => ['nullable', 'integer', 'between:0,10000'],
            'batas_amil_csr_bp' => ['nullable', 'integer', 'between:0,10000'],
            'batas_amil_dskl_bp' => ['nullable', 'integer', 'between:0,10000'],
            'target_orang' => ['nullable', 'integer', 'min:0'],
            'target_pengentasan_ekstrem' => ['nullable', 'integer', 'min:0'],
            'target_pengentasan_garis' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            $this->pastikanDraft($versi);

            $note = RkatNote::firstOrNew(['rkat_version_id' => $versi->id]);
            $lama = $note->exists ? $note->only(RkatNote::KOLOM) : null;

            $nilaiBaru = $versi->isKolaboratif()
                ? ['catatan' => $data['catatan'] ?? null]
                : array_intersect_key($data, array_flip(RkatNote::KOLOM));

            $note->fill($nilaiBaru)->save();

            $this->catatAudit('ubah_catatan', RkatVersion::class, $versi->id, $lama, $note->only(RkatNote::KOLOM));

            return $note;
        });
    }
}
