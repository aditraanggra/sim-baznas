<?php

use App\Services\Pdf\DokumenPdfService;
use App\Services\QrCodeSvg;
use App\Services\Terbilang;

it('F0-22: Terbilang mengonversi angka dan rupiah Indonesia dengan akurat', function () {
    expect(Terbilang::angka(0))->toBe('nol')
        ->and(Terbilang::angka(11))->toBe('sebelas')
        ->and(Terbilang::angka(15))->toBe('lima belas')
        ->and(Terbilang::angka(100))->toBe('seratus')
        ->and(Terbilang::angka(1000))->toBe('seribu')
        ->and(Terbilang::rupiah(1_250_000))->toBe('Satu juta dua ratus lima puluh ribu rupiah')
        ->and(Terbilang::rupiah(3_525_454_479, false))->toBe('tiga miliar lima ratus dua puluh lima juta empat ratus lima puluh empat ribu empat ratus tujuh puluh sembilan rupiah')
        ->and(Terbilang::angka(1_000_000_000_000))->toBe('satu triliun');
});

it('F0-22: QrCodeSvg menghasilkan SVG deterministik dan data URI valid', function () {
    $svg1 = QrCodeSvg::svg('KW/BAZNAS-CJR/2026/XI/00001');
    $svg2 = QrCodeSvg::svg('KW/BAZNAS-CJR/2026/XI/00001');
    $dataUri = QrCodeSvg::dataUri('KW/BAZNAS-CJR/2026/XI/00001');

    expect($svg1)->toContain('<svg')
        ->and($svg1)->toBe($svg2)
        ->and($dataUri)->toStartWith('data:image/svg+xml;base64,');
});

it('F0-22: DokumenPdfService merender kop, terbilang, QR, tanda tangan, watermark DRAFT, dan label CETAK ULANG', function () {
    $service = app(DokumenPdfService::class);

    $htmlDraft = $service->html('pdf.dokumen-standar', [
        'judul' => 'Kwitansi Penerimaan Zakat',
        'nomor' => 'KW/BAZNAS-CJR/2026/XI/00001',
        'tanggal' => '22 Nov 2026',
        'pihak' => 'H. Ahmad Subhan',
        'nominal' => 1_250_000,
        'penandatangan' => [
            ['jabatan' => 'Petugas Penerimaan', 'nama' => 'Siti Aminah'],
        ],
    ], [
        'draft' => true,
        'cetak_ulang_ke' => 2,
        'qr_payload' => 'https://sim.baznaskabcianjur.com/verifikasi/kwitansi/00001',
    ]);

    expect($htmlDraft)->toContain('KABUPATEN CIANJUR')
        ->and($htmlDraft)->toContain('KW/BAZNAS-CJR/2026/XI/00001')
        ->and($htmlDraft)->toContain('Rp 1.250.000')
        ->and($htmlDraft)->toContain('Satu juta dua ratus lima puluh ribu rupiah')
        ->and($htmlDraft)->toContain('data:image/svg+xml;base64,')
        ->and($htmlDraft)->toContain('DRAFT')
        ->and($htmlDraft)->toContain('CETAK ULANG KE-2')
        ->and($htmlDraft)->toContain('Siti Aminah');

    $htmlFinal = $service->html('pdf.dokumen-standar', [
        'judul' => 'Kwitansi Penerimaan Zakat',
        'nomor' => 'KW/BAZNAS-CJR/2026/XI/00001',
    ], [
        'draft' => false,
    ]);

    expect($htmlFinal)->not->toContain('>DRAFT<');
});

it('F0-22: DokumenPdfService menghasilkan binary PDF valid untuk A4 serif dan A5 landscape sans', function () {
    $service = app(DokumenPdfService::class);

    $pdfA4 = $service->buat('pdf.dokumen-standar', [
        'judul' => 'Surat Keterangan Pengumpulan',
        'nomor' => '001/SK/BAZNAS-CJR/XI/2026',
        'isi' => 'Dokumen resmi BAZNAS Kabupaten Cianjur.',
    ], [
        'kertas' => 'a4',
        'orientasi' => 'portrait',
        'tipografi' => 'serif',
        'qr_payload' => '001/SK/BAZNAS-CJR/XI/2026',
    ])->output();

    $pdfA5 = $service->buat('pdf.dokumen-standar', [
        'judul' => 'Kwitansi Penerimaan',
        'nomor' => 'KW/BAZNAS-CJR/2026/XI/00001',
        'nominal' => 500_000,
    ], [
        'kertas' => 'a5',
        'tipografi' => 'sans',
        'draft' => true,
    ])->output();

    expect($pdfA4)->toStartWith('%PDF-')
        ->and($pdfA5)->toStartWith('%PDF-');
});
