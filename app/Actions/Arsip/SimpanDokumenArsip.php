<?php

namespace App\Actions\Arsip;

use App\Actions\Perencanaan\PerencanaanAction;
use App\Models\DokumenArsip;
use App\Services\BerkasPrivatService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanDokumenArsip extends PerencanaanAction
{
    public function __construct(
        private readonly BerkasPrivatService $berkasService,
    ) {}

    public function jalankan(array $data): DokumenArsip
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $this->pastikanAksi(
            $id ? 'update' : 'create',
            $id ? 'Anda tidak memiliki izin untuk mengubah dokumen arsip PKP.' : 'Anda tidak memiliki izin untuk mengunggah dokumen arsip PKP.',
        );

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:dokumen_arsip,id'],
            'induk_versi_id' => ['nullable', 'integer', 'exists:dokumen_arsip,id'],
            'kategori_dokumen_id' => ['required', 'integer', 'exists:kategori_dokumen,id'],
            'bidang_id' => ['nullable', 'integer', 'exists:bidang,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'nomor' => ['nullable', 'string', 'max:120'],
            'judul' => ['required', 'string', 'max:255'],
            'tanggal_pengesahan' => ['nullable', 'date'],
            'ringkasan' => ['nullable', 'string', 'max:2000'],
            'berkas' => [
                $id ? 'nullable' : 'required',
                'file',
                'mimetypes:application/pdf',
                'max:20480',
            ],
            'sahkan_langsung' => ['nullable', 'boolean'],
        ], [
            'berkas.mimetypes' => 'Berkas arsip PKP harus berformat PDF.',
            'berkas.max' => 'Ukuran berkas PDF maksimal 20 MB.',
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $data, $id): DokumenArsip {
            if ($id) {
                $dokumen = DokumenArsip::lockForUpdate()->findOrFail($id);

                if ($dokumen->status !== DokumenArsip::STATUS_DRAFT) {
                    throw ValidationException::withMessages([
                        'judul' => 'Dokumen yang sudah disahkan atau dicabut terkunci. Buat versi baru untuk melakukan revisi.',
                    ]);
                }

                $nilaiLama = $dokumen->only([
                    'kategori_dokumen_id',
                    'bidang_id',
                    'tahun',
                    'nomor',
                    'judul',
                    'tanggal_pengesahan',
                    'ringkasan',
                    'berkas_privat_id',
                    'status',
                ]);

                $berkasId = $dokumen->berkas_privat_id;
                if (($data['berkas'] ?? null) instanceof UploadedFile) {
                    $berkasBaru = $this->berkasService->unggah($data['berkas'], 'arsip-pkp', [
                        'entitas_tipe' => DokumenArsip::class,
                        'entitas_id' => $dokumen->id,
                        'bidang_kode' => 'pkp',
                    ]);
                    $berkasId = $berkasBaru->id;
                }

                $dokumen->update([
                    'kategori_dokumen_id' => (int) $tervalidasi['kategori_dokumen_id'],
                    'bidang_id' => ! empty($tervalidasi['bidang_id']) ? (int) $tervalidasi['bidang_id'] : null,
                    'tahun' => (int) $tervalidasi['tahun'],
                    'nomor' => $tervalidasi['nomor'] ?? null,
                    'judul' => $tervalidasi['judul'],
                    'tanggal_pengesahan' => $tervalidasi['tanggal_pengesahan'] ?? null,
                    'ringkasan' => $tervalidasi['ringkasan'] ?? null,
                    'berkas_privat_id' => $berkasId,
                ]);

                $this->catatAudit(
                    'ubah',
                    DokumenArsip::class,
                    $dokumen->id,
                    $nilaiLama,
                    $dokumen->fresh()->only([
                        'kategori_dokumen_id',
                        'bidang_id',
                        'tahun',
                        'nomor',
                        'judul',
                        'tanggal_pengesahan',
                        'ringkasan',
                        'berkas_privat_id',
                        'status',
                    ]),
                );

                return $this->tanganiSahkanLangsung($dokumen, ! empty($tervalidasi['sahkan_langsung']));
            }

            $induk = null;
            $versi = 1;
            if (! empty($tervalidasi['induk_versi_id'])) {
                $induk = DokumenArsip::lockForUpdate()->findOrFail((int) $tervalidasi['induk_versi_id']);
                if ($induk->status !== DokumenArsip::STATUS_DISAHKAN) {
                    throw ValidationException::withMessages([
                        'induk_versi_id' => 'Versi revisi hanya dapat dibuat dari dokumen yang sudah disahkan.',
                    ]);
                }

                $maksVersiInduk = (int) DokumenArsip::query()
                    ->where(function ($q) use ($induk): void {
                        $q->where('id', $induk->id)
                            ->orWhere('induk_versi_id', $induk->id);
                    })
                    ->max('versi');

                $versi = max($induk->versi + 1, $maksVersiInduk + 1);
            }

            /** @var UploadedFile $file */
            $file = $data['berkas'];
            $berkas = $this->berkasService->unggah($file, 'arsip-pkp', [
                'entitas_tipe' => DokumenArsip::class,
                'bidang_kode' => 'pkp',
            ]);

            $dokumen = DokumenArsip::create([
                'kategori_dokumen_id' => (int) $tervalidasi['kategori_dokumen_id'],
                'bidang_id' => ! empty($tervalidasi['bidang_id']) ? (int) $tervalidasi['bidang_id'] : null,
                'tahun' => (int) $tervalidasi['tahun'],
                'nomor' => $tervalidasi['nomor'] ?? null,
                'judul' => $tervalidasi['judul'],
                'versi' => $versi,
                'induk_versi_id' => $induk?->id,
                'tanggal_pengesahan' => $tervalidasi['tanggal_pengesahan'] ?? null,
                'ringkasan' => $tervalidasi['ringkasan'] ?? null,
                'berkas_privat_id' => $berkas->id,
                'status' => DokumenArsip::STATUS_DRAFT,
                'diunggah_oleh' => Auth::id(),
            ]);

            $berkas->update(['entitas_id' => $dokumen->id]);

            $this->catatAudit(
                'buat',
                DokumenArsip::class,
                $dokumen->id,
                null,
                $dokumen->only([
                    'kategori_dokumen_id',
                    'bidang_id',
                    'tahun',
                    'nomor',
                    'judul',
                    'versi',
                    'induk_versi_id',
                    'berkas_privat_id',
                    'status',
                ]),
            );

            return $this->tanganiSahkanLangsung($dokumen, ! empty($tervalidasi['sahkan_langsung']));
        });
    }

    private function tanganiSahkanLangsung(DokumenArsip $dokumen, bool $sahkanLangsung): DokumenArsip
    {
        if (! $sahkanLangsung) {
            return $dokumen;
        }

        $user = Auth::user();
        if (! $user || ! $user->bolehAksiBidang('pkp', 'approve')) {
            return $dokumen;
        }

        return app(SahkanDokumenArsip::class)->jalankan([
            'id' => $dokumen->id,
            'cabut_versi_lama' => true,
        ]);
    }
}
