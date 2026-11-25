<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use App\Models\TemplateSurat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdministrasiSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $daftar = [
                [
                    'kode' => 'BIASA',
                    'nama' => 'Surat Dinas Umum',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Surat dinas korespondensi umum BAZNAS Kabupaten Cianjur.',
                    'template_nama' => 'Surat Dinas Umum Standar',
                    'template_isi' => "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\nDengan hormat, semoga Bapak/Ibu senantiasa berada dalam lindungan Allah Subhanahu Wa Ta'ala serta diberi kelancaran dalam menjalankan aktivitas sehari-hari.\n\nSehubungan dengan {{perihal_ringkas}}, bersama ini kami sampaikan bahwa {{isi_pokok}}.\n\nDemikian surat ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh.",
                    'penutup_default' => 'Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.',
                ],
                [
                    'kode' => 'UND',
                    'nama' => 'Surat Undangan',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Undangan rapat, sosialisasi, atau kegiatan resmi lembaga.',
                    'template_nama' => 'Undangan Kegiatan / Rapat Resmi',
                    'template_isi' => "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\nDengan hormat, mengharap kehadiran Bapak/Ibu/Saudara pada kegiatan {{acara}} yang insya Allah akan dilaksanakan pada:\n\nHari / Tanggal : {{hari_tanggal}}\nWaktu          : {{waktu}}\nTempat         : {{tempat}}\nAgenda         : {{agenda}}\n\nMengingat pentingnya kegiatan dimaksud, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh.",
                    'penutup_default' => 'Demikian undangan ini kami sampaikan, atas kehadiran Bapak/Ibu diucapkan terima kasih.',
                ],
                [
                    'kode' => 'TGS',
                    'nama' => 'Surat Tugas',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Penugasan amil atau tim pelaksana kegiatan dinas luar/lapangan.',
                    'template_nama' => 'Surat Tugas Amil / Tim Lapangan',
                    'template_isi' => "Berdasarkan {{dasar_penugasan}}, Pimpinan BAZNAS Kabupaten Cianjur dengan ini menugaskan kepada:\n\nNama    : {{nama_petugas}}\nJabatan : {{jabatan_petugas}}\n\nUntuk melaksanakan tugas: {{uraian_tugas}}, yang dilaksanakan pada tanggal {{tanggal_pelaksanaan}} bertempat di {{tempat_pelaksanaan}}.\n\nSetelah selesai melaksanakan tugas agar melaporkan hasilnya secara tertulis kepada Pimpinan.",
                    'penutup_default' => 'Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.',
                ],
                [
                    'kode' => 'SK',
                    'nama' => 'Surat Keputusan',
                    'format_nomor' => 'SK-{urut:3}/{lembaga}/{romawi_bulan}/{tahun}',
                    'pisah_urutan' => true,
                    'keterangan' => 'Keputusan Ketua/Pimpinan BAZNAS Kabupaten Cianjur dengan urutan nomor tersendiri per tahun.',
                    'template_nama' => 'Surat Keputusan Ketua BAZNAS',
                    'template_isi' => "Menimbang:\n{{menimbang}}\n\nMengingat:\n{{mengingat}}\n\nMEMUTUSKAN:\n\nMenetapkan : {{tentang_keputusan}}\nKESATU     : {{memutuskan_pertama}}\nKEDUA      : {{memutuskan_kedua}}\nKETIGA     : Keputusan ini berlaku sejak tanggal ditetapkan dan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.",
                    'penutup_default' => 'Ditetapkan di Cianjur.',
                ],
                [
                    'kode' => 'KET',
                    'nama' => 'Surat Keterangan',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Surat keterangan resmi bagi muzakki, mustahik, UPZ, atau mitra.',
                    'template_nama' => 'Surat Keterangan Resmi',
                    'template_isi' => "Yang bertanda tangan di bawah ini, {{jabatan_penandatangan}} BAZNAS Kabupaten Cianjur, dengan ini menerangkan bahwa:\n\nNama / Lembaga : {{nama_subjek}}\nAlamat         : {{alamat_subjek}}\n\nBahwa yang bersangkutan benar {{isi_keterangan}}.\n\nDemikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.",
                    'penutup_default' => null,
                ],
                [
                    'kode' => 'EDR',
                    'nama' => 'Surat Edaran',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Edaran resmi untuk UPZ, instansi, atau jajaran amil.',
                    'template_nama' => 'Surat Edaran Lembaga',
                    'template_isi' => "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\nDalam rangka {{latar_belakang}}, dengan ini disampaikan ketentuan sebagai berikut:\n\n{{pokok_edaran}}\n\nKetentuan ini mulai berlaku sejak {{tanggal_berlaku}} untuk dilaksanakan oleh pihak terkait.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh.",
                    'penutup_default' => null,
                ],
                [
                    'kode' => 'PMH',
                    'nama' => 'Surat Permohonan / Pengantar',
                    'format_nomor' => null,
                    'pisah_urutan' => false,
                    'keterangan' => 'Surat permohonan kerja sama, audiensi, atau pengantar resmi.',
                    'template_nama' => 'Surat Permohonan / Pengantar Eksternal',
                    'template_isi' => "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\nDengan hormat, dalam rangka {{tujuan_permohonan}}, bersama ini kami mengajukan permohonan kepada {{tujuan}} untuk {{rincian_permohonan}}.\n\nSebagai bahan pertimbangan, bersama ini kami sertakan berkas pendukung terkait.\n\nAtas perkenan dan dukungan Bapak/Ibu, kami ucapkan terima kasih.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh.",
                    'penutup_default' => null,
                ],
            ];

            foreach ($daftar as $item) {
                $jenis = JenisSurat::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'format_nomor' => $item['format_nomor'],
                        'pisah_urutan' => $item['pisah_urutan'],
                        'keterangan' => $item['keterangan'],
                        'aktif' => true,
                    ],
                );

                TemplateSurat::updateOrCreate(
                    ['jenis_surat_id' => $jenis->id, 'nama' => $item['template_nama']],
                    [
                        'isi' => $item['template_isi'],
                        'penutup_default' => $item['penutup_default'],
                        'aktif' => true,
                    ],
                );
            }
        });
    }
}
