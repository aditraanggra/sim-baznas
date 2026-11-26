import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { BarComparisonChart, LineTrendChart, StackedBarChart, type ChartDatum } from '@/components/ui/chart';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Building2, Calendar, Coins, FileDown, FileSpreadsheet, FolderTree, HandHeart, Scale } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Perencanaan, Keuangan & Pelaporan', href: '/pkp' }];

interface RingkasanLampiranItem {
    target_rkat: number;
    realisasi: number;
    selisih: number;
    persen: number | null;
    persen_label: string;
}

interface CuplikanPosItem {
    id: number;
    kode: string;
    nama: string;
    lampiran: string;
    target_rkat: number;
    realisasi: number;
    selisih: number;
    persen: number | null;
    persen_label: string;
}

interface SegmenDana {
    kode: string;
    label: string;
    nilai: number;
    warna: string;
}

interface Props {
    tahun_terpilih: number;
    daftar_tahun?: { id: number; tahun: number; status: string }[];
    status_tahun?: string;
    versi_rkat?: string | null;
    ringkasan?: {
        total_rkat: number;
        total_realisasi: number;
        persen_serapan: number | null;
        persen_serapan_label: string;
        total_pos: number;
    };
    ringkasan_lampiran?: Record<string, RingkasanLampiranItem>;
    cuplikan_pos?: CuplikanPosItem[];
    cuplikan_pos_l3?: CuplikanPosItem[];
    kinerja_pengumpulan?: {
        total_penerimaan: number;
        jumlah_transaksi: number;
        muzakki_menyetor: number;
        total_muzakki_aktif: number;
        total_langsung: number;
        total_upz: number;
        upz_menyetor: number;
        total_upz_aktif: number;
        tren_bulanan: ChartDatum[];
        komposisi_dana: SegmenDana[];
    };
    kinerja_distribusi?: {
        total_penyaluran: number;
        total_komitmen: number;
        jumlah_transaksi: number;
        mustahik_terbantu: number;
        total_mustahik_terverifikasi: number;
        total_pendistribusian: number;
        total_pendayagunaan: number;
        permohonan_aktif: number;
        permohonan_selesai: number;
        permohonan_ditolak: number;
        rasio_penyaluran: number | null;
        rasio_penyaluran_label: string;
        penyaluran_per_program: ChartDatum[];
        penyaluran_per_asnaf: ChartDatum[];
    };
}

const DESKRIPSI_LAMPIRAN: { kode: string; nama: string; bidang: string }[] = [
    { kode: 'L2', nama: 'Rencana Penerimaan ZIS', bidang: 'Pengumpulan & UPZ' },
    { kode: 'L2/LN', nama: 'Penerimaan Luar Neraca', bidang: 'Pengumpulan / Keuangan' },
    { kode: 'L3', nama: 'Rencana Penyaluran ZIS', bidang: 'Pendistribusian & Pendayagunaan' },
    { kode: 'L4', nama: 'Program Penyaluran', bidang: 'Pendistribusian & Pendayagunaan' },
    { kode: 'L5', nama: 'Sasaran Muzakki & Mustahik', bidang: 'Pelayanan & Pendataan' },
    { kode: 'L6', nama: 'Biaya Operasional Amil', bidang: 'SAU & Keuangan' },
    { kode: 'L7', nama: 'Pengembangan SDM & Umum', bidang: 'SAU & Umum' },
];

export default function DasborPkp({
    tahun_terpilih,
    daftar_tahun = [],
    status_tahun = 'draft',
    versi_rkat = null,
    ringkasan = {
        total_rkat: 0,
        total_realisasi: 0,
        persen_serapan: null,
        persen_serapan_label: '-',
        total_pos: 0,
    },
    ringkasan_lampiran = {},
    cuplikan_pos = [],
    cuplikan_pos_l3 = [],
    kinerja_pengumpulan = {
        total_penerimaan: 0,
        jumlah_transaksi: 0,
        muzakki_menyetor: 0,
        total_muzakki_aktif: 0,
        total_langsung: 0,
        total_upz: 0,
        upz_menyetor: 0,
        total_upz_aktif: 0,
        tren_bulanan: [],
        komposisi_dana: [],
    },
    kinerja_distribusi = {
        total_penyaluran: 0,
        total_komitmen: 0,
        jumlah_transaksi: 0,
        mustahik_terbantu: 0,
        total_mustahik_terverifikasi: 0,
        total_pendistribusian: 0,
        total_pendayagunaan: 0,
        permohonan_aktif: 0,
        permohonan_selesai: 0,
        permohonan_ditolak: 0,
        rasio_penyaluran: null,
        rasio_penyaluran_label: '-',
        penyaluran_per_program: [],
        penyaluran_per_asnaf: [],
    },
}: Props) {
    const [tabPos, setTabPos] = useState<'L2' | 'L3'>('L2');

    const handleGantiTahun = (tahun: number) => {
        router.get(route('pkp.index'), { tahun }, { preserveState: true });
    };

    const targetL2 = ringkasan_lampiran.L2?.target_rkat ?? ringkasan.total_rkat;
    const realisasiL2 = ringkasan_lampiran.L2?.realisasi ?? ringkasan.total_realisasi;
    const persenL2 = ringkasan_lampiran.L2?.persen_label ?? ringkasan.persen_serapan_label;

    const targetL3 = ringkasan_lampiran.L3?.target_rkat ?? 0;
    const realisasiL3 = ringkasan_lampiran.L3?.realisasi ?? kinerja_distribusi.total_penyaluran;
    const persenL3 = ringkasan_lampiran.L3?.persen_label ?? '-';

    const posAktif = tabPos === 'L2' ? cuplikan_pos : cuplikan_pos_l3;
    const totalSetoranSemua = kinerja_pengumpulan.total_langsung + kinerja_pengumpulan.total_upz;
    const porsiUpzPersen =
        totalSetoranSemua > 0 ? `${((kinerja_pengumpulan.total_upz / totalSetoranSemua) * 100).toFixed(1)}%` : '0,0%';
    const totalSalurBidang = kinerja_distribusi.total_pendistribusian + kinerja_distribusi.total_pendayagunaan;
    const porsiPendagPersen =
        totalSalurBidang > 0 ? `${((kinerja_distribusi.total_pendayagunaan / totalSalurBidang) * 100).toFixed(1)}%` : '0,0%';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasbor PKP" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Dasbor Kinerja Pengelolaan ZIS (PKP)"
                    description={`Rekapitulasi kinerja perencanaan RKAT, pengumpulan, jaringan UPZ, dan pendistribusian Tahun ${tahun_terpilih}.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="border-border bg-card flex h-9 items-center gap-1.5 rounded-lg border px-3 text-sm font-medium">
                                <Calendar className="text-muted-foreground size-4" aria-hidden="true" />
                                <span>Tahun:</span>
                                <select
                                    value={tahun_terpilih}
                                    onChange={(e) => handleGantiTahun(Number(e.target.value))}
                                    aria-label="Pilih Tahun Anggaran"
                                    className="cursor-pointer bg-transparent font-semibold focus:outline-none"
                                >
                                    {daftar_tahun.map((t) => (
                                        <option key={t.id} value={t.tahun}>
                                            {t.tahun} ({t.status.toUpperCase()})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('pkp.laporan-setoran-upz', { tahun: tahun_terpilih })}>
                                    <Building2 className="mr-1.5 size-4" aria-hidden="true" />
                                    <span>Setoran UPZ</span>
                                </Link>
                            </Button>

                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('pkp.rencana')}>
                                    <BookOpen className="mr-1.5 size-4" aria-hidden="true" />
                                    <span>RKAT</span>
                                </Link>
                            </Button>

                            <Button asChild variant="outline" className="h-9">
                                <a href={route('pkp.kinerja.pdf', { tahun: tahun_terpilih })} target="_blank" rel="noopener noreferrer">
                                    <FileDown className="mr-1.5 size-4" aria-hidden="true" />
                                    <span>PDF Kinerja</span>
                                </a>
                            </Button>

                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('pkp.realisasi', { tahun: tahun_terpilih })}>
                                    <FileSpreadsheet className="size-4" aria-hidden="true" />
                                    <span>RKAT vs Realisasi</span>
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {/* 4 KPI Utama Lintas Elemen Pengelolaan ZIS */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Perencanaan (RKAT)</span>
                            <StatusBadge
                                status={status_tahun}
                                variant={status_tahun === 'berjalan' ? 'aktif' : status_tahun === 'tutup' ? 'draft' : 'warning'}
                                label={status_tahun === 'berjalan' ? 'Berjalan' : status_tahun === 'tutup' ? 'Tutup' : 'Draft'}
                            />
                        </div>
                        <div className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">{formatRupiah(targetL2, true)}</div>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center justify-between gap-1 text-xs">
                            <span>Versi: {versi_rkat ?? 'Belum disahkan'}</span>
                            <span className="tabular-nums">{formatAngka(ringkasan.total_pos)} pos</span>
                        </div>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Pengumpulan ZIS</span>
                            <Coins className="text-success size-4" aria-hidden="true" />
                        </div>
                        <div className="text-success mt-1.5 text-2xl font-semibold tabular-nums">
                            {formatRupiah(kinerja_pengumpulan.total_penerimaan, true)}
                        </div>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center justify-between gap-1 text-xs tabular-nums">
                            <span>Capaian L2: {persenL2}</span>
                            <span>{formatAngka(kinerja_pengumpulan.jumlah_transaksi)} trx</span>
                        </div>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Pendistribusian & Pendayagunaan</span>
                            <HandHeart className="text-primary size-4" aria-hidden="true" />
                        </div>
                        <div className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">
                            {formatRupiah(kinerja_distribusi.total_penyaluran, true)}
                        </div>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center justify-between gap-1 text-xs tabular-nums">
                            <span>Capaian L3: {persenL3}</span>
                            <span>{formatAngka(kinerja_distribusi.mustahik_terbantu)} mustahik</span>
                        </div>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Rasio Penyaluran (ACR)</span>
                            <Scale className="text-muted-foreground size-4" aria-hidden="true" />
                        </div>
                        <div className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">
                            {kinerja_distribusi.rasio_penyaluran_label}
                        </div>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center justify-between gap-1 text-xs tabular-nums">
                            <span>UPZ aktif: {formatAngka(kinerja_pengumpulan.upz_menyetor)}/{formatAngka(kinerja_pengumpulan.total_upz_aktif)}</span>
                            <span>Antrean: {formatAngka(kinerja_distribusi.permohonan_aktif)}</span>
                        </div>
                    </div>
                </div>

                {/* Bagian 1: Kinerja Perencanaan — Ringkasan RKAT per Lampiran (L2–L7) */}
                <section className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">1. Kinerja Perencanaan (RKAT L2–L7 vs Realisasi)</h2>
                            <p className="text-muted-foreground text-xs">
                                Perbandingan target versi {versi_rkat ?? 'aktif'} terhadap realisasi transaksi dan input operasional Tahun {tahun_terpilih}.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" size="sm" className="h-8">
                                <Link href={route('pkp.kegiatan.index')}>
                                    <FolderTree className="mr-1.5 size-3.5" aria-hidden="true" />
                                    <span>Tahun & Kegiatan</span>
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="sm" className="h-8">
                                <Link href={route('pkp.realisasi', { tahun: tahun_terpilih })}>Rincian Lengkap</Link>
                            </Button>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border/80 text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="pb-2">Lampiran</th>
                                    <th className="pb-2">Elemen & Bidang</th>
                                    <th className="pb-2 text-right">Target RKAT</th>
                                    <th className="pb-2 text-right">Realisasi</th>
                                    <th className="pb-2 text-right">Selisih</th>
                                    <th className="pb-2 text-right">Capaian</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border/60 divide-y">
                                {DESKRIPSI_LAMPIRAN.map((lamp) => {
                                    const data = ringkasan_lampiran[lamp.kode] ?? {
                                        target_rkat: 0,
                                        realisasi: 0,
                                        selisih: 0,
                                        persen: null,
                                        persen_label: '-',
                                    };
                                    return (
                                        <tr key={lamp.kode} className="hover:bg-surface-subtle transition">
                                            <td className="py-2.5 font-mono text-xs font-medium">{lamp.kode}</td>
                                            <td className="py-2.5">
                                                <Link
                                                    href={route('pkp.realisasi', { tahun: tahun_terpilih, lampiran: lamp.kode })}
                                                    className="text-foreground hover:text-primary font-medium hover:underline"
                                                >
                                                    {lamp.nama}
                                                </Link>
                                                <div className="text-muted-foreground text-xs">{lamp.bidang}</div>
                                            </td>
                                            <td className="py-2.5 text-right tabular-nums">{formatRupiah(data.target_rkat, true)}</td>
                                            <td className="text-success py-2.5 text-right font-medium tabular-nums">
                                                {formatRupiah(data.realisasi, true)}
                                            </td>
                                            <td className="text-muted-foreground py-2.5 text-right tabular-nums">
                                                {formatRupiah(data.selisih, true)}
                                            </td>
                                            <td className="py-2.5 text-right tabular-nums">
                                                <StatusBadge
                                                    status={data.persen_label}
                                                    variant={
                                                        data.persen === null
                                                            ? 'draft'
                                                            : data.persen >= 80
                                                              ? 'success'
                                                              : data.persen >= 50
                                                                ? 'warning'
                                                                : 'info'
                                                    }
                                                    label={data.persen_label}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* Bagian 2: Kinerja Pengumpulan & Jaringan UPZ */}
                <section className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">2. Kinerja Pengumpulan & Jaringan UPZ</h2>
                            <p className="text-muted-foreground text-xs">
                                Realisasi setoran final muzakki langsung, jaringan UPZ, tren bulanan, dan komposisi dana Tahun {tahun_terpilih}.
                            </p>
                        </div>
                        <Button asChild variant="outline" size="sm" className="h-8">
                            <Link href={route('pkp.laporan-setoran-upz', { tahun: tahun_terpilih })}>Laporan Setoran UPZ</Link>
                        </Button>
                    </div>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Penerimaan Langsung BAZNAS</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_pengumpulan.total_langsung, true)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Setoran Melalui UPZ ({porsiUpzPersen})</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_pengumpulan.total_upz, true)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Muzakki Bertransaksi</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatAngka(kinerja_pengumpulan.muzakki_menyetor)} / {formatAngka(kinerja_pengumpulan.total_muzakki_aktif)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Realisasi vs Target L2</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(realisasiL2, true)} ({persenL2})
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 grid gap-5 lg:grid-cols-5">
                        <div className="lg:col-span-3">
                            {kinerja_pengumpulan.total_penerimaan === 0 ? (
                                <EmptyState
                                    title="Belum Ada Transaksi Pengumpulan"
                                    description={`Grafik tren bulanan akan tampil setelah ada penerimaan berstatus final pada tahun ${tahun_terpilih}.`}
                                />
                            ) : (
                                <LineTrendChart
                                    title={`Tren Penerimaan Bulanan (${tahun_terpilih})`}
                                    data={kinerja_pengumpulan.tren_bulanan}
                                    isCurrency
                                    height={240}
                                />
                            )}
                        </div>
                        <div className="flex flex-col justify-between gap-4 lg:col-span-2">
                            <StackedBarChart
                                title="Komposisi per Kelompok Dana"
                                segments={kinerja_pengumpulan.komposisi_dana}
                                isCurrency
                            />
                            <div className="bg-muted/20 border-border rounded-xl border p-3 text-xs">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">UPZ aktif menyetor tahun ini</span>
                                    <span className="text-foreground font-semibold tabular-nums">
                                        {formatAngka(kinerja_pengumpulan.upz_menyetor)} dari {formatAngka(kinerja_pengumpulan.total_upz_aktif)} UPZ
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Bagian 3: Kinerja Pendistribusian & Pendayagunaan */}
                <section className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">3. Kinerja Pendistribusian & Pendayagunaan</h2>
                            <p className="text-muted-foreground text-xs">
                                Realisasi penyaluran dicairkan, porsi pendayagunaan produktif, status permohonan, dan sebaran asnaf serta program.
                            </p>
                        </div>
                        <div className="text-muted-foreground text-xs tabular-nums">
                            Target L3: <span className="text-foreground font-medium">{formatRupiah(targetL3, true)}</span> | Realisasi:{' '}
                            <span className="text-foreground font-medium">{formatRupiah(realisasiL3, true)}</span> ({persenL3})
                        </div>
                    </div>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Sub-Bidang Pendistribusian</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_distribusi.total_pendistribusian, true)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Sub-Bidang Pendayagunaan ({porsiPendagPersen})</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_distribusi.total_pendayagunaan, true)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Mustahik Terbantu</div>
                            <div className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatAngka(kinerja_distribusi.mustahik_terbantu)} / {formatAngka(kinerja_distribusi.total_mustahik_terverifikasi)}
                            </div>
                        </div>
                        <div className="bg-muted/30 border-border rounded-xl border p-3">
                            <div className="text-muted-foreground text-xs">Alur Permohonan ({tahun_terpilih})</div>
                            <div className="text-foreground mt-1 text-sm font-medium tabular-nums">
                                {formatAngka(kinerja_distribusi.permohonan_selesai)} selesai ·{' '}
                                {formatAngka(kinerja_distribusi.permohonan_aktif)} proses ·{' '}
                                {formatAngka(kinerja_distribusi.permohonan_ditolak)} ditolak
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 grid gap-5 lg:grid-cols-2">
                        <div>
                            {kinerja_distribusi.penyaluran_per_program.length === 0 ? (
                                <EmptyState
                                    title="Belum Ada Penyaluran per Program"
                                    description={`Data grafik tampil setelah penyaluran dicairkan pada tahun ${tahun_terpilih}.`}
                                />
                            ) : (
                                <BarComparisonChart
                                    title="Penyaluran per Program (Terbesar)"
                                    data={kinerja_distribusi.penyaluran_per_program}
                                    isCurrency
                                    height={240}
                                />
                            )}
                        </div>
                        <div>
                            {kinerja_distribusi.penyaluran_per_asnaf.length === 0 ? (
                                <EmptyState
                                    title="Belum Ada Penyaluran per Asnaf"
                                    description={`Data grafik tampil setelah penyaluran dicairkan pada tahun ${tahun_terpilih}.`}
                                />
                            ) : (
                                <BarComparisonChart
                                    title="Penyaluran per Asnaf & Non-Zakat"
                                    data={kinerja_distribusi.penyaluran_per_asnaf}
                                    isCurrency
                                    height={240}
                                />
                            )}
                        </div>
                    </div>
                </section>

                {/* Ringkasan Pos Puncak L2 / L3 */}
                <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <h3 className="text-foreground font-semibold">
                                Ringkasan Pos Puncak {tabPos} vs Realisasi ({tahun_terpilih})
                            </h3>
                            <p className="text-muted-foreground text-xs">
                                {tabPos === 'L2'
                                    ? 'Pos tingkat 1 lampiran L2 Rencana Penerimaan ZIS (Dalam Neraca).'
                                    : 'Pos tingkat 1 lampiran L3 Rencana Penyaluran ZIS (Dalam Neraca).'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="border-border bg-muted/40 inline-flex h-9 items-center rounded-lg border p-0.5">
                                <button
                                    type="button"
                                    onClick={() => setTabPos('L2')}
                                    className={`h-8 rounded-md px-3 text-xs font-medium transition ${
                                        tabPos === 'L2' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    Penerimaan (L2)
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setTabPos('L3')}
                                    className={`h-8 rounded-md px-3 text-xs font-medium transition ${
                                        tabPos === 'L3' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    Penyaluran (L3)
                                </button>
                            </div>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('pkp.realisasi', { tahun: tahun_terpilih, lampiran: tabPos })}>Rincian Lengkap</Link>
                            </Button>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border/80 text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="pb-2">Kode</th>
                                    <th className="pb-2">Uraian Pos</th>
                                    <th className="pb-2 text-right">Target RKAT</th>
                                    <th className="pb-2 text-right">Realisasi</th>
                                    <th className="pb-2 text-right">Capaian</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border/60 divide-y">
                                {posAktif.map((pos) => (
                                    <tr key={pos.id} className="hover:bg-surface-subtle transition">
                                        <td className="text-muted-foreground py-2.5 font-mono text-xs">{pos.kode}</td>
                                        <td className="py-2.5 font-medium">{pos.nama}</td>
                                        <td className="py-2.5 text-right tabular-nums">{formatRupiah(pos.target_rkat, true)}</td>
                                        <td className="text-success py-2.5 text-right font-medium tabular-nums">
                                            {formatRupiah(pos.realisasi, true)}
                                        </td>
                                        <td className="py-2.5 text-right tabular-nums">
                                            <StatusBadge
                                                status={pos.persen_label}
                                                variant={
                                                    pos.persen === null
                                                        ? 'draft'
                                                        : pos.persen >= 80
                                                          ? 'success'
                                                          : pos.persen >= 50
                                                            ? 'warning'
                                                            : 'info'
                                                }
                                                label={pos.persen_label}
                                            />
                                        </td>
                                    </tr>
                                ))}
                                {posAktif.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="text-muted-foreground py-6 text-center text-xs">
                                            Belum ada data pos untuk tahun anggaran {tahun_terpilih}.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
