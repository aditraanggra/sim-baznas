<?php

namespace App\Actions\Rkat;

use App\Models\RkatAturanAmil;
use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use Illuminate\Support\Facades\DB;

/** Menyimpan penyesuaian persentase Hak Amil (<= batas maksimal) per versi RKAT sebelum Pengumpulan terverifikasi. */
class SimpanAturanAmilRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'persen' => ['required', 'array', 'min:1'],
            'persen.*' => ['required', 'integer', 'min:0', 'max:10000'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            $this->pastikanDraft($versi);

            if (! $versi->isKolaboratif()) {
                $this->gagal('versi', 'Aturan persentase Hak Amil per baris hanya berlaku untuk RKAT kolaboratif.');
            }

            $tahapPengumpulan = RkatTahapBidang::query()
                ->where('rkat_version_id', $versi->id)
                ->where('bidang_kode', 'pengumpulan')
                ->lockForUpdate()
                ->firstOrFail();

            if ($tahapPengumpulan->status === RkatTahapBidang::STATUS_TERVERIFIKASI) {
                $this->gagal('tahap', 'Persentase Hak Amil tidak dapat diubah setelah tahap Pengumpulan terverifikasi.');
            }

            $aturanMap = RkatAturanAmil::where('rkat_version_id', $versi->id)
                ->whereIn('id', array_keys($data['persen']))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lama = $baru = [];
            foreach ($data['persen'] as $id => $persenBp) {
                $aturan = $aturanMap->get((int) $id);
                if (! $aturan) {
                    $this->gagal("persen.$id", 'Baris aturan Hak Amil tidak ditemukan.');
                }

                $persenBp = (int) $persenBp;
                if ($persenBp > $aturan->persen_maks_bp) {
                    $maksPersen = number_format($aturan->persen_maks_bp / 100, 1, ',', '.').'%';
                    $this->gagal("persen.$id", "{$aturan->uraian} melebihi batas maksimal ({$maksPersen}).");
                }

                if ($aturan->persen_bp === $persenBp) {
                    continue;
                }

                $lama[$aturan->uraian] = $aturan->persen_bp;
                $baru[$aturan->uraian] = $persenBp;
                $aturan->update(['persen_bp' => $persenBp]);
            }

            if ($baru !== []) {
                SinkronisasiNilaiRkatService::sinkronkan($versi);
                $this->catatAudit('ubah_aturan_amil', RkatVersion::class, $versi->id, $lama, $baru);
            }

            return $versi;
        });
    }
}
