import { EmptyState, InputRupiah } from '@/components/app';
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatAngka, formatRupiah } from '@/lib/format';
import { router, useForm } from '@inertiajs/react';
import { Archive, Pencil, Plus } from 'lucide-react';
import * as React from 'react';
import { type JenisKegiatanItem, type KegiatanRencanaItem, type KodeBidangRkat, type OpsiPosItem } from './types';

export function TabelKegiatanBidang({
    bidangKode,
    versiId,
    kegiatan,
    jenisKegiatan,
    opsiPos,
    opsiProgram,
    bolehEdit,
    onBukaTambahJenis,
}: {
    bidangKode: KodeBidangRkat;
    versiId: number;
    kegiatan: KegiatanRencanaItem[];
    jenisKegiatan: JenisKegiatanItem[];
    opsiPos: OpsiPosItem[];
    opsiProgram: OpsiPosItem[];
    bolehEdit: boolean;
    onBukaTambahJenis: () => void;
}) {
    const jenisAktif = React.useMemo(() => jenisKegiatan.filter((j) => j.aktif), [jenisKegiatan]);
    const [dialogTerbuka, setDialogTerbuka] = React.useState(false);
    const [itemArsip, setItemArsip] = React.useState<KegiatanRencanaItem | null>(null);

    const form = useForm({
        id: '',
        rkat_version_id: versiId,
        rkat_jenis_kegiatan_id: '',
        nama_kegiatan: '',
        pos_anggaran_id: '',
        pos_program_id: '',
        jumlah: 1,
        satuan: bidangKode === 'pengumpulan' ? 'muzakki' : bidangKode === 'distribusi' ? 'mustahik' : 'unit',
        frekuensi: 1,
        besaran: 0,
        keterangan: '',
    });

    const bukaTambah = () => {
        const jenisAwal = jenisAktif[0];
        form.clearErrors();
        form.setData({
            id: '',
            rkat_version_id: versiId,
            rkat_jenis_kegiatan_id: jenisAwal ? String(jenisAwal.id) : '',
            nama_kegiatan: jenisAwal?.nama ?? '',
            pos_anggaran_id: jenisAwal ? String(jenisAwal.pos_anggaran_id) : String(opsiPos[0]?.id ?? ''),
            pos_program_id: jenisAwal?.pos_program_id ? String(jenisAwal.pos_program_id) : String(opsiProgram[0]?.id ?? ''),
            jumlah: 1,
            satuan: jenisAwal?.satuan_default ?? (bidangKode === 'pengumpulan' ? 'muzakki' : bidangKode === 'distribusi' ? 'mustahik' : 'unit'),
            frekuensi: 1,
            besaran: 0,
            keterangan: '',
        });
        setDialogTerbuka(true);
    };

    const bukaUbah = (item: KegiatanRencanaItem) => {
        form.clearErrors();
        form.setData({
            id: String(item.id),
            rkat_version_id: versiId,
            rkat_jenis_kegiatan_id: String(item.rkat_jenis_kegiatan_id),
            nama_kegiatan: item.nama_kegiatan,
            pos_anggaran_id: String(item.pos_anggaran_id),
            pos_program_id: item.pos_program_id ? String(item.pos_program_id) : '',
            jumlah: item.jumlah,
            satuan: item.satuan,
            frekuensi: item.frekuensi,
            besaran: item.besaran,
            keterangan: item.keterangan ?? '',
        });
        setDialogTerbuka(true);
    };

    const pilihJenis = (jenisId: string) => {
        const j = jenisAktif.find((x) => String(x.id) === jenisId);
        if (!j) return;
        form.setData((prev) => ({
            ...prev,
            rkat_jenis_kegiatan_id: jenisId,
            nama_kegiatan: j.nama,
            pos_anggaran_id: String(j.pos_anggaran_id),
            pos_program_id: j.pos_program_id ? String(j.pos_program_id) : prev.pos_program_id,
            satuan: j.satuan_default,
        }));
    };

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route(`${bidangKode}.rkat.kegiatan`), {
            preserveScroll: true,
            onSuccess: () => setDialogTerbuka(false),
        });
    };

    const konfirmasiArsip = () => {
        if (!itemArsip) return;
        router.post(
            route(`${bidangKode}.rkat.kegiatan`),
            {
                aksi: 'arsip',
                id: itemArsip.id,
                rkat_version_id: versiId,
            },
            {
                preserveScroll: true,
                onFinish: () => setItemArsip(null),
            },
        );
    };

    const pratinjauTotal = Math.max(0, Number(form.data.jumlah) || 0) * Math.max(0, Number(form.data.frekuensi) || 0) * Math.max(0, Number(form.data.besaran) || 0);
    const totalSemua = kegiatan.reduce((acc, item) => acc + item.total, 0);
    const labelKolomJumlah = bidangKode === 'pengumpulan' ? 'Muzakki / Vol' : bidangKode === 'distribusi' ? 'Mustahik' : 'Volume';

    return (
        <section className="bg-card border-border rounded-xl border shadow-xs">
            <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                <div>
                    <h2 className="text-foreground text-base font-semibold">Daftar Rincian Anggaran Kegiatan</h2>
                    <p className="text-muted-foreground text-xs">
                        Setiap baris terhubung ke akun RKAT PKP dan otomatis memperbarui total konsolidasi.
                    </p>
                </div>
                {bolehEdit && (
                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="button" variant="outline" className="h-9" onClick={onBukaTambahJenis}>
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            Jenis Kegiatan Baru
                        </Button>
                        <Button type="button" className="h-9" disabled={jenisAktif.length === 0} onClick={bukaTambah}>
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            Tambah Kegiatan
                        </Button>
                    </div>
                )}
            </div>

            {kegiatan.length === 0 ? (
                <div className="p-6">
                    <EmptyState
                        title="Belum ada rincian kegiatan"
                        description={
                            jenisAktif.length === 0
                                ? 'Buat minimal satu Master Jenis Kegiatan terlebih dahulu, lalu tambahkan rincian kegiatan untuk versi RKAT ini.'
                                : 'Tambahkan rincian kegiatan sesuai target atau pagu yang tersedia.'
                        }
                        actionLabel={bolehEdit ? (jenisAktif.length === 0 ? 'Tambah Jenis Kegiatan' : 'Tambah Kegiatan') : undefined}
                        onAction={bolehEdit ? (jenisAktif.length === 0 ? onBukaTambahJenis : bukaTambah) : undefined}
                    />
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-surface-subtle text-muted-foreground text-left text-[11px] font-semibold tracking-wider uppercase">
                            <tr>
                                <th className="px-4 py-3">Kegiatan</th>
                                <th className="px-4 py-3">Akun RKAT</th>
                                {bidangKode === 'distribusi' && <th className="px-4 py-3">Program L4</th>}
                                <th className="px-4 py-3 text-right">{labelKolomJumlah}</th>
                                <th className="px-4 py-3 text-right">Frek / Thn</th>
                                <th className="px-4 py-3 text-right">Besaran</th>
                                <th className="px-4 py-3 text-right">Total</th>
                                {bolehEdit && <th className="px-4 py-3 text-right">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {kegiatan.map((item) => (
                                <tr key={item.id} className="hover:bg-surface-subtle/60">
                                    <td className="px-4 py-3">
                                        <div className="text-foreground font-medium">{item.nama_kegiatan}</div>
                                        {item.keterangan && <div className="text-muted-foreground text-xs">{item.keterangan}</div>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="bg-surface-subtle text-foreground inline-block rounded px-1.5 py-0.5 font-mono text-xs">
                                            {item.pos_kode}
                                        </span>
                                        <div className="text-muted-foreground mt-0.5 text-xs">{item.pos_nama}</div>
                                    </td>
                                    {bidangKode === 'distribusi' && (
                                        <td className="px-4 py-3 text-xs">
                                            <span className="font-mono">{item.program_kode}</span> · {item.program_nama}
                                        </td>
                                    )}
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {formatAngka(item.jumlah)} {item.satuan}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">{item.frekuensi}x</td>
                                    <td className="px-4 py-3 text-right tabular-nums">{formatRupiah(item.besaran)}</td>
                                    <td className="px-4 py-3 text-right font-semibold tabular-nums">{formatRupiah(item.total)}</td>
                                    {bolehEdit && (
                                        <td className="px-4 py-3 text-right">
                                            <div className="inline-flex items-center gap-1">
                                                <Button type="button" variant="ghost" size="sm" className="h-8 px-2" onClick={() => bukaUbah(item)}>
                                                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                                    <span className="sr-only">Ubah</span>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive h-8 px-2"
                                                    onClick={() => setItemArsip(item)}
                                                >
                                                    <Archive className="h-3.5 w-3.5" aria-hidden="true" />
                                                    <span className="sr-only">Arsipkan</span>
                                                </Button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="bg-surface-subtle border-border border-t">
                            <tr>
                                <td colSpan={bidangKode === 'distribusi' ? 6 : 5} className="px-4 py-3 text-right font-semibold">
                                    Total Keseluruhan
                                </td>
                                <td className="px-4 py-3 text-right font-semibold tabular-nums">{formatRupiah(totalSemua)}</td>
                                {bolehEdit && <td />}
                            </tr>
                        </tfoot>
                    </table>
                </div>
            )}

            <Dialog open={dialogTerbuka} onOpenChange={setDialogTerbuka}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{form.data.id ? 'Ubah Rincian Kegiatan' : 'Tambah Rincian Kegiatan'}</DialogTitle>
                        <DialogDescription>
                            Nilai total dihitung otomatis oleh sistem dari Jumlah × Frekuensi × Besaran.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={simpan} className="space-y-4">
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label>Jenis Kegiatan</Label>
                                <button
                                    type="button"
                                    className="text-primary text-xs font-medium hover:underline"
                                    onClick={() => {
                                        setDialogTerbuka(false);
                                        onBukaTambahJenis();
                                    }}
                                >
                                    + Tambah Jenis Kegiatan Baru
                                </button>
                            </div>
                            <Select value={form.data.rkat_jenis_kegiatan_id} onValueChange={pilihJenis}>
                                <SelectTrigger className="h-9">
                                    <SelectValue placeholder="Pilih jenis kegiatan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {jenisAktif.map((j) => (
                                        <SelectItem key={j.id} value={String(j.id)}>
                                            {j.nama} ({j.pos_kode})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.rkat_jenis_kegiatan_id && (
                                <p className="text-destructive text-xs">{form.errors.rkat_jenis_kegiatan_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="kr-nama">Uraian Kegiatan</Label>
                            <Input
                                id="kr-nama"
                                className="h-9"
                                required
                                value={form.data.nama_kegiatan}
                                onChange={(e) => form.setData('nama_kegiatan', e.target.value)}
                            />
                            {form.errors.nama_kegiatan && <p className="text-destructive text-xs">{form.errors.nama_kegiatan}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label>Akun / Pos RKAT</Label>
                                <Select value={form.data.pos_anggaran_id} onValueChange={(v) => form.setData('pos_anggaran_id', v)}>
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Pilih pos RKAT" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {opsiPos.map((p) => (
                                            <SelectItem key={p.id} value={String(p.id)}>
                                                {p.kode} — {p.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.pos_anggaran_id && <p className="text-destructive text-xs">{form.errors.pos_anggaran_id}</p>}
                            </div>

                            {bidangKode === 'distribusi' && (
                                <div className="space-y-1.5">
                                    <Label>Program L4</Label>
                                    <Select value={form.data.pos_program_id} onValueChange={(v) => form.setData('pos_program_id', v)}>
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder="Pilih program L4" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opsiProgram.map((p) => (
                                                <SelectItem key={p.id} value={String(p.id)}>
                                                    {p.kode} — {p.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.pos_program_id && <p className="text-destructive text-xs">{form.errors.pos_program_id}</p>}
                                </div>
                            )}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="kr-jumlah">{labelKolomJumlah}</Label>
                                <Input
                                    id="kr-jumlah"
                                    type="number"
                                    min={1}
                                    className="h-9 text-right tabular-nums"
                                    value={form.data.jumlah}
                                    onChange={(e) => form.setData('jumlah', Number.parseInt(e.target.value || '1', 10))}
                                />
                                {form.errors.jumlah && <p className="text-destructive text-xs">{form.errors.jumlah}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="kr-satuan">Satuan</Label>
                                <Input
                                    id="kr-satuan"
                                    className="h-9"
                                    required
                                    value={form.data.satuan}
                                    onChange={(e) => form.setData('satuan', e.target.value)}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="kr-frek">Frekuensi / Tahun</Label>
                                <Input
                                    id="kr-frek"
                                    type="number"
                                    min={1}
                                    className="h-9 text-right tabular-nums"
                                    value={form.data.frekuensi}
                                    onChange={(e) => form.setData('frekuensi', Number.parseInt(e.target.value || '1', 10))}
                                />
                                {form.errors.frekuensi && <p className="text-destructive text-xs">{form.errors.frekuensi}</p>}
                            </div>
                        </div>

                        <InputRupiah
                            id="kr-besaran"
                            label="Besaran Satuan (Rp)"
                            required
                            value={form.data.besaran}
                            onChange={(v) => form.setData('besaran', v)}
                            error={form.errors.besaran}
                            tampilkanTerbilang
                        />

                        <div className="bg-surface-subtle border-border flex items-center justify-between rounded-lg border px-4 py-2.5 text-sm">
                            <span className="text-muted-foreground font-medium">Pratinjau Total Anggaran</span>
                            <span className="text-foreground text-base font-semibold tabular-nums">{formatRupiah(pratinjauTotal)}</span>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="kr-ket">Keterangan (opsional)</Label>
                            <Input
                                id="kr-ket"
                                className="h-9"
                                value={form.data.keterangan}
                                onChange={(e) => form.setData('keterangan', e.target.value)}
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" className="h-9" onClick={() => setDialogTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" className="h-9" disabled={form.processing}>
                                {form.processing ? 'Menyimpan...' : 'Simpan Rincian'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={Boolean(itemArsip)} onOpenChange={(o) => !o && setItemArsip(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Arsipkan Rincian Kegiatan?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Kegiatan "{itemArsip?.nama_kegiatan}" senilai {formatRupiah(itemArsip?.total)} akan diarsipkan dan dikeluarkan dari
                            perhitungan konsolidasi RKAT.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction destructive onClick={konfirmasiArsip}>
                            Ya, Arsipkan
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </section>
    );
}
