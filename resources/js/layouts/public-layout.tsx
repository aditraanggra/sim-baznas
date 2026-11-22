import AppLogoIcon from '@/components/app-logo-icon';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import * as React from 'react';

export interface PublicLayoutProps {
    title: string;
    description?: string;
    noindex?: boolean;
    lebar?: 'standar' | 'dasbor';
    children: React.ReactNode;
}

export function PublicLayout({
    title,
    description,
    noindex = false,
    lebar = 'standar',
    children,
}: PublicLayoutProps) {
    const maksLebar = lebar === 'dasbor' ? 'max-w-[1120px]' : 'max-w-[720px]';

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <Head title={title}>
                {description && <meta name="description" content={description} />}
                {noindex && <meta name="robots" content="noindex, nofollow" />}
            </Head>

            <header className="bg-card border-brand border-b-2">
                <div className={cn('mx-auto flex h-16 w-full items-center justify-between gap-4 px-4 sm:px-6', maksLebar)}>
                    <Link href="/" className="flex min-w-0 items-center gap-3">
                        <div className="bg-brand flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white">
                            <AppLogoIcon className="h-5 w-5 fill-current" />
                        </div>
                        <div className="min-w-0">
                            <p className="font-display text-foreground truncate text-sm font-semibold sm:text-base">
                                BAZNAS Kabupaten Cianjur
                            </p>
                            <p className="text-muted-foreground truncate text-xs">
                                Layanan Informasi dan Verifikasi Publik SISFOZIS
                            </p>
                        </div>
                    </Link>

                    <Link
                        href={route('login')}
                        className="border-border-strong text-foreground hover:bg-surface-subtle focus-visible:ring-ring inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                    >
                        Masuk Amil
                    </Link>
                </div>
            </header>

            <main className={cn('mx-auto w-full flex-1 px-4 py-8 sm:px-6', maksLebar)}>
                {children}
            </main>

            <footer className="bg-brand-deep border-gold border-t-2 text-white">
                <div
                    className={cn(
                        'mx-auto flex w-full flex-col items-start justify-between gap-2 px-4 py-5 text-xs text-white/90 sm:flex-row sm:items-center sm:px-6',
                        maksLebar,
                    )}
                >
                    <span>BAZNAS Kabupaten Cianjur. Transparansi Pengelolaan ZIS.</span>
                    <span>Jl. Raya Bandung KM. 03 Sadewata, Karangtengah, Cianjur</span>
                </div>
            </footer>

            <Toaster />
        </div>
    );
}
