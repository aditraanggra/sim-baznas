<?php

namespace App\Services\Rkat;

use App\Models\RkatVersion;

/** FR-R-08: sumber baris tunggal untuk ekspor Excel dan PDF. */
final class BarisLampiran
{
    private const BAGIAN = [
        'L2' => ['Neraca', 'Lampiran 2 - Rencana Penerimaan'],
        'L2/LN' => ['Luar Neraca', 'Lampiran 2 - Rencana Penerimaan (Luar Neraca)'],
        'L3' => ['L3', 'Lampiran 3 - Rencana Penyaluran Berdasarkan Jenis Dana dan Asnaf'],
        'L4' => ['L4', 'Lampiran 4 - Rencana Pendistribusian dan Pendayagunaan'],
        'L5' => ['L5', 'Lampiran 5 - Rencana Penggalangan Muzakki dan Mustahik'],
        'L6' => ['L6', 'Lampiran 6 - Rencana Penerimaan Dana Operasional'],
        'L7' => ['L7', 'Lampiran 7 - Rencana Penggunaan Dana Operasional'],
    ];

    /**
     * @return array{bagian: list<array{judul: string, sheet: string, kepala: list<string>, baris: list<array{no: string, uraian: string, tingkat: int, induk: bool, persen: string, jumlah: int, satuan: string}>}>, catatan: list<string>}
     */
    public static function untuk(RkatVersion $versi): array
    {
        $h = HitungRkat::untukVersi($versi);
        $total = HitungRkat::totalPuncak($h['pos'], $h['nilai']);
        $punyaAnak = $h['pos']->pluck('induk_id')->filter()->flip();
        $tingkat = [];
        $kepala = self::kepala($versi);

        $baris = [];
        foreach ($h['pos'] as $p) {
            $tingkat[$p->id] = $p->induk_id ? ($tingkat[$p->induk_id] ?? 0) + 1 : 0;
            $rupiah = $p->tipe_nilai === 'rupiah';
            $grup = HitungRkat::grup($p);
            $baris[$grup][] = [
                'no' => preg_replace('#^L\d(/LN)?/#', '', $p->kode),
                'uraian' => $p->nama,
                'tingkat' => $tingkat[$p->id],
                'induk' => $punyaAnak->has($p->id),
                'persen' => $rupiah ? HitungRkat::tampil(HitungRkat::persen($h['nilai'][$p->id], $total[$grup] ?? 0)) : 'n/a',
                'jumlah' => $h['nilai'][$p->id],
                'satuan' => $rupiah ? 'Rupiah' : $p->tipe_nilai,
            ];
        }

        $bagian = [];
        foreach (self::BAGIAN as $grup => [$sheet, $judul]) {
            if (isset($baris[$grup])) {
                $bagian[] = ['judul' => $judul, 'sheet' => $grup === 'L2' || $grup === 'L2/LN' ? 'L2 '.$sheet : $sheet, 'kepala' => [$judul, ...$kepala], 'baris' => $baris[$grup]];
            }
        }

        $catatan = $h['tak_terpetakan']->isEmpty() ? [] : [
            'Pos tak terpetakan (tidak ikut tabel dan total):',
            ...$h['tak_terpetakan']->map(fn ($v) => "{$v->kode_asal} {$v->nama_asal}: ".number_format($v->nilai, 0, ',', '.'))->all(),
        ];

        return ['bagian' => $bagian, 'catatan' => $catatan];
    }

    /** @return list<string> */
    private static function kepala(RkatVersion $versi): array
    {
        $baris = ["RKAT {$versi->tahun} - {$versi->label()} - ".ucfirst($versi->status)];
        if ($versi->nomor_surat || $versi->tanggal_surat) {
            $baris[] = trim('Surat: '.$versi->nomor_surat.' '.$versi->tanggal_surat?->format('d/m/Y'));
        }

        return $baris;
    }
}
