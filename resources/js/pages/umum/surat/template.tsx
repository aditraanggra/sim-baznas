import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Power } from 'lucide-react';
import * as React from 'react';

interface JenisSuratRow {
    id: number;
    kode: string;
    nama: string;
    format_nomor: string | null;
    pisah_urutan: boolean;
    keterangan: string | null;
    aktif: boolean;
    templates_count: number;
}

interface TemplateRow {
    id: number;
    jenis_surat_id: number;
    jenis_kode: string;
    jenis_nama: string;
    nama: string;
    isi: string;
    penutup_default: string | null;
    placeholder: string[];
    aktif: boolean;
}

interface Props {
    jenis_list: JenisSuratRow[];
    template_list: TemplateRow[];
    format_default: string;
    bisa_kelola: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDM, Administrasi & Umum', href: '/umum' },
    { title: 'Template & Jenis Surat', href: '/umum/surat/template' },
];

const PLACEHOLDER_CEPATS = [
    '{{tujuan}}',
    '{{perihal}}',
    '{{tanggal_surat}}',
    '{{hari_tanggal}}',
    '{{waktu}}',
    '{{tempat}}',
    '{{acara}}',
    '{{uraian_tugas}}',
    '{{jabatan_penandatangan}}',
];

export default function TemplateSuratPage({ jenis_list, template_list, format_default, bisa_kelola }: Props) {
    const [tab, setTab] = React.useState('template');
    const [sheetJenis, setSheetJenis] = React.useState(false);
    const [sheetTemplate, setSheetTemplate] = React.useState(false);
    const textareaRef = React.useRef<HTMLTextAreaElement>(null);

    const formJenis = useForm<{
        id: string;
        kode: string;
        nama: string;
        format_nomor: string;
        pisah_urutan: boolean;
        keterangan: string;
    }>({
        id: '',
        kode: '',
        nama: '',
        format_nomor: '',
        pisah_urutan: false,
        keterangan: '',
    });

    const formTemplate = useForm({
        id: '',
        jenis_surat_id: jenis_list[0] ? String(jenis_list[0].id) : '',
        nama: '',
        isi: '',
        penutup_default: '',
    });

    const bukaTambahJenis = () => {
        formJenis.clearErrors();
        formJenis.setData({ id: '', kode: '', nama: '', format_nomor: '', pisah_urutan: false, keterangan: '' });
        setSheetJenis(true);
    };

    const bukaUbahJenis = (j: JenisSuratRow) => {
        formJenis.clearErrors();
        formJenis.setData({
            id: String(j.id),
            kode: j.kode,
            nama: j.nama,
            format_nomor: j.format_nomor ?? '',
            pisah_urutan: j.pisah_urutan,
            keterangan: j.keterangan ?? '',
        });
        setSheetJenis(true);
    };

    const bukaTambahTemplate = () => {
        formTemplate.clearErrors();
        formTemplate.setData({
            id: '',
            jenis_surat_id: jenis_list[0] ? String(jenis_list[0].id) : '',
            nama: '',
            isi: '',
            penutup_default: '',
        });
        setSheetTemplate(true);
    };

    const bukaUbahTemplate = (t: TemplateRow) => {
        formTemplate.clearErrors();
        formTemplate.setData({
            id: String(t.id),
            jenis_surat_id: String(t.jenis_surat_id),
            nama: t.nama,
            isi: t.isi,
            penutup_default: t.penutup_default ?? '',
        });
        setSheetTemplate(true);
    };

    const sisipkanToken = (token: string) => {
        const el = textareaRef.current;
        if (!el) {
            formTemplate.setData('isi', `${formTemplate.data.isi}${formTemplate.data.isi ? ' ' : ''}${token}`);
            return;
        }
        const awal = el.selectionStart ?? formTemplate.data.isi.length;
        const akhir = el.selectionEnd ?? formTemplate.data.isi.length;
        const isiBaru = formTemplate.data.isi.slice(0, awal) + token + formTemplate.data.isi.slice(akhir);
        formTemplate.setData('isi', isiBaru);
        window.requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(awal + token.length, awal + token.length);
        });
    };

    const kolomJenis: DataTableColumn<JenisSuratRow>[] = [
        {
            id: 'kode',
            header: 'Kode',
            sortable: true,
            sortValue: (j) => j.kode,
            cell: (j) => <span className="font-mono text-xs font-semibold">{j.kode}</span>,
        },
        {
            id: 'nama',
            header: 'Nama Jenis Surat',
            sortable: true,
            sortValue: (j) => j.nama,
            cell: (j) => (
                <div>
                    <p className="text-foreground font-medium">{j.nama}</p>
                    {j.keterangan && <p className="text-muted-foreground text-xs">{j.keterangan}</p>}
                </div>
            ),
        },
        {
            id: 'format',
            header: 'Format Nomor & Urutan',
            cell: (j) => (
                <div className="space-y-0.5 text-xs">
                    <p className="font-mono text-foreground">{j.format_nomor || format_default}</p>
                    <p className="text-muted-foreground">{j.pisah_urutan ? 'Urutan terpisah per tahun' : 'Urutan global surat dinas'}</p>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (j) => (
                <StatusBadge
                    status={j.aktif ? 'aktif' : 'nonaktif'}
                    variant={j.aktif ? 'aktif' : 'draft'}
                    label={j.aktif ? 'Aktif' : 'Nonaktif'}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (j) =>
                bisa_kelola ? (
                    <div className="flex justify-end gap-1.5">
                        <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbahJenis(j)}>
                            <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-8 gap-1 px-2.5 text-xs"
                            onClick={() => router.post(route('umum.surat.template.jenis.toggle'), { id: j.id }, { preserveScroll: true })}
                        >
                            <Power className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>{j.aktif ? 'Nonaktifkan' : 'Aktifkan'}</span>
                        </Button>
                    </div>
                ) : (
                    <span className="text-muted-foreground text-xs">Lihat saja</span>
                ),
        },
    ];

    const kolomTemplate: DataTableColumn<TemplateRow>[] = [
        {
            id: 'nama',
            header: 'Nama Template',
            sortable: true,
            sortValue: (t) => t.nama,
            cell: (t) => (
                <div>
                    <p className="text-foreground font-medium">{t.nama}</p>
                    <p className="text-muted-foreground text-xs">
                        {t.jenis_kode} · {t.jenis_nama}
                    </p>
                </div>
            ),
        },
        {
            id: 'placeholder',
            header: 'Placeholder Terdeteksi',
            cell: (t) =>
                t.placeholder.length === 0 ? (
                    <span className="text-muted-foreground text-xs">Tanpa variabel</span>
                ) : (
                    <div className="flex flex-wrap gap-1">
                        {t.placeholder.map((p) => (
                            <span key={p} className="bg-surface-subtle text-foreground rounded border px-1.5 py-0.5 font-mono text-[11px]">
                                {`{{${p}}}`}
                            </span>
                        ))}
                    </div>
                ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (t) => (
                <StatusBadge
                    status={t.aktif ? 'aktif' : 'nonaktif'}
                    variant={t.aktif ? 'aktif' : 'draft'}
                    label={t.aktif ? 'Aktif' : 'Nonaktif'}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (t) =>
                bisa_kelola ? (
                    <div className="flex justify-end gap-1.5">
                        <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbahTemplate(t)}>
                            <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-8 gap-1 px-2.5 text-xs"
                            onClick={() => router.post(route('umum.surat.template.isi.toggle'), { id: t.id }, { preserveScroll: true })}
                        >
                            <Power className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>{t.aktif ? 'Nonaktifkan' : 'Aktifkan'}</span>
                        </Button>
                    </div>
                ) : (
                    <span className="text-muted-foreground text-xs">Lihat saja</span>
                ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Template & Jenis Surat" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Template & Jenis Surat"
                    description="Atur jenis surat dinas, template format nomor K15, serta kerangka isi surat berplaceholder."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola ? (
                            tab === 'jenis' ? (
                                <Button type="button" onClick={bukaTambahJenis} className="h-9 gap-1.5">
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Tambah Jenis Surat</span>
                                </Button>
                            ) : (
                                <Button type="button" onClick={bukaTambahTemplate} className="h-9 gap-1.5">
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Tambah Template Surat</span>
                                </Button>
                            )
                        ) : undefined
                    }
                />

                <div className="bg-card border-border rounded-2xl border p-5">
                    <Tabs value={tab} onValueChange={setTab}>
                        <TabsList>
                            <TabsTrigger value="template">Template Surat ({template_list.length})</TabsTrigger>
                            <TabsTrigger value="jenis">Jenis &amp; Penomoran ({jenis_list.length})</TabsTrigger>
                        </TabsList>

                        <TabsContent value="template" className="pt-4">
                            <DataTable
                                columns={kolomTemplate}
                                data={template_list}
                                keyExtractor={(t) => t.id}
                                emptyMessage="Belum ada template surat."
                            />
                        </TabsContent>

                        <TabsContent value="jenis" className="pt-4">
                            <DataTable
                                columns={kolomJenis}
                                data={jenis_list}
                                keyExtractor={(j) => j.id}
                                emptyMessage="Belum ada master jenis surat."
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>

            <Sheet open={sheetJenis} onOpenChange={setSheetJenis}>
                <SheetContent className="sm:max-w-[520px]">
                    <SheetHeader>
                        <SheetTitle>{formJenis.data.id ? 'Ubah Jenis Surat' : 'Tambah Jenis Surat'}</SheetTitle>
                        <SheetDescription>Token format nomor: {`{urut:3}, {kode}, {lembaga}, {romawi_bulan}, {tahun}`}.</SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            formJenis.post(route('umum.surat.template.jenis.simpan'), {
                                preserveScroll: true,
                                onSuccess: () => setSheetJenis(false),
                            });
                        }}
                        className="mt-4 space-y-4"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="jenis-kode">Kode Jenis Surat (wajib)</Label>
                            <Input
                                id="jenis-kode"
                                value={formJenis.data.kode}
                                onChange={(e) => formJenis.setData('kode', e.target.value.toUpperCase())}
                                placeholder="Mis. UND, TGS, SK"
                                className="h-9"
                            />
                            {formJenis.errors.kode && <p className="text-destructive text-xs">{formJenis.errors.kode}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="jenis-nama">Nama Jenis Surat (wajib)</Label>
                            <Input
                                id="jenis-nama"
                                value={formJenis.data.nama}
                                onChange={(e) => formJenis.setData('nama', e.target.value)}
                                placeholder="Mis. Surat Undangan Resmi"
                                className="h-9"
                            />
                            {formJenis.errors.nama && <p className="text-destructive text-xs">{formJenis.errors.nama}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="jenis-format">Format Nomor Khusus (opsional)</Label>
                            <Input
                                id="jenis-format"
                                value={formJenis.data.format_nomor}
                                onChange={(e) => formJenis.setData('format_nomor', e.target.value)}
                                placeholder={format_default}
                                className="h-9 font-mono text-xs"
                            />
                            <p className="text-muted-foreground text-xs">Kosongkan untuk memakai format standar: {format_default}</p>
                        </div>

                        <div className="flex items-start gap-2.5 rounded-lg border p-3">
                            <Checkbox
                                id="jenis-pisah"
                                checked={formJenis.data.pisah_urutan}
                                onCheckedChange={(val) => formJenis.setData('pisah_urutan', Boolean(val))}
                            />
                            <div className="space-y-0.5">
                                <Label htmlFor="jenis-pisah" className="cursor-pointer font-medium">
                                    Pisahkan urutan nomor per tahun
                                </Label>
                                <p className="text-muted-foreground text-xs">
                                    Centang jika jenis surat ini (seperti SK) memiliki deret nomor tersendiri mulai dari 001 tiap tahun.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="jenis-ket">Keterangan</Label>
                            <textarea
                                id="jenis-ket"
                                rows={2}
                                value={formJenis.data.keterangan}
                                onChange={(e) => formJenis.setData('keterangan', e.target.value)}
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>

                        <SheetFooter>
                            <Button type="button" variant="outline" onClick={() => setSheetJenis(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formJenis.processing}>
                                {formJenis.processing ? 'Menyimpan...' : 'Simpan Jenis Surat'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <Sheet open={sheetTemplate} onOpenChange={setSheetTemplate}>
                <SheetContent className="overflow-y-auto sm:max-w-[640px]">
                    <SheetHeader>
                        <SheetTitle>{formTemplate.data.id ? 'Ubah Template Surat' : 'Tambah Template Surat'}</SheetTitle>
                        <SheetDescription>Gunakan placeholder seperti {`{{acara}}, {{tempat}}, {{hari_tanggal}}`} di badan surat.</SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            formTemplate.post(route('umum.surat.template.isi.simpan'), {
                                preserveScroll: true,
                                onSuccess: () => setSheetTemplate(false),
                            });
                        }}
                        className="mt-4 space-y-4"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="tpl-jenis">Jenis Surat (wajib)</Label>
                            <Select value={formTemplate.data.jenis_surat_id} onValueChange={(v) => formTemplate.setData('jenis_surat_id', v)}>
                                <SelectTrigger id="tpl-jenis" className="h-9">
                                    <SelectValue placeholder="Pilih jenis surat" />
                                </SelectTrigger>
                                <SelectContent>
                                    {jenis_list.map((j) => (
                                        <SelectItem key={j.id} value={String(j.id)}>
                                            {j.kode} — {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {formTemplate.errors.jenis_surat_id && (
                                <p className="text-destructive text-xs">{formTemplate.errors.jenis_surat_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tpl-nama">Nama Template (wajib)</Label>
                            <Input
                                id="tpl-nama"
                                value={formTemplate.data.nama}
                                onChange={(e) => formTemplate.setData('nama', e.target.value)}
                                placeholder="Mis. Undangan Rapat Koordinasi UPZ"
                                className="h-9"
                            />
                            {formTemplate.errors.nama && <p className="text-destructive text-xs">{formTemplate.errors.nama}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <Label htmlFor="tpl-isi">Isi Template Surat (wajib)</Label>
                                <span className="text-muted-foreground text-xs">Klik tombol untuk menyisipkan placeholder:</span>
                            </div>
                            <div className="flex flex-wrap gap-1.5 pb-1">
                                {PLACEHOLDER_CEPATS.map((tok) => (
                                    <button
                                        key={tok}
                                        type="button"
                                        onClick={() => sisipkanToken(tok)}
                                        className="bg-surface-subtle hover:bg-muted text-foreground rounded border px-2 py-1 font-mono text-[11px] transition-colors"
                                    >
                                        + {tok}
                                    </button>
                                ))}
                            </div>
                            <textarea
                                ref={textareaRef}
                                id="tpl-isi"
                                rows={11}
                                value={formTemplate.data.isi}
                                onChange={(e) => formTemplate.setData('isi', e.target.value)}
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm leading-relaxed"
                            />
                            {formTemplate.errors.isi && <p className="text-destructive text-xs">{formTemplate.errors.isi}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tpl-penutup">Catatan Penutup Default (opsional)</Label>
                            <Input
                                id="tpl-penutup"
                                value={formTemplate.data.penutup_default}
                                onChange={(e) => formTemplate.setData('penutup_default', e.target.value)}
                                className="h-9"
                            />
                        </div>

                        <SheetFooter>
                            <Button type="button" variant="outline" onClick={() => setSheetTemplate(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formTemplate.processing}>
                                {formTemplate.processing ? 'Menyimpan...' : 'Simpan Template'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
