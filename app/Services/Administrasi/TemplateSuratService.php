<?php

namespace App\Services\Administrasi;

class TemplateSuratService
{
    public const TOKEN_HEADER = [
        'tujuan',
        'perihal',
        'tanggal_surat',
        'lampiran',
        'nama_penandatangan',
        'jabatan_penandatangan',
    ];

    /**
     * @return list<string>
     */
    public function ekstrakPlaceholder(string $isi): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $isi, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return list<string>
     */
    public function placeholderKustom(string $isi): array
    {
        return array_values(array_filter(
            $this->ekstrakPlaceholder($isi),
            fn (string $token) => ! in_array($token, self::TOKEN_HEADER, true),
        ));
    }

    /**
     * @param  array<string, string|null>  $nilai
     */
    public function render(string $isi, array $nilai, bool $gantiKosongDenganStrip = false): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $cocok) use ($nilai, $gantiKosongDenganStrip): string {
                $kunci = $cocok[1];
                $val = $nilai[$kunci] ?? null;

                if ($val !== null && trim((string) $val) !== '') {
                    return trim((string) $val);
                }

                return $gantiKosongDenganStrip ? '-' : $cocok[0];
            },
            $isi,
        );
    }

    public static function labelDariToken(string $token): string
    {
        return ucwords(str_replace('_', ' ', $token));
    }
}
