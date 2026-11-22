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
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Power, Search } from 'lucide-react';
import * as React from 'react';

interface JenisMeta {
    kode: string;
    label: string;
    deskripsi: string;
}

interface KolomSkema {
    key: string;
    label: string;
    type: 'text' | 'number' | 'boolean' | 'select';
    required: boolean;
    max?: number;
    options_key?: string;
}

interface OpsiItem {
    value: string;
    label: string;
}

interface Props {
    jenis_aktif: string;
    cari: string;
    daftar_jenis: JenisMeta[];
    skema: {
        label: string;
        deskripsi: string;
        kolom: KolomSkema[];
    };
    opsi: Record<string, OpsiItem[]>;
    data: Record<string, unknown>[];
}

export default function MasterData({ jenis_aktif, cari, daftar_jenis, skema, opsi, data }: Props) {
    const [kataCari, setKataCari] = React.useState(cari);
    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);
    const [targetToggle, setTargetToggle] = React.useState<Record<string, unknown> | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administrasi Sistem', href: '/pengaturan/pengguna' },
        { title: 'Master Data', href: `/pengaturan/master-data?jenis=${jenis_aktif}` },
    ];

    const buatNilaiAwal = React.useCallback(
        (baris?: Record<string, unknown>) => {
            const hasil: Record<string, string | number | boolean> = {
                id: baris ? String(baris.id ?? '') : '',
            };
            for (const kol of skema.kolom) {
                if (baris) {
                    const nilai = baris[kol.key];
                    if (kol.type === 'boolean') {
                        hasil[kol.key] = Boolean(nilai);
                    } else {
                        hasil[kol.key] = nilai === null || nilai === undefined ? '' : String(nilai);
                    }
                } else {
                    hasil[kol.key] = kol.type === 'boolean' ? false : '';
                }
            }
            return hasil;
        },
        [skema.kolom],
    );

    const form = useForm<Record<string, string | number | boolean>>(buatNilaiAwal());

    React.useEffect(() => {
        form.setData(buatNilaiAwal());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [jenis_aktif]);

    const bukaTambah = () => {
        form.clearErrors();
        form.setData(buatNilaiAwal());
        setDrawerTerbuka(true);
    };

    const bukaUbah = (baris: Record<string, unknown>) => {
        form.clearErrors();
        form.setData(buatNilaiAwal(baris));
        setDrawerTerbuka(true);
    };

    const kirimSimpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pengaturan.master-data.simpan', jenis_aktif), {
            preserveScroll: true,
            onSuccess: () => setDrawerTerbuka(false),
        });
    };

    const konfirmasiToggle = () => {
        if (!targetToggle) return;
        router.post(
            route('pengaturan.master-data.toggle-aktif', jenis_aktif),
            { id: Number(targetToggle.id) },
            {
                preserveScroll: true,
                onFinish: () => setTargetToggle(null),
            },
        );
    };

    const formatNilaiSel = (kol: KolomSkema, baris: Record<string, unknown>): React.ReactNode => {
        const nilai = baris[kol.key];
        if (kol.type === 'boolean') {
            return nilai ? 'Ya' : 'Tidak';
        }
        if (kol.type === 'select' && kol.options_key) {
            const daftarOpsi = opsi[kol.options_key] ?? [];
            const ditemukan = daftarOpsi.find((o) => o.value === String(nilai ?? ''));
            return ditemukan ? ditemukan.label : String(nilai ?? '-');
        }
        return nilai === null || nilai === undefined || nilai === '' ? '-' : String(nilai);
    };

    const kolomTabel: DataTableColumn<Record<string, unknown>>[] = [
        ...skema.kolom.map((kol) => ({
            id: kol.key,
            header: kol.label,
            sortable: true,
            sortValue: (baris: Record<string, unknown>) => String(baris[kol.key] ?? ''),
            cell: (baris: Record<string, unknown>) => formatNilaiSel(kol, baris),
        })),
        {
            id: 'aktif',
            header: 'Status',
            sortable: true,
            sortValue: (baris) => (baris.aktif ? '1' : '0'),
            cell: (baris) => (
                <StatusBadge
                    status={baris.aktif ? 'aktif' : 'nonaktif'}
                    variant={baris.aktif ? 'aktif' : 'draft'}
                    label={baris.aktif ? 'Aktif' : 'Nonaktif'}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (baris) => (
                <div className="flex justify-end gap-1.5">
                    <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbah(baris)}>
                        <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Ubah</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 gap-1 px-2.5 text-xs"
                        onClick={() => setTargetToggle(baris)}
                    >
                        <Power className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{baris.aktif ? 'Nonaktifkan' : 'Aktifkan'}</span>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Master Data: ${skema.label}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={`Master Data: ${skema.label}`}
                    description={skema.deskripsi}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Button type="button" onClick={bukaTambah} className="h-9 gap-1.5">
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            <span>Tambah {skema.label}</span>
                        </Button>
                    }
                />

                <div className="bg-card border-border flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-wrap items-end gap-3">
                        <div className="w-full space-y-1.5 sm:w-64">
                            <Label htmlFor="pilih-jenis-master">Kelompok Master Data</Label>
                            <Select
                                value={jenis_aktif}
                                onValueChange={(nilai) => router.get(route('pengaturan.master-data'), { jenis: nilai }, { preserveState: false })}
                            >
                                <SelectTrigger id="pilih-jenis-master" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {daftar_jenis.map((item) => (
                                        <SelectItem key={item.kode} value={item.kode}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <form
                            className="flex flex-1 items-end gap-2 sm:max-w-sm"
                            onSubmit={(e) => {
                                e.preventDefault();
                                router.get(route('pengaturan.master-data'), { jenis: jenis_aktif, cari: kataCari }, { preserveState: true });
                            }}
                        >
                            <div className="flex-1 space-y-1.5">
                                <Label htmlFor="cari-master">Cari Kode atau Nama</Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2" aria-hidden="true" />
                                    <Input
                                        id="cari-master"
                                        value={kataCari}
                                        onChange={(e) => setKataCari(e.target.value)}
                                        placeholder="Ketik kata kunci..."
                                        className="h-9 pl-8"
                                    />
                                </div>
                            </div>
                            <Button type="submit" variant="outline" className="h-9">
                                Cari
                            </Button>
                        </form>
                    </div>
                </div>

                <DataTable
                    columns={kolomTabel}
                    data={data}
                    keyExtractor={(baris) => String(baris.id)}
                    emptyMessage={`Belum ada data untuk master ${skema.label}.`}
                    emptyAction={
                        <Button type="button" onClick={bukaTambah} className="h-9">
                            Tambah {skema.label}
                        </Button>
                    }
                />
            </div>

            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[520px]">
                    <SheetHeader>
                        <SheetTitle>{form.data.id ? `Ubah ${skema.label}` : `Tambah ${skema.label}`}</SheetTitle>
                        <SheetDescription>Perubahan master data tercatat otomatis di audit log.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimSimpan} className="mt-5 space-y-4">
                        {skema.kolom.map((kol) => (
                            <div key={kol.key} className="space-y-1.5">
                                {kol.type === 'boolean' ? (
                                    <div className="flex items-center gap-2.5 pt-1">
                                        <Checkbox
                                            id={`field-${kol.key}`}
                                            checked={Boolean(form.data[kol.key])}
                                            onCheckedChange={(checked) => form.setData(kol.key, Boolean(checked))}
                                        />
                                        <Label htmlFor={`field-${kol.key}`} className="font-normal">
                                            {kol.label}
                                        </Label>
                                    </div>
                                ) : kol.type === 'select' && kol.options_key ? (
                                    <>
                                        <Label htmlFor={`field-${kol.key}`}>
                                            {kol.label}
                                            {kol.required && <span className="text-muted-foreground ml-1 font-normal">(wajib)</span>}
                                        </Label>
                                        <Select
                                            value={String(form.data[kol.key] ?? '')}
                                            onValueChange={(val) => form.setData(kol.key, val)}
                                        >
                                            <SelectTrigger id={`field-${kol.key}`} className="h-9">
                                                <SelectValue placeholder={`Pilih ${kol.label}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {(opsi[kol.options_key] ?? []).map((opt) => (
                                                    <SelectItem key={opt.value} value={opt.value}>
                                                        {opt.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </>
                                ) : (
                                    <>
                                        <Label htmlFor={`field-${kol.key}`}>
                                            {kol.label}
                                            {kol.required && <span className="text-muted-foreground ml-1 font-normal">(wajib)</span>}
                                        </Label>
                                        <Input
                                            id={`field-${kol.key}`}
                                            type={kol.type === 'number' ? 'number' : 'text'}
                                            maxLength={kol.max}
                                            required={kol.required}
                                            value={String(form.data[kol.key] ?? '')}
                                            onChange={(e) => form.setData(kol.key, e.target.value)}
                                            className="h-9"
                                        />
                                    </>
                                )}
                                {form.errors[kol.key] && (
                                    <p role="alert" className="text-destructive text-xs font-medium">
                                        {form.errors[kol.key]}
                                    </p>
                                )}
                            </div>
                        ))}

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" onClick={() => setDrawerTerbuka(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing} className="h-9">
                                {form.processing ? 'Menyimpan...' : 'Simpan Data'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <AlertDialog open={Boolean(targetToggle)} onOpenChange={(open) => !open && setTargetToggle(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {targetToggle?.aktif ? 'Nonaktifkan Master Data?' : 'Aktifkan Kembali Master Data?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Data yang dinonaktifkan tetap dipertahankan untuk menjaga keutuhan riwayat transaksi dan tidak dapat dihapus permanen.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction destructive={Boolean(targetToggle?.aktif)} onClick={konfirmasiToggle}>
                            {targetToggle?.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
