import { EmptyState, PageHeader, PemilihPeriode, type PeriodeFilter } from '@/components/app';
import { Button } from '@/components/ui/button';
import { BarComparisonChart, LineTrendChart, type ChartDatum } from '@/components/ui/chart';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FileDown, FileSpreadsheet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Laporan Penyaluran', href: '/distribusi/laporan' },
];

interface BarisLaporan {
    id: number;
    penyaluran_id: number;
    tanggal: string;
    nomor_penyaluran: string;
    sub_bidang: string;
    mustahik: string;
    program: string;
    jenis_dana: string;
    sumber_dana: string;
    asnaf: string;
    bentuk_bantuan: string;
    uraian_barang: string | null;
    metode: string;
    jumlah: number;
}

interface Props {
    laporan: {
        periode: PeriodeFilter & { label: string };
        total_nominal: number;
        jumlah_transaksi: number;
        jumlah_mustahik: number;
        per_sub_bidang: { kode: string; nama: string; total: number }[];
        per_program: { id: number; kode: string; nama: string; total: number }[];
        per_asnaf: { nama: string; total: number }[];
        per_kecamatan: { nama: string; mustahik: number; total: number }[];
        tren_tanggal: ChartDatum[];
        baris: BarisLaporan[];
    };
    filter: {
        sub_bidang?: string;
        program_id?: string;
        asnaf_id?: string;
        kecamatan_id?: string;
    };
    program_list: { id: number; nama: string; sub_bidang: string | null }[];
    asnaf_list: { id: number; nama: string }[];
    kecamatan_list: { id: number; nama: string }[];
    bisa_ekspor: boolean;
}

export default function LaporanPendistribusian({ laporan, filter, program_list, asnaf_list, kecamatan_list, bisa_ekspor }: Props) {
    const queryString = new URLSearchParams({
        jenis: laporan.periode.jenis,
        tahun: String(laporan.periode.tahun),
        bulan: String(laporan.periode.bulan ?? ''),
        triwulan: String(laporan.periode.triwulan ?? ''),
        semester: String(laporan.periode.semester ?? ''),
        tanggal: String(laporan.periode.tanggal ?? ''),
        sub_bidang: filter.sub_bidang ?? '',
        program_id: filter.program_id ?? '',
        asnaf_id: filter.asnaf_id ?? '',
        kecamatan_id: filter.kecamatan_id ?? '',
    }).toString();

    const perbarui = (periodeBaru: PeriodeFilter, filterBaru = filter) => {
        router.get(
            route('distribusi.laporan.index'),
            {
                ...periodeBaru,
                ...filterBaru,
            },
            { preserveState: true },
        );
    };

    const kolom: DataTableColumn<BarisLaporan>[] = [
        { id: 'tanggal', header: 'Tanggal', cell: (r) => r.tanggal },
        { id: 'nomor', header: 'Nomor', cell: (r) => r.nomor_penyaluran },
        { id: 'mustahik', header: 'Mustahik', cell: (r) => r.mustahik },
        {
            id: 'program',
            header: 'Program',
            cell: (r) => (
                <div>
                    <p>{r.program}</p>
                    <p className="text-muted-foreground text-xs">{r.sub_bidang === 'pendayagunaan' ? 'Pendayagunaan' : 'Pendistribusian'}</p>
                </div>
            ),
        },
        { id: 'dana', header: 'Sumber & Asnaf', cell: (r) => `${r.sumber_dana} · ${r.asnaf}` },
        {
            id: 'jumlah',
            header: 'Nilai Bantuan',
            align: 'right',
            cell: (r) => <span className="font-semibold tabular-nums">{formatRupiah(r.jumlah)}</span>,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Pendistribusian & Pendayagunaan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Laporan Pendistribusian & Pendayagunaan"
                    description={`Rekap realisasi bantuan dicairkan periode ${laporan.periode.label}.`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_ekspor && (
                            <>
                                <Button asChild variant="outline" className="h-9 gap-1.5">
                                    <a href={`${route('distribusi.laporan.pdf')}?${queryString}`} target="_blank" rel="noreferrer">
                                        <FileDown className="h-4 w-4" aria-hidden="true" />
                                        <span>Unduh PDF</span>
                                    </a>
                                </Button>
                                <Button asChild className="h-9 gap-1.5">
                                    <a href={`${route('distribusi.laporan.excel')}?${queryString}`}>
                                        <FileSpreadsheet className="h-4 w-4" aria-hidden="true" />
                                        <span>Ekspor Excel</span>
                                    </a>
                                </Button>
                            </>
                        )
                    }
                />

                <div className="bg-card border-border space-y-4 rounded-2xl border p-4">
                    <PemilihPeriode value={laporan.periode} onChange={(p) => perbarui(p)} />

                    <div className="grid gap-3 sm:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="lap-sub">Sub Bidang</Label>
                            <Select
                                value={filter.sub_bidang || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, sub_bidang: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-sub">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Sub Bidang</SelectItem>
                                    <SelectItem value="pendistribusian">Pendistribusian</SelectItem>
                                    <SelectItem value="pendayagunaan">Pendayagunaan</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="lap-program">Program</Label>
                            <Select
                                value={filter.program_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, program_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-program">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Program</SelectItem>
                                    {program_list.map((pr) => (
                                        <SelectItem key={pr.id} value={String(pr.id)}>
                                            {pr.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="lap-asnaf">Asnaf</Label>
                            <Select
                                value={filter.asnaf_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, asnaf_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-asnaf">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Asnaf</SelectItem>
                                    {asnaf_list.map((a) => (
                                        <SelectItem key={a.id} value={String(a.id)}>
                                            {a.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="lap-kecamatan">Kecamatan</Label>
                            <Select
                                value={filter.kecamatan_id || 'semua'}
                                onValueChange={(v) => perbarui(laporan.periode, { ...filter, kecamatan_id: v === 'semua' ? '' : v })}
                            >
                                <SelectTrigger id="lap-kecamatan">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua Kecamatan</SelectItem>
                                    {kecamatan_list.map((k) => (
                                        <SelectItem key={k.id} value={String(k.id)}>
                                            {k.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Total Penyaluran</p>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">{formatRupiah(laporan.total_nominal)}</p>
                    </div>
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Jumlah Transaksi</p>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">{formatAngka(laporan.jumlah_transaksi)}</p>
                    </div>
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <p className="text-muted-foreground text-xs font-medium">Mustahik Terbantu</p>
                        <p className="text-foreground mt-1.5 text-2xl font-semibold tabular-nums">{formatAngka(laporan.jumlah_mustahik)}</p>
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground mb-3 text-base font-semibold">Tren Penyaluran</h2>
                        {laporan.tren_tanggal.length === 0 ? (
                            <EmptyState title="Belum Ada Tren" description="Grafik tren muncul saat ada penyaluran dicairkan pada periode ini." />
                        ) : (
                            <LineTrendChart title="Tren Penyaluran" data={laporan.tren_tanggal} isCurrency height={240} />
                        )}
                    </section>

                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground mb-3 text-base font-semibold">Rekap per Asnaf</h2>
                        {laporan.per_asnaf.length === 0 ? (
                            <EmptyState title="Belum Ada Data Asnaf" description="Tidak ada realisasi pada filter periode terpilih." />
                        ) : (
                            <BarComparisonChart
                                title="Rekap per Asnaf"
                                data={laporan.per_asnaf.map((a) => ({ label: a.nama, nilai: a.total }))}
                                isCurrency
                                height={240}
                            />
                        )}
                    </section>
                </div>

                {laporan.baris.length === 0 ? (
                    <EmptyState title="Tidak Ada Rincian Penyaluran" description="Ubah periode atau filter untuk melihat rincian penyaluran." />
                ) : (
                    <DataTable data={laporan.baris} columns={kolom} keyExtractor={(r) => r.id} />
                )}
            </div>
        </AppLayout>
    );
}
