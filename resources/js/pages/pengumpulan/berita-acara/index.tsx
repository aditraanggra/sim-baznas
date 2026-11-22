import { PageHeader, StatusBadge } from '@/components/app';
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
    { title: 'Pengumpulan', href: '/pengumpulan' },
    { title: 'Berita Acara Harian', href: '/pengumpulan/berita-acara' },
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

export default function BeritaAcaraPenerimaanIndex({
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
        form.post(route('pengumpulan.berita-acara.store'), { preserveScroll: true });
    };

    const konfirmasiFinal = () => {
        if (!targetFinal) return;
        router.post(
            route('pengumpulan.berita-acara.finalkan', targetFinal.id),
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
        { id: 'total', header: 'Total Penerimaan', align: 'right', cell: (r) => <span className="font-semibold">{formatRupiah(r.total_rupiah)}</span> },
        {
            id: 'status',
            header: 'Status',
            cell: (r) => (
                <StatusBadge
                    status={r.status}
                    variant={r.status === 'final' ? 'final' : r.status === 'digantikan' || r.status === 'batal' ? 'dibatalkan' : 'draft'}
                    label={
                        r.status === 'final'
                            ? 'Final (Terkunci)'
                            : r.status === 'digantikan'
                              ? 'Digantikan Revisi'
                              : r.status === 'batal'
                                ? 'Dibatalkan'
                                : 'Draft'
                    }
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (r) => (
                <div className="flex justify-end gap-1.5">
                    {bisa_kelola && r.status === 'draft' && (
                        <Button type="button" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => setTargetFinal(r)}>
                            <Lock className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Finalkan</span>
                        </Button>
                    )}
                    {bisa_kelola && r.status === 'draft' && (
                        <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => setTargetUbah(r)}>
                            <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                    )}
                    {((bisa_kelola && r.status === 'draft') || (bisa_batalkan_final && r.status === 'final')) && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-destructive h-8 gap-1 px-2.5 text-xs"
                            onClick={() => setTargetBatal(r)}
                        >
                            <XCircle className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Batalkan</span>
                        </Button>
                    )}
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                        <a href={route('pengumpulan.berita-acara.pdf', r.id)} target="_blank" rel="noopener noreferrer">
                            <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Cetak PDF</span>
                        </a>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berita Acara Penerimaan Harian" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Berita Acara Penerimaan Harian"
                    description="Berita acara dibuat dari agregasi seluruh transaksi final pada hari yang dipilih dan mengunci tanggal saat difinalkan."
                    breadcrumbs={breadcrumbs}
                />

                {bisa_kelola && (
                    <form onSubmit={buatDraft} className="bg-card border-border flex flex-wrap items-end gap-3 rounded-xl border p-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="ba-tanggal">Tanggal Penerimaan (wajib)</Label>
                            <Input id="ba-tanggal" type="date" required value={form.data.tanggal} onChange={(e) => form.setData('tanggal', e.target.value)} className="h-9" />
                            {form.errors.tanggal && <p className="text-destructive text-xs">{form.errors.tanggal}</p>}
                        </div>

                        <div className="min-w-[220px] flex-1 space-y-1.5">
                            <Label htmlFor="ba-catatan">Catatan Berita Acara</Label>
                            <Input
                                id="ba-catatan"
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                                placeholder="Keterangan penutupan kas harian"
                                className="h-9"
                            />
                        </div>

                        <Button type="submit" disabled={form.processing} className="h-9 gap-1.5">
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            <span>Buat Draft BA Harian</span>
                        </Button>
                    </form>
                )}

                <DataTable columns={kolom} data={berita_acara} keyExtractor={(r) => r.id} emptyMessage="Belum ada berita acara penerimaan harian." />
            </div>

            <AlertDialog open={Boolean(targetFinal)} onOpenChange={(open) => !open && setTargetFinal(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Finalkan Berita Acara {targetFinal?.nomor}?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Setelah berita acara difinalkan, tanggal {targetFinal?.tanggal_tampil} akan dikunci sehingga transaksi baru bertanggal sama tidak dapat ditambahkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={konfirmasiFinal}>Finalkan & Kunci Tanggal</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <DialogUbahBeritaAcara target={targetUbah} onClose={() => setTargetUbah(null)} url={(id) => route('pengumpulan.berita-acara.update', id)} />
            <DialogBatalkanBeritaAcara target={targetBatal} onClose={() => setTargetBatal(null)} url={(id) => route('pengumpulan.berita-acara.batalkan', id)} />
        </AppLayout>
    );
}
