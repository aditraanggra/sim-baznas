<?php

namespace App\Services\Pdf;

use App\Services\QrCodeSvg;
use App\Services\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Symfony\Component\HttpFoundation\Response;

class DokumenPdfService
{
    public function siapkanData(array $data = [], array $opsi = []): array
    {
        $kertas = strtolower((string) ($opsi['kertas'] ?? 'a4'));
        $orientasi = strtolower((string) ($opsi['orientasi'] ?? ($kertas === 'a5' ? 'landscape' : 'portrait')));
        $tipografi = strtolower((string) ($opsi['tipografi'] ?? 'sans'));
        $qrPayload = $opsi['qr_payload'] ?? ($data['qr_payload'] ?? null);

        $kop = array_merge(config('sisfozis.kop_surat', []), $data['kop'] ?? []);
        $kop['logo_data_uri'] ??= $this->logoDataUri();

        if (isset($data['nominal']) && is_numeric($data['nominal'])) {
            $data['terbilang'] ??= Terbilang::rupiah((int) $data['nominal']);
        }

        return [
            ...$data,
            'kop' => $kop,
            'kertas' => $kertas,
            'orientasi' => $orientasi,
            'tipografi' => $tipografi === 'serif' ? 'serif' : 'sans',
            'draft' => (bool) ($opsi['draft'] ?? ($data['draft'] ?? false)),
            'salinan' => (bool) ($opsi['salinan'] ?? ($data['salinan'] ?? false)),
            'cetak_ulang_ke' => isset($opsi['cetak_ulang_ke'])
                ? (int) $opsi['cetak_ulang_ke']
                : (isset($data['cetak_ulang_ke']) ? (int) $data['cetak_ulang_ke'] : null),
            'qr_payload' => $qrPayload,
            'qr_label' => (string) ($opsi['qr_label'] ?? ($data['qr_label'] ?? 'Pindai QR untuk verifikasi keaslian dokumen')),
            'qr_data_uri' => ! empty($qrPayload) ? QrCodeSvg::dataUri((string) $qrPayload) : null,
        ];
    }

    public function html(string $view, array $data = [], array $opsi = []): string
    {
        return view($view, $this->siapkanData($data, $opsi))->render();
    }

    public function buat(string $view, array $data = [], array $opsi = []): DomPdfWrapper
    {
        $konteks = $this->siapkanData($data, $opsi);
        $fontDefault = $konteks['tipografi'] === 'serif' ? 'DejaVu Serif' : 'DejaVu Sans';

        return Pdf::loadView($view, $konteks)
            ->setPaper($konteks['kertas'], $konteks['orientasi'])
            ->setOption([
                'defaultFont' => $fontDefault,
                'isRemoteEnabled' => false,
            ]);
    }

    public function unduh(string $view, string $namaBerkas, array $data = [], array $opsi = []): Response
    {
        return $this->buat($view, $data, $opsi)->download($namaBerkas);
    }

    public function stream(string $view, string $namaBerkas, array $data = [], array $opsi = []): Response
    {
        return $this->buat($view, $data, $opsi)->stream($namaBerkas);
    }

    private function logoDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<circle cx="32" cy="32" r="30" fill="#ffffff" stroke="#000000" stroke-width="2.5"/>'
            .'<circle cx="32" cy="32" r="24" fill="none" stroke="#000000" stroke-width="1"/>'
            .'<path d="M32 14 L36 26 L48 26 L38 33 L42 46 L32 38 L22 46 L26 33 L16 26 L28 26 Z" fill="#000000"/>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
