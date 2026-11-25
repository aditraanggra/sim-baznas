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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Calendar, Pencil, Plus, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'PKP', href: '/pkp' },
    { title: 'Tahun Anggaran & Kegiatan', href: '/pkp/kegiatan' },
];

interface TahunAnggaranItem {
    id: number;
    tahun: number;
    status: 'draft' | 'berjalan' | 'tutup' | string;
    tanggal_mulai: string | null;
    tanggal_selesai: string | null;
    catatan: string | null;
}

interface RefOption {
    id: number;
    kode: string;
    nama: string;
}

interface SumberDanaOption extends RefOption {
    jenis_dana_id: number;
}

interface PosOption extends RefOption {
    lampiran: string;
    tipe_nilai: string;
}

interface KegiatanItem {
    id: number;
    pos_anggaran_id: number;
    bidang_id: number;
    sumber_transaksi: string;
    jenis_dana_id: number | null;
    sumber_dana_id: number | null;
    asnaf_id: number | null;
    program_id: number | null;
    filter_via_upz: boolean | null;
    filter_jenis_muzakki: string | null;
    nama_kegiatan: string;
    aktif: boolean;
    pos_anggaran?: PosOption | null;
    bidang?: RefOption | null;
    jenis_dana?: RefOption | null;
    sumber_dana?: RefOption | null;
    asnaf?: RefOption | null;
    program?: RefOption | null;
}

interface Props {
    kegiatan: KegiatanItem[];
    tahun_anggaran: TahunAnggaranItem[];
    daftar_bidang: RefOption[];
    daftar_jenis_dana: RefOption[];
    daftar_sumber_dana: SumberDanaOption[];
    daftar_asnaf: RefOption[];
    daftar_program: RefOption[];
    daftar_pos: PosOption[];
    filter: {
        bidang_id: string;
        sumber: string;
    };
    akses: {
        boleh_kelola_kegiatan: boolean;
        boleh_buat_tahun: boolean;
        boleh_status_tahun: boolean;
    };
}

const LABEL_SUMBER: Record<string, string> = {
    penerimaan: 'Penerimaan (Rp)',
    penyaluran: 'Penyaluran (Rp)',
    penerimaan_orang: 'Penerimaan (Orang)',
    penyaluran_orang: 'Penyaluran (Orang)',
    manual: 'Input Manual',
};

function badgeTahun(status: string) {
    if (status === 'berjalan') return <StatusBadge status="aktif" variant="aktif" label="Berjalan" />;
    if (status === 'tutup') return <StatusBadge status="tutup" variant="draft" label="Tutup" />;
    return <StatusBadge status="draft" variant="warning" label="Draft" />;
}

export default function KegiatanIndex({
    kegiatan = [],
    tahun_anggaran = [],
    daftar_bidang = [],
    daftar_jenis_dana = [],
    daftar_sumber_dana = [],
    daftar_asnaf = [],
    daftar_program = [],
    daftar_pos = [],
    filter = { bidang_id: '', sumber: '' },
    akses = { boleh_kelola_kegiatan: false, boleh_buat_tahun: false, boleh_status_tahun: false },
}: Props) {
    const [kataCari, setKataCari] = React.useState('');
    const [sheetTahunTerbuka, setSheetTahunTerbuka] = React.useState(false);
    const [sheetPemetaanTerbuka, setSheetPemetaanTerbuka] = React.useState(false);
    const [targetStatusTahun, setTargetStatusTahun] = React.useState<{
        item: TahunAnggaranItem;
        statusBaru: 'draft' | 'berjalan' | 'tutup';
    } | null>(null);
    const [alasanStatus, setAlasanStatus] = React.useState('');

    const formTahun = useForm({
        id: '' as string | number,
        tahun: String(new Date().getFullYear()),
        status: 'draft',
        tanggal_mulai: `${new Date().getFullYear()}-01-01`,
        tanggal_selesai: `${new Date().getFullYear()}-12-31`,
        catatan: '',
    });

    const formPemetaan = useForm({
        pos_anggaran_id: '',
        bidang_id: '',
        sumber_transaksi: 'penerimaan',
        jenis_dana_id: '',
        sumber_dana_id: '',
        asnaf_id: '',
        program_id: '',
        filter_via_upz: '' as string,
        filter_jenis_muzakki: '',
        nama_kegiatan: '',
        aktif: true as boolean,
    });

    const bukaTambahTahun = () => {
        const tahunBerikutnya = (tahun_anggaran[0]?.tahun ?? new Date().getFullYear()) + 1;
        formTahun.clearErrors();
        formTahun.setData({
            id: '',
            tahun: String(tahunBerikutnya),
            status: 'draft',
            tanggal_mulai: `${tahunBerikutnya}-01-01`,
            tanggal_selesai: `${tahunBerikutnya}-12-31`,
            catatan: '',
        });
        setSheetTahunTerbuka(true);
    };

    const bukaUbahTahun = (item: TahunAnggaranItem) => {
        formTahun.clearErrors();
        formTahun.setData({
            id: item.id,
            tahun: String(item.tahun),
            status: item.status,
            tanggal_mulai: item.tanggal_mulai ? item.tanggal_mulai.slice(0, 10) : '',
            tanggal_selesai: item.tanggal_selesai ? item.tanggal_selesai.slice(0, 10) : '',
            catatan: item.catatan ?? '',
        });
        setSheetTahunTerbuka(true);
    };

    const kirimTahun = (e: React.FormEvent) => {
        e.preventDefault();
        formTahun.post(route('pkp.tahun-anggaran.store'), {
            preserveScroll: true,
            onSuccess: () => setSheetTahunTerbuka(false),
        });
    };

    const konfirmasiUbahStatusTahun = () => {
        if (!targetStatusTahun) return;
        router.post(
            route('pkp.tahun-anggaran.status'),
            {
                id: targetStatusTahun.item.id,
                status: targetStatusTahun.statusBaru,
                alasan: alasanStatus || null,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setTargetStatusTahun(null);
                    setAlasanStatus('');
                },
            },
        );
    };

    const bukaTambahPemetaan = () => {
        formPemetaan.clearErrors();
        formPemetaan.setData({
            pos_anggaran_id: '',
            bidang_id: daftar_bidang[0] ? String(daftar_bidang[0].id) : '',
            sumber_transaksi: 'penerimaan',
            jenis_dana_id: '',
            sumber_dana_id: '',
            asnaf_id: '',
            program_id: '',
            filter_via_upz: '',
            filter_jenis_muzakki: '',
            nama_kegiatan: '',
            aktif: true,
        });
        setSheetPemetaanTerbuka(true);
    };

    const bukaUbahPemetaan = (item: KegiatanItem) => {
        formPemetaan.clearErrors();
        formPemetaan.setData({
            pos_anggaran_id: String(item.pos_anggaran_id),
            bidang_id: String(item.bidang_id),
            sumber_transaksi: item.sumber_transaksi,
            jenis_dana_id: item.jenis_dana_id ? String(item.jenis_dana_id) : '',
            sumber_dana_id: item.sumber_dana_id ? String(item.sumber_dana_id) : '',
            asnaf_id: item.asnaf_id ? String(item.asnaf_id) : '',
            program_id: item.program_id ? String(item.program_id) : '',
            filter_via_upz: item.filter_via_upz === true ? '1' : item.filter_via_upz === false ? '0' : '',
            filter_jenis_muzakki: item.filter_jenis_muzakki ?? '',
            nama_kegiatan: item.nama_kegiatan,
            aktif: item.aktif,
        });
        setSheetPemetaanTerbuka(true);
    };

    const kirimPemetaan = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            route('pkp.kegiatan.store'),
            {
                pos_anggaran_id: Number(formPemetaan.data.pos_anggaran_id),
                bidang_id: Number(formPemetaan.data.bidang_id),
                sumber_transaksi: formPemetaan.data.sumber_transaksi,
                jenis_dana_id: formPemetaan.data.jenis_dana_id ? Number(formPemetaan.data.jenis_dana_id) : null,
                sumber_dana_id: formPemetaan.data.sumber_dana_id ? Number(formPemetaan.data.sumber_dana_id) : null,
                asnaf_id: formPemetaan.data.asnaf_id ? Number(formPemetaan.data.asnaf_id) : null,
                program_id: formPemetaan.data.program_id ? Number(formPemetaan.data.program_id) : null,
                filter_via_upz: formPemetaan.data.filter_via_upz === '1' ? true : formPemetaan.data.filter_via_upz === '0' ? false : null,
                filter_jenis_muzakki: formPemetaan.data.filter_jenis_muzakki || null,
                nama_kegiatan: formPemetaan.data.nama_kegiatan,
                aktif: formPemetaan.data.aktif,
            },
            {
                preserveScroll: true,
                onSuccess: () => setSheetPemetaanTerbuka(false),
            },
        );
    };

    const terapkanFilter = (bidangIdBaru: string, sumberBaru: string) => {
        router.get(
            route('pkp.kegiatan.index'),
            {
                bidang_id: bidangIdBaru || undefined,
                sumber: sumberBaru || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const kegiatanTersaring = React.useMemo(() => {
        const q = kataCari.trim().toLowerCase();
        if (!q) return kegiatan;
        return kegiatan.filter((item) => {
            const kode = item.pos_anggaran?.kode.toLowerCase() ?? '';
            const nama = item.nama_kegiatan.toLowerCase();
            return kode.includes(q) || nama.includes(q);
        });
    }, [kegiatan, kataCari]);

    const formatAturan = (item: KegiatanItem): string => {
        const bagian: string[] = [];
        if (item.sumber_dana) bagian.push(`Sumber: ${item.sumber_dana.kode}`);
        else if (item.jenis_dana) bagian.push(`Dana: ${item.jenis_dana.nama}`);
        if (item.asnaf) bagian.push(`Asnaf: ${item.asnaf.nama}`);
        if (item.program) bagian.push(`Program: ${item.program.nama}`);
        if (item.filter_via_upz === true) bagian.push('Via UPZ');
        if (item.filter_via_upz === false) bagian.push('Langsung');
        if (item.filter_jenis_muzakki) bagian.push(`Muzakki: ${item.filter_jenis_muzakki}`);
        return bagian.length > 0 ? bagian.join(' · ') : '—';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tahun Anggaran & Pemetaan Kegiatan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Tahun Anggaran & Kegiatan Pos RKAT"
                    description="Kelola siklus tahun anggaran serta jembatan pemetaan pos RKAT (L2–L7) ke transaksi bidang operasional (PRC-06)."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            {akses.boleh_buat_tahun && (
                                <Button type="button" variant="outline" className="h-9 gap-1.5" onClick={bukaTambahTahun}>
                                    <Calendar className="h-4 w-4" aria-hidden="true" />
                                    <span>Tambah Tahun</span>
                                </Button>
                            )}
                            {akses.boleh_kelola_kegiatan && (
                                <Button type="button" className="h-9 gap-1.5" onClick={bukaTambahPemetaan}>
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Atur Pemetaan Pos</span>
                                </Button>
                            )}
                        </div>
                    }
                />

                {/* Panel 1: Tahun Anggaran */}
                <section className="bg-card border-border rounded-2xl border p-5 shadow-sm">
                    <div className="mb-3 flex items-center justify-between">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">Siklus Tahun Anggaran</h2>
                            <p className="text-muted-foreground text-xs">
                                Hanya satu tahun anggaran dapat berstatus Berjalan. Tahun berstatus Tutup mengunci perubahan realisasi.
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="py-2.5 pr-3">Tahun</th>
                                    <th className="py-2.5 pr-3">Periode</th>
                                    <th className="py-2.5 pr-3">Status</th>
                                    <th className="py-2.5 pr-3">Catatan</th>
                                    {(akses.boleh_buat_tahun || akses.boleh_status_tahun) && <th className="py-2.5 text-right">Aksi</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-border/70 divide-y">
                                {tahun_anggaran.map((ta) => (
                                    <tr key={ta.id} className="hover:bg-surface-subtle transition-colors">
                                        <td className="py-3 pr-3 font-semibold tabular-nums">{ta.tahun}</td>
                                        <td className="text-muted-foreground py-3 pr-3 text-xs tabular-nums">
                                            {ta.tanggal_mulai ? ta.tanggal_mulai.slice(0, 10) : '-'} s.d.{' '}
                                            {ta.tanggal_selesai ? ta.tanggal_selesai.slice(0, 10) : '-'}
                                        </td>
                                        <td className="py-3 pr-3">{badgeTahun(ta.status)}</td>
                                        <td className="text-muted-foreground max-w-md truncate py-3 pr-3 text-xs">{ta.catatan ?? '—'}</td>
                                        {(akses.boleh_buat_tahun || akses.boleh_status_tahun) && (
                                            <td className="py-3 text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    {akses.boleh_buat_tahun && ta.status !== 'tutup' && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8 px-2.5 text-xs"
                                                            onClick={() => bukaUbahTahun(ta)}
                                                        >
                                                            Ubah
                                                        </Button>
                                                    )}
                                                    {akses.boleh_status_tahun && ta.status !== 'berjalan' && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8 px-2.5 text-xs"
                                                            onClick={() => setTargetStatusTahun({ item: ta, statusBaru: 'berjalan' })}
                                                        >
                                                            Jadikan Berjalan
                                                        </Button>
                                                    )}
                                                    {akses.boleh_status_tahun && ta.status !== 'tutup' && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8 px-2.5 text-xs"
                                                            onClick={() => setTargetStatusTahun({ item: ta, statusBaru: 'tutup' })}
                                                        >
                                                            Tutup Tahun
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {tahun_anggaran.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="text-muted-foreground py-6 text-center text-xs">
                                            Belum ada data tahun anggaran.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* Panel 2: Pemetaan Kegiatan Pos */}
                <section className="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-sm">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">Tabel Kegiatan & Pemetaan Pos RKAT</h2>
                            <p className="text-muted-foreground text-xs">
                                Menampilkan {kegiatanTersaring.length} dari {kegiatan.length} pos daun yang dipetakan ke bidang dan sumber transaksi.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-end gap-2.5">
                            <div className="w-44 space-y-1">
                                <Label htmlFor="filter-bidang-keg" className="text-xs">
                                    Bidang
                                </Label>
                                <Select
                                    value={filter.bidang_id || 'semua'}
                                    onValueChange={(v) => terapkanFilter(v === 'semua' ? '' : v, filter.sumber)}
                                >
                                    <SelectTrigger id="filter-bidang-keg" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="semua">Semua Bidang</SelectItem>
                                        {daftar_bidang.map((b) => (
                                            <SelectItem key={b.id} value={String(b.id)}>
                                                {b.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="w-48 space-y-1">
                                <Label htmlFor="filter-sumber-keg" className="text-xs">
                                    Sumber Transaksi
                                </Label>
                                <Select
                                    value={filter.sumber || 'semua'}
                                    onValueChange={(v) => terapkanFilter(filter.bidang_id, v === 'semua' ? '' : v)}
                                >
                                    <SelectTrigger id="filter-sumber-keg" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="semua">Semua Sumber</SelectItem>
                                        {Object.entries(LABEL_SUMBER).map(([kode, label]) => (
                                            <SelectItem key={kode} value={kode}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="w-56 space-y-1">
                                <Label htmlFor="cari-kegiatan" className="text-xs">
                                    Cari Kode / Kegiatan
                                </Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2" aria-hidden="true" />
                                    <Input
                                        id="cari-kegiatan"
                                        value={kataCari}
                                        onChange={(e) => setKataCari(e.target.value)}
                                        placeholder="Contoh: L2/1.1 atau Fitrah"
                                        className="h-9 pl-8"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="py-2.5 pr-3">Kode Pos</th>
                                    <th className="py-2.5 pr-3">Kegiatan / Uraian</th>
                                    <th className="py-2.5 pr-3">Bidang</th>
                                    <th className="py-2.5 pr-3">Sumber Data</th>
                                    <th className="py-2.5 pr-3">Filter Transaksi</th>
                                    <th className="py-2.5 pr-3">Status</th>
                                    {akses.boleh_kelola_kegiatan && <th className="py-2.5 text-right">Aksi</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-border/70 divide-y">
                                {kegiatanTersaring.map((item) => (
                                    <tr key={item.id} className="hover:bg-surface-subtle transition-colors">
                                        <td className="text-muted-foreground py-2.5 pr-3 font-mono text-xs whitespace-nowrap">
                                            {item.pos_anggaran?.kode ?? '-'}
                                        </td>
                                        <td className="py-2.5 pr-3 font-medium">{item.nama_kegiatan}</td>
                                        <td className="py-2.5 pr-3 text-xs">{item.bidang?.nama ?? '-'}</td>
                                        <td className="py-2.5 pr-3">
                                            <span className="bg-surface-subtle text-foreground inline-flex rounded-md border px-2 py-0.5 text-xs font-medium">
                                                {LABEL_SUMBER[item.sumber_transaksi] ?? item.sumber_transaksi}
                                            </span>
                                        </td>
                                        <td className="text-muted-foreground py-2.5 pr-3 text-xs">{formatAturan(item)}</td>
                                        <td className="py-2.5 pr-3">
                                            <StatusBadge
                                                status={item.aktif ? 'aktif' : 'nonaktif'}
                                                variant={item.aktif ? 'aktif' : 'draft'}
                                                label={item.aktif ? 'Aktif' : 'Nonaktif'}
                                            />
                                        </td>
                                        {akses.boleh_kelola_kegiatan && (
                                            <td className="py-2.5 text-right">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 gap-1 px-2.5 text-xs"
                                                    onClick={() => bukaUbahPemetaan(item)}
                                                >
                                                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                                    <span>Ubah</span>
                                                </Button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {kegiatanTersaring.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="text-muted-foreground py-6 text-center text-xs">
                                            Tidak ada pemetaan pos yang cocok dengan filter pencarian.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {/* Sheet Tahun Anggaran */}
            <Sheet open={sheetTahunTerbuka} onOpenChange={setSheetTahunTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[480px]">
                    <SheetHeader>
                        <SheetTitle>{formTahun.data.id ? 'Ubah Tahun Anggaran' : 'Tambah Tahun Anggaran'}</SheetTitle>
                        <SheetDescription>Pengaturan tahun anggaran dan batas periode aktif.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimTahun} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="ta-tahun">Tahun</Label>
                            <Input
                                id="ta-tahun"
                                type="number"
                                min={2000}
                                max={2100}
                                required
                                value={formTahun.data.tahun}
                                onChange={(e) => formTahun.setData('tahun', e.target.value)}
                                className="h-9"
                            />
                            {formTahun.errors.tahun && <p className="text-destructive text-xs">{formTahun.errors.tahun}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ta-status">Status</Label>
                            <Select value={formTahun.data.status} onValueChange={(val) => formTahun.setData('status', val)}>
                                <SelectTrigger id="ta-status" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="berjalan">Berjalan</SelectItem>
                                    <SelectItem value="tutup">Tutup</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="ta-mulai">Tanggal Mulai</Label>
                                <Input
                                    id="ta-mulai"
                                    type="date"
                                    value={formTahun.data.tanggal_mulai}
                                    onChange={(e) => formTahun.setData('tanggal_mulai', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ta-selesai">Tanggal Selesai</Label>
                                <Input
                                    id="ta-selesai"
                                    type="date"
                                    value={formTahun.data.tanggal_selesai}
                                    onChange={(e) => formTahun.setData('tanggal_selesai', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ta-catatan">Catatan</Label>
                            <Input
                                id="ta-catatan"
                                value={formTahun.data.catatan}
                                onChange={(e) => formTahun.setData('catatan', e.target.value)}
                                placeholder="Keterangan tahun anggaran..."
                                className="h-9"
                            />
                        </div>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" className="h-9" onClick={() => setSheetTahunTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formTahun.processing} className="h-9">
                                {formTahun.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Sheet Pemetaan Pos */}
            <Sheet open={sheetPemetaanTerbuka} onOpenChange={setSheetPemetaanTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[520px]">
                    <SheetHeader>
                        <SheetTitle>Pemetaan Pos RKAT ke Kegiatan</SheetTitle>
                        <SheetDescription>Hubungkan pos daun RKAT ke bidang dan sumber transaksi otomatis atau manual.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimPemetaan} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="map-pos">Pos Anggaran (Daun)</Label>
                            <Select
                                value={formPemetaan.data.pos_anggaran_id}
                                onValueChange={(v) => {
                                    const posDipilih = daftar_pos.find((p) => String(p.id) === v);
                                    formPemetaan.setData((prev) => ({
                                        ...prev,
                                        pos_anggaran_id: v,
                                        nama_kegiatan: prev.nama_kegiatan || posDipilih?.nama || '',
                                    }));
                                }}
                            >
                                <SelectTrigger id="map-pos" className="h-9">
                                    <SelectValue placeholder="Pilih pos RKAT..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {daftar_pos.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.kode} — {p.nama} ({p.tipe_nilai})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="map-nama">Nama Kegiatan</Label>
                            <Input
                                id="map-nama"
                                required
                                value={formPemetaan.data.nama_kegiatan}
                                onChange={(e) => formPemetaan.setData('nama_kegiatan', e.target.value)}
                                className="h-9"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="map-bidang">Bidang Penanggung Jawab</Label>
                                <Select value={formPemetaan.data.bidang_id} onValueChange={(v) => formPemetaan.setData('bidang_id', v)}>
                                    <SelectTrigger id="map-bidang" className="h-9">
                                        <SelectValue placeholder="Pilih bidang" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {daftar_bidang.map((b) => (
                                            <SelectItem key={b.id} value={String(b.id)}>
                                                {b.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="map-sumber">Sumber Transaksi</Label>
                                <Select
                                    value={formPemetaan.data.sumber_transaksi}
                                    onValueChange={(v) => formPemetaan.setData('sumber_transaksi', v)}
                                >
                                    <SelectTrigger id="map-sumber" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(LABEL_SUMBER).map(([kode, label]) => (
                                            <SelectItem key={kode} value={kode}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {formPemetaan.data.sumber_transaksi !== 'manual' && (
                            <div className="bg-surface-subtle border-border space-y-3 rounded-xl border p-3.5">
                                <p className="text-foreground text-xs font-semibold">Parameter Penyaringan Transaksi</p>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1">
                                        <Label htmlFor="map-jd" className="text-xs">
                                            Jenis Dana
                                        </Label>
                                        <Select
                                            value={formPemetaan.data.jenis_dana_id || 'semua'}
                                            onValueChange={(v) => formPemetaan.setData('jenis_dana_id', v === 'semua' ? '' : v)}
                                        >
                                            <SelectTrigger id="map-jd" className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="semua">Tidak difilter</SelectItem>
                                                {daftar_jenis_dana.map((j) => (
                                                    <SelectItem key={j.id} value={String(j.id)}>
                                                        {j.nama}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-1">
                                        <Label htmlFor="map-sd" className="text-xs">
                                            Sumber Dana
                                        </Label>
                                        <Select
                                            value={formPemetaan.data.sumber_dana_id || 'semua'}
                                            onValueChange={(v) => formPemetaan.setData('sumber_dana_id', v === 'semua' ? '' : v)}
                                        >
                                            <SelectTrigger id="map-sd" className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="semua">Tidak difilter</SelectItem>
                                                {daftar_sumber_dana.map((s) => (
                                                    <SelectItem key={s.id} value={String(s.id)}>
                                                        {s.kode} — {s.nama}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {(formPemetaan.data.sumber_transaksi === 'penerimaan' ||
                                    formPemetaan.data.sumber_transaksi === 'penerimaan_orang') && (
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <Label htmlFor="map-upz" className="text-xs">
                                                Jalur UPZ
                                            </Label>
                                            <Select
                                                value={formPemetaan.data.filter_via_upz || 'semua'}
                                                onValueChange={(v) => formPemetaan.setData('filter_via_upz', v === 'semua' ? '' : v)}
                                            >
                                                <SelectTrigger id="map-upz" className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="semua">Semua Jalur</SelectItem>
                                                    <SelectItem value="1">Via UPZ</SelectItem>
                                                    <SelectItem value="0">Langsung (Non-UPZ)</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1">
                                            <Label htmlFor="map-muzakki" className="text-xs">
                                                Jenis Muzakki
                                            </Label>
                                            <Select
                                                value={formPemetaan.data.filter_jenis_muzakki || 'semua'}
                                                onValueChange={(v) => formPemetaan.setData('filter_jenis_muzakki', v === 'semua' ? '' : v)}
                                            >
                                                <SelectTrigger id="map-muzakki" className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="semua">Semua Jenis</SelectItem>
                                                    <SelectItem value="perorangan">Perorangan</SelectItem>
                                                    <SelectItem value="lembaga">Lembaga / Badan</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                )}

                                {(formPemetaan.data.sumber_transaksi === 'penyaluran' ||
                                    formPemetaan.data.sumber_transaksi === 'penyaluran_orang') && (
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <Label htmlFor="map-asnaf" className="text-xs">
                                                Asnaf
                                            </Label>
                                            <Select
                                                value={formPemetaan.data.asnaf_id || 'semua'}
                                                onValueChange={(v) => formPemetaan.setData('asnaf_id', v === 'semua' ? '' : v)}
                                            >
                                                <SelectTrigger id="map-asnaf" className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="semua">Semua Asnaf</SelectItem>
                                                    {daftar_asnaf.map((a) => (
                                                        <SelectItem key={a.id} value={String(a.id)}>
                                                            {a.nama}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1">
                                            <Label htmlFor="map-program" className="text-xs">
                                                Program
                                            </Label>
                                            <Select
                                                value={formPemetaan.data.program_id || 'semua'}
                                                onValueChange={(v) => formPemetaan.setData('program_id', v === 'semua' ? '' : v)}
                                            >
                                                <SelectTrigger id="map-program" className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="semua">Semua Program</SelectItem>
                                                    {daftar_program.map((pr) => (
                                                        <SelectItem key={pr.id} value={String(pr.id)}>
                                                            {pr.nama}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-2.5 pt-1">
                            <Checkbox
                                id="map-aktif"
                                checked={formPemetaan.data.aktif}
                                onCheckedChange={(c) => formPemetaan.setData('aktif', Boolean(c))}
                            />
                            <Label htmlFor="map-aktif" className="font-normal">
                                Pemetaan aktif digunakan dalam perhitungan realisasi
                            </Label>
                        </div>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" className="h-9" onClick={() => setSheetPemetaanTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={!formPemetaan.data.pos_anggaran_id} className="h-9">
                                Simpan Pemetaan
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Konfirmasi Ubah Status Tahun Anggaran */}
            <AlertDialog open={Boolean(targetStatusTahun)} onOpenChange={(open) => !open && setTargetStatusTahun(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {targetStatusTahun?.statusBaru === 'tutup'
                                ? `Tutup Tahun Anggaran ${targetStatusTahun.item.tahun}?`
                                : `Aktifkan Tahun Anggaran ${targetStatusTahun?.item.tahun} sebagai Berjalan?`}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {targetStatusTahun?.statusBaru === 'tutup'
                                ? 'Menutup tahun anggaran akan mengunci seluruh input dan perubahan realisasi manual pada tahun tersebut.'
                                : 'Hanya satu tahun anggaran yang berstatus Berjalan; tahun berjalan sebelumnya akan kembali menjadi Draft.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="space-y-1.5 py-2">
                        <Label htmlFor="alasan-status-ta" className="text-xs">
                            Catatan / Alasan (opsional)
                        </Label>
                        <Input
                            id="alasan-status-ta"
                            value={alasanStatus}
                            onChange={(e) => setAlasanStatus(e.target.value)}
                            placeholder="Alasan perubahan status tahun..."
                            className="h-9"
                        />
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction destructive={targetStatusTahun?.statusBaru === 'tutup'} onClick={konfirmasiUbahStatusTahun}>
                            Lanjutkan
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
