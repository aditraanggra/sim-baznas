import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import * as React from 'react';
import { type JenisKegiatanItem, type KodeBidangRkat, type OpsiPosItem } from './types';

export function DialogJenisKegiatan({
    open,
    onOpenChange,
    bidangKode,
    itemEdit,
    opsiPos,
    opsiProgram,
    onBerhasil,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    bidangKode: KodeBidangRkat;
    itemEdit: JenisKegiatanItem | null;
    opsiPos: OpsiPosItem[];
    opsiProgram: OpsiPosItem[];
    onBerhasil?: () => void;
}) {
    const defaultSatuan = bidangKode === 'pengumpulan' ? 'muzakki' : bidangKode === 'distribusi' ? 'mustahik' : 'unit';

    const form = useForm({
        id: itemEdit?.id ? String(itemEdit.id) : '',
        kode: itemEdit?.kode ?? '',
        nama: itemEdit?.nama ?? '',
        pos_anggaran_id: itemEdit?.pos_anggaran_id ? String(itemEdit.pos_anggaran_id) : String(opsiPos[0]?.id ?? ''),
        pos_program_id: itemEdit?.pos_program_id ? String(itemEdit.pos_program_id) : String(opsiProgram[0]?.id ?? ''),
        satuan_default: itemEdit?.satuan_default ?? defaultSatuan,
        keterangan: itemEdit?.keterangan ?? '',
    });

    React.useEffect(() => {
        if (!open) return;
        form.clearErrors();
        form.setData({
            id: itemEdit?.id ? String(itemEdit.id) : '',
            kode: itemEdit?.kode ?? '',
            nama: itemEdit?.nama ?? '',
            pos_anggaran_id: itemEdit?.pos_anggaran_id ? String(itemEdit.pos_anggaran_id) : String(opsiPos[0]?.id ?? ''),
            pos_program_id: itemEdit?.pos_program_id ? String(itemEdit.pos_program_id) : String(opsiProgram[0]?.id ?? ''),
            satuan_default: itemEdit?.satuan_default ?? defaultSatuan,
            keterangan: itemEdit?.keterangan ?? '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, itemEdit]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route(`${bidangKode}.rkat.jenis-kegiatan`), {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
                onBerhasil?.();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{itemEdit ? 'Ubah Jenis Kegiatan' : 'Tambah Master Jenis Kegiatan'}</DialogTitle>
                    <DialogDescription>
                        Jenis kegiatan terhubung langsung ke akun RKAT di bagian PKP dan dapat digunakan ulang setiap tahun.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5 sm:col-span-1">
                            <Label htmlFor="jk-kode">Kode (opsional)</Label>
                            <Input
                                id="jk-kode"
                                className="h-9"
                                placeholder="Mis. KEG-01"
                                value={form.data.kode}
                                onChange={(e) => form.setData('kode', e.target.value)}
                            />
                            {form.errors.kode && <p className="text-destructive text-xs">{form.errors.kode}</p>}
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="jk-nama">Nama Jenis Kegiatan</Label>
                            <Input
                                id="jk-nama"
                                className="h-9"
                                required
                                placeholder="Mis. Pengumpulan Zakat Fitrah ASN"
                                value={form.data.nama}
                                onChange={(e) => form.setData('nama', e.target.value)}
                            />
                            {form.errors.nama && <p className="text-destructive text-xs">{form.errors.nama}</p>}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label>Akun / Pos RKAT PKP</Label>
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
                            <Label>Program Bidang (L4)</Label>
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

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="jk-satuan">Satuan Default</Label>
                            <Input
                                id="jk-satuan"
                                className="h-9"
                                required
                                placeholder="muzakki / mustahik / unit"
                                value={form.data.satuan_default}
                                onChange={(e) => form.setData('satuan_default', e.target.value)}
                            />
                            {form.errors.satuan_default && <p className="text-destructive text-xs">{form.errors.satuan_default}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="jk-ket">Keterangan (opsional)</Label>
                            <Input
                                id="jk-ket"
                                className="h-9"
                                value={form.data.keterangan}
                                onChange={(e) => form.setData('keterangan', e.target.value)}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" className="h-9" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" className="h-9" disabled={form.processing}>
                            {form.processing ? 'Menyimpan...' : 'Simpan Jenis Kegiatan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
