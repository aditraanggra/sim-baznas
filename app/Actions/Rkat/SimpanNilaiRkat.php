<?php

namespace App\Actions\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Illuminate\Support\Facades\DB;

/**
 * FR-R-02/BR-03: hanya pos daun (input_langsung) yang menerima nilai; induk dihitung server.
 * Mengedit versi hasil impor mengembalikan sumber ke manual (angka tidak lagi sama dengan berkas; BR-04 berlaku lagi).
 */
class SimpanNilaiRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'nilai' => ['required', 'array', 'min:1'],
            'nilai.*' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            $this->pastikanDraft($versi);

            $pos = PosAnggaran::whereIn('id', array_keys($data['nilai']))->get()->keyBy('id');
            foreach ($data['nilai'] as $id => $nilai) {
                $p = $pos->get($id);
                if (! $p) {
                    $this->gagal("nilai.$id", 'Pos tidak ditemukan.');
                }
                if ($p->agregasi !== 'input_langsung') {
                    $this->gagal("nilai.$id", "Pos {$p->kode} dihitung sistem, tidak dapat diinput (BR-03).");
                }
            }

            $ada = $versi->nilai()->whereIn('pos_anggaran_id', $pos->keys())->get()->keyBy('pos_anggaran_id');
            $lama = $baru = [];

            foreach ($data['nilai'] as $id => $nilai) {
                $nilai = (int) $nilai;
                $p = $pos[$id];
                $baris = $ada->get($id);
                if ($baris && $baris->nilai === $nilai) {
                    continue;
                }
                $lama[$p->kode] = $baris?->nilai;
                $baru[$p->kode] = $nilai;

                RkatValue::updateOrCreate(
                    ['rkat_version_id' => $versi->id, 'kode_asal' => $p->kode, 'tipe_nilai' => $p->tipe_nilai],
                    ['pos_anggaran_id' => $p->id, 'nama_asal' => $p->nama, 'nilai' => $nilai],
                );
            }

            if ($baru !== []) {
                if ($versi->sumber === 'impor') {
                    $versi->update(['sumber' => 'manual']);
                }
                $this->catatAudit('ubah_nilai', RkatVersion::class, $versi->id, $lama, $baru);
            }

            return $versi;
        });
    }
}
