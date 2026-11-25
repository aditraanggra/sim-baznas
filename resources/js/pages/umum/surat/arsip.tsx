import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatAngka } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, FileDown, FileSpreadsheet, Search } from 'lucide-react';
import * as React from 'react';

interface ArsipItem {
    uid: string;
    id: number;
    tipe: 'masuk' | 'keluar';
    tipe_label: string;
    nomor: string;
    nomor_referensi: string;
    tanggal: string | null;
    tanggal_label: string;
    pihak: string;
    perihal: string;
    jenis_surat: string;
    sifat: string;
    status_kode: string;
    status_label: string;
    url: string;
}

interface Props {
    items: ArsipItem[];
    ringkasan: { total: number; masuk: number; keluar: number };
    filter: {
        q: string;
        tipe: string;
        jenis_surat_id: string;
        sifat: string;
        status: string;
        dari: string;
        sampai: string;
    };
    jenis_list: { id: number; kode: string; nama: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDM, Administrasi & Umum', href: '/umum' },
    { title: 'Arsip & Agenda Surat', href: '/umum/surat/arsip' },
];

export default function ArsipSurat({ items, ringkasan, filter, jenis_list }: Props) {
    const [q, setQ] = React.useState(filter.q);

    const terapkan = (tambahan: Partial<typeof filter> = {}) => {
        const gabung = { ...filter, q, ...tambahan };
        const bersih = Object.fromEntries(Object.entries(gabung).filter(([, v]) => v !== '' && v !== 'semua'));
        router.get(route('umum.surat.arsip.index'), bersih, { preserveState: true, replace: true });
    };

    const queryEkspor = React.useMemo(
        () => Object.fromEntries(Object.entries({ ...filter, q }).filter(([, v]) => v !== '')),
        [filter, q],
    );

    const kolom: DataTableColumn<ArsipItem>[] = [
        {
            id: 'tanggal',
            header: 'Tanggal',
            sortable: true,
            sortValue: (i) => i.tanggal ?? '',
            cell: (i) => <span className="text-xs tabular-nums">{i.tanggal_label}</span>,
        },
        {
            id: 'tipe',
            header: 'Tipe',
            cell: (i) => (
                <StatusBadge
                    status={i.tipe}
                    variant={i.tipe === 'masuk' ? 'info' : 'disetujui'}
                    label={i.tipe_label}
                />
            ),
        },
        {
            id: 'nomor',
            header: 'Nomor Agenda / Surat',
            cell: (i) => (
                <div>
                    <Link href={i.url} className="text-foreground hover:text-primary font-medium hover:underline">
                        {i.nomor}
                    </Link>
                    <p className="text-muted-foreground text-xs">{i.jenis_surat}</p>
                </div>
            ),
        },
        {
            id: 'pihak',
            header: 'Pengirim / Tujuan & Perihal',
            cell: (i) => (
                <div className="max-w-md">
                    <p className="text-foreground font-medium">{i.pihak}</p>
                    <p className="text-muted-foreground line-clamp-1 text-xs">{i.perihal}</p>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (i) => (
                <StatusBadge
                    status={i.status_kode}
                    variant={
                        i.status_kode === 'selesai' || i.status_kode === 'disetujui'
                            ? 'final'
                            : i.status_kode === 'dibatalkan'
                              ? 'dibatalkan'
                              : 'warning'
                    }
                    label={i.status_label}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (i) => (
                <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                    <Link href={i.url}>
                        <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Buka</span>
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Arsip & Agenda Surat" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Arsip & Buku Agenda Surat"
                    description="Pencarian full-text seluruh surat masuk dan surat keluar sesuai cakupan akses Anda."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <a href={route('umum.surat.arsip.excel', queryEkspor)}>
                                    <FileSpreadsheet className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor Excel</span>
                                </a>
                            </Button>
                            <Button asChild className="h-9 gap-1.5">
                                <a href={route('umum.surat.arsip.pdf', queryEkspor)} target="_blank" rel="noreferrer">
                                    <FileDown className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor PDF Agenda</span>
                                </a>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Total Dokumen Terindeks</p>
                        <p className="text-foreground mt-1 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.total)}</p>
                    </div>
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Surat Masuk</p>
                        <p className="text-foreground mt-1 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.masuk)}</p>
                    </div>
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Surat Keluar</p>
                        <p className="text-foreground mt-1 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.keluar)}</p>
                    </div>
                </div>

                <div className="bg-card border-border space-y-3 rounded-2xl border p-4">
                    <div className="flex flex-wrap items-end gap-3">
                        <form
                            className="flex min-w-[260px] flex-1 gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                terapkan();
                            }}
                        >
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground pointer-events-none absolute top-2.5 left-3 h-4 w-4" aria-hidden="true" />
                                <Input
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Cari kata kunci isi surat, perihal, nomor agenda/surat, pengirim, atau tujuan..."
                                    className="h-9 pl-9"
                                />
                            </div>
                            <Button type="submit" variant="outline" className="h-9">
                                Cari
                            </Button>
                        </form>

                        <div className="w-40">
                            <Select value={filter.tipe || 'semua'} onValueChange={(v) => terapkan({ tipe: v })}>
                                <SelectTrigger className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Tipe</SelectItem>
                                    <SelectItem value="masuk">Surat Masuk</SelectItem>
                                    <SelectItem value="keluar">Surat Keluar</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="w-44">
                            <Select
                                value={filter.jenis_surat_id || 'semua'}
                                onValueChange={(v) => terapkan({ jenis_surat_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Jenis Surat Keluar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Jenis Keluar</SelectItem>
                                    {jenis_list.map((j) => (
                                        <SelectItem key={j.id} value={String(j.id)}>
                                            {j.kode} — {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-center gap-2">
                            <Input
                                type="date"
                                aria-label="Tanggal mulai"
                                value={filter.dari}
                                onChange={(e) => terapkan({ dari: e.target.value })}
                                className="h-9 w-36"
                            />
                            <span className="text-muted-foreground text-xs">s.d.</span>
                            <Input
                                type="date"
                                aria-label="Tanggal akhir"
                                value={filter.sampai}
                                onChange={(e) => terapkan({ sampai: e.target.value })}
                                className="h-9 w-36"
                            />
                        </div>
                    </div>
                </div>

                <div className="bg-card border-border rounded-2xl border p-5">
                    <DataTable
                        columns={kolom}
                        data={items}
                        keyExtractor={(i) => i.uid}
                        onRowClick={(i) => router.visit(i.url)}
                        emptyMessage="Tidak ditemukan dokumen surat yang cocok dengan kata kunci atau filter."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
