<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penerimaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalkanBeritaAcaraPenerimaan extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): BeritaAcaraPenerimaan
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:berita_acara_penerimaan,id'],
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): BeritaAcaraPenerimaan {
            $ba = BeritaAcaraPenerimaan::lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($ba->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya berita acara berstatus draft yang dapat difinalkan.',
                ]);
            }

            $transaksi = Penerimaan::query()
                ->with('items')
                ->whereDate('tanggal', $ba->tanggal)
                ->where('status', 'final')
                ->get();

            if ($transaksi->isEmpty()) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tidak ada transaksi penerimaan final untuk tanggal berita acara ini.',
                ]);
            }

            $lama = $ba->only(['status', 'total_rupiah', 'jumlah_transaksi']);
            $total = $transaksi->sum(fn (Penerimaan $p) => $p->totalRupiah());

            $ba->forceFill([
                'status' => 'final',
                'total_rupiah' => $total,
                'jumlah_transaksi' => $transaksi->count(),
                'penanggung_jawab_id' => $tervalidasi['penanggung_jawab_id'] ?? $ba->penanggung_jawab_id,
                'catatan' => $tervalidasi['catatan'] ?? $ba->catatan,
                'final_at' => now(),
            ])->save();

            Penerimaan::whereIn('id', $transaksi->pluck('id'))
                ->update(['berita_acara_id' => $ba->id]);

            $this->catatAudit(
                'finalkan',
                BeritaAcaraPenerimaan::class,
                $ba->id,
                $lama,
                $ba->only(['status', 'total_rupiah', 'jumlah_transaksi', 'final_at']),
            );

            return $ba->fresh();
        });
    }
}
