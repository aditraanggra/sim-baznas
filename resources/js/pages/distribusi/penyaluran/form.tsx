import { InputRupiah, PageHeader } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Plus, Save, Trash2 } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Input Penyaluran', href: '/distribusi/penyaluran/buat' },
];

type ItemPenyaluran = {
    sumber_dana_id: string;
    asnaf_id: string;
    bentuk_bantuan_id: string;
    uraian_barang: string;
    kuantitas: string;
    satuan: string;
    jumlah: number;
    keterangan: string;
};

type PenyaluranFormData = {
    id: string;
    tanggal: string;
    permohonan_id: string;
    mustahik_id: string;
    program_id: string;
    metode_bayar_id: string;
    rekening_id: string;
    catatan: string;
    items: ItemPenyaluran[];
};

interface Props {
    draft: {
        id: number;
        tanggal: string;
        permohonan_id: number | null;
        mustahik_id: number;
        program_id: number;
        metode_bayar_id: number;
        rekening_id: number | null;
        catatan: string | null;
        items: {
            sumber_dana_id: number;
            asnaf_id: number | null;
            bentuk_bantuan_id: number;
            uraian_barang: string;
            kuantitas: string;
            satuan: string;
            jumlah: number;
            keterangan: string;
        }[];
    } | null;
    prefill_permohonan: {
        id: number;
        nomor: string;
        mustahik_id: number;
        program_id: number;
        asnaf_id: number | null;
        nominal: number;
    } | null;
    mustahik_list: { id: number; kode: string; nama: string }[];
    program_list: { id: number; kode: string; nama: string; sub_bidang: string | null }[];
    sumber_dana_list: {
        id: number;
        kode: string;
        nama: string;
        jenis_dana: string | null;
        wajib_asnaf: boolean;
        kelompok_id: number;
        kelompok_nama: string | null;
    }[];
    asnaf_list: { id: number; kode: string; nama: string }[];
    bentuk_bantuan_list: { id: number; kode: string; nama: string }[];
    metode_bayar_list: { id: number; kode: string; nama: string; butuh_rekening: boolean }[];
    rekening_list: { id: number; bank: string; no_rekening: string; atas_nama: string }[];
    saldo_kelompok: { dana_kelompok_id: number; nama: string; saldo_tersedia: number }[];
}

export default function PenyaluranForm({
    draft,
    prefill_permohonan,
    mustahik_list,
    program_list,
    sumber_dana_list,
    asnaf_list,
    bentuk_bantuan_list,
    metode_bayar_list,
    rekening_list,
    saldo_kelompok,
}: Props) {
    const sumberAwal = sumber_dana_list[0];
    const bentukAwal = bentuk_bantuan_list[0];

    const awalItems: ItemPenyaluran[] = draft?.items?.length
        ? draft.items.map((i) => ({
              sumber_dana_id: String(i.sumber_dana_id),
              asnaf_id: i.asnaf_id ? String(i.asnaf_id) : '',
              bentuk_bantuan_id: String(i.bentuk_bantuan_id),
              uraian_barang: i.uraian_barang,
              kuantitas: i.kuantitas,
              satuan: i.satuan,
              jumlah: i.jumlah,
              keterangan: i.keterangan,
          }))
        : [
              {
                  sumber_dana_id: sumberAwal ? String(sumberAwal.id) : '',
                  asnaf_id: sumberAwal?.wajib_asnaf
                      ? String(prefill_permohonan?.asnaf_id ?? asnaf_list[0]?.id ?? '')
                      : '',
                  bentuk_bantuan_id: bentukAwal ? String(bentukAwal.id) : '',
                  uraian_barang: '',
                  kuantitas: '',
                  satuan: '',
                  jumlah: prefill_permohonan?.nominal ?? 0,
                  keterangan: '',
              },
          ];

    const form = useForm<PenyaluranFormData>({
        id: draft ? String(draft.id) : '',
        tanggal: draft?.tanggal ?? new Date().toISOString().slice(0, 10),
        permohonan_id: draft?.permohonan_id ? String(draft.permohonan_id) : prefill_permohonan ? String(prefill_permohonan.id) : '',
        mustahik_id: draft ? String(draft.mustahik_id) : prefill_permohonan ? String(prefill_permohonan.mustahik_id) : '',
        program_id: draft ? String(draft.program_id) : prefill_permohonan ? String(prefill_permohonan.program_id) : program_list[0] ? String(program_list[0].id) : '',
        metode_bayar_id: draft ? String(draft.metode_bayar_id) : metode_bayar_list[0] ? String(metode_bayar_list[0].id) : '',
        rekening_id: draft?.rekening_id ? String(draft.rekening_id) : '',
        catatan: draft?.catatan ?? '',
        items: awalItems,
    });

    const metodeTerpilih = metode_bayar_list.find((m) => String(m.id) === form.data.metode_bayar_id);
    const butuhRekening = Boolean(metodeTerpilih?.butuh_rekening);
    const total = form.data.items.reduce((acc, i) => acc + (i.jumlah || 0), 0);

    const peringatanKelompok = React.useMemo(() => {
        const kebutuhan = new Map<number, number>();
        for (const item of form.data.items) {
            const sumber = sumber_dana_list.find((s) => String(s.id) === item.sumber_dana_id);
            if (!sumber) continue;
            kebutuhan.set(sumber.kelompok_id, (kebutuhan.get(sumber.kelompok_id) ?? 0) + (item.jumlah || 0));
        }

        const pesan: string[] = [];
        kebutuhan.forEach((dibutuhkan, kelompokId) => {
            const saldo = saldo_kelompok.find((s) => s.dana_kelompok_id === kelompokId);
            if (saldo && saldo.saldo_tersedia < dibutuhkan) {
                pesan.push(
                    `Saldo Kelompok Dana ${saldo.nama} (${formatRupiah(saldo.saldo_tersedia)}) lebih kecil dari kebutuhan (${formatRupiah(dibutuhkan)}).`,
                );
            }
        });
        return pesan;
    }, [form.data.items, sumber_dana_list, saldo_kelompok]);

    const ubahItem = (idx: number, patch: Partial<ItemPenyaluran>) => {
        form.setData(
            'items',
            form.data.items.map((item, i) => (i === idx ? { ...item, ...patch } : item)),
        );
    };

    const ubahSumberDana = (idx: number, sumberDanaId: string) => {
        const sumber = sumber_dana_list.find((s) => String(s.id) === sumberDanaId);
        const asnafBaru = sumber?.wajib_asnaf ? form.data.items[idx].asnaf_id || String(asnaf_list[0]?.id ?? '') : '';
        ubahItem(idx, { sumber_dana_id: sumberDanaId, asnaf_id: asnafBaru });
    };

    const tambahBaris = () => {
        form.setData('items', [
            ...form.data.items,
            {
                sumber_dana_id: sumberAwal ? String(sumberAwal.id) : '',
                asnaf_id: sumberAwal?.wajib_asnaf ? String(asnaf_list[0]?.id ?? '') : '',
                bentuk_bantuan_id: bentukAwal ? String(bentukAwal.id) : '',
                uraian_barang: '',
                kuantitas: '',
                satuan: '',
                jumlah: 0,
                keterangan: '',
            },
        ]);
    };

    const hapusBaris = (idx: number) => {
        if (form.data.items.length <= 1) return;
        form.setData(
            'items',
            form.data.items.filter((_, i) => i !== idx),
        );
    };

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('distribusi.penyaluran.draft'), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Input Penyaluran Bantuan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Input Penyaluran Bantuan"
                    description={
                        prefill_permohonan
                            ? `Terkait Permohonan ${prefill_permohonan.nomor}.`
                            : 'Catat rincian penyaluran uang atau barang per sumber dana dan asnaf.'
                    }
                    breadcrumbs={breadcrumbs}
                />

                {peringatanKelompok.length > 0 && (
                    <div className="border-warning/40 bg-warning/10 text-foreground flex items-start gap-2.5 rounded-2xl border p-4 text-sm">
                        <AlertTriangle className="text-warning mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <div className="space-y-1">
                            <p className="font-semibold">Peringatan Saldo Kelompok Dana (K11)</p>
                            {peringatanKelompok.map((msg) => (
                                <p key={msg} className="text-xs">
                                    {msg}
                                </p>
                            ))}
                        </div>
                    </div>
                )}

                <form onSubmit={simpan} className="space-y-5">
                    <section className="bg-card border-border grid gap-4 rounded-2xl border p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="py-tanggal">Tanggal Penyaluran</Label>
                            <Input
                                id="py-tanggal"
                                type="date"
                                value={form.data.tanggal}
                                onChange={(e) => form.setData('tanggal', e.target.value)}
                                required
                            />
                            {form.errors.tanggal && <p className="text-destructive text-xs">{form.errors.tanggal}</p>}
                        </div>

                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="py-mustahik">Mustahik Penerima</Label>
                            <Combobox
                                value={form.data.mustahik_id}
                                onChange={(v) => form.setData('mustahik_id', v)}
                                placeholder="Pilih mustahik..."
                                options={mustahik_list.map((m) => ({ value: String(m.id), label: `${m.nama} (${m.kode})` }))}
                            />
                            {form.errors.mustahik_id && <p className="text-destructive text-xs">{form.errors.mustahik_id}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="py-program">Program Penyaluran</Label>
                            <Select value={form.data.program_id} onValueChange={(v) => form.setData('program_id', v)}>
                                <SelectTrigger id="py-program">
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

                        <div className="space-y-1.5">
                            <Label htmlFor="py-metode">Metode Bayar</Label>
                            <Select value={form.data.metode_bayar_id} onValueChange={(v) => form.setData('metode_bayar_id', v)}>
                                <SelectTrigger id="py-metode">
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

                        {butuhRekening && (
                            <div className="space-y-1.5 sm:col-span-2">
                                <Label htmlFor="py-rekening">Rekening Sumber BAZNAS</Label>
                                <Select value={form.data.rekening_id} onValueChange={(v) => form.setData('rekening_id', v)}>
                                    <SelectTrigger id="py-rekening">
                                        <SelectValue placeholder="Pilih rekening bank" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rekening_list.map((r) => (
                                            <SelectItem key={r.id} value={String(r.id)}>
                                                {r.bank} - {r.no_rekening} ({r.atas_nama})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.rekening_id && <p className="text-destructive text-xs">{form.errors.rekening_id}</p>}
                            </div>
                        )}

                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="py-catatan">Catatan Penyaluran</Label>
                            <Input id="py-catatan" value={form.data.catatan} onChange={(e) => form.setData('catatan', e.target.value)} />
                        </div>
                    </section>

                    <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="text-foreground text-base font-semibold">Rincian Sumber Dana, Asnaf & Bentuk Bantuan</h2>
                                <p className="text-muted-foreground text-xs">
                                    Zakat wajib memilih asnaf, sedangkan Infak, DSKL, dan CSR tanpa asnaf (K10). Bantuan barang wajib menyertakan taksiran rupiah (Q-16).
                                </p>
                            </div>
                            <Button type="button" variant="outline" size="sm" className="gap-1.5" onClick={tambahBaris}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Tambah Baris</span>
                            </Button>
                        </div>

                        <div className="space-y-4">
                            {form.data.items.map((item, idx) => {
                                const sumber = sumber_dana_list.find((s) => String(s.id) === item.sumber_dana_id);
                                const wajibAsnaf = Boolean(sumber?.wajib_asnaf);
                                const bentuk = bentuk_bantuan_list.find((b) => String(b.id) === item.bentuk_bantuan_id);
                                const adalahBarang = bentuk?.kode === 'BARANG';

                                return (
                                    <div key={idx} className="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor={`item-sd-${idx}`}>Sumber Dana</Label>
                                            <Select value={item.sumber_dana_id} onValueChange={(v) => ubahSumberDana(idx, v)}>
                                                <SelectTrigger id={`item-sd-${idx}`}>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {sumber_dana_list.map((s) => (
                                                        <SelectItem key={s.id} value={String(s.id)}>
                                                            {s.nama} ({s.jenis_dana})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor={`item-asnaf-${idx}`}>Asnaf {wajibAsnaf ? '(Wajib)' : '(Non-Zakat)'}</Label>
                                            <Select
                                                value={item.asnaf_id}
                                                onValueChange={(v) => ubahItem(idx, { asnaf_id: v })}
                                                disabled={!wajibAsnaf}
                                            >
                                                <SelectTrigger id={`item-asnaf-${idx}`}>
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
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor={`item-bentuk-${idx}`}>Bentuk Bantuan</Label>
                                            <Select value={item.bentuk_bantuan_id} onValueChange={(v) => ubahItem(idx, { bentuk_bantuan_id: v })}>
                                                <SelectTrigger id={`item-bentuk-${idx}`}>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {bentuk_bantuan_list.map((b) => (
                                                        <SelectItem key={b.id} value={String(b.id)}>
                                                            {b.nama}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor={`item-jumlah-${idx}`}>{adalahBarang ? 'Taksiran Rupiah (Rp)' : 'Nominal (Rp)'}</Label>
                                            <div className="flex gap-2">
                                                <InputRupiah
                                                    id={`item-jumlah-${idx}`}
                                                    value={item.jumlah}
                                                    onChange={(v) => ubahItem(idx, { jumlah: v })}
                                                />
                                                {form.data.items.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Hapus baris item"
                                                        onClick={() => hapusBaris(idx)}
                                                    >
                                                        <Trash2 className="text-destructive h-4 w-4" aria-hidden="true" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        {adalahBarang && (
                                            <>
                                                <div className="space-y-1.5 sm:col-span-2">
                                                    <Label htmlFor={`item-barang-${idx}`}>Uraian Barang Bantuan</Label>
                                                    <Input
                                                        id={`item-barang-${idx}`}
                                                        value={item.uraian_barang}
                                                        onChange={(e) => ubahItem(idx, { uraian_barang: e.target.value })}
                                                        placeholder="Contoh: Paket sembako / gerobak usaha"
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor={`item-qty-${idx}`}>Kuantitas</Label>
                                                    <Input
                                                        id={`item-qty-${idx}`}
                                                        type="number"
                                                        step="0.01"
                                                        value={item.kuantitas}
                                                        onChange={(e) => ubahItem(idx, { kuantitas: e.target.value })}
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor={`item-satuan-${idx}`}>Satuan</Label>
                                                    <Input
                                                        id={`item-satuan-${idx}`}
                                                        value={item.satuan}
                                                        onChange={(e) => ubahItem(idx, { satuan: e.target.value })}
                                                        placeholder="Paket / Unit"
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <div className="border-border flex flex-wrap items-center justify-between gap-4 border-t pt-4">
                            <div>
                                <p className="text-muted-foreground text-xs">Total Nilai Penyaluran</p>
                                <p className="text-foreground text-xl font-semibold tabular-nums">{formatRupiah(total)}</p>
                            </div>
                            <Button type="submit" className="h-10 gap-2" disabled={form.processing}>
                                <Save className="h-4 w-4" aria-hidden="true" />
                                <span>{form.processing ? 'Menyimpan...' : 'Simpan Draft Penyaluran'}</span>
                            </Button>
                        </div>
                    </section>
                </form>
            </div>
        </AppLayout>
    );
}
