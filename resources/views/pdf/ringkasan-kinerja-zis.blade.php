@extends('pdf.layouts.dokumen')

@section('konten')
    @php
        $kp = $kinerja['kinerja_pengumpulan'] ?? [];
        $kd = $kinerja['kinerja_distribusi'] ?? [];
        $lampiran = $kinerja['ringkasan_lampiran'] ?? [];
        $rekap = $kinerja['rekap_bulanan'] ?? [];
        $rupiah = fn (int|float $v) => 'Rp '.number_format((int) $v, 0, ',', '.');
        $angka = fn (int|float $v) => number_format((int) $v, 0, ',', '.');
        $daftarLampiran = [
            'L2' => 'Penerimaan ZIS (Dalam Neraca)',
            'L2/LN' => 'Penerimaan Luar Neraca',
            'L3' => 'Penyaluran ZIS',
            'L4' => 'Program Penyaluran',
            'L5' => 'Sasaran Muzakki & Mustahik',
            'L6' => 'Biaya Operasional Amil',
            'L7' => 'Pengembangan SDM & Umum',
        ];
    @endphp

    <div class="judul-dokumen">RINGKASAN KINERJA PENGELOLAAN ZIS TAHUN {{ $tahun }}</div>
    <div class="nomor-dokumen">
        Data agregat non-pribadi · Diperbarui per {{ $kinerja['diperbarui_pada'] ?? '-' }}
    </div>

    <table class="meta-tabel">
        <tr>
            <td class="label">Tahun Anggaran</td>
            <td class="titik">:</td>
            <td>{{ $tahun }} ({{ strtoupper($kinerja['status_tahun'] ?? 'draft') }})</td>
            <td class="label">Versi Acuan RKAT</td>
            <td class="titik">:</td>
            <td>{{ $kinerja['versi_rkat'] ?? 'Belum ada versi disahkan' }}</td>
        </tr>
        <tr>
            <td class="label">Total Penerimaan Final</td>
            <td class="titik">:</td>
            <td><strong>{{ $rupiah($kp['total_penerimaan'] ?? 0) }}</strong> ({{ $angka($kp['jumlah_transaksi'] ?? 0) }} transaksi)</td>
            <td class="label">Total Penyaluran Dicairkan</td>
            <td class="titik">:</td>
            <td><strong>{{ $rupiah($kd['total_penyaluran'] ?? 0) }}</strong> ({{ $angka($kd['mustahik_terbantu'] ?? 0) }} mustahik)</td>
        </tr>
        <tr>
            <td class="label">Rasio Penyaluran (ACR)</td>
            <td class="titik">:</td>
            <td><strong>{{ $kd['rasio_penyaluran_label'] ?? '-' }}</strong></td>
            <td class="label">UPZ Aktif Menyetor</td>
            <td class="titik">:</td>
            <td>{{ $angka($kp['upz_menyetor'] ?? 0) }} dari {{ $angka($kp['total_upz_aktif'] ?? 0) }} UPZ</td>
        </tr>
    </table>

    <p style="font-weight: bold; margin: 10pt 0 4pt;">1. Rekapitulasi Penerimaan dan Penyaluran Bulanan (Januari – Desember {{ $tahun }})</p>
    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 24%">Bulan</th>
                <th class="kanan" style="width: 14%">Trx Terima</th>
                <th class="kanan" style="width: 22%">Penerimaan</th>
                <th class="kanan" style="width: 14%">Trx Salur</th>
                <th class="kanan" style="width: 22%">Penyaluran</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap as $baris)
                <tr>
                    <td>{{ $baris['label'] }}</td>
                    <td class="kanan">{{ $angka($baris['transaksi_terima'] ?? 0) }}</td>
                    <td class="kanan">{{ $rupiah($baris['penerimaan'] ?? 0) }}</td>
                    <td class="kanan">{{ $angka($baris['transaksi_salur'] ?? 0) }}</td>
                    <td class="kanan">{{ $rupiah($baris['penyaluran'] ?? 0) }}</td>
                </tr>
            @endforeach
            <tr style="background: #f4f4f4; font-weight: bold;">
                <td>TOTAL TAHUN {{ $tahun }}</td>
                <td class="kanan">{{ $angka($kp['jumlah_transaksi'] ?? 0) }}</td>
                <td class="kanan">{{ $rupiah($kp['total_penerimaan'] ?? 0) }}</td>
                <td class="kanan">{{ $angka($kd['jumlah_transaksi'] ?? 0) }}</td>
                <td class="kanan">{{ $rupiah($kd['total_penyaluran'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="font-weight: bold; margin: 10pt 0 4pt;">2. Komposisi Penerimaan per Jenis &amp; Sumber Dana</p>
    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 16%">Kode</th>
                <th style="width: 24%">Jenis Dana</th>
                <th>Sumber Dana</th>
                <th class="kanan" style="width: 26%">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kp['per_sumber_dana'] ?? [] as $sd)
                <tr>
                    <td>{{ $sd['kode'] }}</td>
                    <td>{{ $sd['jenis_dana'] }}</td>
                    <td>{{ $sd['label'] }}</td>
                    <td class="kanan">{{ $rupiah($sd['nilai']) }}</td>
                </tr>
            @endforeach
            <tr style="background: #f4f4f4; font-weight: bold;">
                <td colspan="3">Langsung BAZNAS: {{ $rupiah($kp['total_langsung'] ?? 0) }} · Melalui UPZ: {{ $rupiah($kp['total_upz'] ?? 0) }}</td>
                <td class="kanan">{{ $rupiah($kp['total_penerimaan'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="font-weight: bold; margin: 10pt 0 4pt;">3. Penyaluran per Asnaf &amp; Program</p>
    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 50%">Asnaf / Non-Zakat (Nominal)</th>
                <th style="width: 50%">Program Penyaluran (Nominal)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @forelse ($kd['penyaluran_per_asnaf'] ?? [] as $a)
                        <div>{{ $a['label'] }}: <strong>{{ $rupiah($a['nilai']) }}</strong></div>
                    @empty
                        <div>Belum ada penyaluran dicairkan pada tahun {{ $tahun }}.</div>
                    @endforelse
                </td>
                <td>
                    @forelse ($kd['penyaluran_per_program'] ?? [] as $pr)
                        <div>{{ $pr['label'] }}: <strong>{{ $rupiah($pr['nilai']) }}</strong></div>
                    @empty
                        <div>Belum ada penyaluran dicairkan pada tahun {{ $tahun }}.</div>
                    @endforelse
                </td>
            </tr>
        </tbody>
    </table>

    <p style="font-weight: bold; margin: 10pt 0 4pt;">4. Ringkasan Capaian RKAT L2–L7 vs Realisasi</p>
    <table class="data-tabel">
        <thead>
            <tr>
                <th style="width: 12%">Lamp.</th>
                <th>Uraian Lampiran RKAT</th>
                <th class="kanan" style="width: 21%">Target RKAT</th>
                <th class="kanan" style="width: 21%">Realisasi</th>
                <th class="kanan" style="width: 12%">Capaian</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($daftarLampiran as $kode => $nama)
                @php $row = $lampiran[$kode] ?? ['target_rkat' => 0, 'realisasi' => 0, 'persen_label' => '-']; @endphp
                <tr>
                    <td>{{ $kode }}</td>
                    <td>{{ $nama }}</td>
                    <td class="kanan">{{ $rupiah($row['target_rkat']) }}</td>
                    <td class="kanan">{{ $rupiah($row['realisasi']) }}</td>
                    <td class="kanan">{{ $row['persen_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
