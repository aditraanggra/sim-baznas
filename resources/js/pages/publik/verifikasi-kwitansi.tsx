import { EmptyState, StatusBadge } from '@/components/app';
import { PublicLayout } from '@/layouts/public-layout';
import { formatRupiah } from '@/lib/format';
import { CheckCircle2, XCircle } from 'lucide-react';

interface DataKwitansi {
    nomor: string;
    tanggal_terbit: string;
    status: 'final' | 'batal' | string;
    metode: string;
    total_rupiah: number;
    rincian: {
        jenis_dana: string;
        sumber_dana: string;
        jumlah: number;
    }[];
}

export default function VerifikasiKwitansi({ ditemukan, data }: { ditemukan: boolean; data: DataKwitansi | null }) {
    return (
        <PublicLayout title="Verifikasi Kwitansi ZIS" noindex={true}>
            {!ditemukan || !data ? (
                <EmptyState
                    title="Kwitansi Tidak Terdaftar"
                    description="Kode verifikasi kwitansi tidak ditemukan di sistem BAZNAS Kabupaten Cianjur."
                />
            ) : (
                <div className="space-y-6">
                    <div className="bg-card border-border rounded-xl border p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
                            <div className="flex items-start gap-3">
                                {data.status === 'final' ? (
                                    <CheckCircle2 className="text-success mt-0.5 h-6 w-6 shrink-0" aria-hidden="true" />
                                ) : (
                                    <XCircle className="text-destructive mt-0.5 h-6 w-6 shrink-0" aria-hidden="true" />
                                )}
                                <div>
                                    <h1 className="font-display text-foreground text-xl font-semibold">
                                        {data.status === 'final' ? 'Kwitansi Resmi Terverifikasi' : 'Kwitansi Dibatalkan'}
                                    </h1>
                                    <p className="text-muted-foreground text-sm">Nomor: {data.nomor}</p>
                                </div>
                            </div>

                            <StatusBadge
                                status={data.status}
                                variant={data.status === 'final' ? 'final' : 'dibatalkan'}
                                label={data.status === 'final' ? 'Sah / Final' : 'Dibatalkan'}
                            />
                        </div>

                        <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                            <div>
                                <dt className="text-muted-foreground text-xs">Tanggal Terbit</dt>
                                <dd className="text-foreground mt-0.5 font-medium">{data.tanggal_terbit}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Metode Pembayaran</dt>
                                <dd className="text-foreground mt-0.5 font-medium">{data.metode}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Total Nominal</dt>
                                <dd className="text-foreground mt-0.5 font-semibold tabular-nums">{formatRupiah(data.total_rupiah)}</dd>
                            </div>
                        </dl>

                        <div className="mt-5 overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-subtle text-left text-xs uppercase">
                                    <tr>
                                        <th className="px-3 py-2">Jenis Dana</th>
                                        <th className="px-3 py-2">Sumber Dana</th>
                                        <th className="px-3 py-2 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.rincian.map((item, idx) => (
                                        <tr key={idx} className="border-t">
                                            <td className="px-3 py-2">{item.jenis_dana}</td>
                                            <td className="px-3 py-2">{item.sumber_dana}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{formatRupiah(item.jumlah)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <p className="text-muted-foreground mt-4 text-xs">
                            Demi menjaga privasi muzakki sesuai UU Pelindungan Data Pribadi, halaman verifikasi publik tidak menampilkan identitas penyetor.
                        </p>
                    </div>
                </div>
            )}
        </PublicLayout>
    );
}
