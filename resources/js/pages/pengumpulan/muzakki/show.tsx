import { PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface SetoranRow {
    id: number;
    nomor: string;
    tanggal: string;
    status: string;
    metode: string | null;
    upz: string | null;
    sumber_dana: string;
    total: number;
}

interface Props {
    muzakki: {
        id: number;
        kode: string;
        jenis: string;
        nama: string;
        nik_masked: string | null;
        npwp: string | null;
        telepon: string | null;
        email: string | null;
        alamat: string | null;
        kecamatan: string | null;
        desa: string | null;
        aktif: boolean;
    };
    setoran: SetoranRow[];
    total_setoran_final: number;
}

export default function MuzakkiShow({ muzakki, setoran, total_setoran_final }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Data Muzakki', href: '/pengumpulan/muzakki' },
        { title: muzakki.nama, href: `/pengumpulan/muzakki/${muzakki.id}` },
    ];

    const kolom: DataTableColumn<SetoranRow>[] = [
        {
            id: 'nomor',
            header: 'No. Kwitansi',
            cell: (r) => (
                <Link href={route('pengumpulan.penerimaan.show', r.id)} className="text-primary font-medium hover:underline">
                    {r.nomor}
                </Link>
            ),
        },
        { id: 'tanggal', header: 'Tanggal', cell: (r) => r.tanggal },
        { id: 'sumber', header: 'Sumber Dana', cell: (r) => r.sumber_dana },
        { id: 'metode', header: 'Metode', cell: (r) => r.metode ?? '-' },
        { id: 'upz', header: 'UPZ', cell: (r) => r.upz ?? 'Langsung' },
        { id: 'status', header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        { id: 'total', header: 'Nominal', align: 'right', cell: (r) => <span className="font-semibold">{formatRupiah(r.total)}</span> },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Muzakki: ${muzakki.nama}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={muzakki.nama}
                    description={`${muzakki.kode} · ${muzakki.jenis === 'lembaga' ? 'Lembaga' : 'Perorangan'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9">
                                <Link href={route('pengumpulan.muzakki.index')}>Kembali</Link>
                            </Button>
                            <Button asChild className="h-9">
                                <Link href={route('pengumpulan.penerimaan.create')}>Input Penerimaan Baru</Link>
                            </Button>
                        </>
                    }
                />

                <section className="bg-card border-border grid gap-4 rounded-xl border p-5 sm:grid-cols-3">
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Identitas & Kontak</p>
                        <p className="text-foreground mt-1 text-sm tabular-nums">
                            {muzakki.nik_masked ? `NIK: ${muzakki.nik_masked}` : muzakki.npwp ? `NPWP: ${muzakki.npwp}` : 'NIK belum diisi'}
                        </p>
                        <p className="text-muted-foreground text-xs">
                            Telp: {muzakki.telepon ?? '-'} · Email: {muzakki.email ?? '-'}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Alamat Wilayah</p>
                        <p className="text-foreground mt-1 text-sm">
                            {muzakki.desa ? `Desa ${muzakki.desa}, Kec. ${muzakki.kecamatan ?? '-'}` : 'Wilayah belum diisi'}
                        </p>
                        <p className="text-muted-foreground text-xs">{muzakki.alamat ?? '-'}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">Akumulasi Setoran Final</p>
                        <p className="text-foreground mt-1 text-xl font-semibold tabular-nums">{formatRupiah(total_setoran_final)}</p>
                    </div>
                </section>

                <DataTable columns={kolom} data={setoran} keyExtractor={(r) => r.id} emptyMessage="Muzakki ini belum memiliki riwayat setoran." />
            </div>
        </AppLayout>
    );
}
