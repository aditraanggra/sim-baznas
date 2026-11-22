import { cn } from '@/lib/utils';

export type StatusVariant =
    | 'draft'
    | 'diproses'
    | 'disetujui'
    | 'final'
    | 'dibatalkan'
    | 'ditolak'
    | 'aktif'
    | 'habis'
    | 'info'
    | 'warning'
    | 'success'
    | 'danger';

const gayaVarian: Record<StatusVariant, { wadah: string; titik: string; labelDefault: string }> = {
    draft: { wadah: 'bg-surface-subtle text-muted-foreground border-border', titik: 'bg-ink-muted', labelDefault: 'Draft' },
    diproses: { wadah: 'bg-info-soft text-info border-info/20', titik: 'bg-info', labelDefault: 'Diproses' },
    info: { wadah: 'bg-info-soft text-info border-info/20', titik: 'bg-info', labelDefault: 'Informasi' },
    warning: { wadah: 'bg-warning-soft text-warning border-warning/25', titik: 'bg-warning', labelDefault: 'Menunggu' },
    disetujui: { wadah: 'bg-success-soft text-success border-success/20', titik: 'bg-success', labelDefault: 'Disetujui' },
    final: { wadah: 'bg-success-soft text-success border-success/20', titik: 'bg-success', labelDefault: 'Final' },
    aktif: { wadah: 'bg-success-soft text-success border-success/20', titik: 'bg-success', labelDefault: 'Aktif' },
    success: { wadah: 'bg-success-soft text-success border-success/20', titik: 'bg-success', labelDefault: 'Selesai' },
    dibatalkan: { wadah: 'bg-destructive-soft text-destructive border-destructive/20', titik: 'bg-destructive', labelDefault: 'Dibatalkan' },
    ditolak: { wadah: 'bg-destructive-soft text-destructive border-destructive/20', titik: 'bg-destructive', labelDefault: 'Ditolak' },
    habis: { wadah: 'bg-destructive-soft text-destructive border-destructive/20', titik: 'bg-destructive', labelDefault: 'Habis' },
    danger: { wadah: 'bg-destructive-soft text-destructive border-destructive/20', titik: 'bg-destructive', labelDefault: 'Ditolak' },
};

function deteksiVarianDariStatus(status: string): StatusVariant {
    const kunci = status.toLowerCase().trim();
    if (kunci in gayaVarian) {
        return kunci as StatusVariant;
    }
    if (kunci.includes('batal')) return 'dibatalkan';
    if (kunci.includes('tolak')) return 'ditolak';
    if (kunci.includes('habis')) return 'habis';
    if (kunci.includes('final') || kunci.includes('sahkan') || kunci.includes('cair') || kunci.includes('selesai')) return 'final';
    if (kunci.includes('setuju') || kunci.includes('acc')) return 'disetujui';
    if (kunci.includes('tunggu') || kunci.includes('asesmen') || kunci.includes('verifikasi')) return 'warning';
    if (kunci.includes('aju') || kunci.includes('disposisi') || kunci.includes('proses')) return 'diproses';
    return 'draft';
}

export interface StatusBadgeProps {
    status: string;
    variant?: StatusVariant;
    label?: string;
    className?: string;
}

export function StatusBadge({ status, variant, label, className }: StatusBadgeProps) {
    const varianTerpilih = variant ?? deteksiVarianDariStatus(status);
    const konfigurasi = gayaVarian[varianTerpilih];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                konfigurasi.wadah,
                className,
            )}
        >
            <span className={cn('h-2 w-2 shrink-0 rounded-full', konfigurasi.titik)} aria-hidden="true" />
            <span>{label ?? (status || konfigurasi.labelDefault)}</span>
        </span>
    );
}
