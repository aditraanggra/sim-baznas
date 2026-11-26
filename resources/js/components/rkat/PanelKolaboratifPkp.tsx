import { InputRupiah, StatusBadge, type StatusVariant } from '@/components/app';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatAngka, formatRupiah } from '@/lib/format';
import { Link, router } from '@inertiajs/react';
import { CheckCircle2, ChevronDown, ChevronRight, RotateCcw } from 'lucide-react';
import * as React from 'react';
import {
    type AturanAmilItem,
    type KelompokKegiatanGabungan,
    type KodeBidangRkat,
    type RingkasanAlokasi,
    type TahapBidangDetail,
} from './types';

const STATUS_TAHAP: Record<string, { label: string; varian: StatusVariant }> = {
    menunggu: { label: 'Menunggu Pengumpulan', varian: 'warning' },
    draft: { label: 'Draft Bidang', varian: 'draft' },
    diajukan: { label: 'Menunggu Verifikasi', varian: 'diproses' },
    terverifikasi: { label: 'Terverifikasi', varian: 'final' },
};

const RUTE_BIDANG: Record<KodeBidangRkat, string> = {
    pengumpulan: 'pengumpulan.rkat.index',
    distribusi: 'distribusi.rkat.index',
    umum: 'umum.rkat.index',
};

export function PanelVerifikasiBidang({
    versiId,
    versiTerkunci,
    tahapBidang,
    ringkasan,
    dapatVerifikasi,
}: {
    versiId: number;
    versiTerkunci: boolean;
    tahapBidang: TahapBidangDetail[];
    ringkasan: RingkasanAlokasi;
    dapatVerifikasi: boolean;
}) {
    const [bidangKembali, setBidangKembali] = React.useState<TahapBidangDetail | null>(null);
    const [alasan, setAlasan] = React.useState('');

    const verifikasi = (kode: KodeBidangRkat) => {
        router.post(
            route('pkp.rencana.tahap.verifikasi', { versi: versiId, bidang: kode }),
            {},
            { preserveScroll: true },
        );
    };

    const kirimKembalikan = (e: React.FormEvent) => {
        e.preventDefault();
        if (!bidangKembali) return;
        router.post(
            route('pkp.rencana.tahap.kembalikan', { versi: versiId, bidang: bidangKembali.bidang_kode }),
            { alasan },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setBidangKembali(null);
                    setAlasan('');
                },
            },
        );
    };

    const infoRingkas = (kode: KodeBidangRkat) => {
        if (kode === 'pengumpulan') {
            return `Terencana ${formatRupiah(ringkasan.pengumpulan.total_neraca)} / Target ${formatRupiah(ringkasan.pengumpulan.target)}`;
        }
        if (kode === 'distribusi') {
            return `Terencana ${formatRupiah(ringkasan.distribusi.total_terpakai)} / Pagu ${formatRupiah(ringkasan.distribusi.total_pagu)}`;
        }
        return `Terencana ${formatRupiah(ringkasan.umum.terpakai)} / Pagu ${formatRupiah(ringkasan.umum.total_pagu)}`;
    };

    return (
        <section className="bg-card border-border rounded-xl border p-5 shadow-xs">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 className="text-foreground text-base font-semibold">Progres Penyusunan &amp; Verifikasi 3 Bidang</h2>
                    <p className="text-muted-foreground text-xs">
                        Verifikasi oleh Kepala Bagian PKP mengunci bidang tersebut dan membuka alokasi pagu berikutnya.
                    </p>
                </div>
                <span className="text-muted-foreground text-xs font-medium">
                    {tahapBidang.filter((t) => t.status === 'terverifikasi').length} / 3 Bidang Terverifikasi
                </span>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                {tahapBidang.map((t) => {
                    const meta = STATUS_TAHAP[t.status] ?? { label: t.status, varian: 'draft' as StatusVariant };
                    const bolehVerifikasi = dapatVerifikasi && !versiTerkunci && t.status === 'diajukan';
                    const bolehKembalikan = dapatVerifikasi && !versiTerkunci && (t.status === 'diajukan' || t.status === 'terverifikasi');

                    return (
                        <div key={t.bidang_kode} className="bg-surface-subtle border-border flex flex-col justify-between gap-3 rounded-lg border p-4">
                            <div className="space-y-1.5">
                                <div className="flex items-start justify-between gap-2">
                                    <h3 className="text-foreground text-sm font-semibold">{t.label}</h3>
                                    <StatusBadge status={t.status} variant={meta.varian} label={meta.label} />
                                </div>
                                <p className="text-muted-foreground text-xs tabular-nums">{infoRingkas(t.bidang_kode)}</p>
                                {t.catatan_verifikasi && <p className="text-warning text-xs">Catatan: {t.catatan_verifikasi}</p>}
                            </div>

                            <div className="flex flex-wrap items-center gap-2 pt-1">
                                <Link className="text-primary text-xs font-medium hover:underline" href={route(RUTE_BIDANG[t.bidang_kode], { versi: versiId })}>
                                    Buka Lembar Bidang
                                </Link>

                                <div className="ml-auto flex flex-wrap gap-1.5">
                                    {bolehVerifikasi && (
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button type="button" size="sm" className="h-8 text-xs">
                                                    <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                                                    Verifikasi
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Verifikasi Tahap {t.label}?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        {t.bidang_kode === 'pengumpulan'
                                                            ? 'Verifikasi Pengumpulan akan menghitung Hak Amil 22 baris dan membuka pagu penyaluran & operasional bagi Pendis/Pendag dan SAU.'
                                                            : `Rincian kegiatan ${t.label} akan ditandai terverifikasi untuk konsolidasi akhir PKP.`}
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                                    <AlertDialogAction onClick={() => verifikasi(t.bidang_kode)}>Ya, Verifikasi</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    )}

                                    {bolehKembalikan && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="h-8 text-xs"
                                            onClick={() => {
                                                setBidangKembali(t);
                                                setAlasan('');
                                            }}
                                        >
                                            <RotateCcw className="h-3.5 w-3.5" aria-hidden="true" />
                                            Kembalikan
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            <Dialog open={Boolean(bidangKembali)} onOpenChange={(o) => !o && setBidangKembali(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Kembalikan Tahap {bidangKembali?.label}</DialogTitle>
                        <DialogDescription>
                            {bidangKembali?.bidang_kode === 'pengumpulan'
                                ? 'Mengembalikan Pengumpulan akan mereset status Pendis/Pendag & SAU menjadi Menunggu tanpa menghapus rincian kegiatan mereka.'
                                : 'Tahap bidang akan dikembalikan ke Draft agar Kabag terkait dapat merevisi rincian.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={kirimKembalikan} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="alasan-kembali">Alasan Pengembalian (wajib)</Label>
                            <Input
                                id="alasan-kembali"
                                className="h-9"
                                required
                                minLength={3}
                                placeholder="Tuliskan catatan perbaikan untuk bidang..."
                                value={alasan}
                                onChange={(e) => setAlasan(e.target.value)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" className="h-9" onClick={() => setBidangKembali(null)}>
                                Batal
                            </Button>
                            <Button type="submit" variant="destructive" className="h-9" disabled={alasan.trim().length < 3}>
                                Kembalikan ke Draft
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </section>
    );
}

export function PanelTargetDanAturanAmil({
    versiId,
    versiTerkunci,
    targetPengumpulan,
    aturanAmil,
    pengumpulanTerverifikasi,
    dapatTulis,
}: {
    versiId: number;
    versiTerkunci: boolean;
    targetPengumpulan: number | null;
    aturanAmil: AturanAmilItem[];
    pengumpulanTerverifikasi: boolean;
    dapatTulis: boolean;
}) {
    const [target, setTarget] = React.useState<number>(targetPengumpulan ?? 0);
    const [bukaTabel, setBukaTabel] = React.useState(false);
    const [persenEdit, setPersenEdit] = React.useState<Record<number, string>>(() => {
        const awal: Record<number, string> = {};
        aturanAmil.forEach((a) => {
            awal[a.id] = String(a.persen_bp / 100);
        });
        return awal;
    });

    React.useEffect(() => {
        setTarget(targetPengumpulan ?? 0);
    }, [targetPengumpulan]);

    React.useEffect(() => {
        const baru: Record<number, string> = {};
        aturanAmil.forEach((a) => {
            baru[a.id] = String(a.persen_bp / 100);
        });
        setPersenEdit(baru);
    }, [aturanAmil]);

    const bisaEditAturan = dapatTulis && !versiTerkunci && !pengumpulanTerverifikasi;

    const simpanTarget = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            route('pkp.rencana.target', versiId),
            { target_pengumpulan: target },
            { preserveScroll: true },
        );
    };

    const simpanPersenAmil = () => {
        const persen: Record<number, number> = {};
        aturanAmil.forEach((a) => {
            const angka = parseFloat((persenEdit[a.id] ?? '0').replace(',', '.'));
            persen[a.id] = Number.isNaN(angka) ? 0 : Math.round(angka * 100);
        });

        router.post(
            route('pkp.rencana.aturan-amil', versiId),
            { persen },
            { preserveScroll: true },
        );
    };

    const totalPenerimaan = aturanAmil.reduce((s, a) => s + a.penerimaan, 0);
    const totalHakAmil = aturanAmil.reduce((s, a) => s + a.hak_amil, 0);
    const totalPenyaluran = aturanAmil.reduce((s, a) => s + a.alokasi_penyaluran, 0);

    return (
        <section className="bg-card border-border rounded-xl border p-5 shadow-xs">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form onSubmit={simpanTarget} className="flex flex-wrap items-end gap-3">
                    <div className="w-72">
                        <InputRupiah
                            id="pkp-target-rakornas"
                            label="Target Pengumpulan RAKORNAS (L2 Neraca)"
                            value={target}
                            disabled={!bisaEditAturan}
                            onChange={setTarget}
                        />
                    </div>
                    {bisaEditAturan && (
                        <Button type="submit" variant="outline" className="h-9">
                            Simpan Target
                        </Button>
                    )}
                </form>

                <div className="flex flex-wrap items-center gap-3 text-xs">
                    <span className="text-muted-foreground">
                        Total Hak Amil: <strong className="text-foreground tabular-nums">{formatRupiah(totalHakAmil)}</strong>
                    </span>
                    <span className="text-muted-foreground">
                        Total Penyaluran: <strong className="text-foreground tabular-nums">{formatRupiah(totalPenyaluran)}</strong>
                    </span>
                    <Button type="button" variant="outline" className="h-9" onClick={() => setBukaTabel((v) => !v)}>
                        {bukaTabel ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                        Aturan 22 Baris Hak Amil
                    </Button>
                </div>
            </div>

            {bukaTabel && (
                <div className="border-border mt-4 overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-xs">
                            <thead className="bg-surface-subtle text-muted-foreground text-left uppercase">
                                <tr>
                                    <th className="px-3 py-2.5">Pos L2</th>
                                    <th className="px-3 py-2.5">Uraian Sumber Dana</th>
                                    <th className="px-3 py-2.5 text-right">% Amil</th>
                                    <th className="px-3 py-2.5 text-right">Maks %</th>
                                    <th className="px-3 py-2.5 text-right">Rencana Penerimaan</th>
                                    <th className="px-3 py-2.5 text-right">Hak Amil (SAU)</th>
                                    <th className="px-3 py-2.5 text-right">Pagu Penyaluran</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {aturanAmil.map((a) => (
                                    <tr key={a.id} className="hover:bg-surface-subtle/60">
                                        <td className="px-3 py-2 font-mono">{a.kode_pos}</td>
                                        <td className="px-3 py-2 font-medium">{a.uraian}</td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {bisaEditAturan ? (
                                                <input
                                                    aria-label={`Persen ${a.uraian}`}
                                                    className="border-input bg-background h-7 w-16 rounded border px-1.5 text-right tabular-nums"
                                                    value={persenEdit[a.id] ?? String(a.persen_bp / 100)}
                                                    onChange={(e) => setPersenEdit((p) => ({ ...p, [a.id]: e.target.value }))}
                                                />
                                            ) : (
                                                `${(a.persen_bp / 100).toFixed(1).replace('.', ',')}%`
                                            )}
                                        </td>
                                        <td className="text-muted-foreground px-3 py-2 text-right tabular-nums">
                                            {(a.persen_maks_bp / 100).toFixed(1).replace('.', ',')}%
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">{formatRupiah(a.penerimaan)}</td>
                                        <td className="px-3 py-2 text-right tabular-nums">{formatRupiah(a.hak_amil)}</td>
                                        <td className="px-3 py-2 text-right font-medium tabular-nums">{formatRupiah(a.alokasi_penyaluran)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-surface-subtle border-border border-t font-semibold">
                                <tr>
                                    <td colSpan={4} className="px-3 py-2.5 text-right">
                                        Total
                                    </td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{formatRupiah(totalPenerimaan)}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{formatRupiah(totalHakAmil)}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{formatRupiah(totalPenyaluran)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {bisaEditAturan && (
                        <div className="bg-surface-subtle border-border flex items-center justify-between border-t px-4 py-2.5">
                            <span className="text-muted-foreground text-xs">Persentase dapat disesuaikan maksimal sesuai batas per baris.</span>
                            <Button type="button" size="sm" className="h-8" onClick={simpanPersenAmil}>
                                Simpan Persentase Hak Amil
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}

export function TabKonsolidasiKegiatan({ kelompokBidang }: { kelompokBidang: KelompokKegiatanGabungan[] }) {
    const [filterBidang, setFilterBidang] = React.useState<string>('semua');
    const tampil = filterBidang === 'semua' ? kelompokBidang : kelompokBidang.filter((k) => k.bidang_kode === filterBidang);

    return (
        <div className="space-y-4 p-4">
            <div className="flex flex-wrap items-center gap-2">
                {[
                    { kode: 'semua', label: 'Semua Bidang' },
                    ...kelompokBidang.map((k) => ({ kode: k.bidang_kode, label: k.bidang_label })),
                ].map((opt) => (
                    <button
                        key={opt.kode}
                        type="button"
                        onClick={() => setFilterBidang(opt.kode)}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium ${
                            filterBidang === opt.kode ? 'bg-primary text-primary-foreground' : 'bg-surface-subtle text-foreground hover:bg-muted'
                        }`}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>

            {tampil.map((kelompok) => (
                <div key={kelompok.bidang_kode} className="border-border overflow-hidden rounded-lg border">
                    <div className="bg-surface-subtle border-border flex items-center justify-between border-b px-4 py-2.5">
                        <span className="text-foreground text-sm font-semibold">{kelompok.bidang_label}</span>
                        <span className="text-foreground text-sm font-semibold tabular-nums">Subtotal: {formatRupiah(kelompok.subtotal)}</span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-muted-foreground text-left text-[11px] font-semibold uppercase">
                                <tr>
                                    <th className="px-3 py-2">Pos RKAT</th>
                                    <th className="px-3 py-2">Uraian Kegiatan</th>
                                    {kelompok.bidang_kode === 'distribusi' && <th className="px-3 py-2">Program L4</th>}
                                    <th className="px-3 py-2 text-right">Jumlah</th>
                                    <th className="px-3 py-2 text-right">Frek</th>
                                    <th className="px-3 py-2 text-right">Besaran</th>
                                    <th className="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {kelompok.baris.length === 0 ? (
                                    <tr>
                                        <td colSpan={kelompok.bidang_kode === 'distribusi' ? 7 : 6} className="text-muted-foreground px-3 py-4 text-center text-xs">
                                            Belum ada rincian kegiatan pada bidang ini.
                                        </td>
                                    </tr>
                                ) : (
                                    kelompok.baris.map((b) => (
                                        <tr key={b.id}>
                                            <td className="px-3 py-2 text-xs">
                                                <span className="font-mono">{b.pos_kode}</span> · {b.pos_nama}
                                            </td>
                                            <td className="px-3 py-2 font-medium">{b.nama_kegiatan}</td>
                                            {kelompok.bidang_kode === 'distribusi' && (
                                                <td className="px-3 py-2 text-xs">
                                                    {b.program_kode} · {b.program_nama}
                                                </td>
                                            )}
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {formatAngka(b.jumlah)} {b.satuan}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">{b.frekuensi}x</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{formatRupiah(b.besaran)}</td>
                                            <td className="px-3 py-2 text-right font-semibold tabular-nums">{formatRupiah(b.total)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            ))}
        </div>
    );
}
