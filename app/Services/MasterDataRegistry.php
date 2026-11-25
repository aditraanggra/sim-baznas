<?php

namespace App\Services;

use App\Models\Asnaf;
use App\Models\BentukBantuan;
use App\Models\Bidang;
use App\Models\DanaKelompok;
use App\Models\Desa;
use App\Models\JenisDana;
use App\Models\JenisDokumen;
use App\Models\JenisSurat;
use App\Models\JenisUpz;
use App\Models\Kecamatan;
use App\Models\MetodeBayar;
use App\Models\Program;
use App\Models\RekeningBank;
use App\Models\StatusDokumen;
use App\Models\SumberDana;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class MasterDataRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definisi(): array
    {
        return [
            'bidang' => [
                'label' => 'Bidang',
                'deskripsi' => 'Empat bidang utama SISFOZIS sesuai kartu launcher.',
                'model' => Bidang::class,
                'urutan_default' => 'urutan',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'nama', 'label' => 'Nama Bidang', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'warna', 'label' => 'Warna Hex', 'type' => 'text', 'required' => true, 'max' => 16],
                    ['key' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'required' => true],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:32', Rule::unique('bidang', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                    'warna' => ['required', 'string', 'max:16'],
                    'urutan' => ['required', 'integer', 'min:0', 'max:999'],
                ],
            ],
            'jenis_dana' => [
                'label' => 'Jenis Dana',
                'deskripsi' => 'Kelompok jenis dana (Zakat, Infak, DSKL, CSR).',
                'model' => JenisDana::class,
                'urutan_default' => 'id',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 16],
                    ['key' => 'nama', 'label' => 'Nama Jenis Dana', 'type' => 'text', 'required' => true, 'max' => 64],
                    ['key' => 'wajib_asnaf', 'label' => 'Wajib Asnaf', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:16', Rule::unique('jenis_dana', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:64', Rule::unique('jenis_dana', 'nama')->ignore($id)],
                    'wajib_asnaf' => ['boolean'],
                ],
            ],
            'dana_kelompok' => [
                'label' => 'Kelompok Dana',
                'deskripsi' => 'Pengelompokan saldo dana dan pemetaan PSAK 109.',
                'model' => DanaKelompok::class,
                'urutan_default' => 'id',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Kelompok Dana', 'type' => 'text', 'required' => true, 'max' => 64],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:24', Rule::unique('dana_kelompok', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:64', Rule::unique('dana_kelompok', 'nama')->ignore($id)],
                ],
            ],
            'sumber_dana' => [
                'label' => 'Sumber Dana',
                'deskripsi' => 'Rincian sumber dana pengumpulan dan penyaluran (ZF, ZM, IFTT, dst).',
                'model' => SumberDana::class,
                'relasi' => ['jenisDana:id,nama', 'danaKelompok:id,nama'],
                'urutan_default' => 'id',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 16],
                    ['key' => 'nama', 'label' => 'Nama Sumber Dana', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'jenis_dana_id', 'label' => 'Jenis Dana', 'type' => 'select', 'options_key' => 'jenis_dana', 'required' => true],
                    ['key' => 'dana_kelompok_id', 'label' => 'Kelompok Dana', 'type' => 'select', 'options_key' => 'dana_kelompok', 'required' => true],
                    ['key' => 'terikat', 'label' => 'Dana Terikat', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:16', Rule::unique('sumber_dana', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                    'jenis_dana_id' => ['required', 'integer', 'exists:jenis_dana,id'],
                    'dana_kelompok_id' => ['required', 'integer', 'exists:dana_kelompok,id'],
                    'terikat' => ['boolean'],
                ],
            ],
            'asnaf' => [
                'label' => 'Asnaf',
                'deskripsi' => 'Delapan golongan penerima zakat.',
                'model' => Asnaf::class,
                'urutan_default' => 'urutan',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Asnaf', 'type' => 'text', 'required' => true, 'max' => 64],
                    ['key' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'required' => true],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:24', Rule::unique('asnaf', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:64', Rule::unique('asnaf', 'nama')->ignore($id)],
                    'urutan' => ['required', 'integer', 'min:0', 'max:999'],
                ],
            ],
            'program' => [
                'label' => 'Program',
                'deskripsi' => 'Daftar program Pendistribusian dan Pendayagunaan.',
                'model' => Program::class,
                'relasi' => ['bidang:id,nama', 'asnaf:id,nama'],
                'urutan_default' => 'kode',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'nama', 'label' => 'Nama Program', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'bidang_id', 'label' => 'Bidang', 'type' => 'select', 'options_key' => 'bidang', 'required' => false],
                    ['key' => 'sub_bidang', 'label' => 'Sub Bidang', 'type' => 'text', 'required' => false, 'max' => 32],
                    ['key' => 'asnaf_id', 'label' => 'Asnaf Default', 'type' => 'select', 'options_key' => 'asnaf', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:32', Rule::unique('program', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                    'bidang_id' => ['nullable', 'integer', 'exists:bidang,id'],
                    'sub_bidang' => ['nullable', 'string', 'max:32'],
                    'asnaf_id' => ['nullable', 'integer', 'exists:asnaf,id'],
                ],
            ],
            'metode_bayar' => [
                'label' => 'Metode Bayar',
                'deskripsi' => 'Metode penerimaan ZIS dari muzakki.',
                'model' => MetodeBayar::class,
                'urutan_default' => 'id',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Metode', 'type' => 'text', 'required' => true, 'max' => 64],
                    ['key' => 'butuh_rekening', 'label' => 'Wajib Pilih Rekening', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:24', Rule::unique('metode_bayar', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:64'],
                    'butuh_rekening' => ['boolean'],
                ],
            ],
            'rekening_bank' => [
                'label' => 'Rekening Bank',
                'deskripsi' => 'Daftar rekening bank resmi BAZNAS Kab. Cianjur.',
                'model' => RekeningBank::class,
                'relasi' => ['jenisDana:id,nama'],
                'urutan_default' => 'bank',
                'kolom' => [
                    ['key' => 'bank', 'label' => 'Nama Bank', 'type' => 'text', 'required' => true, 'max' => 64],
                    ['key' => 'no_rekening', 'label' => 'Nomor Rekening', 'type' => 'text', 'required' => true, 'max' => 64],
                    ['key' => 'atas_nama', 'label' => 'Atas Nama', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'jenis_dana_id', 'label' => 'Jenis Dana', 'type' => 'select', 'options_key' => 'jenis_dana', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'bank' => ['required', 'string', 'max:64'],
                    'no_rekening' => ['required', 'string', 'max:64', Rule::unique('rekening_bank', 'no_rekening')->ignore($id)],
                    'atas_nama' => ['required', 'string', 'max:128'],
                    'jenis_dana_id' => ['nullable', 'integer', 'exists:jenis_dana,id'],
                ],
            ],
            'jenis_dokumen' => [
                'label' => 'Jenis Dokumen',
                'deskripsi' => 'Jenis dokumen persyaratan identitas, permohonan, dan lembaga.',
                'model' => JenisDokumen::class,
                'urutan_default' => 'kelompok',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'nama', 'label' => 'Nama Dokumen', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'kelompok', 'label' => 'Kelompok', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'wajib', 'label' => 'Wajib', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:32', Rule::unique('jenis_dokumen', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                    'kelompok' => ['required', 'string', 'max:32'],
                    'wajib' => ['boolean'],
                ],
            ],
            'status_dokumen' => [
                'label' => 'Status Dokumen',
                'deskripsi' => 'Status alur kerja permohonan, publik, dan persuratan.',
                'model' => StatusDokumen::class,
                'urutan_default' => 'kelompok',
                'kolom' => [
                    ['key' => 'kelompok', 'label' => 'Kelompok', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'nama', 'label' => 'Nama Status', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'required' => true],
                    ['key' => 'warna', 'label' => 'Warna Badge', 'type' => 'text', 'required' => true, 'max' => 16],
                    ['key' => 'tampil_publik', 'label' => 'Tampil Publik', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id, array $input = []) => [
                    'kelompok' => ['required', 'string', 'max:32'],
                    'kode' => [
                        'required',
                        'string',
                        'max:32',
                        Rule::unique('status_dokumen', 'kode')
                            ->where(fn ($q) => $q->where('kelompok', $input['kelompok'] ?? ''))
                            ->ignore($id),
                    ],
                    'nama' => ['required', 'string', 'max:128'],
                    'urutan' => ['required', 'integer', 'min:0', 'max:999'],
                    'warna' => ['required', 'string', 'max:16'],
                    'tampil_publik' => ['boolean'],
                ],
            ],
            'bentuk_bantuan' => [
                'label' => 'Bentuk Bantuan',
                'deskripsi' => 'Bentuk bantuan penyaluran (Uang / Barang).',
                'model' => BentukBantuan::class,
                'urutan_default' => 'id',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Bentuk Bantuan', 'type' => 'text', 'required' => true, 'max' => 64],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:24', Rule::unique('bentuk_bantuan', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:64'],
                ],
            ],
            'jenis_upz' => [
                'label' => 'Jenis UPZ',
                'deskripsi' => 'Kategori Unit Pengumpul Zakat (DKM, Desa, DIBALEKA, Perusahaan, Sekolah, Yayasan).',
                'model' => JenisUpz::class,
                'urutan_default' => 'kode',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 32],
                    ['key' => 'nama', 'label' => 'Nama Jenis UPZ', 'type' => 'text', 'required' => true, 'max' => 128],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:32', Rule::unique('jenis_upz', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                ],
            ],
            'jenis_surat' => [
                'label' => 'Jenis Surat',
                'deskripsi' => 'Kategori surat dinas keluar dan template format nomornya.',
                'model' => JenisSurat::class,
                'urutan_default' => 'kode',
                'kolom' => [
                    ['key' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Jenis Surat', 'type' => 'text', 'required' => true, 'max' => 128],
                    ['key' => 'format_nomor', 'label' => 'Format Nomor (Opsional)', 'type' => 'text', 'required' => false, 'max' => 128],
                    ['key' => 'pisah_urutan', 'label' => 'Pisah Urutan Nomor per Tahun', 'type' => 'boolean', 'required' => false],
                ],
                'aturan' => fn (?int $id) => [
                    'kode' => ['required', 'string', 'max:24', Rule::unique('jenis_surat', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                    'format_nomor' => ['nullable', 'string', 'max:128'],
                    'pisah_urutan' => ['boolean'],
                ],
            ],
            'desa' => [
                'label' => 'Desa / Kelurahan',
                'deskripsi' => 'Wilayah desa dan kelurahan Kabupaten Cianjur.',
                'model' => Desa::class,
                'relasi' => ['kecamatan:id,nama'],
                'urutan_default' => 'kode',
                'kolom' => [
                    ['key' => 'kecamatan_id', 'label' => 'Kecamatan', 'type' => 'select', 'options_key' => 'kecamatan', 'required' => true],
                    ['key' => 'kode', 'label' => 'Kode Wilayah', 'type' => 'text', 'required' => true, 'max' => 24],
                    ['key' => 'nama', 'label' => 'Nama Desa / Kelurahan', 'type' => 'text', 'required' => true, 'max' => 128],
                ],
                'aturan' => fn (?int $id) => [
                    'kecamatan_id' => ['required', 'integer', 'exists:kecamatan,id'],
                    'kode' => ['required', 'string', 'max:24', Rule::unique('desa', 'kode')->ignore($id)],
                    'nama' => ['required', 'string', 'max:128'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ambil(string $jenis): array
    {
        $semua = self::definisi();
        abort_unless(isset($semua[$jenis]), 404, 'Jenis master data tidak dikenal.');

        return $semua[$jenis];
    }

    /**
     * @return class-string<Model>
     */
    public static function kelasModel(string $jenis): string
    {
        return self::ambil($jenis)['model'];
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function opsiSelect(): array
    {
        return [
            'bidang' => Bidang::query()->orderBy('urutan')->get(['id', 'nama'])
                ->map(fn ($x) => ['value' => (string) $x->id, 'label' => $x->nama])->all(),
            'jenis_dana' => JenisDana::query()->orderBy('id')->get(['id', 'nama'])
                ->map(fn ($x) => ['value' => (string) $x->id, 'label' => $x->nama])->all(),
            'dana_kelompok' => DanaKelompok::query()->orderBy('id')->get(['id', 'nama'])
                ->map(fn ($x) => ['value' => (string) $x->id, 'label' => $x->nama])->all(),
            'asnaf' => Asnaf::query()->orderBy('urutan')->get(['id', 'nama'])
                ->map(fn ($x) => ['value' => (string) $x->id, 'label' => $x->nama])->all(),
            'kecamatan' => Kecamatan::query()->orderBy('nama')->get(['id', 'nama'])
                ->map(fn ($x) => ['value' => (string) $x->id, 'label' => $x->nama])->all(),
        ];
    }
}
