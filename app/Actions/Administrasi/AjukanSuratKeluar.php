<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratKeluar;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AjukanSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratKeluar
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengajukan persetujuan surat keluar.');
        }

        $tervalidasi = validator($data, [
            'surat_keluar_id' => ['required', 'integer', 'exists:surat_keluar,id'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratKeluar {
            $surat = SuratKeluar::lockForUpdate()->findOrFail((int) $tervalidasi['surat_keluar_id']);

            if ($surat->status_kode !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya draft surat keluar yang dapat diajukan persetujuan.',
                ]);
            }

            $lama = ['status_kode' => $surat->status_kode];
            $statusMenunggu = StatusDokumen::where('kelompok', 'surat_keluar')->where('kode', 'menunggu_persetujuan')->first();

            $surat->update([
                'status_kode' => 'menunggu_persetujuan',
                'status_id' => $statusMenunggu?->id,
                'diajukan_at' => now(),
                'catatan_revisi' => null,
            ]);

            $this->notifikasi->kirimKePeran(
                ['ketua', 'waka_4', 'kabag_sau'],
                'surat_keluar_persetujuan',
                'Surat Keluar Menunggu Persetujuan',
                "Surat untuk {$surat->tujuan} ({$surat->perihal}) siap ditinjau dan disetujui.",
                route('umum.surat.keluar.show', $surat->id, false),
                SuratKeluar::class,
                $surat->id,
                $user->id,
            );

            $this->catatAudit(
                'ajukan',
                SuratKeluar::class,
                $surat->id,
                $lama,
                ['status_kode' => 'menunggu_persetujuan'],
            );

            return $surat->fresh();
        });
    }
}
