@if (! empty($penandatangan))
    <div class="ttd-blok">
        @if (! empty($tanggal_cetak) || ! empty($kop['kota']))
            <div class="ttd-kota-tanggal">
                {{ $kop['kota'] ?? 'Cianjur' }}, {{ $tanggal_cetak ?? now()->translatedFormat('d M Y') }}
            </div>
        @endif
        <table class="ttd-tabel">
            <tr>
                @foreach ($penandatangan as $ttd)
                    <td class="ttd-kolom">
                        <div class="ttd-jabatan">{{ $ttd['jabatan'] ?? '' }}</div>
                        <div class="ttd-ruang"></div>
                        <div class="ttd-nama">{{ $ttd['nama'] ?? '' }}</div>
                        @if (! empty($ttd['nip']))
                            <div class="ttd-nip">{{ $ttd['nip'] }}</div>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
@endif
