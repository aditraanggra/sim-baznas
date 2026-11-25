import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface LoginForm {
    identitas: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [tampilSandi, setTampilSandi] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        identitas: '',
        password: '',
        remember: false,
    });

    const pesanIdentitas = errors.identitas || errors.email;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="bg-background flex min-h-svh flex-col">
            <Head title="Masuk ke SISFOZIS" />

            <div className="bg-brand-deep border-gold relative overflow-hidden border-b-4 text-white">
                <div className="mx-auto flex max-w-4xl items-center justify-center gap-3 px-6 py-7 text-center">
                    <div className="flex shrink-0 items-center justify-center rounded-xl border border-white/25 bg-white px-2.5 py-1.5 shadow-sm">
                        <img src="/images/logo-baznas.png" alt="Logo BAZNAS" className="h-9 w-auto object-contain" />
                    </div>
                    <div className="text-left">
                        <p className="font-display text-lg leading-tight font-semibold">SISFOZIS BAZNAS</p>
                        <p className="text-xs text-white/85">Kabupaten Cianjur</p>
                    </div>
                </div>
            </div>

            <main className="flex flex-1 items-center justify-center px-4 py-8">
                <div className="bg-card border-border w-full max-w-[400px] rounded-2xl border p-6 shadow-sm sm:p-7">
                    <div className="mb-6 space-y-1.5">
                        <h1 className="font-display text-foreground text-[22px] leading-[30px] font-semibold">Masuk ke Akun</h1>
                        <p className="text-muted-foreground text-sm">
                            Gunakan username atau alamat email resmi untuk masuk ke sistem.
                        </p>
                    </div>

                    {status && (
                        <div className="bg-success-soft text-success border-success/20 mb-5 rounded-lg border px-3 py-2 text-xs font-medium">
                            {status}
                        </div>
                    )}

                    <form className="space-y-4" onSubmit={submit}>
                        <div className="space-y-1.5">
                            <Label htmlFor="identitas">Username atau Email</Label>
                            <Input
                                id="identitas"
                                name="identitas"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="username"
                                value={data.identitas}
                                onChange={(e) => setData('identitas', e.target.value)}
                                placeholder="username atau nama@baznas.go.id"
                                className="h-10"
                            />
                            <InputError message={pesanIdentitas} />
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="password">Kata Sandi</Label>
                                {canResetPassword && (
                                    <TextLink href={route('password.request')} className="text-brand text-xs font-medium" tabIndex={5}>
                                        Lupa sandi?
                                    </TextLink>
                                )}
                            </div>
                            <div className="relative">
                                <Input
                                    id="password"
                                    name="password"
                                    type={tampilSandi ? 'text' : 'password'}
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Masukkan kata sandi"
                                    className="h-10 pr-10"
                                />
                                <button
                                    type="button"
                                    onClick={() => setTampilSandi((prev) => !prev)}
                                    aria-label={tampilSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute top-1/2 right-2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    {tampilSandi ? <EyeOff className="h-4 w-4" aria-hidden="true" /> : <Eye className="h-4 w-4" aria-hidden="true" />}
                                </button>
                            </div>
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center gap-2.5 pt-1">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                                checked={data.remember}
                                onCheckedChange={(checked) => setData('remember', Boolean(checked))}
                            />
                            <Label htmlFor="remember" className="text-sm font-normal">
                                Ingat sesi saya di perangkat ini
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            tabIndex={4}
                            disabled={processing}
                            className="bg-brand hover:bg-brand-hover mt-2 h-10 w-full text-white"
                        >
                            {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" aria-hidden="true" />}
                            <span>Masuk</span>
                        </Button>
                    </form>

                    <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-xs">
                        Akun amil dan pengurus UPZ diterbitkan oleh Administrator Sistem.
                    </p>
                </div>
            </main>
        </div>
    );
}
