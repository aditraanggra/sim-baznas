import { PublicLayout } from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowRight, FileCheck2, MapPin, Phone, QrCode, Search, ShieldCheck } from 'lucide-react';

interface InfoLembaga {
    lembaga?: string;
    kabupaten?: string;
    alamat?: string;
    telepon?: string;
    whatsapp?: string;
    email?: string;
    website?: string;
}

export default function BerandaPublik({ lembaga = {} }: { lembaga?: InfoLembaga }) {
    return (
        <PublicLayout
            title="Portal Layanan Publik BAZNAS Kabupaten Cianjur"
            description="Portal layanan publik BAZNAS Kabupaten Cianjur untuk melacak status permohonan bantuan, memverifikasi SK UPZ resmi, dan mengecek keaslian dokumen QR."
        >
            <div className="space-y-6">
                <section className="bg-card border-border rounded-2xl border p-6 shadow-xs">
                    <p className="text-brand text-xs font-medium tracking-wide uppercase">Layanan Masyarakat Tanpa Login</p>
                    <h1 className="font-display text-foreground mt-1 text-[22px] leading-[30px] font-semibold">
                        Portal Layanan Publik &amp; Verifikasi
                    </h1>
                    <p className="text-muted-foreground mt-1.5 text-sm leading-5">
                        Akses mandiri bagi pemohon bantuan, pengurus Unit Pengumpul Zakat (UPZ), dan masyarakat Kabupaten Cianjur untuk memantau layanan serta memeriksa keabsahan dokumen resmi BAZNAS.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2">
                    <Link
                        href={route('publik.lacak-permohonan')}
                        className="bg-card border-border hover:border-brand/50 group flex flex-col justify-between rounded-2xl border p-5 shadow-xs transition-all hover:-translate-y-0.5"
                    >
                        <div>
                            <div className="bg-brand-soft text-brand flex h-10 w-10 items-center justify-center rounded-xl">
                                <Search className="h-5 w-5" aria-hidden="true" />
                            </div>
                            <h2 className="text-foreground mt-4 text-base font-semibold">Lacak Permohonan Bantuan</h2>
                            <p className="text-muted-foreground mt-1.5 text-sm leading-5">
                                Pantau progres penanganan permohonan bantuan menggunakan Kode Tracking dari tanda terima dan 4 digit akhir nomor telepon.
                            </p>
                        </div>
                        <span className="text-brand mt-4 inline-flex items-center gap-1.5 text-xs font-medium">
                            <span>Buka pelacakan</span>
                            <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </span>
                    </Link>

                    <Link
                        href={route('publik.verifikasi-sk-upz')}
                        className="bg-card border-border hover:border-brand/50 group flex flex-col justify-between rounded-2xl border p-5 shadow-xs transition-all hover:-translate-y-0.5"
                    >
                        <div>
                            <div className="bg-brand-soft text-brand flex h-10 w-10 items-center justify-center rounded-xl">
                                <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                            </div>
                            <h2 className="text-foreground mt-4 text-base font-semibold">Verifikasi SK UPZ Resmi</h2>
                            <p className="text-muted-foreground mt-1.5 text-sm leading-5">
                                Periksa keabsahan Surat Keputusan, nama UPZ (DKM, Desa, Instansi, Sekolah), wilayah, serta masa berlaku resmi.
                            </p>
                        </div>
                        <span className="text-brand mt-4 inline-flex items-center gap-1.5 text-xs font-medium">
                            <span>Periksa status SK</span>
                            <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </span>
                    </Link>
                </section>

                <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                    <h2 className="text-foreground text-base font-semibold">Verifikasi Keaslian Kwitansi &amp; Surat Dinas (QR)</h2>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Setiap kwitansi penerimaan ZIS dan surat dinas resmi BAZNAS Kabupaten Cianjur dilengkapi kode QR verifikasi di bagian bawah dokumen.
                    </p>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        <div className="bg-surface-subtle border-border flex items-start gap-3 rounded-xl border p-3.5">
                            <QrCode className="text-brand mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <div>
                                <p className="text-foreground text-sm font-medium">Kwitansi Penerimaan ZIS</p>
                                <p className="text-muted-foreground mt-0.5 text-xs leading-4">
                                    Pindai QR pada kwitansi untuk memastikan nomor, tanggal terbit, metode, dan total nominal tercatat sah tanpa menampilkan data pribadi muzakki.
                                </p>
                            </div>
                        </div>

                        <div className="bg-surface-subtle border-border flex items-start gap-3 rounded-xl border p-3.5">
                            <FileCheck2 className="text-brand mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <div>
                                <p className="text-foreground text-sm font-medium">Surat Dinas Keluar</p>
                                <p className="text-muted-foreground mt-0.5 text-xs leading-4">
                                    Pindai QR pada surat resmi untuk memvalidasi nomor surat, perihal, tujuan, dan pejabat penandatangan yang mengesahkan.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="bg-card border-border rounded-2xl border p-5 shadow-xs">
                    <h2 className="text-foreground text-sm font-semibold">Layanan Tatap Muka &amp; Kontak Resmi</h2>
                    <div className="text-muted-foreground mt-3 grid gap-2 text-xs sm:grid-cols-2">
                        <div className="flex items-start gap-2">
                            <MapPin className="text-brand mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>{lembaga.alamat ?? 'Jl. Raya Bandung Sadewata No. 108B Cianjur'}</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <Phone className="text-brand mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>
                                Telp. {lembaga.telepon ?? '(0263) 264866'} · WA {lembaga.whatsapp ?? '087771345995'}
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
