import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Impor', href: '/pengaturan/impor' }];

interface Job {
    id: number;
    jenis: string;
    nama_berkas: string;
    status: string;
    total_baris: number;
    baris_sukses: number;
    baris_gagal: number;
    created_at: string | null;
}

export default function Impor({ jobs }: { jobs: Job[] }) {
    const { data, setData, post, processing, errors } = useForm<{ jenis: string; berkas: File | null }>({ jenis: 'rkat', berkas: null });
    const [langkah, setLangkah] = useState(1);

    const kirim = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('pengaturan.impor.simpan'), { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Impor" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="rounded-xl border p-4">
                    <h2 className="mb-1 font-semibold">Impor Excel</h2>
                    <p className="text-muted-foreground mb-4 text-sm">
                        {data.jenis === 'muzakki' || data.jenis === 'mustahik'
                            ? 'Kolom wajib: nama (opsional: kode, jenis, nik, telepon, desa, alamat, asnaf). Impor ulang tidak menggandakan data.'
                            : data.jenis === 'upz'
                              ? 'Kolom wajib: nama (opsional: kode, jenis_upz, desa, ketua, telepon, alamat). Impor ulang tidak menggandakan data.'
                              : `Kolom wajib: kode_pos, tipe_nilai, nilai${data.jenis === 'realisasi' ? ' (plus tahun, bulan)' : ''}. Baris divalidasi per baris; impor ulang tidak menggandakan data.`}
                    </p>

                    <ol className="mb-4 flex gap-2 text-sm">
                        {['Pilih jenis', 'Unggah berkas', 'Lihat hasil'].map((t, i) => (
                            <li key={t} className={langkah === i + 1 ? 'font-semibold' : 'text-muted-foreground'}>
                                {i + 1}. {t}
                            </li>
                        ))}
                    </ol>

                    <form onSubmit={kirim} className="grid gap-3 md:max-w-xl">
                        <div className="space-y-1.5">
                            <Label htmlFor="impor-jenis">Jenis Data Impor</Label>
                            <select
                                id="impor-jenis"
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                value={data.jenis}
                                onChange={(e) => setData('jenis', e.target.value)}
                            >
                                <option value="rkat">RKAT</option>
                                <option value="realisasi">Realisasi</option>
                                <option value="pos">Pos Anggaran</option>
                                <option value="muzakki">Data Muzakki</option>
                                <option value="mustahik">Data Mustahik</option>
                                <option value="upz">Data UPZ</option>
                            </select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="impor-berkas">Berkas Excel (.xlsx / .xls)</Label>
                            <input
                                id="impor-berkas"
                                type="file"
                                accept=".xlsx,.xls"
                                className="text-sm"
                                onChange={(e) => {
                                    setData('berkas', e.target.files?.[0] ?? null);
                                    setLangkah(2);
                                }}
                            />
                        </div>
                        {errors.berkas && <p className="text-sm text-red-600">{errors.berkas}</p>}
                        <button type="submit" disabled={processing || !data.berkas} className="bg-primary text-primary-foreground w-fit rounded-md px-4 py-2 text-sm disabled:opacity-50">
                            {processing ? 'Memproses…' : 'Impor'}
                        </button>
                    </form>
                </section>

                <section className="overflow-x-auto rounded-xl border">
                    <h2 className="p-3 font-semibold">Riwayat impor</h2>
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Berkas</th>
                                <th className="px-3 py-2">Jenis</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2 text-right">Total</th>
                                <th className="px-3 py-2 text-right">Sukses</th>
                                <th className="px-3 py-2 text-right">Gagal</th>
                                <th className="px-3 py-2">Waktu</th>
                                <th className="px-3 py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {jobs.map((j) => (
                                <tr key={j.id} className="border-t">
                                    <td className="px-3 py-2">{j.nama_berkas}</td>
                                    <td className="px-3 py-2">{j.jenis}</td>
                                    <td className="px-3 py-2">{j.status}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{j.total_baris}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{j.baris_sukses}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{j.baris_gagal}</td>
                                    <td className="px-3 py-2">{j.created_at ? new Date(j.created_at).toLocaleString('id-ID') : '-'}</td>
                                    <td className="px-3 py-2 text-right">
                                        <a className="underline" href={route('pengaturan.impor.hasil', j.id)}>
                                            Rincian
                                        </a>
                                    </td>
                                </tr>
                            ))}
                            {jobs.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-3 py-4" colSpan={8}>
                                        Belum ada impor.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </section>
            </div>
        </AppLayout>
    );
}
