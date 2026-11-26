<?php

namespace App\Actions\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatAturanAmil;
use App\Models\RkatKegiatanRencana;
use App\Models\RkatNote;
use App\Models\RkatPelengkapBidang;
use App\Models\RkatTahapBidang;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use App\Models\TahunAnggaran;
use App\Services\NotifikasiService;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use Database\Seeders\PosAnggaranAlurBaruSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** FR-R-01: versi per tahun (Awal / Perubahan ke-n), mendukung mode manual maupun kolaboratif 3 bidang. */
class BuatVersiRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'jenis' => ['required', Rule::in(['awal', 'perubahan'])],
            'salin_dari_id' => ['nullable', 'integer', 'exists:rkat_versions,id'],
            'mode_penyusunan' => ['nullable', Rule::in([RkatVersion::MODE_MANUAL, RkatVersion::MODE_KOLABORATIF])],
            'target_pengumpulan' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $sumber = isset($data['salin_dari_id']) ? RkatVersion::findOrFail($data['salin_dari_id']) : null;

            if ($data['jenis'] === 'awal') {
                if (RkatVersion::where(['tahun' => $data['tahun'], 'jenis' => 'awal'])->exists()) {
                    $this->gagal('jenis', "RKAT Awal tahun {$data['tahun']} sudah ada.");
                }
                $nomor = 0;
            } else {
                // BR-02: perubahan hanya lewat versi baru, berpijak pada versi Disahkan.
                $aktif = RkatVersion::where(['tahun' => $data['tahun'], 'status' => 'disahkan'])->first();
                if (! $aktif) {
                    $this->gagal('jenis', "Tahun {$data['tahun']} belum punya versi Disahkan; versi Perubahan tidak dapat dibuat.");
                }
                $sumber ??= $aktif;
                $nomor = (int) RkatVersion::where(['tahun' => $data['tahun'], 'jenis' => 'perubahan'])->max('nomor_perubahan') + 1;
            }

            $mode = $data['mode_penyusunan'] ?? (
                $sumber?->isKolaboratif() ? RkatVersion::MODE_KOLABORATIF : RkatVersion::MODE_MANUAL
            );

            $targetPengumpulan = null;
            if ($mode === RkatVersion::MODE_KOLABORATIF) {
                $targetPengumpulan = $data['target_pengumpulan'] ?? ($sumber?->isKolaboratif() ? $sumber->target_pengumpulan : null);
                if ($targetPengumpulan === null || (int) $targetPengumpulan < 1) {
                    $this->gagal('target_pengumpulan', 'Target pengumpulan wajib diisi lebih dari 0 untuk RKAT kolaboratif.');
                }
                $targetPengumpulan = (int) $targetPengumpulan;
                PosAnggaranAlurBaruSeeder::pastikanTersedia();
            }

            $tahunAnggaranId = TahunAnggaran::where('tahun', $data['tahun'])->value('id');

            $versi = RkatVersion::create([
                'tahun' => $data['tahun'],
                'tahun_anggaran_id' => $tahunAnggaranId,
                'jenis' => $data['jenis'],
                'nomor_perubahan' => $nomor,
                'status' => 'draft',
                'sumber' => 'manual',
                'mode_penyusunan' => $mode,
                'target_pengumpulan' => $targetPengumpulan,
                'target_ditetapkan_pada' => $mode === RkatVersion::MODE_KOLABORATIF ? now() : null,
                'target_ditetapkan_oleh' => $mode === RkatVersion::MODE_KOLABORATIF ? Auth::id() : null,
                'dibuat_oleh' => Auth::id(),
                'disalin_dari_id' => $sumber?->id,
            ]);

            if ($mode === RkatVersion::MODE_KOLABORATIF) {
                $this->siapkanKolaboratif($versi, $sumber);
            } elseif ($sumber) {
                foreach ($sumber->nilai()->get() as $n) {
                    RkatValue::create([...$n->only(['pos_anggaran_id', 'kode_asal', 'nama_asal', 'tipe_nilai', 'nilai']), 'rkat_version_id' => $versi->id]);
                }
                if ($catatan = $sumber->catatan) {
                    RkatNote::create([...$catatan->only(RkatNote::KOLOM), 'rkat_version_id' => $versi->id]);
                }
            }

            $this->catatAudit('buat', RkatVersion::class, $versi->id, null, [
                'tahun' => $versi->tahun,
                'jenis' => $versi->jenis,
                'nomor_perubahan' => $versi->nomor_perubahan,
                'mode_penyusunan' => $versi->mode_penyusunan,
                'target_pengumpulan' => $versi->target_pengumpulan,
                'disalin_dari_id' => $versi->disalin_dari_id,
            ]);

            return $versi;
        });
    }

    private function siapkanKolaboratif(RkatVersion $versi, ?RkatVersion $sumber): void
    {
        if ($sumber && $sumber->isKolaboratif() && $sumber->aturanAmil()->exists()) {
            foreach ($sumber->aturanAmil()->get() as $aturan) {
                RkatAturanAmil::create([
                    'rkat_version_id' => $versi->id,
                    'pos_anggaran_id' => $aturan->pos_anggaran_id,
                    'urutan' => $aturan->urutan,
                    'uraian' => $aturan->uraian,
                    'kelompok_penyaluran' => $aturan->kelompok_penyaluran,
                    'persen_bp' => $aturan->persen_bp,
                    'persen_maks_bp' => $aturan->persen_maks_bp,
                ]);
            }
        } else {
            $barisConfig = config('rkat_amil.baris', []);
            $posMap = PosAnggaran::whereIn('kode', collect($barisConfig)->pluck('kode_pos')->all())
                ->where('tipe_nilai', 'rupiah')
                ->get()
                ->keyBy('kode');

            foreach ($barisConfig as $item) {
                $pos = $posMap->get($item['kode_pos']);
                if (! $pos) {
                    continue;
                }

                RkatAturanAmil::create([
                    'rkat_version_id' => $versi->id,
                    'pos_anggaran_id' => $pos->id,
                    'urutan' => $item['urutan'],
                    'uraian' => $item['uraian'],
                    'kelompok_penyaluran' => $item['kelompok_penyaluran'],
                    'persen_bp' => $item['persen_default_bp'],
                    'persen_maks_bp' => $item['persen_maks_bp'],
                ]);
            }
        }

        foreach (RkatTahapBidang::BIDANG as $kodeBidang) {
            RkatTahapBidang::create([
                'rkat_version_id' => $versi->id,
                'bidang_kode' => $kodeBidang,
                'status' => $kodeBidang === 'pengumpulan'
                    ? RkatTahapBidang::STATUS_DRAFT
                    : RkatTahapBidang::STATUS_MENUNGGU,
            ]);
        }

        if ($sumber && $sumber->isKolaboratif()) {
            foreach ($sumber->kegiatanRencana()->aktif()->get() as $kegiatan) {
                RkatKegiatanRencana::create([
                    'rkat_version_id' => $versi->id,
                    'bidang_kode' => $kegiatan->bidang_kode,
                    'rkat_jenis_kegiatan_id' => $kegiatan->rkat_jenis_kegiatan_id,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'pos_anggaran_id' => $kegiatan->pos_anggaran_id,
                    'pos_program_id' => $kegiatan->pos_program_id,
                    'jumlah' => $kegiatan->jumlah,
                    'satuan' => $kegiatan->satuan,
                    'frekuensi' => $kegiatan->frekuensi,
                    'besaran' => $kegiatan->besaran,
                    'total' => $kegiatan->total,
                    'keterangan' => $kegiatan->keterangan,
                ]);
            }

            foreach ($sumber->pelengkapBidang()->get() as $pelengkap) {
                RkatPelengkapBidang::create([
                    'rkat_version_id' => $versi->id,
                    'bidang_kode' => $pelengkap->bidang_kode,
                    'pos_anggaran_id' => $pelengkap->pos_anggaran_id,
                    'nilai' => $pelengkap->nilai,
                    'keterangan' => $pelengkap->keterangan,
                ]);
            }
        }

        SinkronisasiNilaiRkatService::sinkronkan($versi);

        app(NotifikasiService::class)->kirimKePeran(
            'kabag_pengumpulan',
            'rkat_target',
            "Target Pengumpulan RKAT {$versi->tahun} ({$versi->label()}) ditetapkan",
            'Target pengumpulan sebesar Rp '.number_format((int) $versi->target_pengumpulan, 0, ',', '.').' siap dirinci pada menu Perencanaan RKAT Pengumpulan.',
            route('pengumpulan.rkat.index', ['versi' => $versi->id], false),
            RkatVersion::class,
            $versi->id,
        );
    }
}
