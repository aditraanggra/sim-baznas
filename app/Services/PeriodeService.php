<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodeService
{
    /**
     * @return array{
     *   jenis: string,
     *   tahun: int,
     *   bulan: int,
     *   triwulan: int,
     *   semester: int,
     *   tanggal: string,
     *   mulai: Carbon,
     *   selesai: Carbon,
     *   label: string
     * }
     */
    public function selesaikan(Request|array $input, string $defaultJenis = 'bulanan'): array
    {
        $ambil = function (string $kunci, mixed $default = null) use ($input): mixed {
            if ($input instanceof Request) {
                return $input->input($kunci, $default);
            }

            return $input[$kunci] ?? $default;
        };

        $sekarang = Carbon::today();
        $jenis = (string) $ambil('jenis', $defaultJenis);
        if (! in_array($jenis, ['harian', 'bulanan', 'triwulan', 'semester', 'tahunan'], true)) {
            $jenis = $defaultJenis;
        }

        $tahun = max(2000, min(2100, (int) $ambil('tahun', $sekarang->year)));
        $bulan = max(1, min(12, (int) $ambil('bulan', $sekarang->month)));
        $triwulan = max(1, min(4, (int) $ambil('triwulan', (int) ceil($bulan / 3))));
        $semester = max(1, min(2, (int) $ambil('semester', $bulan <= 6 ? 1 : 2)));

        $tanggalStr = (string) $ambil('tanggal', $sekarang->toDateString());
        try {
            $tanggalObj = Carbon::parse($tanggalStr)->startOfDay();
        } catch (\Throwable) {
            $tanggalObj = $sekarang->copy()->startOfDay();
            $tanggalStr = $tanggalObj->toDateString();
        }

        [$mulai, $selesai, $label] = match ($jenis) {
            'harian' => [
                $tanggalObj->copy()->startOfDay(),
                $tanggalObj->copy()->endOfDay(),
                'Harian ('.$tanggalObj->translatedFormat('d F Y').')',
            ],
            'triwulan' => (function () use ($tahun, $triwulan): array {
                $bulanAwal = ($triwulan - 1) * 3 + 1;
                $m = Carbon::create($tahun, $bulanAwal, 1)->startOfMonth();

                return [$m, $m->copy()->addMonths(2)->endOfMonth(), "Triwulan {$triwulan} Tahun {$tahun}"];
            })(),
            'semester' => (function () use ($tahun, $semester): array {
                $bulanAwal = $semester === 1 ? 1 : 7;
                $m = Carbon::create($tahun, $bulanAwal, 1)->startOfMonth();

                return [$m, $m->copy()->addMonths(5)->endOfMonth(), "Semester {$semester} Tahun {$tahun}"];
            })(),
            'tahunan' => [
                Carbon::create($tahun, 1, 1)->startOfYear(),
                Carbon::create($tahun, 12, 31)->endOfYear(),
                "Tahun {$tahun}",
            ],
            default => (function () use ($tahun, $bulan): array {
                $m = Carbon::create($tahun, $bulan, 1)->startOfMonth();

                return [$m, $m->copy()->endOfMonth(), $m->translatedFormat('F Y')];
            })(),
        };

        return [
            'jenis' => $jenis,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'triwulan' => $triwulan,
            'semester' => $semester,
            'tanggal' => $tanggalObj->toDateString(),
            'mulai' => $mulai,
            'selesai' => $selesai,
            'label' => $label,
        ];
    }
}
