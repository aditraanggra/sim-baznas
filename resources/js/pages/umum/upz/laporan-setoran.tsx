import { PageHeader } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, TableCell, TableRow, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Baris {
    id: number;
    kode: string;
    nama: string;
    jenis_upz: string;
    wilayah: string;
    jumlah_transaksi: number;
    total_setoran: number;
}

export default function LaporanSetoranUpz({ tahun, baris, total_semua }: { tahun: number; baris: Baris[]; total_semua: number }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'UPZ', href: '/umum/upz' },
        { title: 'Laporan Setoran UPZ', href: '/umum/upz/laporan-setoran' },
    ];

    const tahunPilihan = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - 4 + i);

    const kolom: DataTableColumn<Baris>[] = [
        {
            id: 'nama',
            header: 'Nama UPZ',
            sortable: true,
            sortValue: (b) => b.nama,
            cell: (b) => (
                <Link href={route('umum.upz.show', b.id)} className="text-foreground hover:text-primary font-medium hover:underline">
                    {b.nama}
                </Link>
            ),
        },
        { id: 'jenis', header: 'Jenis', cell: (b) => b.jenis_upz },
        { id: 'wilayah', header: 'Wilayah', cell: (b) => b.wilayah },
        {
            id: 'transaksi',
            header: 'Transaksi Final',
            align: 'right',
            sortable: true,
            sortValue: (b) => b.jumlah_transaksi,
            cell: (b) => formatAngka(b.jumlah_transaksi),
        },
        {
            id: 'total',
            header: 'Total Setoran',
            align: 'right',
            sortable: true,
            sortValue: (b) => b.total_setoran,
            cell: (b) => <span className="font-semibold">{formatRupiah(b.total_setoran)}</span>,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Setoran UPZ" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Laporan Setoran per UPZ"
                    description={`Rekapitulasi setoran penerimaan final melalui jaringan UPZ tahun ${tahun}.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <div className="flex items-center gap-2">
                            <Select
                                value={String(tahun)}
                                onValueChange={(nilai) => router.get(route('umum.upz.laporan-setoran'), { tahun: nilai })}
                            >
                                <SelectTrigger className="h-9 w-28" aria-label="Tahun laporan">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {tahunPilihan.map((t) => (
                                        <SelectItem key={t} value={String(t)}>
                                            {t}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('umum.upz.index')}>Kembali ke Daftar UPZ</Link>
                            </Button>
                        </div>
                    }
                />

                <DataTable
                    columns={kolom}
                    data={baris}
                    keyExtractor={(b) => b.id}
                    emptyMessage="Belum ada data setoran UPZ pada tahun ini."
                    footerRow={
                        <TableRow>
                            <TableCell colSpan={4} className="text-right font-semibold">
                                Total Seluruh UPZ
                            </TableCell>
                            <TableCell className="text-right font-semibold tabular-nums">{formatRupiah(total_semua)}</TableCell>
                        </TableRow>
                    }
                />
            </div>
        </AppLayout>
    );
}
