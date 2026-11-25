<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratMasuk;
use App\Models\SuratMasukStatusLog;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeruskanSuratMasukKeKetua extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratMasuk
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang meneruskan surat masuk ke Ketua.');
        }

        $tervalidasi = validator($data, [
            'surat_masuk_id' => ['required', 'integer', 'exists:surat_masuk,id'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): SuratMasuk {
            $surat = SuratMasuk::lockForUpdate()->findOrFail((int) $tervalidasi['surat_masuk_id']);

            if ($surat->status_kode !== 'diterima_arsip') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya surat masuk berstatus Diterima Admin Arsip yang dapat diteruskan ke Ketua.',
                ]);
            }

            $lama = ['status_kode' => $surat->status_kode];
            $statusDok = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', 'diteruskan_ketua')->first();

            $surat->update([
                'status_kode' => 'diteruskan_ketua',
                'status_id' => $statusDok?->id,
            ]);

            SuratMasukStatusLog::create($this->denganPembuat([
                'surat_masuk_id' => $surat->id,
                'status_kode' => 'diteruskan_ketua',
                'status_id' => $statusDok?->id,
                'user_id' => $user->id,
                'waktu' => now(),
                'catatan' => $tervalidasi['catatan'] ?? 'Diteruskan ke Ketua untuk arahan dan disposisi.',
            ]));

            $this->notifikasi->kirimKePeran(
                'ketua',
                'surat_masuk_ketua',
                'Surat Masuk Menunggu Disposisi',
                "Agenda {$surat->nomor_agenda} dari {$surat->pengirim}: {$surat->perihal}",
                route('umum.surat.masuk.show', $surat->id, false),
                SuratMasuk::class,
                $surat->id,
                $user->id,
            );

            $this->catatAudit(
                'teruskan_ketua',
                SuratMasuk::class,
                $surat->id,
                $lama,
                ['status_kode' => 'diteruskan_ketua'],
                $tervalidasi['catatan'] ?? null,
            );

            return $surat->fresh();
        });
    }
}
