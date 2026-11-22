<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\PembatalanPenerimaan;
use App\Models\Penerimaan;
use App\Services\MakerChecker;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SetujuiPembatalanPenerimaan extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly MakerChecker $makerChecker,
        private readonly PenomoranService $penomoran,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): PembatalanPenerimaan
    {
        $tervalidasi = validator($data, [
            'pembatalan_id' => ['required', 'integer', 'exists:pembatalan_penerimaan,id'],
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_penyetuju' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): PembatalanPenerimaan {
            $pembatalan = PembatalanPenerimaan::with('penerimaan')->lockForUpdate()->findOrFail($tervalidasi['pembatalan_id']);

            if ($pembatalan->status !== 'menunggu') {
                throw ValidationException::withMessages([
                    'status' => 'Pengajuan pembatalan ini sudah diproses.',
                ]);
            }

            $penerimaan = Penerimaan::lockForUpdate()->findOrFail($pembatalan->penerimaan_id);

            $this->makerChecker->pastikanBerhakMenyetujui(
                'pengumpulan',
                $pembatalan->diminta_oleh,
                Auth::user(),
                'pembatalan penerimaan',
            );

            // Penyetuju pembatalan juga tidak boleh merupakan petugas input awal transaksi.
            $this->makerChecker->pastikanBukanPembuat(
                $penerimaan->petugas_id,
                Auth::user(),
                'transaksi penerimaan',
            );

            $lama = $pembatalan->only(['status', 'disetujui_oleh']);

            $pembatalan->forceFill([
                'status' => $tervalidasi['keputusan'],
                'disetujui_oleh' => Auth::id(),
                'catatan_penyetuju' => $tervalidasi['catatan_penyetuju'] ?? null,
                'waktu_disetujui' => now(),
            ])->save();

            if ($tervalidasi['keputusan'] === 'disetujui') {
                $statusPenerimaanLama = $penerimaan->status;
                $baLamaId = $penerimaan->berita_acara_id;

                $penerimaan->forceFill([
                    'status' => 'batal',
                ])->save();

                $this->catatAudit(
                    'batal',
                    Penerimaan::class,
                    $penerimaan->id,
                    ['status' => $statusPenerimaanLama],
                    ['status' => 'batal'],
                    $pembatalan->alasan,
                );

                // Q-06: bila masuk ke Berita Acara final, terbitkan Berita Acara Revisi bernomor baru.
                if ($baLamaId !== null) {
                    $baLama = BeritaAcaraPenerimaan::lockForUpdate()->find($baLamaId);
                    if ($baLama && $baLama->status === 'final') {
                        $baLama->forceFill(['status' => 'digantikan'])->save();

                        $sisaTransaksi = Penerimaan::query()
                            ->with('items')
                            ->whereDate('tanggal', $baLama->tanggal)
                            ->where('status', 'final')
                            ->get();

                        $totalBaru = $sisaTransaksi->sum(fn (Penerimaan $p) => $p->totalRupiah());
                        $baRevisi = BeritaAcaraPenerimaan::create([
                            'nomor' => $this->penomoran->berikutnya('ba_penerimaan', $baLama->tanggal),
                            'tanggal' => $baLama->tanggal,
                            'revisi_ke' => $baLama->revisi_ke + 1,
                            'status' => 'final',
                            'total_rupiah' => $totalBaru,
                            'jumlah_transaksi' => $sisaTransaksi->count(),
                            'penanggung_jawab_id' => $baLama->penanggung_jawab_id,
                            'catatan' => "Revisi otomatis akibat pembatalan transaksi {$penerimaan->nomor}.",
                            'final_at' => now(),
                            'dibuat_oleh' => Auth::id(),
                            'created_by' => Auth::id(),
                        ]);

                        if ($sisaTransaksi->isNotEmpty()) {
                            Penerimaan::whereIn('id', $sisaTransaksi->pluck('id'))
                                ->update(['berita_acara_id' => $baRevisi->id]);
                        }

                        $this->catatAudit(
                            'revisi_ba',
                            BeritaAcaraPenerimaan::class,
                            $baRevisi->id,
                            ['berita_acara_lama_id' => $baLama->id, 'nomor_lama' => $baLama->nomor],
                            ['nomor' => $baRevisi->nomor, 'revisi_ke' => $baRevisi->revisi_ke, 'total_rupiah' => $totalBaru],
                            $pembatalan->alasan,
                        );
                    }
                }
            }

            $this->catatAudit(
                $tervalidasi['keputusan'] === 'disetujui' ? 'setujui_pembatalan' : 'tolak_pembatalan',
                PembatalanPenerimaan::class,
                $pembatalan->id,
                $lama,
                $pembatalan->only(['status', 'disetujui_oleh']),
                $tervalidasi['catatan_penyetuju'] ?? $pembatalan->alasan,
            );

            $this->notifikasi->kirimKeUser(
                $pembatalan->diminta_oleh,
                'pembatalan_penerimaan_hasil',
                "Pengajuan Pembatalan {$penerimaan->nomor} ".($tervalidasi['keputusan'] === 'disetujui' ? 'Disetujui' : 'Ditolak'),
                $tervalidasi['catatan_penyetuju'] ?? $pembatalan->alasan,
                "/pengumpulan/penerimaan/{$penerimaan->id}",
                PembatalanPenerimaan::class,
                $pembatalan->id,
            );

            return $pembatalan->fresh();
        });
    }
}
