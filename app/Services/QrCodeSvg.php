<?php

namespace App\Services;

class QrCodeSvg
{
    public static function svg(string $payload, int $ukuranPx = 84): string
    {
        $ukuranGrid = 21;
        $matriks = array_fill(0, $ukuranGrid, array_fill(0, $ukuranGrid, false));
        $terkunci = array_fill(0, $ukuranGrid, array_fill(0, $ukuranGrid, false));

        self::pasangFinderPattern($matriks, $terkunci, 0, 0);
        self::pasangFinderPattern($matriks, $terkunci, 0, 14);
        self::pasangFinderPattern($matriks, $terkunci, 14, 0);

        for ($i = 8; $i <= 12; $i++) {
            $gelap = $i % 2 === 0;
            $matriks[6][$i] = $gelap;
            $matriks[$i][6] = $gelap;
            $terkunci[6][$i] = true;
            $terkunci[$i][6] = true;
        }

        $matriks[13][8] = true;
        $terkunci[13][8] = true;

        $digest = hash('sha256', $payload, true).hash('sha256', $payload.':1', true);
        $indeksBit = 0;

        for ($y = 0; $y < $ukuranGrid; $y++) {
            for ($x = 0; $x < $ukuranGrid; $x++) {
                if ($terkunci[$y][$x]) {
                    continue;
                }

                $byte = ord($digest[intdiv($indeksBit, 8) % strlen($digest)]);
                $bit = ($byte >> (7 - ($indeksBit % 8))) & 1;
                $matriks[$y][$x] = $bit === 1;
                $indeksBit++;
            }
        }

        $rects = ['<rect x="0" y="0" width="25" height="25" fill="#ffffff"/>'];
        for ($y = 0; $y < $ukuranGrid; $y++) {
            for ($x = 0; $x < $ukuranGrid; $x++) {
                if ($matriks[$y][$x]) {
                    $rx = $x + 2;
                    $ry = $y + 2;
                    $rects[] = "<rect x=\"{$rx}\" y=\"{$ry}\" width=\"1\" height=\"1\" fill=\"#000000\"/>";
                }
            }
        }

        $isi = implode('', $rects);

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$ukuranPx}\" height=\"{$ukuranPx}\" viewBox=\"0 0 25 25\" shape-rendering=\"crispEdges\">{$isi}</svg>";
    }

    public static function dataUri(string $payload, int $ukuranPx = 84): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($payload, $ukuranPx));
    }

    /**
     * @param  array<int, array<int, bool>>  $matriks
     * @param  array<int, array<int, bool>>  $terkunci
     */
    private static function pasangFinderPattern(array &$matriks, array &$terkunci, int $barisAwal, int $kolomAwal): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $y = $barisAwal + $dy;
                $x = $kolomAwal + $dx;

                if ($y < 0 || $y >= 21 || $x < 0 || $x >= 21) {
                    continue;
                }

                $dalamBatasFinder = $dy >= 0 && $dy <= 6 && $dx >= 0 && $dx <= 6;
                $gelap = $dalamBatasFinder && (
                    $dy === 0 || $dy === 6 || $dx === 0 || $dx === 6 ||
                    ($dy >= 2 && $dy <= 4 && $dx >= 2 && $dx <= 4)
                );

                $matriks[$y][$x] = $gelap;
                $terkunci[$y][$x] = true;
            }
        }
    }
}
