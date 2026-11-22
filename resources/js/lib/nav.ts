import { type NavGroup } from '@/types';
import {
    BookOpen,
    Building2,
    CheckSquare,
    Coins,
    Database,
    FileCheck,
    FileSpreadsheet,
    FolderTree,
    Gauge,
    HandHeart,
    Inbox,
    LayoutGrid,
    PlusCircle,
    Receipt,
    ScrollText,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';

export const BIDANG_META: Record<string, { kode: string; nama: string; warna: string; url: string }> = {
    pengumpulan: { kode: 'pengumpulan', nama: 'Pengumpulan', warna: '#259148', url: '/pengumpulan' },
    distribusi: { kode: 'distribusi', nama: 'Pendistribusian dan Pendayagunaan', warna: '#FDC727', url: '/distribusi' },
    umum: { kode: 'umum', nama: 'SDM, Administrasi dan Umum', warna: '#1D4ED8', url: '/umum' },
    pkp: { kode: 'pkp', nama: 'Perencanaan, Keuangan dan Pelaporan', warna: '#5B21B6', url: '/pkp' },
    pengaturan: { kode: 'pengaturan', nama: 'Administrasi Sistem', warna: '#259148', url: '/pengaturan/pengguna' },
};

export const navPerBidang: Record<string, NavGroup[]> = {
    pengumpulan: [
        {
            title: 'Pengumpulan ZIS',
            items: [
                { title: 'Dasbor Pengumpulan', url: '/pengumpulan', icon: LayoutGrid },
                { title: 'Input Penerimaan', url: '/pengumpulan/penerimaan/buat', icon: PlusCircle },
                { title: 'Daftar Penerimaan', url: '/pengumpulan/penerimaan', icon: Receipt },
                { title: 'Data Muzakki', url: '/pengumpulan/muzakki', icon: Users },
                { title: 'Berita Acara Harian', url: '/pengumpulan/berita-acara', icon: FileCheck },
                { title: 'Laporan Penerimaan', url: '/pengumpulan/laporan', icon: FileSpreadsheet },
            ],
        },
    ],
    distribusi: [
        {
            title: 'Pendistribusian & Pendayagunaan',
            items: [
                { title: 'Dasbor Pendistribusian', url: '/distribusi', icon: LayoutGrid },
                { title: 'Disposisi Saya', url: '/distribusi/disposisi-saya', icon: Inbox },
                { title: 'Data Mustahik', url: '/distribusi/mustahik', icon: Users },
                { title: 'Permohonan Bantuan', url: '/distribusi/permohonan', icon: CheckSquare },
                { title: 'Penyaluran', url: '/distribusi/penyaluran', icon: HandHeart },
                { title: 'Berita Acara Harian', url: '/distribusi/berita-acara', icon: FileCheck },
                { title: 'Laporan Pendistribusian', url: '/distribusi/laporan', icon: FileSpreadsheet },
            ],
        },
    ],
    umum: [
        {
            title: 'UPZ (Unit Pengumpul Zakat)',
            items: [
                { title: 'Dasbor Bidang Umum', url: '/umum', icon: Building2 },
                { title: 'Data UPZ & SK UPZ', url: '/umum/upz', icon: ShieldCheck },
                { title: 'Laporan Setoran UPZ', url: '/umum/upz/laporan-setoran', icon: Coins },
            ],
        },
    ],
    pkp: [
        {
            title: 'Perencanaan & Pelaporan',
            items: [
                { title: 'Dasbor PKP', url: '/pkp', icon: LayoutGrid },
                { title: 'RKAT', url: '/pkp/rencana', icon: BookOpen },
                { title: 'Realisasi', url: '/pkp/realisasi', icon: FileSpreadsheet },
                { title: 'Kinerja', url: '/pkp/kinerja', icon: Gauge },
            ],
        },
        {
            title: 'Keuangan',
            items: [{ title: 'Keuangan', url: '/pkp/keuangan', icon: Wallet }],
        },
    ],
};

export const navGroups: NavGroup[] = navPerBidang.pkp;

export const navPengaturan: NavGroup = {
    title: 'Administrasi Sistem',
    items: [
        { title: 'Pengguna', url: '/pengaturan/pengguna', icon: Users },
        { title: 'Master Data', url: '/pengaturan/master-data', icon: Database },
        { title: 'Pos Anggaran', url: '/pengaturan/pos-anggaran', icon: FolderTree },
        { title: 'Impor Data', url: '/pengaturan/impor', icon: ScrollText },
        { title: 'Audit Log', url: '/pengaturan/audit', icon: ScrollText },
    ],
};

export function deteksiBidangAktif(url: string): { kode: string; nama: string; warna: string; url: string } {
    if (url.startsWith('/pengumpulan')) return BIDANG_META.pengumpulan;
    if (url.startsWith('/distribusi')) return BIDANG_META.distribusi;
    if (url.startsWith('/umum')) return BIDANG_META.umum;
    if (url.startsWith('/pengaturan')) return BIDANG_META.pengaturan;
    return BIDANG_META.pkp;
}
