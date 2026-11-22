import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, ShieldCheck } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function TwoFactorChallenge() {
    const [pakaiRecovery, setPakaiRecovery] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('two-factor.challenge.verifikasi'));
    };

    return (
        <div className="bg-background flex min-h-svh flex-col">
            <Head title="Verifikasi Dua Faktor (2FA)" />

            <div className="bg-brand-deep border-gold border-b-4 text-white">
                <div className="mx-auto flex max-w-4xl items-center justify-center gap-3 px-6 py-6">
                    <div className="bg-brand flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 text-white">
                        <AppLogoIcon className="h-5 w-5 fill-current" />
                    </div>
                    <div>
                        <p className="font-display text-base font-semibold">SISFOZIS BAZNAS</p>
                        <p className="text-xs text-white/85">Verifikasi Keamanan Akun</p>
                    </div>
                </div>
            </div>

            <main className="flex flex-1 items-center justify-center px-4 py-8">
                <div className="bg-card border-border w-full max-w-[400px] rounded-2xl border p-6 shadow-sm sm:p-7">
                    <div className="mb-5 flex items-start gap-3">
                        <div className="bg-brand-soft text-brand flex h-10 w-10 shrink-0 items-center justify-center rounded-lg">
                            <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <h1 className="font-display text-foreground text-lg font-semibold">Autentikasi Dua Faktor</h1>
                            <p className="text-muted-foreground mt-0.5 text-xs">
                                {pakaiRecovery
                                    ? 'Masukkan salah satu kode pemulihan cadangan akun Anda.'
                                    : 'Masukkan kode 6 digit dari aplikasi autentikator (Google Authenticator / Authy).'}
                            </p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        {!pakaiRecovery ? (
                            <div className="space-y-1.5">
                                <Label htmlFor="code">Kode Autentikator 6 Digit</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={6}
                                    autoFocus
                                    required
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.replace(/\D+/g, '').slice(0, 6))}
                                    placeholder="000000"
                                    className="h-10 text-center text-base tracking-[0.3em] tabular-nums"
                                />
                                <InputError message={errors.code} />
                            </div>
                        ) : (
                            <div className="space-y-1.5">
                                <Label htmlFor="recovery_code">Kode Pemulihan</Label>
                                <Input
                                    id="recovery_code"
                                    name="recovery_code"
                                    type="text"
                                    autoFocus
                                    required
                                    value={data.recovery_code}
                                    onChange={(e) => setData('recovery_code', e.target.value.toUpperCase())}
                                    placeholder="XXXX-XXXX"
                                    className="h-10 font-mono uppercase"
                                />
                                <InputError message={errors.code || errors.recovery_code} />
                            </div>
                        )}

                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand hover:bg-brand-hover h-10 w-full text-white"
                        >
                            {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" aria-hidden="true" />}
                            <span>Verifikasi dan Masuk</span>
                        </Button>

                        <button
                            type="button"
                            onClick={() => {
                                setPakaiRecovery((prev) => !prev);
                                setData({ code: '', recovery_code: '' });
                            }}
                            className="text-muted-foreground hover:text-foreground w-full pt-1 text-center text-xs underline"
                        >
                            {pakaiRecovery ? 'Gunakan kode autentikator 6 digit' : 'Gunakan kode pemulihan cadangan'}
                        </button>
                    </form>
                </div>
            </main>
        </div>
    );
}
