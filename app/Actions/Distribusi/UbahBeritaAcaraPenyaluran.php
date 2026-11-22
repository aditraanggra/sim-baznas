<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UbahBeritaAcaraPenyaluran extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): BeritaAcaraPenyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:berita_acara_penyaluran,id'],
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): BeritaAcaraPenyaluran {
            $ba = BeritaAcaraPenyaluran::lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($ba->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya berita acara berstatus draft yang dapat diubah. Batalkan berita acara final bila perlu koreksi.',
                ]);
            }

            $transaksi = Penyaluran::query()
                ->with('items')
                ->whereDate('tanggal', $ba->tanggal)
                ->where('status', 'dicairkan')
                ->get();

            $lama = $ba->only(['catatan', 'penanggung_jawab_id', 'total_rupiah', 'jumlah_transaksi']);

            $ba->forceFill([
                'catatan' => $tervalidasi['catatan'] ?? null,
                'penanggung_jawab_id' => $tervalidasi['penanggung_jawab_id'] ?? $ba->penanggung_jawab_id,
                'total_rupiah' => $transaksi->sum(fn (Penyaluran $p) => $p->totalRupiah()),
                'jumlah_transaksi' => $transaksi->count(),
            ])->save();

            Penyaluran::whereIn('id', $transaksi->pluck('id'))->update(['berita_acara_id' => $ba->id]);

            $this->catatAudit(
                'ubah_draft',
                BeritaAcaraPenyaluran::class,
                $ba->id,
                $lama,
                $ba->only(['catatan', 'penanggung_jawab_id', 'total_rupiah', 'jumlah_transaksi']),
            );

            return $ba->fresh();
        });
    }
}
