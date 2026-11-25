import { EmptyState, PageHeader, StatusBadge, Timeline, UnggahBerkas } from '@/components/app';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, ExternalLink, FileDown, Lock, Printer, Reply, Send } from 'lucide-react';
import * as React from 'react';

interface DisposisiRow {
    id: number;
    parent_disposisi_id: number | null;
    tahap: string;
    urutan: number;
    dari_user_id: number;
    dari_nama: string;
    kepada_user_id: number;
    kepada_nama: string;
    kepada_peran: string | null;
    instruksi: string;
    tenggat: string | null;
    keputusan: string | null;
    status: 'menunggu' | 'diteruskan' | 'selesai' | string;
    catatan_tindak_lanjut: string | null;
    waktu: string | null;
    selesai_at: string | null;
    bukti_nama: string | null;
    bukti_url: string | null;
}

interface Props {
    surat: {
        id: number;
        uuid: string;
        nomor_agenda: string;
        nomor_surat: string;
        tanggal_surat: string | null;
        tanggal_terima: string | null;
        pengirim: string;
        perihal: string;
        ringkasan: string | null;
        sifat: string;
        status_kode: string;
        status_label: string;
        pencatat: string;
        selesai_at: string | null;
        catatan_penutupan: string | null;
        berkas_nama: string | null;
        berkas_url: string | null;
        berkas_unduh_url: string | null;
    };
    disposisi: DisposisiRow[];
    status_log: {
        id: number;
        status_kode: string;
        status_label: string;
        pelaku: string;
        waktu: string | null;
        catatan: string | null;
    }[];
    surat_balasan: {
        id: number;
        nomor: string;
        tanggal: string | null;
        perihal: string;
        status_kode: string;
    }[];
    waka_list: { id: number; name: string; jabatan: string }[];
    internal_list: { id: number; name: string; peran: string }[];
    disposisi_aktif_saya: { id: number; tahap: string; instruksi: string } | null;
    akses: {
        boleh_kelola: boolean;
        boleh_disposisi_ketua: boolean;
        boleh_tutup: boolean;
        adalah_waka: boolean;
    };
}

export default function SuratMasukShow({
    surat,
    disposisi,
    status_log,
    surat_balasan,
    waka_list,
    internal_list,
    disposisi_aktif_saya,
    akses,
}: Props) {
    const [tab, setTab] = React.useState('ringkasan');
    const [dialogTutup, setDialogTutup] = React.useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SDM, Administrasi & Umum', href: '/umum' },
        { title: 'Surat Masuk', href: '/umum/surat/masuk' },
        { title: surat.nomor_agenda, href: `/umum/surat/masuk/${surat.id}` },
    ];

    const formTeruskan = useForm({ catatan: '' });

    const formKetua = useForm<{
        kepada_user_ids: number[];
        instruksi: string;
        tenggat: string;
        keputusan: string;
    }>({
        kepada_user_ids: [],
        instruksi: '',
        tenggat: '',
        keputusan: '',
    });

    const formTerusWaka = useForm<{
        parent_disposisi_id: number | null;
        kepada_user_ids: number[];
        instruksi: string;
        tenggat: string;
    }>({
        parent_disposisi_id: disposisi_aktif_saya?.id ?? null,
        kepada_user_ids: [],
        instruksi: '',
        tenggat: '',
    });

    const formSelesai = useForm<{
        catatan_tindak_lanjut: string;
        keputusan: string;
        berkas_bukti: File | null;
    }>({
        catatan_tindak_lanjut: '',
        keputusan: '',
        berkas_bukti: null,
    });

    const formTutup = useForm({ catatan_penutupan: '' });

    React.useEffect(() => {
        formTerusWaka.setData('parent_disposisi_id', disposisi_aktif_saya?.id ?? null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [disposisi_aktif_saya?.id]);

    const toggleId = (list: number[], id: number): number[] =>
        list.includes(id) ? list.filter((x) => x !== id) : [...list, id];

    const selesai = surat.status_kode === 'selesai';
    const cabangUtama = disposisi.filter((d) => d.parent_disposisi_id === null);
    const anakByParent = React.useMemo(() => {
        const map = new Map<number, DisposisiRow[]>();
        for (const d of disposisi) {
            if (d.parent_disposisi_id !== null) {
                const list = map.get(d.parent_disposisi_id) ?? [];
                list.push(d);
                map.set(d.parent_disposisi_id, list);
            }
        }
        return map;
    }, [disposisi]);

    const langkahTimeline = status_log.map((l, idx) => ({
        id: l.id,
        status: l.status_label,
        waktu: l.waktu ?? '-',
        pelaku: l.pelaku,
        catatan: l.catatan,
        aktif: idx === status_log.length - 1 && !selesai,
        selesai: l.status_kode === 'selesai' || idx < status_log.length - 1,
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Surat Masuk ${surat.nomor_agenda}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={surat.nomor_agenda}
                    description={`Surat Asal: ${surat.nomor_surat} · Dari: ${surat.pengirim} · Diterima: ${surat.tanggal_terima ?? '-'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-10 gap-1.5 sm:h-9">
                                <a href={route('umum.surat.masuk.lembar-disposisi-pdf', surat.id)} target="_blank" rel="noreferrer">
                                    <Printer className="h-4 w-4" aria-hidden="true" />
                                    <span>Lembar Disposisi PDF</span>
                                </a>
                            </Button>
                            {akses.boleh_kelola && (
                                <Button asChild variant="outline" className="h-10 gap-1.5 sm:h-9">
                                    <Link href={route('umum.surat.keluar.create', { surat_masuk_id: surat.id })}>
                                        <Reply className="h-4 w-4" aria-hidden="true" />
                                        <span>Buat Surat Balasan</span>
                                    </Link>
                                </Button>
                            )}
                            {akses.boleh_tutup && !selesai && (
                                <Button type="button" variant="outline" className="h-10 sm:h-9" onClick={() => setDialogTutup(true)}>
                                    Tutup / Selesaikan
                                </Button>
                            )}
                        </>
                    }
                />

                {selesai && (
                    <div className="bg-success-soft border-success/25 text-success flex items-center gap-2.5 rounded-xl border px-4 py-3 text-sm">
                        <Lock className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>
                            Surat masuk telah selesai diproses pada {surat.selesai_at ?? '-'}.
                            {surat.catatan_penutupan ? ` Catatan: ${surat.catatan_penutupan}` : ''}
                        </span>
                    </div>
                )}

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <div className="bg-card border-border rounded-2xl border p-5">
                            <div className="mb-4 flex flex-wrap items-center gap-2">
                                <StatusBadge
                                    status={surat.status_kode}
                                    variant={selesai ? 'final' : surat.status_kode === 'diterima_arsip' ? 'info' : 'warning'}
                                    label={surat.status_label}
                                />
                                <StatusBadge
                                    status={surat.sifat}
                                    variant={surat.sifat === 'rahasia' ? 'danger' : surat.sifat === 'segera' || surat.sifat === 'penting' ? 'warning' : 'draft'}
                                    label={`Sifat: ${surat.sifat.toUpperCase()}`}
                                />
                            </div>

                            <Tabs value={tab} onValueChange={setTab}>
                                <TabsList>
                                    <TabsTrigger value="ringkasan">Ringkasan &amp; Berkas</TabsTrigger>
                                    <TabsTrigger value="disposisi">Pohon Disposisi ({disposisi.length})</TabsTrigger>
                                    <TabsTrigger value="riwayat">Riwayat Status ({status_log.length})</TabsTrigger>
                                </TabsList>

                                <TabsContent value="ringkasan" className="space-y-4 pt-4">
                                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt className="text-muted-foreground text-xs">Pengirim</dt>
                                            <dd className="text-foreground mt-0.5 font-medium">{surat.pengirim}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground text-xs">Nomor Surat Asal</dt>
                                            <dd className="text-foreground mt-0.5 font-medium">{surat.nomor_surat}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground text-xs">Tanggal Surat</dt>
                                            <dd className="text-foreground mt-0.5">{surat.tanggal_surat ?? '-'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground text-xs">Tanggal Diterima</dt>
                                            <dd className="text-foreground mt-0.5">{surat.tanggal_terima ?? '-'}</dd>
                                        </div>
                                        <div className="sm:col-span-2">
                                            <dt className="text-muted-foreground text-xs">Perihal</dt>
                                            <dd className="text-foreground mt-0.5 font-medium">{surat.perihal}</dd>
                                        </div>
                                        {surat.ringkasan && (
                                            <div className="sm:col-span-2">
                                                <dt className="text-muted-foreground text-xs">Ringkasan Isi</dt>
                                                <dd className="bg-surface-subtle text-foreground mt-1 rounded-lg p-3 whitespace-pre-line">{surat.ringkasan}</dd>
                                            </div>
                                        )}
                                    </dl>

                                    <div className="border-t pt-4">
                                        <h3 className="text-foreground text-sm font-semibold">Berkas Scan Surat</h3>
                                        {surat.berkas_url ? (
                                            <div className="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3">
                                                <span className="text-foreground text-sm font-medium">{surat.berkas_nama}</span>
                                                <div className="flex gap-2">
                                                    <Button asChild variant="outline" size="sm" className="h-8 gap-1 text-xs">
                                                        <a href={surat.berkas_url} target="_blank" rel="noopener noreferrer">
                                                            <ExternalLink className="h-3.5 w-3.5" aria-hidden="true" />
                                                            <span>Lihat</span>
                                                        </a>
                                                    </Button>
                                                    {surat.berkas_unduh_url && (
                                                        <Button asChild variant="outline" size="sm" className="h-8 gap-1 text-xs">
                                                            <a href={surat.berkas_unduh_url}>
                                                                <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                                <span>Unduh</span>
                                                            </a>
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <p className="text-muted-foreground mt-1 text-xs">Tidak ada berkas scan terlampir.</p>
                                        )}
                                    </div>

                                    {surat_balasan.length > 0 && (
                                        <div className="border-t pt-4">
                                            <h3 className="text-foreground text-sm font-semibold">Surat Keluar Balasan</h3>
                                            <div className="mt-2 space-y-2">
                                                {surat_balasan.map((b) => (
                                                    <Link
                                                        key={b.id}
                                                        href={route('umum.surat.keluar.show', b.id)}
                                                        className="hover:bg-surface-subtle flex items-center justify-between rounded-lg border p-3 text-sm"
                                                    >
                                                        <div>
                                                            <p className="font-medium">{b.nomor}</p>
                                                            <p className="text-muted-foreground text-xs">{b.perihal}</p>
                                                        </div>
                                                        <StatusBadge status={b.status_kode} />
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </TabsContent>

                                <TabsContent value="disposisi" className="space-y-3 pt-4">
                                    {cabangUtama.length === 0 ? (
                                        <EmptyState
                                            title="Belum Ada Disposisi"
                                            description="Disposisi akan tampil setelah Ketua mengarahkan surat kepada Wakil Ketua atau pelaksana."
                                        />
                                    ) : (
                                        cabangUtama.map((c) => {
                                            const anakList = anakByParent.get(c.id) ?? [];
                                            return (
                                                <div key={c.id} className="rounded-xl border p-4">
                                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                                        <div>
                                                            <p className="text-foreground text-sm font-semibold">
                                                                {c.kepada_nama}{' '}
                                                                {c.kepada_peran && (
                                                                    <span className="text-muted-foreground font-normal">({c.kepada_peran})</span>
                                                                )}
                                                            </p>
                                                            <p className="text-muted-foreground text-xs">
                                                                Dari {c.dari_nama} · {c.waktu} {c.tenggat ? `· Tenggat ${c.tenggat}` : ''}
                                                            </p>
                                                        </div>
                                                        <StatusBadge
                                                            status={c.status}
                                                            variant={c.status === 'selesai' ? 'final' : c.status === 'diteruskan' ? 'info' : 'warning'}
                                                            label={c.status === 'selesai' ? 'Selesai' : c.status === 'diteruskan' ? 'Diteruskan' : 'Menunggu'}
                                                        />
                                                    </div>
                                                    <p className="bg-surface-subtle text-foreground mt-2 rounded-lg p-2.5 text-sm">{c.instruksi}</p>
                                                    {c.catatan_tindak_lanjut && (
                                                        <p className="text-success mt-2 text-xs font-medium">
                                                            Tindak lanjut: {c.catatan_tindak_lanjut}
                                                        </p>
                                                    )}
                                                    {c.bukti_url && (
                                                        <a
                                                            href={c.bukti_url}
                                                            className="text-primary mt-1 inline-flex items-center gap-1 text-xs hover:underline"
                                                        >
                                                            <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                            <span>Unduh Bukti: {c.bukti_nama}</span>
                                                        </a>
                                                    )}

                                                    {anakList.length > 0 && (
                                                        <div className="border-border mt-3 space-y-2 border-l-2 pl-4">
                                                            {anakList.map((a) => (
                                                                <div key={a.id} className="bg-surface-subtle/60 rounded-lg border p-3">
                                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                                        <p className="text-foreground text-sm font-medium">
                                                                            {a.kepada_nama}{' '}
                                                                            {a.kepada_peran && (
                                                                                <span className="text-muted-foreground text-xs font-normal">
                                                                                    ({a.kepada_peran})
                                                                                </span>
                                                                            )}
                                                                        </p>
                                                                        <StatusBadge
                                                                            status={a.status}
                                                                            variant={a.status === 'selesai' ? 'final' : 'warning'}
                                                                            label={a.status === 'selesai' ? 'Selesai' : 'Menunggu'}
                                                                        />
                                                                    </div>
                                                                    <p className="text-foreground mt-1 text-xs">{a.instruksi}</p>
                                                                    {a.catatan_tindak_lanjut && (
                                                                        <p className="text-success mt-1.5 text-xs font-medium">
                                                                            Laporan: {a.catatan_tindak_lanjut}
                                                                        </p>
                                                                    )}
                                                                    {a.bukti_url && (
                                                                        <a
                                                                            href={a.bukti_url}
                                                                            className="text-primary mt-1 inline-flex items-center gap-1 text-xs hover:underline"
                                                                        >
                                                                            <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                                            <span>{a.bukti_nama}</span>
                                                                        </a>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })
                                    )}
                                </TabsContent>

                                <TabsContent value="riwayat" className="pt-4">
                                    <Timeline steps={langkahTimeline} />
                                </TabsContent>
                            </Tabs>
                        </div>
                    </div>

                    <div className="space-y-5">
                        {!selesai && akses.boleh_kelola && surat.status_kode === 'diterima_arsip' && (
                            <section className="bg-card border-border rounded-2xl border p-5">
                                <h2 className="text-foreground text-base font-semibold">Teruskan ke Ketua</h2>
                                <p className="text-muted-foreground mt-1 text-xs">Kirim surat masuk ke meja Ketua untuk disposisi pimpinan.</p>
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        formTeruskan.post(route('umum.surat.masuk.teruskan-ketua', surat.id), { preserveScroll: true });
                                    }}
                                    className="mt-3 space-y-3"
                                >
                                    <textarea
                                        rows={2}
                                        placeholder="Catatan pengantar (opsional)"
                                        value={formTeruskan.data.catatan}
                                        onChange={(e) => formTeruskan.setData('catatan', e.target.value)}
                                        className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                    <Button type="submit" className="h-10 w-full gap-1.5" disabled={formTeruskan.processing}>
                                        <Send className="h-4 w-4" aria-hidden="true" />
                                        <span>Teruskan ke Ketua</span>
                                    </Button>
                                </form>
                            </section>
                        )}

                        {!selesai &&
                            akses.boleh_disposisi_ketua &&
                            ['diteruskan_ketua', 'disposisi_waka', 'tindak_lanjut'].includes(surat.status_kode) && (
                                <section className="bg-card border-border rounded-2xl border p-5">
                                    <h2 className="text-foreground text-base font-semibold">Disposisi Ketua</h2>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        Pilih satu atau beberapa Wakil Ketua (atau langsung pelaksana) serta tuliskan instruksi.
                                    </p>
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            formKetua.post(route('umum.surat.masuk.disposisi', surat.id), {
                                                preserveScroll: true,
                                                onSuccess: () => formKetua.reset(),
                                            });
                                        }}
                                        className="mt-3 space-y-3"
                                    >
                                        <div className="space-y-1.5">
                                            <Label>Penerima Disposisi (wajib)</Label>
                                            <div className="max-h-48 space-y-1.5 overflow-y-auto rounded-lg border p-2.5">
                                                {waka_list.length > 0 && (
                                                    <p className="text-muted-foreground px-1 text-[11px] font-semibold uppercase">Wakil Ketua</p>
                                                )}
                                                {waka_list.map((w) => (
                                                    <label
                                                        key={w.id}
                                                        className="hover:bg-surface-subtle flex min-h-10 cursor-pointer items-center gap-2.5 rounded px-1.5 py-1 text-sm"
                                                    >
                                                        <Checkbox
                                                            checked={formKetua.data.kepada_user_ids.includes(w.id)}
                                                            onCheckedChange={() =>
                                                                formKetua.setData('kepada_user_ids', toggleId(formKetua.data.kepada_user_ids, w.id))
                                                            }
                                                        />
                                                        <span className="flex-1">
                                                            {w.name} <span className="text-muted-foreground text-xs">({w.jabatan})</span>
                                                        </span>
                                                    </label>
                                                ))}

                                                <p className="text-muted-foreground mt-2 px-1 text-[11px] font-semibold uppercase">
                                                    Kabag / Staf Internal
                                                </p>
                                                {internal_list
                                                    .filter((u) => !waka_list.some((w) => w.id === u.id))
                                                    .map((u) => (
                                                        <label
                                                            key={u.id}
                                                            className="hover:bg-surface-subtle flex min-h-10 cursor-pointer items-center gap-2.5 rounded px-1.5 py-1 text-sm"
                                                        >
                                                            <Checkbox
                                                                checked={formKetua.data.kepada_user_ids.includes(u.id)}
                                                                onCheckedChange={() =>
                                                                    formKetua.setData(
                                                                        'kepada_user_ids',
                                                                        toggleId(formKetua.data.kepada_user_ids, u.id),
                                                                    )
                                                                }
                                                            />
                                                            <span className="flex-1">
                                                                {u.name} <span className="text-muted-foreground text-xs">({u.peran})</span>
                                                            </span>
                                                        </label>
                                                    ))}
                                            </div>
                                            {formKetua.errors.kepada_user_ids && (
                                                <p className="text-destructive text-xs">{formKetua.errors.kepada_user_ids}</p>
                                            )}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="ketua-instruksi">Instruksi Disposisi (wajib)</Label>
                                            <textarea
                                                id="ketua-instruksi"
                                                rows={3}
                                                value={formKetua.data.instruksi}
                                                onChange={(e) => formKetua.setData('instruksi', e.target.value)}
                                                placeholder="Tuliskan arahan tindak lanjut..."
                                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                            />
                                            {formKetua.errors.instruksi && <p className="text-destructive text-xs">{formKetua.errors.instruksi}</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="ketua-tenggat">Batas Waktu (opsional)</Label>
                                            <Input
                                                id="ketua-tenggat"
                                                type="date"
                                                value={formKetua.data.tenggat}
                                                onChange={(e) => formKetua.setData('tenggat', e.target.value)}
                                                className="h-9"
                                            />
                                        </div>

                                        <Button type="submit" className="h-11 w-full gap-1.5" disabled={formKetua.processing}>
                                            <Send className="h-4 w-4" aria-hidden="true" />
                                            <span>Kirim Disposisi</span>
                                        </Button>
                                    </form>
                                </section>
                            )}

                        {!selesai && disposisi_aktif_saya && (
                            <section className="bg-card border-border space-y-5 rounded-2xl border p-5">
                                <div>
                                    <h2 className="text-foreground text-base font-semibold">Tindak Lanjut Disposisi Saya</h2>
                                    <p className="bg-surface-subtle text-foreground mt-2 rounded-lg p-2.5 text-xs">
                                        Instruksi: {disposisi_aktif_saya.instruksi}
                                    </p>
                                </div>

                                {disposisi_aktif_saya.tahap === 'waka' && akses.adalah_waka && (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            formTerusWaka.post(route('umum.surat.masuk.disposisi', surat.id), {
                                                preserveScroll: true,
                                                onSuccess: () => formTerusWaka.reset('kepada_user_ids', 'instruksi', 'tenggat'),
                                            });
                                        }}
                                        className="space-y-3 border-b pb-5"
                                    >
                                        <p className="text-foreground text-sm font-medium">Teruskan ke Bawahan / Bidang</p>
                                        <div className="max-h-40 space-y-1 overflow-y-auto rounded-lg border p-2">
                                            {internal_list.map((u) => (
                                                <label
                                                    key={u.id}
                                                    className="hover:bg-surface-subtle flex min-h-10 cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-sm"
                                                >
                                                    <Checkbox
                                                        checked={formTerusWaka.data.kepada_user_ids.includes(u.id)}
                                                        onCheckedChange={() =>
                                                            formTerusWaka.setData(
                                                                'kepada_user_ids',
                                                                toggleId(formTerusWaka.data.kepada_user_ids, u.id),
                                                            )
                                                        }
                                                    />
                                                    <span>
                                                        {u.name} <span className="text-muted-foreground text-xs">({u.peran})</span>
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        {formTerusWaka.errors.kepada_user_ids && (
                                            <p className="text-destructive text-xs">{formTerusWaka.errors.kepada_user_ids}</p>
                                        )}
                                        <textarea
                                            rows={2}
                                            placeholder="Instruksi untuk pelaksana..."
                                            value={formTerusWaka.data.instruksi}
                                            onChange={(e) => formTerusWaka.setData('instruksi', e.target.value)}
                                            className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                        />
                                        {formTerusWaka.errors.instruksi && (
                                            <p className="text-destructive text-xs">{formTerusWaka.errors.instruksi}</p>
                                        )}
                                        <Button type="submit" className="h-10 w-full gap-1.5" disabled={formTerusWaka.processing}>
                                            <Send className="h-4 w-4" aria-hidden="true" />
                                            <span>Teruskan ke Pelaksana</span>
                                        </Button>
                                    </form>
                                )}

                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        formSelesai.post(
                                            route('umum.surat.masuk.disposisi.selesai', {
                                                suratMasuk: surat.id,
                                                disposisiSurat: disposisi_aktif_saya.id,
                                            }),
                                            { forceFormData: true, preserveScroll: true },
                                        );
                                    }}
                                    className="space-y-3"
                                >
                                    <p className="text-foreground text-sm font-medium">Selesaikan Cabang Disposisi Ini</p>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="tl-catatan">Laporan Hasil Tindak Lanjut (wajib)</Label>
                                        <textarea
                                            id="tl-catatan"
                                            rows={3}
                                            value={formSelesai.data.catatan_tindak_lanjut}
                                            onChange={(e) => formSelesai.setData('catatan_tindak_lanjut', e.target.value)}
                                            placeholder="Tuliskan hasil pelaksanaan atau tindak lanjut..."
                                            className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                        />
                                        {formSelesai.errors.catatan_tindak_lanjut && (
                                            <p className="text-destructive text-xs">{formSelesai.errors.catatan_tindak_lanjut}</p>
                                        )}
                                    </div>
                                    <UnggahBerkas
                                        label="Bukti Tindak Lanjut (opsional)"
                                        value={formSelesai.data.berkas_bukti}
                                        onChange={(file) => formSelesai.setData('berkas_bukti', file)}
                                    />
                                    <Button type="submit" variant="outline" className="h-10 w-full gap-1.5" disabled={formSelesai.processing}>
                                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                        <span>Tandai Cabang Selesai</span>
                                    </Button>
                                </form>
                            </section>
                        )}
                    </div>
                </div>
            </div>

            <AlertDialog open={dialogTutup} onOpenChange={setDialogTutup}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Tutup &amp; Selesaikan Surat Masuk?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Status surat masuk {surat.nomor_agenda} dan seluruh cabang disposisi yang masih menunggu akan ditandai selesai.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="space-y-1.5 py-2">
                        <Label htmlFor="tutup-catatan">Catatan Penutupan (wajib)</Label>
                        <textarea
                            id="tutup-catatan"
                            rows={3}
                            value={formTutup.data.catatan_penutupan}
                            onChange={(e) => formTutup.setData('catatan_penutupan', e.target.value)}
                            placeholder="Mis. Cukup diketahui pimpinan / diarsipkan."
                            className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        />
                        {formTutup.errors.catatan_penutupan && <p className="text-destructive text-xs">{formTutup.errors.catatan_penutupan}</p>}
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={(e) => {
                                e.preventDefault();
                                formTutup.post(route('umum.surat.masuk.tutup', surat.id), {
                                    preserveScroll: true,
                                    onSuccess: () => setDialogTutup(false),
                                });
                            }}
                        >
                            Selesaikan Surat Masuk
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
