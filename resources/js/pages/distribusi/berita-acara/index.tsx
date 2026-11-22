import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { DialogBatalkanBeritaAcara, DialogUbahBeritaAcara } from '@/components/app/dialog-berita-acara';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FileDown, Lock, Pencil, Plus, XCircle } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Berita Acara Harian', href: '/distribusi/berita-acara' },
];

interface BaRow {
    id: number;
    nomor: string;
    tanggal: string;
    tanggal_tampil: string;
    revisi_ke: number;
    status: 'draft' | 'final' | 'digantikan' | 'batal';
    total_rupiah: number;
    jumlah_transaksi: number;
    penanggung_jawab: string | null;
    catatan: string | null;
    alasan_batal?: string | null;
}

export default function BeritaAcaraPenyaluranIndex({
    berita_acara,
    bisa_kelola,
    bisa_batalkan_final,
}: {
    berita_acara: BaRow[];
    bisa_kelola: boolean;
    bisa_batalkan_final: boolean;
}) {
    const [targetFinal, setTargetFinal] = React.useState<BaRow | null>(null);
    const [targetUbah, setTargetUbah] = React.useState<BaRow | null>(null);
    const [targetBatal, setTargetBatal] = React.useState<BaRow | null>(null);
    const form = useForm({
        tanggal: new Date().toISOString().slice(0, 10),
        catatan: '',
    });

    const buatDraft = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('distribusi.berita-acara.store'), { preserveScroll: true });
    };

    const konfirmasiFinal = () => {
        if (!targetFinal) return;
        router.post(
            route('distribusi.berita-acara.finalkan', targetFinal.id),
            {},
            { preserveScroll: true, onFinish: () => setTargetFinal(null) },
        );
    };

    const kolom: DataTableColumn<BaRow>[] = [
        {
            id: 'nomor',
            header: 'Nomor Berita Acara',
            cell: (r) => (
                <div>
                    <p className="text-foreground font-medium">{r.nomor}</p>
                    {r.revisi_ke > 0 && <p className="text-muted-foreground text-xs">Revisi ke-{r.revisi_ke}</p>}
                    {r.status === 'batal' && r.alasan_batal && (
                        <p className="text-destructive mt-0.5 text-xs">Alasan: {r.alasan_batal}</p>
                    )}
                </div>
            ),
        },
        { id: 'tanggal', header: 'Tanggal', cell: (r) => r.tanggal_tampil },
        { id: 'jumlah', header: 'Transaksi', align: 'right', cell: (r) => formatAngka(r.jumlah_transaksi) },
        { id: 'total', header: 'Total Penyaluran', align: 'right', cell: (r) => <span className="font-semibold">{formatRupiah(r.total_rupiah)}</span> },
        {
            id: 'status',
            header: 'Status',
            cell: (r) => (
                <StatusBadge
                    status={r.status}
                    variant={r.status === 'final' ? 'final' : r.status === 'digantikan' || r.status === 'batal' ? 'ditolak' : 'draft'}
                    label={r.status === 'batal' ? 'dibatalkan' : r.status}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            cell: (r) => (
                <div className="flex flex-wrap justify-end gap-2">
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1">
                        <a href={route('distribusi.berita-acara.pdf', r.id)} target="_blank" rel="noreferrer">
                            <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>PDF</span>
                        </a>
                    </Button>
                    {bisa_kelola && r.status === 'draft' && (
                        <Button type="button" variant="outline" size="sm" className="h-8 gap-1" onClick={() => setTargetUbah(r)}>
                            <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                    )}
                    {((bisa_kelola && r.status === 'draft') || (bisa_batalkan_final && r.status === 'final')) && (
                        <Button type="button" variant="outline" size="sm" className="text-destructive h-8 gap-1" onClick={() => setTargetBatal(r)}>
                            <XCircle className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Batalkan</span>
                        </Button>
                    )}
                    {bisa_kelola && r.status === 'draft' && (
                        <Button type="button" size="sm" className="h-8 gap-1" onClick={() => setTargetFinal(r)}>
                            <Lock className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Finalkan</span>
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berita Acara Penyaluran Harian" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Berita Acara Penyaluran Harian"
                    description="Rekap transaksi dicairkan harian. Berita Acara final mengunci tanggal transaksi dari perubahan langsung (Q-06)."
                    breadcrumbs={breadcrumbs}
                />

                {bisa_kelola && (
                    <form onSubmit={buatDraft} className="bg-card border-border grid gap-3 rounded-2xl border p-4 sm:grid-cols-4 sm:items-end">
                        <div className="space-y-1.5">
                            <Label htmlFor="ba-pny-tanggal">Tanggal Penyaluran</Label>
                            <Input
                                id="ba-pny-tanggal"
                                type="date"
                                value={form.data.tanggal}
                                onChange={(e) => form.setData('tanggal', e.target.value)}
                                required
                            />
                            {form.errors.tanggal && <p className="text-destructive text-xs">{form.errors.tanggal}</p>}
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="ba-pny-catatan">Catatan</Label>
                            <Input
                                id="ba-pny-catatan"
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                                placeholder="Catatan rekapitulasi harian..."
                            />
                        </div>
                        <Button type="submit" className="h-9 gap-1.5" disabled={form.processing}>
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            <span>Buat Draft BA</span>
                        </Button>
                    </form>
                )}

                {berita_acara.length === 0 ? (
                    <EmptyState
                        title="Belum Ada Berita Acara Penyaluran"
                        description="Buat Berita Acara untuk mengunci transaksi penyaluran harian yang telah dicairkan."
                    />
                ) : (
                    <DataTable data={berita_acara} columns={kolom} keyExtractor={(r) => r.id} />
                )}

                <AlertDialog open={Boolean(targetFinal)} onOpenChange={(open) => !open && setTargetFinal(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Finalkan Berita Acara {targetFinal?.nomor}?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Setelah difinalkan, tanggal {targetFinal?.tanggal_tampil} dikunci terhadap penyaluran baru. Pembatalan transaksi di dalam BA final hanya dapat dilakukan melalui otorisasi Maker-Checker untuk menerbitkan Berita Acara Revisi.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Batal</AlertDialogCancel>
                            <AlertDialogAction onClick={konfirmasiFinal}>Finalkan & Kunci</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                <DialogUbahBeritaAcara target={targetUbah} onClose={() => setTargetUbah(null)} url={(id) => route('distribusi.berita-acara.update', id)} />
                <DialogBatalkanBeritaAcara target={targetBatal} onClose={() => setTargetBatal(null)} url={(id) => route('distribusi.berita-acara.batalkan', id)} />
            </div>
        </AppLayout>
    );
}
