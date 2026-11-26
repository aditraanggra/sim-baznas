<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>RKAT {{ $versi->tahun }}</title>
    <style>
        @page { margin: 2cm 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }
        h1 { font-size: 12pt; margin: 0 0 2pt; }
        p { margin: 0 0 2pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
        th, td { border: 0.5pt solid #666; padding: 2pt 4pt; }
        th { background: #e5e5e5; text-align: left; }
        .kanan { text-align: right; }
        .induk td { font-weight: bold; background: #f4f4f4; }
        .pisah { page-break-after: always; }
        .catatan { margin-top: 10pt; font-size: 8pt; }
    </style>
</head>
<body>
@foreach ($bagian as $i => $b)
    <div @class(['pisah' => ! $loop->last])>
        <h1>{{ $b['judul'] }}</h1>
        @foreach (array_slice($b['kepala'], 1) as $k)
            <p>{{ $k }}</p>
        @endforeach
        <table>
            <thead>
                <tr>
                    <th style="width: 10%">No</th>
                    <th>Uraian</th>
                    <th class="kanan" style="width: 10%">Persen</th>
                    <th class="kanan" style="width: 22%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($b['baris'] as $r)
                    <tr @class(['induk' => $r['induk']])>
                        <td>{{ $r['no'] }}</td>
                        <td style="padding-left: {{ 4 + $r['tingkat'] * 10 }}pt">{{ $r['uraian'] }}</td>
                        <td class="kanan">{{ $r['persen'] }}</td>
                        <td class="kanan">{{ number_format($r['jumlah'], 0, ',', '.') }}{{ $r['satuan'] === 'Rupiah' ? '' : ' '.$r['satuan'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($loop->last && $catatan)
            <div class="catatan">
                @foreach ($catatan as $c)
                    <p>{{ $c }}</p>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
@if (! empty($kegiatan_gabungan))
    <div class="pisah"></div>
    <div>
        <h1>Konsolidasi Rincian Kegiatan 3 Bidang</h1>
        <p>RKAT {{ $versi->tahun }} - {{ $versi->label() }} - {{ ucfirst($versi->status) }}</p>
        @foreach ($kegiatan_gabungan as $kelompok)
            <h2 style="font-size: 10pt; margin: 10pt 0 4pt;">{{ $kelompok['bidang_label'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">Pos</th>
                        <th>Kegiatan &amp; Uraian Pos</th>
                        <th class="kanan" style="width: 10%">Jml</th>
                        <th class="kanan" style="width: 7%">Frek</th>
                        <th class="kanan" style="width: 16%">Besaran</th>
                        <th class="kanan" style="width: 18%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelompok['baris'] as $r)
                        <tr>
                            <td>{{ $r['pos_kode'] }}</td>
                            <td>
                                <strong>{{ $r['nama_kegiatan'] }}</strong><br>
                                <span style="font-size: 7.5pt; color: #444;">{{ $r['pos_nama'] }}@if ($r['program_nama']) · Program: {{ $r['program_nama'] }}@endif</span>
                            </td>
                            <td class="kanan">{{ number_format($r['jumlah'], 0, ',', '.') }} {{ $r['satuan'] }}</td>
                            <td class="kanan">{{ $r['frekuensi'] }}x</td>
                            <td class="kanan">{{ number_format($r['besaran'], 0, ',', '.') }}</td>
                            <td class="kanan">{{ number_format($r['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada rincian kegiatan.</td>
                        </tr>
                    @endforelse
                    <tr class="induk">
                        <td colspan="5">Subtotal {{ $kelompok['bidang_label'] }}</td>
                        <td class="kanan">{{ number_format($kelompok['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    </div>
@endif
</body>
</html>
