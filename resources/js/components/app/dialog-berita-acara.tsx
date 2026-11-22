import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import * as React from 'react';

export interface BeritaAcaraDasar {
    id: number;
    nomor: string;
    tanggal_tampil: string;
    status: string;
    catatan: string | null;
}

interface DialogProps {
    target: BeritaAcaraDasar | null;
    onClose: () => void;
    url: (id: number) => string;
}

export function DialogUbahBeritaAcara({ target, onClose, url }: DialogProps) {
    const [catatan, setCatatan] = React.useState('');
    const [galat, setGalat] = React.useState<string | null>(null);
    const [proses, setProses] = React.useState(false);

    React.useEffect(() => {
        setCatatan(target?.catatan ?? '');
        setGalat(null);
    }, [target]);

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        if (!target) return;
        router.put(
            url(target.id),
            { catatan },
            {
                preserveScroll: true,
                onStart: () => setProses(true),
                onFinish: () => setProses(false),
                onSuccess: onClose,
                onError: (errors) => setGalat(Object.values(errors)[0] ?? 'Perubahan gagal disimpan.'),
            },
        );
    };

    return (
        <Dialog open={Boolean(target)} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Ubah Draft {target?.nomor}</DialogTitle>
                    <DialogDescription>
                        Total dan jumlah transaksi dihitung ulang dari transaksi tanggal {target?.tanggal_tampil} saat disimpan.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={simpan} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="ba-ubah-catatan">Catatan</Label>
                        <Input id="ba-ubah-catatan" value={catatan} onChange={(e) => setCatatan(e.target.value)} maxLength={500} />
                        {galat && (
                            <p role="alert" className="text-destructive text-xs">
                                {galat}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Tutup
                        </Button>
                        <Button type="submit" disabled={proses}>
                            Simpan Perubahan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DialogBatalkanBeritaAcara({ target, onClose, url }: DialogProps) {
    const [alasan, setAlasan] = React.useState('');
    const [galat, setGalat] = React.useState<string | null>(null);
    const [proses, setProses] = React.useState(false);

    React.useEffect(() => {
        setAlasan('');
        setGalat(null);
    }, [target]);

    const batalkan = (e: React.FormEvent) => {
        e.preventDefault();
        if (!target) return;
        router.post(
            url(target.id),
            { alasan },
            {
                preserveScroll: true,
                onStart: () => setProses(true),
                onFinish: () => setProses(false),
                onSuccess: onClose,
                onError: (errors) => setGalat(Object.values(errors)[0] ?? 'Pembatalan gagal.'),
            },
        );
    };

    const final = target?.status === 'final';

    return (
        <Dialog open={Boolean(target)} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Batalkan Berita Acara {target?.nomor}?</DialogTitle>
                    <DialogDescription>
                        {final
                            ? `Berita acara final akan dibatalkan dan penguncian tanggal ${target?.tanggal_tampil} dibuka. Tindakan ini tercatat di audit log.`
                            : `Draft berita acara akan dibatalkan. Tanggal ${target?.tanggal_tampil} dapat dibuatkan berita acara baru.`}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={batalkan} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="ba-batal-alasan">Alasan Pembatalan (minimal 10 karakter)</Label>
                        <Input id="ba-batal-alasan" value={alasan} onChange={(e) => setAlasan(e.target.value)} maxLength={500} required />
                        {galat && (
                            <p role="alert" className="text-destructive text-xs">
                                {galat}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Tutup
                        </Button>
                        <Button type="submit" variant="destructive" disabled={proses || alasan.trim().length < 10}>
                            Batalkan Berita Acara
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
