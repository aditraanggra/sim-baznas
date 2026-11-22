import { EmptyState, InputNIK, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Data Mustahik', href: '/distribusi/mustahik' },
];

interface MustahikRow {
    id: number;
    kode: string;
    jenis: string;
    nama: string;
    nik_masked: string | null;
    telepon: string | null;
    wilayah: string;
    asnaf: string[];
    status_verifikasi: string;
    aktif: boolean;
}

type MustahikFormData = {
    jenis: string;
    nama: string;
    nik: string;
    telepon: string;
    desa_id: string;
    alamat: string;
    penanggung_jawab: string;
    asnaf_ids: number[];
};

interface Props {
    mustahik: { data: MustahikRow[]; current_page: number; last_page: number; total: number };
    filter: { cari: string; verifikasi: string };
    asnaf_list: { id: number; kode: string; nama: string }[];
    desa_list: { id: number; nama: string; kecamatan: string | null }[];
    bisa_kelola: boolean;
}

export default function MustahikIndex({ mustahik, filter, asnaf_list, desa_list, bisa_kelola }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [verifikasi, setVerifikasi] = React.useState(filter.verifikasi || 'semua');
    const [dialogBuka, setDialogBuka] = React.useState(false);
    const [peringatanDuplikat, setPeringatanDuplikat] = React.useState<{ id: number; kode: string; nama: string; alasan: string }[]>([]);

    const form = useForm<MustahikFormData>({
        jenis: 'perorangan',
        nama: '',
        nik: '',
        telepon: '',
        desa_id: '',
        alamat: '',
        penanggung_jawab: '',
        asnaf_ids: [],
    });

    const terapkanFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            route('distribusi.mustahik.index'),
            { cari, verifikasi: verifikasi === 'semua' ? '' : verifikasi },
            { preserveState: true },
        );
    };

    const cekDuplikat = async (nikOverride?: string) => {
        const nikCek = nikOverride ?? form.data.nik;
        if (!nikCek && !form.data.telepon) {
            setPeringatanDuplikat([]);
            return;
        }
        try {
            const url = route('distribusi.mustahik.cek-duplikat', { nik: nikCek, telepon: form.data.telepon });
            const res = await fetch(url);
            if (res.ok) {
                const json = await res.json();
                setPeringatanDuplikat(json.duplikat ?? []);
            }
        } catch {
            // Abaikan kegagalan pratinjau duplikat
        }
    };

    const toggleAsnaf = (id: number) => {
        const saatIni = form.data.asnaf_ids;
        const baru = saatIni.includes(id) ? saatIni.filter((x) => x !== id) : [...saatIni, id];
        form.setData('asnaf_ids', baru);
    };

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('distribusi.mustahik.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setDialogBuka(false);
                form.reset();
                setPeringatanDuplikat([]);
            },
        });
    };

    const kolom: DataTableColumn<MustahikRow>[] = [
        {
            id: 'nama',
            header: 'Mustahik',
            cell: (r) => (
                <div>
                    <Link href={route('distribusi.mustahik.show', r.id)} className="text-primary font-medium hover:underline">
                        {r.nama}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {r.kode} · {r.jenis === 'lembaga' ? 'Lembaga' : 'Perorangan'}
                    </p>
                </div>
            ),
        },
        { id: 'nik', header: 'NIK', cell: (r) => r.nik_masked ?? '-' },
        { id: 'telepon', header: 'Telepon', cell: (r) => r.telepon ?? '-' },
        { id: 'wilayah', header: 'Wilayah', cell: (r) => r.wilayah || '-' },
        { id: 'asnaf', header: 'Asnaf', cell: (r) => (r.asnaf.length ? r.asnaf.join(', ') : '-') },
        {
            id: 'status',
            header: 'Verifikasi',
            cell: (r) => (
                <StatusBadge
                    status={r.status_verifikasi}
                    variant={r.status_verifikasi === 'terverifikasi' ? 'success' : r.status_verifikasi === 'ditolak' ? 'ditolak' : 'warning'}
                    label={r.status_verifikasi}
                />
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Mustahik" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Data Mustahik"
                    description="Basis data penerima manfaat (perorangan atau lembaga) dengan enkripsi NIK dan multi-asnaf."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola && (
                            <Button type="button" className="h-9 gap-1.5" onClick={() => setDialogBuka(true)}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Tambah Mustahik</span>
                            </Button>
                        )
                    }
                />

                <form onSubmit={terapkanFilter} className="bg-card border-border grid gap-3 rounded-2xl border p-4 sm:grid-cols-4 sm:items-end">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="cari-mustahik">Cari Nama, Kode, atau Telepon</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-mustahik"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Nama mustahik atau kode..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-verifikasi">Status Verifikasi</Label>
                        <Select value={verifikasi} onValueChange={setVerifikasi}>
                            <SelectTrigger id="filter-verifikasi" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="menunggu">Menunggu</SelectItem>
                                <SelectItem value="terverifikasi">Terverifikasi</SelectItem>
                                <SelectItem value="ditolak">Ditolak</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" variant="outline" className="h-9">
                        Terapkan
                    </Button>
                </form>

                {mustahik.data.length === 0 ? (
                    <EmptyState title="Belum Ada Data Mustahik" description="Tambahkan data mustahik baru untuk mencatat permohonan dan penyaluran." />
                ) : (
                    <DataTable data={mustahik.data} columns={kolom} keyExtractor={(r) => r.id} />
                )}

                <Dialog open={dialogBuka} onOpenChange={setDialogBuka}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Tambah Mustahik Baru</DialogTitle>
                            <DialogDescription>NIK disimpan terenkripsi dengan pengecekan duplikat otomatis.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={simpan} className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="m-jenis">Jenis Mustahik</Label>
                                    <Select value={form.data.jenis} onValueChange={(v) => form.setData('jenis', v)}>
                                        <SelectTrigger id="m-jenis">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="perorangan">Perorangan</SelectItem>
                                            <SelectItem value="lembaga">Lembaga / Yayasan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="m-nama">Nama Lengkap / Lembaga</Label>
                                    <Input
                                        id="m-nama"
                                        value={form.data.nama}
                                        onChange={(e) => form.setData('nama', e.target.value)}
                                        required
                                    />
                                    {form.errors.nama && <p className="text-destructive text-xs">{form.errors.nama}</p>}
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="m-nik">NIK (16 Digit, Opsional Lembaga)</Label>
                                    <InputNIK
                                        id="m-nik"
                                        value={form.data.nik}
                                        onChange={(val) => {
                                            form.setData('nik', val);
                                            if (val.length === 16) void cekDuplikat(val);
                                        }}
                                    />
                                    {form.errors.nik && <p className="text-destructive text-xs">{form.errors.nik}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="m-telepon">Nomor Telepon / HP</Label>
                                    <Input
                                        id="m-telepon"
                                        value={form.data.telepon}
                                        onChange={(e) => form.setData('telepon', e.target.value)}
                                        onBlur={() => void cekDuplikat()}
                                        placeholder="08..."
                                    />
                                </div>
                            </div>

                            {peringatanDuplikat.length > 0 && (
                                <div className="border-warning/40 bg-warning/10 text-foreground rounded-lg border p-3 text-xs">
                                    <p className="font-semibold">Kemungkinan data duplikat ditemukan:</p>
                                    <ul className="mt-1 list-disc pl-4">
                                        {peringatanDuplikat.map((d) => (
                                            <li key={d.id}>
                                                {d.nama} ({d.kode}) - {d.alasan}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {form.data.jenis === 'lembaga' && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="m-pj">Nama Penanggung Jawab</Label>
                                    <Input
                                        id="m-pj"
                                        value={form.data.penanggung_jawab}
                                        onChange={(e) => form.setData('penanggung_jawab', e.target.value)}
                                    />
                                </div>
                            )}

                            <div className="space-y-1.5">
                                <Label htmlFor="m-desa">Desa / Kelurahan (Cianjur)</Label>
                                <Combobox
                                    value={form.data.desa_id}
                                    onChange={(v) => form.setData('desa_id', v)}
                                    placeholder="Pilih desa..."
                                    options={desa_list.map((d) => ({
                                        value: String(d.id),
                                        label: `${d.nama}${d.kecamatan ? ` (Kec. ${d.kecamatan})` : ''}`,
                                    }))}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="m-alamat">Alamat Lengkap</Label>
                                <Input id="m-alamat" value={form.data.alamat} onChange={(e) => form.setData('alamat', e.target.value)} />
                            </div>

                            <div className="space-y-2">
                                <Label>Kategori Asnaf (Dapat Lebih dari Satu)</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    {asnaf_list.map((a) => (
                                        <label key={a.id} htmlFor={`asnaf-${a.id}`} className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                id={`asnaf-${a.id}`}
                                                checked={form.data.asnaf_ids.includes(a.id)}
                                                onCheckedChange={() => toggleAsnaf(a.id)}
                                            />
                                            <span>{a.nama}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setDialogBuka(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Menyimpan...' : 'Simpan Mustahik'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
