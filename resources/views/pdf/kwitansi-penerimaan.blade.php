<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Setoran ZIS - {{ $kwitansi->nomor }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 8mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #000000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .halaman {
            page-break-after: always;
            position: relative;
            min-height: 100%;
        }
        .halaman:last-child {
            page-break-after: avoid;
        }
        .badge-lembar {
            text-align: right;
            margin-bottom: 2pt;
        }
        .badge-lembar-teks {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }
        .badge-lembar-sub {
            font-size: 6.5pt;
            color: #333333;
            font-style: italic;
        }
        table.kop-kwitansi {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5pt solid #000000;
            padding-bottom: 4pt;
            margin-bottom: 6pt;
        }
        table.kop-kwitansi td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 48pt;
            text-align: left;
        }
        .kop-logo img {
            width: 42pt;
            height: auto;
        }
        .kop-teks {
            text-align: left;
            padding-left: 6pt;
        }
        .kop-lembaga {
            font-size: 10pt;
            font-weight: bold;
            color: #000000;
        }
        .kop-kabupaten {
            font-size: 9pt;
            font-weight: bold;
            color: #000000;
        }
        .kop-alamat, .kop-kontak {
            font-size: 6.5pt;
            color: #222222;
        }
        .judul-kwitansi {
            text-align: center;
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 4pt 0 6pt;
            letter-spacing: 0.5pt;
        }
        table.tabel-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }
        table.tabel-meta td {
            padding: 1.5pt 2pt;
            vertical-align: top;
            font-size: 7.5pt;
        }
        table.tabel-meta td.label {
            width: 24%;
            font-weight: bold;
        }
        table.tabel-meta td.titik {
            width: 2%;
        }
        table.tabel-rincian {
            width: 100%;
            border-collapse: collapse;
            margin: 4pt 0 6pt;
        }
        table.tabel-rincian th, table.tabel-rincian td {
            border: 0.5pt solid #000000;
            padding: 3pt 4pt;
            font-size: 7pt;
            vertical-align: top;
        }
        table.tabel-rincian th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .kanan {
            text-align: right;
        }
        .tengah {
            text-align: center;
        }
        .kotak-terbilang {
            border: 0.5pt solid #000000;
            padding: 3pt 5pt;
            margin-bottom: 6pt;
            font-size: 7.5pt;
            background-color: #fafafa;
        }
        .kotak-doa {
            border: 0.5pt dashed #444444;
            padding: 4pt 6pt;
            margin-bottom: 6pt;
            font-size: 7pt;
            font-style: italic;
            background-color: #fcfcfc;
        }
        table.tabel-ttd {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
        }
        table.tabel-ttd td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 4pt;
            font-size: 7.5pt;
        }
        .ttd-ruang {
            height: 32pt;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        .catatan-kaki {
            margin-top: 6pt;
            border-top: 0.4pt solid #777777;
            padding-top: 3pt;
            font-size: 5.5pt;
            color: #444444;
            line-height: 1.2;
        }
        .watermark-draft {
            position: fixed;
            top: 40%;
            left: 15%;
            width: 70%;
            text-align: center;
            font-size: 42pt;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.08);
            border: 2pt solid rgba(0, 0, 0, 0.08);
            transform: rotate(-30deg);
            z-index: -1;
        }
        .qr-badge {
            float: right;
            margin-top: -24pt;
        }
        .qr-badge img {
            width: 32pt;
            height: 32pt;
        }
    </style>
</head>
<body>
    @if (! empty($draft))
        <div class="watermark-draft">DRAFT</div>
    @endif

    @php
        $lembar = [
            [
                'nomor' => '1',
                'judul' => 'Lembar 1',
                'arsip' => 'Untuk Arsip Wajib Zakat / Penyetor',
            ],
            [
                'nomor' => '2',
                'judul' => 'Lembar 2',
                'arsip' => 'Untuk Arsip BAZNAS Kabupaten Cianjur',
            ],
        ];

        $objekUtama = $penerimaan->items->first()?->sumberDana?->jenisDana?->nama ?? 'ZIS';
        $viaBayar = $penerimaan->metodeBayar?->nama ?? 'Kas';
        if ($penerimaan->rekening) {
            $viaBayar .= ' (' . $penerimaan->rekening->bank . ')';
        }
        $periode = $penerimaan->tanggal ? $penerimaan->tanggal->translatedFormat('F Y') : now()->translatedFormat('F Y');
    @endphp

    @foreach ($lembar as $idx => $l)
        <div class="halaman">
            <div class="badge-lembar">
                <span class="badge-lembar-teks">{{ $l['judul'] }}</span> - 
                <span class="badge-lembar-sub">{{ $l['arsip'] }}</span>
                @if (($cetak_ulang_ke ?? null) !== null && $cetak_ulang_ke > 1)
                    <span style="border: 0.5pt solid #000; padding: 1pt 3pt; font-size: 6pt; font-weight: bold; margin-left: 4pt;">CETAK ULANG KE-{{ $cetak_ulang_ke }}</span>
                @endif
            </div>

            <table class="kop-kwitansi">
                <tr>
                    <td class="kop-logo">
                        <img src="{{ $kop['logo_data_uri'] }}" alt="Logo BAZNAS">
                    </td>
                    <td class="kop-teks">
                        <div class="kop-lembaga">{{ $kop['lembaga'] ?? 'BADAN AMIL ZAKAT NASIONAL' }}</div>
                        <div class="kop-kabupaten">{{ $kop['kabupaten'] ?? 'KABUPATEN CIANJUR' }}</div>
                        <div class="kop-alamat">{{ $kop['alamat'] ?? 'Jl. Raya Bandung Sadewata No. 108B Cianjur' }}</div>
                        <div class="kop-kontak">
                            @if (! empty($kop['whatsapp'])) Whatsapp: {{ $kop['whatsapp'] }} | @endif
                            Telp: {{ $kop['telepon'] ?? '(0263) 264866' }} | Email: {{ $kop['email'] ?? 'baznaskab.cianjur@baznas.go.id' }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="judul-kwitansi">BUKTI SETORAN {{ strtoupper($objekUtama) }}</div>

            <table class="tabel-meta">
                <tr>
                    <td class="label">Nomor</td>
                    <td class="titik">:</td>
                    <td style="width: 44%"><strong>{{ $kwitansi->nomor }}</strong></td>
                    <td class="label" style="width: 12%">Periode</td>
                    <td class="titik">:</td>
                    <td>{{ $periode }}</td>
                </tr>
                <tr>
                    <td class="label">Telah terima dari</td>
                    <td class="titik">:</td>
                    <td colspan="4"><strong>{{ $penerimaan->muzakki?->nama ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">NPWZ</td>
                    <td class="titik">:</td>
                    <td>{{ $penerimaan->muzakki?->npwz ?? '-' }}</td>
                    <td class="label">NPWP/NIK</td>
                    <td class="titik">:</td>
                    <td>{{ $penerimaan->muzakki?->nik_masked ?? ($penerimaan->muzakki?->npwp ?: '-') }}</td>
                </tr>
                @if ($penerimaan->upz)
                    <tr>
                        <td class="label">Melalui UPZ</td>
                        <td class="titik">:</td>
                        <td colspan="4">{{ $penerimaan->upz->nama }} ({{ $penerimaan->upz->kode }})</td>
                    </tr>
                @endif
            </table>

            <table class="tabel-rincian">
                <thead>
                    <tr>
                        <th style="width: 22%">Objek ZIS</th>
                        <th style="width: 38%">Uraian Penerimaan</th>
                        <th style="width: 18%">Via</th>
                        <th style="width: 22%">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penerimaan->items as $item)
                        <tr>
                            <td>{{ $item->sumberDana?->jenisDana?->nama ?? '-' }}</td>
                            <td>
                                {{ $item->sumberDana?->nama ?? '-' }}
                                @if (! empty($item->keterangan))
                                    <div style="font-size: 6pt; color: #444;">{{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $viaBayar }}</td>
                            <td class="kanan"><strong>{{ number_format((int) $item->jumlah, 0, ',', '.') }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="kanan">Total</th>
                        <th class="kanan">Rp {{ number_format((int) $nominal, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>

            <div class="kotak-terbilang">
                <strong>Terbilang:</strong> {{ $terbilang ?? '-' }}
            </div>

            <div class="kotak-doa">
                <strong>Doa / Catatan:</strong><br>
                "Semoga Allah SWT memberikan pahala kepada {{ $penerimaan->muzakki?->nama ?? 'Bapak/Ibu' }} atas harta yang telah dikeluarkan dan menjadi berkah dan suci atas harta yang lainnya."
            </div>

            <table class="tabel-ttd">
                <tr>
                    <td>
                        Pengesahan Petugas Amil<br>
                        Tgl: {{ $tanggal_cetak ?? $penerimaan->tanggal?->format('d/m/Y') }}<br>
                        Petugas: {{ $penerimaan->petugas?->name ?? 'BAZNAS Kab. Cianjur' }}
                        <div class="ttd-ruang"></div>
                        <div class="ttd-nama">( BAZNAS Kab. Cianjur )</div>
                    </td>
                    <td>
                        Penyetor / Wajib Zakat<br>
                        Tgl: {{ $tanggal_cetak ?? $penerimaan->tanggal?->format('d/m/Y') }}<br>
                        Nama: {{ $penerimaan->muzakki?->nama ?? '-' }}
                        <div class="ttd-ruang"></div>
                        <div class="ttd-nama">( {{ $penerimaan->muzakki?->nama ?? 'Penyetor' }} )</div>
                    </td>
                </tr>
            </table>

            @if (! empty($qr_data_uri))
                <div class="qr-badge">
                    <img src="{{ $qr_data_uri }}" alt="QR Verifikasi">
                </div>
            @endif

            <div class="catatan-kaki">
                * Nilai donasi natura ditaksir dalam jumlah rupiah oleh petugas yang mengesahkan bukti setoran.<br>
                ** BAZNAS Cianjur hanya menerima donasi dari sumber yang halal, tidak bertentangan dengan peraturan yang berlaku, dan bukan merupakan pencucian uang.
            </div>
        </div>
    @endforeach
</body>
</html>
