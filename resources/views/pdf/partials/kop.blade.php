<table class="kop-tabel">
    <tr>
        <td class="kop-logo">
            <img src="{{ $kop['logo_data_uri'] }}" alt="Logo BAZNAS">
        </td>
        <td class="kop-teks">
            <div class="kop-lembaga">{{ $kop['lembaga'] ?? 'BADAN AMIL ZAKAT NASIONAL (BAZNAS)' }}</div>
            <div class="kop-kabupaten">{{ $kop['kabupaten'] ?? 'KABUPATEN CIANJUR' }}</div>
            <div class="kop-alamat">
                {{ $kop['alamat'] ?? '' }}
            </div>
            <div class="kop-kontak">
                @if (! empty($kop['whatsapp']))
                    Whatsapp: {{ $kop['whatsapp'] }} |
                @endif
                Telp: {{ $kop['telepon'] ?? '-' }} | Email: {{ $kop['email'] ?? '-' }}
                @if (! empty($kop['website']))
                    | Web: {{ $kop['website'] }}
                @endif
            </div>
        </td>
    </tr>
</table>
