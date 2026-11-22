<?php

namespace App\Services;

class TotpService
{
    private const BASE32_ALFABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function buatSecret(int $panjangKarakter = 16): string
    {
        $secret = '';
        $maksIndeks = strlen(self::BASE32_ALFABET) - 1;

        for ($i = 0; $i < $panjangKarakter; $i++) {
            $secret .= self::BASE32_ALFABET[random_int(0, $maksIndeks)];
        }

        return $secret;
    }

    public function kodeSaatIni(string $secret, ?int $timestamp = null): string
    {
        $waktu = $timestamp ?? time();
        $counter = intdiv($waktu, 30);

        return $this->hotp($secret, $counter);
    }

    public function verifikasi(string $secret, string $kode, int $jendela = 1, ?int $timestamp = null): bool
    {
        $kodeBersih = preg_replace('/\s+/', '', trim($kode)) ?? '';
        if (! preg_match('/^\d{6}$/', $kodeBersih)) {
            return false;
        }

        $waktu = $timestamp ?? time();
        $counterSekarang = intdiv($waktu, 30);

        for ($offset = -$jendela; $offset <= $jendela; $offset++) {
            $kandidat = $this->hotp($secret, $counterSekarang + $offset);
            if (hash_equals($kandidat, $kodeBersih)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function buatRecoveryCodes(int $jumlah = 8): array
    {
        $kode = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $bagian1 = strtoupper(bin2hex(random_bytes(2)));
            $bagian2 = strtoupper(bin2hex(random_bytes(2)));
            $kode[] = "{$bagian1}-{$bagian2}";
        }

        return $kode;
    }

    public function uriOtpAuth(string $akun, string $secret, string $penerbit = 'SISFOZIS BAZNAS Cianjur'): string
    {
        $label = rawurlencode("{$penerbit}:{$akun}");

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            rawurlencode($secret),
            rawurlencode($penerbit),
        );
    }

    public function qrDataUri(string $akun, string $secret): string
    {
        return QrCodeSvg::dataUri($this->uriOtpAuth($akun, $secret), 180);
    }

    private function hotp(string $secret, int $counter): string
    {
        $kunciBiner = $this->decodeBase32($secret);
        $counterBiner = pack('J', $counter);
        $hmac = hash_hmac('sha1', $counterBiner, $kunciBiner, true);

        $offset = ord($hmac[19]) & 0x0F;
        $nilai = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($nilai % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $input): string
    {
        $bersih = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $input) ?? '');
        if ($bersih === '') {
            return '';
        }

        $buffer = 0;
        $jumlahBit = 0;
        $hasil = '';

        $panjang = strlen($bersih);
        for ($i = 0; $i < $panjang; $i++) {
            $posisi = strpos(self::BASE32_ALFABET, $bersih[$i]);
            if ($posisi === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $posisi;
            $jumlahBit += 5;

            if ($jumlahBit >= 8) {
                $jumlahBit -= 8;
                $hasil .= chr(($buffer >> $jumlahBit) & 0xFF);
            }
        }

        return $hasil;
    }
}
