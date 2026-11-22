@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">LEMBAR DISPOSISI PERMOHONAN BANTUAN</div>
    <div class="nomor-dokumen">Nomor Permohonan: {{ $permohonan->nomor }}</div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Tanggal Pengajuan</td>
            <td class="titik">:</td>
            <td>{{ $permohonan->tanggal?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Mustahik</td>
            <td class="titik">:</td>
            <td>{{ $permohonan->mustahik?->nama }} ({{ $permohonan->mustahik?->kode }})</td>
        </tr>
        <tr>
            <td class="label">Program / Sub Bidang</td>
            <td class="titik">:</td>
            <td>{{ $permohonan->program?->nama }} ({{ ucfirst($permohonan->sub_bidang) }})</td>
        </tr>
        <tr>
            <td class="label">Jenis Dana / Asnaf</td>
            <td class="titik">:</td>
            <td>{{ $permohonan->jenisDana?->nama ?? '-' }} / {{ $permohonan->asnaf?->nama ?? 'Non-Zakat' }}</td>
        </tr>
        <tr>
            <td class="label">Nominal Diajukan</td>
            <td class="titik">:</td>
            <td>Rp {{ number_format((int) $permohonan->nominal_diajukan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Nominal Disetujui</td>
            <td class="titik">:</td>
            <td>
                @if ($permohonan->nominal_disetujui)
                    <strong>Rp {{ number_format((int) $permohonan->nominal_disetujui, 0, ',', '.') }}</strong>
                @else
                    Belum ditetapkan
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Status Terakhir</td>
            <td class="titik">:</td>
            <td>{{ strtoupper(str_replace('_', ' ', $permohonan->status_kode)) }} (Kesiapan Keuangan: {{ strtoupper($permohonan->kesiapan_keuangan) }})</td>
        </tr>
    </table>

    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 6%">Langkah</th>
                <th style="width: 18%">Nomor Disposisi</th>
                <th style="width: 20%">Dari</th>
                <th style="width: 20%">Tujuan / Peran</th>
                <th style="width: 16%">Waktu</th>
                <th>Catatan / Instruksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($disposisi as $d)
                <tr>
                    <td>{{ $d->langkah }}</td>
                    <td>{{ $d->nomor }}</td>
                    <td>{{ $d->dariUser?->name ?? '-' }}</td>
                    <td>{{ $d->keUser?->name ?? $d->ke_peran ?? '-' }}</td>
                    <td>{{ $d->waktu_tindak_lanjut?->format('d/m/Y H:i') ?? $d->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $d->instruksi ?: ($d->catatan ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada riwayat disposisi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @includeWhen(! empty($penandatangan), 'pdf.partials.tanda-tangan')
@endsection
