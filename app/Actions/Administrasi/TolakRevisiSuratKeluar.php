<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratKeluar;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\MakerChecker;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TolakRevisiSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly MakerChecker $makerChecker,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratKeluar
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehSetujuiSuratKeluar($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengembalikan surat keluar untuk revisi.');
        }

        $tervalidasi = validator($data, [
            'surat_keluar_id' => ['required', 'integer', 'exists:surat_keluar,id'],
            'catatan_revisi' => ['required', 'string', 'min:5', 'max:1500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratKeluar {
            $surat = SuratKeluar::lockForUpdate()->findOrFail((int) $tervalidasi['surat_keluar_id']);

            if ($surat->status_kode !== 'menunggu_persetujuan') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya surat berstatus Menunggu Persetujuan yang dapat dikembalikan untuk revisi.',
                ]);
            }

            $this->makerChecker->pastikanBukanPembuat($surat, $user, 'surat keluar');

            $lama = ['status_kode' => $surat->status_kode];
            $statusDraft = StatusDokumen::where('kelompok', 'surat_keluar')->where('kode', 'draft')->first();

            $surat->update([
                'status_kode' => 'draft',
                'status_id' => $statusDraft?->id,
                'catatan_revisi' => trim($tervalidasi['catatan_revisi']),
            ]);

            if ($surat->dibuat_oleh) {
                $this->notifikasi->kirimKeUser(
                    $surat->dibuat_oleh,
                    'surat_keluar_revisi',
                    'Surat Keluar Perlu Revisi',
                    "Draft untuk {$surat->tujuan} dikembalikan oleh {$user->name}: ".trim($tervalidasi['catatan_revisi']),
                    route('umum.surat.keluar.show', $surat->id, false),
                    SuratKeluar::class,
                    $surat->id,
                );
            }

            $this->catatAudit(
                'revisi',
                SuratKeluar::class,
                $surat->id,
                $lama,
                ['status_kode' => 'draft', 'catatan_revisi' => trim($tervalidasi['catatan_revisi'])],
                trim($tervalidasi['catatan_revisi']),
            );

            return $surat->fresh();
        });
    }
}
