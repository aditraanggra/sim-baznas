import { InputRupiah } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Archive, CheckCircle2, CircleDashed, Plus, Send, type LucideIcon } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rencana (RKAT)', href: '/pkp/rencana' }];

interface VersiRingkas {
    id: number;
    label: string;
    status: string;
    mode_penyusunan?: string;
}

interface BarisTahun {
    tahun: number;
    acuan_id: number | null;
    acuan_label: string | null;
    status: string | null;
    sumber: string | null;
    mode_penyusunan?: string | null;
    target_pengumpulan?: number | null;
    tahap_terverifikasi?: number | null;
    nomor_surat: string | null;
    total_penerimaan: number | null;
    total_penyaluran: number | null;
    total_operasional: number | null;
    versi: VersiRingkas[];
    punya_disahkan: boolean;
}

const STATUS: Record<string, { teks: string; ikon: LucideIcon }> = {
    draft: { teks: 'Draft', ikon: CircleDashed },
    diajukan: { teks: 'Diajukan', ikon: Send },
    disahkan: { teks: 'Disahkan', ikon: CheckCircle2 },
    digantikan: { teks: 'Digantikan', ikon: Archive },
};

const SUMBER: Record<string, string> = { manual: 'Manual', impor: 'Impor' };

export function LencanaStatus({ status }: { status: string | null }) {
    if (!status) return <span className="text-muted-foreground">Belum ada</span>;
    const { teks, ikon: Ikon } = STATUS[status] ?? { teks: status, ikon: CircleDashed };
    return (
        <span className="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium">
            <Ikon className="size-3.5" aria-hidden="true" />
            {teks}
        </span>
    );
}

const Rupiah = ({ nilai }: { nilai: number | null }) => (
    <span title={nilai === null ? undefined : formatRupiah(nilai)}>{formatRupiah(nilai, true)}</span>
);

export default function Index({ daftar, dapat_tulis }: { daftar: BarisTahun[]; dapat_tulis: boolean }) {
    const { props } = usePage<SharedData>();
    const galat = Object.values(props.errors ?? {});
    const sukses = props.flash?.sukses;
    const [dialogKolaboratif, setDialogKolaboratif] = React.useState(false);

    const formKolaboratif = useForm({
        tahun: Math.max(new Date().getFullYear() + 1, 2027),
        jenis: 'awal',
        mode_penyusunan: 'kolaboratif',
        target_pengumpulan: 0,
    });

    const buatKolaboratif = (e: React.FormEvent) => {
        e.preventDefault();
        formKolaboratif.post(route('pkp.rencana.buat'), {
            preserveScroll: true,
            onSuccess: () => setDialogKolaboratif(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rencana (RKAT)" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold">Rencana Kerja dan Anggaran Tahunan</h1>
                    <div className="flex flex-wrap items-center gap-3 text-sm">
                        <Link className="underline" href={route('pkp.rencana.bandingkan')}>
                            Bandingkan
                        </Link>
                        {dapat_tulis && (
                            <>
                                <Link className="underline" href={route('pkp.rencana.impor')}>
                                    Impor RKAT
                                </Link>
                                <Button type="button" className="h-9" onClick={() => setDialogKolaboratif(true)}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    Buat RKAT Kolaboratif
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {sukses && (
                    <p role="status" className="rounded-md border px-3 py-2 text-sm">
                        <CheckCircle2 className="mr-1 inline size-4" aria-hidden="true" />
                        {sukses}
                    </p>
                )}
                {galat.length > 0 && (
                    <div role="alert" className="rounded-md border border-red-600 px-3 py-2 text-sm text-red-700">
                        {galat.map((g, i) => (
                            <p key={i}>Galat: {String(g)}</p>
                        ))}
                    </div>
                )}

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Tahun</th>
                                <th className="px-3 py-2">Versi acuan</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Sumber</th>
                                <th className="px-3 py-2">Nomor surat</th>
                                <th className="px-3 py-2 text-right">Total penerimaan</th>
                                <th className="px-3 py-2 text-right">Total penyaluran</th>
                                <th className="px-3 py-2 text-right">Total biaya operasional</th>
                                <th className="px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {daftar.map((b) => (
                                <tr key={b.tahun} className="border-t align-top">
                                    <td className="px-3 py-2 font-medium tabular-nums">{b.tahun}</td>
                                    <td className="px-3 py-2">
                                        <div>{b.acuan_label ?? '—'}</div>
                                        {b.mode_penyusunan === 'kolaboratif' && (
                                            <span className="bg-primary-soft text-primary mt-0.5 inline-block rounded px-1.5 py-0.5 text-[11px] font-medium">
                                                Kolaboratif ({b.tahap_terverifikasi ?? 0}/3 bidang)
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <LencanaStatus status={b.status} />
                                    </td>
                                    <td className="px-3 py-2">{b.sumber ? (SUMBER[b.sumber] ?? b.sumber) : '—'}</td>
                                    <td className="px-3 py-2">{b.nomor_surat ?? '—'}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        <Rupiah nilai={b.total_penerimaan} />
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        <Rupiah nilai={b.total_penyaluran} />
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        <Rupiah nilai={b.total_operasional} />
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {b.acuan_id !== null && (
                                                <Link
                                                    className="underline"
                                                    href={route('pkp.rencana.show', b.acuan_id)}
                                                    aria-label={`Lihat RKAT ${b.tahun}`}
                                                >
                                                    Lihat
                                                </Link>
                                            )}
                                            <Link className="underline" href={route('pkp.rencana.impor')} aria-label={`Impor RKAT ${b.tahun}`}>
                                                Impor
                                            </Link>
                                            {dapat_tulis && b.versi.length === 0 && (
                                                <button
                                                    type="button"
                                                    className="rounded-md border px-2 py-1 text-xs"
                                                    onClick={() =>
                                                        router.post(
                                                            route('pkp.rencana.buat'),
                                                            { tahun: b.tahun, jenis: 'awal' },
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                >
                                                    Buat RKAT Awal
                                                </button>
                                            )}
                                            {dapat_tulis && b.punya_disahkan && (
                                                <button
                                                    type="button"
                                                    className="rounded-md border px-2 py-1 text-xs"
                                                    onClick={() =>
                                                        router.post(
                                                            route('pkp.rencana.buat'),
                                                            { tahun: b.tahun, jenis: 'perubahan' },
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                >
                                                    Buat versi Perubahan
                                                </button>
                                            )}
                                        </div>
                                        {b.versi.length > 0 && (
                                            <ul className="text-muted-foreground mt-1 flex flex-wrap gap-x-3 text-xs">
                                                {b.versi.map((v) => (
                                                    <li key={v.id}>
                                                        <Link className="underline" href={route('pkp.rencana.show', v.id)}>
                                                            {v.label}
                                                        </Link>{' '}
                                                        ({STATUS[v.status]?.teks ?? v.status})
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Dialog open={dialogKolaboratif} onOpenChange={setDialogKolaboratif}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Buat RKAT Kolaboratif 3 Bidang</DialogTitle>
                            <DialogDescription>
                                Tetapkan Target Pengumpulan RAKORNAS untuk membuka penyusunan rincian kegiatan di Bidang Pengumpulan, Pendis/Pendag,
                                dan SAU.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={buatKolaboratif} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="rkat-tahun">Tahun Anggaran</Label>
                                <Input
                                    id="rkat-tahun"
                                    type="number"
                                    min={2022}
                                    max={2100}
                                    className="h-9 tabular-nums"
                                    value={formKolaboratif.data.tahun}
                                    onChange={(e) => formKolaboratif.setData('tahun', Number.parseInt(e.target.value || '2027', 10))}
                                />
                                {formKolaboratif.errors.tahun && <p className="text-destructive text-xs">{formKolaboratif.errors.tahun}</p>}
                            </div>

                            <InputRupiah
                                id="rkat-target"
                                label="Target Pengumpulan RAKORNAS (L2 Neraca)"
                                required
                                value={formKolaboratif.data.target_pengumpulan}
                                onChange={(val) => formKolaboratif.setData('target_pengumpulan', val)}
                                error={formKolaboratif.errors.target_pengumpulan}
                                tampilkanTerbilang
                            />

                            <DialogFooter>
                                <Button type="button" variant="outline" className="h-9" onClick={() => setDialogKolaboratif(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" className="h-9" disabled={formKolaboratif.processing || formKolaboratif.data.target_pengumpulan <= 0}>
                                    {formKolaboratif.processing ? 'Membuat...' : 'Buat RKAT & Tetapkan Target'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
