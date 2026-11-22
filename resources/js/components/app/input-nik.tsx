import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import * as React from 'react';

export function maskingNik(nik: string): string {
    const bersih = nik.replace(/\D+/g, '');
    if (bersih.length <= 8) {
        return bersih;
    }
    const awal = bersih.slice(0, 4);
    const akhir = bersih.slice(-4);
    return `${awal}${'*'.repeat(Math.max(4, bersih.length - 8))}${akhir}`;
}

export interface InputNIKProps {
    id?: string;
    label?: string;
    value: string;
    onChange: (nik: string) => void;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    helperText?: string;
    className?: string;
}

export function InputNIK({
    id,
    label = 'NIK',
    value,
    onChange,
    required = false,
    disabled = false,
    error,
    helperText,
    className,
}: InputNIKProps) {
    const generatedId = React.useId();
    const inputId = id ?? generatedId;
    const [galatBlur, setGalatBlur] = React.useState<string | null>(null);

    const digitSaatIni = value.replace(/\D+/g, '').slice(0, 16);
    const pesanGalat = error ?? galatBlur;

    const tanganiKetik = (e: React.ChangeEvent<HTMLInputElement>) => {
        const bersih = e.target.value.replace(/\D+/g, '').slice(0, 16);
        if (bersih.length === 16 || bersih.length === 0) {
            setGalatBlur(null);
        }
        onChange(bersih);
    };

    const tanganiBlur = () => {
        if (digitSaatIni.length > 0 && digitSaatIni.length !== 16) {
            setGalatBlur('NIK harus terdiri dari 16 digit angka.');
        } else if (required && digitSaatIni.length === 0) {
            setGalatBlur('NIK wajib diisi sebanyak 16 digit angka.');
        } else {
            setGalatBlur(null);
        }
    };

    return (
        <div className={cn('space-y-1.5', className)}>
            <div className="flex items-center justify-between">
                <Label htmlFor={inputId} className="text-foreground text-sm font-medium">
                    {label}
                    {required && <span className="text-muted-foreground ml-1 font-normal">(wajib)</span>}
                </Label>
                <span className="text-muted-foreground text-xs tabular-nums">{digitSaatIni.length}/16 digit</span>
            </div>

            <Input
                id={inputId}
                type="text"
                inputMode="numeric"
                maxLength={16}
                value={digitSaatIni}
                onChange={tanganiKetik}
                onBlur={tanganiBlur}
                placeholder="Masukkan 16 digit NIK"
                disabled={disabled}
                aria-invalid={Boolean(pesanGalat)}
                className={cn('h-9 tabular-nums', pesanGalat && 'border-destructive focus-visible:ring-destructive/30')}
            />

            {pesanGalat ? (
                <p role="alert" className="text-destructive text-xs font-medium">
                    {pesanGalat}
                </p>
            ) : (
                helperText && <p className="text-muted-foreground text-xs">{helperText}</p>
            )}
        </div>
    );
}
