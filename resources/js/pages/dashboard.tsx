import AppLayout from '@/layouts/app-layout';
import { formatAngka } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

export default function Dashboard({ ringkasan }: { ringkasan: { total_pos: number; total_alias: number; impor_berjalan: number; audit_terakhir: string | null } }) {
    const { tahun_aktif } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <Kartu judul="Pos anggaran" nilai={formatAngka(ringkasan.total_pos)} keterangan={`berlaku tahun ${tahun_aktif}`} />
                    <Kartu judul="Alias pos" nilai={formatAngka(ringkasan.total_alias)} keterangan="pemetaan kode lama" />
                    <Kartu judul="Impor berjalan" nilai={formatAngka(ringkasan.impor_berjalan)} keterangan="job menunggu/proses" />
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-sm">
                    <p className="font-medium">Dashboard PKP</p>
                    <p className="text-muted-foreground mt-1">Kartu ringkas, grafik kumulatif, dan peringatan diisi pada modul PK5 setelah realisasi tersedia.</p>
                    <p className="text-muted-foreground mt-4">
                        Audit terakhir: {ringkasan.audit_terakhir ? new Date(ringkasan.audit_terakhir).toLocaleString('id-ID') : 'belum ada'}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}

function Kartu({ judul, nilai, keterangan }: { judul: string; nilai: string; keterangan: string }) {
    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <p className="text-muted-foreground text-sm">{judul}</p>
            <p className="mt-2 text-2xl font-semibold tabular-nums">{nilai}</p>
            <p className="text-muted-foreground mt-1 text-xs">{keterangan}</p>
        </div>
    );
}
