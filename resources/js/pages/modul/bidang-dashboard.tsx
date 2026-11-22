import { EmptyState, PageHeader } from '@/components/app';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatAngka } from '@/lib/format';
import { type BidangInfo, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface RingkasanPkp {
    total_pos: number;
    total_alias: number;
    impor_berjalan: number;
    audit_terakhir: string | null;
}

export default function BidangDashboard({ bidang, ringkasan }: { bidang: BidangInfo; ringkasan: RingkasanPkp | null }) {
    const { tahun_aktif } = usePage<SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Launcher', href: '/dashboard' },
        { title: bidang.nama, href: `/${bidang.kode}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={bidang.nama} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title={bidang.nama}
                    description={`Area kerja bidang ${bidang.nama} tahun anggaran ${tahun_aktif}.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        bidang.kode === 'pkp' ? (
                            <Button asChild className="h-9">
                                <Link href="/pkp/rencana">Buka RKAT</Link>
                            </Button>
                        ) : (
                            <Button asChild variant="outline" className="h-9">
                                <Link href="/dashboard">Kembali ke Launcher</Link>
                            </Button>
                        )
                    }
                />

                {bidang.kode === 'pkp' && ringkasan ? (
                    <div className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="bg-card border-border rounded-2xl border p-5">
                                <p className="text-muted-foreground text-xs font-medium">Pos Anggaran Aktif</p>
                                <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.total_pos)}</p>
                                <p className="text-muted-foreground mt-1 text-xs">Berlaku tahun anggaran {tahun_aktif}</p>
                            </div>
                            <div className="bg-card border-border rounded-2xl border p-5">
                                <p className="text-muted-foreground text-xs font-medium">Pemetaan Alias Pos</p>
                                <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.total_alias)}</p>
                                <p className="text-muted-foreground mt-1 text-xs">Rekonsiliasi kode impor historis</p>
                            </div>
                            <div className="bg-card border-border rounded-2xl border p-5">
                                <p className="text-muted-foreground text-xs font-medium">Impor Berjalan</p>
                                <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatAngka(ringkasan.impor_berjalan)}</p>
                                <p className="text-muted-foreground mt-1 text-xs">Antrean pemrosesan dokumen</p>
                            </div>
                        </div>

                        <div className="bg-card border-border rounded-2xl border p-5 text-sm">
                            <p className="text-foreground font-semibold">Modul Perencanaan, Keuangan dan Pelaporan</p>
                            <p className="text-muted-foreground mt-1">
                                Penyusunan RKAT dan revisi tersedia melalui menu RKAT di sidebar. Pencatatan realisasi otomatis dan laporan PSAK 109 akan terhubung setelah modul transaksi Fase 1A aktif.
                            </p>
                            <p className="text-muted-foreground mt-3 text-xs">
                                Audit terakhir:{' '}
                                {ringkasan.audit_terakhir ? new Date(ringkasan.audit_terakhir).toLocaleString('id-ID') : 'Belum ada catatan audit'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <EmptyState
                        title={`Kerangka Modul ${bidang.nama} Siap`}
                        description={`Fondasi otorisasi peran, master data, penomoran, dan berkas privat untuk bidang ${bidang.nama} sudah aktif. Layar operasional transaksi dibangun pada Fase 1A dan Fase 1B.`}
                        actionLabel="Kembali ke Launcher"
                        actionHref="/dashboard"
                    />
                )}
            </div>
        </AppLayout>
    );
}
