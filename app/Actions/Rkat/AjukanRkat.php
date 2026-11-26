<?php

namespace App\Actions\Rkat;

use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\Rkat\HitungRkat;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use App\Services\Rkat\ValidasiRkat;
use Illuminate\Support\Facades\DB;

/**
 * BR-04: versi tidak dapat diajukan bila ada VAL-1..6 yang gagal.
 * Pengecualian (keputusan pengguna O-1): versi Awal hasil impor (sumber=impor) lolos dengan peringatan.
 */
class AjukanRkat extends RkatAction
{
    /** @return array{versi: RkatVersion, validasi: list<array>, peringatan: bool} */
    public function jalankan(array $data): array
    {
        $this->pastikanPenulis();
        $data = validator($data, ['rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id']])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            if ($versi->status !== 'draft') {
                $this->gagal('versi', "Hanya versi Draft yang dapat diajukan (status saat ini: {$versi->status}).");
            }

            if ($versi->isKolaboratif()) {
                $terverifikasi = $versi->tahapBidang()
                    ->whereIn('bidang_kode', RkatTahapBidang::BIDANG)
                    ->where('status', RkatTahapBidang::STATUS_TERVERIFIKASI)
                    ->count();

                if ($terverifikasi < count(RkatTahapBidang::BIDANG)) {
                    $this->gagal('tahap', 'Semua tahap bidang (Pengumpulan, Pendistribusian & Pendayagunaan, SAU) harus diverifikasi oleh Kepala Bagian PKP terlebih dahulu.');
                }

                SinkronisasiNilaiRkatService::sinkronkan($versi);
            }

            $validasi = self::evaluasiVersi($versi);
            $gagal = collect($validasi)->where('lulus', false)->pluck('id')->all();
            $lewatImpor = $versi->sumber === 'impor' && $versi->jenis === 'awal';

            if ($gagal !== [] && ! $lewatImpor) {
                $this->gagal('validasi', 'Pengajuan ditolak (BR-04), validasi gagal: '.implode(', ', $gagal).'.');
            }

            $versi->update(['status' => 'diajukan']);
            $this->catatAudit('ajukan', RkatVersion::class, $versi->id, ['status' => 'draft'], [
                'status' => 'diajukan', 'val_gagal' => $gagal, 'lewat_impor' => $lewatImpor && $gagal !== [],
            ]);

            return ['versi' => $versi, 'validasi' => $validasi, 'peringatan' => $gagal !== []];
        });
    }

    /** @return list<array> */
    public static function evaluasiVersi(RkatVersion $versi): array
    {
        $batas = $versi->catatan?->batasBp() ?? ['zakat' => null, 'infak' => null, 'csr' => null, 'dskl' => null];

        return ValidasiRkat::evaluasi(HitungRkat::untukVersi($versi)['per_kode'], $batas);
    }
}
