import { EmptyState, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PublicLayout } from '@/layouts/public-layout';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import * as React from 'react';

interface HasilSk {
    id: number;
    nama: string;
    jenis: string;
    wilayah: string;
    nomor_sk: string | null;
    berlaku_mulai: string | null;
    berlaku_sampai: string | null;
    status: string;
}

export default function VerifikasiSkUpz({ q, hasil }: { q: string; hasil: HasilSk[] }) {
    const [kataKunci, setKataKunci] = React.useState(q);

    const cari = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('publik.verifikasi-sk-upz'), { q: kataKunci }, { preserveState: true });
    };

    return (
        <PublicLayout
            title="Verifikasi SK UPZ BAZNAS Cianjur"
            description="Periksa keabsahan dan masa berlaku Surat Keputusan Unit Pengumpul Zakat (UPZ) resmi BAZNAS Kabupaten Cianjur."
        >
            <div className="space-y-6">
                <div className="space-y-2">
                    <h1 className="font-display text-foreground text-2xl font-semibold">Verifikasi SK Unit Pengumpul Zakat (UPZ)</h1>
                    <p className="text-muted-foreground text-sm">
                        Masukkan nama masjid/lembaga, nama desa, kecamatan, atau nomor SK untuk memastikan status keaktifan UPZ.
                    </p>
                </div>

                <form onSubmit={cari} className="bg-card border-border flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-end">
                    <div className="flex-1 space-y-1.5">
                        <Label htmlFor="cari-sk-upz">Nama UPZ, Desa, atau Nomor SK</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-sk-upz"
                                value={kataKunci}
                                onChange={(e) => setKataKunci(e.target.value)}
                                placeholder="Contoh: DKM Agung atau Karangtengah"
                                className="h-10 pl-9"
                            />
                        </div>
                    </div>
                    <Button type="submit" className="bg-brand hover:bg-brand-hover h-10 text-white">
                        Periksa Status SK
                    </Button>
                </form>

                {q !== '' && hasil.length === 0 ? (
                    <EmptyState
                        title="UPZ Tidak Ditemukan"
                        description="Tidak ditemukan data UPZ yang cocok dengan kata kunci tersebut. Periksa kembali ejaan atau hubungi kantor BAZNAS Kabupaten Cianjur."
                    />
                ) : (
                    <div className="space-y-3">
                        {hasil.map((item) => (
                            <article key={item.id} className="bg-card border-border rounded-xl border p-5">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <h2 className="text-foreground text-base font-semibold">{item.nama}</h2>
                                        <p className="text-muted-foreground text-xs">
                                            {item.jenis} · {item.wilayah}
                                        </p>
                                    </div>
                                    <StatusBadge
                                        status={item.status}
                                        variant={item.status === 'aktif' || item.status === 'segera_habis' ? 'aktif' : item.status === 'habis' ? 'habis' : 'draft'}
                                        label={
                                            item.status === 'aktif' || item.status === 'segera_habis'
                                                ? 'SK Aktif'
                                                : item.status === 'habis'
                                                  ? 'SK Habis Masa Berlaku'
                                                  : 'Belum Terbit SK'
                                        }
                                    />
                                </div>

                                <dl className="mt-4 grid gap-3 border-t pt-3 text-xs sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">Nomor SK</dt>
                                        <dd className="text-foreground mt-0.5 font-medium">{item.nomor_sk ?? 'Belum tersedia'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Masa Berlaku</dt>
                                        <dd className="text-foreground mt-0.5 font-medium">
                                            {item.berlaku_mulai && item.berlaku_sampai ? `${item.berlaku_mulai} s.d. ${item.berlaku_sampai}` : '-'}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
