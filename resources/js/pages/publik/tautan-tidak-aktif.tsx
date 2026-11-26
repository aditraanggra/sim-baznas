import { PublicLayout } from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';

export default function TautanTidakAktif({ alasan, label }: { alasan: 'tidak_ditemukan' | 'dicabut' | 'kadaluarsa' | string; label: string | null }) {
    const judul =
        alasan === 'dicabut'
            ? 'Tautan Akses Telah Dicabut'
            : alasan === 'kadaluarsa'
              ? 'Masa Berlaku Tautan Berakhir'
              : 'Tautan Akses Tidak Ditemukan';

    const keterangan =
        alasan === 'dicabut'
            ? `Tautan khusus${label ? ` (${label})` : ''} telah dinonaktifkan oleh Administrator Sistem atau diganti dengan token terbaru.`
            : alasan === 'kadaluarsa'
              ? `Masa berlaku tautan khusus${label ? ` (${label})` : ''} telah berakhir.`
              : 'Tautan akses yang Anda buka tidak valid atau tidak terdaftar di sistem SISFOZIS.';

    return (
        <PublicLayout
            title={judul}
            noindex={true}
            tampilkanNavPublik={false}
            subjudul="Portal Khusus Pimpinan & Kepala Bagian"
        >
            <div className="bg-card border-border mx-auto max-w-lg rounded-2xl border p-6 text-center shadow-xs sm:p-8">
                <div className="bg-destructive-soft text-destructive mx-auto flex h-12 w-12 items-center justify-center rounded-full">
                    <Lock className="h-6 w-6" aria-hidden="true" />
                </div>
                <h1 className="font-display text-foreground mt-4 text-xl font-semibold">{judul}</h1>
                <p className="text-muted-foreground mt-2 text-sm leading-5">{keterangan}</p>
                <p className="text-muted-foreground mt-4 border-t pt-4 text-xs">
                    Silakan hubungi Administrator Sistem atau Bidang PKP BAZNAS Kabupaten Cianjur untuk mendapatkan tautan akses terbaru, atau{' '}
                    <Link href={route('login')} className="text-brand font-medium hover:underline">
                        masuk menggunakan akun amil
                    </Link>
                    .
                </p>
            </div>
        </PublicLayout>
    );
}
