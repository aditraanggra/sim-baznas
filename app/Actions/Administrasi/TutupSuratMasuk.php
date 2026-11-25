<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\DisposisiSurat;
use App\Models\StatusDokumen;
use App\Models\SuratMasuk;
use App\Models\SuratMasukStatusLog;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TutupSuratMasuk extends Action
{
    use MencatatAudit;

    public function __construct(private readonly AksesPersuratanService $akses) {}

    public function jalankan(array $data): SuratMasuk
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehTutupSuratMasuk($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang menutup status surat masuk.');
        }

        $tervalidasi = validator($data, [
            'surat_masuk_id' => ['required', 'integer', 'exists:surat_masuk,id'],
            'catatan_penutupan' => ['required', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratMasuk {
            $surat = SuratMasuk::lockForUpdate()->findOrFail((int) $tervalidasi['surat_masuk_id']);

            if ($surat->status_kode === 'selesai') {
                throw ValidationException::withMessages([
                    'status' => 'Surat masuk ini sudah berstatus selesai.',
                ]);
            }

            $lama = ['status_kode' => $surat->status_kode];
            $statusSelesai = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', 'selesai')->first();

            DisposisiSurat::query()
                ->where('surat_masuk_id', $surat->id)
                ->where('status', 'menunggu')
                ->update([
                    'status' => 'selesai',
                    'catatan_tindak_lanjut' => 'Ditutup secara administratif: '.trim($tervalidasi['catatan_penutupan']),
                    'selesai_at' => now(),
                ]);

            $surat->update([
                'status_kode' => 'selesai',
                'status_id' => $statusSelesai?->id,
                'selesai_at' => now(),
                'catatan_penutupan' => trim($tervalidasi['catatan_penutupan']),
            ]);

            SuratMasukStatusLog::create($this->denganPembuat([
                'surat_masuk_id' => $surat->id,
                'status_kode' => 'selesai',
                'status_id' => $statusSelesai?->id,
                'user_id' => $user->id,
                'waktu' => now(),
                'catatan' => trim($tervalidasi['catatan_penutupan']),
            ]));

            $this->catatAudit(
                'tutup_surat_masuk',
                SuratMasuk::class,
                $surat->id,
                $lama,
                ['status_kode' => 'selesai', 'catatan_penutupan' => trim($tervalidasi['catatan_penutupan'])],
                trim($tervalidasi['catatan_penutupan']),
            );

            return $surat->fresh();
        });
    }
}
