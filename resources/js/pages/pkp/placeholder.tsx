import { Head } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface ModulProps {
    judul: string;
    keterangan: string;
}

export default function ModulBelumTersedia({ judul, keterangan }: ModulProps) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: judul, href: '#' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={judul} />
            <div className="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center">
                <CalendarDays className="text-muted-foreground size-10" />
                <h1 className="text-xl font-semibold">{judul}</h1>
                <p className="text-muted-foreground max-w-md text-sm">{keterangan}</p>
            </div>
        </AppLayout>
    );
}
