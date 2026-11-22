import { EmptyState, PageHeader, StatusBadge, UnggahBerkas } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, FileDown, ShieldAlert } from 'lucide-react';
import * as React from 'react';

interface Props {
    mustahik: {
        id: number;
        kode: string;
        jenis: string;
        nama: string;
        nik_masked: string | null;
        nik_asli: string | null;
        telepon: string | null;
        alamat: string | null;
        kecamatan: string | null;
        desa: string | null;
        penanggung_jawab: string | null;
        status_verifikasi: string;
        diverifikasi_oleh: string | null;
        waktu_verifikasi: string | null;
        catatan_verifikasi: string | null;
        asnaf_nama: string[];
    };
    dokumen: { id: number; jenis: string | null; nama_file: string | null; keterangan: string | null; url_unduh: string | null }[];
    riwayat_permohonan: { id: number; nomor: string; tanggal: string | null; program: string | null; nominal_diajukan: number; status_kode: string }[];
    riwayat_penyaluran: { id: number; nomor: string; tanggal: string | null; program: string | null; status: string; total: number }[];
    jenis_dokumen_list: { id: number; kode: string; nama: string }[];
    bisa_kelola: boolean;
    bisa_verifikasi: boolean;
}

export default function MustahikShow({
    mustahik,
    dokumen,
    riwayat_permohonan,
    riwayat_penyaluran,
    jenis_dokumen_list,
    bisa_kelola,
    bisa_verifikasi,
}: Props) {
    const [tampilNikAsli, setTampilNikAsli] = React.useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pendistribusian', href: '/distribusi' },
        { title: 'Data Mustahik', href: '/distribusi/mustahik' },
        { title: mustahik.kode, href: `/distribusi/mustahik/${mustahik.id}` },
    ];

    const formVerifikasi = useForm({
        status_verifikasi: 'terverifikasi',
        catatan_verifikasi: '',
    });

    const formDokumen = useForm<{ jenis_dokumen_id: string; berkas: File | null; keterangan: string }>({
        jenis_dokumen_id: jenis_dokumen_list[0] ? String(jenis_dokumen_list[0].id) : '',
        berkas: null,
        keterangan: '',
    });

    const kirimVerifikasi = (e: React.FormEvent) => {
        e.preventDefault();
        formVerifikasi.post(route('distribusi.mustahik.verifikasi', mustahik.id), { preserveScroll: true });
    };

    const unggahDokumen = (e: React.FormEvent) => {
        e.preventDefault();
        formDokumen.post(route('distribusi.mustahik.dokumen', mustahik.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => formDokumen.reset('berkas', 'keterangan'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Profil Mustahik ${mustahik.nama}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={mustahik.nama}
                    description={`Kode ${mustahik.kode} · ${mustahik.jenis === 'lembaga' ? 'Lembaga' : 'Perorangan'}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <StatusBadge
                            status={mustahik.status_verifikasi}
                            variant={mustahik.status_verifikasi === 'terverifikasi' ? 'success' : mustahik.status_verifikasi === 'ditolak' ? 'ditolak' : 'warning'}
                            label={`Status: ${mustahik.status_verifikasi}`}
                        />
                    }
                />

                <div className="grid gap-5 lg:grid-cols-3">
                    <section className="bg-card border-border space-y-3 rounded-2xl border p-5 lg:col-span-2">
                        <h2 className="text-foreground text-base font-semibold">Identitas & Kategori Asnaf</h2>
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground text-xs">NIK</dt>
                                <dd className="text-foreground mt-0.5 flex items-center gap-2 font-medium tabular-nums">
                                    <span>{tampilNikAsli && mustahik.nik_asli ? mustahik.nik_asli : (mustahik.nik_masked ?? '-')}</span>
                                    {mustahik.nik_asli && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            className="h-7 px-2 text-xs"
                                            onClick={() => setTampilNikAsli((v) => !v)}
                                        >
                                            {tampilNikAsli ? 'Sembunyikan' : 'Lihat NIK'}
                                        </Button>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Telepon</dt>
                                <dd className="text-foreground mt-0.5 font-medium">{mustahik.telepon ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Wilayah</dt>
                                <dd className="text-foreground mt-0.5">
                                    {mustahik.desa ? `Desa ${mustahik.desa}, ` : ''}
                                    {mustahik.kecamatan ? `Kec. ${mustahik.kecamatan}` : '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">Kategori Asnaf</dt>
                                <dd className="text-foreground mt-0.5 font-medium">
                                    {mustahik.asnaf_nama.length ? mustahik.asnaf_nama.join(', ') : 'Belum ditentukan'}
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-muted-foreground text-xs">Alamat</dt>
                                <dd className="text-foreground mt-0.5">{mustahik.alamat ?? '-'}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="bg-card border-border space-y-3 rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Verifikasi Mustahik</h2>
                        {mustahik.diverifikasi_oleh && (
                            <p className="text-muted-foreground text-xs">
                                Diperiksa oleh {mustahik.diverifikasi_oleh} pada {mustahik.waktu_verifikasi}.
                            </p>
                        )}
                        {bisa_verifikasi && (
                            <form onSubmit={kirimVerifikasi} className="space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="v-status">Keputusan Verifikasi</Label>
                                    <Select
                                        value={formVerifikasi.data.status_verifikasi}
                                        onValueChange={(v) => formVerifikasi.setData('status_verifikasi', v)}
                                    >
                                        <SelectTrigger id="v-status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="terverifikasi">Terverifikasi (Layak)</SelectItem>
                                            <SelectItem value="ditolak">Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="v-catatan">Catatan Verifikasi</Label>
                                    <Input
                                        id="v-catatan"
                                        value={formVerifikasi.data.catatan_verifikasi}
                                        onChange={(e) => formVerifikasi.setData('catatan_verifikasi', e.target.value)}
                                        placeholder="Catatan kelayakan atau alasan..."
                                    />
                                </div>
                                <Button type="submit" className="w-full gap-1.5" disabled={formVerifikasi.processing}>
                                    {formVerifikasi.data.status_verifikasi === 'terverifikasi' ? (
                                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                    ) : (
                                        <ShieldAlert className="h-4 w-4" aria-hidden="true" />
                                    )}
                                    <span>Simpan Verifikasi</span>
                                </Button>
                            </form>
                        )}
                    </section>
                </div>

                <section className="bg-card border-border space-y-4 rounded-2xl border p-5">
                    <h2 className="text-foreground text-base font-semibold">Dokumen Pendukung Privat (KTP, KK, SKTM, Foto)</h2>
                    {bisa_kelola && (
                        <form onSubmit={unggahDokumen} className="border-border grid gap-3 rounded-xl border p-3 sm:grid-cols-4 sm:items-end">
                            <div className="space-y-1.5">
                                <Label htmlFor="dok-jenis">Jenis Dokumen</Label>
                                <Select value={formDokumen.data.jenis_dokumen_id} onValueChange={(v) => formDokumen.setData('jenis_dokumen_id', v)}>
                                    <SelectTrigger id="dok-jenis">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jenis_dokumen_list.map((j) => (
                                            <SelectItem key={j.id} value={String(j.id)}>
                                                {j.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="dok-file">Berkas (PDF/JPG/PNG)</Label>
                                <UnggahBerkas
                                    id="dok-file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    value={formDokumen.data.berkas}
                                    onChange={(file) => formDokumen.setData('berkas', file)}
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="dok-ket">Keterangan</Label>
                                <Input
                                    id="dok-ket"
                                    value={formDokumen.data.keterangan}
                                    onChange={(e) => formDokumen.setData('keterangan', e.target.value)}
                                />
                            </div>
                            <Button type="submit" disabled={formDokumen.processing || !formDokumen.data.berkas}>
                                Unggah Dokumen
                            </Button>
                        </form>
                    )}

                    {dokumen.length === 0 ? (
                        <EmptyState title="Belum Ada Dokumen Diunggah" description="Unggah KTP, KK, atau SKTM mustahik sebagai arsip privat." />
                    ) : (
                        <div className="divide-border divide-y">
                            {dokumen.map((d) => (
                                <div key={d.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                                    <div>
                                        <p className="text-foreground font-medium">
                                            {d.jenis ?? 'Dokumen'} - {d.nama_file}
                                        </p>
                                        {d.keterangan && <p className="text-muted-foreground text-xs">{d.keterangan}</p>}
                                    </div>
                                    {d.url_unduh && (
                                        <Button asChild variant="outline" size="sm" className="gap-1.5">
                                            <a href={d.url_unduh} target="_blank" rel="noreferrer">
                                                <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                <span>Unduh Aman</span>
                                            </a>
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Riwayat Permohonan</h2>
                        {riwayat_permohonan.length === 0 ? (
                            <EmptyState title="Belum Ada Permohonan" description="Mustahik ini belum memiliki riwayat permohonan." />
                        ) : (
                            <div className="mt-3 divide-y">
                                {riwayat_permohonan.map((p) => (
                                    <div key={p.id} className="flex items-center justify-between py-2.5 text-sm">
                                        <div>
                                            <Link href={route('distribusi.permohonan.show', p.id)} className="text-primary font-medium hover:underline">
                                                {p.nomor}
                                            </Link>
                                            <p className="text-muted-foreground text-xs">
                                                {p.tanggal} · {p.program}
                                            </p>
                                        </div>
                                        <span className="font-medium tabular-nums">{formatRupiah(p.nominal_diajukan)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="bg-card border-border rounded-2xl border p-5">
                        <h2 className="text-foreground text-base font-semibold">Riwayat Penyaluran</h2>
                        {riwayat_penyaluran.length === 0 ? (
                            <EmptyState title="Belum Ada Penyaluran" description="Belum ada realisasi penyaluran untuk mustahik ini." />
                        ) : (
                            <div className="mt-3 divide-y">
                                {riwayat_penyaluran.map((py) => (
                                    <div key={py.id} className="flex items-center justify-between py-2.5 text-sm">
                                        <div>
                                            <p className="text-foreground font-medium">{py.nomor}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {py.tanggal} · {py.program} · {py.status}
                                            </p>
                                        </div>
                                        <span className="font-semibold tabular-nums">{formatRupiah(py.total)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
