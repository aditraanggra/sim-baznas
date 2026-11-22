import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Ban, CheckCircle2, FileDown, Plus, Printer, QrCode } from 'lucide-react';
import * as React from 'react';

interface Props {
    penerimaan: {
        id: number;
        nomor: string | null;
        tanggal: string;
        status: string;
        catatan: string | null;
        petugas: string | null;
        muzakki: { id: number; kode: string; nama: string; nik_masked: string | null; telepon: string | null; alamat: string | null } | null;
        upz: { id: number; kode: string; nama: string } | null;
        metode: string | null;
        rekening: string | null;
        berita_acara: { id: number; nomor: string; status: string } | null;
        items: { id: number; jenis_dana: string | null; sumber_dana: string | null; jumlah: number; keterangan: string | null }[];
        total: number;
        terbilang: string;
        kwitansi: {
            id: number;
            nomor: string;
            token_verifikasi: string;
            jumlah_cetak: number;
            riwayat_cetak: { cetak_ke: number; pencetak: string; waktu: string }[];
        } | null;
        pembatalan: {
            id: number;
            alasan: string;
            status: string;
            peminta: string | null;
            penyetuju: string | null;
            catatan_penyetuju: string | null;
            waktu_diminta: string | null;
            waktu_disetujui: string | null;
        } | null;
    };
    bisa_ajukan_batal: boolean;
    bisa_setujui_batal: boolean;
}

export default function PenerimaanShow({ penerimaan, bisa_ajukan_batal, bisa_setujui_batal }: Props) {
    const [dialogBatal, setDialogBatal] = React.useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Daftar Penerimaan', href: '/pengumpulan/penerimaan' },
        { title: penerimaan.kwitansi?.nomor ?? penerimaan.nomor ?? `Transaksi #${penerimaan.id}`, href: `/pengumpulan/penerimaan/${penerimaan.id}` },
    ];

    const formBatal = useForm({ alasan: '' });
    const formPutusBatal = useForm({ keputusan: 'disetujui', catatan_penyetuju: '' });

    const kirimAjuanBatal = (e: React.FormEvent) => {
        e.preventDefault();
        formBatal.post(route('pengumpulan.penerimaan.batal-ajukan', penerimaan.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDialogBatal(false);
                formBatal.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Penerimaan ${penerimaan.kwitansi?.nomor ?? penerimaan.nomor ?? ''}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={penerimaan.kwitansi?.nomor ?? penerimaan.nomor ?? `Draft #${penerimaan.id}`}
                    description={`Tanggal transaksi: ${penerimaan.tanggal} · Petugas: ${penerimaan.petugas ?? '-'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <Link href={route('pengumpulan.penerimaan.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Input Baru</span>
                                </Link>
                            </Button>

                            {penerimaan.kwitansi && (
                                <>
                                    <Button asChild variant="outline" className="h-9 gap-1.5">
                                        <a href={route('pengumpulan.penerimaan.kwitansi', penerimaan.id)} target="_blank" rel="noopener noreferrer">
                                            <Printer className="h-4 w-4" aria-hidden="true" />
                                            <span>Cetak Kwitansi</span>
                                        </a>
                                    </Button>
                                    <Button asChild className="h-9 gap-1.5">
                                        <a href={`${route('pengumpulan.penerimaan.kwitansi', penerimaan.id)}?unduh=1`}>
                                            <FileDown className="h-4 w-4" aria-hidden="true" />
                                            <span>Unduh PDF</span>
                                        </a>
                                    </Button>
                                </>
                            )}
                        </>
                    }
                />

                {penerimaan.status === 'batal' && (
                    <div className="bg-destructive-soft border-destructive/25 text-destructive rounded-xl border px-4 py-3 text-sm font-medium">
                        Transaksi ini telah DIBATALKAN melalui persetujuan maker-checker dan tidak dihitung dalam laporan maupun saldo dana.
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <section className="bg-card border-border rounded-xl border p-5">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                                <h2 className="text-foreground text-base font-semibold">Informasi Setoran</h2>
                                <StatusBadge status={penerimaan.status} />
                            </div>

                            <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground text-xs">Muzakki</dt>
                                    <dd className="text-foreground mt-0.5 font-medium">
                                        {penerimaan.muzakki ? (
                                            <Link href={route('pengumpulan.muzakki.show', penerimaan.muzakki.id)} className="text-primary hover:underline">
                                                {penerimaan.muzakki.nama} ({penerimaan.muzakki.kode})
                                            </Link>
                                        ) : (
                                            '-'
                                        )}
                                    </dd>
                                    <dd className="text-muted-foreground text-xs">{penerimaan.muzakki?.nik_masked ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Metode & Rekening</dt>
                                    <dd className="text-foreground mt-0.5 font-medium">{penerimaan.metode ?? '-'}</dd>
                                    {penerimaan.rekening && <dd className="text-muted-foreground text-xs">{penerimaan.rekening}</dd>}
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">UPZ Perantara</dt>
                                    <dd className="text-foreground mt-0.5 font-medium">
                                        {penerimaan.upz ? `${penerimaan.upz.nama} (${penerimaan.upz.kode})` : 'Setoran Langsung'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Berita Acara Harian</dt>
                                    <dd className="text-foreground mt-0.5 font-medium">
                                        {penerimaan.berita_acara ? `${penerimaan.berita_acara.nomor} (${penerimaan.berita_acara.status})` : 'Belum masuk BA'}
                                    </dd>
                                </div>
                            </dl>

                            <div className="mt-5 overflow-x-auto rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead className="bg-surface-subtle text-left text-xs uppercase">
                                        <tr>
                                            <th className="px-3 py-2">Jenis Dana</th>
                                            <th className="px-3 py-2">Sumber Dana</th>
                                            <th className="px-3 py-2">Keterangan</th>
                                            <th className="px-3 py-2 text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {penerimaan.items.map((item) => (
                                            <tr key={item.id} className="border-t">
                                                <td className="px-3 py-2">{item.jenis_dana ?? '-'}</td>
                                                <td className="px-3 py-2 font-medium">{item.sumber_dana ?? '-'}</td>
                                                <td className="px-3 py-2">{item.keterangan ?? '-'}</td>
                                                <td className="px-3 py-2 text-right font-medium tabular-nums">{formatRupiah(item.jumlah)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-surface-subtle border-t">
                                        <tr>
                                            <td colSpan={3} className="px-3 py-2.5 text-right font-semibold">
                                                Total
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{formatRupiah(penerimaan.total)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <p className="bg-surface-subtle border-border text-foreground mt-3 rounded-lg border px-3 py-2 text-xs italic">
                                Terbilang: {penerimaan.terbilang}
                            </p>
                        </section>

                        {penerimaan.pembatalan && (
                            <section className="bg-card border-border space-y-3 rounded-xl border p-5">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-foreground text-base font-semibold">Riwayat Pembatalan Transaksi</h2>
                                    <StatusBadge
                                        status={penerimaan.pembatalan.status}
                                        variant={
                                            penerimaan.pembatalan.status === 'disetujui'
                                                ? 'dibatalkan'
                                                : penerimaan.pembatalan.status === 'ditolak'
                                                  ? 'draft'
                                                  : 'warning'
                                        }
                                        label={penerimaan.pembatalan.status}
                                    />
                                </div>
                                <p className="text-foreground text-sm">Alasan: {penerimaan.pembatalan.alasan}</p>
                                <p className="text-muted-foreground text-xs">
                                    Diminta oleh {penerimaan.pembatalan.peminta} pada {penerimaan.pembatalan.waktu_diminta}
                                </p>

                                {bisa_setujui_batal && (
                                    <div className="space-y-3 border-t pt-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="catatan-batal-acc">Catatan Penyetuju (opsional)</Label>
                                            <input
                                                id="catatan-batal-acc"
                                                value={formPutusBatal.data.catatan_penyetuju}
                                                onChange={(e) => formPutusBatal.setData('catatan_penyetuju', e.target.value)}
                                                className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                                            />
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                disabled={formPutusBatal.processing}
                                                onClick={() => {
                                                    formPutusBatal.transform((d) => ({ ...d, keputusan: 'disetujui' }));
                                                    formPutusBatal.post(route('pengumpulan.penerimaan.batal-setujui', penerimaan.pembatalan!.id));
                                                }}
                                                className="h-9 gap-1.5"
                                            >
                                                <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                                <span>Setujui Pembatalan</span>
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={formPutusBatal.processing}
                                                onClick={() => {
                                                    formPutusBatal.transform((d) => ({ ...d, keputusan: 'ditolak' }));
                                                    formPutusBatal.post(route('pengumpulan.penerimaan.batal-setujui', penerimaan.pembatalan!.id));
                                                }}
                                                className="h-9"
                                            >
                                                Tolak Pengajuan
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </section>
                        )}
                    </div>

                    <aside className="space-y-4">
                        {penerimaan.kwitansi && (
                            <section className="bg-card border-border space-y-3 rounded-xl border p-5">
                                <h2 className="text-foreground text-base font-semibold">Kwitansi & Verifikasi QR</h2>
                                <p className="text-muted-foreground text-xs">
                                    Nomor: <span className="text-foreground font-medium">{penerimaan.kwitansi.nomor}</span>
                                </p>
                                <p className="text-muted-foreground text-xs">Total cetak tercatat: {penerimaan.kwitansi.jumlah_cetak} kali</p>

                                <Button asChild variant="outline" size="sm" className="h-9 w-full gap-1.5">
                                    <Link href={route('publik.verifikasi-kwitansi', penerimaan.kwitansi.token_verifikasi)}>
                                        <QrCode className="h-4 w-4" aria-hidden="true" />
                                        <span>Buka Halaman Verifikasi Publik</span>
                                    </Link>
                                </Button>

                                {penerimaan.kwitansi.riwayat_cetak.length > 0 && (
                                    <div className="space-y-1.5 border-t pt-3">
                                        <p className="text-foreground text-xs font-semibold">Log Cetak Kwitansi</p>
                                        <ul className="text-muted-foreground space-y-1 text-xs">
                                            {penerimaan.kwitansi.riwayat_cetak.map((l) => (
                                                <li key={l.cetak_ke}>
                                                    Ke-{l.cetak_ke} oleh {l.pencetak} ({l.waktu})
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </section>
                        )}

                        {bisa_ajukan_batal && (
                            <section className="bg-card border-border space-y-3 rounded-xl border p-5">
                                <h2 className="text-foreground text-sm font-semibold">Koreksi / Pembatalan Transaksi</h2>
                                <p className="text-muted-foreground text-xs">
                                    Transaksi final tidak pernah dihapus. Pembatalan memerlukan alasan dan persetujuan pejabat berwenang (maker-checker).
                                </p>
                                <Button type="button" variant="destructive" onClick={() => setDialogBatal(true)} className="h-9 w-full gap-1.5">
                                    <Ban className="h-4 w-4" aria-hidden="true" />
                                    <span>Ajukan Pembatalan</span>
                                </Button>
                            </section>
                        )}
                    </aside>
                </div>
            </div>

            <Dialog open={dialogBatal} onOpenChange={setDialogBatal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan Pembatalan Penerimaan</DialogTitle>
                        <DialogDescription>Masukkan alasan pembatalan secara jelas (minimal 10 karakter).</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={kirimAjuanBatal} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="alasan-batal">Alasan Pembatalan (wajib)</Label>
                            <textarea
                                id="alasan-batal"
                                rows={3}
                                required
                                minLength={10}
                                value={formBatal.data.alasan}
                                onChange={(e) => formBatal.setData('alasan', e.target.value)}
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />
                            {formBatal.errors.alasan && <p className="text-destructive text-xs">{formBatal.errors.alasan}</p>}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogBatal(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" variant="destructive" disabled={formBatal.processing} className="h-9">
                                Kirim Pengajuan Pembatalan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
