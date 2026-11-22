import { formatAngka, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import * as React from 'react';

export interface ChartDatum {
    label: string;
    nilai: number;
}

function formatRingkasSumbu(nilai: number): string {
    if (Math.abs(nilai) >= 1_000_000_000) {
        return `${(nilai / 1_000_000_000).toFixed(1).replace('.', ',')}M`;
    }
    if (Math.abs(nilai) >= 1_000_000) {
        return `${(nilai / 1_000_000).toFixed(1).replace('.', ',')}Jt`;
    }
    if (Math.abs(nilai) >= 1_000) {
        return `${Math.round(nilai / 1_000)}Rb`;
    }
    return String(nilai);
}

export interface LineTrendChartProps {
    title: string;
    data: ChartDatum[];
    isCurrency?: boolean;
    height?: number;
    className?: string;
}

export function LineTrendChart({ title, data, isCurrency = false, height = 220, className }: LineTrendChartProps) {
    const [aktifIdx, setAktifIdx] = React.useState<number | null>(null);
    const idGrad = React.useId();

    if (data.length === 0) {
        return (
            <div className={cn('bg-card border-border rounded-xl border p-4', className)}>
                <h3 className="text-foreground text-sm font-semibold">{title}</h3>
                <p className="text-muted-foreground py-8 text-center text-xs">Belum ada data grafik untuk periode ini.</p>
            </div>
        );
    }

    const lebar = 560;
    const tinggi = height;
    const padKiri = 46;
    const padKanan = 16;
    const padAtas = 20;
    const padBawah = 28;
    const areaLebar = lebar - padKiri - padKanan;
    const areaTinggi = tinggi - padAtas - padBawah;

    const maksNilai = Math.max(...data.map((d) => d.nilai), 1);
    const titik = data.map((d, idx) => {
        const x = data.length === 1 ? padKiri + areaLebar / 2 : padKiri + (idx / (data.length - 1)) * areaLebar;
        const y = padAtas + areaTinggi - (d.nilai / maksNilai) * areaTinggi;
        return { x, y, ...d };
    });

    const garisPath = titik.map((t, idx) => `${idx === 0 ? 'M' : 'L'} ${t.x.toFixed(1)} ${t.y.toFixed(1)}`).join(' ');
    const areaPath = `${garisPath} L ${titik[titik.length - 1].x.toFixed(1)} ${(padAtas + areaTinggi).toFixed(1)} L ${titik[0].x.toFixed(1)} ${(padAtas + areaTinggi).toFixed(1)} Z`;

    return (
        <div className={cn('bg-card border-border rounded-xl border p-4', className)}>
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-foreground text-sm font-semibold">{title}</h3>
                {aktifIdx !== null && titik[aktifIdx] && (
                    <span className="bg-surface-subtle text-foreground rounded-md px-2 py-0.5 text-xs font-medium tabular-nums">
                        {titik[aktifIdx].label}: {isCurrency ? formatRupiah(titik[aktifIdx].nilai) : formatAngka(titik[aktifIdx].nilai)}
                    </span>
                )}
            </div>

            <svg viewBox={`0 0 ${lebar} ${tinggi}`} role="img" aria-label={title} className="w-full overflow-visible">
                <defs>
                    <linearGradient id={idGrad} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="var(--chart-1)" stopOpacity="0.18" />
                        <stop offset="100%" stopColor="var(--chart-1)" stopOpacity="0" />
                    </linearGradient>
                </defs>

                {[0, 0.5, 1].map((fraksi) => {
                    const y = padAtas + areaTinggi * (1 - fraksi);
                    const nilaiGaris = Math.round(maksNilai * fraksi);
                    return (
                        <g key={fraksi}>
                            <line x1={padKiri} y1={y} x2={lebar - padKanan} y2={y} stroke="var(--border)" strokeWidth="1" />
                            <text x={padKiri - 8} y={y + 4} textAnchor="end" fontSize="11" fill="var(--muted-foreground)">
                                {formatRingkasSumbu(nilaiGaris)}
                            </text>
                        </g>
                    );
                })}

                <path d={areaPath} fill={`url(#${idGrad})`} />
                <path d={garisPath} fill="none" stroke="var(--chart-1)" strokeWidth="2" />

                {titik.map((t, idx) => (
                    <g key={`${t.label}-${idx}`}>
                        {aktifIdx === idx && (
                            <line x1={t.x} y1={padAtas} x2={t.x} y2={padAtas + areaTinggi} stroke="var(--border-strong)" strokeDasharray="3 3" />
                        )}
                        <circle
                            cx={t.x}
                            cy={t.y}
                            r={aktifIdx === idx ? 5 : 3.5}
                            fill={aktifIdx === idx ? 'var(--chart-1)' : 'var(--card)'}
                            stroke="var(--chart-1)"
                            strokeWidth="2"
                            tabIndex={0}
                            onMouseEnter={() => setAktifIdx(idx)}
                            onMouseLeave={() => setAktifIdx(null)}
                            onFocus={() => setAktifIdx(idx)}
                            onBlur={() => setAktifIdx(null)}
                            className="cursor-pointer outline-hidden"
                        />
                        <text x={t.x} y={tinggi - 6} textAnchor="middle" fontSize="11" fill="var(--muted-foreground)">
                            {t.label}
                        </text>
                    </g>
                ))}
            </svg>
        </div>
    );
}

export interface BarComparisonChartProps {
    title: string;
    data: ChartDatum[];
    isCurrency?: boolean;
    height?: number;
    className?: string;
}

export function BarComparisonChart({ title, data, isCurrency = false, height = 210, className }: BarComparisonChartProps) {
    if (data.length === 0) {
        return (
            <div className={cn('bg-card border-border rounded-xl border p-4', className)}>
                <h3 className="text-foreground text-sm font-semibold">{title}</h3>
                <p className="text-muted-foreground py-8 text-center text-xs">Belum ada data grafik untuk periode ini.</p>
            </div>
        );
    }

    const maksNilai = Math.max(...data.map((d) => d.nilai), 1);

    return (
        <div className={cn('bg-card border-border rounded-xl border p-4', className)}>
            <h3 className="text-foreground mb-4 text-sm font-semibold">{title}</h3>
            <div className="flex items-end gap-2.5" style={{ height }}>
                {data.map((item) => {
                    const persenTinggi = Math.max(4, Math.round((item.nilai / maksNilai) * 100));
                    const tertinggi = item.nilai === maksNilai && item.nilai > 0;
                    return (
                        <div key={item.label} className="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                            <span className="text-muted-foreground text-[11px] font-medium tabular-nums">
                                {isCurrency ? formatRingkasSumbu(item.nilai) : formatAngka(item.nilai)}
                            </span>
                            <div
                                className={cn('w-full max-w-[40px] rounded-t-md', tertinggi ? 'bg-primary' : 'bg-chart-bar-idle')}
                                style={{ height: `${persenTinggi}%` }}
                                title={`${item.label}: ${isCurrency ? formatRupiah(item.nilai) : formatAngka(item.nilai)}`}
                            />
                            <span className="text-muted-foreground truncate text-xs">{item.label}</span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export interface StackedSegment {
    kode: string;
    label: string;
    nilai: number;
    warna: string;
}

export interface StackedBarChartProps {
    title: string;
    segments: StackedSegment[];
    isCurrency?: boolean;
    className?: string;
}

export function StackedBarChart({ title, segments, isCurrency = true, className }: StackedBarChartProps) {
    const segmenTerbatas = segments.slice(0, 5);
    const total = segmenTerbatas.reduce((acc, s) => acc + Math.max(0, s.nilai), 0);

    return (
        <div className={cn('bg-card border-border rounded-xl border p-4', className)}>
            <div className="mb-3 flex items-center justify-between gap-2">
                <h3 className="text-foreground text-sm font-semibold">{title}</h3>
                <span className="text-foreground text-sm font-semibold tabular-nums">
                    {isCurrency ? formatRupiah(total) : formatAngka(total)}
                </span>
            </div>

            {total === 0 ? (
                <p className="text-muted-foreground py-4 text-xs">Belum ada komposisi nilai untuk ditampilkan.</p>
            ) : (
                <>
                    <div className="bg-surface-subtle flex h-4 w-full overflow-hidden rounded-md">
                        {segmenTerbatas.map((seg) => {
                            const lebar = (Math.max(0, seg.nilai) / total) * 100;
                            if (lebar <= 0) {
                                return null;
                            }
                            return (
                                <div
                                    key={seg.kode}
                                    style={{ width: `${lebar}%`, backgroundColor: seg.warna }}
                                    title={`${seg.label}: ${isCurrency ? formatRupiah(seg.nilai) : formatAngka(seg.nilai)}`}
                                />
                            );
                        })}
                    </div>

                    <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {segmenTerbatas.map((seg) => (
                            <div key={seg.kode} className="flex items-center justify-between text-xs">
                                <span className="text-muted-foreground flex items-center gap-2">
                                    <span className="h-2.5 w-2.5 rounded-xs" style={{ backgroundColor: seg.warna }} aria-hidden="true" />
                                    <span>{seg.label}</span>
                                </span>
                                <span className="text-foreground font-medium tabular-nums">
                                    {isCurrency ? formatRupiah(seg.nilai) : formatAngka(seg.nilai)}
                                </span>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
