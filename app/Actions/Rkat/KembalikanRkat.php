<?php

namespace App\Actions\Rkat;

use App\Models\RkatVersion;
use Illuminate\Support\Facades\DB;

/** Diajukan -> Draft (sebelum pengesahan), agar nilai dapat dikoreksi. */
class KembalikanRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'alasan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            if ($versi->status !== 'diajukan') {
                $this->gagal('versi', 'Hanya versi Diajukan yang dapat dikembalikan ke Draft.');
            }
            $versi->update(['status' => 'draft']);
            $this->catatAudit('kembalikan', RkatVersion::class, $versi->id, ['status' => 'diajukan'], ['status' => 'draft'], $data['alasan'] ?? null);

            return $versi;
        });
    }
}
