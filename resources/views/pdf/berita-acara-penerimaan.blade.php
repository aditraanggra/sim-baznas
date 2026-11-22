@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">BERITA ACARA PENERIMAAN HARIAN DANA ZIS</div>
    <div class="nomor-dokumen">
        Nomor: {{ $ba->nomor }}
        @if ($ba->revisi_ke > 0)
            (Revisi Ke-{{ $ba->revisi_ke }})
        @endif
    </div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Tanggal Penerimaan</td>
            <td class="titik">:</td>
            <td>{{ $ba->tanggal?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status Dokumen</td>
            <td class="titik">:</td>
            <td>{{ strtoupper($ba->status) }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Transaksi Final</td>
            <td class="titik">:</td>
            <td>{{ number_format((int) $ba->jumlah_transaksi, 0, ',', '.') }} transaksi</td>
        </tr>
        <tr>
            <td class="label">Total Penerimaan</td>
            <td class="titik">:</td>
            <td><strong>Rp {{ number_format((int) $nominal, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if (! empty($terbilang))
        <div class="terbilang-kotak">
            <strong>Terbilang:</strong> {{ $terbilang }}
        </div>
    @endif

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 6%">No</th>
                <th style="width: 24%">Sumber Dana</th>
                <th style="width: 18%">Jenis Dana</th>
                <th class="kanan" style="width: 22%">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap_sumber as $idx => $baris)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $baris['sumber_dana'] }}</td>
                    <td>{{ $baris['jenis_dana'] }}</td>
                    <td class="kanan">{{ number_format((int) $baris['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 6%">No</th>
                <th style="width: 24%">Nomor Kwitansi</th>
                <th>Muzakki</th>
                <th style="width: 20%">Metode</th>
                <th class="kanan" style="width: 20%">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaksi as $idx => $trx)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $trx->kwitansi?->nomor ?? $trx->nomor }}</td>
                    <td>{{ $trx->muzakki?->nama ?? '-' }}</td>
                    <td>{{ $trx->metodeBayar?->nama ?? '-' }}</td>
                    <td class="kanan">{{ number_format((int) $trx->totalRupiah(), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (! empty($ba->catatan))
        <p style="font-size: 9pt; margin-top: 8pt;"><strong>Catatan:</strong> {{ $ba->catatan }}</p>
    @endif

    @includeWhen(! empty($penandatangan), 'pdf.partials.tanda-tangan')
@endsection
