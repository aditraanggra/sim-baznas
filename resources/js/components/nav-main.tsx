import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

const ROOT_BIDANG_URLS = new Set([
    '/dashboard',
    '/launcher',
    '/pengumpulan',
    '/distribusi',
    '/umum',
    '/pkp',
    '/pengaturan',
]);

function normalisasiPath(url: string): string {
    const tanpaQuery = url.split('?')[0].split('#')[0];
    if (tanpaQuery.length > 1 && tanpaQuery.endsWith('/')) {
        return tanpaQuery.slice(0, -1);
    }

    return tanpaQuery || '/';
}

function cocokkanMenu(currentPath: string, itemPath: string): boolean {
    if (ROOT_BIDANG_URLS.has(itemPath)) {
        return currentPath === itemPath;
    }

    return currentPath === itemPath || currentPath.startsWith(`${itemPath}/`);
}

export function NavMain({
    groups = [],
    showPengaturan = false,
    pengaturan,
    accentColor = 'var(--primary)',
}: {
    groups?: NavGroup[];
    showPengaturan?: boolean;
    pengaturan?: NavGroup;
    accentColor?: string;
}) {
    const page = usePage();
    const currentPath = normalisasiPath(page.url);
    const all = showPengaturan && pengaturan ? [...groups, pengaturan] : groups;

    const urlAktifTerpanjang =
        all
            .flatMap((g) => g.items)
            .map((item) => normalisasiPath(item.url))
            .filter((itemPath) => cocokkanMenu(currentPath, itemPath))
            .sort((a, b) => b.length - a.length)[0] ?? null;

    return (
        <>
            {all.map((group) => (
                <SidebarGroup key={group.title} className="px-2 py-1">
                    <SidebarGroupLabel className="text-muted-foreground text-[11px] font-semibold tracking-wider uppercase">
                        {group.title}
                    </SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => {
                            const aktif = normalisasiPath(item.url) === urlAktifTerpanjang;
                            return (
                                <SidebarMenuItem key={item.url} className="relative">
                                    {aktif && (
                                        <span
                                            aria-hidden="true"
                                            className="absolute top-1.5 bottom-1.5 left-0 z-10 w-[3px] rounded-r-full"
                                            style={{ backgroundColor: accentColor }}
                                        />
                                    )}
                                    <SidebarMenuButton
                                        asChild
                                        isActive={aktif}
                                        className={cn(
                                            'h-10 rounded-md pl-3 text-sm font-medium',
                                            aktif
                                                ? 'bg-primary-soft text-primary-hover hover:bg-primary-soft hover:text-primary-hover font-semibold'
                                                : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                        )}
                                    >
                                        <Link href={item.url} prefetch>
                                            {item.icon && <item.icon />}
                                            <span className="flex-1 truncate">{item.title}</span>
                                            {item.badge !== undefined && (
                                                <span className="bg-success-soft text-success rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums">
                                                    {item.badge}
                                                </span>
                                            )}
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
