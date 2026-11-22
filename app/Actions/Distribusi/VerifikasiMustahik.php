<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Mustahik;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VerifikasiMustahik extends Action
{
    use MencatatAudit;

    public function __construct(private readonly NotifikasiService $notifikasi) {}

    public function jalankan(array $data): Mustahik
    {
        $tervalidasi = validator($data, [
            'mustahik_id' => ['required', 'integer', 'exists:mustahik,id'],
            'status_verifikasi' => ['required', Rule::in(['terverifikasi', 'ditolak'])],
            'catatan_verifikasi' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): Mustahik {
            $mustahik = Mustahik::lockForUpdate()->findOrFail($tervalidasi['mustahik_id']);
            $lama = $mustahik->only(['status_verifikasi', 'diverifikasi_oleh', 'catatan_verifikasi']);

            $mustahik->forceFill([
                'status_verifikasi' => $tervalidasi['status_verifikasi'],
                'diverifikasi_oleh' => Auth::id(),
                'waktu_verifikasi' => now(),
                'catatan_verifikasi' => $tervalidasi['catatan_verifikasi'] ?? null,
            ])->save();

            $this->catatAudit(
                'verifikasi',
                Mustahik::class,
                $mustahik->id,
                $lama,
                $mustahik->only(['status_verifikasi', 'diverifikasi_oleh', 'catatan_verifikasi']),
                $tervalidasi['catatan_verifikasi'] ?? null,
            );

            if ($mustahik->created_by && $mustahik->created_by !== Auth::id()) {
                $this->notifikasi->kirimKeUser(
                    $mustahik->created_by,
                    'verifikasi_mustahik',
                    "Verifikasi Mustahik {$mustahik->nama}: ".ucfirst($tervalidasi['status_verifikasi']),
                    $tervalidasi['catatan_verifikasi'] ?? 'Status verifikasi mustahik telah diperbarui.',
                    "/distribusi/mustahik/{$mustahik->id}",
                    Mustahik::class,
                    $mustahik->id,
                );
            }

            return $mustahik->fresh();
        });
    }
}
