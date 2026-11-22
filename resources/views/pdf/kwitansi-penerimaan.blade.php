@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">KWITANSI PENERIMAAN ZIS</div>
    <div class="nomor-dokumen">Nomor: {{ $kwitansi->nomor }}</div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Tanggal Transaksi</td>
            <td class="titik">:</td>
            <td>{{ $tanggal_cetak ?? $penerimaan->tanggal?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Telah Terima Dari</td>
            <td class="titik">:</td>
            <td>
                <strong>{{ $penerimaan->muzakki?->nama ?? '-' }}</strong>
                @if (! empty($penerimaan->muzakki?->kode))
                    ({{ $penerimaan->muzakki->kode }})
                @endif
                @if (! empty($penerimaan->muzakki?->nik_masked))
                    - NIK: {{ $penerimaan->muzakki->nik_masked }}
                @endif
            </td>
        </tr>
        @if ($penerimaan->upz)
            <tr>
                <td class="label">Melalui UPZ</td>
                <td class="titik">:</td>
                <td>{{ $penerimaan->upz->nama }} ({{ $penerimaan->upz->kode }})</td>
            </tr>
        @endif
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td class="titik">:</td>
            <td>
                {{ $penerimaan->metodeBayar?->nama ?? '-' }}
                @if ($penerimaan->rekening)
                    ({{ $penerimaan->rekening->bank }} - {{ $penerimaan->rekening->no_rekening }})
                @endif
            </td>
        </tr>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 7%">No</th>
                <th style="width: 22%">Jenis Dana</th>
                <th style="width: 28%">Sumber Dana</th>
                <th>Keterangan</th>
                <th class="kanan" style="width: 22%">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($penerimaan->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->sumberDana?->jenisDana?->nama ?? '-' }}</td>
                    <td>{{ $item->sumberDana?->nama ?? '-' }}</td>
                    <td>{{ $item->keterangan ?: '-' }}</td>
                    <td class="kanan">{{ number_format((int) $item->jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="kanan">Total Penerimaan</th>
                <th class="kanan">Rp {{ number_format((int) $nominal, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    @if (! empty($terbilang))
        <div class="terbilang-kotak">
            <strong>Terbilang:</strong> {{ $terbilang }}
        </div>
    @endif

    @includeWhen(! empty($penandatangan), 'pdf.partials.tanda-tangan')
@endsection
