@extends('pdf.layouts.dokumen')

@section('konten')
    @php
        $ringkasanAktif = $lampiran && isset($hasil['ringkasan_lampiran'][$lampiran])
            ? $hasil['ringkasan_lampiran'][$lampiran]
            : [
                'target_rkat' => $hasil['total_rkat'],
                'realisasi' => $hasil['total_realisasi'],
                'selisih' => $hasil['total_rkat'] - $hasil['total_realisasi'],
                'persen_label' => $hasil['persen_total_label'],
            ];
    @endphp

    <div class="judul-dokumen">LAPORAN PERBANDINGAN RKAT VS REALISASI</div>
    <div class="nomor-dokumen">
        Periode: {{ $periode }}
        @if (! empty($lampiran))
            · Lampiran {{ $lampiran }}
        @endif
    </div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Tahun / Status</td>
            <td class="titik">:</td>
            <td>{{ $tahun }} ({{ strtoupper($hasil['status_tahun']) }})</td>
            <td class="label">Versi Acuan RKAT</td>
            <td class="titik">:</td>
            <td>{{ $hasil['versi_rkat'] ?? 'Belum ada disahkan' }}</td>
        </tr>
        <tr>
            <td class="label">Total Target RKAT</td>
            <td class="titik">:</td>
            <td><strong>Rp {{ number_format((int) $ringkasanAktif['target_rkat'], 0, ',', '.') }}</strong></td>
            <td class="label">Total Realisasi</td>
            <td class="titik">:</td>
            <td><strong>Rp {{ number_format((int) $ringkasanAktif['realisasi'], 0, ',', '.') }}</strong> ({{ $ringkasanAktif['persen_label'] }})</td>
        </tr>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 11%">Kode Pos</th>
                <th style="width: 7%">Lamp.</th>
                <th>Uraian Pos Anggaran</th>
                <th style="width: 11%">Sumber</th>
                <th class="kanan" style="width: 16%">Target RKAT</th>
                <th class="kanan" style="width: 16%">Realisasi</th>
                <th class="kanan" style="width: 15%">Selisih</th>
                <th class="kanan" style="width: 9%">Capaian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($hasil['baris'] as $b)
                @php
                    $isInduk = $b['agregasi'] === 'jumlah_anak';
                    $satuan = $b['tipe_nilai'] === 'rupiah' ? '' : ' ' . $b['tipe_nilai'];
                @endphp
                <tr @if ($isInduk) style="background: #f4f4f4; font-weight: bold;" @endif>
                    <td>{{ $b['kode'] }}</td>
                    <td>{{ $b['grup'] }}</td>
                    <td style="padding-left: {{ 6 + max(0, $b['tingkat'] - 1) * 10 }}pt;">{{ $b['nama'] }}</td>
                    <td>{{ $isInduk ? 'Rollup' : ucfirst(str_replace('_', ' ', $b['sumber'])) }}</td>
                    <td class="kanan">{{ number_format((int) $b['target_rkat'], 0, ',', '.') }}{{ $satuan }}</td>
                    <td class="kanan">{{ number_format((int) $b['realisasi'], 0, ',', '.') }}{{ $satuan }}</td>
                    <td class="kanan">{{ number_format((int) $b['selisih'], 0, ',', '.') }}{{ $satuan }}</td>
                    <td class="kanan">{{ $b['persen_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data pos RKAT untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
