import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

export default function HalamanError({ status, pesan }: { status: number; pesan: string }) {
    return (
        <AppLayout>
            <Head title={`Galat ${status}`} />
            <div className="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center">
                <p className="text-muted-foreground text-5xl font-semibold tabular-nums">{status}</p>
                <p className="text-sm">{pesan}</p>
                <Link className="text-sm underline" href="/dashboard">
                    Kembali ke Dashboard
                </Link>
            </div>
        </AppLayout>
    );
}
