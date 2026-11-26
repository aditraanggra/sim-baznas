import { PanelTargetDanAturanAmil, PanelVerifikasiBidang, TabKonsolidasiKegiatan } from '@/components/rkat/PanelKolaboratifPkp';
import {
    type AturanAmilItem,
    type KelompokKegiatanGabungan,
    type RingkasanAlokasi,
    type TahapBidangDetail,
} from '@/components/rkat/types';
import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, CheckCircle2, ChevronDown, ChevronRight, CircleDashed, Lock, Send, XCircle, type LucideIcon } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';

interface Versi {
    id: number;
    tahun: number;
    jenis: string;
    label: string;
    status: string;
    sumber: string;
    mode_penyusunan?: string;
    target_pengumpulan?: number | null;
    arsip: boolean;
    nomor_surat: string | null;
    tanggal_surat: string | null;
    disahkan_pada: string | null;
    terkunci: boolean;
}

interface Pos {
    id: number;
    kode: string;
    nama: string;
    lampiran: string;
    grup: string;
    induk_id: number | null;
    tipe_nilai: string;
    agregasi: string;
    editable: boolean;
    nilai: number;
    persen: number | null;
}

interface TakTerpetakan {
    kode_asal: string;
    nama_asal: string | null;
    tipe_nilai: string;
    nilai: number;
}

interface RincianJenis {
    penerimaan: number;
    amil: number;
    batas_bp: number | null;
    lulus: boolean;
    rasio_persen: number | null;
    keterangan: string | null;
}

interface Validasi {
    id: string;
    nama: string;
    lulus: boolean;
    kiri: number;
    kanan: number;
    selisih: number;
    rincian: Record<string, unknown>;
}

interface Catatan {
    batas_amil_zakat_bp: number | null;
    batas_amil_infak_bp: number | null;
    batas_amil_csr_bp: number | null;
    batas_amil_dskl_bp: number | null;
    target_orang: number | null;
    target_pengentasan_ekstrem: number | null;
    target_pengentasan_garis: number | null;
    catatan: string | null;
}

interface Dokumen {
    id: number;
    jenis: string;
    nama_berkas: string;
    ukuran: number;
    diarsipkan: boolean;
    diunggah_pada: string | null;
}

interface Riwayat {
    id: number;
    label: string;
    status: string;
    sumber: string;
    arsip: boolean;
    nomor_surat: string | null;
    disahkan_pada: string | null;
}

interface KolaboratifData {
    ringkasan: RingkasanAlokasi;
    tahap_bidang: TahapBidangDetail[];
    aturan_amil: AturanAmilItem[];
    kegiatan_gabungan: KelompokKegiatanGabungan[];
}

interface Props {
    versi: Versi;
    pos: Pos[];
    tak_terpetakan: TakTerpetakan[];
    validasi: Validasi[];
    catatan: Catatan | null;
    dokumen: Dokumen[];
    riwayat: Riwayat[];
    dapat_tulis: boolean;
    dapat_verifikasi?: boolean;
    kolaboratif?: KolaboratifData | null;
}

const STATUS: Record<string, { teks: string; ikon: LucideIcon }> = {
    draft: { teks: 'Draft', ikon: CircleDashed },
    diajukan: { teks: 'Diajukan', ikon: Send },
    disahkan: { teks: 'Disahkan', ikon: CheckCircle2 },
    digantikan: { teks: 'Digantikan', ikon: Archive },
};

const LAMPIRAN = ['L2', 'L3', 'L4', 'L5', 'L6', 'L7'];
const JUDUL: Record<string, string> = {
    L2: 'Lampiran 2 · Penerimaan',
    L3: 'Lampiran 3 · Penyaluran',
    L4: 'Lampiran 4 · Program',
    L5: 'Lampiran 5 · Muzakki dan Mustahik',
    L6: 'Lampiran 6 · Penerimaan Dana Operasional',
    L7: 'Lampiran 7 · Penggunaan Dana Operasional',
};
const JENIS_DANA: Record<string, string> = { zakat: 'Zakat', infak: 'Infak', csr: 'CSR', dskl: 'DSKL' };
const JENIS_DOKUMEN: Record<string, string> = { surat_pengesahan: 'Surat pengesahan', lembar_penetapan: 'Lembar penetapan' };

const persenTampil = (p: number | null) =>
    p === null ? 'n/a' : `${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(p)}%`;
const bpKePersen = (bp: number | null) => (bp === null ? '' : String(bp / 100).replace('.', ','));
const persenKeBp = (teks: string) => {
    const n = parseFloat(teks.replace(',', '.'));
    return teks.trim() === '' || Number.isNaN(n) ? null : Math.round(n * 100);
};
const satuan = (p: Pos, nilai: number) => (p.tipe_nilai === 'rupiah' ? formatRupiah(nilai) : `${formatAngka(nilai)} ${p.tipe_nilai}`);

function Lencana({ status }: { status: string }) {
    const { teks, ikon: Ikon } = STATUS[status] ?? { teks: status, ikon: CircleDashed };
    return (
        <span className="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium">
            <Ikon className="size-3.5" aria-hidden />
            {teks}
        </span>
    );
}

export default function Show(props: Props) {
    const { versi, pos, tak_terpetakan, validasi, catatan, dokumen, riwayat, dapat_tulis, dapat_verifikasi = false, kolaboratif = null } = props;
    const { errors, flash } = usePage<SharedData & { errors: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Rencana (RKAT)', href: '/pkp/rencana' },
        { title: `${versi.tahun} · ${versi.label}`, href: '#' },
    ];
    const isKolaboratif = versi.mode_penyusunan === 'kolaboratif' && kolaboratif !== null;
    const semuaBidangTerverifikasi = isKolaboratif
        ? kolaboratif.tahap_bidang.every((t) => t.status === 'terverifikasi')
        : true;
    const pengumpulanTerverifikasi = isKolaboratif
        ? kolaboratif.tahap_bidang.some((t) => t.bidang_kode === 'pengumpulan' && t.status === 'terverifikasi')
        : false;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`RKAT ${versi.tahun} ${versi.label}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                {flash?.sukses && (
                    <p className="rounded-md border border-green-600/40 bg-green-50 px-3 py-2 text-sm text-green-900">{flash.sukses}</p>
                )}
                {Object.keys(errors).length > 0 && (
                    <ul className="rounded-md border border-red-600/40 bg-red-50 px-3 py-2 text-sm text-red-900" role="alert">
                        {Object.entries(errors).map(([k, v]) => (
                            <li key={k}>{v}</li>
                        ))}
                    </ul>
                )}

                <Header versi={versi} />
                {tak_terpetakan.length > 0 && <PeringatanTakTerpetakan daftar={tak_terpetakan} />}
                {isKolaboratif && (
                    <>
                        <PanelVerifikasiBidang
                            versiId={versi.id}
                            versiTerkunci={versi.terkunci}
                            tahapBidang={kolaboratif.tahap_bidang}
                            ringkasan={kolaboratif.ringkasan}
                            dapatVerifikasi={dapat_verifikasi}
                        />
                        <PanelTargetDanAturanAmil
                            versiId={versi.id}
                            versiTerkunci={versi.terkunci}
                            targetPengumpulan={versi.target_pengumpulan ?? null}
                            aturanAmil={kolaboratif.aturan_amil}
                            pengumpulanTerverifikasi={pengumpulanTerverifikasi}
                            dapatTulis={dapat_tulis}
                        />
                    </>
                )}
                <Grid versi={versi} pos={pos} dapat_tulis={dapat_tulis} kolaboratif={kolaboratif} />
                <div className="grid gap-4 lg:grid-cols-2">
                    <DaftarValidasi validasi={validasi} sumber={versi.sumber} />
                    {dapat_tulis && <PanelStatus versi={versi} siapAjukanKolaboratif={semuaBidangTerverifikasi} />}
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    <FormCatatan versi={versi} catatan={catatan} bisaUbah={dapat_tulis && !versi.terkunci} isKolaboratif={isKolaboratif} />
                    <PanelDokumen versi={versi} dokumen={dokumen} dapat_tulis={dapat_tulis} />
                </div>
                <Riwayat riwayat={riwayat} aktifId={versi.id} />
            </div>
        </AppLayout>
    );
}

function Header({ versi }: { versi: Versi }) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-xl font-semibold">
                RKAT {versi.tahun} · {versi.label}
            </h1>
            <Lencana status={versi.status} />
            {versi.mode_penyusunan === 'kolaboratif' && (
                <span className="bg-primary-soft text-primary rounded-full px-2.5 py-0.5 text-xs font-medium">
                    Kolaboratif 3 Bidang (Read-Only)
                </span>
            )}
            <span className="text-muted-foreground text-sm">Sumber: {versi.sumber === 'impor' ? 'Impor' : 'Manual'}</span>
            {versi.arsip && <span className="text-sm">Diarsipkan</span>}
            {versi.nomor_surat && (
                <span className="text-muted-foreground text-sm">
                    Surat {versi.nomor_surat}
                    {versi.tanggal_surat ? `, ${versi.tanggal_surat}` : ''}
                </span>
            )}
            <span className="ml-auto flex flex-wrap gap-3 text-sm">
                <a className="underline" href={route('pkp.rencana.ekspor.excel', versi.id)}>
                    Ekspor Excel
                </a>
                <a className="underline" href={route('pkp.rencana.ekspor.pdf', versi.id)}>
                    Ekspor PDF
                </a>
                <Link className="underline" href={route('pkp.rencana.bandingkan')}>
                    Bandingkan
                </Link>
                <Link className="underline" href={route('pkp.rencana')}>
                    Kembali ke daftar
                </Link>
            </span>
        </div>
    );
}

function PeringatanTakTerpetakan({ daftar }: { daftar: TakTerpetakan[] }) {
    return (
        <div className="rounded-md border border-amber-600/40 bg-amber-50 p-3 text-sm text-amber-900" role="alert">
            <p className="font-medium">{daftar.length} pos tak terpetakan; tidak ikut total (BR-10).</p>
            <ul className="mt-1 list-disc pl-5">
                {daftar.map((t) => (
                    <li key={`${t.kode_asal}|${t.tipe_nilai}`}>
                        {t.kode_asal} {t.nama_asal ?? ''}: {formatAngka(t.nilai)} {t.tipe_nilai}
                    </li>
                ))}
            </ul>
            <Link className="mt-1 inline-block underline" href={route('pkp.rencana.impor')}>
                Petakan lewat halaman impor
            </Link>
        </div>
    );
}

function Grid({
    versi,
    pos,
    dapat_tulis,
    kolaboratif,
}: {
    versi: Versi;
    pos: Pos[];
    dapat_tulis: boolean;
    kolaboratif: KolaboratifData | null;
}) {
    const isKolaboratif = versi.mode_penyusunan === 'kolaboratif' && kolaboratif !== null;
    const [tab, setTab] = useState(isKolaboratif ? 'KEGIATAN' : 'L2');
    const [tutup, setTutup] = useState<Set<number>>(new Set());
    const [draft, setDraft] = useState<Record<number, string>>({});
    const [menyimpan, setMenyimpan] = useState(false);
    const refs = useRef<Record<number, HTMLInputElement | null>>({});

    const induk = useMemo(() => new Map(pos.map((p) => [p.id, p.induk_id])), [pos]);
    const punyaAnak = useMemo(() => new Set(pos.map((p) => p.induk_id).filter((x): x is number => x !== null)), [pos]);
    const kedalaman = (p: Pos) => {
        let d = 0;
        for (let i = p.induk_id; i !== null && i !== undefined; i = induk.get(i) ?? null) d++;
        return d;
    };
    const tersembunyi = (p: Pos) => {
        for (let i = p.induk_id; i !== null && i !== undefined; i = induk.get(i) ?? null) if (tutup.has(i)) return true;
        return false;
    };

    const barisTab = pos.filter((p) => p.lampiran === tab);
    const editableIds = barisTab.filter((p) => p.editable).map((p) => p.id);
    const nilaiAsli = useMemo(() => new Map(pos.map((p) => [p.id, p.nilai])), [pos]);
    const berubah = Object.entries(draft).filter(([id, v]) => (v === '' ? 0 : Number(v)) !== nilaiAsli.get(Number(id)));

    const fokusKe = (id: number, arah: 1 | -1) => {
        const i = editableIds.indexOf(id) + arah;
        if (i >= 0 && i < editableIds.length) refs.current[editableIds[i]]?.focus();
    };

    const tempel = (id: number, teks: string) => {
        const nilai = teks
            .split(/\r?\n/)
            .map((l) => l.split('\t')[0].replace(/[^\d]/g, ''))
            .filter((l, i, a) => l !== '' || i < a.length - 1);
        if (nilai.length < 2) return false;
        const mulai = editableIds.indexOf(id);
        setDraft((d) => {
            const baru = { ...d };
            nilai.forEach((n, i) => {
                const target = editableIds[mulai + i];
                if (target !== undefined) baru[target] = n;
            });
            return baru;
        });
        return true;
    };

    const simpan = () => {
        const nilai: Record<number, number> = {};
        berubah.forEach(([id, v]) => (nilai[Number(id)] = v === '' ? 0 : Number(v)));
        router.post(
            route('pkp.rencana.nilai', versi.id),
            { nilai },
            {
                preserveScroll: true,
                onStart: () => setMenyimpan(true),
                onFinish: () => setMenyimpan(false),
                onSuccess: () => setDraft({}),
            },
        );
    };

    let grupSebelumnya = '';

    return (
        <section className="rounded-xl border">
            <div className="flex flex-wrap items-center gap-2 border-b p-2" role="tablist">
                {isKolaboratif && (
                    <button
                        role="tab"
                        aria-selected={tab === 'KEGIATAN'}
                        onClick={() => setTab('KEGIATAN')}
                        className={`rounded-md px-3 py-1.5 text-sm ${tab === 'KEGIATAN' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                    >
                        Konsolidasi Kegiatan (3 Bidang)
                    </button>
                )}
                {LAMPIRAN.map((l) => (
                    <button
                        key={l}
                        role="tab"
                        aria-selected={tab === l}
                        onClick={() => setTab(l)}
                        className={`rounded-md px-3 py-1.5 text-sm ${tab === l ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                    >
                        {JUDUL[l]}
                    </button>
                ))}
                <span className="ml-auto flex items-center gap-3 text-sm">
                    {versi.terkunci ? (
                        <span className="inline-flex items-center gap-1">
                            <Lock className="size-4" aria-hidden /> Terkunci (BR-02)
                        </span>
                    ) : isKolaboratif ? (
                        <span className="text-muted-foreground inline-flex items-center gap-1 text-xs">
                            <Lock className="size-3.5" aria-hidden /> Konsolidasi Otomatis (Read-Only)
                        </span>
                    ) : !dapat_tulis ? (
                        <span>Hanya lihat</span>
                    ) : (
                        <>
                            {berubah.length > 0 && <span>{berubah.length} sel belum disimpan</span>}
                            <button
                                onClick={simpan}
                                disabled={berubah.length === 0 || menyimpan}
                                className="bg-primary text-primary-foreground rounded-md px-3 py-1.5 disabled:opacity-50"
                            >
                                {menyimpan ? 'Menyimpan…' : 'Simpan'}
                            </button>
                        </>
                    )}
                </span>
            </div>

            {tab === 'KEGIATAN' && isKolaboratif ? (
                <TabKonsolidasiKegiatan kelompokBidang={kolaboratif.kegiatan_gabungan} />
            ) : (
            <div className="max-h-[70vh] overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-muted sticky top-0 z-10 text-left">
                        <tr>
                            <th className="px-3 py-2">Kode</th>
                            <th className="px-3 py-2">Uraian</th>
                            <th className="px-3 py-2 text-right">Persen</th>
                            <th className="px-3 py-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        {barisTab.map((p) => {
                            const judulGrup = p.grup === 'L2/LN' && grupSebelumnya !== 'L2/LN';
                            grupSebelumnya = p.grup;
                            if (tersembunyi(p)) return null;
                            const nilai = draft[p.id] ?? String(p.nilai);
                            const induknya = punyaAnak.has(p.id);

                            return (
                                <FragmentBaris key={p.id}>
                                    {judulGrup && (
                                        <tr className="bg-muted/50">
                                            <td colSpan={4} className="px-3 py-1.5 font-semibold">
                                                Pencatatan luar neraca
                                            </td>
                                        </tr>
                                    )}
                                    <tr className={`border-t ${induknya ? 'font-semibold' : ''}`}>
                                        <td className="px-3 py-1.5 whitespace-nowrap">{p.kode}</td>
                                        <td className="px-3 py-1.5" style={{ paddingLeft: `${0.75 + kedalaman(p) * 1.25}rem` }}>
                                            {induknya && (
                                                <button
                                                    aria-label={tutup.has(p.id) ? 'Buka' : 'Tutup'}
                                                    className="mr-1 align-middle"
                                                    onClick={() =>
                                                        setTutup((s) => {
                                                            const n = new Set(s);
                                                            if (n.has(p.id)) n.delete(p.id);
                                                            else n.add(p.id);
                                                            return n;
                                                        })
                                                    }
                                                >
                                                    {tutup.has(p.id) ? <ChevronRight className="size-4" /> : <ChevronDown className="size-4" />}
                                                </button>
                                            )}
                                            {p.nama}
                                        </td>
                                        <td className="px-3 py-1.5 text-right tabular-nums">
                                            {p.tipe_nilai === 'rupiah' ? persenTampil(p.persen) : ''}
                                        </td>
                                        <td className="px-3 py-1.5 text-right tabular-nums">
                                            {p.editable ? (
                                                <input
                                                    ref={(el) => {
                                                        refs.current[p.id] = el;
                                                    }}
                                                    inputMode="numeric"
                                                    aria-label={`Jumlah ${p.kode} ${p.nama}`}
                                                    className="w-44 rounded-md border px-2 py-1 text-right tabular-nums"
                                                    value={nilai === '' ? '' : formatAngka(Number(nilai))}
                                                    onChange={(e) => setDraft((d) => ({ ...d, [p.id]: e.target.value.replace(/[^\d]/g, '') }))}
                                                    onPaste={(e) => {
                                                        if (tempel(p.id, e.clipboardData.getData('text'))) e.preventDefault();
                                                    }}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter' || e.key === 'ArrowDown') {
                                                            e.preventDefault();
                                                            fokusKe(p.id, 1);
                                                        } else if (e.key === 'ArrowUp') {
                                                            e.preventDefault();
                                                            fokusKe(p.id, -1);
                                                        }
                                                    }}
                                                />
                                            ) : (
                                                satuan(p, p.nilai)
                                            )}
                                            {p.editable && p.tipe_nilai !== 'rupiah' && (
                                                <span className="text-muted-foreground ml-1 text-xs">{p.tipe_nilai}</span>
                                            )}
                                        </td>
                                    </tr>
                                </FragmentBaris>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            )}
        </section>
    );
}

function FragmentBaris({ children }: { children: React.ReactNode }) {
    return <>{children}</>;
}

function DaftarValidasi({ validasi, sumber }: { validasi: Validasi[]; sumber: string }) {
    const lulus = validasi.filter((v) => v.lulus).length;

    return (
        <section className="rounded-xl border p-4">
            <h2 className="font-semibold">Validasi silang</h2>
            <p className="text-muted-foreground mb-3 text-sm">
                {lulus} dari {validasi.length} lulus.
                {sumber === 'impor' && ' Versi hasil impor: validasi gagal hanya menjadi peringatan saat pengajuan.'}
            </p>
            <ul className="grid gap-3">
                {validasi.map((v) => (
                    <li key={v.id} className="text-sm">
                        <div className="flex items-start gap-2">
                            {v.lulus ? (
                                <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden />
                            ) : (
                                <XCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
                            )}
                            <div>
                                <p>
                                    <strong>{v.id}</strong> · {v.lulus ? 'Lulus' : 'Gagal'} · {v.nama}
                                </p>
                                <RincianVal v={v} />
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}

function RincianVal({ v }: { v: Validasi }) {
    if (v.id === 'VAL-6') {
        const r = v.rincian as Record<string, RincianJenis>;
        return (
            <table className="mt-1 w-full text-xs">
                <thead className="text-left">
                    <tr>
                        <th>Jenis dana</th>
                        <th className="text-right">Penerimaan</th>
                        <th className="text-right">Amil</th>
                        <th className="text-right">Rasio</th>
                        <th className="text-right">Batas</th>
                        <th>Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    {Object.entries(r).map(([jenis, x]) => (
                        <tr key={jenis} className="tabular-nums">
                            <td>{JENIS_DANA[jenis] ?? jenis}</td>
                            <td className="text-right">{formatRupiah(x.penerimaan)}</td>
                            <td className="text-right">{formatRupiah(x.amil)}</td>
                            <td className="text-right">{persenTampil(x.rasio_persen)}</td>
                            <td className="text-right">{x.batas_bp === null ? '—' : persenTampil(x.batas_bp / 100)}</td>
                            <td>{x.keterangan ?? (x.lulus ? 'Lulus' : 'Gagal')}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        );
    }
    if (v.id === 'VAL-5') {
        const r = v.rincian as Record<string, Record<string, number | boolean>>;
        return (
            <p className="text-muted-foreground tabular-nums">
                Mustahik {formatAngka(r.mustahik.total as number)} / jumlah bidang {formatAngka(r.mustahik.jumlah_bidang as number)} · Orang{' '}
                {formatAngka(r.orang.total as number)} / jumlah rincian {formatAngka(r.orang.jumlah_rincian as number)}
            </p>
        );
    }
    return (
        <p className="text-muted-foreground tabular-nums">
            {formatRupiah(v.kiri)} vs {formatRupiah(v.kanan)} · selisih {v.selisih > 0 ? '+' : ''}
            {formatRupiah(v.selisih)}
        </p>
    );
}

function PanelStatus({ versi, siapAjukanKolaboratif = true }: { versi: Versi; siapAjukanKolaboratif?: boolean }) {
    const [nomor, setNomor] = useState('');
    const [tanggal, setTanggal] = useState('');
    const [alasan, setAlasan] = useState('');
    const kirim = (nama: string, data: Record<string, unknown> = {}, konfirmasi?: string) => {
        if (konfirmasi && !window.confirm(konfirmasi)) return;
        router.post(route(nama, versi.id), data as Record<string, string>, { preserveScroll: true });
    };
    const tombol = 'rounded-md border px-3 py-1.5 text-sm hover:bg-muted';

    return (
        <section className="rounded-xl border p-4">
            <h2 className="mb-3 font-semibold">Status versi</h2>
            <div className="flex flex-wrap items-start gap-2">
                {versi.status === 'draft' && (
                    <>
                        <button
                            className="bg-primary text-primary-foreground rounded-md px-3 py-1.5 text-sm disabled:opacity-50"
                            disabled={!siapAjukanKolaboratif}
                            onClick={() => kirim('pkp.rencana.ajukan')}
                        >
                            Ajukan
                        </button>
                        <button className={tombol} onClick={() => kirim('pkp.rencana.arsip', {}, 'Arsipkan versi ini?')}>
                            Arsipkan
                        </button>
                        {!siapAjukanKolaboratif && (
                            <p className="text-muted-foreground w-full text-xs">
                                Menunggu ketiga bidang (Pengumpulan, Pendis/Pendag, SAU) diverifikasi oleh Kepala Bagian PKP.
                            </p>
                        )}
                    </>
                )}
                {versi.status === 'diajukan' && (
                    <div className="grid w-full gap-3">
                        <div className="flex flex-wrap items-end gap-2">
                            <input
                                className="rounded-md border px-2 py-1.5 text-sm"
                                placeholder="Alasan (opsional)"
                                value={alasan}
                                onChange={(e) => setAlasan(e.target.value)}
                            />
                            <button className={tombol} onClick={() => kirim('pkp.rencana.kembalikan', { alasan })}>
                                Kembalikan ke Draft
                            </button>
                        </div>
                        <div className="flex flex-wrap items-end gap-2">
                            <input
                                aria-label="Nomor surat"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                placeholder="Nomor surat"
                                value={nomor}
                                onChange={(e) => setNomor(e.target.value)}
                            />
                            <input
                                aria-label="Tanggal surat"
                                type="date"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={tanggal}
                                onChange={(e) => setTanggal(e.target.value)}
                            />
                            <button
                                className="bg-primary text-primary-foreground rounded-md px-3 py-1.5 text-sm disabled:opacity-50"
                                disabled={!nomor || !tanggal}
                                onClick={() => kirim('pkp.rencana.sahkan', { nomor_surat: nomor, tanggal_surat: tanggal })}
                            >
                                Sahkan
                            </button>
                        </div>
                    </div>
                )}
                {versi.status === 'disahkan' && (
                    <button className={tombol} onClick={() => router.post(route('pkp.rencana.buat'), { tahun: versi.tahun, jenis: 'perubahan' })}>
                        Buat versi Perubahan
                    </button>
                )}
                {versi.status === 'digantikan' && !versi.arsip && (
                    <button className={tombol} onClick={() => kirim('pkp.rencana.arsip', {}, 'Arsipkan versi ini?')}>
                        Arsipkan
                    </button>
                )}
            </div>
        </section>
    );
}

type CatatanForm = Record<'zakat' | 'infak' | 'csr' | 'dskl', string> &
    Record<'target_orang' | 'target_pengentasan_ekstrem' | 'target_pengentasan_garis' | 'catatan', string>;

function FormCatatan({
    versi,
    catatan,
    bisaUbah,
    isKolaboratif = false,
}: {
    versi: Versi;
    catatan: Catatan | null;
    bisaUbah: boolean;
    isKolaboratif?: boolean;
}) {
    const bisaUbahAngka = bisaUbah && !isKolaboratif;
    const awal: CatatanForm = {
        zakat: bpKePersen(catatan?.batas_amil_zakat_bp ?? null),
        infak: bpKePersen(catatan?.batas_amil_infak_bp ?? null),
        csr: bpKePersen(catatan?.batas_amil_csr_bp ?? null),
        dskl: bpKePersen(catatan?.batas_amil_dskl_bp ?? null),
        target_orang: String(catatan?.target_orang ?? ''),
        target_pengentasan_ekstrem: String(catatan?.target_pengentasan_ekstrem ?? ''),
        target_pengentasan_garis: String(catatan?.target_pengentasan_garis ?? ''),
        catatan: catatan?.catatan ?? '',
    };
    const [f, setF] = useState<CatatanForm>(awal);
    const set = (k: keyof CatatanForm, v: string) => setF((s) => ({ ...s, [k]: v }));
    const angka = (t: string) => (t.trim() === '' ? null : Number(t.replace(/\D/g, '')));

    const simpan = () =>
        router.post(
            route('pkp.rencana.catatan', versi.id),
            {
                batas_amil_zakat_bp: persenKeBp(f.zakat),
                batas_amil_infak_bp: persenKeBp(f.infak),
                batas_amil_csr_bp: persenKeBp(f.csr),
                batas_amil_dskl_bp: persenKeBp(f.dskl),
                target_orang: angka(f.target_orang),
                target_pengentasan_ekstrem: angka(f.target_pengentasan_ekstrem),
                target_pengentasan_garis: angka(f.target_pengentasan_garis),
                catatan: f.catatan || null,
            },
            { preserveScroll: true },
        );

    const kolom = 'rounded-md border px-2 py-1.5 text-sm disabled:bg-muted';

    return (
        <section className="rounded-xl border p-4">
            <h2 className="mb-3 font-semibold">Catatan pengesahan</h2>
            {isKolaboratif && (
                <p className="text-muted-foreground mb-3 text-xs">
                    Batas hak amil dan target mustahik disinkronkan otomatis dari aturan 22 baris dan rincian bidang.
                </p>
            )}
            <div className="grid gap-3 sm:grid-cols-2">
                {(['zakat', 'infak', 'csr', 'dskl'] as const).map((k) => (
                    <label key={k} className="grid gap-1 text-sm">
                        Batas hak amil {JENIS_DANA[k]} (%)
                        <input className={kolom} inputMode="decimal" disabled={!bisaUbahAngka} value={f[k]} onChange={(e) => set(k, e.target.value)} />
                    </label>
                ))}
                <label className="grid gap-1 text-sm">
                    Target jumlah orang
                    <input
                        className={kolom}
                        inputMode="numeric"
                        disabled={!bisaUbahAngka}
                        value={f.target_orang}
                        onChange={(e) => set('target_orang', e.target.value)}
                    />
                </label>
                <label className="grid gap-1 text-sm">
                    Pengentasan: garis kemiskinan ekstrem
                    <input
                        className={kolom}
                        inputMode="numeric"
                        disabled={!bisaUbahAngka}
                        value={f.target_pengentasan_ekstrem}
                        onChange={(e) => set('target_pengentasan_ekstrem', e.target.value)}
                    />
                </label>
                <label className="grid gap-1 text-sm">
                    Pengentasan: garis kemiskinan
                    <input
                        className={kolom}
                        inputMode="numeric"
                        disabled={!bisaUbahAngka}
                        value={f.target_pengentasan_garis}
                        onChange={(e) => set('target_pengentasan_garis', e.target.value)}
                    />
                </label>
                <label className="grid gap-1 text-sm sm:col-span-2">
                    Catatan
                    <textarea className={kolom} rows={3} disabled={!bisaUbah} value={f.catatan} onChange={(e) => set('catatan', e.target.value)} />
                </label>
            </div>
            {bisaUbah ? (
                <button className="bg-primary text-primary-foreground mt-3 rounded-md px-3 py-1.5 text-sm" onClick={simpan}>
                    Simpan catatan
                </button>
            ) : (
                <p className="text-muted-foreground mt-3 text-sm">Catatan hanya dapat diubah pada versi Draft.</p>
            )}
        </section>
    );
}

function PanelDokumen({ versi, dokumen, dapat_tulis }: { versi: Versi; dokumen: Dokumen[]; dapat_tulis: boolean }) {
    const [jenis, setJenis] = useState('surat_pengesahan');
    const [berkas, setBerkas] = useState<File | null>(null);
    const kirim = () =>
        router.post(
            route('pkp.rencana.dokumen', versi.id),
            { jenis, berkas },
            { forceFormData: true, preserveScroll: true, onSuccess: () => setBerkas(null) },
        );

    return (
        <section className="rounded-xl border p-4">
            <h2 className="mb-3 font-semibold">Dokumen pengesahan</h2>
            {dapat_tulis && (
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <select
                        className="rounded-md border px-2 py-1.5 text-sm"
                        value={jenis}
                        onChange={(e) => setJenis(e.target.value)}
                        aria-label="Jenis dokumen"
                    >
                        {Object.entries(JENIS_DOKUMEN).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </select>
                    <input
                        type="file"
                        accept="application/pdf"
                        aria-label="Berkas PDF"
                        className="text-sm"
                        onChange={(e) => setBerkas(e.target.files?.[0] ?? null)}
                    />
                    <button
                        className="bg-primary text-primary-foreground rounded-md px-3 py-1.5 text-sm disabled:opacity-50"
                        disabled={!berkas}
                        onClick={kirim}
                    >
                        Unggah PDF
                    </button>
                </div>
            )}
            {dokumen.length === 0 ? (
                <p className="text-muted-foreground text-sm">Belum ada dokumen.</p>
            ) : (
                <ul className="grid gap-1 text-sm">
                    {dokumen.map((d) => (
                        <li key={d.id}>
                            <a className="underline" href={route('pkp.rencana.dokumen.unduh', d.id)}>
                                {d.nama_berkas}
                            </a>{' '}
                            · {JENIS_DOKUMEN[d.jenis] ?? d.jenis} · {Math.max(1, Math.round(d.ukuran / 1024))} KB
                            {d.diarsipkan && <span className="text-muted-foreground"> · diarsipkan (digantikan)</span>}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function Riwayat({ riwayat, aktifId }: { riwayat: Riwayat[]; aktifId: number }) {
    return (
        <section className="overflow-x-auto rounded-xl border">
            <h2 className="p-3 font-semibold">Riwayat versi tahun ini</h2>
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-3 py-2">Versi</th>
                        <th className="px-3 py-2">Status</th>
                        <th className="px-3 py-2">Sumber</th>
                        <th className="px-3 py-2">Nomor surat</th>
                        <th className="px-3 py-2">Disahkan</th>
                    </tr>
                </thead>
                <tbody>
                    {riwayat.map((r) => (
                        <tr key={r.id} className={`border-t ${r.id === aktifId ? 'bg-muted/30' : ''}`}>
                            <td className="px-3 py-2">
                                <Link className="underline" href={route('pkp.rencana.show', r.id)}>
                                    {r.label}
                                </Link>
                                {r.arsip && ' (arsip)'}
                            </td>
                            <td className="px-3 py-2">
                                <Lencana status={r.status} />
                            </td>
                            <td className="px-3 py-2">{r.sumber === 'impor' ? 'Impor' : 'Manual'}</td>
                            <td className="px-3 py-2">{r.nomor_surat ?? '—'}</td>
                            <td className="px-3 py-2">{r.disahkan_pada ? new Date(r.disahkan_pada).toLocaleDateString('id-ID') : '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>
    );
}
