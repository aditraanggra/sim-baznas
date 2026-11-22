<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\Kwitansi;
use App\Models\Penerimaan;
use App\Services\PenomoranService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinalkanPenerimaan extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly SimpanDraftPenerimaan $simpanDraft,
        private readonly PenomoranService $penomoran,
    ) {}

    public function jalankan(array $data): Penerimaan
    {
        return DB::transaction(function () use ($data): Penerimaan {
            if (isset($data['muzakki_id'], $data['items'])) {
                $penerimaan = $this->simpanDraft->jalankan($data);
            } else {
                $tervalidasi = validator($data, [
                    'id' => ['required', 'integer', 'exists:penerimaan,id'],
                ])->validate();

                $penerimaan = Penerimaan::lockForUpdate()->findOrFail($tervalidasi['id']);
            }

            $penerimaan->loadMissing('items');

            if ($penerimaan->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi berstatus draft yang dapat difinalkan.',
                ]);
            }

            if ($penerimaan->items->isEmpty() || $penerimaan->totalRupiah() <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan wajib memiliki minimal satu item dengan nominal lebih dari 0.',
                ]);
            }

            if (BeritaAcaraPenerimaan::whereDate('tanggal', $penerimaan->tanggal)->where('status', 'final')->exists()) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tanggal transaksi sudah dikunci oleh Berita Acara Penerimaan final (Q-06).',
                ]);
            }

            $lama = $penerimaan->only(['nomor', 'status']);

            $nomorPenerimaan = $this->penomoran->berikutnya('penerimaan', $penerimaan->tanggal);
            $nomorKwitansi = $this->penomoran->berikutnya('kwitansi', $penerimaan->tanggal);

            $penerimaan->forceFill([
                'nomor' => $nomorPenerimaan,
                'status' => 'final',
            ])->save();

            $kwitansi = Kwitansi::create([
                'nomor' => $nomorKwitansi,
                'token_verifikasi' => (string) Str::uuid(),
                'penerimaan_id' => $penerimaan->id,
                'tanggal_terbit' => $penerimaan->tanggal,
                'created_by' => Auth::id(),
            ]);

            $this->catatAudit(
                'finalkan',
                Penerimaan::class,
                $penerimaan->id,
                $lama,
                [
                    'nomor' => $nomorPenerimaan,
                    'status' => 'final',
                    'kwitansi_nomor' => $kwitansi->nomor,
                    'total' => $penerimaan->totalRupiah(),
                ],
            );

            return $penerimaan->fresh(['muzakki', 'upz', 'metodeBayar', 'rekening', 'items.sumberDana.jenisDana', 'kwitansi']);
        });
    }
}
