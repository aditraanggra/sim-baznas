import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Autentikasi Dua Faktor (2FA)',
        href: '/settings/two-factor',
    },
];

interface Props {
    aktif: boolean;
    wajib: boolean;
    secret: string | null;
    qr_data_uri: string | null;
    recovery_codes: string[];
}

export default function TwoFactorSettings({ aktif, wajib, secret, qr_data_uri, recovery_codes }: Props) {
    const formAktifkan = useForm({
        secret: secret ?? '',
        code: '',
    });

    const formNonaktifkan = useForm<{ current_password: string; two_factor?: string }>({
        current_password: '',
    });

    const kirimAktifkan: FormEventHandler = (e) => {
        e.preventDefault();
        formAktifkan.post(route('two-factor.aktifkan'), {
            preserveScroll: true,
            onSuccess: () => formAktifkan.reset('code'),
        });
    };

    const kirimNonaktifkan: FormEventHandler = (e) => {
        e.preventDefault();
        formNonaktifkan.delete(route('two-factor.nonaktifkan'), {
            preserveScroll: true,
            onSuccess: () => formNonaktifkan.reset('current_password'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Autentikasi Dua Faktor (2FA)" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Autentikasi Dua Faktor (TOTP)"
                        description="Amankan akun dengan kode verifikasi 6 digit dari aplikasi autentikator."
                    />

                    {wajib && !aktif && (
                        <div className="bg-warning-soft border-warning/25 text-warning rounded-lg border px-4 py-3 text-sm">
                            Peran Administrator dan Keuangan wajib mengaktifkan 2FA sebelum melanjutkan penggunaan sistem.
                        </div>
                    )}

                    {!aktif ? (
                        <div className="bg-card border-border space-y-5 rounded-xl border p-5">
                            <div className="space-y-2">
                                <p className="text-foreground text-sm font-medium">
                                    1. Pindai kode QR menggunakan Google Authenticator atau Authy
                                </p>
                                {qr_data_uri && (
                                    <div className="bg-card border-border inline-block rounded-lg border p-3">
                                        <img
                                            src={qr_data_uri}
                                            alt="QR Autentikasi Dua Faktor"
                                            width={180}
                                            height={180}
                                        />
                                    </div>
                                )}
                                {secret && (
                                    <p className="text-muted-foreground text-xs">
                                        Kunci manual (Base32): <code className="bg-surface-subtle rounded px-1.5 py-0.5 font-mono text-foreground">{secret}</code>
                                    </p>
                                )}
                            </div>

                            <form onSubmit={kirimAktifkan} className="space-y-3 border-t pt-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="code">2. Masukkan kode 6 digit untuk mengonfirmasi</Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={6}
                                        required
                                        value={formAktifkan.data.code}
                                        onChange={(e) => formAktifkan.setData('code', e.target.value.replace(/\D+/g, '').slice(0, 6))}
                                        placeholder="123456"
                                        className="h-9 max-w-[180px] tabular-nums"
                                    />
                                    <InputError message={formAktifkan.errors.code} />
                                </div>

                                <Button type="submit" disabled={formAktifkan.processing} className="h-9">
                                    Aktifkan 2FA
                                </Button>
                            </form>
                        </div>
                    ) : (
                        <div className="bg-card border-border space-y-5 rounded-xl border p-5">
                            <div className="bg-success-soft border-success/20 text-success flex items-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-medium">
                                <ShieldCheck className="h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>Autentikasi dua faktor aktif pada akun ini.</span>
                            </div>

                            {recovery_codes.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-foreground text-sm font-medium">Kode Pemulihan Cadangan</p>
                                    <p className="text-muted-foreground text-xs">
                                        Simpan kode ini di tempat aman apabila perangkat autentikator Anda hilang.
                                    </p>
                                    <div className="bg-surface-subtle border-border grid grid-cols-2 gap-2 rounded-lg border p-3 font-mono text-xs">
                                        {recovery_codes.map((kode) => (
                                            <span key={kode}>{kode}</span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {!wajib && (
                                <form onSubmit={kirimNonaktifkan} className="space-y-3 border-t pt-4">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="current_password_2fa">Konfirmasi Kata Sandi untuk Menonaktifkan 2FA</Label>
                                        <Input
                                            id="current_password_2fa"
                                            type="password"
                                            required
                                            value={formNonaktifkan.data.current_password}
                                            onChange={(e) => formNonaktifkan.setData('current_password', e.target.value)}
                                            className="h-9 max-w-xs"
                                        />
                                        <InputError message={formNonaktifkan.errors.current_password || formNonaktifkan.errors.two_factor} />
                                    </div>

                                    <Button type="submit" variant="destructive" disabled={formNonaktifkan.processing} className="h-9">
                                        Nonaktifkan 2FA
                                    </Button>
                                </form>
                            )}
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
