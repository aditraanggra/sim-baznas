<?php

namespace App\Actions\Rkat;

use App\Models\RkatNote;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** FR-R-01: versi per tahun (Awal / Perubahan ke-n), opsional salin dari versi lain. */
class BuatVersiRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();

        $data = validator($data, [
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'jenis' => ['required', Rule::in(['awal', 'perubahan'])],
            'salin_dari_id' => ['nullable', 'integer', 'exists:rkat_versions,id'],
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

            $versi = RkatVersion::create([
                'tahun' => $data['tahun'],
                'jenis' => $data['jenis'],
                'nomor_perubahan' => $nomor,
                'status' => 'draft',
                'sumber' => 'manual',
                'dibuat_oleh' => Auth::id(),
                'disalin_dari_id' => $sumber?->id,
            ]);

            if ($sumber) {
                foreach ($sumber->nilai()->get() as $n) {
                    RkatValue::create([...$n->only(['pos_anggaran_id', 'kode_asal', 'nama_asal', 'tipe_nilai', 'nilai']), 'rkat_version_id' => $versi->id]);
                }
                if ($catatan = $sumber->catatan) {
                    RkatNote::create([...$catatan->only(RkatNote::KOLOM), 'rkat_version_id' => $versi->id]);
                }
            }

            $this->catatAudit('buat', RkatVersion::class, $versi->id, null, [
                'tahun' => $versi->tahun, 'jenis' => $versi->jenis, 'nomor_perubahan' => $versi->nomor_perubahan, 'disalin_dari_id' => $versi->disalin_dari_id,
            ]);

            return $versi;
        });
    }
}
