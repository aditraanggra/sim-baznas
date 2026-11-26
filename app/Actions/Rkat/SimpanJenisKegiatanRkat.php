<?php

namespace App\Actions\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatJenisKegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Mengelola master Jenis Kegiatan per bidang (terhubung ke Akun RKAT PKP), tanpa hard delete (BR-11). */
class SimpanJenisKegiatanRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatJenisKegiatan
    {
        $bidang = (string) ($data['bidang_kode'] ?? '');
        $this->pastikanPenulisBidang($bidang);

        $aksi = $data['aksi'] ?? 'simpan';
        if ($aksi === 'toggle_aktif') {
            $tervalidasi = validator($data, [
                'id' => ['required', 'integer', 'exists:rkat_jenis_kegiatan,id'],
                'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
            ])->validate();

            return DB::transaction(function () use ($tervalidasi) {
                $jenis = RkatJenisKegiatan::where('bidang_kode', $tervalidasi['bidang_kode'])
                    ->lockForUpdate()
                    ->findOrFail($tervalidasi['id']);

                $lama = ['aktif' => $jenis->aktif];
                $jenis->update(['aktif' => ! $jenis->aktif]);

                $this->catatAudit('toggle_aktif_jenis_kegiatan', RkatJenisKegiatan::class, $jenis->id, $lama, ['aktif' => $jenis->aktif]);

                return $jenis;
            });
        }

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:rkat_jenis_kegiatan,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
            'kode' => ['nullable', 'string', 'max:40'],
            'nama' => ['required', 'string', 'max:200'],
            'pos_anggaran_id' => ['required', 'integer', 'exists:pos_anggaran,id'],
            'pos_program_id' => [$bidang === 'distribusi' ? 'required' : 'nullable', 'integer', 'exists:pos_anggaran,id'],
            'satuan_default' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $kodeValid = $this->kodePosValidUntukBidang($bidang);
        $pos = PosAnggaran::findOrFail($tervalidasi['pos_anggaran_id']);
        if ($pos->agregasi !== 'input_langsung' || ! in_array($pos->kode, $kodeValid, true)) {
            $this->gagal('pos_anggaran_id', "Pos RKAT {$pos->kode} tidak valid untuk jenis kegiatan bidang {$bidang}.");
        }

        $posProgramId = null;
        if ($bidang === 'distribusi') {
            $posProgram = PosAnggaran::findOrFail($tervalidasi['pos_program_id']);
            if ($posProgram->lampiran !== 'L4' || $posProgram->agregasi !== 'input_langsung') {
                $this->gagal('pos_program_id', "Program RKAT {$posProgram->kode} harus pos program L4.");
            }
            $posProgramId = $posProgram->id;
        }

        return DB::transaction(function () use ($tervalidasi, $pos, $posProgramId) {
            if (! empty($tervalidasi['id'])) {
                $jenis = RkatJenisKegiatan::where('bidang_kode', $tervalidasi['bidang_kode'])
                    ->lockForUpdate()
                    ->findOrFail($tervalidasi['id']);
                $lama = $jenis->only(['kode', 'nama', 'pos_anggaran_id', 'pos_program_id', 'satuan_default', 'keterangan']);
                $jenis->update([
                    'kode' => $tervalidasi['kode'] ?? null,
                    'nama' => $tervalidasi['nama'],
                    'pos_anggaran_id' => $pos->id,
                    'pos_program_id' => $posProgramId,
                    'satuan_default' => $tervalidasi['satuan_default'],
                    'keterangan' => $tervalidasi['keterangan'] ?? null,
                ]);
                $this->catatAudit('ubah_jenis_kegiatan', RkatJenisKegiatan::class, $jenis->id, $lama, $jenis->only(['kode', 'nama', 'pos_anggaran_id', 'pos_program_id', 'satuan_default', 'keterangan']));

                return $jenis;
            }

            $jenis = RkatJenisKegiatan::create($this->denganPembuat([
                'bidang_kode' => $tervalidasi['bidang_kode'],
                'kode' => $tervalidasi['kode'] ?? null,
                'nama' => $tervalidasi['nama'],
                'pos_anggaran_id' => $pos->id,
                'pos_program_id' => $posProgramId,
                'satuan_default' => $tervalidasi['satuan_default'],
                'keterangan' => $tervalidasi['keterangan'] ?? null,
                'aktif' => true,
            ]));

            $this->catatAudit('tambah_jenis_kegiatan', RkatJenisKegiatan::class, $jenis->id, null, $jenis->only(['bidang_kode', 'kode', 'nama', 'pos_anggaran_id', 'pos_program_id', 'satuan_default']));

            return $jenis;
        });
    }
}
