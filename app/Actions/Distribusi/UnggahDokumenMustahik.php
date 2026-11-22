<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Mustahik;
use App\Models\MustahikDokumen;
use App\Services\BerkasPrivatService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnggahDokumenMustahik extends Action
{
    use MencatatAudit;

    public function __construct(private readonly BerkasPrivatService $berkasService) {}

    public function jalankan(array $data): MustahikDokumen
    {
        $tervalidasi = validator($data, [
            'mustahik_id' => ['required', 'integer', 'exists:mustahik,id'],
            'jenis_dokumen_id' => ['required', 'integer', 'exists:jenis_dokumen,id'],
            'berkas' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): MustahikDokumen {
            $mustahik = Mustahik::findOrFail($tervalidasi['mustahik_id']);

            /** @var UploadedFile $berkas */
            $berkas = $tervalidasi['berkas'];
            $berkasPrivat = $this->berkasService->unggah($berkas, 'mustahik', [
                'entitas_tipe' => Mustahik::class,
                'entitas_id' => $mustahik->id,
                'bidang_kode' => 'distribusi',
            ]);

            $dokumen = MustahikDokumen::create([
                'mustahik_id' => $mustahik->id,
                'jenis_dokumen_id' => $tervalidasi['jenis_dokumen_id'],
                'berkas_privat_id' => $berkasPrivat->id,
                'keterangan' => $tervalidasi['keterangan'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->catatAudit(
                'unggah_dokumen',
                MustahikDokumen::class,
                $dokumen->id,
                null,
                $dokumen->only(['mustahik_id', 'jenis_dokumen_id', 'berkas_privat_id']),
            );

            return $dokumen->fresh(['jenisDokumen', 'berkasPrivat']);
        });
    }
}
