import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Disposisi Saya', href: '/distribusi/disposisi-saya' },
];

interface AntreanItem {
    id: number;
    nomor: string;
    tanggal: string | null;
    mustahik: string | null;
    program: string | null;
    nominal_diajukan: number;
    nominal_disetujui: number | null;
    status_kode: string;
    langkah_sekarang: number;
    kesiapan_keuangan: string;
    instruksi: string | null;
}

export default function DisposisiSaya({ antrean }: { antrean: AntreanItem[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Disposisi Saya" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Antrean Disposisi Saya"
                    description="Daftar permohonan bantuan yang memerlukan tindak lanjut sesuai peran atau penugasan Anda."
                    breadcrumbs={breadcrumbs}
                />

                {antrean.length === 0 ? (
                    <EmptyState
                        title="Tidak Ada Disposisi Menunggu"
                        description="Seluruh permohonan yang menjadi tanggung jawab peran Anda sudah ditindaklanjuti."
                    />
                ) : (
                    <div className="grid gap-3">
                        {antrean.map((item) => (
                            <div
                                key={item.id}
                                className="bg-card border-border flex flex-col justify-between gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-foreground font-semibold">{item.nomor}</span>
                                        <StatusBadge status={item.status_kode} label={`Langkah ${item.langkah_sekarang}: ${item.status_kode.replaceAll('_', ' ')}`} />
                                        {item.status_kode === 'minta_keuangan' && (
                                            <StatusBadge
                                                status={item.kesiapan_keuangan}
                                                variant={item.kesiapan_keuangan === 'siap' ? 'success' : 'warning'}
                                                label={item.kesiapan_keuangan === 'siap' ? 'Dana Siap' : 'Menunggu Keuangan'}
                                            />
                                        )}
                                    </div>
                                    <p className="text-foreground text-sm">
                                        {item.mustahik ?? '-'} · {item.program ?? '-'}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        Tanggal {item.tanggal ?? '-'} · Diajukan {formatRupiah(item.nominal_diajukan)}
                                        {item.nominal_disetujui ? ` · Disetujui ${formatRupiah(item.nominal_disetujui)}` : ''}
                                    </p>
                                    {item.instruksi && <p className="text-muted-foreground text-xs">Instruksi: {item.instruksi}</p>}
                                </div>

                                <Button asChild className="h-9 shrink-0 gap-1.5">
                                    <Link href={route('distribusi.permohonan.show', item.id)}>
                                        <span>Buka & Proses</span>
                                        <ArrowRight className="h-4 w-4" aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
