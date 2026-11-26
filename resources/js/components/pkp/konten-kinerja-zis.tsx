import { EmptyState, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { BarComparisonChart, LineTrendChart, StackedBarChart, type ChartDatum } from '@/components/ui/chart';
import { formatAngka, formatRupiah } from '@/lib/format';
import { Calendar, Coins, FileDown, HandHeart, Printer, Scale } from 'lucide-react';

export interface RingkasanLampiranItem {
    target_rkat: number;
    realisasi: number;
    selisih: number;
    persen: number | null;
    persen_label: string;
}

export interface SegmenDana {
    kode: string;
    label: string;
    nilai: number;
    warna: string;
}

export interface SumberDanaItem {
    kode: string;
    label: string;
    jenis_dana: string;
    nilai: number;
}

export interface RekapBulananItem {
    bulan: number;
    label: string;
    label_singkat: string;
    penerimaan: number;
    penyaluran: number;
    selisih: number;
    transaksi_terima: number;
    transaksi_salur: number;
}

export interface DataKinerjaZis {
    tahun_terpilih: number;
    daftar_tahun: { id: number | null; tahun: number; status: string }[];
    status_tahun: string;
    versi_rkat: string | null;
    diperbarui_pada: string;
    ringkasan: {
        total_rkat: number;
        total_realisasi: number;
        persen_serapan: number | null;
        persen_serapan_label: string;
        total_pos: number;
    };
    ringkasan_lampiran: Record<string, RingkasanLampiranItem>;
    kinerja_pengumpulan: {
        total_penerimaan: number;
        jumlah_transaksi: number;
        muzakki_menyetor: number;
        total_muzakki_aktif: number;
        total_langsung: number;
        total_upz: number;
        upz_menyetor: number;
        total_upz_aktif: number;
        tren_bulanan: ChartDatum[];
        komposisi_dana: SegmenDana[];
        per_sumber_dana: SumberDanaItem[];
    };
    kinerja_distribusi: {
        total_penyaluran: number;
        total_komitmen: number;
        jumlah_transaksi: number;
        mustahik_terbantu: number;
        total_mustahik_terverifikasi: number;
        total_pendistribusian: number;
        total_pendayagunaan: number;
        permohonan_aktif: number;
        permohonan_selesai: number;
        permohonan_ditolak: number;
        rasio_penyaluran: number | null;
        rasio_penyaluran_label: string;
        tren_bulanan: ChartDatum[];
        penyaluran_per_program: ChartDatum[];
        penyaluran_per_asnaf: ChartDatum[];
    };
    rekap_bulanan: RekapBulananItem[];
}

const DESKRIPSI_LAMPIRAN: { kode: string; nama: string; bidang: string }[] = [
    { kode: 'L2', nama: 'Penerimaan ZIS (Dalam Neraca)', bidang: 'Pengumpulan & UPZ' },
    { kode: 'L2/LN', nama: 'Penerimaan Luar Neraca', bidang: 'Pengumpulan / Keuangan' },
    { kode: 'L3', nama: 'Penyaluran ZIS', bidang: 'Pendistribusian & Pendayagunaan' },
    { kode: 'L4', nama: 'Program Penyaluran', bidang: 'Pendistribusian & Pendayagunaan' },
    { kode: 'L5', nama: 'Sasaran Muzakki & Mustahik', bidang: 'Pelayanan & Pendataan' },
    { kode: 'L6', nama: 'Biaya Operasional Amil', bidang: 'SAU & Keuangan' },
    { kode: 'L7', nama: 'Pengembangan SDM & Umum', bidang: 'SAU & Umum' },
];

export interface KontenKinerjaZisProps {
    data: DataKinerjaZis;
    onGantiTahun: (tahun: number) => void;
    urlUnduhPdf: string | null;
}

export function KontenKinerjaZis({ data, onGantiTahun, urlUnduhPdf }: KontenKinerjaZisProps) {
    const { tahun_terpilih, daftar_tahun, status_tahun, versi_rkat, diperbarui_pada, ringkasan_lampiran, kinerja_pengumpulan, kinerja_distribusi, rekap_bulanan } =
        data;

    const targetL2 = ringkasan_lampiran.L2?.target_rkat ?? data.ringkasan.total_rkat;
    const persenL2 = ringkasan_lampiran.L2?.persen_label ?? data.ringkasan.persen_serapan_label;
    const persenL3 = ringkasan_lampiran.L3?.persen_label ?? '-';

    const totalSalur = kinerja_distribusi.total_pendistribusian + kinerja_distribusi.total_pendayagunaan;
    const totalSetoran = kinerja_pengumpulan.total_langsung + kinerja_pengumpulan.total_upz;
    const porsiUpz = totalSetoran > 0 ? `${((kinerja_pengumpulan.total_upz / totalSetoran) * 100).toFixed(1)}%` : '0,0%';
    const porsiPendag = totalSalur > 0 ? `${((kinerja_distribusi.total_pendayagunaan / totalSalur) * 100).toFixed(1)}%` : '0,0%';

    return (
        <div className="space-y-5">
            {/* Baris Kontrol Tahun & Ekspor */}
            <div className="bg-card border-border flex flex-col gap-3 rounded-2xl border p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-foreground text-base font-semibold">
                        Laporan Kinerja Pengelolaan ZIS Tahun {tahun_terpilih}
                    </h2>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        Data agregat non-pribadi · Data per tanggal {diperbarui_pada} · RKAT {versi_rkat ?? 'aktif'}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 print:hidden">
                    <div className="border-border-strong bg-card flex h-9 items-center gap-1.5 rounded-md border px-3 text-sm font-medium">
                        <Calendar className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                        <label htmlFor="pilih-tahun-kinerja" className="text-muted-foreground text-xs">
                            Tahun:
                        </label>
                        <select
                            id="pilih-tahun-kinerja"
                            value={tahun_terpilih}
                            onChange={(e) => onGantiTahun(Number(e.target.value))}
                            className="text-foreground cursor-pointer bg-transparent text-sm font-semibold focus:outline-hidden"
                        >
                            {daftar_tahun.map((t) => (
                                <option key={t.tahun} value={t.tahun}>
                                    {t.tahun} ({t.status.toUpperCase()})
                                </option>
                            ))}
                        </select>
                    </div>

                    <Button type="button" variant="outline" size="sm" onClick={() => window.print()} className="h-9 gap-1.5">
                        <Printer className="h-4 w-4" aria-hidden="true" />
                        <span>Cetak</span>
                    </Button>

                    {urlUnduhPdf && (
                        <Button asChild size="sm" className="bg-brand hover:bg-brand-hover h-9 gap-1.5 text-white">
                            <a href={urlUnduhPdf} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" aria-hidden="true" />
                                <span>Unduh PDF</span>
                            </a>
                        </Button>
                    )}
                </div>
            </div>

            {/* 4 KPI Utama */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="bg-card border-border rounded-2xl border p-4 shadow-xs">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground text-xs font-medium">Target RKAT (L2)</span>
                        <StatusBadge
                            status={status_tahun}
                            variant={status_tahun === 'berjalan' ? 'aktif' : status_tahun === 'tutup' ? 'draft' : 'warning'}
                            label={status_tahun === 'berjalan' ? 'Berjalan' : status_tahun === 'tutup' ? 'Tutup' : 'Draft'}
                        />
                    </div>
                    <div className="text-foreground mt-2 text-2xl font-semibold tabular-nums">{formatRupiah(targetL2, true)}</div>
                    <p className="text-muted-foreground mt-1.5 text-xs tabular-nums">
                        {formatRupiah(targetL2)} · {versi_rkat ?? 'Belum disahkan'}
                    </p>
                </div>

                <div className="bg-card border-border rounded-2xl border p-4 shadow-xs">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground text-xs font-medium">Total Penerimaan ZIS</span>
                        <Coins className="text-success h-4 w-4" aria-hidden="true" />
                    </div>
                    <div className="text-success mt-2 text-2xl font-semibold tabular-nums">
                        {formatRupiah(kinerja_pengumpulan.total_penerimaan, true)}
                    </div>
                    <p className="text-muted-foreground mt-1.5 text-xs tabular-nums">
                        Capaian RKAT: {persenL2} · {formatAngka(kinerja_pengumpulan.jumlah_transaksi)} transaksi
                    </p>
                </div>

                <div className="bg-card border-border rounded-2xl border p-4 shadow-xs">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground text-xs font-medium">Total Penyaluran</span>
                        <HandHeart className="text-brand h-4 w-4" aria-hidden="true" />
                    </div>
                    <div className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                        {formatRupiah(kinerja_distribusi.total_penyaluran, true)}
                    </div>
                    <p className="text-muted-foreground mt-1.5 text-xs tabular-nums">
                        Capaian L3: {persenL3} · {formatAngka(kinerja_distribusi.mustahik_terbantu)} mustahik
                    </p>
                </div>

                <div className="bg-card border-border rounded-2xl border p-4 shadow-xs">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-muted-foreground text-xs font-medium">Rasio Penyaluran (ACR)</span>
                        <Scale className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                    </div>
                    <div className="text-foreground mt-2 text-2xl font-semibold tabular-nums">
                        {kinerja_distribusi.rasio_penyaluran_label}
                    </div>
                    <p className="text-muted-foreground mt-1.5 text-xs tabular-nums">
                        UPZ menyetor: {formatAngka(kinerja_pengumpulan.upz_menyetor)}/{formatAngka(kinerja_pengumpulan.total_upz_aktif)}
                    </p>
                </div>
            </div>

            {/* Tren Bulanan & Komposisi Dana */}
            <div className="grid gap-4 lg:grid-cols-5">
                <div className="lg:col-span-3">
                    {kinerja_pengumpulan.total_penerimaan === 0 ? (
                        <EmptyState
                            title="Belum Ada Transaksi Pengumpulan Final"
                            description={`Grafik tren bulanan akan terisi otomatis setelah ada penerimaan berstatus final pada tahun ${tahun_terpilih}.`}
                        />
                    ) : (
                        <LineTrendChart
                            title={`Tren Penerimaan Bulanan (${tahun_terpilih})`}
                            data={kinerja_pengumpulan.tren_bulanan}
                            isCurrency
                            height={230}
                        />
                    )}
                </div>

                <div className="flex flex-col gap-4 lg:col-span-2">
                    <StackedBarChart
                        title="Komposisi per Jenis Dana"
                        segments={kinerja_pengumpulan.komposisi_dana}
                        isCurrency
                    />
                    <div className="bg-card border-border grid grid-cols-2 gap-3 rounded-xl border p-4">
                        <div>
                            <p className="text-muted-foreground text-xs">Langsung BAZNAS</p>
                            <p className="text-foreground mt-1 text-sm font-semibold tabular-nums">
                                {formatRupiah(kinerja_pengumpulan.total_langsung, true)}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground text-xs">Melalui UPZ ({porsiUpz})</p>
                            <p className="text-foreground mt-1 text-sm font-semibold tabular-nums">
                                {formatRupiah(kinerja_pengumpulan.total_upz, true)}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Rincian Sumber Dana & Penyaluran */}
            <div className="grid gap-4 lg:grid-cols-2">
                <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                    <h3 className="text-foreground text-base font-semibold">Rincian Penerimaan per Sumber Dana</h3>
                    <p className="text-muted-foreground mt-0.5 text-xs">Akumulasi penerimaan final berdasarkan 9 sumber dana resmi.</p>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="pb-2">Kode</th>
                                    <th className="pb-2">Sumber Dana</th>
                                    <th className="pb-2 text-right">Realisasi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {kinerja_pengumpulan.per_sumber_dana.map((sd) => (
                                    <tr key={sd.kode} className="hover:bg-surface-subtle h-10 transition-colors">
                                        <td className="text-muted-foreground py-2 font-mono text-xs">{sd.kode}</td>
                                        <td className="py-2">
                                            <span className="text-foreground font-medium">{sd.label}</span>
                                            <span className="text-muted-foreground ml-1.5 text-xs">({sd.jenis_dana})</span>
                                        </td>
                                        <td className="text-foreground py-2 text-right font-medium tabular-nums">
                                            {formatRupiah(sd.nilai)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="bg-card border-border space-y-4 rounded-2xl border p-5 shadow-xs">
                    <div>
                        <h3 className="text-foreground text-base font-semibold">Kinerja Pendistribusian &amp; Pendayagunaan</h3>
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            Realisasi penyaluran dicairkan · Porsi pendayagunaan produktif {porsiPendag}.
                        </p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-surface-subtle border-border rounded-xl border p-3">
                            <p className="text-muted-foreground text-xs">Pendistribusian</p>
                            <p className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_distribusi.total_pendistribusian, true)}
                            </p>
                        </div>
                        <div className="bg-surface-subtle border-border rounded-xl border p-3">
                            <p className="text-muted-foreground text-xs">Pendayagunaan</p>
                            <p className="text-foreground mt-1 text-base font-semibold tabular-nums">
                                {formatRupiah(kinerja_distribusi.total_pendayagunaan, true)}
                            </p>
                        </div>
                    </div>

                    {kinerja_distribusi.penyaluran_per_asnaf.length === 0 ? (
                        <EmptyState
                            title="Belum Ada Penyaluran Dicairkan"
                            description={`Grafik sebaran asnaf dan program akan tampil setelah ada penyaluran dicairkan pada tahun ${tahun_terpilih}.`}
                        />
                    ) : (
                        <div className="space-y-4">
                            <BarComparisonChart
                                title="Penyaluran per Asnaf & Non-Zakat"
                                data={kinerja_distribusi.penyaluran_per_asnaf}
                                isCurrency
                                height={180}
                            />
                            <BarComparisonChart
                                title="Penyaluran per Program"
                                data={kinerja_distribusi.penyaluran_per_program}
                                isCurrency
                                height={180}
                            />
                        </div>
                    )}
                </section>
            </div>

            {/* Tabel Rekap Bulanan Jan–Des */}
            <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                <h3 className="text-foreground text-base font-semibold">Rekapitulasi Bulanan Pengumpulan &amp; Penyaluran ({tahun_terpilih})</h3>
                <p className="text-muted-foreground mt-0.5 text-xs">Perbandingan aliran dana masuk (final) dan keluar (dicairkan) setiap bulan.</p>

                <div className="mt-4 overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                <th className="pb-2">Bulan</th>
                                <th className="pb-2 text-right">Trx Terima</th>
                                <th className="pb-2 text-right">Penerimaan</th>
                                <th className="pb-2 text-right">Trx Salur</th>
                                <th className="pb-2 text-right">Penyaluran</th>
                                <th className="pb-2 text-right">Selisih Bersih</th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {rekap_bulanan.map((b) => (
                                <tr key={b.bulan} className="hover:bg-surface-subtle h-10 transition-colors">
                                    <td className="text-foreground py-2 font-medium">{b.label}</td>
                                    <td className="text-muted-foreground py-2 text-right tabular-nums">{formatAngka(b.transaksi_terima)}</td>
                                    <td className="text-success py-2 text-right font-medium tabular-nums">{formatRupiah(b.penerimaan)}</td>
                                    <td className="text-muted-foreground py-2 text-right tabular-nums">{formatAngka(b.transaksi_salur)}</td>
                                    <td className="text-foreground py-2 text-right font-medium tabular-nums">{formatRupiah(b.penyaluran)}</td>
                                    <td className="text-muted-foreground py-2 text-right tabular-nums">{formatRupiah(b.selisih)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-border bg-surface-subtle border-t font-semibold">
                                <td className="text-foreground py-2.5 pl-1">Total Tahun {tahun_terpilih}</td>
                                <td className="text-foreground py-2.5 text-right tabular-nums">{formatAngka(kinerja_pengumpulan.jumlah_transaksi)}</td>
                                <td className="text-success py-2.5 text-right tabular-nums">{formatRupiah(kinerja_pengumpulan.total_penerimaan)}</td>
                                <td className="text-foreground py-2.5 text-right tabular-nums">{formatAngka(kinerja_distribusi.jumlah_transaksi)}</td>
                                <td className="text-foreground py-2.5 text-right tabular-nums">{formatRupiah(kinerja_distribusi.total_penyaluran)}</td>
                                <td className="text-foreground py-2.5 text-right tabular-nums">
                                    {formatRupiah(kinerja_pengumpulan.total_penerimaan - kinerja_distribusi.total_penyaluran)}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            {/* Ringkasan RKAT L2–L7 */}
            <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                <h3 className="text-foreground text-base font-semibold">Ringkasan Capaian RKAT L2–L7 vs Realisasi ({tahun_terpilih})</h3>
                <p className="text-muted-foreground mt-0.5 text-xs">
                    Perbandingan pagu RKAT versi {versi_rkat ?? 'aktif'} terhadap realisasi sistem dan operasional.
                </p>

                <div className="mt-4 overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                <th className="pb-2">Lampiran</th>
                                <th className="pb-2">Uraian &amp; Bidang</th>
                                <th className="pb-2 text-right">Target RKAT</th>
                                <th className="pb-2 text-right">Realisasi</th>
                                <th className="pb-2 text-right">Capaian</th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {DESKRIPSI_LAMPIRAN.map((lamp) => {
                                const item = ringkasan_lampiran[lamp.kode] ?? {
                                    target_rkat: 0,
                                    realisasi: 0,
                                    selisih: 0,
                                    persen: null,
                                    persen_label: '-',
                                };

                                return (
                                    <tr key={lamp.kode} className="hover:bg-surface-subtle h-11 transition-colors">
                                        <td className="text-muted-foreground py-2 font-mono text-xs font-medium">{lamp.kode}</td>
                                        <td className="py-2">
                                            <div className="text-foreground font-medium">{lamp.nama}</div>
                                            <div className="text-muted-foreground text-xs">{lamp.bidang}</div>
                                        </td>
                                        <td className="text-foreground py-2 text-right tabular-nums">{formatRupiah(item.target_rkat, true)}</td>
                                        <td className="text-success py-2 text-right font-medium tabular-nums">
                                            {formatRupiah(item.realisasi, true)}
                                        </td>
                                        <td className="py-2 text-right tabular-nums">
                                            <StatusBadge
                                                status={item.persen_label}
                                                variant={
                                                    item.persen === null
                                                        ? 'draft'
                                                        : item.persen >= 80
                                                          ? 'success'
                                                          : item.persen >= 50
                                                            ? 'warning'
                                                            : 'info'
                                                }
                                                label={item.persen_label}
                                            />
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    );
}
