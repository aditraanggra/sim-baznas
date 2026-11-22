import { cn } from '@/lib/utils';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-react';
import * as React from 'react';

type ToastTipe = 'success' | 'info' | 'warning' | 'error';

export interface ToastItem {
    id: string;
    tipe: ToastTipe;
    pesan: string;
    deskripsi?: string;
    durasiMs?: number;
}

type Listener = (items: ToastItem[]) => void;

const listeners = new Set<Listener>();
let daftarToast: ToastItem[] = [];

function beriTahu(): void {
    const salinan = [...daftarToast];
    listeners.forEach((fn) => fn(salinan));
}

function tambahToast(tipe: ToastTipe, pesan: string, opsi?: { deskripsi?: string; durasiMs?: number }): string {
    const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    const durasi = Math.min(opsi?.durasiMs ?? 4000, 4000);
    const baru: ToastItem = { id, tipe, pesan, deskripsi: opsi?.deskripsi, durasiMs: durasi };

    daftarToast = [...daftarToast.slice(-3), baru];
    beriTahu();

    if (typeof window !== 'undefined') {
        window.setTimeout(() => {
            hapusToast(id);
        }, durasi);
    }

    return id;
}

export function hapusToast(id: string): void {
    daftarToast = daftarToast.filter((item) => item.id !== id);
    beriTahu();
}

export const toast = {
    success: (pesan: string, opsi?: { deskripsi?: string; durasiMs?: number }) => tambahToast('success', pesan, opsi),
    info: (pesan: string, opsi?: { deskripsi?: string; durasiMs?: number }) => tambahToast('info', pesan, opsi),
    warning: (pesan: string, opsi?: { deskripsi?: string; durasiMs?: number }) => tambahToast('warning', pesan, opsi),
    error: (pesan: string, opsi?: { deskripsi?: string; durasiMs?: number }) => tambahToast('error', pesan, opsi),
    dismiss: hapusToast,
};

const gayaTipe: Record<ToastTipe, { wadah: string; ikon: React.ComponentType<{ className?: string }> }> = {
    success: { wadah: 'border-success/30 bg-card text-foreground', ikon: CheckCircle2 },
    info: { wadah: 'border-info/30 bg-card text-foreground', ikon: Info },
    warning: { wadah: 'border-warning/30 bg-card text-foreground', ikon: AlertTriangle },
    error: { wadah: 'border-destructive/30 bg-card text-foreground', ikon: AlertCircle },
};

const warnaIkon: Record<ToastTipe, string> = {
    success: 'text-success',
    info: 'text-info',
    warning: 'text-warning',
    error: 'text-destructive',
};

export function Toaster() {
    const [items, setItems] = React.useState<ToastItem[]>(daftarToast);

    React.useEffect(() => {
        listeners.add(setItems);
        return () => {
            listeners.delete(setItems);
        };
    }, []);

    if (items.length === 0) {
        return null;
    }

    return (
        <div
            aria-live="polite"
            aria-label="Notifikasi aksi"
            className="pointer-events-none fixed right-4 bottom-4 z-50 flex w-full max-w-sm flex-col gap-2"
        >
            {items.map((item) => {
                const Ikon = gayaTipe[item.tipe].ikon;
                return (
                    <div
                        key={item.id}
                        role="status"
                        className={cn(
                            'pointer-events-auto flex items-start gap-2.5 rounded-xl border p-3.5 shadow-lg',
                            gayaTipe[item.tipe].wadah,
                        )}
                    >
                        <Ikon className={cn('mt-0.5 h-4 w-4 shrink-0', warnaIkon[item.tipe])} aria-hidden="true" />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium">{item.pesan}</p>
                            {item.deskripsi && <p className="text-muted-foreground mt-0.5 text-xs">{item.deskripsi}</p>}
                        </div>
                        <button
                            type="button"
                            onClick={() => hapusToast(item.id)}
                            aria-label="Tutup notifikasi"
                            className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-md p-1 focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <X className="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
