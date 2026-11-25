<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratKeluar;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\MakerChecker;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatalkanSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly MakerChecker $makerChecker,
    ) {}

    public function jalankan(array $data): SuratKeluar
    {
        $user = Auth::user();
        if (! $user || (! $this->akses->bolehKelolaSurat($user) && ! $this->akses->bolehSetujuiSuratKeluar($user))) {
            throw new AuthorizationException('Anda tidak memiliki wewenang membatalkan surat keluar.');
        }

        $tervalidasi = validator($data, [
            'surat_keluar_id' => ['required', 'integer', 'exists:surat_keluar,id'],
            'alasan_batal' => ['required', 'string', 'min:5', 'max:1500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratKeluar {
            $surat = SuratKeluar::lockForUpdate()->findOrFail((int) $tervalidasi['surat_keluar_id']);

            if ($surat->status_kode === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'status' => 'Surat keluar ini sudah berstatus dibatalkan.',
                ]);
            }

            if ($surat->status_kode === 'disetujui') {
                if (! $this->akses->bolehSetujuiSuratKeluar($user)) {
                    throw new AuthorizationException('Hanya penyetuju berwenang yang dapat membatalkan surat keluar yang sudah final.');
                }
                $this->makerChecker->pastikanBukanPembuat($surat, $user, 'pembatalan surat keluar', true);
            }

            $lama = ['status_kode' => $surat->status_kode];
            $statusBatal = StatusDokumen::where('kelompok', 'surat_keluar')->where('kode', 'dibatalkan')->first();

            $surat->update([
                'status_kode' => 'dibatalkan',
                'status_id' => $statusBatal?->id,
                'dibatalkan_oleh' => $user->id,
                'dibatalkan_at' => now(),
                'alasan_batal' => trim($tervalidasi['alasan_batal']),
            ]);

            $this->catatAudit(
                'batal',
                SuratKeluar::class,
                $surat->id,
                $lama,
                ['status_kode' => 'dibatalkan', 'alasan_batal' => trim($tervalidasi['alasan_batal'])],
                trim($tervalidasi['alasan_batal']),
            );

            return $surat->fresh();
        });
    }
}
