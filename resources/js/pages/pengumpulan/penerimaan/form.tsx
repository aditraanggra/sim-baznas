import { InputNIK, InputRupiah, PageHeader } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Printer, Save, Trash2 } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengumpulan', href: '/pengumpulan' },
    { title: 'Input Penerimaan', href: '/pengumpulan/penerimaan/buat' },
];

type ItemBaris = {
    sumber_dana_id: string;
    jumlah: number;
    keterangan: string;
};

type PenerimaanFormData = {
    id: string;
    tanggal: string;
    muzakki_id: string;
    upz_id: string;
    metode_bayar_id: string;
    rekening_id: string;
    catatan: string;
    items: ItemBaris[];
};

interface Props {
    draft: {
        id: number;
        tanggal: string;
        muzakki_id: number;
        upz_id: number | null;
        metode_bayar_id: number;
        rekening_id: number | null;
        catatan: string | null;
        items: { sumber_dana_id: number; jumlah: number; keterangan: string }[];
    } | null;
    muzakki_list: { id: number; kode: string; nama: string; nik_masked: string | null; telepon: string | null }[];
    sumber_dana_list: { id: number; kode: string; nama: string; jenis_dana: string | null }[];
    metode_bayar_list: { id: number; kode: string; nama: string; butuh_rekening: boolean }[];
    rekening_list: { id: number; bank: string; no_rekening: string; atas_nama: string }[];
    upz_list: { id: number; kode: string; nama: string }[];
    desa_list: { id: number; nama: string; kecamatan: string | null }[];
}

const AUTOSAVE_KEY = 'sisfozis_penerimaan_draft_autosave';

function angkaKeKata(n: number): string {
    const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    if (n === 0) return 'nol';
    if (n < 12) return satuan[n];
    if (n < 20) return `${angkaKeKata(n - 10)} belas`;
    if (n < 100) return `${angkaKeKata(Math.floor(n / 10))} puluh${n % 10 ? ` ${angkaKeKata(n % 10)}` : ''}`;
    if (n < 200) return `seratus${n - 100 ? ` ${angkaKeKata(n - 100)}` : ''}`;
    if (n < 1000) return `${angkaKeKata(Math.floor(n / 100))} ratus${n % 100 ? ` ${angkaKeKata(n % 100)}` : ''}`;
    if (n < 2000) return `seribu${n - 1000 ? ` ${angkaKeKata(n - 1000)}` : ''}`;
    if (n < 1_000_000) return `${angkaKeKata(Math.floor(n / 1000))} ribu${n % 1000 ? ` ${angkaKeKata(n % 1000)}` : ''}`;
    if (n < 1_000_000_000) return `${angkaKeKata(Math.floor(n / 1_000_000))} juta${n % 1_000_000 ? ` ${angkaKeKata(n % 1_000_000)}` : ''}`;
    if (n < 1_000_000_000_000) return `${angkaKeKata(Math.floor(n / 1_000_000_000))} miliar${n % 1_000_000_000 ? ` ${angkaKeKata(n % 1_000_000_000)}` : ''}`;
    return `${angkaKeKata(Math.floor(n / 1_000_000_000_000))} triliun`;
}

export default function PenerimaanForm({
    draft,
    muzakki_list,
    sumber_dana_list,
    metode_bayar_list,
    rekening_list,
    upz_list,
    desa_list,
}: Props) {
    const hariIni = new Date().toISOString().slice(0, 10);
    const [dialogMuzakki, setDialogMuzakki] = React.useState(false);
    const [waktuAutosave, setWaktuAutosave] = React.useState<string | null>(null);
    const itemSumberRefs = React.useRef<(HTMLSelectElement | null)[]>([]);

    const awalItems: ItemBaris[] = draft?.items?.length
        ? draft.items.map((i) => ({ sumber_dana_id: String(i.sumber_dana_id), jumlah: i.jumlah, keterangan: i.keterangan }))
        : [{ sumber_dana_id: sumber_dana_list[0] ? String(sumber_dana_list[0].id) : '', jumlah: 0, keterangan: '' }];

    const form = useForm<PenerimaanFormData>({
        id: draft ? String(draft.id) : '',
        tanggal: draft?.tanggal ?? hariIni,
        muzakki_id: draft ? String(draft.muzakki_id) : '',
        upz_id: draft?.upz_id ? String(draft.upz_id) : '',
        metode_bayar_id: draft ? String(draft.metode_bayar_id) : metode_bayar_list[0] ? String(metode_bayar_list[0].id) : '',
        rekening_id: draft?.rekening_id ? String(draft.rekening_id) : '',
        catatan: draft?.catatan ?? '',
        items: awalItems,
    });

    const formMuzakki = useForm({
        jenis: 'perorangan',
        nama: '',
        nik: '',
        telepon: '',
        desa_id: '',
        alamat: '',
    });

    // Pulihkan autosave lokal jika membuat transaksi baru
    React.useEffect(() => {
        if (draft) return;
        try {
            const tersimpan = localStorage.getItem(AUTOSAVE_KEY);
            if (!tersimpan) return;
            const parsed = JSON.parse(tersimpan) as Partial<PenerimaanFormData>;
            if (parsed.items && parsed.items.length > 0) {
                form.setData((prev) => ({ ...prev, ...parsed }));
            }
        } catch {
            // Abaikan JSON rusak
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Autosave perubahan draft secara otomatis ke localStorage
    React.useEffect(() => {
        const timer = window.setTimeout(() => {
            try {
                localStorage.setItem(AUTOSAVE_KEY, JSON.stringify(form.data));
                setWaktuAutosave(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            } catch {
                // Abaikan bila storage penuh
            }
        }, 600);
        return () => window.clearTimeout(timer);
    }, [form.data]);

    const metodeTerpilih = React.useMemo(
        () => metode_bayar_list.find((m) => String(m.id) === form.data.metode_bayar_id) ?? null,
        [metode_bayar_list, form.data.metode_bayar_id],
    );

    const opsiMuzakki = React.useMemo(
        () =>
            muzakki_list.map((m) => ({
                value: String(m.id),
                label: m.nama,
                sublabel: [m.kode, m.nik_masked, m.telepon].filter(Boolean).join(' · '),
            })),
        [muzakki_list],
    );

    const opsiUpz = React.useMemo(
        () => [{ value: '', label: 'Setoran Langsung (Tanpa UPZ)' }, ...upz_list.map((u) => ({ value: String(u.id), label: `${u.kode} - ${u.nama}` }))],
        [upz_list],
    );

    const opsiDesa = React.useMemo(
        () => desa_list.map((d) => ({ value: String(d.id), label: d.nama, sublabel: d.kecamatan ? `Kec. ${d.kecamatan}` : undefined })),
        [desa_list],
    );

    const totalNominal = React.useMemo(() => form.data.items.reduce((acc, item) => acc + (Number(item.jumlah) || 0), 0), [form.data.items]);
    const teksTerbilang = totalNominal > 0 ? `${angkaKeKata(totalNominal).charAt(0).toUpperCase()}${angkaKeKata(totalNominal).slice(1)} rupiah` : '-';

    const tambahBaris = (fokusBaru = true) => {
        const idxBaru = form.data.items.length;
        form.setData('items', [
            ...form.data.items,
            { sumber_dana_id: sumber_dana_list[0] ? String(sumber_dana_list[0].id) : '', jumlah: 0, keterangan: '' },
        ]);
        if (fokusBaru) {
            window.setTimeout(() => itemSumberRefs.current[idxBaru]?.focus(), 30);
        }
    };

    const ubahItem = (idx: number, patch: Partial<ItemBaris>) => {
        form.setData(
            'items',
            form.data.items.map((item, i) => (i === idx ? { ...item, ...patch } : item)),
        );
    };

    const hapusItem = (idx: number) => {
        if (form.data.items.length <= 1) return;
        form.setData(
            'items',
            form.data.items.filter((_, i) => i !== idx),
        );
    };

    const tanganiEnterBaris = (e: React.KeyboardEvent, idx: number) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (idx === form.data.items.length - 1) {
                tambahBaris(true);
            } else {
                itemSumberRefs.current[idx + 1]?.focus();
            }
        }
    };

    const simpanDraft = () => {
        form.post(route('pengumpulan.penerimaan.draft'), {
            preserveScroll: true,
        });
    };

    const finalkanDanCetak = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pengumpulan.penerimaan.final'), {
            preserveScroll: true,
            onSuccess: () => localStorage.removeItem(AUTOSAVE_KEY),
        });
    };

    const simpanMuzakkiCepat = (e: React.FormEvent) => {
        e.preventDefault();
        formMuzakki.post(route('pengumpulan.muzakki.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setDialogMuzakki(false);
                formMuzakki.reset();
                router.reload({ only: ['muzakki_list'] });
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Input Penerimaan Dana ZIS" />

            <form onSubmit={finalkanDanCetak} className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 pb-24 sm:p-6">
                <PageHeader
                    title={draft ? `Lanjutkan Draft Penerimaan #${draft.id}` : 'Input Penerimaan Dana ZIS'}
                    description="Tekan Enter di kolom nominal atau keterangan untuk menambah baris item penerimaan berikutnya."
                    breadcrumbs={breadcrumbs}
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <section className="bg-card border-border space-y-4 rounded-xl border p-5">
                            <h2 className="text-foreground text-base font-semibold">1. Identitas Muzakki & Tanggal</h2>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="pnr-tanggal">Tanggal Transaksi (wajib)</Label>
                                    <Input
                                        id="pnr-tanggal"
                                        type="date"
                                        required
                                        value={form.data.tanggal}
                                        onChange={(e) => form.setData('tanggal', e.target.value)}
                                        className="h-9"
                                    />
                                    {form.errors.tanggal && <p className="text-destructive text-xs">{form.errors.tanggal}</p>}
                                </div>

                                <div className="space-y-1.5 sm:col-span-2">
                                    <Label htmlFor="pnr-muzakki">Pilih Muzakki (wajib)</Label>
                                    <Combobox
                                        id="pnr-muzakki"
                                        options={opsiMuzakki}
                                        value={form.data.muzakki_id}
                                        onChange={(v) => form.setData('muzakki_id', v)}
                                        placeholder="Cari nama, kode, atau telepon muzakki..."
                                        createNewLabel="Tambah muzakki baru"
                                        onCreateNew={(q) => {
                                            formMuzakki.setData('nama', q);
                                            setDialogMuzakki(true);
                                        }}
                                    />
                                    {form.errors.muzakki_id && <p className="text-destructive text-xs">{form.errors.muzakki_id}</p>}
                                </div>
                            </div>
                        </section>

                        <section className="bg-card border-border space-y-4 rounded-xl border p-5">
                            <div className="flex items-center justify-between gap-2">
                                <h2 className="text-foreground text-base font-semibold">2. Rincian Item Penerimaan</h2>
                                <Button type="button" variant="outline" size="sm" onClick={() => tambahBaris(true)} className="h-8 gap-1 text-xs">
                                    <Plus className="h-3.5 w-3.5" aria-hidden="true" />
                                    <span>Tambah Baris</span>
                                </Button>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-muted-foreground border-b text-left text-xs uppercase">
                                            <th className="py-2 pr-2">Sumber Dana (wajib)</th>
                                            <th className="w-48 px-2 py-2">Nominal Rupiah (wajib)</th>
                                            <th className="px-2 py-2">Keterangan (Enter = baris baru)</th>
                                            <th className="w-10 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {form.data.items.map((item, idx) => (
                                            <tr key={idx} className="border-b last:border-0">
                                                <td className="py-2 pr-2 align-top">
                                                    <select
                                                        ref={(el) => {
                                                            itemSumberRefs.current[idx] = el;
                                                        }}
                                                        aria-label={`Sumber dana baris ${idx + 1}`}
                                                        required
                                                        value={item.sumber_dana_id}
                                                        onChange={(e) => ubahItem(idx, { sumber_dana_id: e.target.value })}
                                                        className="border-border-strong bg-card h-9 w-full rounded-md border px-2.5 text-sm"
                                                    >
                                                        <option value="">Pilih sumber dana</option>
                                                        {sumber_dana_list.map((sd) => (
                                                            <option key={sd.id} value={String(sd.id)}>
                                                                {sd.kode} - {sd.nama} ({sd.jenis_dana})
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td className="px-2 py-2 align-top" onKeyDown={(e) => tanganiEnterBaris(e, idx)}>
                                                    <InputRupiah
                                                        value={item.jumlah}
                                                        onChange={(nilai) => ubahItem(idx, { jumlah: nilai })}
                                                    />
                                                </td>
                                                <td className="px-2 py-2 align-top">
                                                    <Input
                                                        aria-label={`Keterangan baris ${idx + 1}`}
                                                        value={item.keterangan}
                                                        onChange={(e) => ubahItem(idx, { keterangan: e.target.value })}
                                                        onKeyDown={(e) => tanganiEnterBaris(e, idx)}
                                                        placeholder="Contoh: Zakat penghasilan bulan ini"
                                                        className="h-9"
                                                    />
                                                </td>
                                                <td className="py-2 pl-2 text-right align-top">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Hapus baris ${idx + 1}`}
                                                        disabled={form.data.items.length <= 1}
                                                        onClick={() => hapusItem(idx)}
                                                        className="text-destructive h-9 w-9"
                                                    >
                                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {form.errors.items && <p className="text-destructive text-xs">{form.errors.items}</p>}
                        </section>

                        <section className="bg-card border-border space-y-4 rounded-xl border p-5">
                            <h2 className="text-foreground text-base font-semibold">3. Metode Pembayaran & UPZ</h2>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="pnr-metode">Metode Bayar (wajib)</Label>
                                    <Select value={form.data.metode_bayar_id} onValueChange={(v) => form.setData('metode_bayar_id', v)}>
                                        <SelectTrigger id="pnr-metode" className="h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {metode_bayar_list.map((m) => (
                                                <SelectItem key={m.id} value={String(m.id)}>
                                                    {m.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {metodeTerpilih?.butuh_rekening && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="pnr-rekening">Rekening Bank Tujuan (wajib)</Label>
                                        <Select value={form.data.rekening_id} onValueChange={(v) => form.setData('rekening_id', v)}>
                                            <SelectTrigger id="pnr-rekening" className="h-9">
                                                <SelectValue placeholder="Pilih rekening bank" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {rekening_list.map((r) => (
                                                    <SelectItem key={r.id} value={String(r.id)}>
                                                        {r.bank} - {r.no_rekening}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {form.errors.rekening_id && <p className="text-destructive text-xs">{form.errors.rekening_id}</p>}
                                    </div>
                                )}

                                <div className="space-y-1.5">
                                    <Label htmlFor="pnr-upz">Melalui UPZ (opsional)</Label>
                                    <Combobox
                                        id="pnr-upz"
                                        options={opsiUpz}
                                        value={form.data.upz_id}
                                        onChange={(v) => form.setData('upz_id', v)}
                                        placeholder="Setoran Langsung (Tanpa UPZ)"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="pnr-catatan">Catatan Transaksi</Label>
                                    <Input
                                        id="pnr-catatan"
                                        value={form.data.catatan}
                                        onChange={(e) => form.setData('catatan', e.target.value)}
                                        placeholder="Catatan opsional"
                                        className="h-9"
                                    />
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside className="lg:col-span-1">
                        <div className="bg-card border-border sticky top-6 space-y-4 rounded-xl border p-5">
                            <div className="flex items-center justify-between border-b pb-3">
                                <h2 className="text-foreground text-base font-semibold">Ringkasan Penerimaan</h2>
                                <span className="bg-surface-subtle border-border text-muted-foreground rounded-md border px-2 py-0.5 text-xs">
                                    Draft
                                </span>
                            </div>

                            <div className="space-y-1">
                                <p className="text-muted-foreground text-xs">Total Penerimaan</p>
                                <p className="text-foreground text-3xl font-semibold tabular-nums">{formatRupiah(totalNominal)}</p>
                            </div>

                            <div className="bg-surface-subtle border-border rounded-lg border p-3">
                                <p className="text-muted-foreground text-xs font-medium">Terbilang</p>
                                <p className="text-foreground mt-0.5 text-xs italic">{teksTerbilang}</p>
                            </div>

                            {waktuAutosave && (
                                <p className="text-muted-foreground text-xs">Draft lokal tersimpan otomatis pukul {waktuAutosave}</p>
                            )}

                            <div className="space-y-2 border-t pt-4">
                                <Button type="submit" disabled={form.processing} className="h-10 w-full gap-1.5">
                                    <Printer className="h-4 w-4" aria-hidden="true" />
                                    <span>{form.processing ? 'Memproses...' : 'Finalkan dan Cetak Kwitansi'}</span>
                                </Button>

                                <Button type="button" variant="outline" disabled={form.processing} onClick={simpanDraft} className="h-9 w-full gap-1.5">
                                    <Save className="h-4 w-4" aria-hidden="true" />
                                    <span>Simpan sebagai Draft</span>
                                </Button>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>

            <Dialog open={dialogMuzakki} onOpenChange={setDialogMuzakki}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Cepat Muzakki</DialogTitle>
                        <DialogDescription>Data muzakki baru langsung tersedia di daftar pilihan form penerimaan.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={simpanMuzakkiCepat} className="space-y-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="qm-jenis">Jenis</Label>
                            <Select value={formMuzakki.data.jenis} onValueChange={(v) => formMuzakki.setData('jenis', v)}>
                                <SelectTrigger id="qm-jenis" className="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="perorangan">Perorangan</SelectItem>
                                    <SelectItem value="lembaga">Lembaga</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="qm-nama">Nama Lengkap / Lembaga (wajib)</Label>
                            <Input id="qm-nama" required value={formMuzakki.data.nama} onChange={(e) => formMuzakki.setData('nama', e.target.value)} className="h-9" />
                        </div>

                        {formMuzakki.data.jenis === 'perorangan' && (
                            <InputNIK value={formMuzakki.data.nik} onChange={(nik) => formMuzakki.setData('nik', nik)} error={formMuzakki.errors.nik} />
                        )}

                        <div className="space-y-1.5">
                            <Label htmlFor="qm-telp">Telepon</Label>
                            <Input id="qm-telp" value={formMuzakki.data.telepon} onChange={(e) => formMuzakki.setData('telepon', e.target.value)} className="h-9" />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="qm-desa">Desa / Kelurahan</Label>
                            <Combobox options={opsiDesa} value={formMuzakki.data.desa_id} onChange={(v) => formMuzakki.setData('desa_id', v)} placeholder="Pilih desa..." />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogMuzakki(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={formMuzakki.processing} className="h-9">
                                Simpan Muzakki
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
