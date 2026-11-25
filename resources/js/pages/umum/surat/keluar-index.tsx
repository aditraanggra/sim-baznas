import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, Search } from 'lucide-react';
import * as React from 'react';

interface SuratKeluarRow {
    id: number;
    nomor: string | null;
    jenis: string;
    jenis_kode: string;
    tanggal: string | null;
    tujuan: string;
    perihal: string;
    sifat: string;
    status_kode: string;
    status_label: string;
    pembuat: string;
    penyetuju: string | null;
}

interface Props {
    surat_list: {
        data: SuratKeluarRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    jenis_list: { id: number; kode: string; nama: string }[];
    filter: {
        cari: string;
        status: string;
        jenis_surat_id: number | null;
        sifat: string;
    };
    bisa_buat: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDM, Administrasi & Umum', href: '/umum' },
    { title: 'Surat Keluar', href: '/umum/surat/keluar' },
];

export default function SuratKeluarIndex({ surat_list, jenis_list, filter, bisa_buat }: Props) {
    const [cari, setCari] = React.useState(filter.cari);

    const terapkanFilter = (tambahan: Record<string, string> = {}) => {
        const gabung = {
            cari,
            status: filter.status,
            jenis_surat_id: filter.jenis_surat_id ? String(filter.jenis_surat_id) : '',
            sifat: filter.sifat,
            ...tambahan,
        };
        const bersih = Object.fromEntries(Object.entries(gabung).filter(([, v]) => v !== ''));
        router.get(route('umum.surat.keluar.index'), bersih, { preserveState: true, replace: true });
    };

    const kolom: DataTableColumn<SuratKeluarRow>[] = [
        {
            id: 'nomor',
            header: 'Nomor & Jenis',
            cell: (s) => (
                <div>
                    <Link href={route('umum.surat.keluar.show', s.id)} className="text-foreground hover:text-primary font-medium hover:underline">
                        {s.nomor ?? 'Draft (Belum Bernomor)'}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {s.jenis_kode} · {s.jenis}
                    </p>
                </div>
            ),
        },
        {
            id: 'tujuan',
            header: 'Tujuan & Perihal',
            cell: (s) => (
                <div className="max-w-md">
                    <p className="text-foreground font-medium">{s.tujuan}</p>
                    <p className="text-muted-foreground line-clamp-1 text-xs">{s.perihal}</p>
                </div>
            ),
        },
        {
            id: 'tanggal',
            header: 'Tanggal',
            sortable: true,
            sortValue: (s) => s.tanggal ?? '',
            cell: (s) => <span className="text-xs tabular-nums">{s.tanggal ?? '-'}</span>,
        },
        {
            id: 'status',
            header: 'Status',
            cell: (s) => (
                <StatusBadge
                    status={s.status_kode}
                    variant={
                        s.status_kode === 'disetujui'
                            ? 'final'
                            : s.status_kode === 'dibatalkan'
                              ? 'dibatalkan'
                              : s.status_kode === 'menunggu_persetujuan'
                                ? 'warning'
                                : 'draft'
                    }
                    label={s.status_label}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (s) => (
                <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                    <Link href={route('umum.surat.keluar.show', s.id)}>
                        <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Detail</span>
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Surat Keluar" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Surat Keluar"
                    description="Buat surat keluar dari template resmi, ajukan persetujuan Maker-Checker, dan cetak PDF kop BAZNAS."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_buat ? (
                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('umum.surat.keluar.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Buat Surat Keluar</span>
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />

                <div className="bg-card border-border rounded-2xl border p-4">
                    <div className="flex flex-wrap items-end gap-3">
                        <form
                            className="flex min-w-[240px] flex-1 gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                terapkanFilter();
                            }}
                        >
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground pointer-events-none absolute top-2.5 left-3 h-4 w-4" aria-hidden="true" />
                                <Input
                                    value={cari}
                                    onChange={(e) => setCari(e.target.value)}
                                    placeholder="Cari nomor surat, tujuan, atau perihal..."
                                    className="h-9 pl-9"
                                />
                            </div>
                            <Button type="submit" variant="outline" className="h-9">
                                Cari
                            </Button>
                        </form>

                        <div className="w-44">
                            <Select
                                value={filter.jenis_surat_id ? String(filter.jenis_surat_id) : 'semua'}
                                onValueChange={(v) => terapkanFilter({ jenis_surat_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Semua Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Jenis</SelectItem>
                                    {jenis_list.map((j) => (
                                        <SelectItem key={j.id} value={String(j.id)}>
                                            {j.kode} — {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="w-44">
                            <Select value={filter.status || 'semua'} onValueChange={(v) => terapkanFilter({ status: v === 'semua' ? '' : v })}>
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="menunggu_persetujuan">Menunggu Persetujuan</SelectItem>
                                    <SelectItem value="disetujui">Disetujui / Final</SelectItem>
                                    <SelectItem value="dibatalkan">Dibatalkan</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="bg-card border-border rounded-2xl border p-5">
                    <DataTable
                        columns={kolom}
                        data={surat_list.data}
                        keyExtractor={(s) => s.id}
                        page={surat_list.current_page}
                        perPage={surat_list.per_page}
                        total={surat_list.total}
                        onPageChange={(page) =>
                            router.get(route('umum.surat.keluar.index'), { ...filter, page }, { preserveState: true })
                        }
                        onRowClick={(s) => router.visit(route('umum.surat.keluar.show', s.id))}
                        emptyMessage="Belum ada surat keluar sesuai filter."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
