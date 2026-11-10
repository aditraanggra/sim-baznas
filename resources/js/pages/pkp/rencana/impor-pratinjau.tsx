import AppLayout from '@/layouts/app-layout';
import { formatAngka } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, CircleHelp, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PenandaLangkah } from './impor';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rencana (RKAT)', href: '/pkp/rencana' },
    { title: 'Impor', href: '/pkp/rencana/impor' },
    { title: 'Pratinjau', href: '#' },
];

interface Baris {
    id: number;
    nomor_baris: number;
    status: string;
    galat: Record<string, string[]> | null;
    kode: string;
    nama: string | null;
    tipe_nilai: string;
    nilai: number | string | null;
    subtotal: boolean;
    sheet: string | null;
    pos: { kode: string; nama: string; daun: boolean } | null;
}

interface PosDaun {
    id: number;
    kode: string;
    nama: string;
    tipe_nilai: string;
}

interface Tujuan {
    id: number;
    label: string;
    sumber: string;
}

interface Props {
    job: { id: number; nama_berkas: string; tahun: number; total_baris: number; baris_sukses: number; baris_gagal: number };
    rows: Baris[];
    tujuan: Tujuan[];
    awal_ada: boolean;
    pos_daun: PosDaun[];
}

type Filter = 'semua' | 'galat' | 'tak_terpetakan' | 'subtotal';

const takTerpetakan = (r: Baris) => !r.subtotal && r.pos === null;

const FILTER: { kunci: Filter; teks: string; cocok: (r: Baris) => boolean }[] = [
    { kunci: 'semua', teks: 'Semua', cocok: () => true },
    { kunci: 'galat', teks: 'Galat', cocok: (r) => r.status !== 'valid' },
    { kunci: 'tak_terpetakan', teks: 'Tak terpetakan', cocok: takTerpetakan },
    { kunci: 'subtotal', teks: 'Subtotal', cocok: (r) => r.subtotal },
];

function PemetaAlias({ tahun, baris, posDaun }: { tahun: number; baris: Baris; posDaun: PosDaun[] }) {
    const [cari, setCari] = useState('');
    const [pilih, setPilih] = useState('');
    const [proses, setProses] = useState(false);

    const opsi = useMemo(() => {
        const q = cari.trim().toLowerCase();
        return posDaun
            .filter((p) => !q || p.kode.toLowerCase().includes(q) || p.nama.toLowerCase().includes(q))
            .sort((a, b) => Number(b.tipe_nilai === baris.tipe_nilai) - Number(a.tipe_nilai === baris.tipe_nilai));
    }, [cari, posDaun, baris.tipe_nilai]);

    return (
        <form
            className="mt-1 flex flex-wrap items-center gap-1"
            onSubmit={(e) => {
                e.preventDefault();
                router.post(
                    route('pkp.rencana.impor.alias'),
                    { tahun, kode_alias: baris.kode, pos_anggaran_id: Number(pilih) },
                    { preserveScroll: true, onStart: () => setProses(true), onFinish: () => setProses(false) },
                );
            }}
        >
            <input
                type="search"
                value={cari}
                onChange={(e) => setCari(e.target.value)}
                placeholder="Cari kode/nama pos"
                aria-label={`Cari pos baku untuk ${baris.kode}`}
                className="w-36 rounded-md border px-2 py-1 text-xs"
            />
            <select
                value={pilih}
                onChange={(e) => setPilih(e.target.value)}
                aria-label={`Pos baku tujuan untuk ${baris.kode}`}
                className="max-w-56 rounded-md border px-2 py-1 text-xs"
            >
                <option value="">Pilih pos baku…</option>
                {opsi.map((p) => (
                    <option key={p.id} value={p.id}>
                        {p.kode} — {p.nama} ({p.tipe_nilai})
                    </option>
                ))}
            </select>
            <button type="submit" disabled={!pilih || proses} className="rounded-md border px-2 py-1 text-xs disabled:opacity-50">
                Petakan
            </button>
        </form>
    );
}

export default function ImporPratinjau({ job, rows, tujuan, awal_ada, pos_daun }: Props) {
    const { props } = usePage<SharedData>();
    const galatServer = Object.values(props.errors ?? {});
    const sukses = props.flash?.sukses;

    const [filter, setFilter] = useState<Filter>('semua');
    const [pilihTujuan, setPilihTujuan] = useState<string>(awal_ada ? String(tujuan[0]?.id ?? '') : 'baru');
    const [proses, setProses] = useState(false);

    const tampil = rows.filter(FILTER.find((f) => f.kunci === filter)!.cocok);
    const bisaTerapkan = job.baris_sukses > 0 && pilihTujuan !== '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pratinjau impor RKAT" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <section className="flex flex-col gap-3 rounded-xl border p-4">
                    <h1 className="text-lg font-semibold">Pratinjau impor RKAT {job.tahun}</h1>
                    <PenandaLangkah aktif={2} />
                    <p className="text-sm">
                        <span className="font-medium">{job.nama_berkas}</span> · tahun {job.tahun} · {formatAngka(job.total_baris)} baris,{' '}
                        {formatAngka(job.baris_sukses)} valid, {formatAngka(job.baris_gagal)} galat
                    </p>
                    <Link className="w-fit text-sm underline" href={route('pkp.rencana.impor')}>
                        Unggah berkas lain
                    </Link>
                </section>

                {sukses && (
                    <p role="status" className="rounded-md border px-3 py-2 text-sm">
                        <CheckCircle2 className="mr-1 inline size-4" aria-hidden="true" />
                        {sukses}
                    </p>
                )}
                {galatServer.length > 0 && (
                    <div role="alert" className="rounded-md border border-red-600 px-3 py-2 text-sm text-red-700">
                        {galatServer.map((g, i) => (
                            <p key={i}>
                                <XCircle className="mr-1 inline size-4" aria-hidden="true" />
                                Galat: {String(g)}
                            </p>
                        ))}
                    </div>
                )}

                <div className="flex flex-wrap gap-2" role="group" aria-label="Filter baris">
                    {FILTER.map((f) => (
                        <button
                            key={f.kunci}
                            type="button"
                            aria-pressed={filter === f.kunci}
                            onClick={() => setFilter(f.kunci)}
                            className={`rounded-md border px-3 py-1 text-sm ${filter === f.kunci ? 'bg-primary text-primary-foreground' : ''}`}
                        >
                            {f.teks} ({rows.filter(f.cocok).length})
                        </button>
                    ))}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Baris</th>
                                <th className="px-3 py-2">Sheet</th>
                                <th className="px-3 py-2">Kode</th>
                                <th className="px-3 py-2">Uraian</th>
                                <th className="px-3 py-2 text-right">Nilai</th>
                                <th className="px-3 py-2">Jenis</th>
                                <th className="px-3 py-2">Pemetaan</th>
                                <th className="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tampil.length === 0 && (
                                <tr className="border-t">
                                    <td colSpan={8} className="text-muted-foreground px-3 py-2">
                                        Tidak ada baris untuk filter ini.
                                    </td>
                                </tr>
                            )}
                            {tampil.map((r) => (
                                <tr key={r.id} className="border-t align-top">
                                    <td className="px-3 py-1.5 tabular-nums">{r.nomor_baris}</td>
                                    <td className="px-3 py-1.5">{r.sheet ?? '—'}</td>
                                    <td className="px-3 py-1.5 font-mono text-xs">{r.kode}</td>
                                    <td className="px-3 py-1.5">{r.nama ?? '—'}</td>
                                    <td className="px-3 py-1.5 text-right tabular-nums">{r.nilai === null ? '—' : formatAngka(Number(r.nilai))}</td>
                                    <td className="px-3 py-1.5">{r.subtotal ? 'Subtotal dilewati' : 'Daun'}</td>
                                    <td className="px-3 py-1.5">
                                        {r.subtotal ? (
                                            '—'
                                        ) : r.pos ? (
                                            <span>
                                                <span className="font-mono text-xs">{r.pos.kode}</span> {r.pos.nama}
                                            </span>
                                        ) : (
                                            <>
                                                <span className="inline-flex items-center gap-1 rounded-full border border-amber-600 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                    <CircleHelp className="size-3.5" aria-hidden="true" />
                                                    Tak terpetakan
                                                </span>
                                                <PemetaAlias tahun={job.tahun} baris={r} posDaun={pos_daun} />
                                            </>
                                        )}
                                    </td>
                                    <td className="px-3 py-1.5">
                                        {r.status === 'valid' ? (
                                            <span className="inline-flex items-center gap-1 text-xs font-medium">
                                                <CheckCircle2 className="size-3.5" aria-hidden="true" />
                                                Valid
                                            </span>
                                        ) : (
                                            <span className="text-xs font-medium text-red-700">
                                                <span className="inline-flex items-center gap-1">
                                                    <XCircle className="size-3.5" aria-hidden="true" />
                                                    Galat
                                                </span>
                                                {r.galat && (
                                                    <span className="block font-normal">
                                                        {Object.entries(r.galat)
                                                            .map(([k, v]) => `${k}: ${v.join(', ')}`)
                                                            .join('; ')}
                                                    </span>
                                                )}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <section className="flex flex-col gap-3 rounded-xl border p-4">
                    <h2 className="font-semibold">Terapkan impor</h2>
                    <PenandaLangkah aktif={3} />
                    <p className="flex items-start gap-1 text-sm">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        Baris galat tidak diterapkan. Hanya {formatAngka(job.baris_sukses)} baris valid yang akan disimpan.
                    </p>
                    <fieldset className="grid gap-2 text-sm">
                        <legend className="mb-1 font-medium">Tujuan</legend>
                        <label className="flex items-start gap-2">
                            <input
                                type="radio"
                                name="tujuan"
                                className="mt-1"
                                disabled={awal_ada}
                                checked={pilihTujuan === 'baru'}
                                onChange={() => setPilihTujuan('baru')}
                            />
                            <span>
                                Buat RKAT Awal baru
                                {awal_ada && (
                                    <span className="text-muted-foreground block text-xs">
                                        Tidak tersedia: RKAT Awal tahun {job.tahun} sudah ada. Pilih versi Draft di bawah.
                                    </span>
                                )}
                            </span>
                        </label>
                        {tujuan.map((t) => (
                            <label key={t.id} className="flex items-start gap-2">
                                <input
                                    type="radio"
                                    name="tujuan"
                                    className="mt-1"
                                    checked={pilihTujuan === String(t.id)}
                                    onChange={() => setPilihTujuan(String(t.id))}
                                />
                                <span>
                                    {t.label} (Draft, sumber {t.sumber === 'impor' ? 'Impor' : 'Manual'})
                                </span>
                            </label>
                        ))}
                        {awal_ada && tujuan.length === 0 && (
                            <p className="text-muted-foreground text-xs">
                                Tidak ada versi Draft untuk tahun {job.tahun}; buat versi Draft terlebih dahulu.
                            </p>
                        )}
                    </fieldset>
                    {job.baris_sukses === 0 && <p className="text-sm text-red-700">Tidak ada baris valid; impor tidak dapat diterapkan.</p>}
                    <button
                        type="button"
                        disabled={!bisaTerapkan || proses}
                        className="bg-primary text-primary-foreground w-fit rounded-md px-4 py-2 text-sm disabled:opacity-50"
                        onClick={() =>
                            router.post(
                                route('pkp.rencana.impor.terapkan', job.id),
                                { rkat_version_id: pilihTujuan === 'baru' ? null : Number(pilihTujuan) },
                                { onStart: () => setProses(true), onFinish: () => setProses(false) },
                            )
                        }
                    >
                        {proses ? 'Menerapkan…' : 'Terapkan impor'}
                    </button>
                </section>
            </div>
        </AppLayout>
    );
}
