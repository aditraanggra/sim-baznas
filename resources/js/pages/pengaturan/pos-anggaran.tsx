import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pos Anggaran', href: '/pengaturan/pos-anggaran' }];

interface BarisPos {
    id: number;
    kode: string;
    nama: string;
    lampiran: string;
    tingkat: number;
    tipe_nilai: string;
    agregasi: string;
    sifat_waktu: string;
    aktif: boolean;
    induk: string | null;
}

export default function PosAnggaran({ pos }: { pos: BarisPos[] }) {
    const [lampiran, setLampiran] = useState('');
    const [cari, setCari] = useState('');

    const hasil = useMemo(
        () =>
            pos.filter(
                (p) =>
                    (!lampiran || p.lampiran === lampiran) &&
                    (!cari || p.kode.toLowerCase().includes(cari.toLowerCase()) || p.nama.toLowerCase().includes(cari.toLowerCase())),
            ),
        [pos, lampiran, cari],
    );

    const lampiranList = [...new Set(pos.map((p) => p.lampiran))];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pos Anggaran" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <select className="rounded-md border px-3 py-2 text-sm" value={lampiran} onChange={(e) => setLampiran(e.target.value)}>
                        <option value="">Semua lampiran</option>
                        {lampiranList.map((l) => (
                            <option key={l} value={l}>
                                Lampiran {l}
                            </option>
                        ))}
                    </select>
                    <input className="rounded-md border px-3 py-2 text-sm" placeholder="Cari kode atau nama" value={cari} onChange={(e) => setCari(e.target.value)} />
                    <span className="text-muted-foreground text-sm">
                        {hasil.length} dari {pos.length} pos
                    </span>
                </div>

                <div className="max-h-[calc(100vh-16rem)] overflow-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/80 sticky top-0 text-left">
                            <tr>
                                <th className="px-3 py-2">Kode</th>
                                <th className="px-3 py-2">Nama</th>
                                <th className="px-3 py-2">Induk</th>
                                <th className="px-3 py-2">Tipe</th>
                                <th className="px-3 py-2">Agregasi</th>
                                <th className="px-3 py-2">Sifat</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {hasil.map((p) => (
                                <tr key={p.id} className="border-t">
                                    <td className="px-3 py-1.5 font-mono" style={{ paddingLeft: `${0.75 + (p.tingkat - 2) * 0.9}rem` }}>
                                        {p.kode}
                                    </td>
                                    <td className="px-3 py-1.5">{p.nama}</td>
                                    <td className="px-3 py-1.5 font-mono text-xs">{p.induk ?? '—'}</td>
                                    <td className="px-3 py-1.5">{p.tipe_nilai}</td>
                                    <td className="px-3 py-1.5">{p.agregasi}</td>
                                    <td className="px-3 py-1.5">{p.sifat_waktu}</td>
                                    <td className="px-3 py-1.5">{p.aktif ? 'Aktif' : 'Nonaktif'}</td>
                                    <td className="px-3 py-1.5 text-right">
                                        {p.aktif && (
                                            <button
                                                className="text-sm underline"
                                                onClick={() => {
                                                    if (!confirm(`Nonaktifkan ${p.kode}?`)) return;
                                                    router.post(route('pengaturan.pos-anggaran.nonaktifkan'), { id: p.id }, { preserveScroll: true });
                                                }}
                                            >
                                                Nonaktifkan
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
