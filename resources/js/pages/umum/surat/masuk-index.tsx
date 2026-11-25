import { PageHeader, StatusBadge, UnggahBerkas } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Plus, Search } from 'lucide-react';
import * as React from 'react';

interface SuratMasukRow {
    id: number;
    nomor_agenda: string;
    nomor_surat: string;
    tanggal_surat: string | null;
    tanggal_terima: string | null;
    pengirim: string;
    perihal: string;
    sifat: string;
    status_kode: string;
    status_label: string;
    jumlah_disposisi: number;
    menunggu_saya: boolean;
}

interface Props {
    surat_list: {
        data: SuratMasukRow[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filter: {
        cari: string;
        status: string;
        sifat: string;
        disposisi_saya: boolean;
    };
    bisa_catat: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDM, Administrasi & Umum', href: '/umum' },
    { title: 'Surat Masuk', href: '/umum/surat/masuk' },
];

export default function SuratMasukIndex({ surat_list, filter, bisa_catat }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [sheetTerbuka, setSheetTerbuka] = React.useState(false);

    const form = useForm<{
        nomor_surat: string;
        tanggal_surat: string;
        tanggal_terima: string;
        pengirim: string;
        perihal: string;
        ringkasan: string;
        sifat: string;
        langsung_teruskan_ketua: boolean;
        catatan: string;
        berkas: File | null;
    }>({
        nomor_surat: '',
        tanggal_surat: new Date().toISOString().slice(0, 10),
        tanggal_terima: new Date().toISOString().slice(0, 10),
        pengirim: '',
        perihal: '',
        ringkasan: '',
        sifat: 'biasa',
        langsung_teruskan_ketua: true,
        catatan: '',
        berkas: null,
    });

    const terapkanFilter = (tambahan: Partial<typeof filter> = {}) => {
        const gabung = {
            cari,
            status: filter.status,
            sifat: filter.sifat,
            disposisi_saya: filter.disposisi_saya ? '1' : '',
            ...tambahan,
        };
        const bersih = Object.fromEntries(Object.entries(gabung).filter(([, v]) => v !== '' && v !== false));
        router.get(route('umum.surat.masuk.index'), bersih, { preserveState: true, replace: true });
    };

    const kirimCatat = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('umum.surat.masuk.store'), {
            forceFormData: true,
            onSuccess: () => {
                setSheetTerbuka(false);
                form.reset();
            },
        });
    };

    const kolom: DataTableColumn<SuratMasukRow>[] = [
        {
            id: 'agenda',
            header: 'No. Agenda & Surat',
            sortable: true,
            sortValue: (s) => s.nomor_agenda,
            cell: (s) => (
                <div>
                    <Link href={route('umum.surat.masuk.show', s.id)} className="text-foreground hover:text-primary font-medium hover:underline">
                        {s.nomor_agenda}
                    </Link>
                    <p className="text-muted-foreground text-xs">Asal: {s.nomor_surat}</p>
                </div>
            ),
        },
        {
            id: 'pengirim',
            header: 'Pengirim & Perihal',
            cell: (s) => (
                <div className="max-w-md">
                    <p className="text-foreground font-medium">{s.pengirim}</p>
                    <p className="text-muted-foreground line-clamp-1 text-xs">{s.perihal}</p>
                </div>
            ),
        },
        {
            id: 'tanggal',
            header: 'Tgl Terima',
            sortable: true,
            sortValue: (s) => s.tanggal_terima ?? '',
            cell: (s) => <span className="text-xs tabular-nums">{s.tanggal_terima ?? '-'}</span>,
        },
        {
            id: 'sifat',
            header: 'Sifat',
            cell: (s) => (
                <StatusBadge
                    status={s.sifat}
                    variant={s.sifat === 'rahasia' ? 'danger' : s.sifat === 'segera' || s.sifat === 'penting' ? 'warning' : 'draft'}
                    label={s.sifat.toUpperCase()}
                />
            ),
        },
        {
            id: 'status',
            header: 'Status Alur',
            cell: (s) => (
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge
                        status={s.status_kode}
                        variant={s.status_kode === 'selesai' ? 'final' : s.status_kode === 'diterima_arsip' ? 'info' : 'warning'}
                        label={s.status_label}
                    />
                    {s.menunggu_saya && (
                        <span className="bg-primary-soft text-primary-hover rounded px-1.5 py-0.5 text-[11px] font-medium">Menunggu Saya</span>
                    )}
                </div>
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (s) => (
                <Button asChild variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs">
                    <Link href={route('umum.surat.masuk.show', s.id)}>
                        <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Detail</span>
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Surat Masuk" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Surat Masuk"
                    description="Registrasi agenda surat masuk dan pantau disposisi berjenjang Ketua hingga tindak lanjut."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_catat ? (
                            <Button type="button" onClick={() => setSheetTerbuka(true)} className="h-9 gap-1.5">
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Catat Surat Masuk</span>
                            </Button>
                        ) : undefined
                    }
                />

                <div className="bg-card border-border rounded-2xl border p-4">
                    <div className="flex flex-wrap items-end gap-3">
                        <form
                            className="flex min-w-[240px] flex-1 gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                terapkanFilter();
                            }}
                        >
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground pointer-events-none absolute top-2.5 left-3 h-4 w-4" aria-hidden="true" />
                                <Input
                                    value={cari}
                                    onChange={(e) => setCari(e.target.value)}
                                    placeholder="Cari nomor agenda, nomor surat, pengirim, atau perihal..."
                                    className="h-9 pl-9"
                                />
                            </div>
                            <Button type="submit" variant="outline" className="h-9">
                                Cari
                            </Button>
                        </form>

                        <div className="w-44">
                            <Select
                                value={filter.status || 'semua'}
                                onValueChange={(v) => terapkanFilter({ status: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Status</SelectItem>
                                    <SelectItem value="diterima_arsip">Diterima Admin Arsip</SelectItem>
                                    <SelectItem value="diteruskan_ketua">Diteruskan ke Ketua</SelectItem>
                                    <SelectItem value="disposisi_waka">Disposisi Wakil Ketua</SelectItem>
                                    <SelectItem value="tindak_lanjut">Tindak Lanjut Bawahan</SelectItem>
                                    <SelectItem value="selesai">Selesai</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="w-36">
                            <Select value={filter.sifat || 'semua'} onValueChange={(v) => terapkanFilter({ sifat: v === 'semua' ? '' : v })}>
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Semua Sifat" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Sifat</SelectItem>
                                    <SelectItem value="biasa">Biasa</SelectItem>
                                    <SelectItem value="penting">Penting</SelectItem>
                                    <SelectItem value="segera">Segera</SelectItem>
                                    <SelectItem value="rahasia">Rahasia</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            type="button"
                            variant={filter.disposisi_saya ? 'default' : 'outline'}
                            className="h-9"
                            onClick={() => terapkanFilter({ disposisi_saya: !filter.disposisi_saya })}
                        >
                            Disposisi Saya
                        </Button>
                    </div>
                </div>

                <div className="bg-card border-border rounded-2xl border p-5">
                    <DataTable
                        columns={kolom}
                        data={surat_list.data}
                        keyExtractor={(s) => s.id}
                        page={surat_list.current_page}
                        perPage={surat_list.per_page}
                        total={surat_list.total}
                        onPageChange={(page) =>
                            router.get(route('umum.surat.masuk.index'), { ...filter, page }, { preserveState: true })
                        }
                        onRowClick={(s) => router.visit(route('umum.surat.masuk.show', s.id))}
                        emptyMessage="Belum ada surat masuk yang sesuai filter."
                    />
                </div>
            </div>

            <Sheet open={sheetTerbuka} onOpenChange={setSheetTerbuka}>
                <SheetContent className="overflow-y-auto sm:max-w-[560px]">
                    <SheetHeader>
                        <SheetTitle>Catat Surat Masuk</SheetTitle>
                        <SheetDescription>Nomor agenda akan diterbitkan otomatis oleh sistem saat disimpan.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimCatat} className="mt-4 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="sm-nomor">Nomor Surat Asal (wajib)</Label>
                            <Input
                                id="sm-nomor"
                                value={form.data.nomor_surat}
                                onChange={(e) => form.setData('nomor_surat', e.target.value)}
                                placeholder="Mis. 451.1/102/Kesra/2026"
                                className="h-9"
                            />
                            {form.errors.nomor_surat && <p className="text-destructive text-xs">{form.errors.nomor_surat}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="sm-tgl-surat">Tanggal Surat (wajib)</Label>
                                <Input
                                    id="sm-tgl-surat"
                                    type="date"
                                    value={form.data.tanggal_surat}
                                    onChange={(e) => form.setData('tanggal_surat', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="sm-tgl-terima">Tanggal Terima (wajib)</Label>
                                <Input
                                    id="sm-tgl-terima"
                                    type="date"
                                    value={form.data.tanggal_terima}
                                    onChange={(e) => form.setData('tanggal_terima', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="sm-pengirim">Pengirim / Instansi (wajib)</Label>
                            <Input
                                id="sm-pengirim"
                                value={form.data.pengirim}
                                onChange={(e) => form.setData('pengirim', e.target.value)}
                                placeholder="Mis. Pemerintah Kabupaten Cianjur"
                                className="h-9"
                            />
                            {form.errors.pengirim && <p className="text-destructive text-xs">{form.errors.pengirim}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="sm-perihal">Perihal (wajib)</Label>
                                <Input
                                    id="sm-perihal"
                                    value={form.data.perihal}
                                    onChange={(e) => form.setData('perihal', e.target.value)}
                                    placeholder="Pokok isi surat masuk"
                                    className="h-9"
                                />
                                {form.errors.perihal && <p className="text-destructive text-xs">{form.errors.perihal}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="sm-sifat">Sifat Surat</Label>
                                <Select value={form.data.sifat} onValueChange={(v) => form.setData('sifat', v)}>
                                    <SelectTrigger id="sm-sifat" className="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="biasa">Biasa</SelectItem>
                                        <SelectItem value="penting">Penting</SelectItem>
                                        <SelectItem value="segera">Segera</SelectItem>
                                        <SelectItem value="rahasia">Rahasia</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="sm-ringkasan">Ringkasan Isi Surat (opsional)</Label>
                            <textarea
                                id="sm-ringkasan"
                                rows={3}
                                value={form.data.ringkasan}
                                onChange={(e) => form.setData('ringkasan', e.target.value)}
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>

                        <UnggahBerkas
                            label="Scan / Berkas Surat Masuk (opsional)"
                            value={form.data.berkas}
                            onChange={(file) => form.setData('berkas', file)}
                            error={form.errors.berkas}
                        />

                        <div className="flex items-start gap-2.5 rounded-lg border p-3">
                            <Checkbox
                                id="sm-teruskan"
                                checked={form.data.langsung_teruskan_ketua}
                                onCheckedChange={(v) => form.setData('langsung_teruskan_ketua', Boolean(v))}
                            />
                            <div className="space-y-0.5">
                                <Label htmlFor="sm-teruskan" className="cursor-pointer font-medium">
                                    Simpan &amp; langsung teruskan ke Ketua
                                </Label>
                                <p className="text-muted-foreground text-xs">
                                    Ketua langsung menerima notifikasi untuk melakukan disposisi ke Wakil Ketua.
                                </p>
                            </div>
                        </div>

                        <SheetFooter>
                            <Button type="button" variant="outline" onClick={() => setSheetTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Menyimpan...' : 'Simpan Surat Masuk'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
