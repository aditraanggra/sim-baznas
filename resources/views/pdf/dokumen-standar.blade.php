@extends('pdf.layouts.dokumen')

@section('konten')
    @if (! empty($judul))
        <div class="judul-dokumen">{{ $judul }}</div>
    @endif

    @if (! empty($nomor))
        <div class="nomor-dokumen">Nomor: {{ $nomor }}</div>
    @endif

    <table class="meta-tabel">
        @if (! empty($tanggal))
            <tr>
                <td class="label">Tanggal</td>
                <td class="titik">:</td>
                <td>{{ $tanggal }}</td>
            </tr>
        @endif
        @if (! empty($pihak))
            <tr>
                <td class="label">{{ $label_pihak ?? 'Diterima dari / Kepada' }}</td>
                <td class="titik">:</td>
                <td>{{ $pihak }}</td>
            </tr>
        @endif
        @if (isset($nominal))
            <tr>
                <td class="label">Jumlah Nominal</td>
                <td class="titik">:</td>
                <td><strong>Rp {{ number_format((int) $nominal, 0, ',', '.') }}</strong></td>
            </tr>
        @endif
        @if (! empty($perihal))
            <tr>
                <td class="label">Perihal / Uraian</td>
                <td class="titik">:</td>
                <td>{{ $perihal }}</td>
            </tr>
        @endif
    </table>

    @if (! empty($terbilang))
        <div class="terbilang-kotak">
            <strong>Terbilang:</strong> {{ $terbilang }}
        </div>
    @endif

    @if (! empty($rincian))
        <table class="data-tabel">
            <thead>
                <tr>
                    <th style="width: 8%">No</th>
                    <th>Uraian</th>
                    <th class="kanan" style="width: 28%">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rincian as $idx => $baris)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $baris['uraian'] ?? '-' }}</td>
                        <td class="kanan">
                            @if (isset($baris['jumlah']))
                                {{ number_format((int) $baris['jumlah'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($isi))
        <div class="isi-dokumen">{!! nl2br(e($isi)) !!}</div>
    @endif

    @includeWhen(! empty($penandatangan), 'pdf.partials.tanda-tangan')
@endsection
