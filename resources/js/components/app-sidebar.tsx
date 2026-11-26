import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { deteksiBidangAktif, navPengaturan, navPerBidang } from '@/lib/nav';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogo from './app-logo';

const PERAN_PENUH_SURAT = new Set(['admin', 'admin_arsip', 'kabag_sau', 'ketua', 'waka_1', 'waka_2', 'waka_3', 'waka_4']);

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const peran = auth.user?.peran ?? [];
    const isAdmin = peran.includes('admin');
    const adalahUpzEksternal = peran.includes('upz') && peran.every((p) => p === 'upz');
    const bolehLihatTemplate = peran.some((p) => PERAN_PENUH_SURAT.has(p));
    const bidangAktif = deteksiBidangAktif(page.url);

    let groups = bidangAktif.kode === 'pengaturan' ? [] : (navPerBidang[bidangAktif.kode] ?? []);
    if (bidangAktif.kode === 'umum') {
        groups = groups
            .filter((g) => !(adalahUpzEksternal && g.title === 'Administrasi & Persuratan'))
            .map((g) => ({
                ...g,
                items: g.items.filter(
                    (item) =>
                        !(item.url === '/umum/surat/template' && !bolehLihatTemplate) &&
                        !(item.url === '/umum/rkat' && adalahUpzEksternal),
                ),
            }));
    }

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
