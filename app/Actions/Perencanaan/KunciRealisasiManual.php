<?php

namespace App\Actions\Perencanaan;

use App\Models\RealisasiManual;
use App\Models\TahunAnggaran;
use Illuminate\Validation\ValidationException;

class KunciRealisasiManual extends PerencanaanAction
{
    public function jalankan(array $data): int
    {
        $this->pastikanAksi('approve', 'Anda tidak memiliki izin untuk mengunci atau membuka kunci periode realisasi.');

        $tervalidasi = validator($data, [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'terkunci' => ['required', 'boolean'],
            'alasan' => ['required_if:terkunci,false', 'nullable', 'string', 'max:500'],
        ], [
            'alasan.required_if' => 'Alasan wajib diisi saat membuka kunci periode realisasi.',
        ])->validate();

        $tahunAnggaran = TahunAnggaran::where('tahun', $tervalidasi['tahun'])->first();
        if (! $tervalidasi['terkunci'] && $tahunAnggaran && $tahunAnggaran->isTutup()) {
            throw ValidationException::withMessages([
                'tahun' => 'Tahun anggaran '.$tervalidasi['tahun'].' telah ditutup. Periode tidak dapat dibuka kuncinya.',
            ]);
        }

        $jumlahDiperbarui = RealisasiManual::where('tahun', $tervalidasi['tahun'])
            ->where('bulan', $tervalidasi['bulan'])
            ->update(['terkunci' => $tervalidasi['terkunci']]);

        $this->catatAudit(
            $tervalidasi['terkunci'] ? 'kunci' : 'buka_kunci',
            RealisasiManual::class,
            0,
            null,
            [
                'tahun' => $tervalidasi['tahun'],
                'bulan' => $tervalidasi['bulan'],
                'terkunci' => $tervalidasi['terkunci'],
                'jumlah_baris' => $jumlahDiperbarui,
            ],
            $tervalidasi['alasan'] ?? null
        );

        return $jumlahDiperbarui;
    }
}
