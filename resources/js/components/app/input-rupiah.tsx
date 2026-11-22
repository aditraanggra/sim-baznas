import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatAngka } from '@/lib/format';
import { cn } from '@/lib/utils';
import * as React from 'react';

function angkaKeKata(n: number): string {
    const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    if (n === 0) return 'nol';
    if (n < 12) return satuan[n];
    if (n < 20) return `${angkaKeKata(n - 10)} belas`;
    if (n < 100) return `${angkaKeKata(Math.floor(n / 10))} puluh${n % 10 ? ` ${angkaKeKata(n % 10)}` : ''}`;
    if (n < 200) return `seratus${n - 100 ? ` ${angkaKeKata(n - 100)}` : ''}`;
    if (n < 1000) return `${angkaKeKata(Math.floor(n / 100))} ratus${n % 100 ? ` ${angkaKeKata(n % 100)}` : ''}`;
    if (n < 2000) return `seribu${n - 1000 ? ` ${angkaKeKata(n - 1000)}` : ''}`;
    if (n < 1_000_000) return `${angkaKeKata(Math.floor(n / 1000))} ribu${n % 1000 ? ` ${angkaKeKata(n % 1000)}` : ''}`;
    if (n < 1_000_000_000) return `${angkaKeKata(Math.floor(n / 1_000_000))} juta${n % 1_000_000 ? ` ${angkaKeKata(n % 1_000_000)}` : ''}`;
    if (n < 1_000_000_000_000) {
        return `${angkaKeKata(Math.floor(n / 1_000_000_000))} miliar${n % 1_000_000_000 ? ` ${angkaKeKata(n % 1_000_000_000)}` : ''}`;
    }
    return `${angkaKeKata(Math.floor(n / 1_000_000_000_000))} triliun${n % 1_000_000_000_000 ? ` ${angkaKeKata(n % 1_000_000_000_000)}` : ''}`;
}

export interface InputRupiahProps {
    id?: string;
    label?: string;
    value: number | null;
    onChange: (nilai: number) => void;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    helperText?: string;
    placeholder?: string;
    tampilkanTerbilang?: boolean;
    className?: string;
}

export function InputRupiah({
    id,
    label,
    value,
    onChange,
    required = false,
    disabled = false,
    error,
    helperText,
    placeholder = '0',
    tampilkanTerbilang = false,
    className,
}: InputRupiahProps) {
    const generatedId = React.useId();
    const inputId = id ?? generatedId;
    const nilaiTerformat = value === null || value === undefined ? '' : formatAngka(value);

    const tanganiUbah = (e: React.ChangeEvent<HTMLInputElement>) => {
        const hanyaDigit = e.target.value.replace(/\D+/g, '');
        if (hanyaDigit === '') {
            onChange(0);
            return;
        }
        const angka = Number.parseInt(hanyaDigit, 10);
        onChange(Number.isNaN(angka) ? 0 : angka);
    };

    return (
        <div className={cn('space-y-1.5', className)}>
            {label && (
                <Label htmlFor={inputId} className="text-foreground text-sm font-medium">
                    {label}
                    {required && <span className="text-muted-foreground ml-1 font-normal">(wajib)</span>}
                </Label>
            )}

            <div className="relative flex items-center">
                <span className="text-muted-foreground pointer-events-none absolute left-3 text-sm font-medium select-none">Rp</span>
                <Input
                    id={inputId}
                    type="text"
                    inputMode="numeric"
                    value={nilaiTerformat}
                    onChange={tanganiUbah}
                    placeholder={placeholder}
                    disabled={disabled}
                    aria-invalid={Boolean(error)}
                    className={cn(
                        'h-9 pl-10 text-right tabular-nums',
                        error && 'border-destructive focus-visible:ring-destructive/30',
                    )}
                />
            </div>

            {error ? (
                <p role="alert" className="text-destructive text-xs font-medium">
                    {error}
                </p>
            ) : (
                <>
                    {helperText && <p className="text-muted-foreground text-xs">{helperText}</p>}
                    {tampilkanTerbilang && value !== null && value > 0 && (
                        <p className="text-muted-foreground text-xs italic">
                            Terbilang: {`${angkaKeKata(value).charAt(0).toUpperCase()}${angkaKeKata(value).slice(1)}`} rupiah
                        </p>
                    )}
                </>
            )}
        </div>
    );
}
