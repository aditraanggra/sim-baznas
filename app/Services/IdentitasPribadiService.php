<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class IdentitasPribadiService
{
    public function bersihkanDigit(?string $nilai): string
    {
        return preg_replace('/\D+/', '', (string) $nilai) ?? '';
    }

    public function enkripsi(?string $nilai): ?string
    {
        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        return Crypt::encryptString($teks);
    }

    public function dekripsi(?string $terenkripsi): ?string
    {
        if (empty($terenkripsi)) {
            return null;
        }

        try {
            return Crypt::decryptString($terenkripsi);
        } catch (\Throwable) {
            return null;
        }
    }

    public function hashPencarian(?string $nilai): ?string
    {
        $digit = $this->bersihkanDigit($nilai);
        if ($digit === '') {
            return null;
        }

        return hash_hmac('sha256', $digit, (string) config('app.key'));
    }

    public function maskNik(?string $nik): ?string
    {
        $digit = $this->bersihkanDigit($nik);
        if ($digit === '') {
            return null;
        }

        $panjang = strlen($digit);
        if ($panjang <= 6) {
            return str_repeat('*', $panjang);
        }

        return substr($digit, 0, 4).str_repeat('*', max(4, $panjang - 6)).substr($digit, -2);
    }

    public function normalisasiTelepon(?string $telepon): ?string
    {
        $digit = $this->bersihkanDigit($telepon);
        if ($digit === '') {
            return null;
        }

        if (str_starts_with($digit, '62')) {
            $digit = '0'.substr($digit, 2);
        }

        return $digit;
    }

    public function empatDigitAkhirTelepon(?string $telepon): ?string
    {
        $norm = $this->normalisasiTelepon($telepon);
        if ($norm === null || strlen($norm) < 4) {
            return null;
        }

        return substr($norm, -4);
    }

    public function samarkanNamaPublik(string $nama): string
    {
        $kata = preg_split('/\s+/', trim($nama)) ?: [];

        return collect($kata)
            ->filter(fn (string $k) => $k !== '')
            ->map(function (string $k): string {
                $len = mb_strlen($k);
                if ($len <= 1) {
                    return '*';
                }

                return mb_substr($k, 0, 1).str_repeat('*', min(5, max(2, $len - 1)));
            })
            ->implode(' ');
    }
}
