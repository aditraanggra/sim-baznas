import { EmptyState, InputNIK, InputRupiah, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, Eye, Plus, Printer, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Permohonan Bantuan', href: '/distribusi/permohonan' },
];

interface PermohonanRow {
    id: number;
    nomor: string;
    kode_tracking: string;
    tanggal: string | null;
    mustahik: string | null;
    mustahik_kode: string | null;
    pemohon_sama_mustahik: boolean;
    pemohon_nama: string | null;
    pemohon_telepon: string | null;
    pemohon_hubungan: string | null;
    program: string | null;
    sub_bidang: string;
    jenis_dana: string | null;
    asnaf: string | null;
    nominal_diajukan: number;
    nominal_disetujui: number | null;
    status_kode: string;
    langkah_sekarang: number;
    kesiapan_keuangan: string;
}

type PermohonanFormData = {
    tanggal: string;
    mustahik_id: string;
    pemohon_sama_mustahik: boolean;
    pemohon_nama: string;
    pemohon_telepon: string;
    pemohon_hubungan: string;
    pemohon_alamat: string;
    program_id: string;
    jenis_dana_id: string;
    asnaf_id: string;
    nominal_diajukan: number;
    uraian: string;
};

type MustahikCepatFormData = {
    jenis: string;
    nama: string;
    nik: string;
    telepon: string;
    desa_id: string;
    alamat: string;
    asnaf_ids: number[];
};

interface Props {
    permohonan: { data: PermohonanRow[] };
    filter: { cari: string; status: string };
    mustahik_list: { id: number; kode: string; nama: string; nik_masked: string | null; telepon: string | null }[];
    desa_list: { id: number; nama: string; kecamatan: string | null }[];
    program_list: { id: number; kode: string; nama: string; sub_bidang: string | null }[];
    jenis_dana_list: { id: number; kode: string; nama: string; wajib_asnaf: boolean }[];
    asnaf_list: { id: number; kode: string; nama: string }[];
    bisa_kelola: boolean;
}

const OPSI_HUBUNGAN_PEMOHON = ['Keluarga', 'Pengurus DKM / RT / RW', 'Pendamping / Lembaga', 'Tetangga / Kerabat', 'Lainnya'];

export default function PermohonanIndex({
    permohonan,
    filter,
    mustahik_list,
    desa_list,
    program_list,
    jenis_dana_list,
    asnaf_list,
    bisa_kelola,
}: Props) {
    const { flash } = usePage<{ flash?: { mustahik_baru_id?: number | null } }>().props;
    const [cari, setCari] = React.useState(filter.cari);
    const [status, setStatus] = React.useState(filter.status || 'semua');
    const [dialogBuka, setDialogBuka] = React.useState(false);
    const [dialogMustahik, setDialogMustahik] = React.useState(false);
    const lastMustahikBaruRef = React.useRef<number | null>(null);

    const form = useForm<PermohonanFormData>({
        tanggal: new Date().toISOString().slice(0, 10),
        mustahik_id: '',
        pemohon_sama_mustahik: true,
        pemohon_nama: '',
        pemohon_telepon: '',
        pemohon_hubungan: 'Keluarga',
        pemohon_alamat: '',
        program_id: program_list[0] ? String(program_list[0].id) : '',
        jenis_dana_id: jenis_dana_list[0] ? String(jenis_dana_list[0].id) : '',
        asnaf_id: asnaf_list[0] ? String(asnaf_list[0].id) : '',
        nominal_diajukan: 0,
        uraian: '',
    });

    const formMustahik = useForm<MustahikCepatFormData>({
        jenis: 'perorangan',
        nama: '',
        nik: '',
        telepon: '',
        desa_id: '',
        alamat: '',
        asnaf_ids: [],
    });

    const setPermohonanData = form.setData;

    React.useEffect(() => {
        if (flash?.mustahik_baru_id && flash.mustahik_baru_id !== lastMustahikBaruRef.current) {
            lastMustahikBaruRef.current = flash.mustahik_baru_id;
            setPermohonanData('mustahik_id', String(flash.mustahik_baru_id));
        }
    }, [flash?.mustahik_baru_id, setPermohonanData]);

    const jenisTerpilih = jenis_dana_list.find((j) => String(j.id) === form.data.jenis_dana_id);
    const wajibAsnaf = Boolean(jenisTerpilih?.wajib_asnaf);

    React.useEffect(() => {
        if (!wajibAsnaf && form.data.asnaf_id !== '') {
            setPermohonanData('asnaf_id', '');
        }
    }, [wajibAsnaf, form.data.asnaf_id, setPermohonanData]);

    const opsiMustahik = React.useMemo(
        () =>
            mustahik_list.map((m) => ({
                value: String(m.id),
                label: m.nama,
                sublabel: [m.kode, m.nik_masked, m.telepon].filter(Boolean).join(' · '),
            })),
        [mustahik_list],
    );

    const opsiDesa = React.useMemo(
        () => desa_list.map((d) => ({ value: String(d.id), label: d.nama, sublabel: d.kecamatan ? `Kec. ${d.kecamatan}` : undefined })),
        [desa_list],
    );

    const terapkanFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('distribusi.permohonan.index'), { cari, status: status === 'semua' ? '' : status }, { preserveState: true });
    };

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('distribusi.permohonan.store'), {
            preserveScroll: true,
            onSuccess: () => setDialogBuka(false),
        });
    };

    const simpanMustahikCepat = (e: React.FormEvent) => {
        e.preventDefault();
        formMustahik.post(route('distribusi.mustahik.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setDialogMustahik(false);
                formMustahik.reset();
            },
        });
    };

    const toggleAsnafCepat = (id: number) => {
        const saatIni = formMustahik.data.asnaf_ids;
        const baru = saatIni.includes(id) ? saatIni.filter((x) => x !== id) : [...saatIni, id];
        formMustahik.setData('asnaf_ids', baru);
    };

    const daftarGalat = Object.entries(form.errors as Record<string, string>).filter(([, v]) => Boolean(v));

    const kolom: DataTableColumn<PermohonanRow>[] = [
        {
            id: 'nomor',
            header: 'Permohonan',
            cell: (r) => (
                <div>
                    <Link href={route('distribusi.permohonan.show', r.id)} className="text-primary font-medium hover:underline">
                        {r.nomor}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {r.tanggal} · Lacak: {r.kode_tracking}
                    </p>
                </div>
            ),
        },
        {
            id: 'mustahik',
            header: 'Penerima Manfaat & Pemohon',
            cell: (r) => (
                <div>
                    <p className="text-foreground font-medium">{r.mustahik ?? '-'}</p>
                    {!r.pemohon_sama_mustahik && r.pemohon_nama ? (
                        <p className="text-muted-foreground text-xs">
                            Pemohon: {r.pemohon_nama} ({r.pemohon_hubungan || 'Perwakilan'})
                        </p>
                    ) : (
                        <p className="text-muted-foreground text-xs">{r.mustahik_kode ?? 'Diri sendiri'}</p>
                    )}
                </div>
            ),
        },
        {
            id: 'program',
            header: 'Program & Dana',
            cell: (r) => (
                <div>
                    <p className="text-foreground text-sm">{r.program ?? '-'}</p>
                    <p className="text-muted-foreground text-xs">
                        {r.jenis_dana ?? '-'}
                        {r.asnaf ? ` (${r.asnaf})` : ''}
                    </p>
                </div>
            ),
        },
        {
            id: 'nominal',
            header: 'Nominal',
            align: 'right',
            cell: (r) => (
                <div className="text-right tabular-nums">
                    <p className="font-medium">{formatRupiah(r.nominal_disetujui ?? r.nominal_diajukan)}</p>
                    {r.nominal_disetujui && <p className="text-muted-foreground text-xs">ACC</p>}
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Tahap (K8)',
            cell: (r) => (
                <StatusBadge
                    status={r.status_kode}
                    variant={r.status_kode === 'diterima_mustahik' ? 'success' : r.status_kode === 'ditolak' ? 'ditolak' : 'aktif'}
                    label={r.langkah_sekarang > 0 ? `L${r.langkah_sekarang}: ${r.status_kode.replaceAll('_', ' ')}` : 'Ditolak'}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (r) => (
                <div className="flex flex-wrap justify-end gap-1.5">
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                        <a href={route('distribusi.permohonan.tanda-terima', r.id)} target="_blank" rel="noopener noreferrer">
                            <Printer className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Bukti Lacak</span>
                        </a>
                    </Button>
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                        <Link href={route('distribusi.permohonan.show', r.id)}>
                            <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Detail</span>
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permohonan Bantuan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Permohonan Bantuan Mustahik"
                    description="Kelola alur disposisi 7 langkah mulai dari Pelayanan Mustahik hingga diterima penerima manfaat."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola && (
                            <Button
                                type="button"
                                className="h-9 gap-1.5"
                                onClick={() => {
                                    form.clearErrors();
                                    setDialogBuka(true);
                                }}
                            >
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Catat Permohonan</span>
                            </Button>
                        )
                    }
                />

                <form onSubmit={terapkanFilter} className="bg-card border-border grid gap-3 rounded-2xl border p-4 sm:grid-cols-4 sm:items-end">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="cari-permohonan">Cari Nomor, Kode Lacak, Mustahik, atau Pemohon</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-permohonan"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Nomor PRM, TRK, atau nama..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-status-perm">Tahap Permohonan</Label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger id="filter-status-perm" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Tahap</SelectItem>
                                <SelectItem value="diajukan">1. Diajukan</SelectItem>
                                <SelectItem value="disposisi_ketua">2. Disposisi Ketua</SelectItem>
                                <SelectItem value="asesmen_kabag">3. Asesmen Kabag</SelectItem>
                                <SelectItem value="ajuan_waka_2">4. Ajuan Waka II</SelectItem>
                                <SelectItem value="acc_ketua">5. ACC Ketua</SelectItem>
                                <SelectItem value="minta_keuangan">6. Minta Keuangan</SelectItem>
                                <SelectItem value="diterima_mustahik">7. Diterima Mustahik</SelectItem>
                                <SelectItem value="ditolak">Ditolak</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" variant="outline" className="h-9">
                        Terapkan
                    </Button>
                </form>

                {permohonan.data.length === 0 ? (
                    <EmptyState title="Belum Ada Permohonan" description="Catat permohonan baru untuk memulai alur disposisi." />
                ) : (
                    <DataTable data={permohonan.data} columns={kolom} keyExtractor={(r) => r.id} />
                )}

                <Dialog open={dialogBuka} onOpenChange={setDialogBuka}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>Catat Permohonan Bantuan</DialogTitle>
                            <DialogDescription>Permohonan otomatis memperoleh nomor agenda dan kode tracking publik.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={simpan} className="space-y-4">
                            {daftarGalat.length > 0 && (
                                <div role="alert" className="bg-destructive-soft border-destructive/30 text-destructive rounded-lg border p-3 text-xs">
                                    <div className="flex items-center gap-1.5 font-semibold">
                                        <AlertCircle className="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <span>Periksa isian yang belum valid:</span>
                                    </div>
                                    <ul className="mt-1 list-disc space-y-0.5 pl-5">
                                        {daftarGalat.map(([kunci, pesan]) => (
                                            <li key={kunci}>{pesan}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-tanggal">Tanggal</Label>
                                    <Input
                                        id="p-tanggal"
                                        type="date"
                                        value={form.data.tanggal}
                                        onChange={(e) => form.setData('tanggal', e.target.value)}
                                        className="h-9"
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-nominal">Nominal Diajukan</Label>
                                    <InputRupiah
                                        id="p-nominal"
                                        value={form.data.nominal_diajukan}
                                        onChange={(v) => form.setData('nominal_diajukan', v)}
                                    />
                                    {form.errors.nominal_diajukan && <p className="text-destructive text-xs">{form.errors.nominal_diajukan}</p>}
                                </div>
                            </div>

                            <div className="border-border space-y-2.5 rounded-xl border p-3.5">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="p-mustahik" className="font-semibold">
                                        1. Penerima Manfaat (Mustahik)
                                    </Label>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            formMustahik.clearErrors();
                                            setDialogMustahik(true);
                                        }}
                                        className="text-primary text-xs font-medium hover:underline"
                                    >
                                        + Tambah Mustahik Baru
                                    </button>
                                </div>
                                <Combobox
                                    id="p-mustahik"
                                    value={form.data.mustahik_id}
                                    onChange={(v) => form.setData('mustahik_id', v)}
                                    placeholder="Cari nama, kode, NIK, atau telepon mustahik..."
                                    createNewLabel="Tambah mustahik baru"
                                    onCreateNew={(q) => {
                                        formMustahik.clearErrors();
                                        formMustahik.setData('nama', q);
                                        setDialogMustahik(true);
                                    }}
                                    options={opsiMustahik}
                                />
                                {form.errors.mustahik_id && <p className="text-destructive text-xs">{form.errors.mustahik_id}</p>}
                            </div>

                            <div className="border-border space-y-3 rounded-xl border p-3.5">
                                <p className="text-foreground text-sm font-semibold">2. Data Pemohon</p>
                                <label className="flex items-center gap-2 text-xs font-medium">
                                    <Checkbox
                                        checked={form.data.pemohon_sama_mustahik}
                                        onCheckedChange={(checked) => form.setData('pemohon_sama_mustahik', Boolean(checked))}
                                    />
                                    <span>Pemohon sama dengan Penerima Manfaat (Mustahik)</span>
                                </label>

                                {!form.data.pemohon_sama_mustahik && (
                                    <div className="grid gap-3 pt-1 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="p-pemohon-nama">Nama Pemohon (wajib)</Label>
                                            <Input
                                                id="p-pemohon-nama"
                                                value={form.data.pemohon_nama}
                                                onChange={(e) => form.setData('pemohon_nama', e.target.value)}
                                                placeholder="Nama pengaju / perwakilan"
                                                className="h-9"
                                                required
                                            />
                                            {form.errors.pemohon_nama && <p className="text-destructive text-xs">{form.errors.pemohon_nama}</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="p-pemohon-telp">No. Telepon Pemohon (wajib)</Label>
                                            <Input
                                                id="p-pemohon-telp"
                                                value={form.data.pemohon_telepon}
                                                onChange={(e) => form.setData('pemohon_telepon', e.target.value)}
                                                placeholder="08..."
                                                className="h-9"
                                                required
                                            />
                                            {form.errors.pemohon_telepon && <p className="text-destructive text-xs">{form.errors.pemohon_telepon}</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="p-pemohon-hubungan">Hubungan dengan Mustahik</Label>
                                            <Select
                                                value={form.data.pemohon_hubungan}
                                                onValueChange={(v) => form.setData('pemohon_hubungan', v)}
                                            >
                                                <SelectTrigger id="p-pemohon-hubungan" className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {OPSI_HUBUNGAN_PEMOHON.map((hub) => (
                                                        <SelectItem key={hub} value={hub}>
                                                            {hub}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="p-pemohon-alamat">Alamat Pemohon</Label>
                                            <Input
                                                id="p-pemohon-alamat"
                                                value={form.data.pemohon_alamat}
                                                onChange={(e) => form.setData('pemohon_alamat', e.target.value)}
                                                placeholder="Alamat singkat pemohon"
                                                className="h-9"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="p-program">Program Bantuan</Label>
                                <Select value={form.data.program_id} onValueChange={(v) => form.setData('program_id', v)}>
                                    <SelectTrigger id="p-program" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {program_list.map((pr) => (
                                            <SelectItem key={pr.id} value={String(pr.id)}>
                                                {pr.nama} ({pr.sub_bidang})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-jenis-dana">Rencana Jenis Dana</Label>
                                    <Select value={form.data.jenis_dana_id} onValueChange={(v) => form.setData('jenis_dana_id', v)}>
                                        <SelectTrigger id="p-jenis-dana" className="h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {jenis_dana_list.map((j) => (
                                                <SelectItem key={j.id} value={String(j.id)}>
                                                    {j.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="p-asnaf">Asnaf {wajibAsnaf ? '(Wajib Zakat)' : '(Tidak Berlaku)'}</Label>
                                    <Select
                                        value={form.data.asnaf_id}
                                        onValueChange={(v) => form.setData('asnaf_id', v)}
                                        disabled={!wajibAsnaf}
                                    >
                                        <SelectTrigger id="p-asnaf" className="h-9">
                                            <SelectValue placeholder={wajibAsnaf ? 'Pilih asnaf' : 'Tanpa asnaf'} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {asnaf_list.map((a) => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.asnaf_id && <p className="text-destructive text-xs">{form.errors.asnaf_id}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="p-uraian">Uraian Kebutuhan Bantuan</Label>
                                <Input
                                    id="p-uraian"
                                    value={form.data.uraian}
                                    onChange={(e) => form.setData('uraian', e.target.value)}
                                    placeholder="Keperluan pengobatan, modal usaha, atau pendidikan..."
                                    className="h-9"
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setDialogBuka(false)} className="h-9">
                                    Batal
                                </Button>
                                <Button type="submit" disabled={form.processing} className="h-9">
                                    {form.processing ? 'Menyimpan...' : 'Simpan Permohonan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog open={dialogMustahik} onOpenChange={setDialogMustahik}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Tambah Cepat Mustahik</DialogTitle>
                            <DialogDescription>Data mustahik baru langsung tersimpan dan terpilih di form permohonan.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={simpanMustahikCepat} className="space-y-3">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="mc-jenis">Jenis Mustahik</Label>
                                    <Select value={formMustahik.data.jenis} onValueChange={(v) => formMustahik.setData('jenis', v)}>
                                        <SelectTrigger id="mc-jenis" className="h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="perorangan">Perorangan</SelectItem>
                                            <SelectItem value="lembaga">Lembaga / Yayasan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="mc-nama">Nama Lengkap / Lembaga (wajib)</Label>
                                    <Input
                                        id="mc-nama"
                                        required
                                        value={formMustahik.data.nama}
                                        onChange={(e) => formMustahik.setData('nama', e.target.value)}
                                        className="h-9"
                                    />
                                    {formMustahik.errors.nama && <p className="text-destructive text-xs">{formMustahik.errors.nama}</p>}
                                </div>
                            </div>

                            {formMustahik.data.jenis === 'perorangan' && (
                                <InputNIK
                                    id="mc-nik"
                                    value={formMustahik.data.nik}
                                    onChange={(nik) => formMustahik.setData('nik', nik)}
                                    error={formMustahik.errors.nik}
                                />
                            )}

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="mc-telp">Telepon</Label>
                                    <Input
                                        id="mc-telp"
                                        value={formMustahik.data.telepon}
                                        onChange={(e) => formMustahik.setData('telepon', e.target.value)}
                                        placeholder="08..."
                                        className="h-9"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="mc-desa">Desa / Kelurahan</Label>
                                    <Combobox
                                        id="mc-desa"
                                        options={opsiDesa}
                                        value={formMustahik.data.desa_id}
                                        onChange={(v) => formMustahik.setData('desa_id', v)}
                                        placeholder="Pilih desa..."
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="mc-alamat">Alamat Lengkap</Label>
                                <Input
                                    id="mc-alamat"
                                    value={formMustahik.data.alamat}
                                    onChange={(e) => formMustahik.setData('alamat', e.target.value)}
                                    placeholder="Kp. / Jl. / RT / RW..."
                                    className="h-9"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label>Kategori Asnaf (Opsional)</Label>
                                <div className="grid grid-cols-2 gap-2 pt-1 sm:grid-cols-4">
                                    {asnaf_list.map((a) => (
                                        <label key={a.id} className="flex items-center gap-1.5 text-xs">
                                            <Checkbox
                                                checked={formMustahik.data.asnaf_ids.includes(a.id)}
                                                onCheckedChange={() => toggleAsnafCepat(a.id)}
                                            />
                                            <span>{a.nama}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setDialogMustahik(false)} className="h-9">
                                    Batal
                                </Button>
                                <Button type="submit" disabled={formMustahik.processing} className="h-9">
                                    {formMustahik.processing ? 'Menyimpan...' : 'Simpan Mustahik'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
