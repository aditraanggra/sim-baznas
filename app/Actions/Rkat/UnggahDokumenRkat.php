<?php

namespace App\Actions\Rkat;

use App\Models\RkatDocument;
use App\Models\RkatVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** FR-R-05: PDF surat pengesahan / lembar penetapan. Berkas lama jenis yang sama diarsipkan (BR-11). Boleh pada versi Disahkan (O-6). */
class UnggahDokumenRkat extends RkatAction
{
    public function jalankan(array $data): RkatDocument
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'jenis' => ['required', Rule::in(RkatDocument::JENIS)],
            'berkas' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = RkatVersion::findOrFail($data['rkat_version_id']);
            $berkas = $data['berkas'];

            RkatDocument::where(['rkat_version_id' => $versi->id, 'jenis' => $data['jenis']])
                ->whereNull('diarsipkan_pada')->update(['diarsipkan_pada' => now()]);

            $dokumen = RkatDocument::create([
                'rkat_version_id' => $versi->id,
                'jenis' => $data['jenis'],
                'nama_berkas' => $berkas->getClientOriginalName(),
                'path' => $berkas->store("rkat/{$versi->id}"),
                'mime' => 'application/pdf',
                'ukuran' => $berkas->getSize(),
                'diunggah_oleh' => Auth::id(),
            ]);

            $this->catatAudit('unggah_dokumen', RkatVersion::class, $versi->id, null, [
                'jenis' => $dokumen->jenis, 'nama_berkas' => $dokumen->nama_berkas, 'dokumen_id' => $dokumen->id,
            ]);

            return $dokumen;
        });
    }
}
