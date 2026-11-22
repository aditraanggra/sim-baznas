<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use App\Services\MakerChecker;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use App\Services\SaldoDanaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetujuiPenyaluran extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly MakerChecker $makerChecker,
        private readonly PenomoranService $penomoran,
        private readonly SaldoDanaService $saldoService,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): Penyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:penyaluran,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): Penyaluran {
            $penyaluran = Penyaluran::with('items')->lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($penyaluran->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya draft penyaluran yang dapat disetujui.',
                ]);
            }

            if ($penyaluran->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Minimal harus ada satu rincian penyaluran.',
                ]);
            }

            $terkunciBa = BeritaAcaraPenyaluran::query()
                ->whereDate('tanggal', $penyaluran->tanggal)
                ->where('status', 'final')
                ->exists();

            if ($terkunciBa) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tanggal ini sudah dikunci oleh Berita Acara Penyaluran Harian final.',
                ]);
            }

            $this->makerChecker->pastikanBerhakMenyetujui(
                'distribusi',
                $penyaluran->petugas_id ?: $penyaluran->created_by,
                Auth::user(),
                'persetujuan penyaluran bantuan',
            );

            $itemArray = $penyaluran->items->map(fn ($i) => [
                'sumber_dana_id' => $i->sumber_dana_id,
                'jumlah' => $i->jumlah,
            ])->all();

            $hasilSaldo = $this->saldoService->periksaKecukupan($itemArray);
            $peringatanSaldo = ! $hasilSaldo['cukup'] ? implode(' ', $hasilSaldo['peringatan']) : null;

            $lama = $penyaluran->only(['nomor', 'status', 'disetujui_oleh']);

            $penyaluran->forceFill([
                'nomor' => $penyaluran->nomor ?: $this->penomoran->berikutnya('penyaluran', $penyaluran->tanggal),
                'status' => 'disetujui',
                'disetujui_oleh' => Auth::id(),
                'disetujui_at' => now(),
                'peringatan_saldo' => $peringatanSaldo,
                'catatan' => $tervalidasi['catatan'] ?? $penyaluran->catatan,
            ])->save();

            $this->catatAudit(
                'setujui',
                Penyaluran::class,
                $penyaluran->id,
                $lama,
                $penyaluran->only(['nomor', 'status', 'disetujui_oleh', 'peringatan_saldo']),
                $tervalidasi['catatan'] ?? null,
            );

            if ($penyaluran->petugas_id && $penyaluran->petugas_id !== Auth::id()) {
                $this->notifikasi->kirimKeUser(
                    $penyaluran->petugas_id,
                    'penyaluran_disetujui',
                    "Penyaluran {$penyaluran->nomor} Disetujui",
                    'Draft penyaluran telah disetujui dan siap dicairkan.',
                    '/distribusi/penyaluran',
                    Penyaluran::class,
                    $penyaluran->id,
                );
            }

            return $penyaluran->fresh(['items.sumberDana', 'items.asnaf', 'items.bentukBantuan', 'mustahik', 'program']);
        });
    }
}
