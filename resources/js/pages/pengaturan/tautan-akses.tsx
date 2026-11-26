import { EmptyState, PageHeader, StatusBadge } from '@/components/app';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { toast } from '@/components/ui/sonner';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, Copy, ExternalLink, Plus, RefreshCw, ShieldOff } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi Sistem', href: '/pengaturan/pengguna' },
    { title: 'Tautan Akses', href: '/pengaturan/tautan-akses' },
];

interface TautanItem {
    id: number;
    label: string;
    jenis: string;
    jenis_label: string;
    token_prefix: string;
    status: 'aktif' | 'kadaluarsa' | 'dicabut' | string;
    kadaluarsa_at: string | null;
    dicabut_at: string | null;
    terakhir_diakses_at: string | null;
    jumlah_akses: number;
    catatan: string | null;
    pembuat: string | null;
    pencabut: string | null;
    created_at: string | null;
}

interface LogAksesItem {
    id: number;
    konteks: string;
    aksi: string;
    target: string;
    sumber: string;
    ip_address: string | null;
    waktu: string | null;
}

interface Props {
    daftar_tautan: TautanItem[];
    daftar_jenis: { kode: string; label: string }[];
    log_terbaru: LogAksesItem[];
}

export default function TautanAksesPengaturan({ daftar_tautan, daftar_jenis, log_terbaru }: Props) {
    const { flash } = usePage<SharedData>().props;
    const tautanBaru = flash.tautan_baru ?? null;

    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);
    const [targetCabut, setTargetCabut] = React.useState<TautanItem | null>(null);
    const [targetPutar, setTargetPutar] = React.useState<TautanItem | null>(null);
    const [tersalin, setTersalin] = React.useState(false);

    const form = useForm({
        label: '',
        jenis: daftar_jenis[0]?.kode ?? 'portal_pimpinan',
        kadaluarsa_at: '',
        catatan: '',
    });

    const buatTautan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pengaturan.tautan-akses.buat'), {
            preserveScroll: true,
            onSuccess: () => {
                setDrawerTerbuka(false);
                form.reset();
            },
        });
    };

    const cabutTautan = () => {
        if (!targetCabut) return;
        router.post(
            route('pengaturan.tautan-akses.cabut', { tautan: targetCabut.id }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setTargetCabut(null),
            },
        );
    };

    const putarTautan = () => {
        if (!targetPutar) return;
        router.post(
            route('pengaturan.tautan-akses.putar', { tautan: targetPutar.id }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setTargetPutar(null),
            },
        );
    };

    const salinUrl = async (url: string) => {
        try {
            await navigator.clipboard.writeText(url);
            setTersalin(true);
            toast.success('URL tautan khusus berhasil disalin.');
            setTimeout(() => setTersalin(false), 2500);
        } catch {
            toast.error('Gagal menyalin otomatis. Salin URL langsung dari kotak di bawah.');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tautan Akses Khusus Pimpinan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Tautan Akses Khusus Pimpinan &amp; Kabag"
                    description="Kelola token akses tanpa login (noindex, hash SHA-256) untuk Portal Kinerja ZIS dan Portal Arsip PKP (PPID)."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Button type="button" onClick={() => setDrawerTerbuka(true)} className="h-9 gap-1.5">
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            <span>Buat Tautan Baru</span>
                        </Button>
                    }
                />

                {/* Banner Token Sekali Tampil */}
                {tautanBaru && (
                    <section className="border-success/30 bg-success-soft rounded-2xl border p-5 shadow-xs">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="text-success text-xs font-semibold tracking-wide uppercase">Tautan Siap Dibagikan (Tampil Sekali)</p>
                                <h2 className="text-foreground mt-0.5 text-base font-semibold">
                                    {tautanBaru.label} · {tautanBaru.jenis_label}
                                </h2>
                                <p className="text-muted-foreground mt-0.5 text-xs">
                                    Simpan atau kirimkan URL ini sekarang kepada Pimpinan/Kepala Bagian. Sistem hanya menyimpan hash token sehingga URL lengkap tidak dapat dilihat ulang.
                                </p>
                            </div>
                        </div>

                        <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Input value={tautanBaru.url} readOnly className="bg-card font-mono text-xs" />
                            <div className="flex shrink-0 items-center gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => salinUrl(tautanBaru.url)} className="h-9 gap-1.5">
                                    {tersalin ? <Check className="h-4 w-4" aria-hidden="true" /> : <Copy className="h-4 w-4" aria-hidden="true" />}
                                    <span>{tersalin ? 'Tersalin' : 'Salin URL'}</span>
                                </Button>
                                <Button asChild size="sm" className="h-9 gap-1.5">
                                    <a href={tautanBaru.url} target="_blank" rel="noopener noreferrer">
                                        <ExternalLink className="h-4 w-4" aria-hidden="true" />
                                        <span>Buka Portal</span>
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </section>
                )}

                {/* Daftar Tautan Akses */}
                {daftar_tautan.length === 0 ? (
                    <EmptyState
                        title="Belum Ada Tautan Akses Khusus"
                        description="Buat tautan khusus bertoken untuk memberikan akses baca langsung (tanpa login) ke Portal Kinerja & Arsip PKP bagi Pimpinan dan Kepala Bagian."
                        actionLabel="Buat Tautan Pertama"
                        onAction={() => setDrawerTerbuka(true)}
                    />
                ) : (
                    <div className="bg-card border-border overflow-x-auto rounded-2xl border shadow-xs">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                    <th className="px-4 py-3">Label &amp; Cakupan</th>
                                    <th className="px-4 py-3">Token &amp; Masa Berlaku</th>
                                    <th className="px-4 py-3">Aktivitas Akses</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-border divide-y">
                                {daftar_tautan.map((t) => (
                                    <tr key={t.id} className="hover:bg-surface-subtle transition-colors">
                                        <td className="px-4 py-3">
                                            <div className="text-foreground font-medium">{t.label}</div>
                                            <div className="text-muted-foreground text-xs">{t.jenis_label}</div>
                                            {t.catatan && <div className="text-muted-foreground mt-0.5 text-xs">{t.catatan}</div>}
                                        </td>

                                        <td className="px-4 py-3 text-xs">
                                            <div className="text-foreground font-mono">Awalan: {t.token_prefix}••••••••</div>
                                            <div className="text-muted-foreground mt-0.5">
                                                {t.kadaluarsa_at ? `Kedaluwarsa: ${t.kadaluarsa_at}` : 'Tanpa batas kedaluwarsa'}
                                            </div>
                                        </td>

                                        <td className="px-4 py-3 text-xs">
                                            <div className="text-foreground font-medium tabular-nums">{t.jumlah_akses} kunjungan</div>
                                            <div className="text-muted-foreground mt-0.5">
                                                Terakhir: {t.terakhir_diakses_at ?? 'Belum pernah'}
                                            </div>
                                        </td>

                                        <td className="px-4 py-3">
                                            <StatusBadge
                                                status={t.status}
                                                variant={t.status === 'aktif' ? 'aktif' : t.status === 'dicabut' ? 'dibatalkan' : 'habis'}
                                                label={t.status === 'aktif' ? 'Aktif' : t.status === 'dicabut' ? 'Dicabut' : 'Kedaluwarsa'}
                                            />
                                        </td>

                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-1.5">
                                                {t.status !== 'dicabut' && (
                                                    <>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => setTargetPutar(t)}
                                                            className="h-8 gap-1 px-2.5 text-xs"
                                                        >
                                                            <RefreshCw className="h-3.5 w-3.5" aria-hidden="true" />
                                                            <span>Putar Token</span>
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setTargetCabut(t)}
                                                            className="text-destructive hover:bg-destructive-soft hover:text-destructive h-8 gap-1 px-2.5 text-xs"
                                                        >
                                                            <ShieldOff className="h-3.5 w-3.5" aria-hidden="true" />
                                                            <span>Cabut</span>
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Log Akses Terbaru */}
                {log_terbaru.length > 0 && (
                    <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                        <h2 className="text-foreground text-base font-semibold">Log Akses Portal &amp; Dokumen Terbaru</h2>
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            Rekam jejak pembukaan Laporan Kinerja maupun pratinjau, cetak, dan unduh Arsip Dokumen PKP.
                        </p>

                        <div className="mt-3 overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-border text-muted-foreground border-b text-[11px] font-semibold tracking-wider uppercase">
                                        <th className="pb-2">Waktu</th>
                                        <th className="pb-2">Tautan / Pengguna</th>
                                        <th className="pb-2">Konteks</th>
                                        <th className="pb-2">Aksi</th>
                                        <th className="pb-2">Objek / Filter</th>
                                        <th className="pb-2">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-border divide-y">
                                    {log_terbaru.map((log) => (
                                        <tr key={log.id} className="h-9">
                                            <td className="text-muted-foreground py-2 tabular-nums">{log.waktu ?? '-'}</td>
                                            <td className="text-foreground py-2 font-medium">{log.sumber}</td>
                                            <td className="text-muted-foreground py-2 uppercase">{log.konteks}</td>
                                            <td className="py-2 uppercase">{log.aksi}</td>
                                            <td className="text-foreground py-2">{log.target}</td>
                                            <td className="text-muted-foreground py-2 font-mono">{log.ip_address ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}
            </div>

            {/* Sheet Buat Tautan Baru */}
            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[480px]">
                    <SheetHeader>
                        <SheetTitle>Buat Tautan Akses Khusus</SheetTitle>
                        <SheetDescription>
                            Token acak panjang akan dibuat dan hanya ditampilkan satu kali setelah tersimpan.
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={buatTautan} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="tautan-label">Label / Penerima (wajib)</Label>
                            <Input
                                id="tautan-label"
                                value={form.data.label}
                                onChange={(e) => form.setData('label', e.target.value)}
                                placeholder="Contoh: Portal Pimpinan & Kabag 2026"
                                required
                            />
                            {form.errors.label && <p className="text-destructive text-xs">{form.errors.label}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tautan-jenis">Cakupan Akses (wajib)</Label>
                            <select
                                id="tautan-jenis"
                                value={form.data.jenis}
                                onChange={(e) => form.setData('jenis', e.target.value)}
                                className="border-input bg-card text-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            >
                                {daftar_jenis.map((j) => (
                                    <option key={j.kode} value={j.kode}>
                                        {j.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.jenis && <p className="text-destructive text-xs">{form.errors.jenis}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tautan-kadaluarsa">Masa Berlaku Hingga (opsional)</Label>
                            <Input
                                id="tautan-kadaluarsa"
                                type="datetime-local"
                                value={form.data.kadaluarsa_at}
                                onChange={(e) => form.setData('kadaluarsa_at', e.target.value)}
                            />
                            <p className="text-muted-foreground text-xs">Kosongkan bila tautan aktif sampai dicabut manual.</p>
                            {form.errors.kadaluarsa_at && <p className="text-destructive text-xs">{form.errors.kadaluarsa_at}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="tautan-catatan">Catatan Internal (opsional)</Label>
                            <textarea
                                id="tautan-catatan"
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                                rows={3}
                                placeholder="Contoh: Dibagikan via grup pimpinan BAZNAS Cianjur"
                                className="border-input bg-card placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            />
                            {form.errors.catatan && <p className="text-destructive text-xs">{form.errors.catatan}</p>}
                        </div>

                        <SheetFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setDrawerTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Menerbitkan...' : 'Terbitkan Tautan'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Konfirmasi Cabut */}
            <AlertDialog open={targetCabut !== null} onOpenChange={(open) => !open && setTargetCabut(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cabut Tautan Akses Khusus?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tautan <strong>{targetCabut?.label}</strong> akan langsung tidak dapat dibuka lagi oleh siapa pun.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={cabutTautan} className="bg-destructive hover:bg-destructive/90 text-white">
                            Cabut Tautan
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Konfirmasi Putar Token */}
            <AlertDialog open={targetPutar !== null} onOpenChange={(open) => !open && setTargetPutar(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Putar Token Tautan Akses?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Token lama untuk <strong>{targetPutar?.label}</strong> akan langsung dicabut dan diganti dengan URL bertoken baru.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={putarTautan}>Putar Token</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
