import { Button } from '@/components/ui/button';
import { DatePickerId } from '@/components/ui/calendar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type JenisPeriode = 'harian' | 'bulanan' | 'triwulan' | 'semester' | 'tahunan';

export interface PeriodeFilter {
    jenis: JenisPeriode;
    tahun: number;
    bulan?: number;
    triwulan?: number;
    semester?: number;
    tanggal?: string;
}

const pilihanCepat: { kode: JenisPeriode; label: string }[] = [
    { kode: 'harian', label: 'Hari ini' },
    { kode: 'bulanan', label: 'Bulan ini' },
    { kode: 'triwulan', label: 'Triwulan' },
    { kode: 'semester', label: 'Semester' },
    { kode: 'tahunan', label: 'Tahun' },
];

const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

export interface PemilihPeriodeProps {
    value: PeriodeFilter;
    onChange: (nilai: PeriodeFilter) => void;
    tahunTersedia?: number[];
    className?: string;
}

export function PemilihPeriode({ value, onChange, tahunTersedia, className }: PemilihPeriodeProps) {
    const tahunSekarang = new Date().getFullYear();
    const daftarTahun = tahunTersedia ?? Array.from({ length: 6 }, (_, i) => tahunSekarang - 4 + i);
    const tanggalObjek = value.tanggal ? new Date(value.tanggal) : new Date();

    const pilihJenis = (jenis: JenisPeriode) => {
        const sekarang = new Date();
        onChange({
            jenis,
            tahun: value.tahun || sekarang.getFullYear(),
            bulan: value.bulan ?? sekarang.getMonth() + 1,
            triwulan: value.triwulan ?? Math.floor(sekarang.getMonth() / 3) + 1,
            semester: value.semester ?? (sekarang.getMonth() < 6 ? 1 : 2),
            tanggal: value.tanggal ?? sekarang.toISOString().slice(0, 10),
        });
    };

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            <div role="group" aria-label="Pilihan periode cepat" className="bg-surface-subtle border-border inline-flex rounded-lg border p-0.5">
                {pilihanCepat.map((item) => {
                    const aktif = value.jenis === item.kode;
                    return (
                        <Button
                            key={item.kode}
                            type="button"
                            variant={aktif ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => pilihJenis(item.kode)}
                            className={cn('h-8 px-2.5 text-xs', !aktif && 'text-muted-foreground hover:text-foreground')}
                        >
                            {item.label}
                        </Button>
                    );
                })}
            </div>

            {value.jenis === 'harian' && (
                <div className="w-40">
                    <DatePickerId
                        value={Number.isNaN(tanggalObjek.getTime()) ? new Date() : tanggalObjek}
                        onChange={(tgl) => {
                            if (tgl) {
                                const yyyy = tgl.getFullYear();
                                const mm = String(tgl.getMonth() + 1).padStart(2, '0');
                                const dd = String(tgl.getDate()).padStart(2, '0');
                                onChange({ ...value, tahun: yyyy, tanggal: `${yyyy}-${mm}-${dd}` });
                            }
                        }}
                    />
                </div>
            )}

            {value.jenis === 'bulanan' && (
                <Select value={String(value.bulan ?? 1)} onValueChange={(b) => onChange({ ...value, bulan: Number(b) })}>
                    <SelectTrigger className="h-9 w-36" aria-label="Pilih bulan">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {namaBulan.map((nama, idx) => (
                            <SelectItem key={nama} value={String(idx + 1)}>
                                {nama}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {value.jenis === 'triwulan' && (
                <Select value={String(value.triwulan ?? 1)} onValueChange={(tw) => onChange({ ...value, triwulan: Number(tw) })}>
                    <SelectTrigger className="h-9 w-32" aria-label="Pilih triwulan">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {[1, 2, 3, 4].map((tw) => (
                            <SelectItem key={tw} value={String(tw)}>
                                Triwulan {tw}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {value.jenis === 'semester' && (
                <Select value={String(value.semester ?? 1)} onValueChange={(s) => onChange({ ...value, semester: Number(s) })}>
                    <SelectTrigger className="h-9 w-32" aria-label="Pilih semester">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="1">Semester I</SelectItem>
                        <SelectItem value="2">Semester II</SelectItem>
                    </SelectContent>
                </Select>
            )}

            {value.jenis !== 'harian' && (
                <Select value={String(value.tahun)} onValueChange={(th) => onChange({ ...value, tahun: Number(th) })}>
                    <SelectTrigger className="h-9 w-24" aria-label="Pilih tahun">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {daftarTahun.map((th) => (
                            <SelectItem key={th} value={String(th)}>
                                {th}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}
