@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">LAPORAN PENERIMAAN DANA ZIS</div>
    <div class="nomor-dokumen">Periode: {{ $label_periode }}</div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Total Penerimaan</td>
            <td class="titik">:</td>
            <td><strong>Rp {{ number_format((int) $nominal, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Jumlah Transaksi Final</td>
            <td class="titik">:</td>
            <td>{{ number_format((int) $jumlah_transaksi, 0, ',', '.') }} transaksi</td>
        </tr>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 6%">No</th>
                <th style="width: 12%">Tanggal</th>
                <th style="width: 22%">No. Kwitansi</th>
                <th>Muzakki</th>
                <th style="width: 20%">Sumber Dana</th>
                <th class="kanan" style="width: 18%">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $idx => $b)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $b['tanggal'] }}</td>
                    <td>{{ $b['nomor_kwitansi'] }}</td>
                    <td>{{ $b['muzakki'] }}</td>
                    <td>{{ $b['sumber_dana'] }}</td>
                    <td class="kanan">{{ number_format((int) $b['jumlah'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data penerimaan untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="kanan">Grand Total</th>
                <th class="kanan">Rp {{ number_format((int) $nominal, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
