<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgendaSuratExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, string>  $filter
     */
    public function __construct(
        private readonly array $items,
        private readonly array $filter = [],
    ) {}

    public function title(): string
    {
        return 'Buku Agenda Surat';
    }

    public function array(): array
    {
        $rows = [
            ['BAZNAS KABUPATEN CIANJUR'],
            ['BUKU AGENDA SURAT MASUK DAN SURAT KELUAR'],
            ['Dicetak: '.now()->format('d/m/Y H:i').' WIB'],
            [''],
            ['No', 'Tanggal', 'Jenis Agenda', 'Nomor Surat / Agenda', 'Kategori', 'Pengirim / Tujuan', 'Perihal', 'Sifat', 'Status'],
        ];

        foreach ($this->items as $idx => $item) {
            $rows[] = [
                $idx + 1,
                $item['tanggal_label'] ?? '-',
                $item['tipe_label'] ?? '-',
                $item['nomor'] ?? '-',
                $item['jenis_surat'] ?? '-',
                $item['pihak'] ?? '-',
                $item['perihal'] ?? '-',
                strtoupper((string) ($item['sifat'] ?? 'biasa')),
                $item['status_label'] ?? '-',
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 16,
            'D' => 28,
            'E' => 22,
            'F' => 28,
            'G' => 36,
            'H' => 12,
            'I' => 24,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);

        return null;
    }
}
