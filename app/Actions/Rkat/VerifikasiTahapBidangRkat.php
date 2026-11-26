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

/** Verifikasi rincian RKAT bidang oleh Kepala Bagian PKP (atau Admin). */
class VerifikasiTahapBidangRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatTahapBidang
    {
        $this->pastikanVerifikatorPkp();

        $tervalidasi = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
        ])->validate();

        $bidang = $tervalidasi['bidang_kode'];

        return DB::transaction(function () use ($tervalidasi, $bidang) {
            $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
            $this->pastikanKolaboratifDraft($versi);
            $tahap = $this->ambilTahapTerkunci($versi, $bidang);

            if ($tahap->status !== RkatTahapBidang::STATUS_DIAJUKAN) {
                $this->gagal('tahap', "Hanya tahap berstatus Diajukan yang dapat diverifikasi (status saat ini: {$tahap->status}).");
            }

            $ringkasan = AlokasiRkatService::ringkasan($versi);
            if ($bidang === 'pengumpulan' && ! $ringkasan['pengumpulan']['terpenuhi']) {
                $this->gagal('tahap', 'Total Pengumpulan belum sama persis dengan target pengumpulan.');
            }
            if ($bidang === 'distribusi' && (! $ringkasan['distribusi']['terbuka'] || ! $ringkasan['distribusi']['terpenuhi'])) {
                $this->gagal('tahap', 'Rincian Penyaluran belum sesuai pagu kelompok dana.');
            }
            if ($bidang === 'umum' && (! $ringkasan['umum']['terbuka'] || ! $ringkasan['umum']['terpenuhi'])) {
                $this->gagal('tahap', 'Rincian Operasional SAU belum sesuai pagu operasional.');
            }

            $tahap->update([
                'status' => RkatTahapBidang::STATUS_TERVERIFIKASI,
                'diverifikasi_pada' => now(),
                'diverifikasi_oleh' => Auth::id(),
                'catatan_verifikasi' => null,
            ]);

            $notif = app(NotifikasiService::class);

            if ($bidang === 'pengumpulan') {
                RkatTahapBidang::query()
                    ->where('rkat_version_id', $versi->id)
                    ->whereIn('bidang_kode', ['distribusi', 'umum'])
                    ->where('status', RkatTahapBidang::STATUS_MENUNGGU)
                    ->update(['status' => RkatTahapBidang::STATUS_DRAFT]);

                SinkronisasiNilaiRkatService::sinkronkan($versi);

                $notif->kirimKePeran(
                    'kabag_pengumpulan',
                    'rkat_bidang_terverifikasi',
                    "RKAT Pengumpulan {$versi->tahun} Terverifikasi",
                    "Rincian RKAT Pengumpulan {$versi->tahun} ({$versi->label()}) telah diverifikasi oleh Kepala Bagian PKP.",
                    route('pengumpulan.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );

                $notif->kirimKePeran(
                    'kabag_pendis_pendag',
                    'rkat_pagu_terbuka',
                    "Pagu Penyaluran RKAT {$versi->tahun} Terbuka",
                    'Target pengumpulan telah diverifikasi Kabag PKP. Total pagu penyaluran sebesar Rp '.number_format($ringkasan['pengumpulan']['total_alokasi_penyaluran'], 0, ',', '.').' siap dirinci.',
                    route('distribusi.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );

                $notif->kirimKePeran(
                    'kabag_sau',
                    'rkat_pagu_terbuka',
                    "Pagu Operasional SAU RKAT {$versi->tahun} Terbuka",
                    'Target pengumpulan telah diverifikasi Kabag PKP. Pagu Hak Amil sebesar Rp '.number_format($ringkasan['pengumpulan']['total_hak_amil'], 0, ',', '.').' siap dirinci.',
                    route('umum.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );
            } else {
                SinkronisasiNilaiRkatService::sinkronkan($versi);

                $peranTujuan = $bidang === 'distribusi' ? 'kabag_pendis_pendag' : 'kabag_sau';
                $ruteTujuan = $bidang === 'distribusi' ? 'distribusi.rkat.index' : 'umum.rkat.index';
                $labelBidang = self::LABEL_BIDANG[$bidang];

                $notif->kirimKePeran(
                    $peranTujuan,
                    'rkat_bidang_terverifikasi',
                    "RKAT {$labelBidang} {$versi->tahun} Terverifikasi",
                    "Rincian RKAT {$labelBidang} {$versi->tahun} ({$versi->label()}) telah diverifikasi oleh Kepala Bagian PKP.",
                    route($ruteTujuan, ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );

                $semuaTerverifikasi = RkatTahapBidang::where('rkat_version_id', $versi->id)
                    ->where('status', RkatTahapBidang::STATUS_TERVERIFIKASI)
                    ->count() === 3;

                if ($semuaTerverifikasi) {
                    $notif->kirimKePeran(
                        ['perencanaan', 'kabag_pkp'],
                        'rkat_siap_diajukan',
                        "Konsolidasi RKAT {$versi->tahun} Siap Diajukan",
                        "Ketiga bidang telah terverifikasi pada RKAT {$versi->tahun} ({$versi->label()}). Silakan periksa validasi dan ajukan pengesahan.",
                        route('pkp.rencana.show', $versi->id, false),
                        RkatVersion::class,
                        $versi->id,
                    );
                }
            }

            $this->catatAudit('verifikasi_tahap_bidang', RkatTahapBidang::class, $tahap->id, ['status' => RkatTahapBidang::STATUS_DIAJUKAN], [
                'status' => RkatTahapBidang::STATUS_TERVERIFIKASI,
                'bidang_kode' => $bidang,
                'rkat_version_id' => $versi->id,
            ]);

            return $tahap;
        });
    }
}
