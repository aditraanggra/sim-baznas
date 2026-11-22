import { InputNIK, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Eye, Pencil, Plus, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengumpulan', href: '/pengumpulan' },
    { title: 'Data Muzakki', href: '/pengumpulan/muzakki' },
];

interface MuzakkiRow {
    id: number;
    kode: string;
    jenis: 'perorangan' | 'lembaga';
    nama: string;
    nik_masked: string | null;
    npwp: string | null;
    telepon: string | null;
    email: string | null;
    alamat: string | null;
    kecamatan_id: number | null;
    desa_id: number | null;
    wilayah: string;
    aktif: boolean;
}

interface DuplikatInfo {
    id: number;
    kode: string;
    nama: string;
    nik_masked: string | null;
    telepon: string | null;
    alasan: string;
}

interface Props {
    muzakki: {
        data: MuzakkiRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    kecamatan_list: { id: number; nama: string }[];
    desa_list: { id: number; kecamatan_id: number; nama: string }[];
    filter: { cari: string; jenis: string; kecamatan_id: number | null };
    bisa_kelola: boolean;
}

export default function MuzakkiIndex({ muzakki, kecamatan_list, desa_list, filter, bisa_kelola }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);
    const [duplikat, setDuplikat] = React.useState<DuplikatInfo[]>([]);

    const form = useForm({
        id: '',
        jenis: 'perorangan',
        nama: '',
        nik: '',
        npwp: '',
        telepon: '',
        email: '',
        alamat: '',
        kecamatan_id: '',
        desa_id: '',
    });

    const desaTersaring = React.useMemo(() => {
        if (!form.data.kecamatan_id) return desa_list;
        return desa_list.filter((d) => String(d.kecamatan_id) === form.data.kecamatan_id);
    }, [desa_list, form.data.kecamatan_id]);

    const periksaDuplikat = React.useCallback(async (nik: string, telepon: string, ignoreId: string) => {
        if (nik.length < 16 && telepon.replace(/\D+/g, '').length < 8) {
            setDuplikat([]);
            return;
        }
        try {
            const params = new URLSearchParams();
            if (nik) params.set('nik', nik);
            if (telepon) params.set('telepon', telepon);
            if (ignoreId) params.set('ignore_id', ignoreId);
            const res = await fetch(`${route('pengumpulan.muzakki.cek-duplikat')}?${params.toString()}`);
            if (res.ok) {
                const json = (await res.json()) as { duplikat: DuplikatInfo[] };
                setDuplikat(json.duplikat ?? []);
            }
        } catch {
            // Abaikan kegagalan pengecekan jaringan sementara
        }
    }, []);

    const bukaTambah = () => {
        form.clearErrors();
        setDuplikat([]);
        form.setData({
            id: '',
            jenis: 'perorangan',
            nama: '',
            nik: '',
            npwp: '',
            telepon: '',
            email: '',
            alamat: '',
            kecamatan_id: '',
            desa_id: '',
        });
        setDrawerTerbuka(true);
    };

    const bukaUbah = (m: MuzakkiRow) => {
        form.clearErrors();
        setDuplikat([]);
        form.setData({
            id: String(m.id),
            jenis: m.jenis,
            nama: m.nama,
            nik: '',
            npwp: m.npwp ?? '',
            telepon: m.telepon ?? '',
            email: m.email ?? '',
            alamat: m.alamat ?? '',
            kecamatan_id: m.kecamatan_id ? String(m.kecamatan_id) : '',
            desa_id: m.desa_id ? String(m.desa_id) : '',
        });
        setDrawerTerbuka(true);
    };

    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pengumpulan.muzakki.store'), {
            preserveScroll: true,
            onSuccess: () => setDrawerTerbuka(false),
        });
    };

    const kolom: DataTableColumn<MuzakkiRow>[] = [
        {
            id: 'nama',
            header: 'Nama Muzakki',
            sortable: true,
            sortValue: (m) => m.nama,
            cell: (m) => (
                <div>
                    <Link href={route('pengumpulan.muzakki.show', m.id)} className="text-foreground hover:text-primary font-medium hover:underline">
                        {m.nama}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {m.kode} · {m.jenis === 'lembaga' ? 'Lembaga' : 'Perorangan'}
                    </p>
                </div>
            ),
        },
        {
            id: 'identitas',
            header: 'NIK / Telepon',
            cell: (m) => (
                <div className="text-xs tabular-nums">
                    <p className="text-foreground">{m.nik_masked ?? (m.npwp ? `NPWP: ${m.npwp}` : '-')}</p>
                    <p className="text-muted-foreground">{m.telepon ?? '-'}</p>
                </div>
            ),
        },
        {
            id: 'wilayah',
            header: 'Wilayah',
            cell: (m) => <span className="text-xs">{m.wilayah || '-'}</span>,
        },
        {
            id: 'aktif',
            header: 'Status',
            cell: (m) => <StatusBadge status={m.aktif ? 'aktif' : 'draft'} label={m.aktif ? 'Aktif' : 'Nonaktif'} />,
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (m) => (
                <div className="flex justify-end gap-1.5">
                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                        <Link href={route('pengumpulan.muzakki.show', m.id)}>
                            <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Riwayat</span>
                        </Link>
                    </Button>
                    {bisa_kelola && (
                        <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbah(m)}>
                            <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Muzakki" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Data Muzakki"
                    description="Database muzakki perorangan dan lembaga dengan perlindungan enkripsi NIK serta deteksi duplikat otomatis."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola ? (
                            <Button type="button" onClick={bukaTambah} className="h-9 gap-1.5">
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Tambah Muzakki</span>
                            </Button>
                        ) : null
                    }
                />

                <form
                    className="bg-card border-border flex flex-wrap items-end gap-3 rounded-xl border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            route('pengumpulan.muzakki.index'),
                            { cari, jenis: filter.jenis, kecamatan_id: filter.kecamatan_id ?? '' },
                            { preserveState: true },
                        );
                    }}
                >
                    <div className="min-w-[220px] flex-1 space-y-1.5">
                        <Label htmlFor="cari-muzakki">Cari Nama, Kode, Telepon, atau 16 Digit NIK</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2" aria-hidden="true" />
                            <Input id="cari-muzakki" value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Ketik kata kunci..." className="h-9 pl-8" />
                        </div>
                    </div>

                    <div className="w-full space-y-1.5 sm:w-44">
                        <Label htmlFor="filter-jenis-muzakki">Jenis</Label>
                        <Select
                            value={filter.jenis || 'semua'}
                            onValueChange={(v) =>
                                router.get(route('pengumpulan.muzakki.index'), { cari, jenis: v === 'semua' ? '' : v, kecamatan_id: filter.kecamatan_id ?? '' })
                            }
                        >
                            <SelectTrigger id="filter-jenis-muzakki" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Jenis</SelectItem>
                                <SelectItem value="perorangan">Perorangan</SelectItem>
                                <SelectItem value="lembaga">Lembaga</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="w-full space-y-1.5 sm:w-52">
                        <Label htmlFor="filter-kecamatan">Kecamatan</Label>
                        <Select
                            value={filter.kecamatan_id ? String(filter.kecamatan_id) : 'semua'}
                            onValueChange={(v) =>
                                router.get(route('pengumpulan.muzakki.index'), { cari, jenis: filter.jenis, kecamatan_id: v === 'semua' ? '' : v })
                            }
                        >
                            <SelectTrigger id="filter-kecamatan" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Kecamatan</SelectItem>
                                {kecamatan_list.map((k) => (
                                    <SelectItem key={k.id} value={String(k.id)}>
                                        {k.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Button type="submit" variant="outline" className="h-9">
                        Cari
                    </Button>
                </form>

                <DataTable
                    columns={kolom}
                    data={muzakki.data}
                    keyExtractor={(m) => m.id}
                    page={muzakki.current_page}
                    perPage={muzakki.per_page}
                    total={muzakki.total}
                    onPageChange={(hal) =>
                        router.get(route('pengumpulan.muzakki.index'), { ...filter, page: hal }, { preserveState: true })
                    }
                    emptyMessage="Belum ada data muzakki terdaftar."
                />
            </div>

            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[540px]">
                    <SheetHeader>
                        <SheetTitle>{form.data.id ? 'Ubah Data Muzakki' : 'Tambah Muzakki Baru'}</SheetTitle>
                        <SheetDescription>NIK dienkripsi sebelum disimpan dan hanya ditampilkan dalam bentuk samaran.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirim} className="mt-5 space-y-4">
                        {duplikat.length > 0 && (
                            <div className="bg-warning-soft border-warning/30 text-warning rounded-lg border p-3 text-xs">
                                <div className="flex items-center gap-1.5 font-semibold">
                                    <AlertTriangle className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span>Kemungkinan Duplikat Terdeteksi</span>
                                </div>
                                <ul className="mt-1.5 list-disc space-y-0.5 pl-5">
                                    {duplikat.map((d) => (
                                        <li key={d.id}>
                                            {d.nama} ({d.kode}) - {d.alasan}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="mzk-jenis">Jenis Muzakki (wajib)</Label>
                                <Select value={form.data.jenis} onValueChange={(v) => form.setData('jenis', v)}>
                                    <SelectTrigger id="mzk-jenis" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="perorangan">Perorangan</SelectItem>
                                        <SelectItem value="lembaga">Lembaga / Instansi</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="mzk-telepon">Telepon / WhatsApp</Label>
                                <Input
                                    id="mzk-telepon"
                                    value={form.data.telepon}
                                    onChange={(e) => form.setData('telepon', e.target.value)}
                                    onBlur={() => periksaDuplikat(form.data.nik, form.data.telepon, form.data.id)}
                                    className="h-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="mzk-nama">Nama Lengkap / Lembaga (wajib)</Label>
                            <Input id="mzk-nama" required value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} className="h-9" />
                            {form.errors.nama && <p className="text-destructive text-xs">{form.errors.nama}</p>}
                        </div>

                        {form.data.jenis === 'perorangan' ? (
                            <InputNIK
                                id="mzk-nik"
                                value={form.data.nik}
                                onChange={(nik) => {
                                    form.setData('nik', nik);
                                    if (nik.length === 16) {
                                        periksaDuplikat(nik, form.data.telepon, form.data.id);
                                    }
                                }}
                                helperText={form.data.id ? 'Kosongkan jika tidak ingin mengubah NIK tersimpan.' : undefined}
                                error={form.errors.nik}
                            />
                        ) : (
                            <div className="space-y-1.5">
                                <Label htmlFor="mzk-npwp">NPWP Lembaga</Label>
                                <Input id="mzk-npwp" value={form.data.npwp} onChange={(e) => form.setData('npwp', e.target.value)} className="h-9" />
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <Label htmlFor="mzk-email">Email</Label>
                            <Input id="mzk-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} className="h-9" />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="mzk-kecamatan">Kecamatan</Label>
                                <Select
                                    value={form.data.kecamatan_id}
                                    onValueChange={(v) => form.setData({ ...form.data, kecamatan_id: v, desa_id: '' })}
                                >
                                    <SelectTrigger id="mzk-kecamatan" className="h-9">
                                        <SelectValue placeholder="Pilih kecamatan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kecamatan_list.map((k) => (
                                            <SelectItem key={k.id} value={String(k.id)}>
                                                {k.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="mzk-desa">Desa / Kelurahan</Label>
                                <Select value={form.data.desa_id} onValueChange={(v) => form.setData('desa_id', v)}>
                                    <SelectTrigger id="mzk-desa" className="h-9">
                                        <SelectValue placeholder="Pilih desa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {desaTersaring.map((d) => (
                                            <SelectItem key={d.id} value={String(d.id)}>
                                                {d.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="mzk-alamat">Alamat</Label>
                            <textarea
                                id="mzk-alamat"
                                rows={2}
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
                                {form.processing ? 'Menyimpan...' : 'Simpan Muzakki'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
