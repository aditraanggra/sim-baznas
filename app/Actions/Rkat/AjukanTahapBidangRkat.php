<?php

namespace App\Actions\Rkat;

use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\NotifikasiService;
use App\Services\Rkat\AlokasiRkatService;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Mengajukan rincian RKAT bidang ke Kepala Bagian PKP setelah syarat target/pagu terpenuhi 100%. */
class AjukanTahapBidangRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatTahapBidang
    {
        $bidang = (string) ($data['bidang_kode'] ?? '');
        $this->pastikanPenulisBidang($bidang);

        $tervalidasi = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $bidang) {
            $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
            $this->pastikanKolaboratifDraft($versi);
            $tahap = $this->ambilTahapTerkunci($versi, $bidang);
            $this->pastikanTahapDraft($tahap);

            $ringkasan = AlokasiRkatService::ringkasan($versi);

            if ($bidang === 'pengumpulan' && ! $ringkasan['pengumpulan']['terpenuhi']) {
                $selisih = $ringkasan['pengumpulan']['selisih'];
                $this->gagal('tahap', 'Total rincian pengumpulan harus sama persis dengan Target Pengumpulan (selisih saat ini: Rp '.number_format($selisih, 0, ',', '.').').');
            }

            if ($bidang === 'distribusi') {
                if (! $ringkasan['distribusi']['terbuka']) {
                    $this->gagal('tahap', 'Tahap Pengumpulan harus diverifikasi Kepala Bagian PKP terlebih dahulu.');
                }
                if (! $ringkasan['distribusi']['terpenuhi']) {
                    $gagalKelompok = collect($ringkasan['distribusi']['kelompok'])
                        ->where('terpenuhi', false)
                        ->pluck('label')
                        ->implode(', ');
                    $this->gagal('tahap', 'Alokasi penyaluran belum sesuai pagu pada kelompok dana: '.$gagalKelompok.'.');
                }
            }

            if ($bidang === 'umum') {
                if (! $ringkasan['umum']['terbuka']) {
                    $this->gagal('tahap', 'Tahap Pengumpulan harus diverifikasi Kepala Bagian PKP terlebih dahulu.');
                }
                if (! $ringkasan['umum']['terpenuhi']) {
                    $selisih = $ringkasan['umum']['selisih'];
                    $this->gagal('tahap', 'Total belanja operasional SAU harus sama persis dengan pagu operasional (selisih saat ini: Rp '.number_format($selisih, 0, ',', '.').').');
                }
            }

            $tahap->update([
                'status' => RkatTahapBidang::STATUS_DIAJUKAN,
                'diajukan_pada' => now(),
                'diajukan_oleh' => Auth::id(),
            ]);

            SinkronisasiNilaiRkatService::sinkronkan($versi);

            $labelBidang = self::LABEL_BIDANG[$bidang];
            app(NotifikasiService::class)->kirimKePeran(
                'kabag_pkp',
                'rkat_bidang_diajukan',
                "Pengajuan RKAT Bidang {$labelBidang} ({$versi->tahun})",
                "Kepala Bagian {$labelBidang} mengajukan rincian kegiatan RKAT {$versi->tahun} ({$versi->label()}) untuk diverifikasi.",
                route('pkp.rencana.show', $versi->id, false),
                RkatVersion::class,
                $versi->id,
            );

            $this->catatAudit('ajukan_tahap_bidang', RkatTahapBidang::class, $tahap->id, ['status' => RkatTahapBidang::STATUS_DRAFT], [
                'status' => RkatTahapBidang::STATUS_DIAJUKAN,
                'bidang_kode' => $bidang,
                'rkat_version_id' => $versi->id,
            ]);

            return $tahap;
        });
    }
}
