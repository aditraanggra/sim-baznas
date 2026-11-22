import { cn } from '@/lib/utils';

export interface TimelineStep {
    id: string | number;
    status: string;
    waktu: string;
    pelaku?: string | null;
    catatan?: string | null;
    aktif?: boolean;
    selesai?: boolean;
}

export interface TimelineProps {
    steps: TimelineStep[];
    emptyText?: string;
    className?: string;
}

export function Timeline({ steps, emptyText = 'Belum ada riwayat status dokumen.', className }: TimelineProps) {
    if (steps.length === 0) {
        return <p className="text-muted-foreground py-4 text-sm">{emptyText}</p>;
    }

    return (
        <ol className={cn('space-y-0', className)}>
            {steps.map((langkah, idx) => {
                const terakhir = idx === steps.length - 1;
                return (
                    <li key={langkah.id} className="relative flex gap-3 pb-5 last:pb-0">
                        {!terakhir && (
                            <span
                                aria-hidden="true"
                                className="bg-border absolute top-3.5 left-[7px] h-[calc(100%-10px)] w-px"
                            />
                        )}
                        <span
                            aria-hidden="true"
                            className={cn(
                                'relative z-10 mt-1 h-4 w-4 shrink-0 rounded-full border-2',
                                langkah.aktif
                                    ? 'border-primary bg-primary-soft'
                                    : langkah.selesai
                                      ? 'border-success bg-success-soft'
                                      : 'border-border-strong bg-card',
                            )}
                        />
                        <div className="min-w-0 flex-1 space-y-0.5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="text-foreground text-sm font-medium">{langkah.status}</p>
                                <time className="text-muted-foreground text-xs tabular-nums">{langkah.waktu}</time>
                            </div>
                            {langkah.pelaku && <p className="text-muted-foreground text-xs">Oleh: {langkah.pelaku}</p>}
                            {langkah.catatan && (
                                <p className="bg-surface-subtle text-foreground mt-1 rounded-md px-2.5 py-1.5 text-xs">{langkah.catatan}</p>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
