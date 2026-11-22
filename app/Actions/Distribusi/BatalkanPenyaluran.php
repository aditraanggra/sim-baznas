<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use App\Services\MakerChecker;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatalkanPenyaluran extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly MakerChecker $makerChecker,
        private readonly PenomoranService $penomoran,
    ) {}

    public function jalankan(array $data): Penyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:penyaluran,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): Penyaluran {
            $penyaluran = Penyaluran::lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($penyaluran->status === 'batal') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi penyaluran ini sudah berstatus batal.',
                ]);
            }

            $this->makerChecker->pastikanBerhakMenyetujui(
                'distribusi',
                $penyaluran->petugas_id ?: $penyaluran->created_by,
                Auth::user(),
                'pembatalan penyaluran',
            );

            $lama = $penyaluran->only(['status', 'alasan_batal']);
            $baLamaId = $penyaluran->berita_acara_id;

            $penyaluran->forceFill([
                'status' => 'batal',
                'alasan_batal' => $tervalidasi['alasan'],
                'dibatalkan_oleh' => Auth::id(),
                'dibatalkan_at' => now(),
            ])->save();

            $this->catatAudit(
                'batal',
                Penyaluran::class,
                $penyaluran->id,
                $lama,
                ['status' => 'batal', 'alasan_batal' => $tervalidasi['alasan']],
                $tervalidasi['alasan'],
            );

            // Q-06: bila masuk Berita Acara final, tandai BA lama digantikan dan terbitkan BA Revisi.
            if ($baLamaId !== null) {
                $baLama = BeritaAcaraPenyaluran::lockForUpdate()->find($baLamaId);
                if ($baLama && $baLama->status === 'final') {
                    $baLama->forceFill(['status' => 'digantikan'])->save();

                    $sisaTransaksi = Penyaluran::query()
                        ->with('items')
                        ->whereDate('tanggal', $baLama->tanggal)
                        ->where('status', 'dicairkan')
                        ->get();

                    $totalBaru = $sisaTransaksi->sum(fn (Penyaluran $p) => $p->totalRupiah());

                    $baRevisi = BeritaAcaraPenyaluran::create([
                        'nomor' => $this->penomoran->berikutnya('ba_penyaluran', $baLama->tanggal),
                        'tanggal' => $baLama->tanggal,
                        'revisi_ke' => $baLama->revisi_ke + 1,
                        'status' => 'final',
                        'total_rupiah' => $totalBaru,
                        'jumlah_transaksi' => $sisaTransaksi->count(),
                        'penanggung_jawab_id' => $baLama->penanggung_jawab_id,
                        'catatan' => "Revisi otomatis akibat pembatalan penyaluran {$penyaluran->nomor}.",
                        'final_at' => now(),
                        'dibuat_oleh' => Auth::id(),
                        'created_by' => Auth::id(),
                    ]);

                    if ($sisaTransaksi->isNotEmpty()) {
                        Penyaluran::whereIn('id', $sisaTransaksi->pluck('id'))
                            ->update(['berita_acara_id' => $baRevisi->id]);
                    }

                    $this->catatAudit(
                        'revisi_ba',
                        BeritaAcaraPenyaluran::class,
                        $baRevisi->id,
                        ['berita_acara_lama_id' => $baLama->id, 'nomor_lama' => $baLama->nomor],
                        ['nomor' => $baRevisi->nomor, 'revisi_ke' => $baRevisi->revisi_ke, 'total_rupiah' => $totalBaru],
                        $tervalidasi['alasan'],
                    );
                }
            }

            return $penyaluran->fresh(['items', 'mustahik', 'program']);
        });
    }
}
