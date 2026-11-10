import AppLayout from '@/layouts/app-layout';
import { formatAngka, formatPersen, formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengguna', href: '/pengaturan/pengguna' }];

interface BarisPengguna {
    id: number;
    name: string;
    email: string;
    peran: string[];
}

export default function Pengguna({ pengguna, daftar_peran }: { pengguna: BarisPengguna[]; daftar_peran: string[] }) {
    const { data, setData, post, errors } = useForm<{
        id: string;
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        peran: string[];
    }>({ id: '', name: '', email: '', password: '', password_confirmation: '', peran: [] });

    const ubah = (nilai: Partial<typeof data>) => setData({ ...data, ...nilai });

    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('pengaturan.pengguna.simpan'), {
            preserveScroll: true,
            onSuccess: () => ubah({ id: '', name: '', email: '', password: '', password_confirmation: '', peran: [] }),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengguna" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="rounded-xl border p-4">
                    <h2 className="mb-3 font-semibold">{data.id ? 'Ubah pengguna' : 'Tambah pengguna'}</h2>
                    <form onSubmit={kirim} className="grid gap-3 md:grid-cols-2">
                        <input className="rounded-md border px-3 py-2" name="name" placeholder="Nama" value={data.name} onChange={(e) => ubah({ name: e.target.value })} required />
                        <input className="rounded-md border px-3 py-2" name="email" type="email" placeholder="Email" value={data.email} onChange={(e) => ubah({ email: e.target.value })} required />
                        <input
                            className="rounded-md border px-3 py-2"
                            name="password"
                            type="password"
                            placeholder={data.id ? 'Sandi baru (opsional)' : 'Sandi'}
                            value={data.password}
                            onChange={(e) => ubah({ password: e.target.value })}
                        />
                        <input
                            className="rounded-md border px-3 py-2"
                            name="password_confirmation"
                            type="password"
                            placeholder="Ulangi sandi"
                            value={data.password_confirmation}
                            onChange={(e) => ubah({ password_confirmation: e.target.value })}
                        />
                        <fieldset className="md:col-span-2 flex flex-wrap gap-3">
                            <legend className="mb-1 text-sm font-medium">Peran</legend>
                            {daftar_peran.map((p) => (
                                <label key={p} className="flex items-center gap-2 text-sm capitalize">
                                    <input
                                        type="checkbox"
                                        name="peran[]"
                                        checked={data.peran.includes(p)}
                                        onChange={(e) =>
                                            ubah({ peran: e.target.checked ? [...data.peran, p] : data.peran.filter((x) => x !== p) })
                                        }
                                    />
                                    {p}
                                </label>
                            ))}
                        </fieldset>
                        {Object.values(errors).length > 0 && (
                            <p className="md:col-span-2 text-sm text-red-600">{Object.values(errors).join(' ')}</p>
                        )}
                        <div className="md:col-span-2 flex gap-2">
                            <button type="submit" className="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm">
                                Simpan
                            </button>
                            {data.id && (
                                <button type="button" className="rounded-md border px-4 py-2 text-sm" onClick={() => ubah({ id: '', name: '', email: '', password: '', password_confirmation: '', peran: [] })}>
                                    Batal
                                </button>
                            )}
                        </div>
                    </form>
                </section>

                <section className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-3 py-2 text-left">Nama</th>
                                <th className="px-3 py-2 text-left">Email</th>
                                <th className="px-3 py-2 text-left">Peran</th>
                                <th className="px-3 py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {pengguna.map((u) => (
                                <tr key={u.id} className="border-t">
                                    <td className="px-3 py-2">{u.name}</td>
                                    <td className="px-3 py-2">{u.email}</td>
                                    <td className="px-3 py-2 capitalize">{u.peran.join(', ')}</td>
                                    <td className="px-3 py-2 text-right">
                                        <button
                                            className="text-sm underline"
                                            onClick={() => ubah({ id: String(u.id), name: u.name, email: u.email, password: '', password_confirmation: '', peran: u.peran })}
                                        >
                                            Ubah
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>

                <p className="text-muted-foreground text-xs">
                    Contoh format: {formatRupiah(22015000000)}; {formatAngka(201430)} orang; capaian {formatPersen(1, 3)}.
                </p>
            </div>
        </AppLayout>
    );
}
