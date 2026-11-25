@extends('pdf.layouts.dokumen')

@section('konten')
    <div class="judul-dokumen">BUKU AGENDA SURAT MASUK DAN SURAT KELUAR</div>
    <div class="nomor-dokumen">
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB |
        Total: {{ $ringkasan['total'] ?? 0 }} dokumen (Masuk: {{ $ringkasan['masuk'] ?? 0 }}, Keluar: {{ $ringkasan['keluar'] ?? 0 }})
    </div>

    <table class="data-tabel" style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 10%;">Tipe</th>
                <th style="width: 18%;">Nomor Agenda / Surat</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 16%;">Pengirim / Tujuan</th>
                <th style="width: 19%;">Perihal</th>
                <th style="width: 12%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item['tanggal_label'] ?? '-' }}</td>
                    <td>{{ $item['tipe_label'] ?? '-' }}</td>
                    <td><strong>{{ $item['nomor'] ?? '-' }}</strong></td>
                    <td>{{ $item['jenis_surat'] ?? '-' }}</td>
                    <td>{{ $item['pihak'] ?? '-' }}</td>
                    <td>{{ $item['perihal'] ?? '-' }}</td>
                    <td>{{ $item['status_label'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 12pt;">Tidak ada data arsip surat sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
