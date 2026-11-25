<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Terima Permohonan - {{ $permohonan->kode_tracking }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 9mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        table.kop-tt {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5pt solid #000000;
            padding-bottom: 4pt;
            margin-bottom: 7pt;
        }
        table.kop-tt td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 48pt;
        }
        .kop-logo img {
            width: 42pt;
            height: auto;
        }
        .kop-teks {
            padding-left: 6pt;
        }
        .kop-lembaga {
            font-size: 10pt;
            font-weight: bold;
        }
        .kop-kabupaten {
            font-size: 9pt;
            font-weight: bold;
        }
        .kop-alamat, .kop-kontak {
            font-size: 6.5pt;
            color: #222222;
        }
        .judul-tt {
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin: 4pt 0 2pt;
        }
        .subjudul-tt {
            text-align: center;
            font-size: 7.5pt;
            margin-bottom: 8pt;
        }
        .kotak-lacak {
            border: 1.2pt solid #000000;
            padding: 7pt 9pt;
            margin-bottom: 8pt;
            background-color: #fafafa;
        }
        table.lacak-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.lacak-grid td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kode-label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            color: #333333;
        }
        .kode-besar {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.8pt;
            margin: 2pt 0 4pt;
        }
        .panduan-lacak {
            font-size: 7pt;
            color: #222222;
            line-height: 1.3;
        }
        .qr-sel {
            width: 68pt;
            text-align: right;
        }
        .qr-sel img {
            width: 60pt;
            height: 60pt;
        }
        table.tabel-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 7pt;
        }
        table.tabel-info th, table.tabel-info td {
            border: 0.5pt solid #000000;
            padding: 3.5pt 5pt;
            font-size: 7.8pt;
            vertical-align: top;
        }
        table.tabel-info th {
            background-color: #efefef;
            text-align: left;
            font-weight: bold;
        }
        table.tabel-info td.lbl {
            width: 28%;
            font-weight: bold;
        }
        table.tabel-ttd {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
        }
        table.tabel-ttd td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 7.5pt;
            padding: 0 6pt;
        }
        .ruang-ttd {
            height: 36pt;
        }
        .nama-ttd {
            font-weight: bold;
            text-decoration: underline;
        }
        .catatan-bawah {
            margin-top: 8pt;
            border-top: 0.4pt solid #666666;
            padding-top: 4pt;
            font-size: 6.5pt;
            color: #333333;
        }
    </style>
</head>
<body>
    <table class="kop-tt">
        <tr>
            <td class="kop-logo">
                <img src="{{ $kop['logo_data_uri'] }}" alt="Logo BAZNAS">
            </td>
            <td class="kop-teks">
                <div class="kop-lembaga">{{ $kop['lembaga'] ?? 'BADAN AMIL ZAKAT NASIONAL (BAZNAS)' }}</div>
                <div class="kop-kabupaten">{{ $kop['kabupaten'] ?? 'KABUPATEN CIANJUR' }}</div>
                <div class="kop-alamat">{{ $kop['alamat'] ?? '' }}</div>
                <div class="kop-kontak">
                    @if (! empty($kop['whatsapp'])) Whatsapp: {{ $kop['whatsapp'] }} | @endif
                    Telp: {{ $kop['telepon'] ?? '-' }} | Email: {{ $kop['email'] ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="judul-tt">TANDA TERIMA PERMOHONAN BANTUAN</div>
    <div class="subjudul-tt">
        No. Agenda: <strong>{{ $permohonan->nomor }}</strong> &middot; Tanggal: {{ $permohonan->tanggal?->translatedFormat('d F Y') }}
    </div>

    <div class="kotak-lacak">
        <table class="lacak-grid">
            <tr>
                <td>
                    <div class="kode-label">Kode Lacak Publik Permohonan</div>
                    <div class="kode-besar">{{ $permohonan->kode_tracking }}</div>
                    <div class="panduan-lacak">
                        <strong>Cara Pantau Status Permohonan:</strong><br>
                        1. Pindai QR di samping atau buka laman: <strong>{{ route('publik.lacak-permohonan') }}</strong><br>
                        2. Masukkan Kode Lacak di atas dan <strong>4 digit akhir telepon</strong> ({{ $telepon_hint }}).
                    </div>
                </td>
                @if (! empty($qr_data_uri))
                    <td class="qr-sel">
                        <img src="{{ $qr_data_uri }}" alt="QR Lacak Permohonan">
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <table class="tabel-info">
        <tr>
            <th colspan="2">I. Data Pemohon</th>
        </tr>
        <tr>
            <td class="lbl">Nama Pemohon</td>
            <td>
                <strong>{{ $permohonan->namaPemohonEfektif() ?: '-' }}</strong>
                @if ($permohonan->pemohon_sama_mustahik)
                    <span>(Diri sendiri / sama dengan penerima manfaat)</span>
                @else
                    <span>({{ $permohonan->pemohon_hubungan ?: 'Perwakilan' }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">No. Telepon Pemohon</td>
            <td>{{ $permohonan->teleponPemohonEfektif() ?: '-' }}</td>
        </tr>
        @if (! $permohonan->pemohon_sama_mustahik && $permohonan->pemohon_alamat)
            <tr>
                <td class="lbl">Alamat Pemohon</td>
                <td>{{ $permohonan->pemohon_alamat }}</td>
            </tr>
        @endif
    </table>

    <table class="tabel-info">
        <tr>
            <th colspan="2">II. Penerima Manfaat (Mustahik) &amp; Permohonan</th>
        </tr>
        <tr>
            <td class="lbl">Nama Mustahik</td>
            <td><strong>{{ $permohonan->mustahik?->nama ?? '-' }}</strong> ({{ $permohonan->mustahik?->kode ?? '-' }})</td>
        </tr>
        <tr>
            <td class="lbl">Wilayah / Alamat</td>
            <td>
                {{ $permohonan->mustahik?->alamat ?: '-' }}
                @if ($permohonan->mustahik?->desa)
                    , Desa {{ $permohonan->mustahik->desa->nama }}
                @endif
                @if ($permohonan->mustahik?->kecamatan)
                    , Kec. {{ $permohonan->mustahik->kecamatan->nama }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">Program &amp; Dana</td>
            <td>
                {{ $permohonan->program?->nama ?? '-' }}
                ({{ $permohonan->jenisDana?->nama ?? '-' }}{{ $permohonan->asnaf ? ' - '.$permohonan->asnaf->nama : '' }})
            </td>
        </tr>
        <tr>
            <td class="lbl">Nominal Diajukan</td>
            <td><strong>Rp {{ number_format((int) $permohonan->nominal_diajukan, 0, ',', '.') }}</strong></td>
        </tr>
        @if ($permohonan->uraian)
            <tr>
                <td class="lbl">Keperluan</td>
                <td>{{ $permohonan->uraian }}</td>
            </tr>
        @endif
    </table>

    <table class="tabel-ttd">
        <tr>
            <td>
                Petugas Pelayanan Mustahik<br>
                BAZNAS Kabupaten Cianjur
                <div class="ruang-ttd"></div>
                <div class="nama-ttd">( {{ $permohonan->pembuat?->name ?? 'Petugas Pelayanan' }} )</div>
            </td>
            <td>
                Pemohon / Penerima Bukti<br>
                Cianjur, {{ $permohonan->tanggal?->translatedFormat('d F Y') }}
                <div class="ruang-ttd"></div>
                <div class="nama-ttd">( {{ $permohonan->namaPemohonEfektif() ?: 'Pemohon' }} )</div>
            </td>
        </tr>
    </table>

    <div class="catatan-bawah">
        Simpan tanda terima ini dengan baik. Layanan permohonan bantuan BAZNAS Kabupaten Cianjur tidak dipungut biaya apa pun.
    </div>
</body>
</html>
