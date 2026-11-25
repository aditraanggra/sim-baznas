import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, Building2, ChevronDown, Coins, HandHeart, LogOut, Settings, Shield } from 'lucide-react';
import * as React from 'react';

interface KartuDefinisi {
    kode: 'pengumpulan' | 'distribusi' | 'umum' | 'pkp';
    judul: string;
    deskripsi: string;
    href: string;
    latarStyle: React.CSSProperties;
    teksGelap: boolean;
    ikon: React.ComponentType<{ className?: string }>;
}

const DAFTAR_KARTU: KartuDefinisi[] = [
    {
        kode: 'pengumpulan',
        judul: 'Pengumpulan',
        deskripsi: 'Pengelolaan data muzakki, penerimaan zakat, infak, sedekah, DSKL, kwitansi, dan berita acara harian.',
        href: '/pengumpulan',
        latarStyle: { backgroundColor: '#259148' },
        teksGelap: false,
        ikon: Coins,
    },
    {
        kode: 'distribusi',
        judul: 'Pendistribusian dan Pendayagunaan',
        deskripsi: 'Data mustahik, permohonan bantuan, disposisi berjenjang 7 langkah, penyaluran program, dan pelacakan.',
        href: '/distribusi',
        latarStyle: { backgroundColor: '#FDC727' },
        teksGelap: true,
        ikon: HandHeart,
    },
    {
        kode: 'umum',
        judul: 'SDM, Administrasi dan Umum',
        deskripsi: 'Surat masuk dan keluar, manajemen aset dan penyusutan, pengelolaan UPZ dan SK UPZ, serta data amil.',
        href: '/umum',
        latarStyle: { backgroundColor: '#1D4ED8' },
        teksGelap: false,
        ikon: Building2,
    },
    {
        kode: 'pkp',
        judul: 'Perencanaan, Keuangan dan Pelaporan',
        deskripsi: 'Penyusunan RKAT dan revisi, RENJA, RENSTRA, laporan kinerja, kas, jurnal PSAK 109, dan arsip dokumen.',
        href: '/pkp',
        latarStyle: { backgroundColor: '#5B21B6' },
        teksGelap: false,
        ikon: BookOpen,
    },
];

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;
    const pengguna = auth.user;
    const kartuDiizinkan = pengguna?.kartu_launcher ?? [];
    const labelPeran = pengguna?.peran_label?.length ? pengguna.peran_label.join(' · ') : (pengguna?.peran ?? []).join(', ');
    const kartuTampak = DAFTAR_KARTU.filter((kartu) => kartuDiizinkan.includes(kartu.kode));

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <Head title="Launcher Modul" />

            <header className="bg-card border-border border-b">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="flex shrink-0 items-center justify-center rounded-lg border border-white/25 bg-white px-2 py-1 shadow-sm">
                            <img src="/images/logo-baznas.png" alt="Logo BAZNAS" className="h-8 w-auto object-contain" />
                        </div>
                        <div className="min-w-0">
                            <p className="font-display text-foreground truncate text-base font-semibold">SISFOZIS</p>
                            <p className="text-muted-foreground truncate text-xs">Sistem Informasi Zakat, Infak, dan Sedekah BAZNAS Kab. Cianjur</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {pengguna?.admin_sistem && (
                            <Button asChild variant="outline" size="sm" className="border-border-strong hidden h-10 gap-1.5 sm:inline-flex">
                                <Link href="/pengaturan/pengguna">
                                    <Shield className="h-4 w-4" aria-hidden="true" />
                                    <span>Administrasi Sistem</span>
                                </Link>
                            </Button>
                        )}

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="border-border-strong h-11 gap-2 rounded-lg px-3 text-left sm:h-10"
                                >
                                    <div className="bg-brand-soft text-brand flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
                                        {(pengguna?.name ?? 'U').slice(0, 1).toUpperCase()}
                                    </div>
                                    <div className="hidden max-w-[180px] min-w-0 sm:block">
                                        <p className="text-foreground truncate text-xs font-semibold">{pengguna?.name}</p>
                                        <p className="text-muted-foreground truncate text-[11px]">{labelPeran || 'Pengguna'}</p>
                                    </div>
                                    <ChevronDown className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-60">
                                <DropdownMenuLabel className="font-normal">
                                    <p className="text-foreground text-sm font-semibold">{pengguna?.name}</p>
                                    <p className="text-muted-foreground truncate text-xs">{pengguna?.email}</p>
                                    {labelPeran && <p className="text-brand mt-1 text-[11px] font-medium">{labelPeran}</p>}
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                {pengguna?.admin_sistem && (
                                    <DropdownMenuItem asChild className="sm:hidden">
                                        <Link href="/pengaturan/pengguna">
                                            <Shield className="h-4 w-4" aria-hidden="true" />
                                            <span>Administrasi Sistem</span>
                                        </Link>
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuItem asChild>
                                    <Link href="/settings/profile">
                                        <Settings className="h-4 w-4" aria-hidden="true" />
                                        <span>Profil dan Pengaturan</span>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    onSelect={() => router.post(route('logout'))}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <LogOut className="h-4 w-4" aria-hidden="true" />
                                    <span>Keluar</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <section className="bg-brand-deep border-gold border-b-4 text-white">
                <div className="mx-auto w-full max-w-6xl px-4 py-7 sm:px-6 sm:py-9">
                    <h1 className="font-display text-2xl leading-tight font-semibold sm:text-[32px] sm:leading-[40px]">
                        Selamat Datang di SISFOZIS
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-white/90 sm:text-base">
                        Pilih bidang kerja sesuai kewenangan peran Anda untuk mengelola pengumpulan, penyaluran, administrasi, dan perencanaan.
                    </p>
                </div>
            </section>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
                {kartuTampak.length === 0 ? (
                    <div className="bg-card border-border rounded-2xl border p-8 text-center">
                        <h2 className="text-foreground text-base font-semibold">Belum Ada Modul Bidang yang Diizinkan</h2>
                        <p className="text-muted-foreground mx-auto mt-1.5 max-w-md text-sm">
                            Akun Anda belum memiliki akses kartu bidang. Hubungi Administrator Sistem untuk menetapkan peran bidang kerja.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {kartuTampak.map((kartu) => {
                            const Ikon = kartu.ikon;
                            return (
                                <Link
                                    key={kartu.kode}
                                    href={kartu.href}
                                    style={kartu.latarStyle}
                                    className="group focus-visible:ring-ring relative flex min-h-[196px] flex-col justify-between overflow-hidden rounded-[20px] p-6 transition-transform duration-150 ease-out hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden sm:p-7"
                                >
                                    <Ikon
                                        className={`pointer-events-none absolute -right-4 -bottom-4 h-36 w-36 opacity-10 ${
                                            kartu.teksGelap ? 'text-[#101828]' : 'text-white'
                                        }`}
                                        aria-hidden="true"
                                    />

                                    <div className="relative z-10 pr-8">
                                        <h2
                                            className={`font-display text-xl font-semibold ${
                                                kartu.teksGelap ? 'text-[#101828]' : 'text-white'
                                            }`}
                                        >
                                            {kartu.judul}
                                        </h2>
                                        <p
                                            className={`mt-2 max-w-md text-sm leading-relaxed ${
                                                kartu.teksGelap ? 'text-[#1D2939]' : 'text-white/90'
                                            }`}
                                        >
                                            {kartu.deskripsi}
                                        </p>
                                    </div>

                                    <div className="relative z-10 mt-6 flex items-center justify-between">
                                        <span
                                            className={`text-xs font-medium tracking-wide uppercase ${
                                                kartu.teksGelap ? 'text-[#1D2939]' : 'text-white/85'
                                            }`}
                                        >
                                            Buka Modul
                                        </span>
                                        <span
                                            className={`inline-flex h-10 w-10 items-center justify-center rounded-full transition-transform duration-150 group-hover:translate-x-1 ${
                                                kartu.teksGelap ? 'bg-[#101828]/10 text-[#101828]' : 'bg-white/15 text-white'
                                            }`}
                                        >
                                            <ArrowRight className="h-5 w-5" aria-hidden="true" />
                                        </span>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </main>

            <footer className="bg-brand-deep text-white">
                <div className="mx-auto flex w-full max-w-6xl flex-col items-start justify-between gap-2 px-4 py-4 text-xs text-white/85 sm:flex-row sm:items-center sm:px-6">
                    <span>BAZNAS Kabupaten Cianjur</span>
                    <span>Sistem Informasi Zakat, Infak, dan Sedekah (SISFOZIS)</span>
                </div>
            </footer>

            <Toaster />
        </div>
    );
}
