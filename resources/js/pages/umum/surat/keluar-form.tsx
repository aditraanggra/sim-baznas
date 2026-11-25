import { PageHeader, UnggahBerkas } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, Send, Wand2 } from 'lucide-react';
import * as React from 'react';

interface TemplateItem {
    id: number;
    jenis_surat_id: number;
    nama: string;
    isi: string;
    penutup_default: string | null;
    placeholder_kustom: string[];
}

interface Props {
    surat: {
        id: number;
        jenis_surat_id: number;
        template_surat_id: number | null;
        surat_masuk_id: number | null;
        tanggal: string;
        sifat: string;
        lampiran: string | null;
        tujuan: string;
        alamat_tujuan: string | null;
        perihal: string;
        isi: string;
        data_placeholder: Record<string, string>;
        tembusan: string | null;
        nama_penandatangan: string;
        jabatan_penandatangan: string;
        catatan_revisi: string | null;
    } | null;
    surat_masuk_awal_id: number | null;
    jenis_list: { id: number; kode: string; nama: string }[];
    template_list: TemplateItem[];
    surat_masuk_list: { id: number; label: string }[];
}

export default function SuratKeluarForm({ surat, surat_masuk_awal_id, jenis_list, template_list, surat_masuk_list }: Props) {
    const isEdit = Boolean(surat);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SDM, Administrasi & Umum', href: '/umum' },
        { title: 'Surat Keluar', href: '/umum/surat/keluar' },
        { title: isEdit ? 'Ubah Draft' : 'Buat Surat Keluar', href: '#' },
    ];

    const jenisAwal = surat?.jenis_surat_id ?? jenis_list[0]?.id ?? '';
    const templateAwal = surat?.template_surat_id ?? template_list.find((t) => String(t.jenis_surat_id) === String(jenisAwal))?.id ?? '';

    const form = useForm<{
        jenis_surat_id: string;
        template_surat_id: string;
        surat_masuk_id: string;
        tanggal: string;
        sifat: string;
        lampiran: string;
        tujuan: string;
        alamat_tujuan: string;
        perihal: string;
        isi: string;
        data_placeholder: Record<string, string>;
        tembusan: string;
        nama_penandatangan: string;
        jabatan_penandatangan: string;
        berkas_lampiran: File | null;
        ajukan_persetujuan: boolean;
    }>({
        jenis_surat_id: String(jenisAwal),
        template_surat_id: templateAwal ? String(templateAwal) : '',
        surat_masuk_id: surat?.surat_masuk_id ? String(surat.surat_masuk_id) : surat_masuk_awal_id ? String(surat_masuk_awal_id) : '',
        tanggal: surat?.tanggal ?? new Date().toISOString().slice(0, 10),
        sifat: surat?.sifat ?? 'biasa',
        lampiran: surat?.lampiran ?? '',
        tujuan: surat?.tujuan ?? '',
        alamat_tujuan: surat?.alamat_tujuan ?? '',
        perihal: surat?.perihal ?? '',
        isi: surat?.isi ?? template_list.find((t) => String(t.id) === String(templateAwal))?.isi ?? '',
        data_placeholder: surat?.data_placeholder ?? {},
        tembusan: surat?.tembusan ?? '',
        nama_penandatangan: surat?.nama_penandatangan ?? 'H. Yosef Umar',
        jabatan_penandatangan: surat?.jabatan_penandatangan ?? 'Ketua BAZNAS Kabupaten Cianjur',
        berkas_lampiran: null,
        ajukan_persetujuan: false,
    });

    const templateSesuaiJenis = React.useMemo(
        () => template_list.filter((t) => String(t.jenis_surat_id) === form.data.jenis_surat_id),
        [template_list, form.data.jenis_surat_id],
    );

    const templateAktif = React.useMemo(
        () => template_list.find((t) => String(t.id) === form.data.template_surat_id) ?? null,
        [template_list, form.data.template_surat_id],
    );

    const terapkanTemplate = (tpl: TemplateItem | null, nilaiPlaceholder: Record<string, string>) => {
        if (!tpl) return;
        const konteksHeader: Record<string, string> = {
            tujuan: form.data.tujuan,
            perihal: form.data.perihal,
            tanggal_surat: form.data.tanggal,
            lampiran: form.data.lampiran,
            nama_penandatangan: form.data.nama_penandatangan,
            jabatan_penandatangan: form.data.jabatan_penandatangan,
            ...nilaiPlaceholder,
        };
        const hasil = tpl.isi.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (asli, kunci: string) => {
            const val = konteksHeader[kunci];
            return val && val.trim() !== '' ? val.trim() : asli;
        });
        form.setData('isi', hasil);
    };

    const ubahJenis = (jenisId: string) => {
        const tplBaru = template_list.find((t) => String(t.jenis_surat_id) === jenisId) ?? null;
        form.setData((prev) => ({
            ...prev,
            jenis_surat_id: jenisId,
            template_surat_id: tplBaru ? String(tplBaru.id) : '',
            isi: !isEdit && tplBaru ? tplBaru.isi : prev.isi,
        }));
    };

    const ubahTemplate = (templateId: string) => {
        const tpl = template_list.find((t) => String(t.id) === templateId) ?? null;
        form.setData((prev) => ({
            ...prev,
            template_surat_id: templateId,
            isi: tpl ? tpl.isi : prev.isi,
        }));
    };

    const kirim = (ajukan: boolean) => {
        form.transform((data) => ({
            ...data,
            ajukan_persetujuan: ajukan,
            ...(isEdit ? { _method: 'put' } : {}),
        }));

        const target = isEdit && surat ? route('umum.surat.keluar.update', surat.id) : route('umum.surat.keluar.store');
        form.post(target, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? 'Ubah Draft Surat Keluar' : 'Buat Surat Keluar'} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={isEdit ? 'Ubah Draft Surat Keluar' : 'Buat Surat Keluar'}
                    description="Pilih jenis surat & template, isi variabel placeholder, lalu sesuaikan badan surat sebelum diajukan."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Button asChild variant="outline" className="h-9">
                            <Link href={route('umum.surat.keluar.index')}>Kembali ke Daftar</Link>
                        </Button>
                    }
                />

                {surat?.catatan_revisi && (
                    <div className="bg-warning-soft border-warning/30 text-warning rounded-xl border px-4 py-3 text-sm">
                        <p className="font-semibold">Catatan Revisi Penyetuju:</p>
                        <p className="mt-0.5">{surat.catatan_revisi}</p>
                    </div>
                )}

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                            <h2 className="text-foreground text-base font-semibold">1. Jenis Surat &amp; Template</h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-jenis">Jenis Surat (wajib)</Label>
                                    <Select value={form.data.jenis_surat_id} onValueChange={ubahJenis}>
                                        <SelectTrigger id="sk-jenis" className="h-9">
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
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-template">Template Surat</Label>
                                    <Select value={form.data.template_surat_id || 'kosong'} onValueChange={(v) => ubahTemplate(v === 'kosong' ? '' : v)}>
                                        <SelectTrigger id="sk-template" className="h-9">
                                            <SelectValue placeholder="Pilih template (opsional)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="kosong">Tanpa template</SelectItem>
                                            {templateSesuaiJenis.map((t) => (
                                                <SelectItem key={t.id} value={String(t.id)}>
                                                    {t.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {templateAktif && templateAktif.placeholder_kustom.length > 0 && (
                                <div className="bg-surface-subtle space-y-3 rounded-xl border p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="text-foreground text-sm font-medium">Variabel Placeholder Template</p>
                                            <p className="text-muted-foreground text-xs">
                                                Isi variabel di bawah lalu klik Terapkan ke Badan Surat.
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="h-8 gap-1.5 text-xs"
                                            onClick={() => terapkanTemplate(templateAktif, form.data.data_placeholder)}
                                        >
                                            <Wand2 className="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Terapkan ke Badan Surat</span>
                                        </Button>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {templateAktif.placeholder_kustom.map((token) => (
                                            <div key={token} className="space-y-1">
                                                <Label htmlFor={`ph-${token}`} className="text-xs capitalize">
                                                    {token.replaceAll('_', ' ')}
                                                </Label>
                                                <Input
                                                    id={`ph-${token}`}
                                                    value={form.data.data_placeholder[token] ?? ''}
                                                    onChange={(e) =>
                                                        form.setData('data_placeholder', {
                                                            ...form.data.data_placeholder,
                                                            [token]: e.target.value,
                                                        })
                                                    }
                                                    placeholder={`Isi ${token.replaceAll('_', ' ')}`}
                                                    className="h-9"
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                            <h2 className="text-foreground text-base font-semibold">2. Header, Tujuan &amp; Badan Surat</h2>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-tgl">Tanggal Surat (wajib)</Label>
                                    <Input
                                        id="sk-tgl"
                                        type="date"
                                        value={form.data.tanggal}
                                        onChange={(e) => form.setData('tanggal', e.target.value)}
                                        className="h-9"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-sifat">Sifat Surat</Label>
                                    <Select value={form.data.sifat} onValueChange={(v) => form.setData('sifat', v)}>
                                        <SelectTrigger id="sk-sifat" className="h-9">
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
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-lampiran">Lampiran (opsional)</Label>
                                    <Input
                                        id="sk-lampiran"
                                        value={form.data.lampiran}
                                        onChange={(e) => form.setData('lampiran', e.target.value)}
                                        placeholder="Mis. 1 (satu) berkas"
                                        className="h-9"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="sk-tujuan">Kepada / Tujuan Surat (wajib)</Label>
                                <Input
                                    id="sk-tujuan"
                                    value={form.data.tujuan}
                                    onChange={(e) => form.setData('tujuan', e.target.value)}
                                    placeholder="Mis. Kepala Kantor Kemenag Kabupaten Cianjur"
                                    className="h-9"
                                />
                                {form.errors.tujuan && <p className="text-destructive text-xs">{form.errors.tujuan}</p>}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-perihal">Perihal (wajib)</Label>
                                    <Input
                                        id="sk-perihal"
                                        value={form.data.perihal}
                                        onChange={(e) => form.setData('perihal', e.target.value)}
                                        placeholder="Pokok isi surat keluar"
                                        className="h-9"
                                    />
                                    {form.errors.perihal && <p className="text-destructive text-xs">{form.errors.perihal}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-masuk-ref">Balasan dari Surat Masuk (opsional)</Label>
                                    <Select
                                        value={form.data.surat_masuk_id || 'none'}
                                        onValueChange={(v) => form.setData('surat_masuk_id', v === 'none' ? '' : v)}
                                    >
                                        <SelectTrigger id="sk-masuk-ref" className="h-9">
                                            <SelectValue placeholder="Tidak menautkan surat masuk" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Tidak menautkan surat masuk</SelectItem>
                                            {surat_masuk_list.map((sm) => (
                                                <SelectItem key={sm.id} value={String(sm.id)}>
                                                    {sm.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="sk-isi">Isi Badan Surat (wajib)</Label>
                                <textarea
                                    id="sk-isi"
                                    rows={12}
                                    value={form.data.isi}
                                    onChange={(e) => form.setData('isi', e.target.value)}
                                    className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm leading-relaxed"
                                />
                                {form.errors.isi && <p className="text-destructive text-xs">{form.errors.isi}</p>}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-nama-ttd">Nama Penandatangan (wajib)</Label>
                                    <Input
                                        id="sk-nama-ttd"
                                        value={form.data.nama_penandatangan}
                                        onChange={(e) => form.setData('nama_penandatangan', e.target.value)}
                                        className="h-9"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="sk-jabatan-ttd">Jabatan Penandatangan (wajib)</Label>
                                    <Input
                                        id="sk-jabatan-ttd"
                                        value={form.data.jabatan_penandatangan}
                                        onChange={(e) => form.setData('jabatan_penandatangan', e.target.value)}
                                        className="h-9"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="sk-tembusan">Tembusan (opsional)</Label>
                                <textarea
                                    id="sk-tembusan"
                                    rows={2}
                                    value={form.data.tembusan}
                                    onChange={(e) => form.setData('tembusan', e.target.value)}
                                    placeholder="1. Arsip&#10;2. Wakil Ketua Bidang terkait"
                                    className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                />
                            </div>

                            <UnggahBerkas
                                label="Lampiran Berkas Pendukung (opsional)"
                                value={form.data.berkas_lampiran}
                                onChange={(file) => form.setData('berkas_lampiran', file)}
                            />
                        </section>
                    </div>

                    <div className="lg:col-span-1">
                        <div className="bg-card border-border sticky top-20 space-y-4 rounded-2xl border p-5">
                            <h2 className="text-foreground text-base font-semibold">Ringkasan Surat Keluar</h2>
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between gap-2">
                                    <dt className="text-muted-foreground">Nomor</dt>
                                    <dd className="font-medium">Terbit saat Disetujui</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt className="text-muted-foreground">Tujuan</dt>
                                    <dd className="max-w-[180px] truncate font-medium">{form.data.tujuan || '-'}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt className="text-muted-foreground">Sifat</dt>
                                    <dd className="font-medium uppercase">{form.data.sifat}</dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt className="text-muted-foreground">Penandatangan</dt>
                                    <dd className="max-w-[180px] truncate text-right">{form.data.nama_penandatangan}</dd>
                                </div>
                            </dl>

                            <div className="space-y-2 border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-10 w-full gap-1.5"
                                    disabled={form.processing}
                                    onClick={() => kirim(false)}
                                >
                                    <Save className="h-4 w-4" aria-hidden="true" />
                                    <span>Simpan Draft</span>
                                </Button>
                                <Button type="button" className="h-10 w-full gap-1.5" disabled={form.processing} onClick={() => kirim(true)}>
                                    <Send className="h-4 w-4" aria-hidden="true" />
                                    <span>Simpan &amp; Ajukan Persetujuan</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
