import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rencana (RKAT)', href: '/pkp/rencana' },
    { title: 'Impor', href: '/pkp/rencana/impor' },
];

interface Draft {
    id: number;
    tahun: number;
    label: string;
    sumber: string;
}

const LANGKAH = ['Unggah', 'Pratinjau', 'Terapkan'];

export function PenandaLangkah({ aktif }: { aktif: number }) {
    return (
        <ol className="flex gap-4 text-sm" aria-label="Langkah impor">
            {LANGKAH.map((t, i) => (
                <li
                    key={t}
                    aria-current={aktif === i + 1 ? 'step' : undefined}
                    className={aktif === i + 1 ? 'font-semibold underline' : 'text-muted-foreground'}
                >
                    {i + 1}. {t}
                    {aktif === i + 1 && <span className="sr-only"> (langkah saat ini)</span>}
                </li>
            ))}
        </ol>
    );
}

export default function Impor({ tahun_default, draft }: { tahun_default: number; draft: Draft[] }) {
    const { data, setData, post, processing, errors } = useForm<{ tahun: number; berkas: File | null }>({ tahun: tahun_default, berkas: null });

    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('pkp.rencana.impor.unggah'), { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Impor RKAT" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="flex flex-col gap-4 rounded-xl border p-4">
                    <h1 className="text-lg font-semibold">Impor RKAT dari Excel</h1>
                    <PenandaLangkah aktif={1} />
                    <p className="text-muted-foreground text-sm">
                        Template yang dikenali: <strong>Rencana 2022-2031</strong> (sheet A1-A7, kolom <code>Tahun YYYY</code>). Pilih tahun yang akan
                        diambil dari berkas. Impor ulang tidak menggandakan data.
                    </p>

                    <form onSubmit={kirim} className="grid gap-3 md:max-w-xl">
                        <label className="grid gap-1 text-sm">
                            Tahun
                            <input
                                type="number"
                                min={2000}
                                max={2100}
                                required
                                className="rounded-md border px-3 py-2"
                                value={data.tahun}
                                onChange={(e) => setData('tahun', Number(e.target.value))}
                                aria-invalid={!!errors.tahun}
                            />
                        </label>
                        {errors.tahun && (
                            <p role="alert" className="text-sm text-red-700">
                                Galat: {errors.tahun}
                            </p>
                        )}
                        <label className="grid gap-1 text-sm">
                            Berkas Excel (.xlsx)
                            <input
                                type="file"
                                required
                                accept=".xlsx"
                                onChange={(e) => setData('berkas', e.target.files?.[0] ?? null)}
                                aria-invalid={!!errors.berkas}
                            />
                        </label>
                        {errors.berkas && (
                            <p role="alert" className="text-sm text-red-700">
                                Galat: {errors.berkas}
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={processing || !data.berkas}
                            className="bg-primary text-primary-foreground w-fit rounded-md px-4 py-2 text-sm disabled:opacity-50"
                        >
                            {processing ? 'Memproses…' : 'Unggah dan pratinjau'}
                        </button>
                    </form>
                    <Link className="w-fit text-sm underline" href={route('pkp.rencana')}>
                        Kembali ke daftar RKAT
                    </Link>
                </section>

                <section className="overflow-x-auto rounded-xl border">
                    <h2 className="p-3 font-semibold">Draft yang dapat menjadi tujuan impor ulang</h2>
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Tahun</th>
                                <th className="px-3 py-2">Versi</th>
                                <th className="px-3 py-2">Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            {draft.length === 0 && (
                                <tr className="border-t">
                                    <td colSpan={3} className="text-muted-foreground px-3 py-2">
                                        Belum ada versi Draft.
                                    </td>
                                </tr>
                            )}
                            {draft.map((d) => (
                                <tr key={d.id} className="border-t">
                                    <td className="px-3 py-2 tabular-nums">{d.tahun}</td>
                                    <td className="px-3 py-2">{d.label}</td>
                                    <td className="px-3 py-2">{d.sumber === 'impor' ? 'Impor' : 'Manual'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
            </div>
        </AppLayout>
    );
}
