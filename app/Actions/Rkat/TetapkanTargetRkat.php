<?php

namespace App\Actions\Rkat;

use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Menetapkan atau mengubah target pengumpulan RAKORNAS oleh PKP sebelum tahap Pengumpulan terverifikasi. */
class TetapkanTargetRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'target_pengumpulan' => ['required', 'integer', 'min:1'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            $this->pastikanDraft($versi);

            if (! $versi->isKolaboratif()) {
                $this->gagal('versi', 'Penetapan target pengumpulan hanya berlaku untuk RKAT kolaboratif.');
            }

            $tahapPengumpulan = RkatTahapBidang::query()
                ->where('rkat_version_id', $versi->id)
                ->where('bidang_kode', 'pengumpulan')
                ->lockForUpdate()
                ->firstOrFail();

            if ($tahapPengumpulan->status === RkatTahapBidang::STATUS_TERVERIFIKASI) {
                $this->gagal('tahap', 'Tahap Pengumpulan sudah terverifikasi; kembalikan tahap Pengumpulan terlebih dahulu jika target ingin diubah.');
            }

            $lama = ['target_pengumpulan' => $versi->target_pengumpulan];

            $versi->update([
                'target_pengumpulan' => (int) $data['target_pengumpulan'],
                'target_ditetapkan_pada' => now(),
                'target_ditetapkan_oleh' => Auth::id(),
            ]);

            if ($tahapPengumpulan->status === RkatTahapBidang::STATUS_DIAJUKAN) {
                $tahapPengumpulan->update([
                    'status' => RkatTahapBidang::STATUS_DRAFT,
                    'catatan_verifikasi' => 'Target pengumpulan diperbarui oleh PKP; silakan sesuaikan rincian sebelum mengajukan ulang.',
                ]);
            }

            $this->catatAudit('tetapkan_target', RkatVersion::class, $versi->id, $lama, [
                'target_pengumpulan' => $versi->target_pengumpulan,
            ]);

            app(NotifikasiService::class)->kirimKePeran(
                'kabag_pengumpulan',
                'rkat_target',
                "Target Pengumpulan RKAT {$versi->tahun} diperbarui",
                'Target pengumpulan diperbarui menjadi Rp '.number_format((int) $versi->target_pengumpulan, 0, ',', '.').'.',
                route('pengumpulan.rkat.index', ['versi' => $versi->id], false),
                RkatVersion::class,
                $versi->id,
            );

            return $versi;
        });
    }
}
