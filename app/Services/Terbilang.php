<?php

namespace App\Services;

class Terbilang
{
    private const SATUAN = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'sepuluh',
        'sebelas',
    ];

    public static function angka(int $nilai): string
    {
        if ($nilai === 0) {
            return 'nol';
        }

        if ($nilai < 0) {
            return 'minus '.self::angka(abs($nilai));
        }

        if ($nilai < 12) {
            return self::SATUAN[$nilai];
        }

        if ($nilai < 20) {
            return self::angka($nilai - 10).' belas';
        }

        if ($nilai < 100) {
            $sisa = $nilai % 10;

            return self::angka(intdiv($nilai, 10)).' puluh'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 200) {
            $sisa = $nilai - 100;

            return 'seratus'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 1000) {
            $sisa = $nilai % 100;

            return self::angka(intdiv($nilai, 100)).' ratus'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 2000) {
            $sisa = $nilai - 1000;

            return 'seribu'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 1_000_000) {
            $sisa = $nilai % 1000;

            return self::angka(intdiv($nilai, 1000)).' ribu'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 1_000_000_000) {
            $sisa = $nilai % 1_000_000;

            return self::angka(intdiv($nilai, 1_000_000)).' juta'.($sisa ? ' '.self::angka($sisa) : '');
        }

        if ($nilai < 1_000_000_000_000) {
            $sisa = $nilai % 1_000_000_000;

            return self::angka(intdiv($nilai, 1_000_000_000)).' miliar'.($sisa ? ' '.self::angka($sisa) : '');
        }

        $sisa = $nilai % 1_000_000_000_000;

        return self::angka(intdiv($nilai, 1_000_000_000_000)).' triliun'.($sisa ? ' '.self::angka($sisa) : '');
    }

    public static function rupiah(int $nilai, bool $kapitalAwal = true): string
    {
        $teks = trim(self::angka($nilai)).' rupiah';

        return $kapitalAwal ? ucfirst($teks) : $teks;
    }
}
