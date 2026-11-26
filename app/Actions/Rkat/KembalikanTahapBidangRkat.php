<?php

namespace App\Actions\Rkat;

use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Mengembalikan tahap bidang ke Draft (dengan alasan wajib) oleh Kepala Bagian PKP / Admin.
 * Cascade: jika Pengumpulan dikembalikan, status Pendis & SAU di-reset ke Menunggu tanpa menghapus baris kegiatan mereka.
 */
class KembalikanTahapBidangRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatTahapBidang
    {
        $this->pastikanVerifikatorPkp();

        $tervalidasi = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
            'alasan' => ['required', 'string', 'min:3', 'max:500'],
        ])->validate();

        $bidang = $tervalidasi['bidang_kode'];
        $alasan = trim($tervalidasi['alasan']);

        return DB::transaction(function () use ($tervalidasi, $bidang, $alasan) {
            $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
            $this->pastikanKolaboratifDraft($versi);
            $tahap = $this->ambilTahapTerkunci($versi, $bidang);

            if (! in_array($tahap->status, [RkatTahapBidang::STATUS_DIAJUKAN, RkatTahapBidang::STATUS_TERVERIFIKASI], true)) {
                $this->gagal('tahap', "Tahap berstatus {$tahap->status} tidak dapat dikembalikan.");
            }

            $statusLama = $tahap->status;
            $tahap->update([
                'status' => RkatTahapBidang::STATUS_DRAFT,
                'diverifikasi_pada' => null,
                'diverifikasi_oleh' => null,
                'catatan_verifikasi' => $alasan,
            ]);

            $notif = app(NotifikasiService::class);
            $labelBidang = self::LABEL_BIDANG[$bidang];

            if ($bidang === 'pengumpulan') {
                RkatTahapBidang::query()
                    ->where('rkat_version_id', $versi->id)
                    ->whereIn('bidang_kode', ['distribusi', 'umum'])
                    ->update([
                        'status' => RkatTahapBidang::STATUS_MENUNGGU,
                        'diverifikasi_pada' => null,
                        'diverifikasi_oleh' => null,
                        'catatan_verifikasi' => "Menunggu verifikasi ulang Pengumpulan karena revisi: {$alasan}",
                    ]);

                $notif->kirimKePeran(
                    'kabag_pengumpulan',
                    'rkat_bidang_dikembalikan',
                    "Revisi RKAT Pengumpulan {$versi->tahun}",
                    "Tahap Pengumpulan dikembalikan ke Draft oleh Kabag PKP. Alasan: {$alasan}",
                    route('pengumpulan.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );

                $notif->kirimKePeran(
                    'kabag_pendis_pendag',
                    'rkat_tahap_direset',
                    "Tahap Penyaluran RKAT {$versi->tahun} Direset Sementara",
                    "Tahap Pengumpulan dikembalikan untuk revisi ({$alasan}). Rincian kegiatan Anda tetap tersimpan dan dapat disesuaikan kembali setelah Pengumpulan terverifikasi.",
                    route('distribusi.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );

                $notif->kirimKePeran(
                    'kabag_sau',
                    'rkat_tahap_direset',
                    "Tahap Operasional SAU RKAT {$versi->tahun} Direset Sementara",
                    "Tahap Pengumpulan dikembalikan untuk revisi ({$alasan}). Rincian kegiatan Anda tetap tersimpan dan dapat disesuaikan kembali setelah Pengumpulan terverifikasi.",
                    route('umum.rkat.index', ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );
            } else {
                $peranTujuan = $bidang === 'distribusi' ? 'kabag_pendis_pendag' : 'kabag_sau';
                $ruteTujuan = $bidang === 'distribusi' ? 'distribusi.rkat.index' : 'umum.rkat.index';

                $notif->kirimKePeran(
                    $peranTujuan,
                    'rkat_bidang_dikembalikan',
                    "Revisi RKAT {$labelBidang} {$versi->tahun}",
                    "Tahap {$labelBidang} dikembalikan ke Draft oleh Kabag PKP. Alasan: {$alasan}",
                    route($ruteTujuan, ['versi' => $versi->id], false),
                    RkatVersion::class,
                    $versi->id,
                );
            }

            $this->catatAudit(
                'kembalikan_tahap_bidang',
                RkatTahapBidang::class,
                $tahap->id,
                ['status' => $statusLama],
                ['status' => RkatTahapBidang::STATUS_DRAFT, 'bidang_kode' => $bidang, 'rkat_version_id' => $versi->id],
                $alasan,
            );

            return $tahap;
        });
    }
}
