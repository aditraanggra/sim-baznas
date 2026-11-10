import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { tahun_aktif } = usePage<SharedData>().props;
    const tahun = Array.from({ length: 7 }, (_, i) => tahun_aktif - 5 + i);

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto">
                <Select
                    value={String(tahun_aktif)}
                    onValueChange={(nilai) => router.get(window.location.pathname, { tahun: nilai }, { preserveScroll: true, preserveState: false })}
                >
                    <SelectTrigger className="h-9 w-28" aria-label="Tahun aktif">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {tahun.map((t) => (
                            <SelectItem key={t} value={String(t)}>
                                {t}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </header>
    );
}
