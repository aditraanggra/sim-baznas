import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { FileText, Trash2, Upload } from 'lucide-react';
import * as React from 'react';

function formatUkuran(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

export interface UnggahBerkasProps {
    id?: string;
    label?: string;
    accept?: string;
    maxSizeMb?: number;
    value: File | null;
    onChange: (file: File | null) => void;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    progress?: number | null;
    className?: string;
}

export function UnggahBerkas({
    id,
    label = 'Unggah Berkas',
    accept = '.pdf,.jpg,.jpeg,.png',
    maxSizeMb = 10,
    value,
    onChange,
    required = false,
    disabled = false,
    error,
    progress = null,
    className,
}: UnggahBerkasProps) {
    const generatedId = React.useId();
    const inputId = id ?? generatedId;
    const inputRef = React.useRef<HTMLInputElement>(null);
    const [seretAktif, setSeretAktif] = React.useState(false);
    const [galatLokal, setGalatLokal] = React.useState<string | null>(null);

    const pesanGalat = error ?? galatLokal;

    const prosesBerkas = (berkas: File | undefined) => {
        if (!berkas) {
            return;
        }
        const batasBytes = maxSizeMb * 1024 * 1024;
        if (berkas.size > batasBytes) {
            setGalatLokal(`Ukuran berkas maksimal ${maxSizeMb} MB.`);
            return;
        }
        setGalatLokal(null);
        onChange(berkas);
    };

    const tanganiDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setSeretAktif(false);
        if (disabled) {
            return;
        }
        prosesBerkas(e.dataTransfer.files[0]);
    };

    return (
        <div className={cn('space-y-1.5', className)}>
            {label && (
                <Label htmlFor={inputId} className="text-foreground text-sm font-medium">
                    {label}
                    {required && <span className="text-muted-foreground ml-1 font-normal">(wajib)</span>}
                </Label>
            )}

            <input
                ref={inputRef}
                id={inputId}
                type="file"
                accept={accept}
                disabled={disabled}
                onChange={(e) => prosesBerkas(e.target.files?.[0])}
                className="sr-only"
            />

            {!value ? (
                <div
                    onDragOver={(e) => {
                        e.preventDefault();
                        if (!disabled) setSeretAktif(true);
                    }}
                    onDragLeave={() => setSeretAktif(false)}
                    onDrop={tanganiDrop}
                    className={cn(
                        'border-border-strong bg-surface-subtle flex flex-col items-center justify-center rounded-xl border border-dashed px-4 py-6 text-center transition-colors',
                        seretAktif && 'border-primary bg-primary-soft/50',
                        pesanGalat && 'border-destructive',
                    )}
                >
                    <Upload className="text-muted-foreground mb-2 h-5 w-5" aria-hidden="true" />
                    <p className="text-foreground text-sm font-medium">Seret berkas ke area ini atau pilih dari perangkat</p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Format yang didukung: {accept.replaceAll('.', '').toUpperCase()} (maksimal {maxSizeMb} MB)
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={disabled}
                        onClick={() => inputRef.current?.click()}
                        className="mt-3 h-9"
                    >
                        Pilih Berkas
                    </Button>
                </div>
            ) : (
                <div className="bg-card border-border flex items-center justify-between gap-3 rounded-xl border p-3">
                    <div className="flex min-w-0 items-center gap-2.5">
                        <div className="bg-primary-soft text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                            <FileText className="h-4 w-4" aria-hidden="true" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-foreground truncate text-sm font-medium">{value.name}</p>
                            <p className="text-muted-foreground text-xs">{formatUkuran(value.size)}</p>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={disabled}
                        aria-label="Hapus berkas"
                        onClick={() => {
                            if (inputRef.current) inputRef.current.value = '';
                            setGalatLokal(null);
                            onChange(null);
                        }}
                        className="text-destructive hover:bg-destructive-soft hover:text-destructive h-9 px-2.5"
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                    </Button>
                </div>
            )}

            {progress !== null && progress > 0 && progress < 100 && (
                <div className="space-y-1">
                    <div className="bg-surface-subtle h-1.5 w-full overflow-hidden rounded-full">
                        <div className="bg-primary h-full transition-all" style={{ width: `${progress}%` }} />
                    </div>
                    <p className="text-muted-foreground text-xs tabular-nums">Mengunggah {progress}%</p>
                </div>
            )}

            {pesanGalat && (
                <p role="alert" className="text-destructive text-xs font-medium">
                    {pesanGalat}
                </p>
            )}
        </div>
    );
}
