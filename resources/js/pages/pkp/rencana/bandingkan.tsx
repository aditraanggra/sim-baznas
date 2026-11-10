import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rencana', href: '/pkp/rencana' },
    { title: 'Bandingkan', href: '/pkp/rencana/bandingkan' },
];

type Mode = 'versi' | 'tahun';

interface VersiRingkas {
    id: number;
    tahun: number;
    label: string;
    status: string;
}

interface Sisi {
    galat: string | null;
    bukan_disahkan: boolean;
    versi: VersiRingkas | null;
}

interface TakTerpetakan {
    jumlah: number;
    kode: string[];
}

interface Baris {
    id: number;
    kode: string;
    nama: string;
    lampiran: string;
    grup: string;
    induk_id: number | null;
    tipe_nilai: string;
    agregasi: string;
    nilai_a: number;
    nilai_b: number;
    selisih: number;
    persen: number | null;
    persen_tampil: string;
}

interface Hasil {
    baris: Baris[];
    tak_terpetakan_a: TakTerpetakan;
    tak_terpetakan_b: TakTerpetakan;
}

interface Props {
    mode: Mode;
    daftar_versi: VersiRingkas[];
    a: Sisi | null;
    b: Sisi | null;
    hasil: Hasil | null;
}

const URUTAN_GRUP = ['L2', 'L2/LN', 'L3', 'L4', 'L5', 'L6', 'L7'];

const nama = (v: VersiRingkas) => `${v.tahun} ${v.label} (${v.status})`;

const tampilNilai = (nilai: number, tipe: string) => (tipe === 'rupiah' ? formatRupiah(nilai) : formatAngka(nilai));

function tampilSelisih(selisih: number, tipe: string) {
    const isi = tampilNilai(Math.abs(selisih), tipe);
    if (selisih > 0) return `+${isi}`;
    if (selisih < 0) return `−${isi}`;
    return isi;
}

function tampilPersen(b: Baris) {
    if (b.persen === null) return b.persen_tampil;
    if (b.persen > 0) return `+${b.persen_tampil}`;
    return b.persen_tampil.replace('-', '−');
}

const warnaSelisih = (selisih: number) => (selisih > 0 ? 'text-green-700 dark:text-green-400' : selisih < 0 ? 'text-red-700 dark:text-red-400' : '');

function Pemilih({
    judul,
    mode,
    daftar,
    tahunList,
    nilai,
    onChange,
}: {
    judul: string;
    mode: Mode;
    daftar: VersiRingkas[];
    tahunList: number[];
    nilai: string;
    onChange: (v: string) => void;
}) {
    return (
        <label className="flex flex-col gap-1 text-sm">
            <span className="font-medium">{judul}</span>
            <select className="rounded-md border px-3 py-2 text-sm" value={nilai} onChange={(e) => onChange(e.target.value)}>
                <option value="">{mode === 'versi' ? 'Pilih versi' : 'Pilih tahun'}</option>
                {mode === 'versi'
                    ? daftar.map((v) => (
                          <option key={v.id} value={v.id}>
                              {nama(v)}
                          </option>
                      ))
                    : tahunList.map((t) => (
                          <option key={t} value={t}>
                              {t}
                          </option>
                      ))}
            </select>
        </label>
    );
}

function Peringatan({ judul, sisi, label, tak }: { judul: string; sisi: Sisi | null; label: string; tak: TakTerpetakan }) {
    if (tak.jumlah === 0) return null;
    return (
        <Alert>
            <AlertTitle>
                {judul}: {tak.jumlah} nilai tak terpetakan
            </AlertTitle>
            <AlertDescription>
                Sisi {label} ({sisi?.versi ? nama(sisi.versi) : '-'}) memuat nilai yang belum terpetakan ke pos baku. Nilai ini tidak ikut total
                pembanding. Kode asal: <span className="font-mono">{tak.kode.join(', ')}</span>
            </AlertDescription>
        </Alert>
    );
}

export default function Bandingkan({ mode: modeAwal, daftar_versi, a, b, hasil }: Props) {
    const [mode, setMode] = useState<Mode>(modeAwal);
    const [pilihA, setPilihA] = useState(String((modeAwal === 'versi' ? a?.versi?.id : a?.versi?.tahun) ?? ''));
    const [pilihB, setPilihB] = useState(String((modeAwal === 'versi' ? b?.versi?.id : b?.versi?.tahun) ?? ''));
    const [tab, setTab] = useState('');

    const tahunList = useMemo(() => [...new Set(daftar_versi.map((v) => v.tahun))], [daftar_versi]);

    const grupList = useMemo(() => {
        const ada = new Set((hasil?.baris ?? []).map((r) => r.grup));
        return URUTAN_GRUP.filter((g) => ada.has(g));
    }, [hasil]);

    const aktif = grupList.includes(tab) ? tab : (grupList[0] ?? '');

    const baris = useMemo(() => {
        const semua = hasil?.baris ?? [];
        const induk = new Map(semua.map((r) => [r.id, r.induk_id]));
        const tingkat = (id: number) => {
            let t = 0;
            for (let p = induk.get(id); p; p = induk.get(p)) t++;
            return t;
        };
        return semua.filter((r) => r.grup === aktif).map((r) => ({ ...r, tingkat: tingkat(r.id) }));
    }, [hasil, aktif]);

    const ganti = (m: Mode) => {
        setMode(m);
        setPilihA('');
        setPilihB('');
    };

    const bandingkan = () => {
        const kunci = mode === 'versi' ? 'versi' : 'tahun';
        router.get(route('pkp.rencana.bandingkan'), { [`${kunci}_a`]: pilihA, [`${kunci}_b`]: pilihB }, { preserveState: true });
    };

    const galat = [a?.galat, b?.galat].filter(Boolean);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bandingkan RKAT" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end gap-3">
                    <label className="flex flex-col gap-1 text-sm">
                        <span className="font-medium">Banding menurut</span>
                        <select className="rounded-md border px-3 py-2 text-sm" value={mode} onChange={(e) => ganti(e.target.value as Mode)}>
                            <option value="versi">Versi</option>
                            <option value="tahun">Tahun</option>
                        </select>
                    </label>
                    <Pemilih judul="Sisi A (acuan)" mode={mode} daftar={daftar_versi} tahunList={tahunList} nilai={pilihA} onChange={setPilihA} />
                    <Pemilih
                        judul="Sisi B (pembanding)"
                        mode={mode}
                        daftar={daftar_versi}
                        tahunList={tahunList}
                        nilai={pilihB}
                        onChange={setPilihB}
                    />
                    <button
                        className="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm disabled:opacity-50"
                        disabled={!pilihA || !pilihB}
                        onClick={bandingkan}
                    >
                        Bandingkan
                    </button>
                </div>

                {galat.map((g) => (
                    <Alert key={g} variant="destructive">
                        <AlertDescription>{g}</AlertDescription>
                    </Alert>
                ))}

                {(
                    [
                        ['A', a],
                        ['B', b],
                    ] as const
                ).map(([label, sisi]) =>
                    sisi?.bukan_disahkan && sisi.versi ? (
                        <Alert key={label}>
                            <AlertTitle>Versi acuan bukan Disahkan</AlertTitle>
                            <AlertDescription>
                                Tahun {sisi.versi.tahun} belum punya versi Disahkan; sisi {label} memakai versi terbaru: {nama(sisi.versi)}.
                            </AlertDescription>
                        </Alert>
                    ) : null,
                )}

                {hasil && (
                    <>
                        <Peringatan judul="Peringatan" sisi={a} label="A" tak={hasil.tak_terpetakan_a} />
                        <Peringatan judul="Peringatan" sisi={b} label="B" tak={hasil.tak_terpetakan_b} />

                        <div className="text-muted-foreground text-sm">
                            A: {a?.versi ? nama(a.versi) : '-'} · B: {b?.versi ? nama(b.versi) : '-'} · Selisih = B − A; persen = selisih terhadap A.
                        </div>

                        <div className="flex flex-wrap gap-2" role="tablist">
                            {grupList.map((g) => (
                                <button
                                    key={g}
                                    role="tab"
                                    aria-selected={g === aktif}
                                    className={`rounded-md border px-3 py-1.5 text-sm ${g === aktif ? 'bg-primary text-primary-foreground' : ''}`}
                                    onClick={() => setTab(g)}
                                >
                                    {g === 'L2/LN' ? 'L2 Luar Neraca' : `Lampiran ${g}`}
                                </button>
                            ))}
                        </div>

                        <div className="max-h-[calc(100vh-20rem)] overflow-auto rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/80 sticky top-0 text-left">
                                    <tr>
                                        <th className="px-3 py-2">Kode</th>
                                        <th className="px-3 py-2">Uraian</th>
                                        <th className="px-3 py-2 text-right">A</th>
                                        <th className="px-3 py-2 text-right">B</th>
                                        <th className="px-3 py-2 text-right">Selisih</th>
                                        <th className="px-3 py-2 text-right">Persen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {baris.map((r) => (
                                        <tr key={r.id} className={`border-t ${r.agregasi === 'jumlah_anak' ? 'bg-muted/30 font-medium' : ''}`}>
                                            <td className="px-3 py-1.5 font-mono" style={{ paddingLeft: `${0.75 + r.tingkat * 0.9}rem` }}>
                                                {r.kode}
                                            </td>
                                            <td className="px-3 py-1.5">{r.nama}</td>
                                            <td className="px-3 py-1.5 text-right tabular-nums">{tampilNilai(r.nilai_a, r.tipe_nilai)}</td>
                                            <td className="px-3 py-1.5 text-right tabular-nums">{tampilNilai(r.nilai_b, r.tipe_nilai)}</td>
                                            <td className={`px-3 py-1.5 text-right tabular-nums ${warnaSelisih(r.selisih)}`}>
                                                {tampilSelisih(r.selisih, r.tipe_nilai)}
                                            </td>
                                            <td className={`px-3 py-1.5 text-right tabular-nums ${warnaSelisih(r.selisih)}`}>{tampilPersen(r)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
