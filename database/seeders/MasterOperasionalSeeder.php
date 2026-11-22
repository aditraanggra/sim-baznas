<?php

namespace Database\Seeders;

use App\Models\BentukBantuan;
use App\Models\JenisDana;
use App\Models\JenisDokumen;
use App\Models\MetodeBayar;
use App\Models\RekeningBank;
use App\Models\StatusDokumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterOperasionalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                ['kode' => 'TUNAI', 'nama' => 'Tunai', 'butuh_rekening' => false],
                ['kode' => 'TRANSFER', 'nama' => 'Transfer Bank', 'butuh_rekening' => true],
                ['kode' => 'QRIS', 'nama' => 'QRIS', 'butuh_rekening' => true],
                ['kode' => 'JEMPUT', 'nama' => 'Jemput Zakat', 'butuh_rekening' => false],
            ] as $item) {
                MetodeBayar::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'butuh_rekening' => $item['butuh_rekening'],
                        'aktif' => true,
                    ],
                );
            }

            $zakatId = JenisDana::where('kode', 'ZAKAT')->value('id');
            $infakId = JenisDana::where('kode', 'INFAK')->value('id');

            foreach ([
                ['bank' => 'BJB Syariah', 'no_rekening' => '0000000001', 'atas_nama' => 'BAZNAS Kab. Cianjur (Zakat)', 'jenis_dana_id' => $zakatId],
                ['bank' => 'BJB Syariah', 'no_rekening' => '0000000002', 'atas_nama' => 'BAZNAS Kab. Cianjur (Infak/Sedekah)', 'jenis_dana_id' => $infakId],
            ] as $item) {
                RekeningBank::updateOrCreate(
                    ['no_rekening' => $item['no_rekening']],
                    [
                        'bank' => $item['bank'],
                        'atas_nama' => $item['atas_nama'],
                        'jenis_dana_id' => $item['jenis_dana_id'],
                        'aktif' => true,
                    ],
                );
            }

            foreach ([
                ['kode' => 'KTP', 'nama' => 'Kartu Tanda Penduduk (KTP)', 'kelompok' => 'identitas', 'wajib' => true],
                ['kode' => 'KK', 'nama' => 'Kartu Keluarga (KK)', 'kelompok' => 'identitas', 'wajib' => true],
                ['kode' => 'SKTM', 'nama' => 'Surat Keterangan Tidak Mampu (SKTM)', 'kelompok' => 'permohonan', 'wajib' => false],
                ['kode' => 'PROPOSAL', 'nama' => 'Proposal Permohonan Bantuan', 'kelompok' => 'permohonan', 'wajib' => true],
                ['kode' => 'RAB', 'nama' => 'Rencana Anggaran Biaya (RAB)', 'kelompok' => 'permohonan', 'wajib' => false],
                ['kode' => 'FOTO_RUMAH', 'nama' => 'Foto Kondisi Rumah / Usaha', 'kelompok' => 'permohonan', 'wajib' => false],
                ['kode' => 'SK_LEMBAGA', 'nama' => 'SK / Legalitas Lembaga', 'kelompok' => 'lembaga', 'wajib' => false],
            ] as $item) {
                JenisDokumen::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'kelompok' => $item['kelompok'],
                        'wajib' => $item['wajib'],
                        'aktif' => true,
                    ],
                );
            }

            foreach ([
                // Status publik permohonan (PDP-05)
                ['kelompok' => 'permohonan_publik', 'kode' => 'diajukan', 'nama' => 'Diajukan', 'urutan' => 1, 'warna' => 'info', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'disposisi', 'nama' => 'Disposisi', 'urutan' => 2, 'warna' => 'info', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'verifikasi', 'nama' => 'Verifikasi', 'urutan' => 3, 'warna' => 'warning', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'acc_pimpinan', 'nama' => 'Acc Pimpinan', 'urutan' => 4, 'warna' => 'warning', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'disetujui', 'nama' => 'Disetujui', 'urutan' => 5, 'warna' => 'success', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'dicairkan', 'nama' => 'Dicairkan', 'urutan' => 6, 'warna' => 'success', 'tampil_publik' => true],
                ['kelompok' => 'permohonan_publik', 'kode' => 'ditolak', 'nama' => 'Ditolak', 'urutan' => 7, 'warna' => 'danger', 'tampil_publik' => true],

                // Status internal permohonan 7 langkah (K8)
                ['kelompok' => 'permohonan', 'kode' => 'diajukan', 'nama' => 'Input Pelayanan Mustahik', 'urutan' => 1, 'warna' => 'info', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'disposisi_ketua', 'nama' => 'Disposisi Ketua', 'urutan' => 2, 'warna' => 'info', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'asesmen_kabag', 'nama' => 'Hasil Asesmen Kabag Pendis Pendag', 'urutan' => 3, 'warna' => 'warning', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'ajuan_waka_2', 'nama' => 'Ajuan Besaran Wakil Ketua II', 'urutan' => 4, 'warna' => 'warning', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'acc_ketua', 'nama' => 'ACC Besaran Ketua', 'urutan' => 5, 'warna' => 'success', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'minta_keuangan', 'nama' => 'Permintaan Pencairan Staf ke Keuangan', 'urutan' => 6, 'warna' => 'warning', 'tampil_publik' => false],
                ['kelompok' => 'permohonan', 'kode' => 'diterima_mustahik', 'nama' => 'Bantuan Diterima Mustahik', 'urutan' => 7, 'warna' => 'success', 'tampil_publik' => true],
                ['kelompok' => 'permohonan', 'kode' => 'ditolak', 'nama' => 'Ditolak', 'urutan' => 8, 'warna' => 'danger', 'tampil_publik' => true],

                // Status surat masuk (K9)
                ['kelompok' => 'surat_masuk', 'kode' => 'diterima_arsip', 'nama' => 'Diterima Admin Arsip', 'urutan' => 1, 'warna' => 'info', 'tampil_publik' => false],
                ['kelompok' => 'surat_masuk', 'kode' => 'diteruskan_ketua', 'nama' => 'Diteruskan ke Ketua', 'urutan' => 2, 'warna' => 'warning', 'tampil_publik' => false],
                ['kelompok' => 'surat_masuk', 'kode' => 'disposisi_waka', 'nama' => 'Disposisi Wakil Ketua', 'urutan' => 3, 'warna' => 'warning', 'tampil_publik' => false],
                ['kelompok' => 'surat_masuk', 'kode' => 'tindak_lanjut', 'nama' => 'Tindak Lanjut Bawahan', 'urutan' => 4, 'warna' => 'info', 'tampil_publik' => false],
                ['kelompok' => 'surat_masuk', 'kode' => 'selesai', 'nama' => 'Selesai', 'urutan' => 5, 'warna' => 'success', 'tampil_publik' => false],

                // Status surat keluar
                ['kelompok' => 'surat_keluar', 'kode' => 'draft', 'nama' => 'Draft', 'urutan' => 1, 'warna' => 'info', 'tampil_publik' => false],
                ['kelompok' => 'surat_keluar', 'kode' => 'menunggu_persetujuan', 'nama' => 'Menunggu Persetujuan', 'urutan' => 2, 'warna' => 'warning', 'tampil_publik' => false],
                ['kelompok' => 'surat_keluar', 'kode' => 'disetujui', 'nama' => 'Disetujui / Final', 'urutan' => 3, 'warna' => 'success', 'tampil_publik' => false],
                ['kelompok' => 'surat_keluar', 'kode' => 'dibatalkan', 'nama' => 'Dibatalkan', 'urutan' => 4, 'warna' => 'danger', 'tampil_publik' => false],
            ] as $item) {
                StatusDokumen::updateOrCreate(
                    ['kelompok' => $item['kelompok'], 'kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'urutan' => $item['urutan'],
                        'warna' => $item['warna'],
                        'tampil_publik' => $item['tampil_publik'],
                        'aktif' => true,
                    ],
                );
            }

            foreach ([
                ['kode' => 'UANG', 'nama' => 'Uang'],
                ['kode' => 'BARANG', 'nama' => 'Barang'],
            ] as $item) {
                BentukBantuan::updateOrCreate(
                    ['kode' => $item['kode']],
                    ['nama' => $item['nama'], 'aktif' => true],
                );
            }
        });
    }
}
