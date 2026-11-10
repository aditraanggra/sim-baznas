import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Impor', href: '/pengaturan/impor' },
    { title: 'Hasil', href: '#' },
];

interface Row {
    id: number;
    nomor_baris: number;
    kunci: string | null;
    status: string;
    galat: Record<string, string[]> | null;
}

export default function ImporHasil({ job, rows }: { job: { id: number; nama_berkas: string; jenis: string; status: string; total_baris: number; baris_sukses: number; baris_gagal: number }; rows: Row[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hasil impor" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="rounded-xl border p-4 text-sm">
                    <p className="font-semibold">{job.nama_berkas}</p>
                    <p className="text-muted-foreground">
                        Jenis {job.jenis} · status {job.status} · {job.baris_sukses} valid, {job.baris_gagal} galat dari {job.total_baris} baris
                    </p>
                    <Link className="mt-2 inline-block underline" href={route('pengaturan.impor')}>
                        Kembali ke impor
                    </Link>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Baris</th>
                                <th className="px-3 py-2">Kunci</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Galat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((r) => (
                                <tr key={r.id} className="border-t">
                                    <td className="px-3 py-1.5 tabular-nums">{r.nomor_baris}</td>
                                    <td className="px-3 py-1.5 font-mono text-xs">{r.kunci ?? '—'}</td>
                                    <td className="px-3 py-1.5">{r.status}</td>
                                    <td className="px-3 py-1.5 text-xs">{r.galat ? Object.entries(r.galat).map(([k, v]) => `${k}: ${v.join(', ')}`).join('; ') : '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
