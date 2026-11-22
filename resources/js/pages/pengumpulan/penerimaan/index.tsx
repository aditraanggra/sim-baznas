import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengumpulan', href: '/pengumpulan' },
    { title: 'Daftar Penerimaan', href: '/pengumpulan/penerimaan' },
];

interface PenerimaanRow {
    id: number;
    nomor: string;
    tanggal: string;
    status: string;
    muzakki: string | null;
    upz: string | null;
    metode: string | null;
    sumber_dana: string;
    total: number;
}

interface Props {
    penerimaan: {
        data: PenerimaanRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filter: { cari: string; status: string };
    bisa_kelola: boolean;
}

export default function PenerimaanIndex({ penerimaan, filter, bisa_kelola }: Props) {
    const [cari, setCari] = React.useState(filter.cari);

    const kolom: DataTableColumn<PenerimaanRow>[] = [
        {
            id: 'nomor',
            header: 'No. Kwitansi / Transaksi',
            cell: (r) => (
                <Link
                    href={r.status === 'draft' ? route('pengumpulan.penerimaan.create', { draft_id: r.id }) : route('pengumpulan.penerimaan.show', r.id)}
                    className="text-foreground hover:text-primary font-medium hover:underline"
                >
                    {r.nomor}
                </Link>
            ),
        },
        { id: 'tanggal', header: 'Tanggal', cell: (r) => r.tanggal },
        { id: 'muzakki', header: 'Muzakki', cell: (r) => r.muzakki ?? '-' },
        { id: 'sumber', header: 'Sumber Dana', cell: (r) => r.sumber_dana },
        { id: 'metode', header: 'Metode', cell: (r) => r.metode ?? '-' },
        { id: 'upz', header: 'UPZ', cell: (r) => r.upz ?? 'Langsung' },
        { id: 'status', header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        { id: 'total', header: 'Total', align: 'right', cell: (r) => <span className="font-semibold">{formatRupiah(r.total)}</span> },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (r) => (
                <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                    <Link href={r.status === 'draft' ? route('pengumpulan.penerimaan.create', { draft_id: r.id }) : route('pengumpulan.penerimaan.show', r.id)}>
                        <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{r.status === 'draft' ? 'Lanjutkan' : 'Detail'}</span>
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar Penerimaan ZIS" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Daftar Transaksi Penerimaan"
                    description="Seluruh pencatatan penerimaan zakat, infak, sedekah, DSKL, dan CSR beserta status kwitansi."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola ? (
                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('pengumpulan.penerimaan.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Input Penerimaan</span>
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    className="bg-card border-border flex flex-wrap items-end gap-3 rounded-xl border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(route('pengumpulan.penerimaan.index'), { cari, status: filter.status }, { preserveState: true });
                    }}
                >
                    <div className="min-w-[220px] flex-1 space-y-1.5">
                        <Label htmlFor="cari-penerimaan">Cari Nomor Kwitansi atau Nama Muzakki</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2" aria-hidden="true" />
                            <Input id="cari-penerimaan" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Ketik nomor atau nama..." className="h-9 pl-8" />
                        </div>
                    </div>

                    <div className="w-full space-y-1.5 sm:w-44">
                        <Label htmlFor="filter-status-penerimaan">Status</Label>
                        <Select
                            value={filter.status || 'semua'}
                            onValueChange={(v) => router.get(route('pengumpulan.penerimaan.index'), { cari, status: v === 'semua' ? '' : v })}
                        >
                            <SelectTrigger id="filter-status-penerimaan" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="final">Final</SelectItem>
                                <SelectItem value="batal">Dibatalkan</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <Button type="submit" variant="outline" className="h-9">
                        Cari
                    </Button>
                </form>

                <DataTable
                    columns={kolom}
                    data={penerimaan.data}
                    keyExtractor={(r) => r.id}
                    page={penerimaan.current_page}
                    perPage={penerimaan.per_page}
                    total={penerimaan.total}
                    onPageChange={(hal) => router.get(route('pengumpulan.penerimaan.index'), { ...filter, page: hal })}
                    emptyMessage="Belum ada transaksi penerimaan tercatat."
                />
            </div>
        </AppLayout>
    );
}
