@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">LAPORAN PENDISTRIBUSIAN DAN PENDAYAGUNAAN</div>
    <div class="nomor-dokumen">Periode: {{ $label_periode }}</div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Total Penyaluran</td>
            <td class="titik">:</td>
            <td><strong>Rp {{ number_format((int) $nominal, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Transaksi / Mustahik</td>
            <td class="titik">:</td>
            <td>{{ number_format((int) $jumlah_transaksi, 0, ',', '.') }} transaksi / {{ number_format((int) $jumlah_mustahik, 0, ',', '.') }} mustahik</td>
        </tr>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 11%">Tanggal</th>
                <th style="width: 18%">Nomor</th>
                <th>Mustahik</th>
                <th style="width: 18%">Program</th>
                <th style="width: 13%">Asnaf / Dana</th>
                <th class="kanan" style="width: 15%">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $idx => $b)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $b['tanggal'] }}</td>
                    <td>{{ $b['nomor_penyaluran'] }}</td>
                    <td>{{ $b['mustahik'] }}</td>
                    <td>{{ $b['program'] }}</td>
                    <td>{{ $b['asnaf'] }} ({{ $b['sumber_dana'] }})</td>
                    <td class="kanan">{{ number_format((int) $b['jumlah'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data penyaluran untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="kanan">Grand Total</th>
                <th class="kanan">Rp {{ number_format((int) $nominal, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
