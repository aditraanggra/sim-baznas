import { EmptyState, InputRupiah, PageHeader } from '@/components/app';
import { BannerStatusTahap } from '@/components/rkat/BannerStatusTahap';
import { DialogJenisKegiatan } from '@/components/rkat/DialogJenisKegiatan';
import { KartuPaguBidang } from '@/components/rkat/KartuPaguBidang';
import { TabelKegiatanBidang } from '@/components/rkat/TabelKegiatanBidang';
import {
    type JenisKegiatanItem,
    type KegiatanRencanaItem,
    type KodeBidangRkat,
    type OpsiPelengkapItem,
    type OpsiPosItem,
    type RingkasanAlokasi,
    type TahapStatusInfo,
} from '@/components/rkat/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Pencil, Plus, Power } from 'lucide-react';
import * as React from 'react';

interface VersiRingkas {
    id: number;
    tahun: number;
    label: string;
    status: string;
    terkunci?: boolean;
    target_pengumpulan: number | null;
}

interface Props {
    bidang: { kode: KodeBidangRkat; nama: string; warna: string };
    daftar_versi: VersiRingkas[];
    versi_aktif: VersiRingkas | null;
    tahap: TahapStatusInfo | null;
    ringkasan: RingkasanAlokasi | null;
    kegiatan: KegiatanRencanaItem[];
    jenis_kegiatan: JenisKegiatanItem[];
    pelengkap: Record<number, number>;
    opsi_pos: OpsiPosItem[];
    opsi_program: OpsiPosItem[];
    opsi_pelengkap: OpsiPelengkapItem[];
    dapat_kelola: boolean;
}

export default function RkatBidangIndex({
    bidang,
    daftar_versi,
    versi_aktif,
    tahap,
    ringkasan,
    kegiatan,
    jenis_kegiatan,
    pelengkap,
    opsi_pos,
    opsi_program,
    opsi_pelengkap,
    dapat_kelola,
}: Props) {
    const { props } = usePage<SharedData>();
    const galat = Object.values(props.errors ?? {});
    const sukses = props.flash?.sukses;
    const peran = props.auth.user?.peran ?? [];
    const bolehLihatPkp = peran.some((p) => ['admin', 'ketua', 'perencanaan', 'keuangan', 'kabag_pkp', 'waka_1', 'waka_2', 'waka_3', 'waka_4', 'kabag_pengumpulan', 'kabag_pendis_pendag', 'kabag_sau'].includes(p));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: bidang.nama, href: `/${bidang.kode}` },
        { title: 'Perencanaan RKAT', href: `/${bidang.kode}/rkat` },
    ];

    const [dialogJenisTerbuka, setDialogJenisTerbuka] = React.useState(false);
    const [jenisEdit, setJenisEdit] = React.useState<JenisKegiatanItem | null>(null);
    const [nilaiPelengkap, setNilaiPelengkap] = React.useState<Record<number, number>>(() => {
        const awal: Record<number, number> = {};
        opsi_pelengkap.forEach((p) => {
            awal[p.id] = pelengkap[p.id] ?? 0;
        });
        return awal;
    });
    const [simpanPelengkapLoading, setSimpanPelengkapLoading] = React.useState(false);

    React.useEffect(() => {
        const baru: Record<number, number> = {};
        opsi_pelengkap.forEach((p) => {
            baru[p.id] = pelengkap[p.id] ?? 0;
        });
        setNilaiPelengkap(baru);
    }, [pelengkap, opsi_pelengkap, versi_aktif?.id]);

    const bolehEditDraft = Boolean(dapat_kelola && versi_aktif && !versi_aktif.terkunci && tahap?.status === 'draft');

    const terpenuhi =
        bidang.kode === 'pengumpulan'
            ? Boolean(ringkasan?.pengumpulan.terpenuhi)
            : bidang.kode === 'distribusi'
              ? Boolean(ringkasan?.distribusi.terpenuhi)
              : Boolean(ringkasan?.umum.terpenuhi);

    const pesanBelumTerpenuhi =
        bidang.kode === 'pengumpulan'
            ? 'Total rincian kegiatan pengumpulan harus sama persis dengan Target Pengumpulan RAKORNAS sebelum dapat diajukan.'
            : bidang.kode === 'distribusi'
              ? 'Total rincian setiap kelompok dana (7 kelompok) harus sama persis dengan pagu penyaluran sebelum dapat diajukan.'
              : 'Total rincian belanja operasional (L7) harus sama persis dengan total pagu operasional SAU sebelum dapat diajukan.';

    const simpanPelengkap = (e: React.FormEvent) => {
        e.preventDefault();
        if (!versi_aktif) return;
        router.post(
            route(`${bidang.kode}.rkat.pelengkap`),
            {
                rkat_version_id: versi_aktif.id,
                nilai: nilaiPelengkap,
            },
            {
                preserveScroll: true,
                onStart: () => setSimpanPelengkapLoading(true),
                onFinish: () => setSimpanPelengkapLoading(false),
            },
        );
    };

    const toggleAktifJenis = (item: JenisKegiatanItem) => {
        router.post(
            route(`${bidang.kode}.rkat.jenis-kegiatan`),
            {
                aksi: 'toggle_aktif',
                id: item.id,
            },
            { preserveScroll: true },
        );
    };

    const judulPelengkap =
        bidang.kode === 'pengumpulan'
            ? 'Pencatatan Luar Neraca (L2/LN — Opsional)'
            : bidang.kode === 'distribusi'
              ? 'Target Pengentasan Kemiskinan (L5/3 — Opsional)'
              : 'Hibah Operasional APBN/APBD & Saldo Awal Dana Amil (L6 — Opsional)';

    const deskripsiPelengkap =
        bidang.kode === 'pengumpulan'
            ? 'Catatan potensi di luar neraca BAZNAS (tidak memengaruhi Target Pengumpulan maupun potongan Hak Amil).'
            : bidang.kode === 'distribusi'
              ? 'Target jumlah mustahik yang dientaskan dari garis kemiskinan ekstrem dan garis kemiskinan.'
              : 'Nilai Hibah APBN/APBD dan Saldo Awal Dana Amil otomatis menambah Total Pagu Operasional SAU.';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Perencanaan RKAT — ${bidang.nama}`} />
            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-4 p-6">
                <PageHeader
                    title={`Perencanaan RKAT — ${bidang.nama}`}
                    description="Rincian kegiatan bidang terhubung otomatis dengan akun RKAT konsolidasi di bagian PKP."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            {daftar_versi.length > 0 && versi_aktif && (
                                <Select
                                    value={String(versi_aktif.id)}
                                    onValueChange={(v) => router.get(route(`${bidang.kode}.rkat.index`), { versi: v })}
                                >
                                    <SelectTrigger className="h-9 w-56 bg-card">
                                        <SelectValue placeholder="Pilih Versi RKAT" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {daftar_versi.map((v) => (
                                            <SelectItem key={v.id} value={String(v.id)}>
                                                RKAT {v.tahun} · {v.label} ({v.status})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                            {versi_aktif && bolehLihatPkp && (
                                <Button variant="outline" asChild className="h-9">
                                    <Link href={route('pkp.rencana.show', versi_aktif.id)}>Lihat Konsolidasi PKP</Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                {sukses && (
                    <div
                        role="status"
                        className="bg-success-soft text-success border-success/20 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium"
                    >
                        <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>{sukses}</span>
                    </div>
                )}

                {galat.length > 0 && (
                    <div role="alert" className="bg-destructive-soft text-destructive border-destructive/20 rounded-xl border px-4 py-3 text-sm">
                        <ul className="list-disc space-y-1 pl-4">
                            {galat.map((g, i) => (
                                <li key={i}>{String(g)}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {!versi_aktif || !tahap || !ringkasan ? (
                    <EmptyState
                        title="Belum ada RKAT kolaboratif yang aktif"
                        description="Bagian PKP belum menetapkan Target Pengumpulan RAKORNAS untuk versi RKAT kolaboratif baru. Anda tetap dapat menyiapkan Master Jenis Kegiatan pada tab di bawah."
                        actionLabel={dapat_kelola ? 'Kelola Master Jenis Kegiatan' : undefined}
                        onAction={
                            dapat_kelola
                                ? () => {
                                      setJenisEdit(null);
                                      setDialogJenisTerbuka(true);
                                  }
                                : undefined
                        }
                    />
                ) : (
                    <>
                        <BannerStatusTahap
                            bidangKode={bidang.kode}
                            versiId={versi_aktif.id}
                            versiTahun={versi_aktif.tahun}
                            versiLabel={versi_aktif.label}
                            versiTerkunci={Boolean(versi_aktif.terkunci)}
                            tahap={tahap}
                            terpenuhi={terpenuhi}
                            pesanBelumTerpenuhi={pesanBelumTerpenuhi}
                            dapatKelola={dapat_kelola}
                        />

                        <KartuPaguBidang bidangKode={bidang.kode} ringkasan={ringkasan} />
                    </>
                )}

                <Tabs defaultValue={versi_aktif ? 'kegiatan' : 'jenis'}>
                    <TabsList>
                        {versi_aktif && <TabsTrigger value="kegiatan">Rincian Kegiatan ({kegiatan.length})</TabsTrigger>}
                        {versi_aktif && opsi_pelengkap.length > 0 && <TabsTrigger value="pelengkap">Pos Pelengkap RKAT</TabsTrigger>}
                        <TabsTrigger value="jenis">Master Jenis Kegiatan ({jenis_kegiatan.length})</TabsTrigger>
                    </TabsList>

                    {versi_aktif && (
                        <TabsContent value="kegiatan">
                            <TabelKegiatanBidang
                                bidangKode={bidang.kode}
                                versiId={versi_aktif.id}
                                kegiatan={kegiatan}
                                jenisKegiatan={jenis_kegiatan}
                                opsiPos={opsi_pos}
                                opsiProgram={opsi_program}
                                bolehEdit={bolehEditDraft}
                                onBukaTambahJenis={() => {
                                    setJenisEdit(null);
                                    setDialogJenisTerbuka(true);
                                }}
                            />
                        </TabsContent>
                    )}

                    {versi_aktif && opsi_pelengkap.length > 0 && (
                        <TabsContent value="pelengkap">
                            <section className="bg-card border-border rounded-xl border p-5 shadow-xs">
                                <h2 className="text-foreground text-base font-semibold">{judulPelengkap}</h2>
                                <p className="text-muted-foreground mt-1 text-xs">{deskripsiPelengkap}</p>

                                <form onSubmit={simpanPelengkap} className="mt-4 grid gap-4 sm:grid-cols-2">
                                    {opsi_pelengkap.map((pos) => (
                                        <div key={pos.id} className="space-y-1.5">
                                            {pos.tipe_nilai === 'rupiah' ? (
                                                <InputRupiah
                                                    id={`pelengkap-${pos.id}`}
                                                    label={`${pos.kode} · ${pos.nama}`}
                                                    value={nilaiPelengkap[pos.id] ?? 0}
                                                    disabled={!bolehEditDraft}
                                                    onChange={(val) => setNilaiPelengkap((prev) => ({ ...prev, [pos.id]: val }))}
                                                />
                                            ) : (
                                                <div className="space-y-1.5">
                                                    <Label htmlFor={`pelengkap-${pos.id}`}>
                                                        {pos.kode} · {pos.nama} ({pos.tipe_nilai})
                                                    </Label>
                                                    <Input
                                                        id={`pelengkap-${pos.id}`}
                                                        type="number"
                                                        min={0}
                                                        className="h-9 text-right tabular-nums"
                                                        disabled={!bolehEditDraft}
                                                        value={nilaiPelengkap[pos.id] ?? 0}
                                                        onChange={(e) =>
                                                            setNilaiPelengkap((prev) => ({
                                                                ...prev,
                                                                [pos.id]: Number.parseInt(e.target.value || '0', 10),
                                                            }))
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    {bolehEditDraft && (
                                        <div className="sm:col-span-2">
                                            <Button type="submit" className="h-9" disabled={simpanPelengkapLoading}>
                                                {simpanPelengkapLoading ? 'Menyimpan...' : 'Simpan Data Pelengkap'}
                                            </Button>
                                        </div>
                                    )}
                                </form>
                            </section>
                        </TabsContent>
                    )}

                    <TabsContent value="jenis">
                        <section className="bg-card border-border rounded-xl border shadow-xs">
                            <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                                <div>
                                    <h2 className="text-foreground text-base font-semibold">Master Jenis Kegiatan Bidang</h2>
                                    <p className="text-muted-foreground text-xs">
                                        Dikelola oleh Kepala Bagian {bidang.nama} dan terpetakan ke akun RKAT PKP (tanpa hapus permanen).
                                    </p>
                                </div>
                                {dapat_kelola && (
                                    <Button
                                        type="button"
                                        className="h-9"
                                        onClick={() => {
                                            setJenisEdit(null);
                                            setDialogJenisTerbuka(true);
                                        }}
                                    >
                                        <Plus className="h-4 w-4" aria-hidden="true" />
                                        Tambah Jenis Kegiatan
                                    </Button>
                                )}
                            </div>

                            {jenis_kegiatan.length === 0 ? (
                                <div className="p-6">
                                    <EmptyState
                                        title="Belum ada master jenis kegiatan"
                                        description="Tambahkan jenis kegiatan agar dapat dipakai berulang saat menyusun rincian RKAT."
                                        actionLabel={dapat_kelola ? 'Tambah Jenis Kegiatan' : undefined}
                                        onAction={
                                            dapat_kelola
                                                ? () => {
                                                      setJenisEdit(null);
                                                      setDialogJenisTerbuka(true);
                                                  }
                                                : undefined
                                        }
                                    />
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="bg-surface-subtle text-muted-foreground text-left text-[11px] font-semibold tracking-wider uppercase">
                                            <tr>
                                                <th className="px-4 py-3">Nama Jenis Kegiatan</th>
                                                <th className="px-4 py-3">Akun RKAT</th>
                                                {bidang.kode === 'distribusi' && <th className="px-4 py-3">Program L4</th>}
                                                <th className="px-4 py-3">Satuan</th>
                                                <th className="px-4 py-3">Status</th>
                                                {dapat_kelola && <th className="px-4 py-3 text-right">Aksi</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-border divide-y">
                                            {jenis_kegiatan.map((item) => (
                                                <tr key={item.id} className="hover:bg-surface-subtle/60">
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">
                                                            {item.kode && <span className="text-muted-foreground mr-1.5 font-mono text-xs">{item.kode}</span>}
                                                            {item.nama}
                                                        </div>
                                                        {item.keterangan && <div className="text-muted-foreground text-xs">{item.keterangan}</div>}
                                                    </td>
                                                    <td className="px-4 py-3 text-xs">
                                                        <span className="font-mono">{item.pos_kode}</span> · {item.pos_nama}
                                                    </td>
                                                    {bidang.kode === 'distribusi' && (
                                                        <td className="px-4 py-3 text-xs">
                                                            <span className="font-mono">{item.program_kode}</span> · {item.program_nama}
                                                        </td>
                                                    )}
                                                    <td className="px-4 py-3 text-xs">{item.satuan_default}</td>
                                                    <td className="px-4 py-3 text-xs">
                                                        <span
                                                            className={
                                                                item.aktif
                                                                    ? 'bg-success-soft text-success rounded-md px-2 py-0.5 font-medium'
                                                                    : 'bg-surface-subtle text-muted-foreground rounded-md px-2 py-0.5 font-medium'
                                                            }
                                                        >
                                                            {item.aktif ? 'Aktif' : 'Nonaktif'}
                                                        </span>
                                                    </td>
                                                    {dapat_kelola && (
                                                        <td className="px-4 py-3 text-right">
                                                            <div className="inline-flex items-center gap-1">
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-8 px-2"
                                                                    onClick={() => {
                                                                        setJenisEdit(item);
                                                                        setDialogJenisTerbuka(true);
                                                                    }}
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                                                    <span className="sr-only">Ubah</span>
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-8 px-2"
                                                                    onClick={() => toggleAktifJenis(item)}
                                                                >
                                                                    <Power className="h-3.5 w-3.5" aria-hidden="true" />
                                                                    <span className="sr-only">Ubah Status</span>
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </section>
                    </TabsContent>
                </Tabs>

                <DialogJenisKegiatan
                    open={dialogJenisTerbuka}
                    onOpenChange={setDialogJenisTerbuka}
                    bidangKode={bidang.kode}
                    itemEdit={jenisEdit}
                    opsiPos={opsi_pos}
                    opsiProgram={opsi_program}
                />
            </div>
        </AppLayout>
    );
}
