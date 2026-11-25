@extends('pdf.layouts.dokumen')

@push('gaya')
<style>
    .surat-tanggal-kanan {
        text-align: right;
        margin: 2pt 0 10pt;
        font-size: 11pt;
    }
    table.surat-meta {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
    }
    table.surat-meta td {
        padding: 1.5pt 2pt;
        vertical-align: top;
        font-size: 11pt;
    }
    table.surat-meta td.lbl {
        width: 72pt;
    }
    table.surat-meta td.sep {
        width: 10pt;
        text-align: center;
    }
    .surat-tujuan {
        margin: 8pt 0 14pt;
        font-size: 11pt;
    }
    .surat-isi {
        font-size: 11pt;
        line-height: 1.55;
        text-align: justify;
        white-space: pre-line;
        margin-bottom: 16pt;
    }
    .surat-ttd-wrap {
        width: 100%;
        margin-top: 14pt;
        page-break-inside: avoid;
    }
    .surat-ttd-kotak {
        width: 46%;
        margin-left: 54%;
        text-align: center;
        font-size: 11pt;
    }
    .surat-ttd-spasi {
        height: 48pt;
    }
    .surat-tembusan {
        margin-top: 18pt;
        font-size: 9.5pt;
        border-top: 0.5pt solid #cccccc;
        padding-top: 6pt;
        white-space: pre-line;
    }
</style>
@endpush

@section('konten')
    @php
        $kota = $kop['kota'] ?? 'Cianjur';
        $tanggalStr = $surat->tanggal ? $surat->tanggal->translatedFormat('d F Y') : '-';
        $sifatLabel = \App\Models\SuratKeluar::SIFAT[$surat->sifat] ?? ucfirst($surat->sifat);
    @endphp

    @if (($surat->jenisSurat?->kode ?? '') === 'SK')
        <div class="judul-dokumen">KEPUTUSAN KETUA BAZNAS KABUPATEN CIANJUR</div>
        <div class="nomor-dokumen">Nomor: {{ $surat->nomor ?? 'DRAFT (BELUM BERNOMOR)' }}</div>
        <div style="text-align: center; font-weight: bold; margin-bottom: 12pt; font-size: 11pt;">
            TENTANG<br>{{ strtoupper($surat->perihal) }}
        </div>
    @else
        <div class="surat-tanggal-kanan">{{ $kota }}, {{ $tanggalStr }}</div>

        <table class="surat-meta">
            <tr>
                <td class="lbl">Nomor</td>
                <td class="sep">:</td>
                <td><strong>{{ $surat->nomor ?? 'DRAFT (Belum Diterbitkan)' }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Sifat</td>
                <td class="sep">:</td>
                <td>{{ $sifatLabel }}</td>
            </tr>
            <tr>
                <td class="lbl">Lampiran</td>
                <td class="sep">:</td>
                <td>{{ $surat->lampiran ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Perihal</td>
                <td class="sep">:</td>
                <td><strong>{{ $surat->perihal }}</strong></td>
            </tr>
        </table>

        <div class="surat-tujuan">
            Kepada Yth.<br>
            <strong>{{ $surat->tujuan }}</strong><br>
            @if ($surat->alamat_tujuan)
                <span style="white-space: pre-line;">{{ $surat->alamat_tujuan }}</span><br>
            @endif
            di Tempat
        </div>
    @endif

    <div class="surat-isi">{{ $surat->isi }}</div>

    <div class="surat-ttd-wrap">
        <div class="surat-ttd-kotak">
            <div>{{ $kota }}, {{ $tanggalStr }}</div>
            <div style="font-weight: bold;">{{ $surat->jabatan_penandatangan }}</div>
            <div class="surat-ttd-spasi"></div>
            <div style="font-weight: bold; text-decoration: underline;">{{ $surat->nama_penandatangan }}</div>
        </div>
    </div>

    @if ($surat->tembusan)
        <div class="surat-tembusan"><strong>Tembusan:</strong>
{{ $surat->tembusan }}</div>
    @endif
@endsection
