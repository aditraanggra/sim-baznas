import { StatusBadge } from '@/components/app';
import { formatRupiah } from '@/lib/format';
import { ArrowDownRight, ArrowUpRight, CheckCircle2, Coins, Scale, Wallet } from 'lucide-react';
import { type KodeBidangRkat, type RingkasanAlokasi } from './types';

function SelisihPill({ selisih }: { selisih: number }) {
    if (selisih === 0) {
        return <StatusBadge status="final" variant="final" label="Pas (Rp 0)" />;
    }
    if (selisih > 0) {
        return (
            <span className="bg-destructive-soft text-destructive inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium tabular-nums">
                <ArrowUpRight className="h-3.5 w-3.5" aria-hidden="true" />
                Lebih {formatRupiah(selisih)}
            </span>
        );
    }
    return (
        <span className="bg-warning-soft text-warning inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium tabular-nums">
            <ArrowDownRight className="h-3.5 w-3.5" aria-hidden="true" />
            Kurang {formatRupiah(Math.abs(selisih))}
        </span>
    );
}

function KpiTile({
    label,
    nilai,
    subteks,
    ikon,
    extra,
}: {
    label: string;
    nilai: number;
    subteks?: string;
    ikon: React.ReactNode;
    extra?: React.ReactNode;
}) {
    return (
        <div className="bg-card border-border rounded-xl border p-4 shadow-xs">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-xs font-medium">{label}</span>
                <span className="text-primary">{ikon}</span>
            </div>
            <div className="text-foreground mt-2 text-2xl leading-8 font-semibold tracking-[-0.01em] tabular-nums">{formatRupiah(nilai)}</div>
            <div className="mt-2 flex flex-wrap items-center justify-between gap-2">
                {subteks && <span className="text-muted-foreground text-xs">{subteks}</span>}
                {extra}
            </div>
        </div>
    );
}

export function KartuPaguBidang({ bidangKode, ringkasan }: { bidangKode: KodeBidangRkat; ringkasan: RingkasanAlokasi }) {
    if (bidangKode === 'pengumpulan') {
        const p = ringkasan.pengumpulan;
        return (
            <div className="space-y-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <KpiTile
                        label="Target Pengumpulan (RAKORNAS)"
                        nilai={p.target}
                        subteks="Target L2 Neraca ditetapkan PKP"
                        ikon={<Scale className="h-4 w-4" aria-hidden="true" />}
                    />
                    <KpiTile
                        label="Total Rincian Neraca (L2)"
                        nilai={p.total_neraca}
                        subteks={`Luar Neraca (opsional): ${formatRupiah(p.total_luar_neraca)}`}
                        ikon={<Coins className="h-4 w-4" aria-hidden="true" />}
                        extra={<SelisihPill selisih={p.selisih} />}
                    />
                    <KpiTile
                        label="Estimasi Alokasi Penyaluran"
                        nilai={p.total_alokasi_penyaluran}
                        subteks={`Hak Amil SAU: ${formatRupiah(p.total_hak_amil)}`}
                        ikon={<Wallet className="h-4 w-4" aria-hidden="true" />}
                    />
                </div>
            </div>
        );
    }

    if (bidangKode === 'distribusi') {
        const d = ringkasan.distribusi;
        return (
            <div className="space-y-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <KpiTile
                        label="Total Pagu Penyaluran (L3 Non-Amil)"
                        nilai={d.total_pagu}
                        subteks="Sisa pengumpulan setelah potong Hak Amil"
                        ikon={<Wallet className="h-4 w-4" aria-hidden="true" />}
                    />
                    <KpiTile
                        label="Total Rincian Kegiatan Penyaluran"
                        nilai={d.total_terpakai}
                        subteks="Akumulasi seluruh 7 kelompok dana"
                        ikon={<Coins className="h-4 w-4" aria-hidden="true" />}
                        extra={<SelisihPill selisih={d.selisih} />}
                    />
                    <div className="bg-card border-border rounded-xl border p-4 shadow-xs">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-muted-foreground text-xs font-medium">Kesesuaian 7 Kelompok Dana</span>
                            <CheckCircle2 className="text-primary h-4 w-4" aria-hidden="true" />
                        </div>
                        <div className="text-foreground mt-2 text-2xl leading-8 font-semibold tabular-nums">
                            {d.kelompok.filter((k) => k.terpenuhi).length} / {d.kelompok.length} Kelompok
                        </div>
                        <p className="text-muted-foreground mt-2 text-xs">
                            Setiap kelompok dana wajib memiliki selisih Rp 0 sebelum diajukan.
                        </p>
                    </div>
                </div>

                <section className="bg-card border-border overflow-hidden rounded-xl border shadow-xs">
                    <div className="border-border border-b px-5 py-3">
                        <h2 className="text-foreground text-base font-semibold">Kontrol Pagu per Kelompok / Jenis Dana</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-subtle text-muted-foreground text-left text-[11px] font-semibold tracking-wider uppercase">
                                <tr>
                                    <th className="px-4 py-3">Kelompok Dana</th>
                                    <th className="px-4 py-3 text-right">Pagu Tersedia</th>
                                    <th className="px-4 py-3 text-right">Rincian Kegiatan</th>
                                    <th className="px-4 py-3 text-right">Selisih</th>
                                    <th className="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {d.kelompok.map((k) => (
                                    <tr key={k.kode} className="hover:bg-surface-subtle/60">
                                        <td className="px-4 py-3 font-medium">
                                            <div>{k.label}</div>
                                            <div className="text-muted-foreground text-xs">Pos: {k.kode_pos_l3.join(', ')}</div>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">{formatRupiah(k.pagu)}</td>
                                        <td className="px-4 py-3 text-right font-medium tabular-nums">{formatRupiah(k.terpakai)}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{formatRupiah(k.selisih)}</td>
                                        <td className="px-4 py-3">
                                            <SelisihPill selisih={k.selisih} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        );
    }

    const u = ringkasan.umum;
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <KpiTile
                label="Pagu Hak Amil"
                nilai={u.pagu_hak_amil}
                subteks="Potongan otomatis dari target Pengumpulan"
                ikon={<Coins className="h-4 w-4" aria-hidden="true" />}
            />
            <KpiTile
                label="Hibah APBN/APBD & Saldo Awal"
                nilai={u.tambahan_hibah_saldo}
                subteks="Diinput pada tab Pos Pelengkap (L6)"
                ikon={<Wallet className="h-4 w-4" aria-hidden="true" />}
            />
            <KpiTile
                label="Total Pagu Operasional SAU"
                nilai={u.total_pagu}
                subteks="Hak Amil + Hibah + Saldo Awal"
                ikon={<Scale className="h-4 w-4" aria-hidden="true" />}
            />
            <KpiTile
                label="Total Rincian Belanja (L7)"
                nilai={u.terpakai}
                subteks="Wajib sama dengan Total Pagu Operasional"
                ikon={<CheckCircle2 className="h-4 w-4" aria-hidden="true" />}
                extra={<SelisihPill selisih={u.selisih} />}
            />
        </div>
    );
}
