import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

class AppErrorBoundary extends Component<{ children: ReactNode }, { hasError: boolean; message: string }> {
    constructor(props: { children: ReactNode }) {
        super(props);
        this.state = { hasError: false, message: '' };
    }

    static getDerivedStateFromError(error: Error) {
        return { hasError: true, message: error?.message || 'Terjadi kesalahan saat merender halaman.' };
    }

    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('Kesalahan antarmuka aplikasi:', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="bg-background text-foreground flex min-h-screen items-center justify-center p-6">
                    <div className="border-border bg-card w-full max-w-md rounded-xl border p-6 shadow-sm">
                        <h1 className="text-base font-semibold">Halaman gagal dimuat</h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Terjadi kendala saat menampilkan tampilan ini. Silakan muat ulang halaman atau kembali ke dasbor utama.
                        </p>
                        {this.state.message && (
                            <pre className="bg-muted text-muted-foreground mt-3 max-h-28 overflow-auto rounded-md p-2.5 text-xs whitespace-pre-wrap">
                                {this.state.message}
                            </pre>
                        )}
                        <div className="mt-4 flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-9 items-center rounded-md px-3 text-sm font-medium"
                            >
                                Muat Ulang Halaman
                            </button>
                            <a
                                href="/dashboard"
                                className="border-border bg-background hover:bg-muted inline-flex h-9 items-center rounded-md border px-3 text-sm font-medium"
                            >
                                Ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

window.addEventListener('vite:preloadError', () => {
    window.location.reload();
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <AppErrorBoundary>
                <App {...props} />
            </AppErrorBoundary>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
