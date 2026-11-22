import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';

const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const NAMA_HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

function formatTanggalId(tanggal: Date | null): string {
    if (!tanggal || Number.isNaN(tanggal.getTime())) {
        return '';
    }
    const dd = String(tanggal.getDate()).padStart(2, '0');
    const mm = String(tanggal.getMonth() + 1).padStart(2, '0');
    const yyyy = tanggal.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

function parseTanggalId(teks: string): Date | null {
    const cocok = teks.trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!cocok) {
        return null;
    }
    const hari = Number(cocok[1]);
    const bulan = Number(cocok[2]);
    const tahun = Number(cocok[3]);
    if (bulan < 1 || bulan > 12 || hari < 1 || hari > 31) {
        return null;
    }
    const hasil = new Date(tahun, bulan - 1, hari);
    if (hasil.getFullYear() !== tahun || hasil.getMonth() !== bulan - 1 || hasil.getDate() !== hari) {
        return null;
    }
    return hasil;
}

export interface CalendarProps {
    selected?: Date | null;
    onSelect?: (date: Date) => void;
    className?: string;
}

export function Calendar({ selected, onSelect, className }: CalendarProps) {
    const [bulanAktif, setBulanAktif] = React.useState(() => {
        const acuan = selected ?? new Date();
        return new Date(acuan.getFullYear(), acuan.getMonth(), 1);
    });

    const tahun = bulanAktif.getFullYear();
    const bulan = bulanAktif.getMonth();
    const hariPertama = (new Date(tahun, bulan, 1).getDay() + 6) % 7;
    const jumlahHari = new Date(tahun, bulan + 1, 0).getDate();

    const selTanggal: (Date | null)[] = [
        ...Array.from({ length: hariPertama }, () => null),
        ...Array.from({ length: jumlahHari }, (_, idx) => new Date(tahun, bulan, idx + 1)),
    ];

    const samaDenganTerpilih = (tgl: Date) =>
        Boolean(selected && tgl.getFullYear() === selected.getFullYear() && tgl.getMonth() === selected.getMonth() && tgl.getDate() === selected.getDate());

    return (
        <div className={cn('bg-card border-border w-72 rounded-xl border p-3 shadow-lg', className)}>
            <div className="mb-2 flex items-center justify-between">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    aria-label="Bulan sebelumnya"
                    onClick={() => setBulanAktif(new Date(tahun, bulan - 1, 1))}
                >
                    <ChevronLeft className="h-4 w-4" aria-hidden="true" />
                </Button>
                <span className="text-sm font-semibold">
                    {NAMA_BULAN[bulan]} {tahun}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    aria-label="Bulan berikutnya"
                    onClick={() => setBulanAktif(new Date(tahun, bulan + 1, 1))}
                >
                    <ChevronRight className="h-4 w-4" aria-hidden="true" />
                </Button>
            </div>

            <div className="grid grid-cols-7 gap-1 text-center">
                {NAMA_HARI.map((h) => (
                    <div key={h} className="text-muted-foreground py-1 text-[11px] font-semibold">
                        {h}
                    </div>
                ))}
                {selTanggal.map((tgl, idx) =>
                    tgl ? (
                        <button
                            key={tgl.toISOString()}
                            type="button"
                            onClick={() => onSelect?.(tgl)}
                            className={cn(
                                'hover:bg-accent focus-visible:ring-ring flex h-8 w-8 items-center justify-center rounded-md text-xs font-medium tabular-nums focus-visible:ring-2 focus-visible:outline-hidden',
                                samaDenganTerpilih(tgl) ? 'bg-primary text-primary-foreground hover:bg-primary' : 'text-foreground',
                            )}
                        >
                            {tgl.getDate()}
                        </button>
                    ) : (
                        <div key={`kosong-${idx}`} />
                    ),
                )}
            </div>
        </div>
    );
}

export interface DatePickerIdProps {
    value?: Date | null;
    onChange: (date: Date | null) => void;
    placeholder?: string;
    disabled?: boolean;
    id?: string;
    className?: string;
}

export function DatePickerId({ value = null, onChange, placeholder = 'dd/mm/yyyy', disabled = false, id, className }: DatePickerIdProps) {
    const [teks, setTeks] = React.useState(formatTanggalId(value));
    const [terbuka, setTerbuka] = React.useState(false);
    const wadahRef = React.useRef<HTMLDivElement>(null);

    React.useEffect(() => {
        setTeks(formatTanggalId(value));
    }, [value]);

    React.useEffect(() => {
        if (!terbuka) {
            return;
        }
        const klikDiLuar = (e: MouseEvent) => {
            if (wadahRef.current && !wadahRef.current.contains(e.target as Node)) {
                setTerbuka(false);
            }
        };
        document.addEventListener('mousedown', klikDiLuar);
        return () => document.removeEventListener('mousedown', klikDiLuar);
    }, [terbuka]);

    const tanganiKetik = (e: React.ChangeEvent<HTMLInputElement>) => {
        const nilai = e.target.value;
        setTeks(nilai);
        if (nilai.trim() === '') {
            onChange(null);
            return;
        }
        const parsed = parseTanggalId(nilai);
        if (parsed) {
            onChange(parsed);
        }
    };

    return (
        <div ref={wadahRef} className={cn('relative w-full', className)}>
            <div className="relative flex items-center">
                <Input
                    id={id}
                    type="text"
                    inputMode="numeric"
                    value={teks}
                    onChange={tanganiKetik}
                    placeholder={placeholder}
                    disabled={disabled}
                    className="h-9 pr-9 tabular-nums"
                />
                <button
                    type="button"
                    disabled={disabled}
                    aria-label="Pilih tanggal dari kalender"
                    onClick={() => setTerbuka((prev) => !prev)}
                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute right-2 rounded-md p-1 focus-visible:ring-2 focus-visible:outline-hidden"
                >
                    <CalendarDays className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            {terbuka && (
                <div className="absolute right-0 z-50 mt-1.5">
                    <Calendar
                        selected={value}
                        onSelect={(tgl) => {
                            onChange(tgl);
                            setTeks(formatTanggalId(tgl));
                            setTerbuka(false);
                        }}
                    />
                </div>
            )}
        </div>
    );
}
