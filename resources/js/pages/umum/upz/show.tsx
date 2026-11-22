import { PageHeader, StatusBadge, UnggahBerkas } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FileDown, Plus } from 'lucide-react';
import * as React from 'react';

interface SkRow {
    id: number;
    nomor: string;
    tanggal_sk: string;
    berlaku_mulai: string;
    berlaku_sampai: string;
    sisa_hari: number;
    status_berlaku: string;
    keterangan: string | null;
    berkas_nama: string | null;
    berkas_url: string | null;
}

interface SetoranRow {
    id: number;
    nomor: string;
    tanggal: string;
    muzakki: string;
    metode: string;
    status: string;
    total: number;
}

interface Props {
    upz: {
        id: number;
        kode: string;
        nama: string;
        nomor_sk?: string | null;
        jenis_upz: string | null;
        desa: string | null;
        kecamatan: string | null;
        ketua: string;
        sekretaris: string | null;
        bendahara: string | null;
        alamat: string;
        pic_nama: string;
        pic_telepon: string;
        aktif: boolean;
    };
    daftar_sk: SkRow[];
    setoran: SetoranRow[];
    bisa_kelola: boolean;
}

export default function UpzShow({ upz, daftar_sk, setoran, bisa_kelola }: Props) {
    const [drawerSk, setDrawerSk] = React.useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'UPZ', href: '/umum/upz' },
        { title: upz.nama, href: `/umum/upz/${upz.id}` },
    ];

    const formSk = useForm<{
        nomor: string;
        tanggal_sk: string;
        berlaku_mulai: string;
        berlaku_sampai: string;
        keterangan: string;
        berkas: File | null;
    }>({
        nomor: '',
        tanggal_sk: '',
        berlaku_mulai: '',
        berlaku_sampai: '',
        keterangan: '',
        berkas: null,
    });

    const kirimSk = (e: React.FormEvent) => {
        e.preventDefault();
        formSk.post(route('umum.upz.sk.store', upz.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setDrawerSk(false);
                formSk.reset();
            },
        });
    };

    const kolomSk: DataTableColumn<SkRow>[] = [
        { id: 'nomor', header: 'Nomor SK', cell: (r) => <span className="font-medium">{r.nomor}</span> },
        { id: 'tanggal', header: 'Tanggal SK', cell: (r) => r.tanggal_sk },
        { id: 'masa', header: 'Masa Berlaku', cell: (r) => `${r.berlaku_mulai} s.d. ${r.berlaku_sampai}` },
        {
            id: 'status',
            header: 'Status',
            cell: (r) => (
                <StatusBadge
                    status={r.status_berlaku}
                    variant={r.status_berlaku === 'aktif' ? 'aktif' : r.status_berlaku === 'segera_habis' ? 'warning' : 'habis'}
                    label={r.status_berlaku === 'aktif' ? 'Aktif' : r.status_berlaku === 'segera_habis' ? `Segera Habis (${r.sisa_hari} hari)` : 'Habis'}
                />
            ),
        },
        {
            id: 'berkas',
            header: 'Berkas SK',
            align: 'right',
            cell: (r) =>
                r.berkas_url ? (
                    <a
                        href={r.berkas_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary inline-flex items-center gap-1 text-xs font-medium hover:underline"
                    >
                        <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{r.berkas_nama ?? 'Unduh SK'}</span>
                    </a>
                ) : (
                    <span className="text-muted-foreground text-xs">Tidak ada berkas</span>
                ),
        },
    ];

    const kolomSetoran: DataTableColumn<SetoranRow>[] = [
        { id: 'nomor', header: 'No. Kwitansi', cell: (r) => <span className="font-medium">{r.nomor}</span> },
        { id: 'tanggal', header: 'Tanggal', cell: (r) => r.tanggal },
        { id: 'muzakki', header: 'Muzakki', cell: (r) => r.muzakki },
        { id: 'metode', header: 'Metode', cell: (r) => r.metode },
        { id: 'status', header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        { id: 'total', header: 'Nominal', align: 'right', cell: (r) => <span className="font-medium">{formatRupiah(r.total)}</span> },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`UPZ ${upz.nama}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={upz.nama}
                    description={`${upz.kode} · ${upz.jenis_upz ?? 'UPZ'} · Desa ${upz.desa ?? '-'}, Kec. ${upz.kecamatan ?? '-'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('umum.upz.index')}>Kembali ke Daftar UPZ</Link>
                            </Button>
                            {bisa_kelola && (
                                <Button type="button" onClick={() => setDrawerSk(true)} className="h-9 gap-1.5">
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    <span>Catat SK Baru</span>
                                </Button>
                            )}
                        </>
                    }
                />

                <section className="bg-card border-border grid gap-4 rounded-xl border p-5 sm:grid-cols-3">
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Pengurus UPZ</p>
                        <p className="text-foreground mt-1 text-sm font-medium">Ketua: {upz.ketua}</p>
                        <p className="text-muted-foreground text-xs">
                            Sekretaris: {upz.sekretaris || '-'} · Bendahara: {upz.bendahara || '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Penanggung Jawab (PIC)</p>
                        <p className="text-foreground mt-1 text-sm font-medium">{upz.pic_nama}</p>
                        <p className="text-muted-foreground text-xs">{upz.pic_telepon}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Alamat & Status</p>
                        <p className="text-foreground mt-1 text-xs">{upz.alamat}</p>
                        <p className="text-muted-foreground mt-1 text-xs">Nomor SK: {upz.nomor_sk || '-'}</p>
                        <div className="mt-2">
                            <StatusBadge status={upz.aktif ? 'aktif' : 'draft'} label={upz.aktif ? 'UPZ Aktif' : 'Nonaktif'} />
                        </div>
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="text-foreground text-base font-semibold">Riwayat SK Pengangkatan UPZ</h2>
                    <DataTable columns={kolomSk} data={daftar_sk} keyExtractor={(s) => s.id} emptyMessage="Belum ada SK tercatat untuk UPZ ini." />
                </section>

                <section className="space-y-3">
                    <h2 className="text-foreground text-base font-semibold">Riwayat Setoran Melalui UPZ</h2>
                    <DataTable columns={kolomSetoran} data={setoran} keyExtractor={(s) => s.id} emptyMessage="Belum ada setoran penerimaan melalui UPZ ini." />
                </section>
            </div>

            <Sheet open={drawerSk} onOpenChange={setDrawerSk}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[500px]">
                    <SheetHeader>
                        <SheetTitle>Catat SK UPZ Baru</SheetTitle>
                        <SheetDescription>Unggah salinan SK resmi dan tetapkan tanggal masa berlaku untuk pengingat otomatis.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimSk} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="sk-nomor">Nomor SK (wajib)</Label>
                            <Input id="sk-nomor" required value={formSk.data.nomor} onChange={(e) => formSk.setData('nomor', e.target.value)} className="h-9" />
                            {formSk.errors.nomor && <p className="text-destructive text-xs">{formSk.errors.nomor}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="sk-tanggal">Tanggal Penetapan SK (wajib)</Label>
                            <Input id="sk-tanggal" type="date" required value={formSk.data.tanggal_sk} onChange={(e) => formSk.setData('tanggal_sk', e.target.value)} className="h-9" />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="sk-mulai">Berlaku Mulai (wajib)</Label>
                                <Input id="sk-mulai" type="date" required value={formSk.data.berlaku_mulai} onChange={(e) => formSk.setData('berlaku_mulai', e.target.value)} className="h-9" />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="sk-sampai">Berlaku Sampai (wajib)</Label>
                                <Input id="sk-sampai" type="date" required value={formSk.data.berlaku_sampai} onChange={(e) => formSk.setData('berlaku_sampai', e.target.value)} className="h-9" />
                            </div>
                        </div>

                        <UnggahBerkas
                            label="Berkas PDF / Gambar SK"
                            accept=".pdf,.jpg,.jpeg,.png"
                            value={formSk.data.berkas}
                            onChange={(file) => formSk.setData('berkas', file)}
                            error={formSk.errors.berkas}
                        />

                        <div className="space-y-1.5">
                            <Label htmlFor="sk-ket">Keterangan</Label>
                            <Input id="sk-ket" value={formSk.data.keterangan} onChange={(e) => formSk.setData('keterangan', e.target.value)} className="h-9" />
                        </div>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" onClick={() => setDrawerSk(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={formSk.processing} className="h-9">
                                {formSk.processing ? 'Menyimpan...' : 'Simpan SK UPZ'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
