import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { DropdownMenu, DropdownMenuContent, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { BIDANG_META, deteksiBidangAktif, navPengaturan, navPerBidang } from '@/lib/nav';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, LayoutGrid, Search } from 'lucide-react';
import * as React from 'react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const page = usePage<SharedData>();
    const { tahun_aktif, auth, notifikasi } = page.props;
    const bidangAktif = deteksiBidangAktif(page.url);
    const kartuDiizinkan = auth.user?.kartu_launcher ?? [];
    const isAdmin = auth.user?.peran?.includes('admin') ?? false;
    const tahun = Array.from({ length: 7 }, (_, i) => tahun_aktif - 5 + i);

    const daftarNotifikasi = notifikasi?.daftar ?? [];
    const belumDibaca = notifikasi?.belum_dibaca ?? 0;

    const [paletTerbuka, setPaletTerbuka] = React.useState(false);
    const [kueriCari, setKueriCari] = React.useState('');
    const [hasilGlobal, setHasilGlobal] = React.useState<{ kategori: string; judul: string; subjudul: string; url: string }[]>([]);
    const [sedangCari, setSedangCari] = React.useState(false);

    React.useEffect(() => {
        if (!paletTerbuka) {
            setKueriCari('');
            setHasilGlobal([]);
            return;
        }
        const trimmed = kueriCari.trim();
        if (trimmed.length < 2) {
            setHasilGlobal([]);
            return;
        }
        const ctrl = new AbortController();
        const timer = window.setTimeout(async () => {
            setSedangCari(true);
            try {
                const res = await fetch(route('pencarian-global', { q: trimmed }), { signal: ctrl.signal });
                if (res.ok) {
                    const json = await res.json();
                    setHasilGlobal(json.hasil ?? []);
                }
            } catch {
                // Abaikan saat dibatalkan atau jaringan gagal
            } finally {
                setSedangCari(false);
            }
        }, 250);

        return () => {
            window.clearTimeout(timer);
            ctrl.abort();
        };
    }, [kueriCari, paletTerbuka]);

    React.useEffect(() => {
        const tanganiKeyDown = (e: KeyboardEvent) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setPaletTerbuka((prev) => !prev);
            }
        };
        window.addEventListener('keydown', tanganiKeyDown);
        return () => window.removeEventListener('keydown', tanganiKeyDown);
    }, []);

    const tampilkanPemilihTahun = page.url.startsWith('/pkp') || page.url.startsWith('/dashboard');

    return (
        <>
            <header className="bg-card border-border flex h-14 shrink-0 items-center justify-between gap-2 border-b px-4 transition-[width,height] ease-linear md:px-6">
                <div className="flex min-w-0 items-center gap-2">
                    <SidebarTrigger className="-ml-1" />

                    <Link
                        href="/dashboard"
                        aria-label="Kembali ke Launcher Modul"
                        title="Kembali ke Launcher Modul"
                        className="border-border bg-surface-subtle text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                    >
                        <LayoutGrid className="h-4 w-4" aria-hidden="true" />
                    </Link>

                    <div className="bg-surface-subtle border-border hidden items-center gap-2 rounded-md border px-2.5 py-1 text-xs font-medium sm:inline-flex">
                        <span className="h-2.5 w-2.5 shrink-0 rounded-full" style={{ backgroundColor: bidangAktif.warna }} aria-hidden="true" />
                        <span className="text-foreground truncate">{bidangAktif.nama}</span>
                    </div>

                    <div className="hidden min-w-0 lg:block">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setPaletTerbuka(true)}
                        className="border-border-strong text-muted-foreground h-9 gap-2 px-3 text-xs font-normal"
                    >
                        <Search className="h-3.5 w-3.5" aria-hidden="true" />
                        <span className="hidden sm:inline">Cari modul atau data...</span>
                        <kbd className="bg-surface-subtle border-border text-muted-foreground hidden rounded border px-1.5 py-0.5 text-[10px] font-medium md:inline">
                            Ctrl+K
                        </kbd>
                    </Button>

                    {tampilkanPemilihTahun && (
                        <Select
                            value={String(tahun_aktif)}
                            onValueChange={(nilai) => router.get(window.location.pathname, { tahun: nilai }, { preserveScroll: true, preserveState: false })}
                        >
                            <SelectTrigger className="h-9 w-24" aria-label="Tahun aktif">
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
                    )}

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                aria-label="Notifikasi"
                                className="border-border-strong relative h-9 w-9"
                            >
                                <Bell className="h-4 w-4" aria-hidden="true" />
                                {belumDibaca > 0 && (
                                    <span className="bg-brand absolute -top-1 -right-1 inline-flex min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] leading-[18px] font-semibold text-white tabular-nums">
                                        {belumDibaca > 99 ? '99+' : belumDibaca}
                                    </span>
                                )}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-80">
                            <div className="flex items-center justify-between px-2 py-1.5">
                                <DropdownMenuLabel className="p-0">Notifikasi</DropdownMenuLabel>
                                {belumDibaca > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => router.post(route('notifikasi.baca-semua'), {}, { preserveScroll: true })}
                                        className="text-primary hover:underline inline-flex items-center gap-1 text-xs font-medium"
                                    >
                                        <CheckCheck className="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>Tandai semua dibaca</span>
                                    </button>
                                )}
                            </div>
                            <DropdownMenuSeparator />

                            {daftarNotifikasi.length === 0 ? (
                                <div className="text-muted-foreground px-3 py-5 text-center text-xs">
                                    Belum ada notifikasi baru.
                                </div>
                            ) : (
                                <div className="max-h-80 divide-y overflow-y-auto">
                                    {daftarNotifikasi.map((item) => (
                                        <button
                                            key={item.id}
                                            type="button"
                                            onClick={() => router.post(route('notifikasi.baca', item.id), {}, { preserveScroll: true })}
                                            className="hover:bg-surface-subtle flex w-full items-start gap-2.5 px-3 py-2.5 text-left transition-colors"
                                        >
                                            <span
                                                aria-hidden="true"
                                                className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${item.dibaca ? 'bg-border-strong' : 'bg-brand'}`}
                                            />
                                            <div className="min-w-0 flex-1 space-y-0.5">
                                                <p className={`text-xs ${item.dibaca ? 'text-muted-foreground font-normal' : 'text-foreground font-semibold'}`}>
                                                    {item.judul}
                                                </p>
                                                <p className="text-muted-foreground line-clamp-2 text-xs">{item.pesan}</p>
                                                {item.waktu && <p className="text-muted-foreground text-[11px]">{item.waktu}</p>}
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <CommandDialog open={paletTerbuka} onOpenChange={setPaletTerbuka}>
                <CommandInput
                    onQueryChange={setKueriCari}
                    placeholder="Cari modul, muzakki, mustahik, kwitansi, permohonan, atau UPZ..."
                />
                <CommandList>
                    {kueriCari.trim().length >= 2 && (
                        <CommandGroup heading={sedangCari ? 'Mencari Data...' : 'Hasil Pencarian Data'}>
                            {hasilGlobal.map((item, idx) => (
                                <CommandItem
                                    key={`${item.url}-${idx}`}
                                    value={`${item.kategori} ${item.judul} ${item.subjudul} ${kueriCari}`}
                                    onSelect={() => {
                                        setPaletTerbuka(false);
                                        router.visit(item.url);
                                    }}
                                >
                                    <Search className="text-muted-foreground h-3.5 w-3.5" aria-hidden="true" />
                                    <span className="text-muted-foreground text-xs">[{item.kategori}]</span>
                                    <span>{item.judul}</span>
                                    <span className="text-muted-foreground ml-auto text-xs">{item.subjudul}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    <CommandGroup heading="Modul Bidang">
                        <CommandItem
                            value="Launcher Utama 2x2"
                            onSelect={() => {
                                setPaletTerbuka(false);
                                router.visit('/dashboard');
                            }}
                        >
                            <LayoutGrid className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                            <span>Launcher Modul</span>
                        </CommandItem>
                        {kartuDiizinkan.map((kode) => {
                            const meta = BIDANG_META[kode];
                            if (!meta) return null;
                            return (
                                <CommandItem
                                    key={kode}
                                    value={`${meta.nama} ${meta.kode}`}
                                    onSelect={() => {
                                        setPaletTerbuka(false);
                                        router.visit(meta.url);
                                    }}
                                >
                                    <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: meta.warna }} aria-hidden="true" />
                                    <span>{meta.nama}</span>
                                </CommandItem>
                            );
                        })}
                    </CommandGroup>

                    {kartuDiizinkan.includes('pkp') && (
                        <CommandGroup heading="Menu PKP">
                            {navPerBidang.pkp
                                .flatMap((g) => g.items)
                                .map((item) => (
                                    <CommandItem
                                        key={item.url}
                                        value={`${item.title} PKP`}
                                        onSelect={() => {
                                            setPaletTerbuka(false);
                                            router.visit(item.url);
                                        }}
                                    >
                                        <span>{item.title}</span>
                                    </CommandItem>
                                ))}
                        </CommandGroup>
                    )}

                    {isAdmin && (
                        <CommandGroup heading="Administrasi Sistem">
                            {navPengaturan.items.map((item) => (
                                <CommandItem
                                    key={item.url}
                                    value={`${item.title} Administrasi`}
                                    onSelect={() => {
                                        setPaletTerbuka(false);
                                        router.visit(item.url);
                                    }}
                                >
                                    <span>{item.title}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    <CommandEmpty />
                </CommandList>
            </CommandDialog>
        </>
    );
}
