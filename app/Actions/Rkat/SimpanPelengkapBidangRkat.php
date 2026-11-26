<?php

namespace App\Actions\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatPelengkapBidang;
use App\Models\RkatVersion;
use App\Services\Rkat\SinkronisasiNilaiRkatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Menyimpan pos pelengkap non-kegiatan per bidang (L2/LN, L5/3, L6 Hibah & Saldo Awal) agar grid PKP tetap 100% read-only. */
class SimpanPelengkapBidangRkat extends RkatBidangAction
{
    public function jalankan(array $data): RkatVersion
    {
        $bidang = (string) ($data['bidang_kode'] ?? '');
        $this->pastikanPenulisBidang($bidang);

        $tervalidasi = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'bidang_kode' => ['required', Rule::in(array_keys(self::PERAN_BIDANG))],
            'nilai' => ['required', 'array', 'min:1'],
            'nilai.*' => ['required', 'integer', 'min:0'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $bidang) {
            $versi = $this->versiTerkunci($tervalidasi['rkat_version_id']);
            $this->pastikanKolaboratifDraft($versi);
            $tahap = $this->ambilTahapTerkunci($versi, $bidang);
            $this->pastikanTahapDraft($tahap);

            $kodeDiizinkan = config("rkat_amil.pos_pelengkap.{$bidang}", []);
            $posMap = PosAnggaran::whereIn('id', array_keys($tervalidasi['nilai']))->get()->keyBy('id');

            $lama = $baru = [];
            foreach ($tervalidasi['nilai'] as $posId => $nilai) {
                $pos = $posMap->get((int) $posId);
                if (! $pos || ! in_array($pos->kode, $kodeDiizinkan, true)) {
                    $this->gagal("nilai.$posId", 'Pos pelengkap tidak valid untuk bidang ini.');
                }

                $nilaiInt = (int) $nilai;
                $baris = RkatPelengkapBidang::where('rkat_version_id', $versi->id)
                    ->where('pos_anggaran_id', $pos->id)
                    ->first();

                if ($baris && $baris->nilai === $nilaiInt) {
                    continue;
                }

                $lama[$pos->kode] = $baris?->nilai;
                $baru[$pos->kode] = $nilaiInt;

                RkatPelengkapBidang::updateOrCreate(
                    ['rkat_version_id' => $versi->id, 'pos_anggaran_id' => $pos->id],
                    $this->denganPembuat(['bidang_kode' => $bidang, 'nilai' => $nilaiInt]),
                );
            }

            if ($baru !== []) {
                SinkronisasiNilaiRkatService::sinkronkan($versi);
                $this->catatAudit("ubah_pelengkap_{$bidang}", RkatVersion::class, $versi->id, $lama, $baru);
            }

            return $versi;
        });
    }
}
