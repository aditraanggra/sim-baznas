import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ groups = [], showPengaturan = false, pengaturan }: { groups?: NavGroup[]; showPengaturan?: boolean; pengaturan?: NavGroup }) {
    const page = usePage();
    const all = showPengaturan && pengaturan ? [...groups, pengaturan] : groups;

    return (
        <>
            {all.map((group) => (
                <SidebarGroup key={group.title} className="px-2 py-0">
                    <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.url}>
                                <SidebarMenuButton asChild isActive={page.url.startsWith(item.url)}>
                                    <Link href={item.url} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
