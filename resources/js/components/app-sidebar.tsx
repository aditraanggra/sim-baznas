import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { deteksiBidangAktif, navPengaturan, navPerBidang } from '@/lib/nav';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const isAdmin = auth.user?.peran?.includes('admin') ?? false;
    const bidangAktif = deteksiBidangAktif(page.url);

    const groups = bidangAktif.kode === 'pengaturan' ? [] : (navPerBidang[bidangAktif.kode] ?? []);
    const tampilkanPengaturan = bidangAktif.kode === 'pengaturan' || isAdmin;

    return (
        <Sidebar collapsible="icon" variant="inset" className="border-r">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={groups} showPengaturan={tampilkanPengaturan} pengaturan={navPengaturan} accentColor={bidangAktif.warna} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
