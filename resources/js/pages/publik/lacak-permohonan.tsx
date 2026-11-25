import { EmptyState, StatusBadge, Timeline } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PublicLayout } from '@/layouts/public-layout';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import * as React from 'react';

interface HasilLacak {
    kode_tracking: string;
    nomor: string;
    tanggal: string | null;
    pemohon_sama_mustahik: boolean;
    pemohon_samaran: string | null;
    penerima_manfaat_samaran: string | null;
    program: string;
    status_publik: string;
    judul_status: string;
    linimasa: {
        status_publik: string;
        judul: string;
        keterangan: string;
        waktu: string | null;
    }[];
}

interface Props {
    kode_tracking: string;
    telepon_4: string;
    sudah_dicari: boolean;
    pesan_error: string | null;
    hasil: HasilLacak | null;
}

export default function LacakPermohonan({ kode_tracking, telepon_4, sudah_dicari, pesan_error, hasil }: Props) {
    const [kode, setKode] = React.useState(kode_tracking);
    const [telp4, setTelp4] = React.useState(telepon_4);

    const cari = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('publik.lacak-permohonan'), { kode_tracking: kode, telepon_4: telp4, cari: 1 }, { preserveState: true });
    };

    return (
        <PublicLayout
            title="Lacak Status Permohonan Bantuan"
            description="Portal publik pemohon untuk memantau status permohonan bantuan BAZNAS Kabupaten Cianjur menggunakan kode tracking dan 4 digit akhir nomor telepon."
        >
            <div className="space-y-6">
                <div className="space-y-2">
                    <h1 className="font-display text-foreground text-2xl font-semibold">Lacak Permohonan Bantuan</h1>
                    <p className="text-muted-foreground text-sm">
                        Masukkan Kode Tracking dari tanda terima serta 4 digit terakhir nomor telepon pemohon untuk melihat progres penanganan.
                    </p>
                </div>

                <form onSubmit={cari} className="bg-card border-border grid gap-4 rounded-xl border p-4 sm:grid-cols-3 sm:items-end">
                    <div className="space-y-1.5">
                        <Label htmlFor="lacak-kode">Kode Tracking</Label>
                        <Input
                            id="lacak-kode"
                            value={kode}
                            onChange={(e) => setKode(e.target.value.toUpperCase())}
                            placeholder="Contoh: TRK-261122-ABCDE"
                            required
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="lacak-telp">4 Digit Akhir Telepon</Label>
                        <Input
                            id="lacak-telp"
                            value={telp4}
                            onChange={(e) => setTelp4(e.target.value.replace(/\D+/g, '').slice(0, 4))}
                            placeholder="Contoh: 5678"
                            maxLength={4}
                            required
                        />
                    </div>
                    <Button type="submit" className="bg-brand hover:bg-brand-hover h-10 gap-2 text-white">
                        <Search className="h-4 w-4" aria-hidden="true" />
                        <span>Lacak Status</span>
                    </Button>
                </form>

                {sudah_dicari && pesan_error && <EmptyState title="Data Tidak Cocok" description={pesan_error} />}

                {hasil && (
                    <div className="space-y-5">
                        <section className="bg-card border-border rounded-xl border p-5">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="text-muted-foreground text-xs">Kode Tracking {hasil.kode_tracking}</p>
                                    <h2 className="text-foreground text-lg font-semibold">{hasil.judul_status}</h2>
                                    <p className="text-muted-foreground text-sm">
                                        Pemohon: {hasil.pemohon_samaran ?? '-'}
                                        {!hasil.pemohon_sama_mustahik && hasil.penerima_manfaat_samaran
                                            ? ` · Penerima Manfaat: ${hasil.penerima_manfaat_samaran}`
                                            : ''}
                                        {` · Program: ${hasil.program}`}
                                    </p>
                                </div>
                                <StatusBadge
                                    status={hasil.status_publik}
                                    variant={hasil.status_publik === 'dicairkan' ? 'success' : hasil.status_publik === 'ditolak' ? 'ditolak' : 'aktif'}
                                    label={hasil.judul_status}
                                />
                            </div>
                        </section>

                        <section className="bg-card border-border rounded-xl border p-5">
                            <h3 className="text-foreground mb-4 text-base font-semibold">Riwayat Tahapan Penanganan</h3>
                            <Timeline
                                steps={hasil.linimasa.map((item, idx) => ({
                                    id: idx,
                                    status: item.judul,
                                    waktu: item.waktu ?? '-',
                                    catatan: item.keterangan,
                                    selesai: true,
                                }))}
                            />
                        </section>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
