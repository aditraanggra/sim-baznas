<?php

namespace App\Services\Rkat;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Membaca template "Rencana 2022-2031": sheet A1..A7 (L2, L2/LN, L3..L7), kolom "No" + "Tahun YYYY".
 * Kolom tahun dicari dari header, bukan posisi. Sel rumus = subtotal (nilai hitung, hanya pembanding).
 * Template lain (mis. 2021) menunggu D6; tidak ditebak.
 */
final class ParserRkatExcel
{
    public const SHEET = ['A1' => 'L2', 'A2' => 'L2/LN', 'A3' => 'L3', 'A4' => 'L4', 'A5' => 'L5', 'A6' => 'L6', 'A7' => 'L7'];

    /**
     * @return list<array{nomor: int, sheet: string, kode: string, nama: string, nilai: mixed, subtotal: bool}>
     */
    public static function baca(string $path, int $tahun): array
    {
        $pembaca = IOFactory::createReaderForFile($path);
        $pembaca->setReadDataOnly(true);
        $pembaca->setReadEmptyCells(false);
        $buku = $pembaca->load($path);
        $baris = [];
        $nomor = 0;
        $sheetDikenal = 0;

        foreach ($buku->getWorksheetIterator() as $sheet) {
            $awalan = self::SHEET[substr($sheet->getTitle(), 0, 2)] ?? null;
            if ($awalan === null) {
                continue;
            }
            $sheetDikenal++;

            [$kolom, $barisHeader] = self::cariKolom($sheet, $tahun);
            if ($kolom === null) {
                throw new InvalidArgumentException("Kolom 'Tahun $tahun' tidak ditemukan di sheet {$sheet->getTitle()}.");
            }

            for ($r = $barisHeader + 1; $r <= $sheet->getHighestDataRow(); $r++) {
                $no = trim((string) $sheet->getCell([1, $r])->getValue());
                if (! preg_match('/^\d+(\.\d+)*$/', $no)) {
                    continue;
                }

                $sel = $sheet->getCell([$kolom, $r]);
                $subtotal = $sel->isFormula();
                $nilai = $subtotal ? self::hitung($sel) : $sel->getValue();

                $baris[] = [
                    'nomor' => ++$nomor,
                    'sheet' => $sheet->getTitle(),
                    'kode' => $awalan.'/'.$no,
                    'nama' => trim((string) $sheet->getCell([2, $r])->getValue()),
                    'nilai' => self::bulat($nilai),
                    'subtotal' => $subtotal,
                ];
            }
        }

        if ($sheetDikenal === 0) {
            throw new InvalidArgumentException("Template tidak dikenali: diharapkan sheet A1-A7 'Rencana 2022-2031'.");
        }

        return $baris;
    }

    /** @return array{0: ?int, 1: int} kolom (1-based) dan baris header */
    private static function cariKolom($sheet, int $tahun): array
    {
        for ($r = 1; $r <= min(10, $sheet->getHighestDataRow()); $r++) {
            for ($c = 1; $c <= 30; $c++) {
                if (preg_match('/^Tahun\s+'.$tahun.'$/i', trim((string) $sheet->getCell([$c, $r])->getValue()))) {
                    return [$c, $r];
                }
            }
        }

        return [null, 0];
    }

    private static function hitung($sel): mixed
    {
        try {
            return $sel->getCalculatedValue();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Bilangan bulat tetap int; pecahan/teks dibiarkan agar ditolak validasi baris (BR-12). */
    private static function bulat(mixed $nilai): mixed
    {
        if (is_float($nilai) && floor($nilai) === $nilai) {
            return (int) $nilai;
        }
        if (is_string($nilai) && preg_match('/^-?\d+$/', trim($nilai))) {
            return (int) $nilai;
        }

        return $nilai === null ? '' : $nilai;
    }
}
