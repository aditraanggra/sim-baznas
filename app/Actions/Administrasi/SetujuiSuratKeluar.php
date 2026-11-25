<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratKeluar;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\MakerChecker;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetujuiSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly MakerChecker $makerChecker,
        private readonly PenomoranService $penomoran,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratKeluar
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehSetujuiSuratKeluar($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang menyetujui surat keluar.');
        }

        $tervalidasi = validator($data, [
            'surat_keluar_id' => ['required', 'integer', 'exists:surat_keluar,id'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratKeluar {
            $surat = SuratKeluar::with('jenisSurat')->lockForUpdate()->findOrFail((int) $tervalidasi['surat_keluar_id']);

            if ($surat->status_kode !== 'menunggu_persetujuan') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya surat berstatus Menunggu Persetujuan yang dapat disetujui.',
                ]);
            }

            $this->makerChecker->pastikanBukanPembuat($surat, $user, 'surat keluar');

            $jenis = $surat->jenisSurat;
            $jenisUrutan = $jenis->pisah_urutan
                ? 'surat_keluar_'.strtolower($jenis->kode)
                : 'surat_keluar';
            $templateOverride = $jenis->format_nomor ?: config('penomoran.format.surat_keluar');

            $nomorFinal = $this->penomoran->berikutnya(
                $jenisUrutan,
                $surat->tanggal,
                ['kode' => $jenis->kode, 'jenis' => $jenis->kode],
                $templateOverride,
            );

            $lama = ['status_kode' => $surat->status_kode, 'nomor' => $surat->nomor];
            $statusFinal = StatusDokumen::where('kelompok', 'surat_keluar')->where('kode', 'disetujui')->first();

            $surat->update([
                'nomor' => $nomorFinal,
                'status_kode' => 'disetujui',
                'status_id' => $statusFinal?->id,
                'disetujui_oleh' => $user->id,
                'disetujui_at' => now(),
                'catatan_revisi' => null,
            ]);

            if ($surat->dibuat_oleh) {
                $this->notifikasi->kirimKeUser(
                    $surat->dibuat_oleh,
                    'surat_keluar_disetujui',
                    'Surat Keluar Disetujui',
                    "Surat keluar {$nomorFinal} ({$surat->perihal}) telah disetujui dan siap dicetak.",
                    route('umum.surat.keluar.show', $surat->id, false),
                    SuratKeluar::class,
                    $surat->id,
                );
            }

            $this->catatAudit(
                'setujui',
                SuratKeluar::class,
                $surat->id,
                $lama,
                ['status_kode' => 'disetujui', 'nomor' => $nomorFinal, 'disetujui_oleh' => $user->id],
            );

            return $surat->fresh(['jenisSurat', 'penyetuju']);
        });
    }
}
