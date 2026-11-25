import { PageHeader, StatusBadge } from '@/components/app';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Edit3, FileDown, FileSpreadsheet, Lock, Unlock } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'PKP', href: '/pkp' },
    { title: 'RKAT vs Realisasi', href: '/pkp/realisasi' },
];

interface BarisRealisasi {
    id: number;
    kode: string;
    nama: string;
    lampiran: string;
    grup: string;
    tingkat: number;
    induk_id: number | null;
    tipe_nilai: string;
    agregasi: string;
    sumber: string;
    nilai_otomatis: number;
    nilai_manual: number;
    boleh_manual: boolean;
    target_rkat: number;
    realisasi: number;
    selisih: number;
    persen: number | null;
    persen_label: string;
}

interface RingkasanGrup {
    target_rkat: number;
    realisasi: number;
    selisih: number;
    persen: number | null;
    persen_label: string;
}

interface Props {
    tahun_terpilih: number;
    bulan_terpilih: number | null;
    bulan_input_default: number;
    lampiran_terpilih: string | null;
    daftar_tahun: { id: number; tahun: number; status: string }[];
    status_kunci_bulan: boolean;
    manual_bulan_ini: Record<number, { nilai: number; catatan: string | null; terkunci: boolean }>;
    hasil: {
        tahun: number;
        bulan: number | null;
        status_tahun: string;
        versi_rkat: string | null;
        total_rkat: number;
        total_realisasi: number;
        persen_total: number | null;
        persen_total_label: string;
        ringkasan_lampiran: Record<string, RingkasanGrup>;
        baris: BarisRealisasi[];
    };
    akses: {
        boleh_tulis: boolean;
        boleh_kunci: boolean;
    };
}

const NAMA_BULAN = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const DAFTAR_LAMPIRAN = [
    { kode: 'semua', label: 'Semua Lampiran (L2–L7)' },
    { kode: 'L2', label: 'L2 — Rencana Penerimaan' },
    { kode: 'L2/LN', label: 'L2/LN — Luar Neraca' },
    { kode: 'L3', label: 'L3 — Rencana Penyaluran' },
    { kode: 'L4', label: 'L4 — Program Penyaluran' },
    { kode: 'L5', label: 'L5 — Muzakki & Mustahik' },
    { kode: 'L6', label: 'L6 — Operasional Amil' },
    { kode: 'L7', label: 'L7 — Pengembangan SDM & Umum' },
];

function formatNilaiPos(nilai: number, tipeNilai: string): string {
    if (tipeNilai === 'rupiah') {
        return formatRupiah(nilai);
    }
    return `${formatAngka(nilai)} ${tipeNilai}`;
}

export default function RealisasiIndex({
    tahun_terpilih,
    bulan_terpilih,
    bulan_input_default,
    lampiran_terpilih,
    daftar_tahun = [],
    status_kunci_bulan = false,
    manual_bulan_ini = {},
    hasil,
    akses = { boleh_tulis: false, boleh_kunci: false },
}: Props) {
    const [posDipilih, setPosDipilih] = React.useState<BarisRealisasi | null>(null);
    const [dialogKunciTerbuka, setDialogKunciTerbuka] = React.useState(false);
    const [alasanBukaKunci, setAlasanBukaKunci] = React.useState('');

    const formManual = useForm({
        tahun: tahun_terpilih,
        bulan: bulan_input_default,
        pos_anggaran_id: 0,
        nilai: '0',
        catatan: '',
    });

    const terapkanFilter = (tahun: number, bulan: number | null, lampiran: string | null) => {
        router.get(
            route('pkp.realisasi'),
            {
                tahun,
                bulan: bulan ?? undefined,
                lampiran: lampiran || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const bukaInputManual = (baris: BarisRealisasi) => {
        const eksisting = manual_bulan_ini[baris.id];
        formManual.clearErrors();
        formManual.setData({
            tahun: tahun_terpilih,
            bulan: bulan_terpilih ?? bulan_input_default,
            pos_anggaran_id: baris.id,
            nilai: String(eksisting?.nilai ?? 0),
            catatan: eksisting?.catatan ?? '',
        });
        setPosDipilih(baris);
    };

    const kirimManual = (e: React.FormEvent) => {
        e.preventDefault();
        formManual.transform((d) => ({
            ...d,
            nilai: Number.parseInt(String(d.nilai), 10) || 0,
        }));
        formManual.post(route('pkp.realisasi.manual'), {
            preserveScroll: true,
            onSuccess: () => setPosDipilih(null),
        });
    };

    const kirimKunciPeriode = () => {
        if (!bulan_terpilih) return;
        router.post(
            route('pkp.realisasi.kunci'),
            {
                tahun: tahun_terpilih,
                bulan: bulan_terpilih,
                terkunci: !status_kunci_bulan,
                alasan: status_kunci_bulan ? alasanBukaKunci : 'Penguncian periode realisasi bulanan',
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setDialogKunciTerbuka(false);
                    setAlasanBukaKunci('');
                },
            },
        );
    };

    const ringkasanAktif: RingkasanGrup =
        lampiran_terpilih && hasil.ringkasan_lampiran[lampiran_terpilih]
            ? hasil.ringkasan_lampiran[lampiran_terpilih]
            : {
                  target_rkat: hasil.total_rkat,
                  realisasi: hasil.total_realisasi,
                  selisih: hasil.total_rkat - hasil.total_realisasi,
                  persen: hasil.persen_total,
                  persen_label: hasil.persen_total_label,
              };

    const queryEkspor = new URLSearchParams({
        tahun: String(tahun_terpilih),
        ...(bulan_terpilih ? { bulan: String(bulan_terpilih) } : {}),
        ...(lampiran_terpilih ? { lampiran: lampiran_terpilih } : {}),
    }).toString();

    const tahunTutup = hasil.status_tahun === 'tutup';
    const nilaiNumerikForm = Number.parseInt(String(formManual.data.nilai), 10);
    const nilaiNegatif = !Number.isNaN(nilaiNumerikForm) && nilaiNumerikForm < 0;
    const progressPersen = ringkasanAktif.persen !== null ? Math.min(100, Math.max(0, ringkasanAktif.persen)) : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`RKAT vs Realisasi ${tahun_terpilih}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={`RKAT vs Realisasi (${tahun_terpilih})`}
                    description={`Acuan RKAT: ${hasil.versi_rkat ?? 'Belum ada versi disahkan'} · Nilai otomatis berasal dari view v_realisasi_rkat.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            {akses.boleh_kunci && bulan_terpilih !== null && !tahunTutup && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-9 gap-1.5"
                                    onClick={() => setDialogKunciTerbuka(true)}
                                >
                                    {status_kunci_bulan ? (
                                        <>
                                            <Unlock className="h-4 w-4" aria-hidden="true" />
                                            <span>Buka Kunci Bulan {NAMA_BULAN[bulan_terpilih - 1]}</span>
                                        </>
                                    ) : (
                                        <>
                                            <Lock className="h-4 w-4" aria-hidden="true" />
                                            <span>Kunci Bulan {NAMA_BULAN[bulan_terpilih - 1]}</span>
                                        </>
                                    )}
                                </Button>
                            )}
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <a href={`${route('pkp.realisasi.pdf')}?${queryEkspor}`} target="_blank" rel="noopener noreferrer">
                                    <FileDown className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor PDF</span>
                                </a>
                            </Button>
                            <Button asChild className="h-9 gap-1.5">
                                <a href={`${route('pkp.realisasi.excel')}?${queryEkspor}`}>
                                    <FileSpreadsheet className="h-4 w-4" aria-hidden="true" />
                                    <span>Ekspor Excel</span>
                                </a>
                            </Button>
                        </div>
                    }
                />

                {/* Bar Filter */}
                <div className="bg-card border-border grid gap-3 rounded-2xl border p-4 shadow-sm sm:grid-cols-3">
                    <div className="space-y-1">
                        <Label htmlFor="realisasi-tahun" className="text-xs">
                            Tahun Anggaran
                        </Label>
                        <Select
                            value={String(tahun_terpilih)}
                            onValueChange={(v) => terapkanFilter(Number(v), bulan_terpilih, lampiran_terpilih)}
                        >
                            <SelectTrigger id="realisasi-tahun" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {daftar_tahun.map((t) => (
                                    <SelectItem key={t.id} value={String(t.tahun)}>
                                        {t.tahun} ({t.status.toUpperCase()})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="realisasi-bulan" className="text-xs">
                            Periode Kumulatif
                        </Label>
                        <Select
                            value={bulan_terpilih ? String(bulan_terpilih) : 'semua'}
                            onValueChange={(v) => terapkanFilter(tahun_terpilih, v === 'semua' ? null : Number(v), lampiran_terpilih)}
                        >
                            <SelectTrigger id="realisasi-bulan" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Setahun Penuh (Jan–Des)</SelectItem>
                                {NAMA_BULAN.map((nama, idx) => (
                                    <SelectItem key={idx + 1} value={String(idx + 1)}>
                                        Kumulatif s.d. {nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="realisasi-lampiran" className="text-xs">
                            Lampiran RKAT
                        </Label>
                        <Select
                            value={lampiran_terpilih || 'semua'}
                            onValueChange={(v) => terapkanFilter(tahun_terpilih, bulan_terpilih, v === 'semua' ? null : v)}
                        >
                            <SelectTrigger id="realisasi-lampiran" className="h-9">
                                <SelectValue placeholder="Semua Lampiran (L2–L7)" />
                            </SelectTrigger>
                            <SelectContent>
                                {DAFTAR_LAMPIRAN.map((l) => (
                                    <SelectItem key={l.kode} value={l.kode}>
                                        {l.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* 4 Kartu KPI */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <p className="text-muted-foreground text-xs font-medium">
                            Target RKAT ({lampiran_terpilih || 'L2 Dalam Neraca'})
                        </p>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">
                            {lampiran_terpilih === 'L5' ? formatAngka(ringkasanAktif.target_rkat) : formatRupiah(ringkasanAktif.target_rkat, true)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Status tahun: {hasil.status_tahun.toUpperCase()}</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <p className="text-muted-foreground text-xs font-medium">Realisasi Aktual</p>
                        <p className="text-success mt-1.5 text-2xl font-semibold tabular-nums">
                            {lampiran_terpilih === 'L5' ? formatAngka(ringkasanAktif.realisasi) : formatRupiah(ringkasanAktif.realisasi, true)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {bulan_terpilih ? `Kumulatif Jan–${NAMA_BULAN[bulan_terpilih - 1]}` : 'Akumulasi satu tahun'}
                        </p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <p className="text-muted-foreground text-xs font-medium">Selisih Target</p>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">
                            {lampiran_terpilih === 'L5' ? formatAngka(ringkasanAktif.selisih) : formatRupiah(ringkasanAktif.selisih, true)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Target dikurangi realisasi</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-muted-foreground text-xs font-medium">Capaian Serapan</p>
                            {bulan_terpilih !== null && (
                                <StatusBadge
                                    status={status_kunci_bulan ? 'terkunci' : 'terbuka'}
                                    variant={status_kunci_bulan ? 'warning' : 'aktif'}
                                    label={status_kunci_bulan ? 'Terkunci' : 'Terbuka'}
                                />
                            )}
                        </div>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">{ringkasanAktif.persen_label}</p>
                        <div className="bg-surface-subtle mt-2 h-2 w-full overflow-hidden rounded-full">
                            <div className="bg-primary h-full rounded-full transition-all" style={{ width: `${progressPersen}%` }} />
                        </div>
                    </div>
                </div>

                {/* Tabel Pohon RKAT vs Realisasi */}
                <div className="bg-card border-border overflow-hidden rounded-2xl border shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border bg-surface-subtle text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="px-4 py-3">Kode</th>
                                    <th className="px-4 py-3">Uraian Pos Anggaran</th>
                                    <th className="px-4 py-3">Sumber</th>
                                    <th className="px-4 py-3 text-right">Target RKAT</th>
                                    <th className="px-4 py-3 text-right">Realisasi</th>
                                    <th className="px-4 py-3 text-right">Selisih</th>
                                    <th className="px-4 py-3 text-right">Capaian</th>
                                    {akses.boleh_tulis && <th className="px-4 py-3 text-right">Aksi</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-border/70 divide-y">
                                {hasil.baris.map((b) => {
                                    const isInduk = b.agregasi === 'jumlah_anak';
                                    return (
                                        <tr
                                            key={b.id}
                                            className={cn(
                                                'transition-colors',
                                                isInduk ? 'bg-surface-subtle/80 font-semibold' : 'hover:bg-surface-subtle/50',
                                            )}
                                        >
                                            <td className="text-muted-foreground px-4 py-2.5 font-mono text-xs whitespace-nowrap">{b.kode}</td>
                                            <td className="px-4 py-2.5">
                                                <div style={{ paddingLeft: `${Math.max(0, b.tingkat - 1) * 16}px` }}>{b.nama}</div>
                                            </td>
                                            <td className="px-4 py-2.5 text-xs">
                                                {isInduk ? (
                                                    <span className="text-muted-foreground">Rollup</span>
                                                ) : (
                                                    <span className="bg-surface-subtle text-ink-secondary inline-flex rounded-md border px-2 py-0.5 text-xs font-medium">
                                                        {b.sumber === 'manual' ? 'Manual' : `Otomatis (${b.sumber})`}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums whitespace-nowrap">
                                                {formatNilaiPos(b.target_rkat, b.tipe_nilai)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums whitespace-nowrap">
                                                {formatNilaiPos(b.realisasi, b.tipe_nilai)}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-2.5 text-right tabular-nums whitespace-nowrap">
                                                {formatNilaiPos(b.selisih, b.tipe_nilai)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums whitespace-nowrap">
                                                <StatusBadge
                                                    status={b.persen_label}
                                                    variant={
                                                        b.persen === null
                                                            ? 'draft'
                                                            : b.persen >= 80
                                                              ? 'success'
                                                              : b.persen >= 50
                                                                ? 'warning'
                                                                : 'info'
                                                    }
                                                    label={b.persen_label}
                                                />
                                            </td>
                                            {akses.boleh_tulis && (
                                                <td className="px-4 py-2.5 text-right">
                                                    {b.boleh_manual && !tahunTutup && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8 gap-1 px-2.5 text-xs"
                                                            onClick={() => bukaInputManual(b)}
                                                        >
                                                            <Edit3 className="h-3.5 w-3.5" aria-hidden="true" />
                                                            <span>Input Manual</span>
                                                        </Button>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    );
                                })}
                                {hasil.baris.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="text-muted-foreground py-8 text-center text-xs">
                                            Belum ada pos anggaran aktif.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Sheet Input Realisasi Manual */}
            <Sheet open={Boolean(posDipilih)} onOpenChange={(open) => !open && setPosDipilih(null)}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[480px]">
                    <SheetHeader>
                        <SheetTitle>Input Realisasi Manual</SheetTitle>
                        <SheetDescription>
                            {posDipilih ? `${posDipilih.kode} — ${posDipilih.nama}` : ''}
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimManual} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="manual-bulan">Bulan Realisasi ({tahun_terpilih})</Label>
                            <Select
                                value={String(formManual.data.bulan)}
                                onValueChange={(v) => formManual.setData('bulan', Number(v))}
                            >
                                <SelectTrigger id="manual-bulan" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {NAMA_BULAN.map((nama, idx) => (
                                        <SelectItem key={idx + 1} value={String(idx + 1)}>
                                            {nama} {tahun_terpilih}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="manual-nilai">
                                Nilai ({posDipilih?.tipe_nilai ?? 'rupiah'})
                            </Label>
                            <Input
                                id="manual-nilai"
                                type="number"
                                step={1}
                                required
                                value={formManual.data.nilai}
                                onChange={(e) => formManual.setData('nilai', e.target.value)}
                                className="h-9 text-right tabular-nums"
                            />
                            <p className="text-muted-foreground text-xs">
                                Pratinjau:{' '}
                                <span className="text-foreground font-medium">
                                    {formatNilaiPos(Number.isNaN(nilaiNumerikForm) ? 0 : nilaiNumerikForm, posDipilih?.tipe_nilai ?? 'rupiah')}
                                </span>
                                {' · '}Gunakan nilai negatif hanya untuk koreksi (BR-05).
                            </p>
                            {formManual.errors.nilai && <p className="text-destructive text-xs">{formManual.errors.nilai}</p>}
                            {formManual.errors.tahun && <p className="text-destructive text-xs">{formManual.errors.tahun}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="manual-catatan">
                                Catatan {nilaiNegatif && <span className="text-destructive font-normal">(wajib untuk nilai negatif)</span>}
                            </Label>
                            <Input
                                id="manual-catatan"
                                required={nilaiNegatif}
                                value={formManual.data.catatan}
                                onChange={(e) => formManual.setData('catatan', e.target.value)}
                                placeholder="Sumber data atau alasan koreksi..."
                                className="h-9"
                            />
                            {formManual.errors.catatan && <p className="text-destructive text-xs">{formManual.errors.catatan}</p>}
                        </div>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" className="h-9" onClick={() => setPosDipilih(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formManual.processing} className="h-9">
                                {formManual.processing ? 'Menyimpan...' : 'Simpan Realisasi'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Konfirmasi Kunci / Buka Kunci Bulan */}
            <AlertDialog open={dialogKunciTerbuka} onOpenChange={setDialogKunciTerbuka}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {status_kunci_bulan
                                ? `Buka Kunci Realisasi Bulan ${bulan_terpilih ? NAMA_BULAN[bulan_terpilih - 1] : ''} ${tahun_terpilih}?`
                                : `Kunci Realisasi Bulan ${bulan_terpilih ? NAMA_BULAN[bulan_terpilih - 1] : ''} ${tahun_terpilih}?`}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {status_kunci_bulan
                                ? 'Membuka kunci mengizinkan perubahan kembali pada nilai realisasi manual bulan ini dan wajib menyertakan alasan untuk audit log.'
                                : 'Mengunci periode mencegah perubahan nilai realisasi manual pada bulan tersebut.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    {status_kunci_bulan && (
                        <div className="space-y-1.5 py-2">
                            <Label htmlFor="alasan-buka-kunci" className="text-xs">
                                Alasan Buka Kunci (wajib)
                            </Label>
                            <Input
                                id="alasan-buka-kunci"
                                value={alasanBukaKunci}
                                onChange={(e) => setAlasanBukaKunci(e.target.value)}
                                placeholder="Contoh: Koreksi data historis sesuai bukti..."
                                className="h-9"
                            />
                        </div>
                    )}

                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            disabled={status_kunci_bulan && alasanBukaKunci.trim() === ''}
                            onClick={kirimKunciPeriode}
                        >
                            {status_kunci_bulan ? 'Buka Kunci' : 'Kunci Periode'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
