import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { BarComparisonChart, LineTrendChart, type ChartDatum } from '@/components/ui/chart';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, FileCheck, Plus, Receipt, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengumpulan', href: '/pengumpulan' }];

interface Props {
    kpi: {
        penerimaan_hari_ini: number;
        transaksi_hari_ini: number;
        penerimaan_bulan_ini: number;
        transaksi_bulan_ini: number;
        draft_belum_final: number;
        pembatalan_menunggu: number;
    };
    tren_harian: ChartDatum[];
    komposisi_sumber_dana: { id: number; kode: string; nama: string; jenis_dana: string; total: number }[];
    pembatalan_list: { id: number; penerimaan_id: number; nomor: string | null; muzakki: string | null; alasan: string; peminta: string | null }[];
    ba_draft: { id: number; nomor: string; tanggal: string | null; total_rupiah: number }[];
    label_bulan: string;
}

export default function DasborPengumpulan({ kpi, tren_harian, komposisi_sumber_dana, pembatalan_list, ba_draft, label_bulan }: Props) {
    const dataBatang: ChartDatum[] = komposisi_sumber_dana.map((item) => ({
        label: item.kode,
        nilai: item.total,
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasbor Pengumpulan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Dasbor Bidang Pengumpulan"
                    description={`Ringkasan penghimpunan ZIS periode ${label_bulan}.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('pengumpulan.laporan.index')}>Lihat Laporan</Link>
                            </Button>
                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('pengumpulan.penerimaan.create')}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Input Penerimaan Baru</span>
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Penerimaan Hari Ini</span>
                            <Receipt className="text-primary h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatRupiah(kpi.penerimaan_hari_ini, true)}</p>
                        <p className="text-muted-foreground mt-1 text-xs">{formatAngka(kpi.transaksi_hari_ini)} transaksi final</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Penerimaan {label_bulan}</span>
                            <TrendingUp className="text-success h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatRupiah(kpi.penerimaan_bulan_ini, true)}</p>
                        <p className="text-muted-foreground mt-1 text-xs">{formatAngka(kpi.transaksi_bulan_ini)} transaksi final</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Draft Belum Final</span>
                            <FileCheck className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatAngka(kpi.draft_belum_final)}</p>
                        <Link href={`${route('pengumpulan.penerimaan.index')}?status=draft`} className="text-primary mt-1 inline-block text-xs hover:underline">
                            Tinjau transaksi draft
                        </Link>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Pembatalan Menunggu Persetujuan</span>
                            <AlertTriangle className="text-warning h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatAngka(kpi.pembatalan_menunggu)}</p>
                        <p className="text-muted-foreground mt-1 text-xs">Memerlukan tinjauan maker-checker</p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <LineTrendChart title={`Tren Penerimaan Harian (${label_bulan})`} data={tren_harian} isCurrency className="lg:col-span-2" />
                    <BarComparisonChart title="Komposisi per Sumber Dana" data={dataBatang} isCurrency />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Pengajuan Pembatalan Menunggu</h2>
                        <div className="mt-3 space-y-2.5">
                            {pembatalan_list.length === 0 ? (
                                <EmptyState
                                    title="Tidak Ada Pembatalan Tertunda"
                                    description="Seluruh pengajuan pembatalan transaksi telah diproses."
                                />
                            ) : (
                                pembatalan_list.map((pb) => (
                                    <Link
                                        key={pb.id}
                                        href={route('pengumpulan.penerimaan.show', pb.penerimaan_id)}
                                        className="hover:bg-surface-subtle flex items-start justify-between gap-3 rounded-lg border p-3 transition-colors"
                                    >
                                        <div className="min-w-0">
                                            <p className="text-foreground text-sm font-medium">
                                                {pb.nomor ?? `Penerimaan #${pb.penerimaan_id}`} · {pb.muzakki ?? '-'}
                                            </p>
                                            <p className="text-muted-foreground mt-0.5 line-clamp-1 text-xs">{pb.alasan}</p>
                                            <p className="text-muted-foreground mt-0.5 text-[11px]">Diajukan oleh {pb.peminta ?? '-'}</p>
                                        </div>
                                        <StatusBadge status="warning" label="Menunggu" />
                                    </Link>
                                ))
                            )}
                        </div>
                    </section>

                    <section className="bg-card border-border rounded-xl border p-5">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="text-foreground text-base font-semibold">Berita Acara Harian Berstatus Draft</h2>
                            <Link href={route('pengumpulan.berita-acara.index')} className="text-primary text-xs font-medium hover:underline">
                                Kelola BA Harian
                            </Link>
                        </div>
                        <div className="mt-3 space-y-2.5">
                            {ba_draft.length === 0 ? (
                                <EmptyState
                                    title="Tidak Ada Berita Acara Draft"
                                    description="Semua berita acara harian yang dibuat sudah berstatus final."
                                />
                            ) : (
                                ba_draft.map((ba) => (
                                    <Link
                                        key={ba.id}
                                        href={route('pengumpulan.berita-acara.index')}
                                        className="hover:bg-surface-subtle flex items-center justify-between gap-3 rounded-lg border p-3 transition-colors"
                                    >
                                        <div>
                                            <p className="text-foreground text-sm font-medium">{ba.nomor}</p>
                                            <p className="text-muted-foreground text-xs">Tanggal {ba.tanggal ?? '-'}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-foreground text-sm font-semibold tabular-nums">{formatRupiah(ba.total_rupiah)}</p>
                                            <StatusBadge status="draft" />
                                        </div>
                                    </Link>
                                ))
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
