import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Audit', href: '/pengaturan/audit' }];

interface Log {
    id: number;
    waktu: string | null;
    pengguna: string;
    aksi: string;
    entitas: string;
    nilai_lama: Record<string, unknown> | null;
    nilai_baru: Record<string, unknown> | null;
    alasan: string | null;
}

export default function Audit({
    log,
    filter,
}: {
    log: { data: Log[]; links: { url: string | null; label: string }[]; from: number | null; to: number | null; total: number };
    filter: { entitas: string };
}) {
    const [entitas, setEntitas] = useState(filter.entitas);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <form
                    className="flex gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(route('pengaturan.audit'), entitas ? { entitas } : {}, { preserveState: true });
                    }}
                >
                    <input className="rounded-md border px-3 py-2 text-sm" placeholder="Filter entitas (mis. PosAnggaran)" value={entitas} onChange={(e) => setEntitas(e.target.value)} />
                    <button className="rounded-md border px-3 py-2 text-sm" type="submit">
                        Terapkan
                    </button>
                </form>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Waktu</th>
                                <th className="px-3 py-2">Pengguna</th>
                                <th className="px-3 py-2">Aksi</th>
                                <th className="px-3 py-2">Entitas</th>
                                <th className="px-3 py-2">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {log.data.map((l) => (
                                <tr key={l.id} className="border-t align-top">
                                    <td className="px-3 py-1.5 whitespace-nowrap">{l.waktu ? new Date(l.waktu).toLocaleString('id-ID') : '—'}</td>
                                    <td className="px-3 py-1.5">{l.pengguna}</td>
                                    <td className="px-3 py-1.5">{l.aksi}</td>
                                    <td className="px-3 py-1.5 font-mono text-xs">{l.entitas}</td>
                                    <td className="px-3 py-1.5 text-xs">
                                        {l.nilai_baru ? JSON.stringify(l.nilai_baru).slice(0, 160) : l.alasan ?? '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="text-muted-foreground flex gap-2 text-sm">
                    {log.links.map((l, i) => (
                        <button
                            key={i}
                            disabled={!l.url}
                            className="rounded border px-2 py-1 disabled:opacity-40"
                            onClick={() => l.url && router.get(l.url, {}, { preserveState: true })}
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
