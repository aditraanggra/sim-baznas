import { EmptyState, InputRupiah, PageHeader, StatusBadge, Timeline } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Ban, CheckCircle2, FileDown, HandHeart, Printer, Send } from 'lucide-react';
import * as React from 'react';

interface Props {
    permohonan: {
        id: number;
        nomor: string;
        kode_tracking: string;
        tanggal: string | null;
        mustahik_id: number;
        mustahik: string | null;
        mustahik_kode: string | null;
        mustahik_wilayah: string;
        program: string | null;
        sub_bidang: string;
        jenis_dana: string | null;
        asnaf: string | null;
        nominal_diajukan: number;
        nominal_disetujui: number | null;
        status_kode: string;
        status_publik: string;
        langkah_sekarang: number;
        petugas_lapangan_id: number | null;
        petugas_lapangan: string | null;
        kesiapan_keuangan: string;
        keuangan_diproses_oleh: string | null;
        keuangan_siap_at: string | null;
        catatan_asesmen: string | null;
        uraian: string | null;
        alasan_tolak: string | null;
    };
    disposisi: {
        id: number;
        nomor: string;
        langkah: number;
        dari: string;
        ke: string;
        status: string;
        nominal_usulan: number | null;
        instruksi: string | null;
        catatan: string | null;
        waktu: string | null;
    }[];
    status_log: {
        id: number;
        status_kode: string;
        judul_publik: string;
        keterangan_internal: string | null;
        petugas: string;
        waktu: string | null;
    }[];
    dokumen: { id: number; jenis: string | null; nama_file: string | null; url_unduh: string | null }[];
    staf_list: { id: number; name: string }[];
    penyaluran: { id: number; nomor: string | null; status: string } | null;
    sumber_dana_list: { id: number; kode: string; nama: string }[];
    metode_bayar_list: { id: number; kode: string; nama: string; butuh_rekening: boolean }[];
    rekening_list: { id: number; bank: string; no_rekening: string; atas_nama: string }[];
    peran_user: string[];
}

const DAFTAR_LANGKAH_K8 = [
    '1. Pelayanan Mustahik',
    '2. Disposisi Ketua',
    '3. Asesmen Kabag',
    '4. Ajuan Waka II',
    '5. ACC Ketua',
    '6. Staf & Keuangan',
    '7. Diterima Mustahik',
];

export default function PermohonanShow({
    permohonan,
    disposisi,
    status_log,
    dokumen,
    staf_list,
    penyaluran,
    sumber_dana_list,
    metode_bayar_list,
    rekening_list,
    peran_user,
}: Props) {
    const [dialogTolak, setDialogTolak] = React.useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pendistribusian', href: '/distribusi' },
        { title: 'Permohonan Bantuan', href: '/distribusi/permohonan' },
        { title: permohonan.nomor, href: `/distribusi/permohonan/${permohonan.id}` },
    ];

    const formLangkah = useForm({
        aksi: 'teruskan',
        nominal_usulan: permohonan.nominal_diajukan,
        nominal_disetujui: permohonan.nominal_disetujui ?? permohonan.nominal_diajukan,
        petugas_lapangan_id: permohonan.petugas_lapangan_id ? String(permohonan.petugas_lapangan_id) : '',
        catatan_asesmen: permohonan.catatan_asesmen ?? '',
        instruksi: '',
        catatan: '',
        sumber_dana_id: sumber_dana_list[0] ? String(sumber_dana_list[0].id) : '',
        metode_bayar_id: String((metode_bayar_list.find((m) => m.kode === 'TUNAI') ?? metode_bayar_list[0])?.id ?? ''),
        rekening_id: '',
    });

    const metodeTerpilih = metode_bayar_list.find((m) => String(m.id) === formLangkah.data.metode_bayar_id);
    const daftarGalat = Object.values(formLangkah.errors as Record<string, string>).filter(Boolean);
    const nominalUsulanLangkah5 = React.useMemo(
        () => [...disposisi].reverse().find((d) => d.langkah === 5 && d.nominal_usulan !== null)?.nominal_usulan ?? null,
        [disposisi],
    );

    const setDataLangkah = formLangkah.setData;
    React.useEffect(() => {
        setDataLangkah((prev) => ({
            ...prev,
            nominal_usulan: nominalUsulanLangkah5 ?? permohonan.nominal_diajukan,
            nominal_disetujui: permohonan.nominal_disetujui ?? nominalUsulanLangkah5 ?? permohonan.nominal_diajukan,
            petugas_lapangan_id: permohonan.petugas_lapangan_id ? String(permohonan.petugas_lapangan_id) : '',
            catatan_asesmen: permohonan.catatan_asesmen ?? '',
            instruksi: '',
            catatan: '',
        }));
    }, [
        permohonan.id,
        permohonan.status_kode,
        permohonan.nominal_diajukan,
        permohonan.nominal_disetujui,
        permohonan.petugas_lapangan_id,
        permohonan.catatan_asesmen,
        nominalUsulanLangkah5,
        setDataLangkah,
    ]);
    const formKeuangan = useForm({ catatan: '' });
    const formTolak = useForm({ alasan_tolak: '' });

    const punyaPeran = (daftar: string[]) => daftar.some((p) => peran_user.includes(p));
    const selesai = permohonan.status_kode === 'diterima_mustahik' || permohonan.status_kode === 'ditolak';

    const kirimLangkah = (e: React.FormEvent, aksi = 'teruskan') => {
        e.preventDefault();
        formLangkah.transform((data) => ({ ...data, aksi }));
        formLangkah.post(route('distribusi.permohonan.proses-langkah', permohonan.id), { preserveScroll: true });
    };

    const konfirmasiKeuangan = (e: React.FormEvent) => {
        e.preventDefault();
        formKeuangan.post(route('distribusi.permohonan.siap-keuangan', permohonan.id), { preserveScroll: true });
    };

    const kirimTolak = (e: React.FormEvent) => {
        e.preventDefault();
        formTolak.post(route('distribusi.permohonan.tolak', permohonan.id), {
            preserveScroll: true,
            onSuccess: () => setDialogTolak(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Permohonan ${permohonan.nomor}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={permohonan.nomor}
                    description={`Kode Lacak Publik: ${permohonan.kode_tracking} · Diajukan ${permohonan.tanggal ?? '-'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <a href={route('distribusi.permohonan.lembar-disposisi', permohonan.id)} target="_blank" rel="noreferrer">
                                    <Printer className="h-4 w-4" aria-hidden="true" />
                                    <span>Lembar Disposisi</span>
                                </a>
                            </Button>
                            {!selesai && permohonan.status_kode !== 'diajukan' && (
                                <Button asChild className="h-9 gap-1.5">
                                    <Link href={route('distribusi.penyaluran.create', { permohonan_id: permohonan.id })}>
                                        <HandHeart className="h-4 w-4" aria-hidden="true" />
                                        <span>Buat Penyaluran</span>
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="bg-card border-border rounded-2xl border p-4">
                    <p className="text-muted-foreground mb-3 text-xs font-medium">Progres Disposisi 7 Langkah (K8)</p>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                        {DAFTAR_LANGKAH_K8.map((label, idx) => {
                            const no = idx + 1;
                            const aktif = permohonan.langkah_sekarang === no && permohonan.status_kode !== 'ditolak';
                            const lewat = permohonan.langkah_sekarang > no || permohonan.status_kode === 'diterima_mustahik';
                            return (
                                <div
                                    key={label}
                                    className={`rounded-xl border px-3 py-2 text-xs font-medium ${
                                        aktif
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : lewat
                                              ? 'border-success/40 bg-success/10 text-foreground'
                                              : 'border-border text-muted-foreground'
                                    }`}
                                >
                                    {label}
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <section className="bg-card border-border space-y-4 rounded-2xl border p-5 lg:col-span-2">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="text-foreground text-base font-semibold">Ringkasan Permohonan</h2>
                            <StatusBadge
                                status={permohonan.status_kode}
                                variant={permohonan.status_kode === 'diterima_mustahik' ? 'success' : permohonan.status_kode === 'ditolak' ? 'ditolak' : 'aktif'}
                                label={`Publik: ${permohonan.status_publik}`}
                            />
                        </div>

                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground text-xs">Mustahik</dt>
                                <dd className="text-foreground mt-0.5 font-medium">
                                    <Link href={route('distribusi.mustahik.show', permohonan.mustahik_id)} className="text-primary hover:underline">
                                        {permohonan.mustahik} ({permohonan.mustahik_kode})
                                    </Link>
                                </dd>
                                <dd className="text-muted-foreground text-xs">{permohonan.mustahik_wilayah}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Program & Dana</dt>
                                <dd className="text-foreground mt-0.5 font-medium">{permohonan.program}</dd>
                                <dd className="text-muted-foreground text-xs">
                                    {permohonan.jenis_dana ?? '-'} {permohonan.asnaf ? `· ${permohonan.asnaf}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Nominal Diajukan</dt>
                                <dd className="text-foreground mt-0.5 font-semibold tabular-nums">{formatRupiah(permohonan.nominal_diajukan)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Nominal ACC Ketua</dt>
                                <dd className="text-foreground mt-0.5 font-semibold tabular-nums">
                                    {permohonan.nominal_disetujui ? formatRupiah(permohonan.nominal_disetujui) : 'Belum ditetapkan'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Staf Lapangan Ditugaskan</dt>
                                <dd className="text-foreground mt-0.5">{permohonan.petugas_lapangan ?? 'Belum ditunjuk'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Kesiapan Dana Keuangan</dt>
                                <dd className="text-foreground mt-0.5 font-medium">
                                    {permohonan.kesiapan_keuangan === 'siap'
                                        ? `Siap (${permohonan.keuangan_diproses_oleh ?? '-'} · ${permohonan.keuangan_siap_at ?? ''})`
                                        : permohonan.kesiapan_keuangan === 'diminta'
                                          ? 'Diminta ke Bagian Keuangan'
                                          : 'Belum diminta'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Data Penyaluran</dt>
                                <dd className="text-foreground mt-0.5 font-medium">
                                    {penyaluran ? (
                                        <Link href={route('distribusi.penyaluran.index', { cari: penyaluran.nomor ?? '' })} className="text-primary hover:underline">
                                            {penyaluran.nomor ?? '(Draft)'} · {penyaluran.status}
                                        </Link>
                                    ) : (
                                        'Terbit otomatis saat bantuan diterima mustahik'
                                    )}
                                </dd>
                            </div>
                        </dl>

                        {permohonan.catatan_asesmen && (
                            <div className="bg-muted/40 rounded-xl p-3 text-xs">
                                <p className="text-foreground font-semibold">Hasil Asesmen:</p>
                                <p className="text-muted-foreground mt-0.5">{permohonan.catatan_asesmen}</p>
                            </div>
                        )}

                        {permohonan.alasan_tolak && (
                            <div className="border-destructive/40 bg-destructive/10 text-destructive rounded-xl border p-3 text-xs">
                                <p className="font-semibold">Alasan Penolakan:</p>
                                <p className="mt-0.5">{permohonan.alasan_tolak}</p>
                            </div>
                        )}
                    </section>

                    <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="text-foreground text-base font-semibold">Tindak Lanjut Tahap</h2>
                            {!selesai && punyaPeran(['ketua', 'waka_2', 'kabag_pendis_pendag', 'pelayanan_mustahik', 'admin']) && (
                                <Button type="button" size="sm" variant="destructive" className="h-8 gap-1" onClick={() => setDialogTolak(true)}>
                                    <Ban className="h-3.5 w-3.5" aria-hidden="true" />
                                    <span>Tolak</span>
                                </Button>
                            )}
                        </div>

                        {selesai ? (
                            <p className="text-muted-foreground text-sm">Alur disposisi permohonan ini telah berakhir ({permohonan.status_kode}).</p>
                        ) : (
                            <div className="space-y-4">
                                <form onSubmit={(e) => kirimLangkah(e, 'teruskan')} className="space-y-3">
                                    {permohonan.status_kode === 'asesmen_kabag' && (
                                        <div className="space-y-1.5">
                                            <Label htmlFor="l-asesmen">Catatan Hasil Asesmen</Label>
                                            <Input
                                                id="l-asesmen"
                                                value={formLangkah.data.catatan_asesmen}
                                                onChange={(e) => formLangkah.setData('catatan_asesmen', e.target.value)}
                                                placeholder="Ringkasan kelayakan lapangan..."
                                                required
                                            />
                                        </div>
                                    )}

                                    {(permohonan.status_kode === 'disposisi_ketua' ||
                                        permohonan.status_kode === 'asesmen_kabag' ||
                                        permohonan.status_kode === 'acc_ketua') && (
                                        <div className="space-y-1.5">
                                            <Label htmlFor="l-staf">Tugaskan Staf Pendis / Pendag</Label>
                                            <Select
                                                value={formLangkah.data.petugas_lapangan_id}
                                                onValueChange={(v) => formLangkah.setData('petugas_lapangan_id', v)}
                                            >
                                                <SelectTrigger id="l-staf">
                                                    <SelectValue placeholder="Pilih staf (opsional)" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {staf_list.map((s) => (
                                                        <SelectItem key={s.id} value={String(s.id)}>
                                                            {s.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}

                                    {permohonan.status_kode === 'ajuan_waka_2' && (
                                        <div className="space-y-1.5">
                                            <Label htmlFor="l-usulan">Ajuan Besaran Bantuan (Waka II)</Label>
                                            <InputRupiah
                                                id="l-usulan"
                                                value={formLangkah.data.nominal_usulan}
                                                onChange={(v) => formLangkah.setData('nominal_usulan', v)}
                                            />
                                        </div>
                                    )}

                                    {permohonan.status_kode === 'acc_ketua' && (
                                        <div className="space-y-1.5">
                                            <Label htmlFor="l-acc">Nominal ACC Ketua</Label>
                                            <InputRupiah
                                                id="l-acc"
                                                value={formLangkah.data.nominal_disetujui}
                                                onChange={(v) => formLangkah.setData('nominal_disetujui', v)}
                                            />
                                        </div>
                                    )}

                                    <div className="space-y-1.5">
                                        <Label htmlFor="l-catatan">Catatan / Instruksi</Label>
                                        <Input
                                            id="l-catatan"
                                            value={formLangkah.data.instruksi}
                                            onChange={(e) => {
                                                formLangkah.setData('instruksi', e.target.value);
                                                formLangkah.setData('catatan', e.target.value);
                                            }}
                                            placeholder="Catatan untuk tahap berikutnya..."
                                        />
                                    </div>

                                    {permohonan.status_kode === 'minta_keuangan' && permohonan.kesiapan_keuangan === 'siap' && (
                                        <div className="border-border grid gap-3 rounded-xl border p-3">
                                            <p className="text-foreground text-xs font-semibold">Pencatatan Penyaluran saat Serah Terima</p>
                                            <div className="space-y-1.5">
                                                <Label htmlFor="l-sumber">Sumber Dana</Label>
                                                <Select
                                                    value={formLangkah.data.sumber_dana_id}
                                                    onValueChange={(v) => formLangkah.setData('sumber_dana_id', v)}
                                                >
                                                    <SelectTrigger id="l-sumber">
                                                        <SelectValue placeholder="Pilih sumber dana" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {sumber_dana_list.map((s) => (
                                                            <SelectItem key={s.id} value={String(s.id)}>
                                                                {s.nama}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="space-y-1.5">
                                                <Label htmlFor="l-metode">Metode Pembayaran</Label>
                                                <Select
                                                    value={formLangkah.data.metode_bayar_id}
                                                    onValueChange={(v) => formLangkah.setData('metode_bayar_id', v)}
                                                >
                                                    <SelectTrigger id="l-metode">
                                                        <SelectValue placeholder="Pilih metode" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {metode_bayar_list.map((m) => (
                                                            <SelectItem key={m.id} value={String(m.id)}>
                                                                {m.nama}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            {metodeTerpilih?.butuh_rekening && (
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="l-rekening">Rekening Bank</Label>
                                                    <Select value={formLangkah.data.rekening_id} onValueChange={(v) => formLangkah.setData('rekening_id', v)}>
                                                        <SelectTrigger id="l-rekening">
                                                            <SelectValue placeholder="Pilih rekening" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {rekening_list.map((r) => (
                                                                <SelectItem key={r.id} value={String(r.id)}>
                                                                    {r.bank} · {r.no_rekening}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {daftarGalat.length > 0 && (
                                        <div role="alert" className="border-destructive/40 bg-destructive/10 text-destructive rounded-xl border p-3 text-xs">
                                            <p className="font-semibold">Langkah belum dapat diproses:</p>
                                            <ul className="mt-1 list-disc pl-4">
                                                {daftarGalat.map((pesan) => (
                                                    <li key={pesan}>{pesan}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    <Button type="submit" className="w-full gap-1.5" disabled={formLangkah.processing}>
                                        <Send className="h-4 w-4" aria-hidden="true" />
                                        <span>
                                            {permohonan.status_kode === 'diajukan' && 'Teruskan ke Ketua'}
                                            {permohonan.status_kode === 'disposisi_ketua' && 'Disposisi ke Kabag'}
                                            {permohonan.status_kode === 'asesmen_kabag' && 'Kirim Asesmen ke Waka II'}
                                            {permohonan.status_kode === 'ajuan_waka_2' && 'Kirim Ajuan ke Ketua'}
                                            {permohonan.status_kode === 'acc_ketua' && 'ACC & Minta Dana Keuangan'}
                                            {permohonan.status_kode === 'minta_keuangan' && 'Tandai Diterima Mustahik'}
                                        </span>
                                    </Button>
                                </form>

                                {permohonan.status_kode === 'minta_keuangan' &&
                                    permohonan.kesiapan_keuangan !== 'siap' &&
                                    punyaPeran(['keuangan', 'admin']) && (
                                        <form onSubmit={konfirmasiKeuangan} className="border-border space-y-2 border-t pt-3">
                                            <Label htmlFor="k-catatan">Konfirmasi Kesiapan Dana (Keuangan)</Label>
                                            <Input
                                                id="k-catatan"
                                                value={formKeuangan.data.catatan}
                                                onChange={(e) => formKeuangan.setData('catatan', e.target.value)}
                                                placeholder="Kas / rekening siap dicairkan..."
                                            />
                                            <Button type="submit" variant="secondary" className="w-full gap-1.5" disabled={formKeuangan.processing}>
                                                <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                                <span>Tandai Dana Siap</span>
                                            </Button>
                                        </form>
                                    )}
                            </div>
                        )}
                    </section>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground mb-4 text-base font-semibold">Linimasa Status</h2>
                        <Timeline
                            steps={status_log.map((l) => ({
                                id: l.id,
                                status: `${l.judul_publik} (${l.status_kode})`,
                                waktu: l.waktu ?? '-',
                                pelaku: l.petugas,
                                catatan: l.keterangan_internal,
                                selesai: true,
                            }))}
                        />
                    </section>

                    <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Riwayat Disposisi & Dokumen</h2>
                        {disposisi.length === 0 ? (
                            <EmptyState title="Belum Ada Disposisi" description="Catatan disposisi akan tampil di sini." />
                        ) : (
                            <div className="divide-border divide-y text-sm">
                                {disposisi.map((d) => (
                                    <div key={d.id} className="py-2.5">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="font-medium">
                                                Langkah {d.langkah}: {d.dari} ke {d.ke}
                                            </span>
                                            <span className="text-muted-foreground text-xs">{d.waktu}</span>
                                        </div>
                                        {d.instruksi && <p className="text-muted-foreground mt-0.5 text-xs">{d.instruksi}</p>}
                                    </div>
                                ))}
                            </div>
                        )}

                        {dokumen.length > 0 && (
                            <div className="border-border space-y-2 border-t pt-3">
                                <p className="text-foreground text-xs font-semibold">Dokumen Lampiran Permohonan</p>
                                {dokumen.map((dok) => (
                                    <div key={dok.id} className="flex items-center justify-between text-xs">
                                        <span>
                                            {dok.jenis}: {dok.nama_file}
                                        </span>
                                        {dok.url_unduh && (
                                            <a href={dok.url_unduh} className="text-primary inline-flex items-center gap-1 hover:underline">
                                                <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                <span>Unduh</span>
                                            </a>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>

                <Dialog open={dialogTolak} onOpenChange={setDialogTolak}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tolak Permohonan Bantuan</DialogTitle>
                            <DialogDescription>Masukkan alasan penolakan minimal 10 karakter.</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={kirimTolak} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="alasan-tolak">Alasan Penolakan</Label>
                                <Input
                                    id="alasan-tolak"
                                    value={formTolak.data.alasan_tolak}
                                    onChange={(e) => formTolak.setData('alasan_tolak', e.target.value)}
                                    required
                                />
                                {formTolak.errors.alasan_tolak && <p className="text-destructive text-xs">{formTolak.errors.alasan_tolak}</p>}
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setDialogTolak(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="destructive" disabled={formTolak.processing}>
                                    Konfirmasi Penolakan
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
