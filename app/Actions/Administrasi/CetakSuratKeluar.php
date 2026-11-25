<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\SuratKeluar;
use App\Models\SuratKeluarCetakLog;
use App\Services\Pdf\DokumenPdfService;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class CetakSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(private readonly DokumenPdfService $pdfService) {}

    /**
     * @return array{surat: SuratKeluar, cetak_ulang_ke: int|null, pdf: DomPdfWrapper}
     */
    public function jalankan(array $data): array
    {
        $tervalidasi = validator($data, [
            'surat_keluar_id' => ['required', 'integer', 'exists:surat_keluar,id'],
            'pratinjau' => ['nullable', 'boolean'],
        ])->validate();

        $pratinjau = (bool) ($tervalidasi['pratinjau'] ?? false);

        return DB::transaction(function () use ($tervalidasi, $pratinjau): array {
            $surat = SuratKeluar::with(['jenisSurat', 'pembuat', 'penyetuju'])
                ->lockForUpdate()
                ->findOrFail((int) $tervalidasi['surat_keluar_id']);

            $cetakUlangKe = null;
            if ($surat->status_kode === 'disetujui' && ! $pratinjau) {
                SuratKeluarCetakLog::create([
                    'surat_keluar_id' => $surat->id,
                    'user_id' => Auth::id(),
                    'waktu' => now(),
                    'ip_address' => Request::ip(),
                ]);

                $jumlahCetak = $surat->cetakLogs()->count();
                if ($jumlahCetak > 1) {
                    $cetakUlangKe = $jumlahCetak - 1;
                    $this->catatAudit(
                        'cetak_ulang',
                        SuratKeluar::class,
                        $surat->id,
                        null,
                        ['nomor' => $surat->nomor, 'cetak_ulang_ke' => $cetakUlangKe],
                    );
                }
            }

            $isDraft = $surat->status_kode !== 'disetujui';
            $urlVerifikasi = route('publik.verifikasi-surat', $surat->uuid);

            $pdf = $this->pdfService->buat('pdf.surat-keluar', [
                'judul' => $surat->jenisSurat?->nama ?? 'SURAT KELUAR',
                'nomor' => $surat->nomor,
                'surat' => $surat,
            ], [
                'kertas' => 'a4',
                'orientasi' => 'portrait',
                'tipografi' => 'serif',
                'draft' => $isDraft,
                'cetak_ulang_ke' => $cetakUlangKe,
                'qr_payload' => ! $isDraft ? $urlVerifikasi : null,
                'qr_label' => 'Pindai QR untuk memverifikasi keaslian surat dinas BAZNAS Kabupaten Cianjur',
            ]);

            return [
                'surat' => $surat,
                'cetak_ulang_ke' => $cetakUlangKe,
                'pdf' => $pdf,
            ];
        });
    }
}
