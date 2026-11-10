<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RkatLampiranSheet extends DefaultValueBinder implements FromArray, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithStyles, WithTitle
{
    /**
     * @param  array{judul: string, sheet: string, kepala: list<string>, baris: list<array>}  $bagian
     * @param  list<string>  $catatan
     */
    public function __construct(private array $bagian, private array $catatan) {}

    /** Kolom No tetap teks: "1.10" tidak boleh menjadi angka 1,1. */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === 'A' && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return $this->bagian['sheet'];
    }

    public function array(): array
    {
        $rows = array_map(fn (string $k) => [$k], $this->bagian['kepala']);
        $rows[] = [''];
        $rows[] = ['No', 'Uraian', 'Persen', 'Jumlah', 'Satuan'];

        foreach ($this->bagian['baris'] as $b) {
            $rows[] = [$b['no'], $b['uraian'], $b['persen'], $b['jumlah'], $b['satuan']];
        }

        if ($this->catatan) {
            $rows[] = [''];
            foreach ($this->catatan as $c) {
                $rows[] = [$c];
            }
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return ['D' => '#,##0'];
    }

    public function columnWidths(): array
    {
        return ['A' => 10, 'B' => 60, 'C' => 10, 'D' => 22, 'E' => 10];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $kepala = count($this->bagian['kepala']) + 2;
        $awal = $kepala + 1;
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A{$kepala}:E{$kepala}")->getFont()->setBold(true);
        $sheet->getStyle("C{$kepala}:D{$kepala}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($this->bagian['baris'] as $i => $b) {
            $r = $awal + $i;
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B{$r}")->getAlignment()->setIndent($b['tingkat'] * 2);
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            if ($b['induk']) {
                $sheet->getStyle("A{$r}:E{$r}")->getFont()->setBold(true);
            }
        }

        return null;
    }
}
