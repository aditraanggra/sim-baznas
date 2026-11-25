import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatAngka } from '@/lib/format';
import { type BidangInfo, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Archive, FileCheck2, Inbox, Mail, Send, ShieldAlert, ShieldCheck } from 'lucide-react';

interface Props {
    bidang: BidangInfo;
    adalah_upz: boolean;
    upz_ringkas: {
        id: number;
        nama: string;
        jenis: string | null;
        sk_nomor: string | null;
        sk_status: string;
        sk_sisa_hari: number | null;
    } | null;
    ringkasan: {
        surat_masuk_aktif: number;
        disposisi_menunggu_saya: number;
        surat_keluar_proses: number;
        sk_upz_perlu_tindakan: number;
    } | null;
    disposisi_menunggu: {
        id: number;
        surat_masuk_id: number;
        nomor_agenda: string;
        pengirim: string;
        perihal: string;
        sifat: string;
        dari: string;
        instruksi: string;
        tenggat: string | null;
    }[];
    surat_keluar_menunggu: {
        id: number;
        jenis: string;
        tujuan: string;
        perihal: string;
        tanggal: string | null;
        pembuat: string;
    }[];
    sk_segera_habis: {
        upz_id: number;
        nama: string;
        nomor: string | null;
        berlaku_sampai: string;
        sisa_hari: number;
        status_berlaku: string;
    }[];
    akses: {
        boleh_kelola_surat: boolean;
        boleh_setujui_keluar: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'SDM, Administrasi & Umum', href: '/umum' }];

export default function DasborUmum({
    bidang,
    adalah_upz,
    upz_ringkas,
    ringkasan,
    disposisi_menunggu,
    surat_keluar_menunggu,
    sk_segera_habis,
    akses,
}: Props) {
    if (adalah_upz) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Portal UPZ" />
                <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                    <PageHeader
                        title="Portal Pengurus UPZ"
                        description="Pantau status SK UPZ dan rekap setoran ZIS UPZ Anda."
                        breadcrumbs={breadcrumbs}
                        actions={
                            upz_ringkas ? (
                                <Button asChild className="h-9 gap-1.5">
                                    <Link href={route('umum.upz.show', upz_ringkas.id)}>
                                        <ShieldCheck className="h-4 w-4" aria-hidden="true" />
                                        <span>Buka Detail UPZ</span>
                                    </Link>
                                </Button>
                            ) : undefined
                        }
                    />

                    {upz_ringkas ? (
                        <div className="bg-card border-border rounded-2xl border p-5">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="text-muted-foreground text-xs font-medium">{upz_ringkas.jenis ?? 'UPZ'}</p>
                                    <h2 className="text-foreground mt-0.5 text-lg font-semibold">{upz_ringkas.nama}</h2>
                                    <p className="text-muted-foreground mt-1 text-xs">Nomor SK: {upz_ringkas.sk_nomor ?? 'Belum tercatat'}</p>
                                </div>
                                <StatusBadge
                                    status={upz_ringkas.sk_status}
                                    variant={
                                        upz_ringkas.sk_status === 'aktif'
                                            ? 'aktif'
                                            : upz_ringkas.sk_status === 'segera_habis'
                                              ? 'warning'
                                              : 'habis'
                                    }
                                    label={
                                        upz_ringkas.sk_status === 'aktif'
                                            ? 'SK Aktif'
                                            : upz_ringkas.sk_status === 'segera_habis'
                                              ? `Segera Habis (${upz_ringkas.sk_sisa_hari} hari)`
                                              : 'SK Habis / Belum Ada'
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <EmptyState
                            title="Akun Belum Tertaut ke UPZ"
                            description="Hubungi administrator BAZNAS Kabupaten Cianjur untuk menautkan akun ini ke data UPZ Anda."
                        />
                    )}
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Dasbor ${bidang.nama}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={`Dasbor ${bidang.nama}`}
                    description="Ringkasan alur persuratan masuk/keluar, disposisi pimpinan, dan pemantauan masa berlaku SK UPZ."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <Link href={route('umum.surat.arsip.index')}>
                                    <Archive className="h-4 w-4" aria-hidden="true" />
                                    <span>Arsip Surat</span>
                                </Link>
                            </Button>
                            {akses.boleh_kelola_surat && (
                                <Button asChild className="h-9 gap-1.5">
                                    <Link href={route('umum.surat.keluar.create')}>
                                        <Send className="h-4 w-4" aria-hidden="true" />
                                        <span>Buat Surat Keluar</span>
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {ringkasan && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="bg-card border-border rounded-2xl border p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-xs font-medium">Surat Masuk Aktif</span>
                                <Mail className="text-primary h-4 w-4" aria-hidden="true" />
                            </div>
                            <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                                {formatAngka(ringkasan.surat_masuk_aktif)}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">Dalam proses disposisi / tindak lanjut</p>
                        </div>

                        <div className="bg-card border-border rounded-2xl border p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-xs font-medium">Disposisi Menunggu Saya</span>
                                <Inbox className="text-warning h-4 w-4" aria-hidden="true" />
                            </div>
                            <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                                {formatAngka(ringkasan.disposisi_menunggu_saya)}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">Perlu arahan atau laporan selesai</p>
                        </div>

                        <div className="bg-card border-border rounded-2xl border p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-xs font-medium">Surat Keluar Proses</span>
                                <FileCheck2 className="text-info h-4 w-4" aria-hidden="true" />
                            </div>
                            <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                                {formatAngka(ringkasan.surat_keluar_proses)}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">Draft &amp; menunggu persetujuan</p>
                        </div>

                        <div className="bg-card border-border rounded-2xl border p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground text-xs font-medium">SK UPZ Perlu Perhatian</span>
                                <ShieldAlert className="text-destructive h-4 w-4" aria-hidden="true" />
                            </div>
                            <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                                {formatAngka(ringkasan.sk_upz_perlu_tindakan)}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">Masa berlaku habis atau &le; 90 hari</p>
                        </div>
                    </div>
                )}

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-2xl border p-5">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <h2 className="text-foreground text-base font-semibold">Disposisi Surat Menunggu Saya</h2>
                                <p className="text-muted-foreground text-xs">Daftar surat masuk yang membutuhkan tindak lanjut Anda.</p>
                            </div>
                            <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                <Link href={route('umum.surat.masuk.index', { disposisi_saya: 1 })}>Lihat Semua</Link>
                            </Button>
                        </div>

                        {disposisi_menunggu.length === 0 ? (
                            <div className="mt-4">
                                <EmptyState
                                    title="Tidak Ada Antrean Disposisi"
                                    description="Semua disposisi surat yang ditujukan kepada Anda telah ditindaklanjuti."
                                />
                            </div>
                        ) : (
                            <div className="mt-4 divide-y">
                                {disposisi_menunggu.map((d) => (
                                    <Link
                                        key={d.id}
                                        href={route('umum.surat.masuk.show', d.surat_masuk_id)}
                                        className="hover:bg-surface-subtle block rounded-lg px-2 py-3 transition-colors"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="text-foreground truncate text-sm font-medium">{d.perihal}</p>
                                                <p className="text-muted-foreground text-xs">
                                                    {d.nomor_agenda} · Dari {d.pengirim} (Disposisi: {d.dari})
                                                </p>
                                                <p className="text-foreground/80 mt-1 line-clamp-1 text-xs">{d.instruksi}</p>
                                            </div>
                                            {d.tenggat && (
                                                <span className="bg-warning-soft text-warning shrink-0 rounded px-2 py-0.5 text-[11px] font-medium">
                                                    Tenggat {d.tenggat}
                                                </span>
                                            )}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="bg-card border-border rounded-2xl border p-5">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <h2 className="text-foreground text-base font-semibold">
                                    {akses.boleh_setujui_keluar ? 'Surat Keluar Menunggu Persetujuan' : 'Pemantauan SK UPZ'}
                                </h2>
                                <p className="text-muted-foreground text-xs">
                                    {akses.boleh_setujui_keluar
                                        ? 'Ajuan surat dinas yang siap ditinjau dan diterbitkan nomornya.'
                                        : 'Daftar SK UPZ yang mendekati batas masa berlaku atau telah habis.'}
                                </p>
                            </div>
                            <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                <Link
                                    href={
                                        akses.boleh_setujui_keluar
                                            ? route('umum.surat.keluar.index', { status: 'menunggu_persetujuan' })
                                            : route('umum.upz.index')
                                    }
                                >
                                    Buka Daftar
                                </Link>
                            </Button>
                        </div>

                        {akses.boleh_setujui_keluar ? (
                            surat_keluar_menunggu.length === 0 ? (
                                <div className="mt-4">
                                    <EmptyState
                                        title="Belum Ada Ajuan Surat Keluar"
                                        description="Belum ada surat keluar yang menunggu persetujuan pimpinan saat ini."
                                    />
                                </div>
                            ) : (
                                <div className="mt-4 divide-y">
                                    {surat_keluar_menunggu.map((sk) => (
                                        <Link
                                            key={sk.id}
                                            href={route('umum.surat.keluar.show', sk.id)}
                                            className="hover:bg-surface-subtle block rounded-lg px-2 py-3 transition-colors"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="text-foreground truncate text-sm font-medium">{sk.perihal}</p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {sk.jenis} · Kepada {sk.tujuan}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">Dibuat oleh {sk.pembuat}</p>
                                                </div>
                                                <StatusBadge status="menunggu" variant="warning" label="Menunggu" />
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )
                        ) : sk_segera_habis.length === 0 ? (
                            <div className="mt-4">
                                <EmptyState title="Semua SK UPZ Aktif" description="Tidak ada SK UPZ yang mendekati kedaluwarsa saat ini." />
                            </div>
                        ) : (
                            <div className="mt-4 divide-y">
                                {sk_segera_habis.map((u) => (
                                    <Link
                                        key={u.upz_id}
                                        href={route('umum.upz.show', u.upz_id)}
                                        className="hover:bg-surface-subtle flex items-center justify-between gap-2 rounded-lg px-2 py-3 transition-colors"
                                    >
                                        <div className="min-w-0">
                                            <p className="text-foreground truncate text-sm font-medium">{u.nama}</p>
                                            <p className="text-muted-foreground text-xs">SK {u.nomor ?? '-'} · Berakhir {u.berlaku_sampai}</p>
                                        </div>
                                        <StatusBadge
                                            status={u.status_berlaku}
                                            variant={u.status_berlaku === 'habis' ? 'habis' : 'warning'}
                                            label={u.status_berlaku === 'habis' ? 'Habis' : `${u.sisa_hari} hari`}
                                        />
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
