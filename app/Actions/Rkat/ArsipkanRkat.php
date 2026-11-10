<?php

namespace App\Actions\Rkat;

use App\Models\RkatVersion;
use Illuminate\Support\Facades\DB;

/** BR-11: tanpa hard delete; versi diarsipkan. Versi Diajukan/Disahkan tidak dapat diarsipkan. */
class ArsipkanRkat extends RkatAction
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
            if (! in_array($versi->status, ['draft', 'digantikan'], true)) {
                $this->gagal('versi', "Versi berstatus {$versi->status} tidak dapat diarsipkan.");
            }
            $versi->update(['arsip' => true]);
            $this->catatAudit('arsip', RkatVersion::class, $versi->id, ['arsip' => false], ['arsip' => true], $data['alasan'] ?? null);

            return $versi;
        });
    }
}
