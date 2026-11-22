import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Pencil, Plus, Power, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDM, Administrasi & Umum', href: '/umum' },
    { title: 'Data UPZ & SK', href: '/umum/upz' },
];

interface UpzItem {
    id: number;
    kode: string;
    nama: string;
    nomor_sk: string | null;
    jenis_upz_id: number;
    jenis_upz: string | null;
    desa_id: number;
    desa: string | null;
    kecamatan: string | null;
    ketua: string;
    sekretaris: string | null;
    bendahara: string | null;
    alamat: string;
    pic_nama: string;
    pic_telepon: string;
    aktif: boolean;
    sk_nomor: string | null;
    sk_berlaku_sampai: string | null;
    sk_sisa_hari: number | null;
    sk_status: string;
}

interface Props {
    upz_list: UpzItem[];
    jenis_upz: { id: number; kode: string; nama: string }[];
    desa_list: { id: number; nama: string; kecamatan: string | null }[];
    filter: { cari: string; jenis_upz_id: number | null };
    bisa_kelola: boolean;
}

export default function UpzIndex({ upz_list, jenis_upz, desa_list, filter, bisa_kelola }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);

    const form = useForm({
        id: '',
        kode: '',
        nama: '',
        nomor_sk: '',
        jenis_upz_id: '',
        desa_id: '',
        ketua: '',
        sekretaris: '',
        bendahara: '',
        alamat: '',
        pic_nama: '',
        pic_telepon: '',
    });

    const opsiDesa = React.useMemo(
        () =>
            desa_list.map((d) => ({
                value: String(d.id),
                label: d.nama,
                sublabel: d.kecamatan ? `Kec. ${d.kecamatan}` : undefined,
            })),
        [desa_list],
    );

    const bukaTambah = () => {
        form.clearErrors();
        form.setData({
            id: '',
            kode: '',
            nama: '',
            nomor_sk: '',
            jenis_upz_id: jenis_upz[0] ? String(jenis_upz[0].id) : '',
            desa_id: '',
            ketua: '',
            sekretaris: '',
            bendahara: '',
            alamat: '',
            pic_nama: '',
            pic_telepon: '',
        });
        setDrawerTerbuka(true);
    };

    const bukaUbah = (u: UpzItem) => {
        form.clearErrors();
        form.setData({
            id: String(u.id),
            kode: u.kode,
            nama: u.nama,
            nomor_sk: u.nomor_sk ?? u.sk_nomor ?? '',
            jenis_upz_id: String(u.jenis_upz_id),
            desa_id: String(u.desa_id),
            ketua: u.ketua,
            sekretaris: u.sekretaris ?? '',
            bendahara: u.bendahara ?? '',
            alamat: u.alamat,
            pic_nama: u.pic_nama,
            pic_telepon: u.pic_telepon,
        });
        setDrawerTerbuka(true);
    };

    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('umum.upz.store'), {
            preserveScroll: true,
            onSuccess: () => setDrawerTerbuka(false),
        });
    };

    const labelStatusSk = (u: UpzItem) => {
        if (u.sk_status === 'aktif') return <StatusBadge status="aktif" variant="aktif" label="SK Aktif" />;
        if (u.sk_status === 'segera_habis') {
            return <StatusBadge status="warning" variant="warning" label={`Segera Habis (${u.sk_sisa_hari} hari)`} />;
        }
        if (u.sk_status === 'habis') return <StatusBadge status="habis" variant="habis" label="SK Habis" />;
        return <StatusBadge status="draft" variant="draft" label="Belum Ada SK" />;
    };

    const kolom: DataTableColumn<UpzItem>[] = [
        {
            id: 'nama',
            header: 'Nama UPZ',
            sortable: true,
            sortValue: (u) => u.nama,
            cell: (u) => (
                <div>
                    <Link href={route('umum.upz.show', u.id)} className="text-foreground hover:text-primary font-medium hover:underline">
                        {u.nama}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {u.kode} · {u.jenis_upz ?? '-'}
                    </p>
                </div>
            ),
        },
        {
            id: 'wilayah',
            header: 'Wilayah',
            cell: (u) => (
                <div className="text-xs">
                    <p className="text-foreground">{u.desa ?? '-'}</p>
                    <p className="text-muted-foreground">Kec. {u.kecamatan ?? '-'}</p>
                </div>
            ),
        },
        {
            id: 'pengurus',
            header: 'Ketua & PIC',
            cell: (u) => (
                <div className="text-xs">
                    <p className="text-foreground">Ketua: {u.ketua}</p>
                    <p className="text-muted-foreground">
                        PIC: {u.pic_nama} ({u.pic_telepon})
                    </p>
                </div>
            ),
        },
        {
            id: 'nomor_sk',
            header: 'Nomor SK',
            sortable: true,
            sortValue: (u) => u.nomor_sk ?? u.sk_nomor ?? '',
            cell: (u) => <span className="text-foreground text-xs font-medium">{u.nomor_sk ?? u.sk_nomor ?? '-'}</span>,
        },
        {
            id: 'sk',
            header: 'Status SK',
            cell: (u) => <div className="space-y-1">{labelStatusSk(u)}</div>,
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (u) => (
                <div className="flex justify-end gap-1.5">
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                        <Link href={route('umum.upz.show', u.id)}>
                            <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Detail</span>
                        </Link>
                    </Button>
                    {bisa_kelola && (
                        <>
                            <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbah(u)}>
                                <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                <span>Ubah</span>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="h-8 gap-1 px-2.5 text-xs"
                                onClick={() => router.post(route('umum.upz.toggle-aktif'), { id: u.id }, { preserveScroll: true })}
                            >
                                <Power className="h-3.5 w-3.5" aria-hidden="true" />
                                <span>{u.aktif ? 'Nonaktif' : 'Aktifkan'}</span>
                            </Button>
                        </>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data UPZ & SK UPZ" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Unit Pengumpul Zakat (UPZ)"
                    description="Kelola database UPZ, pengurus, penanggung jawab wilayah, serta pemantauan masa berlaku SK UPZ."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('umum.upz.laporan-setoran')}>Laporan Setoran UPZ</Link>
                            </Button>
                            {bisa_kelola && (
                                <Button type="button" onClick={bukaTambah} className="h-9 gap-1.5">
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Tambah UPZ</span>
                                </Button>
                            )}
                        </>
                    }
                />

                <form
                    className="bg-card border-border flex flex-wrap items-end gap-3 rounded-xl border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            route('umum.upz.index'),
                            { cari, jenis_upz_id: filter.jenis_upz_id ?? '' },
                            { preserveState: true },
                        );
                    }}
                >
                    <div className="min-w-[220px] flex-1 space-y-1.5">
                        <Label htmlFor="cari-upz">Cari Nama, Kode, atau Ketua UPZ</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2" aria-hidden="true" />
                            <Input id="cari-upz" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Ketik nama UPZ..." className="h-9 pl-8" />
                        </div>
                    </div>

                    <div className="w-full space-y-1.5 sm:w-56">
                        <Label htmlFor="filter-jenis-upz">Jenis UPZ</Label>
                        <Select
                            value={filter.jenis_upz_id ? String(filter.jenis_upz_id) : 'semua'}
                            onValueChange={(v) =>
                                router.get(
                                    route('umum.upz.index'),
                                    { cari, jenis_upz_id: v === 'semua' ? '' : v },
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger id="filter-jenis-upz" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Jenis UPZ</SelectItem>
                                {jenis_upz.map((j) => (
                                    <SelectItem key={j.id} value={String(j.id)}>
                                        {j.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Button type="submit" variant="outline" className="h-9">
                        Terapkan
                    </Button>
                </form>

                <DataTable
                    columns={kolom}
                    data={upz_list}
                    keyExtractor={(u) => u.id}
                    emptyMessage="Belum ada data UPZ terdaftar."
                />
            </div>

            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[540px]">
                    <SheetHeader>
                        <SheetTitle>{form.data.id ? 'Ubah Data UPZ' : 'Tambah UPZ Baru'}</SheetTitle>
                        <SheetDescription>Lengkapi profil pengurus dan kontak penanggung jawab UPZ.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirim} className="mt-5 space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-jenis">Jenis UPZ (wajib)</Label>
                                <Select value={form.data.jenis_upz_id} onValueChange={(v) => form.setData('jenis_upz_id', v)}>
                                    <SelectTrigger id="upz-jenis" className="h-9">
                                        <SelectValue placeholder="Pilih jenis UPZ" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jenis_upz.map((j) => (
                                            <SelectItem key={j.id} value={String(j.id)}>
                                                {j.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.jenis_upz_id && <p className="text-destructive text-xs">{form.errors.jenis_upz_id}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="upz-kode">Kode UPZ (opsional)</Label>
                                <Input
                                    id="upz-kode"
                                    value={form.data.kode}
                                    onChange={(e) => form.setData('kode', e.target.value)}
                                    placeholder="Otomatis jika kosong"
                                    className="h-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="upz-nama">Nama UPZ (wajib)</Label>
                            <Input id="upz-nama" required value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} className="h-9" />
                            {form.errors.nama && <p className="text-destructive text-xs">{form.errors.nama}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="upz-nomor-sk">Nomor SK UPZ</Label>
                            <Input
                                id="upz-nomor-sk"
                                value={form.data.nomor_sk}
                                onChange={(e) => form.setData('nomor_sk', e.target.value)}
                                placeholder="Contoh: SK-UPZ/001/2026"
                                className="h-9"
                            />
                            {form.errors.nomor_sk && <p className="text-destructive text-xs">{form.errors.nomor_sk}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="upz-desa">Desa / Kelurahan (wajib)</Label>
                            <Combobox
                                id="upz-desa"
                                options={opsiDesa}
                                value={form.data.desa_id}
                                onChange={(v) => form.setData('desa_id', v)}
                                placeholder="Pilih desa / kelurahan..."
                            />
                            {form.errors.desa_id && <p className="text-destructive text-xs">{form.errors.desa_id}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-ketua">Ketua (wajib)</Label>
                                <Input id="upz-ketua" required value={form.data.ketua} onChange={(e) => form.setData('ketua', e.target.value)} className="h-9" />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-sekretaris">Sekretaris</Label>
                                <Input id="upz-sekretaris" value={form.data.sekretaris} onChange={(e) => form.setData('sekretaris', e.target.value)} className="h-9" />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-bendahara">Bendahara</Label>
                                <Input id="upz-bendahara" value={form.data.bendahara} onChange={(e) => form.setData('bendahara', e.target.value)} className="h-9" />
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-pic-nama">Nama PIC (wajib)</Label>
                                <Input id="upz-pic-nama" required value={form.data.pic_nama} onChange={(e) => form.setData('pic_nama', e.target.value)} className="h-9" />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="upz-pic-telepon">Telepon PIC (wajib)</Label>
                                <Input id="upz-pic-telepon" required value={form.data.pic_telepon} onChange={(e) => form.setData('pic_telepon', e.target.value)} className="h-9" />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="upz-alamat">Alamat Sekretariat (wajib)</Label>
                            <textarea
                                id="upz-alamat"
                                rows={2}
                                required
                                value={form.data.alamat}
                                onChange={(e) => form.setData('alamat', e.target.value)}
                                className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            />
                        </div>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" onClick={() => setDrawerTerbuka(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing} className="h-9">
                                {form.processing ? 'Menyimpan...' : 'Simpan Data UPZ'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
