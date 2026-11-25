import { EmptyState, StatusBadge } from '@/components/app';
import { PublicLayout } from '@/layouts/public-layout';
import { CheckCircle2, XCircle } from 'lucide-react';

interface DataSuratPublik {
    nomor: string;
    tanggal: string;
    jenis_surat: string;
    sifat: string;
    status: 'disetujui' | 'dibatalkan' | string;
    perihal: string;
    tujuan: string;
    nama_penandatangan: string;
    jabatan_penandatangan: string;
    disetujui_at: string | null;
}

export default function VerifikasiSurat({ ditemukan, data }: { ditemukan: boolean; data: DataSuratPublik | null }) {
    return (
        <PublicLayout title="Verifikasi Keaslian Surat Dinas" noindex={true}>
            {!ditemukan || !data ? (
                <EmptyState
                    title="Surat Dinas Tidak Terdaftar"
                    description="Kode verifikasi surat dinas tidak ditemukan atau surat belum disahkan secara resmi oleh BAZNAS Kabupaten Cianjur."
                />
            ) : (
                <div className="bg-card border-border rounded-xl border p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
                        <div className="flex items-start gap-3">
                            {data.status === 'disetujui' ? (
                                <CheckCircle2 className="text-success mt-0.5 h-6 w-6 shrink-0" aria-hidden="true" />
                            ) : (
                                <XCircle className="text-destructive mt-0.5 h-6 w-6 shrink-0" aria-hidden="true" />
                            )}
                            <div>
                                <h1 className="font-display text-foreground text-xl font-semibold">
                                    {data.status === 'disetujui' ? 'Surat Dinas Resmi Terverifikasi' : 'Surat Dinas Dibatalkan'}
                                </h1>
                                <p className="text-muted-foreground text-sm">Nomor: {data.nomor}</p>
                            </div>
                        </div>

                        <StatusBadge
                            status={data.status}
                            variant={data.status === 'disetujui' ? 'final' : 'dibatalkan'}
                            label={data.status === 'disetujui' ? 'Sah / Final' : 'Dibatalkan'}
                        />
                    </div>

                    <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground text-xs">Jenis Surat</dt>
                            <dd className="text-foreground mt-0.5 font-medium">{data.jenis_surat}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Tanggal Surat</dt>
                            <dd className="text-foreground mt-0.5 font-medium">{data.tanggal}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Perihal</dt>
                            <dd className="text-foreground mt-0.5 font-medium">{data.perihal}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Tujuan</dt>
                            <dd className="text-foreground mt-0.5 font-medium">{data.tujuan}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground text-xs">Penandatangan</dt>
                            <dd className="text-foreground mt-0.5 font-medium">
                                {data.nama_penandatangan} ({data.jabatan_penandatangan})
                            </dd>
                        </div>
                        {data.disetujui_at && (
                            <div>
                                <dt className="text-muted-foreground text-xs">Disahkan Pada</dt>
                                <dd className="text-foreground mt-0.5 font-medium">{data.disetujui_at}</dd>
                            </div>
                        )}
                    </dl>

                    <p className="text-muted-foreground mt-5 border-t pt-4 text-xs">
                        Halaman ini hanya menampilkan metadata pengesahan dokumen resmi BAZNAS Kabupaten Cianjur dan tidak memuat isi surat maupun lampiran.
                    </p>
                </div>
            )}
        </PublicLayout>
    );
}
