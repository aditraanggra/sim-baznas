<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul ?? 'Dokumen SISFOZIS' }}</title>
    <style>
        @page {
            margin: {{ ($kertas ?? 'a4') === 'a5' ? '12mm 15mm' : '20mm' }};
        }
        body {
            font-family: {{ ($tipografi ?? 'sans') === 'serif' ? "'DejaVu Serif', serif" : "'DejaVu Sans', sans-serif" }};
            font-size: {{ ($tipografi ?? 'sans') === 'serif' ? '11pt' : '10pt' }};
            color: #000000;
            line-height: 1.45;
            margin: 0;
        }
        .watermark-draft {
            position: fixed;
            top: 38%;
            left: 16%;
            width: 68%;
            text-align: center;
            font-size: 56pt;
            font-weight: bold;
            letter-spacing: 6pt;
            color: rgba(0, 0, 0, 0.12);
            border: 2pt solid rgba(0, 0, 0, 0.12);
            padding: 8pt 0;
            transform: rotate(-35deg);
            z-index: -1;
        }
        .badge-status-bar {
            text-align: right;
            margin-bottom: 6pt;
        }
        .badge-cetak-ulang {
            display: inline-block;
            border: 1pt solid #000000;
            padding: 2pt 6pt;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
        }
        .kop-tabel {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5pt double #000000;
            margin-bottom: 12pt;
            padding-bottom: 6pt;
        }
        .kop-tabel td {
            border: none;
            vertical-align: middle;
            padding: 0 0 6pt 0;
        }
        .kop-logo {
            width: 70pt;
            text-align: center;
        }
        .kop-logo img {
            width: 58pt;
            height: auto;
        }
        .kop-teks {
            text-align: center;
            padding-right: 20pt;
        }
        .kop-lembaga {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
        }
        .kop-kabupaten {
            font-size: 12pt;
            font-weight: bold;
        }
        .kop-alamat, .kop-kontak {
            font-size: 8.5pt;
            margin-top: 1pt;
        }
        .judul-dokumen {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 6pt 0 2pt;
        }
        .nomor-dokumen {
            text-align: center;
            font-size: 9.5pt;
            margin-bottom: 12pt;
        }
        table.data-tabel {
            width: 100%;
            border-collapse: collapse;
            margin: 8pt 0 10pt;
        }
        table.data-tabel th, table.data-tabel td {
            border: 0.6pt solid #000000;
            padding: 4pt 6pt;
            text-align: left;
            vertical-align: top;
        }
        table.data-tabel th {
            background: #f2f2f2;
            font-weight: bold;
        }
        table.meta-tabel {
            width: 100%;
            border-collapse: collapse;
            margin: 6pt 0 10pt;
        }
        table.meta-tabel td {
            padding: 3pt 4pt;
            vertical-align: top;
        }
        table.meta-tabel td.label {
            width: 28%;
            font-weight: bold;
        }
        table.meta-tabel td.titik {
            width: 3%;
        }
        .kanan {
            text-align: right;
        }
        .terbilang-kotak {
            border: 0.75pt solid #000000;
            padding: 5pt 8pt;
            margin: 8pt 0;
            font-style: italic;
        }
        .ttd-blok {
            margin-top: 16pt;
            page-break-inside: avoid;
        }
        .ttd-kota-tanggal {
            text-align: right;
            margin-bottom: 6pt;
            font-size: 9.5pt;
        }
        table.ttd-tabel {
            width: 100%;
            border-collapse: collapse;
        }
        td.ttd-kolom {
            text-align: center;
            vertical-align: top;
            padding: 0 8pt;
        }
        .ttd-jabatan {
            font-size: 9.5pt;
        }
        .ttd-ruang {
            height: 44pt;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            font-size: 9.5pt;
        }
        .ttd-nip {
            font-size: 8.5pt;
        }
        .qr-footer {
            margin-top: 14pt;
            border-top: 0.5pt solid #000000;
            padding-top: 6pt;
            page-break-inside: avoid;
        }
        table.qr-tabel {
            width: 100%;
            border-collapse: collapse;
        }
        td.qr-gambar {
            width: 68pt;
            vertical-align: middle;
        }
        td.qr-gambar img {
            width: 60pt;
            height: 60pt;
        }
        td.qr-keterangan {
            vertical-align: middle;
            font-size: 8pt;
            padding-left: 6pt;
        }
    </style>
    @stack('gaya')
</head>
<body>
    @if (! empty($draft))
        <div class="watermark-draft">DRAFT</div>
    @endif

    @if (($cetak_ulang_ke ?? null) !== null && $cetak_ulang_ke >= 1)
        <div class="badge-status-bar">
            <span class="badge-cetak-ulang">CETAK ULANG KE-{{ $cetak_ulang_ke }}</span>
        </div>
    @elseif (! empty($salinan))
        <div class="badge-status-bar">
            <span class="badge-cetak-ulang">SALINAN</span>
        </div>
    @endif

    @includeWhen($tampilkan_kop ?? true, 'pdf.partials.kop')

    @yield('konten')

    @if (! empty($qr_data_uri))
        <div class="qr-footer">
            <table class="qr-tabel">
                <tr>
                    <td class="qr-gambar">
                        <img src="{{ $qr_data_uri }}" alt="QR Verifikasi">
                    </td>
                    <td class="qr-keterangan">
                        <div><strong>Verifikasi Dokumen</strong></div>
                        <div>{{ $qr_label }}</div>
                        @if (! empty($nomor))
                            <div>Nomor: {{ $nomor }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif
</body>
</html>
