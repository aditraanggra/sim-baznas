import { EmptyState, PageHeader, PratinjauPdfModal, StatusBadge, UnggahBerkas } from '@/components/app';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, Download, Eye, FileText, GitBranch, Pencil, Printer, Search, ShieldOff, Upload } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'PKP', href: '/pkp' },
    { title: 'Arsip Dokumen PKP', href: '/pkp/arsip' },
];

interface DokumenArsipItem {
    id: number;
    uuid: string;
    kategori_dokumen_id: number;
    kategori: string | null;
    bidang_id: number | null;
    bidang: string | null;
    tahun: number;
    nomor: string | null;
    judul: string;
    versi: number;
    induk_versi_id: number | null;
    induk_versi_label: string | null;
    tanggal_pengesahan: string | null;
    tanggal_pengesahan_label: string | null;
    ringkasan: string | null;
    status: 'draft' | 'disahkan' | 'dicabut' | string;
    ukuran: number;
    nama_berkas: string;
    pengunggah: string | null;
    pengesah: string | null;
    disahkan_at: string | null;
    pencabut: string | null;
    alasan_cabut: string | null;
    url_lihat: string;
    url_cetak: string;
    url_unduh: string;
}

interface LogAksesItem {
    id: number;
    dokumen_judul: string;
    dokumen_versi: number | null;
    aksi: string;
    pelaku: string;
    waktu: string | null;
    ip_address: string | null;
}

interface Props {
    filter: {
        q: string;
        kategori_id: number | null;
        tahun: number | null;
        status: string;
    };
    daftar_kategori: { id: number; kode: string; nama: string }[];
    daftar_bidang: { id: number; kode: string; nama: string }[];
    daftar_tahun: number[];
    dokumen: DokumenArsipItem[];
    log_terbaru: LogAksesItem[];
    akses: {
        boleh_tulis: boolean;
        boleh_sah: boolean;
    };
}

function formatUkuran(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

export default function ArsipDokumenPkp({ filter, daftar_kategori, daftar_bidang, daftar_tahun, dokumen, log_terbaru, akses }: Props) {
    const [q, setQ] = React.useState(filter.q);
    const [kategoriId, setKategoriId] = React.useState(filter.kategori_id ? String(filter.kategori_id) : '');
    const [tahun, setTahun] = React.useState(filter.tahun ? String(filter.tahun) : '');
    const [status, setStatus] = React.useState(filter.status || 'semua');

    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);
    const [modeDrawer, setModeDrawer] = React.useState<'baru' | 'edit' | 'revisi'>('baru');
    const [pratinjau, setPratinjau] = React.useState<{ dokumen: DokumenArsipItem; cetakOtomatis: boolean } | null>(null);
    const [targetSahkan, setTargetSahkan] = React.useState<DokumenArsipItem | null>(null);
    const [targetCabut, setTargetCabut] = React.useState<DokumenArsipItem | null>(null);
    const [alasanCabut, setAlasanCabut] = React.useState('');

    const form = useForm<{
        id: string;
        induk_versi_id: string;
        kategori_dokumen_id: string;
        bidang_id: string;
        tahun: string;
        nomor: string;
        judul: string;
        tanggal_pengesahan: string;
        ringkasan: string;
        berkas: File | null;
        sahkan_langsung: boolean;
    }>({
        id: '',
        induk_versi_id: '',
        kategori_dokumen_id: daftar_kategori[0] ? String(daftar_kategori[0].id) : '',
        bidang_id: '',
        tahun: String(new Date().getFullYear()),
        nomor: '',
        judul: '',
        tanggal_pengesahan: '',
        ringkasan: '',
        berkas: null,
        sahkan_langsung: false,
    });

    const terapkanFilter = (e?: React.FormEvent) => {
        e?.preventDefault();
        router.get(
            route('pkp.arsip.index'),
            {
                q: q || undefined,
                kategori_id: kategoriId || undefined,
                tahun: tahun || undefined,
                status: status !== 'semua' ? status : undefined,
            },
            { preserveState: true },
        );
    };

    const bukaDrawerBaru = () => {
        setModeDrawer('baru');
        form.clearErrors();
        form.setData({
            id: '',
            induk_versi_id: '',
            kategori_dokumen_id: daftar_kategori[0] ? String(daftar_kategori[0].id) : '',
            bidang_id: '',
            tahun: String(new Date().getFullYear()),
            nomor: '',
            judul: '',
            tanggal_pengesahan: '',
            ringkasan: '',
            berkas: null,
            sahkan_langsung: false,
        });
        setDrawerTerbuka(true);
    };

    const bukaDrawerEdit = (dok: DokumenArsipItem) => {
        setModeDrawer('edit');
        form.clearErrors();
        form.setData({
            id: String(dok.id),
            induk_versi_id: dok.induk_versi_id ? String(dok.induk_versi_id) : '',
            kategori_dokumen_id: String(dok.kategori_dokumen_id),
            bidang_id: dok.bidang_id ? String(dok.bidang_id) : '',
            tahun: String(dok.tahun),
            nomor: dok.nomor ?? '',
            judul: dok.judul,
            tanggal_pengesahan: dok.tanggal_pengesahan ?? '',
            ringkasan: dok.ringkasan ?? '',
            berkas: null,
            sahkan_langsung: false,
        });
        setDrawerTerbuka(true);
    };

    const bukaDrawerRevisi = (dok: DokumenArsipItem) => {
        setModeDrawer('revisi');
        form.clearErrors();
        form.setData({
            id: '',
            induk_versi_id: String(dok.id),
            kategori_dokumen_id: String(dok.kategori_dokumen_id),
            bidang_id: dok.bidang_id ? String(dok.bidang_id) : '',
            tahun: String(dok.tahun),
            nomor: dok.nomor ?? '',
            judul: dok.judul,
            tanggal_pengesahan: '',
            ringkasan: dok.ringkasan ?? '',
            berkas: null,
            sahkan_langsung: false,
        });
        setDrawerTerbuka(true);
    };

    const simpanDokumen = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pkp.arsip.simpan'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setDrawerTerbuka(false);
                form.reset();
            },
        });
    };

    const sahkanDokumen = () => {
        if (!targetSahkan) return;
        router.post(
            route('pkp.arsip.sahkan', { dokumen: targetSahkan.id }),
            { cabut_versi_lama: true },
            {
                preserveScroll: true,
                onSuccess: () => setTargetSahkan(null),
            },
        );
    };

    const cabutDokumen = () => {
        if (!targetCabut || alasanCabut.trim().length < 5) return;
        router.post(
            route('pkp.arsip.cabut', { dokumen: targetCabut.id }),
            { alasan_cabut: alasanCabut },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTargetCabut(null);
                    setAlasanCabut('');
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Arsip Dokumen PKP" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Arsip Dokumen PKP (Portal PPID)"
                    description="Penyimpanan dokumen perencanaan, laporan kinerja, laporan keuangan, dan SOP resmi yang disahkan untuk Pimpinan & Kabag."
                    breadcrumbs={breadcrumbs}
                    actions={
                        akses.boleh_tulis ? (
                            <Button type="button" onClick={bukaDrawerBaru} className="h-9 gap-1.5">
                                <Upload className="h-4 w-4" aria-hidden="true" />
                                <span>Unggah Dokumen</span>
                            </Button>
                        ) : undefined
                    }
                />

                {/* Bilah Pencarian & Filter */}
                <form onSubmit={terapkanFilter} className="bg-card border-border grid gap-3 rounded-2xl border p-4 shadow-xs sm:grid-cols-12 sm:items-end">
                    <div className="space-y-1 sm:col-span-4">
                        <Label htmlFor="arsip-cari" className="text-xs">
                            Pencarian
                        </Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="arsip-cari"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Judul, nomor dokumen, ringkasan..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>

                    <div className="space-y-1 sm:col-span-3">
                        <Label htmlFor="arsip-kategori" className="text-xs">
                            Kategori
                        </Label>
                        <select
                            id="arsip-kategori"
                            value={kategoriId}
                            onChange={(e) => setKategoriId(e.target.value)}
                            className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <option value="">Semua Kategori</option>
                            {daftar_kategori.map((k) => (
                                <option key={k.id} value={k.id}>
                                    {k.nama}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1 sm:col-span-2">
                        <Label htmlFor="arsip-tahun" className="text-xs">
                            Tahun
                        </Label>
                        <select
                            id="arsip-tahun"
                            value={tahun}
                            onChange={(e) => setTahun(e.target.value)}
                            className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <option value="">Semua Tahun</option>
                            {daftar_tahun.map((thn) => (
                                <option key={thn} value={thn}>
                                    {thn}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1 sm:col-span-2">
                        <Label htmlFor="arsip-status" className="text-xs">
                            Status
                        </Label>
                        <select
                            id="arsip-status"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <option value="semua">Semua Status</option>
                            <option value="disahkan">Disahkan (Tampil di Portal)</option>
                            <option value="draft">Draft</option>
                            <option value="dicabut">Dicabut</option>
                        </select>
                    </div>

                    <div className="sm:col-span-1">
                        <Button type="submit" variant="outline" className="h-9 w-full">
                            Filter
                        </Button>
                    </div>
                </form>

                {/* Daftar Dokumen */}
                {dokumen.length === 0 ? (
                    <EmptyState
                        title="Belum Ada Dokumen Arsip PKP"
                        description="Unggah dokumen PDF resmi (RKAT, RENJA, RENSTRA, Laporan Kinerja, Laporan Keuangan, atau SOP) untuk ditampilkan di Portal Arsip Pimpinan."
                        actionLabel={akses.boleh_tulis ? 'Unggah Dokumen Pertama' : undefined}
                        onAction={akses.boleh_tulis ? bukaDrawerBaru : undefined}
                    />
                ) : (
                    <div className="bg-card border-border overflow-x-auto rounded-2xl border shadow-xs">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="px-4 py-3">Dokumen</th>
                                    <th className="px-4 py-3">Kategori &amp; Tahun</th>
                                    <th className="px-4 py-3">Versi &amp; Pengesahan</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {dokumen.map((dok) => (
                                    <tr key={dok.id} className="hover:bg-surface-subtle transition-colors">
                                        <td className="px-4 py-3">
                                            <div className="flex items-start gap-2.5">
                                                <div className="bg-surface-subtle text-primary mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border">
                                                    <FileText className="h-4 w-4" aria-hidden="true" />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="text-foreground font-medium">{dok.judul}</p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {dok.nomor ? `No. ${dok.nomor} · ` : ''}
                                                        {dok.nama_berkas} ({formatUkuran(dok.ukuran)})
                                                    </p>
                                                    {dok.alasan_cabut && (
                                                        <p className="text-destructive mt-0.5 text-xs">Dicabut: {dok.alasan_cabut}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>

                                        <td className="px-4 py-3">
                                            <div className="text-foreground font-medium">{dok.kategori ?? '-'}</div>
                                            <div className="text-muted-foreground text-xs tabular-nums">
                                                Tahun {dok.tahun}
                                                {dok.bidang ? ` · ${dok.bidang}` : ''}
                                            </div>
                                        </td>

                                        <td className="px-4 py-3 text-xs">
                                            <div className="text-foreground font-medium tabular-nums">
                                                Versi {dok.versi}
                                                {dok.induk_versi_label ? ` (revisi dari ${dok.induk_versi_label})` : ''}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {dok.tanggal_pengesahan_label
                                                    ? `Sah: ${dok.tanggal_pengesahan_label}${dok.pengesah ? ` · ${dok.pengesah}` : ''}`
                                                    : `Diunggah: ${dok.pengunggah ?? '-'}`}
                                            </div>
                                        </td>

                                        <td className="px-4 py-3">
                                            <StatusBadge
                                                status={dok.status}
                                                variant={dok.status === 'disahkan' ? 'final' : dok.status === 'dicabut' ? 'dibatalkan' : 'draft'}
                                                label={dok.status === 'disahkan' ? 'Disahkan' : dok.status === 'dicabut' ? 'Dicabut' : 'Draft'}
                                            />
                                        </td>

                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap items-center justify-end gap-1.5">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setPratinjau({ dokumen: dok, cetakOtomatis: false })}
                                                    className="h-8 gap-1 px-2.5 text-xs"
                                                >
                                                    <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                                                    <span>Lihat</span>
                                                </Button>

                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setPratinjau({ dokumen: dok, cetakOtomatis: true })}
                                                    className="h-8 gap-1 px-2.5 text-xs"
                                                >
                                                    <Printer className="h-3.5 w-3.5" aria-hidden="true" />
                                                    <span>Cetak</span>
                                                </Button>

                                                <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                                                    <a href={dok.url_unduh}>
                                                        <Download className="h-3.5 w-3.5" aria-hidden="true" />
                                                        <span>Unduh</span>
                                                    </a>
                                                </Button>

                                                {akses.boleh_tulis && dok.status === 'draft' && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => bukaDrawerEdit(dok)}
                                                        className="h-8 gap-1 px-2.5 text-xs"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                                        <span>Edit</span>
                                                    </Button>
                                                )}

                                                {akses.boleh_tulis && dok.status === 'disahkan' && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => bukaDrawerRevisi(dok)}
                                                        className="h-8 gap-1 px-2.5 text-xs"
                                                    >
                                                        <GitBranch className="h-3.5 w-3.5" aria-hidden="true" />
                                                        <span>Versi Baru</span>
                                                    </Button>
                                                )}

                                                {akses.boleh_sah && dok.status === 'draft' && (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() => setTargetSahkan(dok)}
                                                        className="h-8 gap-1 px-2.5 text-xs"
                                                    >
                                                        <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                                                        <span>Sahkan</span>
                                                    </Button>
                                                )}

                                                {akses.boleh_sah && dok.status !== 'dicabut' && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => {
                                                            setTargetCabut(dok);
                                                            setAlasanCabut('');
                                                        }}
                                                        className="text-destructive hover:bg-destructive-soft hover:text-destructive h-8 gap-1 px-2.5 text-xs"
                                                    >
                                                        <ShieldOff className="h-3.5 w-3.5" aria-hidden="true" />
                                                        <span>Cabut</span>
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Riwayat Akses Terbaru */}
                {log_terbaru.length > 0 && (
                    <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                        <h2 className="text-foreground text-base font-semibold">Riwayat Akses Arsip Terbaru</h2>
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            Log aktivitas lihat, cetak, dan unduh dokumen arsip PKP (internal maupun lewat tautan khusus).
                        </p>

                        <div className="mt-3 overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                        <th className="pb-2">Waktu</th>
                                        <th className="pb-2">Dokumen</th>
                                        <th className="pb-2">Aksi</th>
                                        <th className="pb-2">Pengakses</th>
                                        <th className="pb-2">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-border divide-y">
                                    {log_terbaru.map((log) => (
                                        <tr key={log.id} className="h-9">
                                            <td className="text-muted-foreground py-2 tabular-nums">{log.waktu ?? '-'}</td>
                                            <td className="text-foreground py-2 font-medium">
                                                {log.dokumen_judul}
                                                {log.dokumen_versi ? ` (v${log.dokumen_versi})` : ''}
                                            </td>
                                            <td className="py-2 uppercase">{log.aksi}</td>
                                            <td className="text-muted-foreground py-2">{log.pelaku}</td>
                                            <td className="text-muted-foreground py-2 font-mono">{log.ip_address ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}
            </div>

            {/* Sheet Unggah / Edit / Versi Baru */}
            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[520px]">
                    <SheetHeader>
                        <SheetTitle>
                            {modeDrawer === 'edit'
                                ? 'Ubah Draft Dokumen Arsip'
                                : modeDrawer === 'revisi'
                                  ? 'Buat Versi Revisi Baru'
                                  : 'Unggah Dokumen Arsip PKP'}
                        </SheetTitle>
                        <SheetDescription>
                            {modeDrawer === 'revisi'
                                ? 'Mengunggah versi revisi baru. Saat versi baru disahkan, versi sebelumnya otomatis dicabut.'
                                : 'Unggah berkas PDF resmi (maksimal 20 MB). Dokumen hanya tampil di Portal Arsip Pimpinan setelah berstatus Disahkan.'}
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={simpanDokumen} className="mt-5 space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="form-kategori">Kategori Dokumen (wajib)</Label>
                                <select
                                    id="form-kategori"
                                    value={form.data.kategori_dokumen_id}
                                    onChange={(e) => form.setData('kategori_dokumen_id', e.target.value)}
                                    required
                                    className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    {daftar_kategori.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.kategori_dokumen_id && (
                                    <p className="text-destructive text-xs">{form.errors.kategori_dokumen_id}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="form-tahun">Tahun Dokumen (wajib)</Label>
                                <Input
                                    id="form-tahun"
                                    type="number"
                                    min={2000}
                                    max={2100}
                                    value={form.data.tahun}
                                    onChange={(e) => form.setData('tahun', e.target.value)}
                                    required
                                />
                                {form.errors.tahun && <p className="text-destructive text-xs">{form.errors.tahun}</p>}
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="form-judul">Judul Dokumen (wajib)</Label>
                            <Input
                                id="form-judul"
                                value={form.data.judul}
                                onChange={(e) => form.setData('judul', e.target.value)}
                                placeholder="Contoh: Laporan Kinerja Pengelolaan ZIS Tahun 2026"
                                required
                            />
                            {form.errors.judul && <p className="text-destructive text-xs">{form.errors.judul}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="form-nomor">Nomor Dokumen (opsional)</Label>
                                <Input
                                    id="form-nomor"
                                    value={form.data.nomor}
                                    onChange={(e) => form.setData('nomor', e.target.value)}
                                    placeholder="SK / Nomor resmi"
                                />
                                {form.errors.nomor && <p className="text-destructive text-xs">{form.errors.nomor}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="form-tanggal-sah">Tanggal Pengesahan</Label>
                                <Input
                                    id="form-tanggal-sah"
                                    type="date"
                                    value={form.data.tanggal_pengesahan}
                                    onChange={(e) => form.setData('tanggal_pengesahan', e.target.value)}
                                />
                                {form.errors.tanggal_pengesahan && (
                                    <p className="text-destructive text-xs">{form.errors.tanggal_pengesahan}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="form-bidang">Bidang Terkait (opsional, untuk SOP)</Label>
                            <select
                                id="form-bidang"
                                value={form.data.bidang_id}
                                onChange={(e) => form.setData('bidang_id', e.target.value)}
                                className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            >
                                <option value="">Lintas Lembaga / Umum PKP</option>
                                {daftar_bidang.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.nama}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="form-ringkasan">Ringkasan Singkat (opsional)</Label>
                            <textarea
                                id="form-ringkasan"
                                value={form.data.ringkasan}
                                onChange={(e) => form.setData('ringkasan', e.target.value)}
                                rows={3}
                                placeholder="Ringkasan isi atau catatan revisi untuk pimpinan..."
                                className="border-input bg-card placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            />
                            {form.errors.ringkasan && <p className="text-destructive text-xs">{form.errors.ringkasan}</p>}
                        </div>

                        <UnggahBerkas
                            id="form-berkas-pdf"
                            label={modeDrawer === 'edit' ? 'Ganti Berkas PDF (opsional)' : 'Berkas Dokumen PDF'}
                            accept=".pdf"
                            maxSizeMb={20}
                            value={form.data.berkas}
                            onChange={(file) => form.setData('berkas', file)}
                            required={modeDrawer !== 'edit'}
                            error={form.errors.berkas}
                            progress={form.progress?.percentage ?? null}
                        />

                        {akses.boleh_sah && (
                            <div className="bg-surface-subtle border-border flex items-start gap-2.5 rounded-xl border p-3">
                                <Checkbox
                                    id="form-sahkan-langsung"
                                    checked={form.data.sahkan_langsung}
                                    onCheckedChange={(checked) => form.setData('sahkan_langsung', Boolean(checked))}
                                />
                                <Label htmlFor="form-sahkan-langsung" className="text-xs leading-4 font-normal">
                                    Langsung sahkan setelah disimpan agar tampil di Portal Arsip Pimpinan
                                    {modeDrawer === 'revisi' ? ' (versi lama otomatis dicabut).' : '.'}
                                </Label>
                            </div>
                        )}

                        <SheetFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setDrawerTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Menyimpan...' : 'Simpan Dokumen'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Konfirmasi Pengesahan */}
            <AlertDialog open={targetSahkan !== null} onOpenChange={(open) => !open && setTargetSahkan(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Sahkan Dokumen Arsip PKP?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Dokumen <strong>{targetSahkan?.judul}</strong> akan dikunci sebagai dokumen resmi berstatus <strong>Disahkan</strong>{' '}
                            dan langsung tersedia di Portal Arsip Pimpinan (PPID).
                            {targetSahkan?.induk_versi_id ? ' Versi sebelumnya akan otomatis ditandai Dicabut.' : ''}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={sahkanDokumen}>Sahkan Dokumen</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Konfirmasi Pencabutan */}
            <AlertDialog open={targetCabut !== null} onOpenChange={(open) => !open && setTargetCabut(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cabut Dokumen Arsip?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Dokumen <strong>{targetCabut?.judul}</strong> (v{targetCabut?.versi}) akan ditarik dari Portal Arsip Pimpinan. Arsip lama tetap tersimpan sebagai riwayat audit.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-1.5 py-2">
                        <Label htmlFor="alasan-cabut-dok">Alasan Pencabutan (wajib, min. 5 karakter)</Label>
                        <textarea
                            id="alasan-cabut-dok"
                            value={alasanCabut}
                            onChange={(e) => setAlasanCabut(e.target.value)}
                            rows={3}
                            placeholder="Tuliskan alasan pencabutan dokumen..."
                            className="border-input bg-card placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        />
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={cabutDokumen}
                            disabled={alasanCabut.trim().length < 5}
                            className="bg-destructive hover:bg-destructive/90 text-white"
                        >
                            Cabut Dokumen
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Modal Pratinjau PDF */}
            {pratinjau && (
                <PratinjauPdfModal
                    terbuka={true}
                    onTutup={() => setPratinjau(null)}
                    judul={pratinjau.dokumen.judul}
                    subjudul={`${pratinjau.dokumen.kategori ?? 'Dokumen'} · Tahun ${pratinjau.dokumen.tahun} · Versi ${pratinjau.dokumen.versi}`}
                    urlLihat={pratinjau.cetakOtomatis ? pratinjau.dokumen.url_cetak : pratinjau.dokumen.url_lihat}
                    urlCetak={pratinjau.dokumen.url_cetak}
                    urlUnduh={pratinjau.dokumen.url_unduh}
                    aksiOtomatisCetak={pratinjau.cetakOtomatis}
                />
            )}
        </AppLayout>
    );
}
