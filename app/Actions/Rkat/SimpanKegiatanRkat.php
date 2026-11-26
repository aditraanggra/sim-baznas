<?php

namespace App\Actions\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatJenisKegiatan;
use App\Models\RkatKegiatanRencana;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Menyimpan atau mengarsipkan baris rincian kegiatan RKAT bidang, menghitung total di server, dan menyinkronkan pos L2-L7. */
class SimpanKegiatanRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatKegiatanRencana
    {
        $bidang = (string) ($data['bidang_kode'] ?? '');
        $this->pastikanPenulisBidang($bidang);

        $aksi = $data['aksi'] ?? 'simpan';

        if ($aksi === 'arsip') {
            $tervalidasi = validator($data, [
                'id' => ['required', 'integer', 'exists:rkat_kegiatan_rencana,id'],
                'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
                'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
                'alasan' => ['nullable', 'string', 'max:500'],
            ])->validate();

            return DB::transaction(function () use ($tervalidasi, $bidang) {
                $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
                $this->pastikanKolaboratifDraft($versi);
                $tahap = $this->ambilTahapTerkunci($versi, $bidang);
                $this->pastikanTahapDraft($tahap);

                $kegiatan = RkatKegiatanRencana::query()
                    ->where('rkat_version_id', $versi->id)
                    ->where('bidang_kode', $bidang)
                    ->whereNull('diarsipkan_pada')
                    ->lockForUpdate()
                    ->findOrFail($tervalidasi['id']);

                $kegiatan->update(['diarsipkan_pada' => now()]);
                SinkronisasiNilaiRkatService::sinkronkan($versi);

                $this->catatAudit(
                    'arsip_kegiatan_rkat',
                    RkatKegiatanRencana::class,
                    $kegiatan->id,
                    ['diarsipkan_pada' => null, 'total' => $kegiatan->total],
                    ['diarsipkan_pada' => $kegiatan->diarsipkan_pada?->toIso8601String()],
                    $tervalidasi['alasan'] ?? null,
                );

                return $kegiatan;
            });
        }

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:rkat_kegiatan_rencana,id'],
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
            'rkat_jenis_kegiatan_id' => ['required', 'integer', 'exists:rkat_jenis_kegiatan,id'],
            'nama_kegiatan' => ['nullable', 'string', 'max:200'],
            'pos_anggaran_id' => ['nullable', 'integer', 'exists:pos_anggaran,id'],
            'pos_program_id' => ['nullable', 'integer', 'exists:pos_anggaran,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000000'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'frekuensi' => ['required', 'integer', 'min:1', 'max:10000'],
            'besaran' => ['required', 'integer', 'min:0', 'max:10000000000000'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $bidang) {
            $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
            $this->pastikanKolaboratifDraft($versi);
            $tahap = $this->ambilTahapTerkunci($versi, $bidang);
            $this->pastikanTahapDraft($tahap);

            $jenis = RkatJenisKegiatan::where('bidang_kode', $bidang)->findOrFail($tervalidasi['rkat_jenis_kegiatan_id']);
            $posId = $tervalidasi['pos_anggaran_id'] ?? $jenis->pos_anggaran_id;
            $pos = PosAnggaran::findOrFail($posId);

            if ($pos->agregasi !== 'input_langsung' || ! in_array($pos->kode, $this->kodePosValidUntukBidang($bidang), true)) {
                $this->gagal('pos_anggaran_id', "Pos RKAT {$pos->kode} tidak valid untuk bidang {$bidang}.");
            }

            $posProgramId = null;
            if ($bidang === 'distribusi') {
                $programId = $tervalidasi['pos_program_id'] ?? $jenis->pos_program_id;
                if (! $programId) {
                    $this->gagal('pos_program_id', 'Program L4 wajib dipilih untuk kegiatan penyaluran.');
                }
                $posProgram = PosAnggaran::findOrFail($programId);
                if ($posProgram->lampiran !== 'L4' || $posProgram->agregasi !== 'input_langsung') {
                    $this->gagal('pos_program_id', "Program RKAT {$posProgram->kode} harus pos program L4.");
                }
                $posProgramId = $posProgram->id;
            }

            $jumlah = (int) $tervalidasi['jumlah'];
            $frekuensi = (int) $tervalidasi['frekuensi'];
            $besaran = (int) $tervalidasi['besaran'];
            $totalFloat = (float) $jumlah * (float) $frekuensi * (float) $besaran;
            if ($totalFloat > PHP_INT_MAX) {
                $this->gagal('besaran', 'Total nominal melebihi batas perhitungan.');
            }
            $total = $jumlah * $frekuensi * $besaran;

            $payload = [
                'rkat_version_id' => $versi->id,
                'bidang_kode' => $bidang,
                'rkat_jenis_kegiatan_id' => $jenis->id,
                'nama_kegiatan' => trim((string) ($tervalidasi['nama_kegiatan'] ?? '')) ?: $jenis->nama,
                'pos_anggaran_id' => $pos->id,
                'pos_program_id' => $posProgramId,
                'jumlah' => $jumlah,
                'satuan' => trim((string) ($tervalidasi['satuan'] ?? '')) ?: $jenis->satuan_default,
                'frekuensi' => $frekuensi,
                'besaran' => $besaran,
                'total' => $total,
                'keterangan' => $tervalidasi['keterangan'] ?? null,
            ];

            if (! empty($tervalidasi['id'])) {
                $kegiatan = RkatKegiatanRencana::query()
                    ->where('rkat_version_id', $versi->id)
                    ->where('bidang_kode', $bidang)
                    ->whereNull('diarsipkan_pada')
                    ->lockForUpdate()
                    ->findOrFail($tervalidasi['id']);
                $lama = $kegiatan->only(array_keys($payload));
                $kegiatan->update($payload);
                $aksiAudit = 'ubah_kegiatan_rkat';
            } else {
                $lama = null;
                $kegiatan = RkatKegiatanRencana::create($this->denganPembuat($payload));
                $aksiAudit = 'tambah_kegiatan_rkat';
            }

            SinkronisasiNilaiRkatService::sinkronkan($versi);
            $this->catatAudit($aksiAudit, RkatKegiatanRencana::class, $kegiatan->id, $lama, $payload);

            return $kegiatan;
        });
    }
}
