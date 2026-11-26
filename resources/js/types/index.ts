import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    badge?: number | string;
}

export interface BidangInfo {
    kode: 'pengumpulan' | 'distribusi' | 'umum' | 'pkp' | string;
    nama: string;
    warna: string;
    urutan: number;
}

export interface NotifikasiItem {
    id: number;
    jenis: string;
    judul: string;
    pesan: string;
    tautan: string | null;
    dibaca: boolean;
    waktu: string | null;
}

export interface SharedData {
    name: string;
    tahun_aktif: number;
    bidang_daftar?: BidangInfo[];
    auth: Auth;
    notifikasi?: {
        belum_dibaca: number;
        daftar: NotifikasiItem[];
    };
    flash: {
        sukses?: string | null;
        peringatan?: string | null;
        error?: string | null;
        tautan_baru?: {
            id: number;
            label: string;
            jenis: string;
            jenis_label: string;
            url: string;
            kadaluarsa_at: string | null;
        } | null;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    username?: string | null;
    email: string;
    status?: 'aktif' | 'nonaktif' | string;
    upz_id?: number | null;
    peran: string[];
    peran_label?: string[];
    kartu_launcher?: string[];
    admin_sistem?: boolean;
    wajib_2fa?: boolean;
    two_factor_aktif?: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}
