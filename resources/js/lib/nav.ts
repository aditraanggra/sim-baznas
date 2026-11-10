import { NavGroup } from '@/types';
import { BookOpen, FileSpreadsheet, FolderTree, Gauge, LayoutGrid, ScrollText, Users, Wallet } from 'lucide-react';

export const navGroups: NavGroup[] = [
    {
        title: 'Platform',
        items: [
            { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
            { title: 'Rencana', url: '/pkp/rencana', icon: BookOpen },
            { title: 'Realisasi', url: '/pkp/realisasi', icon: FileSpreadsheet },
            { title: 'Kinerja', url: '/pkp/kinerja', icon: Gauge },
            { title: 'Keuangan', url: '/pkp/keuangan', icon: Wallet },
        ],
    },
];

export const navPengaturan: NavGroup = {
    title: 'Pengaturan',
    items: [
        { title: 'Pengguna', url: '/pengaturan/pengguna', icon: Users },
        { title: 'Pos Anggaran', url: '/pengaturan/pos-anggaran', icon: FolderTree },
        { title: 'Impor', url: '/pengaturan/impor', icon: ScrollText },
        { title: 'Audit', url: '/pengaturan/audit', icon: ScrollText },
    ],
};
