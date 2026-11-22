<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PenomoranService
{
    public function berikutnya(
        string $jenisNomor,
        ?CarbonInterface $tanggal = null,
        array $konteks = [],
        ?string $templateOverride = null,
    ): string {
        $tanggal = $tanggal ? Carbon::instance($tanggal) : now();
        $tahun = (int) ($konteks['tahun'] ?? $tanggal->year);

        $urut = DB::transaction(function () use ($jenisNomor, $tahun): int {
            $baris = DB::selectOne(
                'INSERT INTO nomor_sequence (jenis_nomor, tahun, nilai_terakhir, created_at, updated_at)
                 VALUES (?, ?, 1, NOW(), NOW())
                 ON CONFLICT (jenis_nomor, tahun)
                 DO UPDATE SET nilai_terakhir = nomor_sequence.nilai_terakhir + 1, updated_at = NOW()
                 RETURNING nilai_terakhir',
                [$jenisNomor, $tahun]
            );

            return (int) $baris->nilai_terakhir;
        });

        $template = $templateOverride
            ?? config("penomoran.format.{$jenisNomor}")
            ?? config('penomoran.format.default', '{jenis}/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}');

        return $this->formatTemplate($template, $urut, $tanggal, $jenisNomor, $konteks);
    }

    public function formatTemplate(
        string $template,
        int $urut,
        CarbonInterface $tanggal,
        string $jenisNomor,
        array $konteks = [],
    ): string {
        $hasil = preg_replace_callback(
            '/\{urut:(\d+)\}/',
            fn (array $cocok) => str_pad((string) $urut, (int) $cocok[1], '0', STR_PAD_LEFT),
            $template,
        );

        $tahun = (string) ($konteks['tahun'] ?? $tanggal->format('Y'));

        $pengganti = [
            '{urut}' => (string) $urut,
            '{jenis}' => (string) ($konteks['jenis'] ?? config("penomoran.kode_jenis.{$jenisNomor}", strtoupper($jenisNomor))),
            '{lembaga}' => (string) ($konteks['lembaga'] ?? config('penomoran.lembaga', 'BAZNAS-CJR')),
            '{tahun}' => $tahun,
            '{tahun_pendek}' => substr($tahun, -2),
            '{bulan}' => $tanggal->format('m'),
            '{romawi_bulan}' => self::bulanRomawi((int) $tanggal->format('n')),
            '{tanggal}' => $tanggal->format('d'),
            '{bidang}' => (string) ($konteks['bidang'] ?? ''),
            '{kode}' => (string) ($konteks['kode'] ?? ''),
        ];

        return strtr((string) $hasil, $pengganti);
    }

    public static function bulanRomawi(int $bulan): string
    {
        return match ($bulan) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            default => (string) $bulan,
        };
    }
}
