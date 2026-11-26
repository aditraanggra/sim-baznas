import { EmptyState, PratinjauPdfModal } from '@/components/app';
import { KontenKinerjaZis, type DataKinerjaZis } from '@/components/pkp/konten-kinerja-zis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PublicLayout } from '@/layouts/public-layout';
import { router } from '@inertiajs/react';
import { Archive, Download, Eye, FileText, Gauge, Printer, Search } from 'lucide-react';
import * as React from 'react';

interface DokumenPublikItem {
    id: number;
    uuid: string;
    judul: string;
    nomor: string | null;
    tahun: number;
    versi: number;
    tanggal_pengesahan: string | null;
    ringkasan: string | null;
    kategori: string | null;
    kategori_id: number;
    bidang: string | null;
    ukuran: number;
    nama_berkas: string;
    url_lihat: string;
    url_cetak: string;
    url_unduh: string;
}

interface Props {
    token: string;
    tautan: {
        label: string;
        jenis: string;
        jenis_label: string;
        boleh_kinerja: boolean;
        boleh_arsip: boolean;
        kadaluarsa_at: string | null;
    };
    tab_aktif: 'kinerja' | 'arsip';
    kinerja: DataKinerjaZis | null;
    arsip: {
        filter: {
            q: string;
            kategori_id: number | null;
            tahun_arsip: number | null;
        };
        daftar_kategori: { id: number; kode: string; nama: string }[];
        daftar_tahun: number[];
        dokumen: DokumenPublikItem[];
    } | null;
    url_pdf_kinerja: string | null;
}

function formatUkuranBerkas(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

export default function PortalPimpinan({ token, tautan, tab_aktif, kinerja, arsip, url_pdf_kinerja }: Props) {
    const [tab, setTab] = React.useState<'kinerja' | 'arsip'>(tab_aktif);
    const [q, setQ] = React.useState(arsip?.filter.q ?? '');
    const [kategoriId, setKategoriId] = React.useState(arsip?.filter.kategori_id ? String(arsip.filter.kategori_id) : '');
    const [tahunArsip, setTahunArsip] = React.useState(arsip?.filter.tahun_arsip ? String(arsip.filter.tahun_arsip) : '');
    const [pratinjau, setPratinjau] = React.useState<{
        dokumen: DokumenPublikItem;
        cetakOtomatis: boolean;
    } | null>(null);

    React.useEffect(() => {
        setTab(tab_aktif);
    }, [tab_aktif]);

    const pindahTab = (nilai: string) => {
        const nextTab = nilai === 'arsip' ? 'arsip' : 'kinerja';
        setTab(nextTab);
        router.get(
            route('akses.portal', { token }),
            {
                tab: nextTab,
                tahun_kinerja: kinerja?.tahun_terpilih,
                q: q || undefined,
                kategori_id: kategoriId || undefined,
                tahun_arsip: tahunArsip || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const gantiTahunKinerja = (tahunBaru: number) => {
        router.get(
            route('akses.portal', { token }),
            {
                tab: 'kinerja',
                tahun_kinerja: tahunBaru,
                q: q || undefined,
                kategori_id: kategoriId || undefined,
                tahun_arsip: tahunArsip || undefined,
            },
            { preserveState: true },
        );
    };

    const terapkanFilterArsip = (e?: React.FormEvent) => {
        e?.preventDefault();
        router.get(
            route('akses.portal', { token }),
            {
                tab: 'arsip',
                tahun_kinerja: kinerja?.tahun_terpilih,
                q: q || undefined,
                kategori_id: kategoriId || undefined,
                tahun_arsip: tahunArsip || undefined,
            },
            { preserveState: true },
        );
    };

    const panelArsip = arsip ? (
        <div className="space-y-5">
            <section className="bg-card border-border rounded-2xl border p-4 shadow-xs">
                <div className="mb-3">
                    <h2 className="text-foreground text-base font-semibold">Portal Arsip Dokumen PKP (PPID)</h2>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        Dokumen perencanaan, pelaporan kinerja, laporan keuangan, dan SOP resmi yang telah disahkan dan siap cetak/unduh dalam format PDF.
                    </p>
                </div>

                <form onSubmit={terapkanFilterArsip} className="grid gap-3 sm:grid-cols-12 sm:items-end">
                    <div className="space-y-1 sm:col-span-5">
                        <Label htmlFor="cari-arsip-pimpinan" className="text-xs">
                            Kata Kunci (Judul / Nomor)
                        </Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-arsip-pimpinan"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Cari RKAT, RENSTRA, Laporan Keuangan..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>

                    <div className="space-y-1 sm:col-span-3">
                        <Label htmlFor="filter-kategori-pimpinan" className="text-xs">
                            Kategori Dokumen
                        </Label>
                        <select
                            id="filter-kategori-pimpinan"
                            value={kategoriId}
                            onChange={(e) => setKategoriId(e.target.value)}
                            className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <option value="">Semua Kategori</option>
                            {arsip.daftar_kategori.map((k) => (
                                <option key={k.id} value={k.id}>
                                    {k.nama}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-1 sm:col-span-2">
                        <Label htmlFor="filter-tahun-pimpinan" className="text-xs">
                            Tahun
                        </Label>
                        <select
                            id="filter-tahun-pimpinan"
                            value={tahunArsip}
                            onChange={(e) => setTahunArsip(e.target.value)}
                            className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <option value="">Semua Tahun</option>
                            {arsip.daftar_tahun.map((thn) => (
                                <option key={thn} value={thn}>
                                    {thn}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="sm:col-span-2">
                        <Button type="submit" className="bg-brand hover:bg-brand-hover h-9 w-full text-white">
                            Tampilkan
                        </Button>
                    </div>
                </form>
            </section>

            {arsip.dokumen.length === 0 ? (
                <EmptyState
                    title="Belum Ada Dokumen Sesuai Filter"
                    description="Tidak ditemukan dokumen arsip PKP berstatus disahkan untuk kata kunci atau filter yang dipilih."
                />
            ) : (
                <div className="space-y-3">
                    {arsip.dokumen.map((dok) => (
                        <article key={dok.id} className="bg-card border-border rounded-2xl border p-4 shadow-xs sm:p-5">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div className="bg-brand-soft text-brand flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                                        <FileText className="h-5 w-5" aria-hidden="true" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="bg-surface-subtle text-foreground rounded-md border px-2 py-0.5 text-xs font-medium">
                                                {dok.kategori ?? 'Dokumen'}
                                            </span>
                                            <span className="text-muted-foreground text-xs font-medium tabular-nums">
                                                Tahun {dok.tahun} · v{dok.versi}
                                            </span>
                                            {dok.bidang && (
                                                <span className="text-muted-foreground text-xs">· {dok.bidang}</span>
                                            )}
                                        </div>
                                        <h3 className="text-foreground mt-1.5 text-base font-semibold">{dok.judul}</h3>
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {dok.nomor ? `Nomor: ${dok.nomor} · ` : ''}
                                            Disahkan: {dok.tanggal_pengesahan ?? '-'} · PDF ({formatUkuranBerkas(dok.ukuran)})
                                        </p>
                                        {dok.ringkasan && (
                                            <p className="text-muted-foreground mt-2 text-sm leading-5">{dok.ringkasan}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex shrink-0 flex-wrap items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPratinjau({ dokumen: dok, cetakOtomatis: false })}
                                        className="h-9 gap-1.5"
                                    >
                                        <Eye className="h-4 w-4" aria-hidden="true" />
                                        <span>Lihat</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPratinjau({ dokumen: dok, cetakOtomatis: true })}
                                        className="h-9 gap-1.5"
                                    >
                                        <Printer className="h-4 w-4" aria-hidden="true" />
                                        <span>Cetak</span>
                                    </Button>
                                    <Button asChild size="sm" className="bg-brand hover:bg-brand-hover h-9 gap-1.5 text-white">
                                        <a href={dok.url_unduh}>
                                            <Download className="h-4 w-4" aria-hidden="true" />
                                            <span>Unduh</span>
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </div>
    ) : null;

    return (
        <PublicLayout
            title={`${tautan.label} — Portal Pimpinan BAZNAS Cianjur`}
            description="Portal khusus Pimpinan dan Kepala Bagian BAZNAS Kabupaten Cianjur untuk melihat Laporan Kinerja Pengelolaan ZIS dan Arsip Dokumen PKP."
            noindex={true}
            lebar="dasbor"
            tampilkanNavPublik={false}
            subjudul="Portal Khusus Pimpinan & Kepala Bagian"
        >
            <div className="space-y-5">
                <section className="bg-card border-border flex flex-col gap-2 rounded-2xl border p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-brand text-xs font-medium tracking-wide uppercase">{tautan.jenis_label}</p>
                        <h1 className="font-display text-foreground mt-0.5 text-[22px] leading-[30px] font-semibold">
                            {tautan.label}
                        </h1>
                    </div>
                    <div className="text-muted-foreground text-xs">
                        {tautan.kadaluarsa_at ? `Berlaku hingga ${tautan.kadaluarsa_at} WIB` : 'Tautan aktif (dapat dicabut Admin)'}
                    </div>
                </section>

                {tautan.boleh_kinerja && tautan.boleh_arsip ? (
                    <Tabs value={tab} onValueChange={pindahTab}>
                        <TabsList className="print:hidden">
                            <TabsTrigger value="kinerja" className="gap-2">
                                <Gauge className="h-4 w-4" aria-hidden="true" />
                                <span>Laporan Kinerja ZIS</span>
                            </TabsTrigger>
                            <TabsTrigger value="arsip" className="gap-2">
                                <Archive className="h-4 w-4" aria-hidden="true" />
                                <span>Arsip Dokumen PKP (PPID)</span>
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="kinerja">
                            {kinerja && <KontenKinerjaZis data={kinerja} onGantiTahun={gantiTahunKinerja} urlUnduhPdf={url_pdf_kinerja} />}
                        </TabsContent>

                        <TabsContent value="arsip">{panelArsip}</TabsContent>
                    </Tabs>
                ) : tautan.boleh_kinerja && kinerja ? (
                    <KontenKinerjaZis data={kinerja} onGantiTahun={gantiTahunKinerja} urlUnduhPdf={url_pdf_kinerja} />
                ) : (
                    panelArsip
                )}
            </div>

            {pratinjau && (
                <PratinjauPdfModal
                    terbuka={true}
                    onTutup={() => setPratinjau(null)}
                    judul={pratinjau.dokumen.judul}
                    subjudul={`${pratinjau.dokumen.kategori ?? 'Dokumen'} · Tahun ${pratinjau.dokumen.tahun} · Versi ${pratinjau.dokumen.versi}`}
                    urlLihat={pratinjau.cetakOtomatis ? pratinjau.dokumen.url_cetak : pratinjau.dokumen.url_lihat}
                    urlCetak={pratinjau.dokumen.url_cetak}
                    urlUnduh={pratinjau.dokumen.url_unduh}
                    aksiOtomatisCetak={pratinjau.cetakOtomatis}
                />
            )}
        </PublicLayout>
    );
}
