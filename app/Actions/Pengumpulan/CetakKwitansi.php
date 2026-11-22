<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Kwitansi;
use App\Models\KwitansiCetakLog;
use App\Services\Pdf\DokumenPdfService;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class CetakKwitansi extends Action
{
    use MencatatAudit;

    public function __construct(private readonly DokumenPdfService $pdfService) {}

    /**
     * @return array{kwitansi: Kwitansi, cetak_ke: int, pdf: DomPdfWrapper}
     */
    public function jalankan(array $data): array
    {
        $tervalidasi = validator($data, [
            'kwitansi_id' => ['nullable', 'integer', 'exists:kwitansi,id'],
            'penerimaan_id' => ['nullable', 'integer', 'exists:penerimaan,id'],
        ])->validate();

        if (empty($tervalidasi['kwitansi_id']) && empty($tervalidasi['penerimaan_id'])) {
            throw ValidationException::withMessages([
                'kwitansi_id' => 'ID kwitansi atau penerimaan wajib disertakan.',
            ]);
        }

        return DB::transaction(function () use ($tervalidasi): array {
            $kwitansi = Kwitansi::query()
                ->with(['penerimaan.muzakki', 'penerimaan.upz', 'penerimaan.metodeBayar', 'penerimaan.rekening', 'penerimaan.petugas', 'penerimaan.items.sumberDana.jenisDana'])
                ->when(! empty($tervalidasi['kwitansi_id']), fn ($q) => $q->whereKey($tervalidasi['kwitansi_id']))
                ->when(! empty($tervalidasi['penerimaan_id']), fn ($q) => $q->where('penerimaan_id', $tervalidasi['penerimaan_id']))
                ->lockForUpdate()
                ->firstOrFail();

            $penerimaan = $kwitansi->penerimaan;
            if (! $penerimaan || $penerimaan->status === 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Kwitansi hanya dapat dicetak untuk transaksi yang sudah difinalkan.',
                ]);
            }

            $cetakKe = $kwitansi->cetakLogs()->count() + 1;

            KwitansiCetakLog::create([
                'kwitansi_id' => $kwitansi->id,
                'user_id' => Auth::id(),
                'cetak_ke' => $cetakKe,
                'ip_address' => Request::ip(),
                'waktu' => now(),
            ]);

            $this->catatAudit(
                'cetak',
                Kwitansi::class,
                $kwitansi->id,
                null,
                ['nomor' => $kwitansi->nomor, 'cetak_ke' => $cetakKe],
            );

            $total = $penerimaan->totalRupiah();
            $urlVerifikasi = route('publik.verifikasi-kwitansi', $kwitansi->token_verifikasi);

            $pdf = $this->pdfService->buat('pdf.kwitansi-penerimaan', [
                'judul' => 'KWITANSI PENERIMAAN ZIS',
                'nomor' => $kwitansi->nomor,
                'kwitansi' => $kwitansi,
                'penerimaan' => $penerimaan,
                'nominal' => $total,
                'tanggal_cetak' => $penerimaan->tanggal?->translatedFormat('d F Y'),
                'penandatangan' => [
                    ['jabatan' => 'Petugas Penerima / Amil', 'nama' => $penerimaan->petugas?->name ?? 'Petugas Pengumpulan'],
                    ['jabatan' => 'Penyetor / Muzakki', 'nama' => $penerimaan->muzakki?->nama ?? '-'],
                ],
            ], [
                'kertas' => 'a5',
                'orientasi' => 'landscape',
                'tipografi' => 'sans',
                'draft' => $penerimaan->status === 'batal',
                'cetak_ulang_ke' => $cetakKe > 1 ? $cetakKe : null,
                'qr_payload' => $urlVerifikasi,
                'qr_label' => 'Pindai QR untuk memeriksa keabsahan kwitansi secara publik',
            ]);

            return [
                'kwitansi' => $kwitansi,
                'cetak_ke' => $cetakKe,
                'pdf' => $pdf,
            ];
        });
    }
}
