import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { BarComparisonChart, type ChartDatum } from '@/components/ui/chart';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckSquare, HandHeart, Inbox, Users, Wallet } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pendistribusian', href: '/distribusi' }];

interface SaldoKelompok {
    dana_kelompok_id: number;
    kode: string;
    nama: string;
    total_masuk: number;
    total_keluar: number;
    total_komitmen: number;
    saldo_tersedia: number;
}

interface PermohonanRingkas {
    id: number;
    nomor: string;
    tanggal: string | null;
    mustahik: string | null;
    program: string | null;
    nominal_diajukan: number;
    status_kode: string;
    langkah_sekarang: number;
}

interface Props {
    ringkasan: {
        total_dicairkan_bulan: number;
        jumlah_mustahik_bulan: number;
        permohonan_aktif: number;
        mustahik_terverifikasi: number;
    };
    saldo_kelompok: SaldoKelompok[];
    grafik_asnaf: ChartDatum[];
    permohonan_terbaru: PermohonanRingkas[];
}

export default function DasborPendistribusian({ ringkasan, saldo_kelompok, grafik_asnaf, permohonan_terbaru }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasbor Pendistribusian & Pendayagunaan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Dasbor Pendistribusian & Pendayagunaan"
                    description="Pantau saldo kelompok dana, antrean disposisi 7 langkah, dan realisasi penyaluran bantuan."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <Link href={route('distribusi.disposisi-saya')}>
                                    <Inbox className="h-4 w-4" aria-hidden="true" />
                                    <span>Disposisi Saya</span>
                                </Link>
                            </Button>
                            <Button asChild className="h-9 gap-1.5">
                                <Link href={route('distribusi.penyaluran.create')}>
                                    <HandHeart className="h-4 w-4" aria-hidden="true" />
                                    <span>Input Penyaluran</span>
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Disalurkan Bulan Ini</span>
                            <HandHeart className="text-primary h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                            {formatRupiah(ringkasan.total_dicairkan_bulan, true)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Status dicairkan bulan berjalan</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Mustahik Terbantu</span>
                            <Users className="text-success h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                            {formatAngka(ringkasan.jumlah_mustahik_bulan)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Penerima manfaat unik bulan ini</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Permohonan Aktif</span>
                            <CheckSquare className="text-warning h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                            {formatAngka(ringkasan.permohonan_aktif)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Sedang dalam alur disposisi</p>
                    </div>

                    <div className="bg-card border-border rounded-2xl border p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground text-xs font-medium">Mustahik Terverifikasi</span>
                            <Wallet className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                        </div>
                        <p className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                            {formatAngka(ringkasan.mustahik_terverifikasi)}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">Basis data aktif siap dibantu</p>
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Saldo per Kelompok Dana</h2>
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            Dihitung dari penerimaan final dikurangi penyaluran disetujui dan dicairkan.
                        </p>
                        {saldo_kelompok.length === 0 ? (
                            <EmptyState title="Belum Ada Data Kelompok Dana" description="Kelompok dana akan muncul setelah data master tersedia." />
                        ) : (
                            <div className="mt-4 space-y-3">
                                {saldo_kelompok.map((s) => (
                                    <div key={s.dana_kelompok_id} className="border-border flex items-center justify-between rounded-xl border px-3.5 py-2.5">
                                        <div>
                                            <p className="text-foreground text-sm font-medium">{s.nama}</p>
                                            <p className="text-muted-foreground text-xs">
                                                Masuk {formatRupiah(s.total_masuk)} · Keluar {formatRupiah(s.total_keluar + s.total_komitmen)}
                                            </p>
                                        </div>
                                        <span className={`text-sm font-semibold tabular-nums ${s.saldo_tersedia < 0 ? 'text-destructive' : 'text-foreground'}`}>
                                            {formatRupiah(s.saldo_tersedia)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Penyaluran per Asnaf Bulan Ini</h2>
                        <p className="text-muted-foreground mt-0.5 text-xs">Distribusi dana Zakat per asnaf serta program Non-Zakat.</p>
                        {grafik_asnaf.length === 0 ? (
                            <EmptyState title="Belum Ada Penyaluran Bulan Ini" description="Grafik asnaf akan tampil setelah penyaluran dicairkan." />
                        ) : (
                            <div className="mt-4">
                                <BarComparisonChart title="Realisasi Penyaluran per Asnaf" data={grafik_asnaf} isCurrency height={260} />
                            </div>
                        )}
                    </section>
                </div>

                <section className="bg-card border-border rounded-2xl border p-5">
                    <div className="flex items-center justify-between gap-2">
                        <div>
                            <h2 className="text-foreground text-base font-semibold">Permohonan Terbaru</h2>
                            <p className="text-muted-foreground text-xs">Klik detail untuk memproses langkah disposisi sesuai peran.</p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('distribusi.permohonan.index')}>Lihat Semua</Link>
                        </Button>
                    </div>

                    {permohonan_terbaru.length === 0 ? (
                        <EmptyState title="Belum Ada Permohonan" description="Catat permohonan bantuan baru dari menu Permohonan Bantuan." />
                    ) : (
                        <div className="mt-4 overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-border text-muted-foreground border-b text-xs">
                                    <tr>
                                        <th className="py-2 pr-3 font-medium">Nomor</th>
                                        <th className="py-2 pr-3 font-medium">Mustahik</th>
                                        <th className="py-2 pr-3 font-medium">Program</th>
                                        <th className="py-2 pr-3 text-right font-medium">Diajukan</th>
                                        <th className="py-2 pr-3 font-medium">Tahap</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-border divide-y">
                                    {permohonan_terbaru.map((p) => (
                                        <tr key={p.id} className="hover:bg-muted/40">
                                            <td className="py-2.5 pr-3 font-medium">
                                                <Link href={route('distribusi.permohonan.show', p.id)} className="text-primary hover:underline">
                                                    {p.nomor}
                                                </Link>
                                            </td>
                                            <td className="py-2.5 pr-3">{p.mustahik ?? '-'}</td>
                                            <td className="py-2.5 pr-3">{p.program ?? '-'}</td>
                                            <td className="py-2.5 pr-3 text-right tabular-nums">{formatRupiah(p.nominal_diajukan)}</td>
                                            <td className="py-2.5 pr-3">
                                                <StatusBadge
                                                    status={p.status_kode}
                                                    variant={p.status_kode === 'diterima_mustahik' ? 'success' : p.status_kode === 'ditolak' ? 'ditolak' : 'aktif'}
                                                    label={p.status_kode.replaceAll('_', ' ')}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
