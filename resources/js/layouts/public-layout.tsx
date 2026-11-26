import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import { Head, Link, usePage } from '@inertiajs/react';
import * as React from 'react';

export interface PublicLayoutProps {
    title: string;
    description?: string;
    noindex?: boolean;
    lebar?: 'standar' | 'dasbor';
    tampilkanNavPublik?: boolean;
    subjudul?: string;
    aksiKanan?: React.ReactNode;
    children: React.ReactNode;
}

const MENU_PUBLIK = [
    { judul: 'Beranda', rute: 'publik.index', pola: '/publik' },
    { judul: 'Lacak Permohonan', rute: 'publik.lacak-permohonan', pola: '/publik/lacak-permohonan' },
    { judul: 'Verifikasi SK UPZ', rute: 'publik.verifikasi-sk-upz', pola: '/publik/verifikasi-sk-upz' },
];

export function PublicLayout({
    title,
    description,
    noindex = false,
    lebar = 'standar',
    tampilkanNavPublik = true,
    subjudul = 'Layanan Informasi dan Verifikasi Publik SISFOZIS',
    aksiKanan,
    children,
}: PublicLayoutProps) {
    const maksLebar = lebar === 'dasbor' ? 'max-w-[1120px]' : 'max-w-[720px]';
    const { url } = usePage();
    const jalurSaatIni = url.split('?')[0];

    const IdentitasLembaga = (
        <>
            <div className="border-border/80 flex shrink-0 items-center justify-center rounded-lg border bg-white px-2 py-1 shadow-xs">
                <img src="/images/logo-baznas.png" alt="Logo BAZNAS" className="h-7 w-auto object-contain" />
            </div>
            <div className="min-w-0">
                <p className="font-display text-foreground truncate text-sm font-semibold sm:text-base">
                    BAZNAS Kabupaten Cianjur
                </p>
                <p className="text-muted-foreground truncate text-xs">{subjudul}</p>
            </div>
        </>
    );

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <Head title={title}>
                {description && <meta name="description" content={description} />}
                {noindex && <meta name="robots" content="noindex, nofollow" />}
            </Head>

            <header className="bg-card border-brand border-b-2 print:border-black">
                <div className={cn('mx-auto flex h-16 w-full items-center justify-between gap-4 px-4 sm:px-6', maksLebar)}>
                    {tampilkanNavPublik ? (
                        <Link href={route('publik.index')} className="flex min-w-0 items-center gap-3">
                            {IdentitasLembaga}
                        </Link>
                    ) : (
                        <div className="flex min-w-0 items-center gap-3">{IdentitasLembaga}</div>
                    )}

                    <div className="flex shrink-0 items-center gap-2">
                        {aksiKanan}
                        <Link
                            href={route('login')}
                            className="border-border-strong text-foreground hover:bg-surface-subtle focus-visible:ring-ring inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:outline-hidden print:hidden"
                        >
                            Masuk Amil
                        </Link>
                    </div>
                </div>

                {tampilkanNavPublik && (
                    <nav
                        aria-label="Navigasi layanan publik"
                        className="border-border bg-surface-subtle/60 border-t print:hidden"
                    >
                        <div className={cn('mx-auto flex w-full items-center gap-1 overflow-x-auto px-4 py-1.5 sm:px-6', maksLebar)}>
                            {MENU_PUBLIK.map((item) => {
                                const aktif =
                                    item.pola === '/publik'
                                        ? jalurSaatIni === '/publik'
                                        : jalurSaatIni.startsWith(item.pola);

                                return (
                                    <Link
                                        key={item.rute}
                                        href={route(item.rute)}
                                        className={cn(
                                            'inline-flex h-8 shrink-0 items-center rounded-md px-3 text-xs font-medium transition-colors',
                                            aktif
                                                ? 'bg-brand-soft text-brand'
                                                : 'text-muted-foreground hover:bg-card hover:text-foreground',
                                        )}
                                    >
                                        {item.judul}
                                    </Link>
                                );
                            })}
                        </div>
                    </nav>
                )}
            </header>

            <main className={cn('mx-auto w-full flex-1 px-4 py-8 sm:px-6 print:max-w-full print:px-0 print:py-4', maksLebar)}>
                {children}
            </main>

            <footer className="bg-brand-deep border-gold border-t-2 text-white print:hidden">
                <div
                    className={cn(
                        'mx-auto flex w-full flex-col items-start justify-between gap-2 px-4 py-5 text-xs text-white/90 sm:flex-row sm:items-center sm:px-6',
                        maksLebar,
                    )}
                >
                    <span>BAZNAS Kabupaten Cianjur · Transparansi &amp; Layanan Publik ZIS</span>
                    <span>Jl. Raya Bandung Sadewata No. 108B Cianjur · Telp. (0263) 264866</span>
                </div>
            </footer>

            <Toaster />
        </div>
    );
}
