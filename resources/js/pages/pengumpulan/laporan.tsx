import { PageHeader, PemilihPeriode, type PeriodeFilter } from '@/components/app';
import { Button } from '@/components/ui/button';
import { LineTrendChart, type ChartDatum } from '@/components/ui/chart';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, TableCell, TableRow, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileDown, FileSpreadsheet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengumpulan', href: '/pengumpulan' },
    { title: 'Laporan Penerimaan', href: '/pengumpulan/laporan' },
];

interface BarisLaporan {
    id: number;
    penerimaan_id: number;
    tanggal: string;
    nomor_kwitansi: string;
    muzakki: string;
    jenis_dana: string;
    sumber_dana: string;
    metode_bayar: string;
    upz_nama: string | null;
    jumlah: number;
    keterangan: string | null;
}

interface Props {
    laporan: {
        periode: PeriodeFilter & { label: string };
        total_nominal: number;
        jumlah_transaksi: number;
        per_jenis_dana: { id: number; kode: string; nama: string; total: number }[];
        tren_harian: ChartDatum[];
        baris: BarisLaporan[];
    };
    filter: {
        jenis_dana_id: string;
        sumber_dana_id: string;
        upz_id: string;
        metode_bayar_id: string;
    };
    opsi: {
        jenis_dana: { id: number; nama: string }[];
        sumber_dana: { id: number; nama: string; jenis_dana_id: number }[];
        upz: { id: number; nama: string }[];
        metode_bayar: { id: number; nama: string }[];
    };
}

export default function LaporanPenerimaan({ laporan, filter, opsi }: Props) {
    const queryString = new URLSearchParams({
        jenis: laporan.periode.jenis,
        tahun: String(laporan.periode.tahun),
        bulan: String(laporan.periode.bulan ?? ''),
        triwulan: String(laporan.periode.triwulan ?? ''),
        semester: String(laporan.periode.semester ?? ''),
        tanggal: String(laporan.periode.tanggal ?? ''),
        ...filter,
    }).toString();

    const perbarui = (periodeBaru: PeriodeFilter, filterBaru = filter) => {
        router.get(
            route('pengumpulan.laporan.index'),
            {
                ...periodeBaru,
                ...filterBaru,
            },
            { preserveState: true },
        );
    };

    const kolom: DataTableColumn<BarisLaporan>[] = [
        { id: 'tanggal', header: 'Tanggal', cell: (b) => b.tanggal },
        {
            id: 'nomor',
            header: 'No. Kwitansi',
            cell: (b) => (
                <Link href={route('pengumpulan.penerimaan.show', b.penerimaan_id)} className="text-primary font-medium hover:underline">
                    {b.nomor_kwitansi}
                </Link>
            ),
        },
        { id: 'muzakki', header: 'Muzakki', cell: (b) => b.muzakki },
        { id: 'jenis', header: 'Jenis Dana', cell: (b) => b.jenis_dana },
        { id: 'sumber', header: 'Sumber Dana', cell: (b) => b.sumber_dana },
        { id: 'metode', header: 'Metode', cell: (b) => b.metode_bayar },
        { id: 'upz', header: 'UPZ', cell: (b) => b.upz_nama ?? 'Langsung' },
        { id: 'jumlah', header: 'Nominal', align: 'right', cell: (b) => <span className="font-semibold">{formatRupiah(b.jumlah)}</span> },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Penerimaan Dana ZIS" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Laporan Penerimaan Dana ZIS"
                    description={`Periode aktif: ${laporan.periode.label}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <a href={`${route('pengumpulan.laporan.pdf')}?${queryString}`} target="_blank" rel="noopener noreferrer">
                                    <FileDown className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor PDF</span>
                                </a>
                            </Button>
                            <Button asChild className="h-9 gap-1.5">
                                <a href={`${route('pengumpulan.laporan.excel')}?${queryString}`}>
                                    <FileSpreadsheet className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor Excel</span>
                                </a>
                            </Button>
                        </>
                    }
                />

                <div className="bg-card border-border space-y-4 rounded-xl border p-4">
                    <PemilihPeriode value={laporan.periode} onChange={(p) => perbarui(p, filter)} />

                    <div className="grid gap-3 border-t pt-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1">
                            <Label htmlFor="lap-jenis-dana">Jenis Dana</Label>
                            <Select
                                value={filter.jenis_dana_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, jenis_dana_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-jenis-dana" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Jenis Dana</SelectItem>
                                    {opsi.jenis_dana.map((j) => (
                                        <SelectItem key={j.id} value={String(j.id)}>
                                            {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="lap-sumber-dana">Sumber Dana</Label>
                            <Select
                                value={filter.sumber_dana_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, sumber_dana_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-sumber-dana" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Sumber Dana</SelectItem>
                                    {opsi.sumber_dana.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
                                            {s.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="lap-upz">UPZ</Label>
                            <Select
                                value={filter.upz_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, upz_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-upz" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua UPZ</SelectItem>
                                    {opsi.upz.map((u) => (
                                        <SelectItem key={u.id} value={String(u.id)}>
                                            {u.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="lap-metode">Metode Bayar</Label>
                            <Select
                                value={filter.metode_bayar_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, metode_bayar_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-metode" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Metode</SelectItem>
                                    {opsi.metode_bayar.map((m) => (
                                        <SelectItem key={m.id} value={String(m.id)}>
                                            {m.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-card border-border rounded-xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Total Penerimaan</p>
                        <p className="text-foreground mt-1 text-xl font-semibold tabular-nums">{formatRupiah(laporan.total_nominal)}</p>
                        <p className="text-muted-foreground mt-1 text-xs">{formatAngka(laporan.jumlah_transaksi)} transaksi final</p>
                    </div>
                    {laporan.per_jenis_dana.slice(0, 3).map((jd) => (
                        <div key={jd.id} className="bg-card border-border rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs font-medium">Dana {jd.nama}</p>
                            <p className="text-foreground mt-1 text-xl font-semibold tabular-nums">{formatRupiah(jd.total)}</p>
                        </div>
                    ))}
                </div>

                <LineTrendChart title={`Tren Penerimaan (${laporan.periode.label})`} data={laporan.tren_harian} isCurrency />

                <DataTable
                    columns={kolom}
                    data={laporan.baris}
                    keyExtractor={(b) => b.id}
                    emptyMessage="Tidak ada penerimaan final pada periode dan filter yang dipilih."
                    footerRow={
                        <TableRow>
                            <TableCell colSpan={7} className="text-right font-semibold">
                                Grand Total
                            </TableCell>
                            <TableCell className="text-right font-semibold tabular-nums">{formatRupiah(laporan.total_nominal)}</TableCell>
                        </TableRow>
                    }
                />
            </div>
        </AppLayout>
    );
}
