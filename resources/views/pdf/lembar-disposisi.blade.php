@extends('pdf.layouts.dokumen')

@push('gaya')
<style>
    .judul-disposisi {
        text-align: center;
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5pt;
        margin: 2pt 0 4pt;
    }
    .subjudul-disposisi {
        text-align: center;
        font-size: 8.5pt;
        margin-bottom: 8pt;
    }
    table.tabel-kotak {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6pt;
    }
    table.tabel-kotak td, table.tabel-kotak th {
        border: 0.6pt solid #000000;
        padding: 3pt 5pt;
        font-size: 8pt;
        vertical-align: middle;
    }
    table.tabel-kotak th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: left;
    }
    .cb-item {
        display: inline-block;
        margin-right: 12pt;
        font-size: 7.5pt;
        white-space: nowrap;
    }
    .cb-box {
        display: inline-block;
        width: 9pt;
        height: 9pt;
        border: 0.8pt solid #000000;
        text-align: center;
        line-height: 8pt;
        font-size: 7.5pt;
        font-weight: bold;
        margin-right: 3pt;
        vertical-align: middle;
    }
    .cb-box.checked {
        background-color: #000000;
        color: #ffffff;
    }
    table.tabel-pimpinan {
        width: 100%;
        border-collapse: collapse;
        margin: 6pt 0;
    }
    table.tabel-pimpinan th, table.tabel-pimpinan td {
        border: 0.6pt solid #000000;
        padding: 4pt 6pt;
        font-size: 7.5pt;
        vertical-align: top;
    }
    table.tabel-pimpinan th {
        background-color: #f2f2f2;
        text-align: center;
        font-weight: bold;
    }
    .ruang-paraf {
        height: 38pt;
    }
</style>
@endpush

@section('konten')
    <div class="judul-disposisi">LEMBAR DISPOSISI PERMOHONAN BANTUAN</div>
    <div class="subjudul-disposisi">Nomor Register: <strong>{{ $permohonan->nomor }}</strong> | Tanggal: {{ $permohonan->tanggal?->translatedFormat('d F Y') }}</div>

    @php
        $progNama = strtolower($permohonan->program?->nama ?? '');
        $asnafKode = strtoupper($permohonan->asnaf?->kode ?? '');
        $asnafNama = strtoupper($permohonan->asnaf?->nama ?? '');
        $jenisDana = strtoupper($permohonan->jenisDana?->nama ?? '');

        // 5 Pilar BAZNAS Cianjur:
        // Makmur (Ekonomi), Cerdas (Pendidikan), Takwa (Dakwah), Sehat (Kesehatan), Peduli (Kemanusiaan)
        $isMakmur = str_contains($progNama, 'ekonomi') || str_contains($progNama, 'makmur');
        $isCerdas = str_contains($progNama, 'pendidikan') || str_contains($progNama, 'cerdas');
        $isTakwa = str_contains($progNama, 'dakwah') || str_contains($progNama, 'takwa') || str_contains($progNama, 'advokasi');
        $isSehat = str_contains($progNama, 'kesehatan') || str_contains($progNama, 'sehat');
        $isPeduli = str_contains($progNama, 'kemanusiaan') || str_contains($progNama, 'peduli');
    @endphp

    <table class="tabel-kotak">
        <tr>
            <th colspan="4" style="background-color: #e8e8e8; font-size: 8.5pt;">I. PENERIMA MANFAAT (MUSTAHIK) &amp; DATA PEMOHON</th>
        </tr>
        <tr>
            <td style="width: 22%;"><strong>Nama Mustahik</strong></td>
            <td style="width: 38%;"><strong>{{ $permohonan->mustahik?->nama }}</strong> ({{ $permohonan->mustahik?->kode }})</td>
            <td style="width: 18%;"><strong>Telp. Mustahik</strong></td>
            <td style="width: 22%;">{{ $permohonan->mustahik?->telepon ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Alamat Mustahik</strong></td>
            <td colspan="3">
                {{ $permohonan->mustahik?->alamat ?: '-' }}
                @if ($permohonan->mustahik?->desa)
                    , Desa {{ $permohonan->mustahik->desa->nama }}
                @endif
                @if ($permohonan->mustahik?->kecamatan)
                    , Kec. {{ $permohonan->mustahik->kecamatan->nama }}
                @endif
            </td>
        </tr>
        <tr>
            <td><strong>Data Pemohon</strong></td>
            <td>
                @if ($permohonan->pemohon_sama_mustahik)
                    <em>Sama dengan Penerima Manfaat ({{ $permohonan->mustahik?->nama }})</em>
                @else
                    <strong>{{ $permohonan->namaPemohonEfektif() ?: '-' }}</strong>
                    ({{ $permohonan->pemohon_hubungan ?: 'Perwakilan' }})
                    @if ($permohonan->pemohon_alamat)
                        <div style="font-size: 7pt; color: #333;">{{ $permohonan->pemohon_alamat }}</div>
                    @endif
                @endif
            </td>
            <td><strong>Telp. Pemohon</strong></td>
            <td>{{ $permohonan->teleponPemohonEfektif() ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Perihal / Uraian</strong></td>
            <td colspan="3">{{ $permohonan->uraian ?: ($permohonan->program?->nama ?? 'Bantuan ZIS') }}</td>
        </tr>
        <tr>
            <td><strong>Nominal Diajukan</strong></td>
            <td><strong>Rp {{ number_format((int) $permohonan->nominal_diajukan, 0, ',', '.') }}</strong></td>
            <td><strong>Nominal Disetujui</strong></td>
            <td>
                @if ($permohonan->nominal_disetujui)
                    <strong style="color: #0b6b32;">Rp {{ number_format((int) $permohonan->nominal_disetujui, 0, ',', '.') }}</strong>
                @else
                    <em>Dalam proses</em>
                @endif
            </td>
        </tr>
        <tr>
            <td><strong>Mata Pencaharian</strong></td>
            <td colspan="3">
                <span class="cb-item"><span class="cb-box"></span> Karyawan</span>
                <span class="cb-item"><span class="cb-box"></span> Petani</span>
                <span class="cb-item"><span class="cb-box"></span> Nelayan</span>
                <span class="cb-item"><span class="cb-box"></span> Penyedia Jasa</span>
                <span class="cb-item"><span class="cb-box"></span> Pedagang</span>
                <span class="cb-item"><span class="cb-box"></span> Peternak</span>
                <span class="cb-item"><span class="cb-box"></span> Buruh Harian Lepas</span>
                <span class="cb-item"><span class="cb-box"></span> Lainnya</span>
            </td>
        </tr>
    </table>

    <table class="tabel-kotak">
        <tr>
            <th colspan="2" style="background-color: #e8e8e8; font-size: 8.5pt;">II. KLASIFIKASI PROGRAM & ASNAF BAZNAS CIANJUR</th>
        </tr>
        <tr>
            <td style="width: 24%;"><strong>5 Pilar Program</strong></td>
            <td>
                <span class="cb-item"><span class="cb-box {{ $isMakmur ? 'checked' : '' }}">{!! $isMakmur ? '&#10003;' : '' !!}</span> <strong>Cianjur Makmur</strong> (Ekonomi)</span>
                <span class="cb-item"><span class="cb-box {{ $isCerdas ? 'checked' : '' }}">{!! $isCerdas ? '&#10003;' : '' !!}</span> <strong>Cianjur Cerdas</strong> (Pendidikan)</span>
                <span class="cb-item"><span class="cb-box {{ $isTakwa ? 'checked' : '' }}">{!! $isTakwa ? '&#10003;' : '' !!}</span> <strong>Cianjur Takwa</strong> (Dakwah)</span><br>
                <span class="cb-item"><span class="cb-box {{ $isSehat ? 'checked' : '' }}">{!! $isSehat ? '&#10003;' : '' !!}</span> <strong>Cianjur Sehat</strong> (Kesehatan)</span>
                <span class="cb-item"><span class="cb-box {{ $isPeduli ? 'checked' : '' }}">{!! $isPeduli ? '&#10003;' : '' !!}</span> <strong>Cianjur Peduli</strong> (Kemanusiaan)</span>
            </td>
        </tr>
        <tr>
            <td><strong>Sumber Dana</strong></td>
            <td>
                <span class="cb-item"><span class="cb-box {{ str_contains($jenisDana, 'ZAKAT') ? 'checked' : '' }}">{!! str_contains($jenisDana, 'ZAKAT') ? '&#10003;' : '' !!}</span> Zakat</span>
                <span class="cb-item"><span class="cb-box"></span> Zakat via UPZ</span>
                <span class="cb-item"><span class="cb-box {{ str_contains($jenisDana, 'INFAK') ? 'checked' : '' }}">{!! str_contains($jenisDana, 'INFAK') ? '&#10003;' : '' !!}</span> Infak Terikat</span>
                <span class="cb-item"><span class="cb-box"></span> Infak via UPZ</span>
                <span class="cb-item"><span class="cb-box"></span> Infak Tidak Terikat</span>
                <span class="cb-item"><span class="cb-box {{ str_contains($jenisDana, 'DSKL') ? 'checked' : '' }}">{!! str_contains($jenisDana, 'DSKL') ? '&#10003;' : '' !!}</span> DSKL</span>
                <span class="cb-item"><span class="cb-box {{ str_contains($jenisDana, 'CSR') ? 'checked' : '' }}">{!! str_contains($jenisDana, 'CSR') ? '&#10003;' : '' !!}</span> CSR</span>
            </td>
        </tr>
        <tr>
            <td><strong>Asnaf Penerima</strong></td>
            <td>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'FAKIR' ? 'checked' : '' }}">{!! $asnafKode === 'FAKIR' ? '&#10003;' : '' !!}</span> Fakir</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'MISKIN' ? 'checked' : '' }}">{!! $asnafKode === 'MISKIN' ? '&#10003;' : '' !!}</span> Miskin</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'AMIL' ? 'checked' : '' }}">{!! $asnafKode === 'AMIL' ? '&#10003;' : '' !!}</span> Amil</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'MUALAF' ? 'checked' : '' }}">{!! $asnafKode === 'MUALAF' ? '&#10003;' : '' !!}</span> Mualaf</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'RIQOB' ? 'checked' : '' }}">{!! $asnafKode === 'RIQOB' ? '&#10003;' : '' !!}</span> Riqab</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'GHARIMIN' ? 'checked' : '' }}">{!! $asnafKode === 'GHARIMIN' ? '&#10003;' : '' !!}</span> Gharimin</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'FISABILILLAH' ? 'checked' : '' }}">{!! $asnafKode === 'FISABILILLAH' ? '&#10003;' : '' !!}</span> Fisabilillah</span>
                <span class="cb-item"><span class="cb-box {{ $asnafKode === 'IBNU_SABIL' ? 'checked' : '' }}">{!! $asnafKode === 'IBNU_SABIL' ? '&#10003;' : '' !!}</span> Ibnu Sabil</span>
            </td>
        </tr>
    </table>

    <table class="tabel-pimpinan">
        <thead>
            <tr>
                <th colspan="3" style="background-color: #e8e8e8; font-size: 8.5pt;">III. LEMBAR DISPOSISI PERSETUJUAN BESARAN BANTUAN</th>
            </tr>
            <tr>
                <th style="width: 33.3%;">1. KEPALA BAGIAN PENDIS PENDAG<br><span style="font-size: 6.5pt; font-weight: normal;">(Hasil Asesmen & Rekomendasi)</span></th>
                <th style="width: 33.3%;">2. WAKIL KETUA II<br><span style="font-size: 6.5pt; font-weight: normal;">(Ajuan Besaran Bantuan)</span></th>
                <th style="width: 33.4%;">3. KETUA BAZNAS<br><span style="font-size: 6.5pt; font-weight: normal;">(ACC & Keputusan Besaran)</span></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div><strong>Rekomendasi Besaran:</strong></div>
                    <div style="font-size: 8.5pt; margin: 2pt 0;">
                        @if ($permohonan->nominal_disetujui)
                            Rp {{ number_format((int) $permohonan->nominal_disetujui, 0, ',', '.') }}
                        @else
                            Rp ........................................
                        @endif
                    </div>
                    <div style="margin-top: 4pt;"><strong>Catatan Asesmen:</strong></div>
                    <div style="min-height: 24pt; font-size: 7pt; color: #333;">
                        {{ $permohonan->catatan_asesmen ?: '-' }}
                    </div>
                    <div class="ruang-paraf"></div>
                    <div style="text-align: center; border-top: 0.5pt solid #000; padding-top: 2pt;">
                        <strong>Kabag Pendis & Pendag</strong><br>
                        <span style="font-size: 6.5pt;">Tanggal: ................................</span>
                    </div>
                </td>
                <td>
                    <div><strong>Ajuan Besaran Bantuan:</strong></div>
                    <div style="font-size: 8.5pt; margin: 2pt 0;">
                        @if ($permohonan->nominal_disetujui)
                            Rp {{ number_format((int) $permohonan->nominal_disetujui, 0, ',', '.') }}
                        @else
                            Rp ........................................
                        @endif
                    </div>
                    <div style="margin-top: 4pt;"><strong>Catatan / Disposisi:</strong></div>
                    <div style="min-height: 24pt; font-size: 7pt; color: #333;">
                        Setuju diajukan bantuan sesuai hasil asesmen lapangan.
                    </div>
                    <div class="ruang-paraf"></div>
                    <div style="text-align: center; border-top: 0.5pt solid #000; padding-top: 2pt;">
                        <strong>Wakil Ketua II</strong><br>
                        <span style="font-size: 6.5pt;">Tanggal: ................................</span>
                    </div>
                </td>
                <td>
                    <div><strong>ACC Besaran Bantuan:</strong></div>
                    <div style="font-size: 8.5pt; margin: 2pt 0; color: #0b6b32;">
                        @if ($permohonan->nominal_disetujui)
                            <strong>Rp {{ number_format((int) $permohonan->nominal_disetujui, 0, ',', '.') }}</strong>
                        @else
                            Rp ........................................
                        @endif
                    </div>
                    <div style="margin-top: 4pt;"><strong>Instruksi Ketua:</strong></div>
                    <div style="min-height: 24pt; font-size: 7pt; color: #333;">
                        Disetujui. Lanjutkan proses pencairan dan serah terima bantuan.
                    </div>
                    <div class="ruang-paraf"></div>
                    <div style="text-align: center; border-top: 0.5pt solid #000; padding-top: 2pt;">
                        <strong>Ketua BAZNAS</strong><br>
                        <span style="font-size: 6.5pt;">Tanggal: ................................</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="data-tabel" style="margin-top: 4pt;">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">No. Disposisi</th>
                <th style="width: 18%">Dari</th>
                <th style="width: 18%">Kepada</th>
                <th style="width: 16%">Waktu</th>
                <th>Instruksi / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($disposisi as $idx => $d)
                <tr>
                    <td class="tengah">{{ $idx + 1 }}</td>
                    <td>{{ $d->nomor }}</td>
                    <td>{{ $d->dariUser?->name ?? '-' }}</td>
                    <td>{{ $d->keUser?->name ?? $d->ke_peran ?? '-' }}</td>
                    <td>{{ $d->waktu_tindak_lanjut?->format('d/m/Y H:i') ?? $d->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $d->instruksi ?: ($d->catatan ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="tengah">Belum ada riwayat disposisi tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
