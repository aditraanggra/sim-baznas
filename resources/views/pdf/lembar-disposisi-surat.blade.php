@extends('pdf.layouts.dokumen')

@push('gaya')
<style>
    table.kotak {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10pt;
    }
    table.kotak th, table.kotak td {
        border: 0.6pt solid #000000;
        padding: 4pt 6pt;
        font-size: 9pt;
        vertical-align: top;
    }
    table.kotak th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: left;
    }
</style>
@endpush

@section('konten')
    <div class="judul-dokumen">LEMBAR DISPOSISI SURAT MASUK</div>
    <div class="nomor-dokumen">Nomor Agenda: <strong>{{ $surat->nomor_agenda }}</strong> | Sifat: {{ strtoupper($surat->sifat) }}</div>

    <table class="kotak">
        <tr>
            <th style="width: 24%;">Nomor Surat Asal</th>
            <td style="width: 26%;">{{ $surat->nomor_surat }}</td>
            <th style="width: 22%;">Tanggal Surat</th>
            <td style="width: 28%;">{{ $surat->tanggal_surat?->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Pengirim</th>
            <td><strong>{{ $surat->pengirim }}</strong></td>
            <th>Tanggal Diterima</th>
            <td>{{ $surat->tanggal_terima?->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Perihal</th>
            <td colspan="3"><strong>{{ $surat->perihal }}</strong></td>
        </tr>
        @if ($surat->ringkasan)
            <tr>
                <th>Ringkasan Isi</th>
                <td colspan="3">{{ $surat->ringkasan }}</td>
            </tr>
        @endif
        <tr>
            <th>Status Terakhir</th>
            <td colspan="3">{{ \App\Models\SuratMasuk::labelStatus($surat->status_kode) }}</td>
        </tr>
    </table>

    <table class="kotak">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 14%;">Tahap &amp; Waktu</th>
                <th style="width: 22%;">Dari &rarr; Kepada</th>
                <th style="width: 30%;">Instruksi / Arahan</th>
                <th style="width: 28%;">Status &amp; Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($disposisi as $idx => $d)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <div><strong>{{ strtoupper($d->tahap) }}</strong></div>
                        <div style="font-size: 8pt;">{{ $d->waktu?->format('d/m/Y H:i') }}</div>
                        @if ($d->tenggat)
                            <div style="font-size: 8pt;">Tenggat: {{ $d->tenggat->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td>
                        <div>Dari: {{ $d->dariUser?->name ?? '-' }}</div>
                        <div><strong>Kepada: {{ $d->kepadaUser?->name ?? '-' }}</strong></div>
                    </td>
                    <td>
                        {{ $d->instruksi }}
                        @if ($d->keputusan)
                            <div style="font-size: 8pt; margin-top: 2pt;"><em>Keputusan: {{ $d->keputusan }}</em></div>
                        @endif
                    </td>
                    <td>
                        <div><strong>{{ strtoupper($d->status) }}</strong></div>
                        @if ($d->catatan_tindak_lanjut)
                            <div style="font-size: 8pt; margin-top: 2pt;">{{ $d->catatan_tindak_lanjut }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 12pt;">Belum ada catatan disposisi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ttd-blok">
        <div class="ttd-kota-tanggal">{{ $kop['kota'] ?? 'Cianjur' }}, {{ now()->translatedFormat('d F Y') }}</div>
        <table class="ttd-tabel">
            <tr>
                <td class="ttd-kolom" style="width: 50%;">
                    <div class="ttd-jabatan">Pencatat / Admin Arsip</div>
                    <div class="ttd-ruang"></div>
                    <div class="ttd-nama">{{ $surat->pencatat?->name ?? 'Admin Arsip' }}</div>
                </td>
                <td class="ttd-kolom" style="width: 50%;">
                    <div class="ttd-jabatan">Ketua / Pimpinan BAZNAS Kab. Cianjur</div>
                    <div class="ttd-ruang"></div>
                    <div class="ttd-nama">( ........................................ )</div>
                </td>
            </tr>
        </table>
    </div>
@endsection
