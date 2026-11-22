<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use App\Services\PenomoranService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatBeritaAcaraPenyaluran extends Action
{
    use MencatatAudit;

    public function __construct(private readonly PenomoranService $penomoran) {}

    public function jalankan(array $data): BeritaAcaraPenyaluran
    {
        $tervalidasi = validator($data, [
            'tanggal' => ['required', 'date'],
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): BeritaAcaraPenyaluran {
            $sudahAda = BeritaAcaraPenyaluran::query()
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->whereIn('status', ['draft', 'final'])
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Berita acara penyaluran untuk tanggal tersebut sudah dibuat.',
                ]);
            }

            $transaksi = Penyaluran::query()
                ->with('items')
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->where('status', 'dicairkan')
                ->get();

            if ($transaksi->isEmpty()) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Belum ada transaksi penyaluran berstatus dicairkan pada tanggal tersebut.',
                ]);
            }

            $pernahFinal = BeritaAcaraPenyaluran::query()
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->whereNotNull('final_at')
                ->exists();

            $revisiKe = $pernahFinal
                ? ((int) BeritaAcaraPenyaluran::query()
                    ->whereDate('tanggal', $tervalidasi['tanggal'])
                    ->max('revisi_ke')) + 1
                : 0;

            $total = $transaksi->sum(fn (Penyaluran $p) => $p->totalRupiah());

            $ba = BeritaAcaraPenyaluran::create([
                'nomor' => $this->penomoran->berikutnya('ba_penyaluran', Carbon::parse($tervalidasi['tanggal'])),
                'tanggal' => $tervalidasi['tanggal'],
                'revisi_ke' => $revisiKe,
                'status' => 'draft',
                'total_rupiah' => $total,
                'jumlah_transaksi' => $transaksi->count(),
                'penanggung_jawab_id' => $tervalidasi['penanggung_jawab_id'] ?? Auth::id(),
                'catatan' => $tervalidasi['catatan'] ?? null,
                'dibuat_oleh' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            Penyaluran::whereIn('id', $transaksi->pluck('id'))
                ->update(['berita_acara_id' => $ba->id]);

            $this->catatAudit(
                'buat',
                BeritaAcaraPenyaluran::class,
                $ba->id,
                null,
                $ba->only(['nomor', 'tanggal', 'status', 'total_rupiah', 'jumlah_transaksi']),
            );

            return $ba->fresh();
        });
    }
}
