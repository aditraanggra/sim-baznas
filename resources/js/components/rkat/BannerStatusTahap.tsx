import { StatusBadge, type StatusVariant } from '@/components/app';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import { router } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Lock, Send } from 'lucide-react';
import { type KodeBidangRkat, type TahapStatusInfo } from './types';

const LABEL_STATUS: Record<string, { label: string; varian: StatusVariant }> = {
    menunggu: { label: 'Menunggu Verifikasi Pengumpulan', varian: 'warning' },
    draft: { label: 'Draft Penyusunan', varian: 'draft' },
    diajukan: { label: 'Menunggu Verifikasi Kabag PKP', varian: 'diproses' },
    terverifikasi: { label: 'Terverifikasi PKP', varian: 'final' },
};

export function BannerStatusTahap({
    bidangKode,
    versiId,
    versiTahun,
    versiLabel,
    versiTerkunci,
    tahap,
    terpenuhi,
    pesanBelumTerpenuhi,
    dapatKelola,
}: {
    bidangKode: KodeBidangRkat;
    versiId: number;
    versiTahun: number;
    versiLabel: string;
    versiTerkunci: boolean;
    tahap: TahapStatusInfo;
    terpenuhi: boolean;
    pesanBelumTerpenuhi: string;
    dapatKelola: boolean;
}) {
    const info = LABEL_STATUS[tahap.status] ?? { label: tahap.status, varian: 'draft' as StatusVariant };
    const bolehAjukan = dapatKelola && !versiTerkunci && tahap.status === 'draft';

    const ajukanTahap = () => {
        router.post(
            route(`${bidangKode}.rkat.ajukan`, versiId),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <section className="bg-card border-border rounded-xl border p-5 shadow-xs">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1.5">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-foreground text-base font-semibold">
                            RKAT {versiTahun} · {versiLabel}
                        </span>
                        <StatusBadge status={tahap.status} variant={info.varian} label={info.label} />
                        {versiTerkunci && (
                            <span className="text-muted-foreground inline-flex items-center gap-1 text-xs font-medium">
                                <Lock className="h-3.5 w-3.5" aria-hidden="true" />
                                Versi RKAT terkunci
                            </span>
                        )}
                    </div>
                    <p className="text-muted-foreground text-sm">
                        {tahap.status === 'menunggu' &&
                            'Pagu bidang ini baru terbuka setelah rincian Pengumpulan diverifikasi oleh Kepala Bagian PKP.'}
                        {tahap.status === 'draft' &&
                            (terpenuhi
                                ? 'Seluruh target atau pagu telah terpenuhi tepat (selisih Rp 0). Rincian siap diajukan ke Kepala Bagian PKP.'
                                : pesanBelumTerpenuhi)}
                        {tahap.status === 'diajukan' &&
                            'Rincian bidang telah diajukan dan sedang menunggu verifikasi dari Kepala Bagian PKP.'}
                        {tahap.status === 'terverifikasi' &&
                            'Rincian bidang telah diverifikasi oleh Kepala Bagian PKP dan terkunci untuk konsolidasi.'}
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {!dapatKelola && (
                        <span className="bg-surface-subtle text-muted-foreground border-border rounded-md border px-3 py-1.5 text-xs font-medium">
                            Mode lihat saja
                        </span>
                    )}
                    {bolehAjukan && (
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button type="button" className="h-9" disabled={!terpenuhi}>
                                    <Send className="h-4 w-4" aria-hidden="true" />
                                    Ajukan ke Kabag PKP
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Ajukan Rincian RKAT Bidang?</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Rincian RKAT {versiTahun} ({versiLabel}) akan dikirim ke Kepala Bagian PKP untuk diverifikasi dan sementara
                                        terkunci dari perubahan.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                    <AlertDialogAction onClick={ajukanTahap}>Ya, Ajukan Sekarang</AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    )}
                </div>
            </div>

            {tahap.catatan_verifikasi && (
                <div
                    role="alert"
                    className="bg-warning-soft text-warning border-warning/30 mt-4 flex items-start gap-2.5 rounded-lg border px-3.5 py-2.5 text-sm"
                >
                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    <div>
                        <p className="font-medium">Catatan dari Kepala Bagian PKP:</p>
                        <p>{tahap.catatan_verifikasi}</p>
                    </div>
                </div>
            )}

            {tahap.status === 'draft' && terpenuhi && (
                <div className="bg-success-soft text-success border-success/20 mt-4 flex items-center gap-2 rounded-lg border px-3.5 py-2 text-xs font-medium">
                    <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>Validasi pagu bidang lulus (selisih {formatRupiah(0)}).</span>
                </div>
            )}
        </section>
    );
}
