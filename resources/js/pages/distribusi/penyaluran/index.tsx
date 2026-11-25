import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Ban, CheckCircle2, Pencil, Plus, Search, Wallet } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Penyaluran', href: '/distribusi/penyaluran' },
];

interface PenyaluranRow {
    id: number;
    nomor: string;
    tanggal: string | null;
    status: 'draft' | 'disetujui' | 'dicairkan' | 'batal';
    mustahik: string | null;
    mustahik_kode: string | null;
    program: string | null;
    sub_bidang: string;
    asnaf: string | null;
    sumber_dana: string;
    metode: string | null;
    peringatan_saldo: string | null;
    total: number;
    ringkas_item: string;
}

interface Props {
    penyaluran: { data: PenyaluranRow[] };
    filter: { cari: string; status: string };
    bisa_kelola: boolean;
    bisa_setujui: boolean;
}

export default function PenyaluranIndex({ penyaluran, filter, bisa_kelola, bisa_setujui }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [status, setStatus] = React.useState(filter.status || 'semua');
    const [targetBatal, setTargetBatal] = React.useState<PenyaluranRow | null>(null);

    const formBatal = useForm({ alasan: '' });

    const terapkanFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('distribusi.penyaluran.index'), { cari, status: status === 'semua' ? '' : status }, { preserveState: true });
    };

    const kirimBatal = (e: React.FormEvent) => {
        e.preventDefault();
        if (!targetBatal) return;
        formBatal.post(route('distribusi.penyaluran.batalkan', targetBatal.id), {
            preserveScroll: true,
            onSuccess: () => {
                setTargetBatal(null);
                formBatal.reset();
            },
        });
    };

    const kolom: DataTableColumn<PenyaluranRow>[] = [
        {
            id: 'nomor',
            header: 'Nomor & Tanggal',
            cell: (r) => (
                <div>
                    <p className="text-foreground font-medium">{r.nomor}</p>
                    <p className="text-muted-foreground text-xs">{r.tanggal}</p>
                </div>
            ),
        },
        {
            id: 'mustahik',
            header: 'Mustahik',
            cell: (r) => (
                <div>
                    <p className="text-foreground font-medium">{r.mustahik ?? '-'}</p>
                    {r.mustahik_kode && <p className="text-muted-foreground text-xs">{r.mustahik_kode}</p>}
                </div>
            ),
        },
        {
            id: 'program',
            header: 'Program',
            cell: (r) => (
                <div>
                    <p className="text-foreground">{r.program ?? '-'}</p>
                    <p className="text-muted-foreground text-xs capitalize">{r.sub_bidang}</p>
                </div>
            ),
        },
        {
            id: 'asnaf',
            header: 'Asnaf & Sumber Dana',
            cell: (r) => (
                <div>
                    <p className="text-foreground">{r.asnaf ?? 'Non-Zakat'}</p>
                    <p className="text-muted-foreground text-xs">{r.sumber_dana || '-'}</p>
                </div>
            ),
        },
        {
            id: 'total',
            header: 'Nilai Bantuan',
            align: 'right',
            cell: (r) => (
                <div className="text-right">
                    <p className="font-semibold tabular-nums">{formatRupiah(r.total)}</p>
                    {r.peringatan_saldo && (
                        <p className="text-warning inline-flex items-center gap-1 text-xs">
                            <AlertTriangle className="h-3 w-3" aria-hidden="true" />
                            <span>Saldo kurang</span>
                        </p>
                    )}
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (r) => (
                <StatusBadge
                    status={r.status}
                    variant={r.status === 'dicairkan' ? 'success' : r.status === 'batal' ? 'ditolak' : r.status === 'disetujui' ? 'aktif' : 'draft'}
                    label={r.status}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            cell: (r) => (
                <div className="flex flex-wrap justify-end gap-1.5">
                    {r.status === 'draft' && bisa_kelola && (
                        <Button asChild size="sm" variant="outline" className="h-8 gap-1">
                            <Link href={route('distribusi.penyaluran.create', { draft_id: r.id })}>
                                <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                <span>Ubah Draft</span>
                            </Link>
                        </Button>
                    )}
                    {r.status === 'draft' && bisa_setujui && (
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            className="h-8 gap-1"
                            onClick={() => router.post(route('distribusi.penyaluran.setujui', r.id))}
                        >
                            <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Setujui</span>
                        </Button>
                    )}
                    {r.status === 'disetujui' && (
                        <Button
                            type="button"
                            size="sm"
                            className="h-8 gap-1"
                            onClick={() => router.post(route('distribusi.penyaluran.cairkan', r.id))}
                        >
                            <Wallet className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Cairkan</span>
                        </Button>
                    )}
                    {r.status !== 'batal' && bisa_setujui && (
                        <Button type="button" size="sm" variant="destructive" className="h-8 gap-1" onClick={() => setTargetBatal(r)}>
                            <Ban className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Batalkan</span>
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar Penyaluran Bantuan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Penyaluran Bantuan"
                    description="Transaksi penyaluran uang dan taksiran barang dengan pemisahan Zakat wajib asnaf dan Non-Zakat."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola && (
                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('distribusi.penyaluran.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Input Penyaluran Baru</span>
                                </Link>
                            </Button>
                        )
                    }
                />

                <form onSubmit={terapkanFilter} className="bg-card border-border grid gap-3 rounded-2xl border p-4 sm:grid-cols-4 sm:items-end">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="cari-penyaluran">Cari Nomor atau Nama Mustahik</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-penyaluran"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Nomor PNY atau nama mustahik..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-status-pny">Status</Label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger id="filter-status-pny" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="disetujui">Disetujui</SelectItem>
                                <SelectItem value="dicairkan">Dicairkan</SelectItem>
                                <SelectItem value="batal">Batal</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" variant="outline" className="h-9">
                        Terapkan
                    </Button>
                </form>

                {penyaluran.data.length === 0 ? (
                    <EmptyState title="Belum Ada Transaksi Penyaluran" description="Buat draft penyaluran bantuan baru dari tombol di atas." />
                ) : (
                    <DataTable data={penyaluran.data} columns={kolom} keyExtractor={(r) => r.id} />
                )}

                <Dialog open={Boolean(targetBatal)} onOpenChange={(open) => !open && setTargetBatal(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Batalkan Penyaluran {targetBatal?.nomor}</DialogTitle>
                            <DialogDescription>
                                Pembatalan transaksi yang sudah masuk Berita Acara final otomatis menerbitkan Berita Acara Revisi (Q-06).
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={kirimBatal} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="alasan-batal-pny">Alasan Pembatalan</Label>
                                <Input
                                    id="alasan-batal-pny"
                                    value={formBatal.data.alasan}
                                    onChange={(e) => formBatal.setData('alasan', e.target.value)}
                                    required
                                />
                                {formBatal.errors.alasan && <p className="text-destructive text-xs">{formBatal.errors.alasan}</p>}
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setTargetBatal(null)}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="destructive" disabled={formBatal.processing}>
                                    Konfirmasi Pembatalan
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
