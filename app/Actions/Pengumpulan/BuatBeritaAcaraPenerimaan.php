<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penerimaan;
use App\Services\PenomoranService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatBeritaAcaraPenerimaan extends Action
{
    use MencatatAudit;

    public function __construct(private readonly PenomoranService $penomoran) {}

    public function jalankan(array $data): BeritaAcaraPenerimaan
    {
        $tervalidasi = validator($data, [
            'tanggal' => ['required', 'date'],
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): BeritaAcaraPenerimaan {
            $sudahAda = BeritaAcaraPenerimaan::query()
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->whereIn('status', ['draft', 'final'])
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Berita acara penerimaan untuk tanggal tersebut sudah dibuat.',
                ]);
            }

            $transaksi = Penerimaan::query()
                ->with('items')
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->where('status', 'final')
                ->get();

            if ($transaksi->isEmpty()) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Belum ada transaksi penerimaan final pada tanggal tersebut.',
                ]);
            }

            $pernahFinal = BeritaAcaraPenerimaan::query()
                ->whereDate('tanggal', $tervalidasi['tanggal'])
                ->whereNotNull('final_at')
                ->exists();

            $revisiKe = $pernahFinal
                ? ((int) BeritaAcaraPenerimaan::query()
                    ->whereDate('tanggal', $tervalidasi['tanggal'])
                    ->max('revisi_ke')) + 1
                : 0;

            $total = $transaksi->sum(fn (Penerimaan $p) => $p->totalRupiah());

            $ba = BeritaAcaraPenerimaan::create([
                'nomor' => $this->penomoran->berikutnya('ba_penerimaan', Carbon::parse($tervalidasi['tanggal'])),
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

            Penerimaan::whereIn('id', $transaksi->pluck('id'))
                ->update(['berita_acara_id' => $ba->id]);

            $this->catatAudit(
                'buat',
                BeritaAcaraPenerimaan::class,
                $ba->id,
                null,
                $ba->only(['nomor', 'tanggal', 'status', 'total_rupiah', 'jumlah_transaksi']),
            );

            return $ba;
        });
    }
}
