import { PageHeader, StatusBadge } from '@/components/app';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Power, ShieldCheck } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi Sistem', href: '/pengaturan/pengguna' },
    { title: 'Pengguna', href: '/pengaturan/pengguna' },
];

interface BarisPengguna {
    id: number;
    name: string;
    username: string | null;
    email: string;
    status: 'aktif' | 'nonaktif' | string;
    upz_id: number | null;
    last_login_at: string | null;
    two_factor_aktif: boolean;
    peran: string[];
}

interface PeranDefinisi {
    kode: string;
    nama: string;
    bidang: string | null;
}

interface UpzOpsi {
    id: number;
    kode: string;
    nama: string;
}

export default function Pengguna({
    pengguna,
    daftar_peran,
    daftar_upz = [],
}: {
    pengguna: BarisPengguna[];
    daftar_peran: PeranDefinisi[];
    daftar_upz?: UpzOpsi[];
}) {
    const [drawerTerbuka, setDrawerTerbuka] = React.useState(false);
    const [targetReset, setTargetReset] = React.useState<BarisPengguna | null>(null);
    const [targetToggle, setTargetToggle] = React.useState<BarisPengguna | null>(null);

    const form = useForm<{
        id: string;
        name: string;
        username: string;
        email: string;
        password: string;
        password_confirmation: string;
        upz_id: string;
        peran: string[];
    }>({
        id: '',
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        upz_id: '',
        peran: [],
    });

    const formReset = useForm({
        id: 0,
        password: '',
        password_confirmation: '',
    });

    const labelPeran = React.useMemo(() => {
        const peta: Record<string, string> = {};
        for (const p of daftar_peran) {
            peta[p.kode] = p.nama;
        }
        return peta;
    }, [daftar_peran]);

    const bukaTambah = () => {
        form.clearErrors();
        form.setData({
            id: '',
            name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            upz_id: '',
            peran: [],
        });
        setDrawerTerbuka(true);
    };

    const bukaUbah = (u: BarisPengguna) => {
        form.clearErrors();
        form.setData({
            id: String(u.id),
            name: u.name,
            username: u.username ?? '',
            email: u.email,
            password: '',
            password_confirmation: '',
            upz_id: u.upz_id ? String(u.upz_id) : '',
            peran: u.peran,
        });
        setDrawerTerbuka(true);
    };

    const kirimSimpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('pengaturan.pengguna.simpan'), {
            preserveScroll: true,
            onSuccess: () => setDrawerTerbuka(false),
        });
    };

    const kirimResetSandi = (e: React.FormEvent) => {
        e.preventDefault();
        formReset.post(route('pengaturan.pengguna.reset-sandi'), {
            preserveScroll: true,
            onSuccess: () => {
                setTargetReset(null);
                formReset.reset();
            },
        });
    };

    const konfirmasiToggleStatus = () => {
        if (!targetToggle) return;
        router.post(
            route('pengaturan.pengguna.toggle-status'),
            { id: targetToggle.id },
            {
                preserveScroll: true,
                onFinish: () => setTargetToggle(null),
            },
        );
    };

    const kolom: DataTableColumn<BarisPengguna>[] = [
        {
            id: 'name',
            header: 'Pengguna',
            sortable: true,
            sortValue: (u) => u.name,
            cell: (u) => (
                <div className="min-w-0">
                    <p className="text-foreground font-medium">{u.name}</p>
                    <p className="text-muted-foreground text-xs">
                        {u.username ? `@${u.username} · ` : ''}
                        {u.email}
                    </p>
                </div>
            ),
        },
        {
            id: 'peran',
            header: 'Peran',
            cell: (u) => (
                <div className="flex flex-wrap gap-1">
                    {u.peran.map((p) => (
                        <span key={p} className="bg-surface-subtle border-border text-foreground rounded-md border px-2 py-0.5 text-xs">
                            {labelPeran[p] ?? p}
                        </span>
                    ))}
                </div>
            ),
        },
        {
            id: 'keamanan',
            header: '2FA',
            cell: (u) =>
                u.two_factor_aktif ? (
                    <span className="text-success inline-flex items-center gap-1 text-xs font-medium">
                        <ShieldCheck className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Aktif</span>
                    </span>
                ) : (
                    <span className="text-muted-foreground text-xs">Belum aktif</span>
                ),
        },
        {
            id: 'status',
            header: 'Status',
            sortable: true,
            sortValue: (u) => u.status,
            cell: (u) => (
                <StatusBadge
                    status={u.status}
                    variant={u.status === 'aktif' ? 'aktif' : 'draft'}
                    label={u.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                />
            ),
        },
        {
            id: 'aksi',
            header: 'Aksi',
            align: 'right',
            hideable: false,
            cell: (u) => (
                <div className="flex justify-end gap-1.5">
                    <Button type="button" variant="outline" size="sm" className="h-8 gap-1 px-2.5 text-xs" onClick={() => bukaUbah(u)}>
                        <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Ubah</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 gap-1 px-2.5 text-xs"
                        onClick={() => {
                            formReset.clearErrors();
                            formReset.setData({ id: u.id, password: '', password_confirmation: '' });
                            setTargetReset(u);
                        }}
                    >
                        <KeyRound className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>Reset Sandi</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 gap-1 px-2.5 text-xs"
                        onClick={() => setTargetToggle(u)}
                    >
                        <Power className="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{u.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}</span>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengelolaan Pengguna" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Pengelolaan Pengguna"
                    description="Kelola akun amil dan pengurus UPZ, tetapkan multi-peran bidang, atur ulang sandi, serta nonaktifkan akses."
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Button type="button" onClick={bukaTambah} className="h-9 gap-1.5">
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            <span>Tambah Pengguna</span>
                        </Button>
                    }
                />

                <DataTable
                    columns={kolom}
                    data={pengguna}
                    keyExtractor={(u) => u.id}
                    emptyMessage="Belum ada data pengguna terdaftar."
                />
            </div>

            <Sheet open={drawerTerbuka} onOpenChange={setDrawerTerbuka}>
                <SheetContent className="w-full overflow-y-auto sm:max-w-[540px]">
                    <SheetHeader>
                        <SheetTitle>{form.data.id ? 'Ubah Pengguna' : 'Tambah Pengguna Baru'}</SheetTitle>
                        <SheetDescription>
                            Pengguna internal otomatis mendapat peran dasar Amil. Perubahan peran tercatat di audit log.
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={kirimSimpan} className="mt-5 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="pengguna-name">Nama Lengkap (wajib)</Label>
                            <Input
                                id="pengguna-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                                className="h-9"
                            />
                            {form.errors.name && <p className="text-destructive text-xs">{form.errors.name}</p>}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="pengguna-username">Username</Label>
                                <Input
                                    id="pengguna-username"
                                    value={form.data.username}
                                    onChange={(e) => form.setData('username', e.target.value)}
                                    placeholder="otomatis dari email"
                                    className="h-9"
                                />
                                {form.errors.username && <p className="text-destructive text-xs">{form.errors.username}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="pengguna-email">Alamat Email (wajib)</Label>
                                <Input
                                    id="pengguna-email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    required
                                    className="h-9"
                                />
                                {form.errors.email && <p className="text-destructive text-xs">{form.errors.email}</p>}
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="pengguna-password">
                                    {form.data.id ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi (wajib)'}
                                </Label>
                                <Input
                                    id="pengguna-password"
                                    type="password"
                                    required={!form.data.id}
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    placeholder="Minimal 8 karakter huruf & angka"
                                    className="h-9"
                                />
                                {form.errors.password && <p className="text-destructive text-xs">{form.errors.password}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="pengguna-password-konfirmasi">Ulangi Kata Sandi</Label>
                                <Input
                                    id="pengguna-password-konfirmasi"
                                    type="password"
                                    required={!form.data.id || form.data.password.length > 0}
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                        </div>

                        {form.data.peran.includes('upz') && daftar_upz.length > 0 && (
                            <div className="space-y-1.5">
                                <Label htmlFor="pengguna-upz">Cakupan UPZ (untuk peran Pengurus UPZ)</Label>
                                <Select value={form.data.upz_id} onValueChange={(v) => form.setData('upz_id', v)}>
                                    <SelectTrigger id="pengguna-upz" className="h-9">
                                        <SelectValue placeholder="Pilih UPZ terkait" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {daftar_upz.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                {u.kode} - {u.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <fieldset className="space-y-2 rounded-lg border p-3">
                            <legend className="px-1 text-xs font-semibold">Peran Pengguna (wajib minimal satu)</legend>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {daftar_peran.map((p) => (
                                    <label key={p.kode} className="flex items-center gap-2 text-xs">
                                        <Checkbox
                                            checked={form.data.peran.includes(p.kode)}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'peran',
                                                    checked
                                                        ? [...form.data.peran, p.kode]
                                                        : form.data.peran.filter((x) => x !== p.kode),
                                                )
                                            }
                                        />
                                        <span>{p.nama}</span>
                                    </label>
                                ))}
                            </div>
                            {form.errors.peran && <p className="text-destructive text-xs">{form.errors.peran}</p>}
                        </fieldset>

                        <SheetFooter className="border-t pt-4">
                            <Button type="button" variant="outline" onClick={() => setDrawerTerbuka(false)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing} className="h-9">
                                {form.processing ? 'Menyimpan...' : 'Simpan Pengguna'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <Dialog open={Boolean(targetReset)} onOpenChange={(open) => !open && setTargetReset(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Atur Ulang Kata Sandi</DialogTitle>
                        <DialogDescription>
                            Tetapkan kata sandi baru untuk akun <strong>{targetReset?.name}</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={kirimResetSandi} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="reset-password">Kata Sandi Baru (minimal 8 karakter huruf dan angka)</Label>
                            <Input
                                id="reset-password"
                                type="password"
                                required
                                value={formReset.data.password}
                                onChange={(e) => formReset.setData('password', e.target.value)}
                                className="h-9"
                            />
                            {formReset.errors.password && <p className="text-destructive text-xs">{formReset.errors.password}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="reset-password-confirm">Ulangi Kata Sandi Baru</Label>
                            <Input
                                id="reset-password-confirm"
                                type="password"
                                required
                                value={formReset.data.password_confirmation}
                                onChange={(e) => formReset.setData('password_confirmation', e.target.value)}
                                className="h-9"
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setTargetReset(null)} className="h-9">
                                Batal
                            </Button>
                            <Button type="submit" disabled={formReset.processing} className="h-9">
                                Simpan Kata Sandi
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={Boolean(targetToggle)} onOpenChange={(open) => !open && setTargetToggle(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {targetToggle?.status === 'aktif' ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Kembali Akun Pengguna?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {targetToggle?.status === 'aktif'
                                ? `Akun ${targetToggle.name} tidak akan bisa masuk ke sistem sampai diaktifkan kembali.`
                                : `Akun ${targetToggle?.name} akan dapat masuk kembali ke sistem sesuai perannya.`}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction destructive={targetToggle?.status === 'aktif'} onClick={konfirmasiToggleStatus}>
                            {targetToggle?.status === 'aktif' ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
