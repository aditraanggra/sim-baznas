<?php

namespace App\Actions\Perencanaan;

use App\Models\RealisasiManual;
use App\Models\TahunAnggaran;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SimpanRealisasiManual extends PerencanaanAction
{
    public function jalankan(array $data): RealisasiManual
    {
        $this->pastikanAksi('create', 'Anda tidak memiliki izin untuk menyimpan nilai realisasi manual.');

        $tervalidasi = validator($data, [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'pos_anggaran_id' => ['required', 'integer', 'exists:pos_anggaran,id'],
            'nilai' => ['required', 'integer'], // BR-12 BIGINT
            'sumber' => ['nullable', 'string', Rule::in(['manual', 'impor'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $tahunAnggaran = TahunAnggaran::where('tahun', $tervalidasi['tahun'])->first();
        if ($tahunAnggaran && $tahunAnggaran->isTutup()) {
            throw ValidationException::withMessages([
                'tahun' => 'Tahun anggaran '.$tervalidasi['tahun'].' telah ditutup. Tidak dapat menambah atau mengubah nilai realisasi.',
            ]);
        }

        // BR-05: Nilai negatif wajib menyertakan catatan penjelasan
        if ((int) $tervalidasi['nilai'] < 0 && trim((string) ($tervalidasi['catatan'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'catatan' => 'Nilai realisasi negatif wajib menyertakan catatan alasan/penjelasan (aturan BR-05).',
            ]);
        }

        $realisasi = RealisasiManual::firstOrNew([
            'tahun' => $tervalidasi['tahun'],
            'bulan' => $tervalidasi['bulan'],
            'pos_anggaran_id' => $tervalidasi['pos_anggaran_id'],
        ]);

        if ($realisasi->terkunci) {
            throw ValidationException::withMessages([
                'nilai' => 'Periode realisasi pos ini telah terkunci dan tidak dapat diubah.',
            ]);
        }

        $sebelumnya = $realisasi->exists ? $realisasi->toArray() : null;

        if (! $realisasi->exists) {
            $realisasi->fill($this->denganPembuat([]));
        }

        $realisasi->nilai = (int) $tervalidasi['nilai'];
        $realisasi->sumber = $tervalidasi['sumber'] ?? 'manual';
        $realisasi->catatan = $tervalidasi['catatan'] ?? null;
        $realisasi->save();

        $this->catatAudit(
            $sebelumnya ? 'ubah' : 'tambah',
            RealisasiManual::class,
            $realisasi->id,
            $sebelumnya,
            $realisasi->fresh()->toArray(),
            $tervalidasi['catatan'] ?? null
        );

        return $realisasi;
    }
}
