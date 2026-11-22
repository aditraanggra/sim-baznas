<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\PembatalanPenerimaan;
use App\Models\Penerimaan;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AjukanPembatalanPenerimaan extends Action
{
    use MencatatAudit;

    public function __construct(private readonly NotifikasiService $notifikasi) {}

    public function jalankan(array $data): PembatalanPenerimaan
    {
        $tervalidasi = validator($data, [
            'penerimaan_id' => ['required', 'integer', 'exists:penerimaan,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): PembatalanPenerimaan {
            $penerimaan = Penerimaan::lockForUpdate()->findOrFail($tervalidasi['penerimaan_id']);

            if ($penerimaan->status !== 'final') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi berstatus final yang dapat diajukan pembatalannya.',
                ]);
            }

            if (PembatalanPenerimaan::where('penerimaan_id', $penerimaan->id)->whereIn('status', ['menunggu', 'disetujui'])->exists()) {
                throw ValidationException::withMessages([
                    'penerimaan_id' => 'Transaksi ini sudah memiliki pengajuan pembatalan yang aktif.',
                ]);
            }

            $pembatalan = PembatalanPenerimaan::updateOrCreate(
                ['penerimaan_id' => $penerimaan->id],
                [
                    'alasan' => trim((string) $tervalidasi['alasan']),
                    'status' => 'menunggu',
                    'diminta_oleh' => Auth::id(),
                    'disetujui_oleh' => null,
                    'catatan_penyetuju' => null,
                    'waktu_diminta' => now(),
                    'waktu_disetujui' => null,
                    'created_by' => Auth::id(),
                ],
            );

            $this->catatAudit(
                'ajukan_pembatalan',
                PembatalanPenerimaan::class,
                $pembatalan->id,
                null,
                ['penerimaan_id' => $penerimaan->id, 'nomor' => $penerimaan->nomor, 'alasan' => $pembatalan->alasan],
                $pembatalan->alasan,
            );

            $this->notifikasi->kirimKePeran(
                ['kabag_pengumpulan', 'waka_1', 'ketua'],
                'pembatalan_penerimaan',
                "Pengajuan Pembatalan Penerimaan {$penerimaan->nomor}",
                "Alasan: {$pembatalan->alasan}",
                "/pengumpulan/penerimaan/{$penerimaan->id}",
                PembatalanPenerimaan::class,
                $pembatalan->id,
                Auth::id(),
            );

            return $pembatalan;
        });
    }
}
