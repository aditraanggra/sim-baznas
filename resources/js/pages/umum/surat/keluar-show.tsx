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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertCircle, Ban, CheckCircle2, FileDown, Lock, Pencil, Printer, RotateCcw, Send } from 'lucide-react';
import * as React from 'react';

interface Props {
    surat: {
        id: number;
        uuid: string;
        nomor: string | null;
        jenis: string;
        jenis_kode: string;
        template_nama: string | null;
        surat_masuk: { id: number; nomor_agenda: string; pengirim: string; perihal: string } | null;
        tanggal: string | null;
        sifat: string;
        lampiran: string | null;
        tujuan: string;
        alamat_tujuan: string | null;
        perihal: string;
        isi: string;
        tembusan: string | null;
        nama_penandatangan: string;
        jabatan_penandatangan: string;
        status_kode: string;
        status_label: string;
        pembuat: string;
        diajukan_at: string | null;
        penyetuju: string | null;
        disetujui_at: string | null;
        catatan_revisi: string | null;
        pembatal: string | null;
        dibatalkan_at: string | null;
        alasan_batal: string | null;
        lampiran_nama: string | null;
        lampiran_url: string | null;
        url_verifikasi_publik: string | null;
    };
    cetak_logs: { id: number; cetak_ke: number; pencetak: string; waktu: string | null }[];
    akses: {
        boleh_kelola: boolean;
        boleh_setujui: boolean;
        terblokir_maker_checker: boolean;
        boleh_batalkan: boolean;
    };
}

export default function SuratKeluarShow({ surat, cetak_logs, akses }: Props) {
    const [dialogSetujui, setDialogSetujui] = React.useState(false);
    const [dialogRevisi, setDialogRevisi] = React.useState(false);
    const [dialogBatal, setDialogBatal] = React.useState(false);

    const formRevisi = useForm({ catatan_revisi: '' });
    const formBatal = useForm({ alasan_batal: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SDM, Administrasi & Umum', href: '/umum' },
        { title: 'Surat Keluar', href: '/umum/surat/keluar' },
        { title: surat.nomor ?? `Draft #${surat.id}`, href: `/umum/surat/keluar/${surat.id}` },
    ];

    const isDraft = surat.status_kode === 'draft';
    const isMenunggu = surat.status_kode === 'menunggu_persetujuan';
    const isFinal = surat.status_kode === 'disetujui';
    const isBatal = surat.status_kode === 'dibatalkan';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={surat.nomor ?? `Draft Surat Keluar #${surat.id}`} />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title={surat.nomor ?? 'Draft Surat Keluar'}
                    description={`${surat.jenis_kode} · ${surat.jenis} · Kepada: ${surat.tujuan}`}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <>
                            <Button asChild variant="outline" className="h-9 gap-1.5">
                                <a href={route('umum.surat.keluar.pdf', { suratKeluar: surat.id })} target="_blank" rel="noreferrer">
                                    <Printer className="h-4 w-4" aria-hidden="true" />
                                    <span>{isFinal ? 'Cetak PDF Resmi' : 'Pratinjau PDF Draft'}</span>
                                </a>
                            </Button>

                            {isDraft && akses.boleh_kelola && (
                                <>
                                    <Button asChild variant="outline" className="h-9 gap-1.5">
                                        <Link href={route('umum.surat.keluar.edit', surat.id)}>
                                            <Pencil className="h-4 w-4" aria-hidden="true" />
                                            <span>Ubah Draft</span>
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        className="h-9 gap-1.5"
                                        onClick={() => router.post(route('umum.surat.keluar.ajukan', surat.id), {}, { preserveScroll: true })}
                                    >
                                        <Send className="h-4 w-4" aria-hidden="true" />
                                        <span>Ajukan Persetujuan</span>
                                    </Button>
                                </>
                            )}

                            {isMenunggu && akses.boleh_setujui && (
                                <>
                                    <Button type="button" variant="outline" className="h-9 gap-1.5" onClick={() => setDialogRevisi(true)}>
                                        <RotateCcw className="h-4 w-4" aria-hidden="true" />
                                        <span>Kembalikan Revisi</span>
                                    </Button>
                                    <Button type="button" className="h-9 gap-1.5" onClick={() => setDialogSetujui(true)}>
                                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                        <span>Setujui &amp; Terbitkan Nomor</span>
                                    </Button>
                                </>
                            )}

                            {akses.boleh_batalkan && (
                                <Button type="button" variant="destructive" className="h-9 gap-1.5" onClick={() => setDialogBatal(true)}>
                                    <Ban className="h-4 w-4" aria-hidden="true" />
                                    <span>Batalkan</span>
                                </Button>
                            )}
                        </>
                    }
                />

                {isFinal && (
                    <div className="bg-success-soft border-success/25 text-success flex items-center gap-2.5 rounded-xl border px-4 py-3 text-sm">
                        <Lock className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>
                            Dokumen final dan terkunci. Disetujui oleh {surat.penyetuju ?? '-'} pada {surat.disetujui_at ?? '-'}.
                        </span>
                    </div>
                )}

                {isBatal && (
                    <div className="bg-destructive-soft border-destructive/25 text-destructive flex items-center gap-2.5 rounded-xl border px-4 py-3 text-sm">
                        <Ban className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>
                            Dibatalkan oleh {surat.pembatal ?? '-'} pada {surat.dibatalkan_at ?? '-'}. Alasan: {surat.alasan_batal}
                        </span>
                    </div>
                )}

                {isMenunggu && akses.terblokir_maker_checker && (
                    <div className="bg-warning-soft border-warning/30 text-warning flex items-center gap-2.5 rounded-xl border px-4 py-3 text-sm">
                        <AlertCircle className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>Anda adalah pembuat draft surat ini. Sesuai prinsip Maker-Checker, persetujuan harus dilakukan oleh pejabat berwenang lain.</span>
                    </div>
                )}

                {isDraft && surat.catatan_revisi && (
                    <div className="bg-warning-soft border-warning/30 text-warning rounded-xl border px-4 py-3 text-sm">
                        <p className="font-semibold">Catatan Revisi:</p>
                        <p className="mt-0.5">{surat.catatan_revisi}</p>
                    </div>
                )}

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="space-y-5 lg:col-span-2">
                        <div className="bg-card border-border rounded-2xl border p-6">
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-2 border-b pb-4">
                                <div>
                                    <p className="text-muted-foreground text-xs">Perihal</p>
                                    <h2 className="text-foreground text-lg font-semibold">{surat.perihal}</h2>
                                </div>
                                <StatusBadge
                                    status={surat.status_kode}
                                    variant={isFinal ? 'final' : isBatal ? 'dibatalkan' : isMenunggu ? 'warning' : 'draft'}
                                    label={surat.status_label}
                                />
                            </div>

                            <div className="bg-surface-subtle/60 rounded-xl border p-5 font-serif text-sm leading-relaxed whitespace-pre-line">
                                {surat.isi}
                            </div>

                            {surat.tembusan && (
                                <div className="text-muted-foreground mt-4 border-t pt-3 text-xs whitespace-pre-line">
                                    <strong className="text-foreground">Tembusan:</strong>
                                    {'\n'}
                                    {surat.tembusan}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-5">
                        <section className="bg-card border-border rounded-2xl border p-5">
                            <h3 className="text-foreground text-base font-semibold">Metadata Dokumen</h3>
                            <dl className="mt-3 space-y-2.5 text-sm">
                                <div>
                                    <dt className="text-muted-foreground text-xs">Tanggal Surat</dt>
                                    <dd className="text-foreground font-medium">{surat.tanggal ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Tujuan</dt>
                                    <dd className="text-foreground font-medium">{surat.tujuan}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Sifat / Lampiran</dt>
                                    <dd className="text-foreground">
                                        {surat.sifat.toUpperCase()} · {surat.lampiran || 'Tanpa lampiran'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Penandatangan</dt>
                                    <dd className="text-foreground font-medium">{surat.nama_penandatangan}</dd>
                                    <dd className="text-muted-foreground text-xs">{surat.jabatan_penandatangan}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Dibuat Oleh</dt>
                                    <dd className="text-foreground">{surat.pembuat}</dd>
                                </div>
                                {surat.surat_masuk && (
                                    <div>
                                        <dt className="text-muted-foreground text-xs">Balasan Untuk Surat Masuk</dt>
                                        <dd>
                                            <Link href={route('umum.surat.masuk.show', surat.surat_masuk.id)} className="text-primary hover:underline">
                                                {surat.surat_masuk.nomor_agenda}
                                            </Link>
                                        </dd>
                                    </div>
                                )}
                                {surat.lampiran_url && (
                                    <div>
                                        <dt className="text-muted-foreground text-xs">Berkas Lampiran</dt>
                                        <dd>
                                            <a href={surat.lampiran_url} className="text-primary inline-flex items-center gap-1 hover:underline">
                                                <FileDown className="h-3.5 w-3.5" aria-hidden="true" />
                                                <span>{surat.lampiran_nama}</span>
                                            </a>
                                        </dd>
                                    </div>
                                )}
                                {surat.url_verifikasi_publik && (
                                    <div>
                                        <dt className="text-muted-foreground text-xs">Tautan Verifikasi QR Publik</dt>
                                        <dd>
                                            <a
                                                href={surat.url_verifikasi_publik}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary text-xs break-all hover:underline"
                                            >
                                                Buka Halaman Verifikasi
                                            </a>
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </section>

                        {cetak_logs.length > 0 && (
                            <section className="bg-card border-border rounded-2xl border p-5">
                                <h3 className="text-foreground text-base font-semibold">Riwayat Cetak PDF ({cetak_logs.length})</h3>
                                <ul className="mt-3 space-y-2 text-xs">
                                    {cetak_logs.map((log) => (
                                        <li key={log.id} className="flex items-center justify-between border-b pb-1.5 last:border-b-0">
                                            <span className="text-foreground font-medium">
                                                Cetak ke-{log.cetak_ke} · {log.pencetak}
                                            </span>
                                            <span className="text-muted-foreground tabular-nums">{log.waktu}</span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </div>
                </div>
            </div>

            <AlertDialog open={dialogSetujui} onOpenChange={setDialogSetujui}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Setujui Surat Keluar &amp; Terbitkan Nomor?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Setelah disetujui, nomor surat resmi akan diterbitkan otomatis dan isi surat terkunci secara permanen.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                router.post(
                                    route('umum.surat.keluar.setujui', surat.id),
                                    {},
                                    { preserveScroll: true, onFinish: () => setDialogSetujui(false) },
                                )
                            }
                        >
                            Setujui Surat Keluar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={dialogRevisi} onOpenChange={setDialogRevisi}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Kembalikan Surat Keluar untuk Revisi</AlertDialogTitle>
                        <AlertDialogDescription>Status akan dikembalikan ke Draft agar pembuat dapat memperbaiki isi surat.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="space-y-1.5 py-2">
                        <Label htmlFor="rev-catatan">Catatan Revisi (wajib)</Label>
                        <textarea
                            id="rev-catatan"
                            rows={3}
                            value={formRevisi.data.catatan_revisi}
                            onChange={(e) => formRevisi.setData('catatan_revisi', e.target.value)}
                            className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        />
                        {formRevisi.errors.catatan_revisi && <p className="text-destructive text-xs">{formRevisi.errors.catatan_revisi}</p>}
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={(e) => {
                                e.preventDefault();
                                formRevisi.post(route('umum.surat.keluar.revisi', surat.id), {
                                    preserveScroll: true,
                                    onSuccess: () => setDialogRevisi(false),
                                });
                            }}
                        >
                            Kembalikan ke Draft
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={dialogBatal} onOpenChange={setDialogBatal}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Batalkan Surat Keluar?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Surat tidak akan dihapus dari database demi jejak audit, tetapi statusnya berubah menjadi Dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="space-y-1.5 py-2">
                        <Label htmlFor="batal-alasan">Alasan Pembatalan (wajib)</Label>
                        <textarea
                            id="batal-alasan"
                            rows={3}
                            value={formBatal.data.alasan_batal}
                            onChange={(e) => formBatal.setData('alasan_batal', e.target.value)}
                            className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        />
                        {formBatal.errors.alasan_batal && <p className="text-destructive text-xs">{formBatal.errors.alasan_batal}</p>}
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Kembali</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={(e) => {
                                e.preventDefault();
                                formBatal.post(route('umum.surat.keluar.batalkan', surat.id), {
                                    preserveScroll: true,
                                    onSuccess: () => setDialogBatal(false),
                                });
                            }}
                        >
                            Batalkan Surat
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
