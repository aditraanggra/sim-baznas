<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalkanBeritaAcaraPenyaluran extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): BeritaAcaraPenyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:berita_acara_penyaluran,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): BeritaAcaraPenyaluran {
            $ba = BeritaAcaraPenyaluran::lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($ba->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya draft Berita Acara Penyaluran yang dapat difinalkan.',
                ]);
            }

            $transaksi = Penyaluran::query()
                ->with('items')
                ->whereDate('tanggal', $ba->tanggal)
                ->where('status', 'dicairkan')
                ->get();

            $lama = $ba->only(['status', 'total_rupiah', 'jumlah_transaksi']);

            $ba->forceFill([
                'status' => 'final',
                'total_rupiah' => $transaksi->sum(fn (Penyaluran $p) => $p->totalRupiah()),
                'jumlah_transaksi' => $transaksi->count(),
                'catatan' => $tervalidasi['catatan'] ?? $ba->catatan,
                'final_at' => now(),
            ])->save();

            if ($transaksi->isNotEmpty()) {
                Penyaluran::whereIn('id', $transaksi->pluck('id'))
                    ->update(['berita_acara_id' => $ba->id]);
            }

            $this->catatAudit(
                'finalkan',
                BeritaAcaraPenyaluran::class,
                $ba->id,
                $lama,
                $ba->only(['status', 'total_rupiah', 'jumlah_transaksi', 'final_at']),
            );

            return $ba->fresh();
        });
    }
}
