import { EmptyState, InputRupiah, PageHeader, StatusBadge } from '@/components/app';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable, type DataTableColumn } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendistribusian', href: '/distribusi' },
    { title: 'Permohonan Bantuan', href: '/distribusi/permohonan' },
];

interface PermohonanRow {
    id: number;
    nomor: string;
    kode_tracking: string;
    tanggal: string | null;
    mustahik: string | null;
    program: string | null;
    sub_bidang: string;
    jenis_dana: string | null;
    asnaf: string | null;
    nominal_diajukan: number;
    nominal_disetujui: number | null;
    status_kode: string;
    langkah_sekarang: number;
    kesiapan_keuangan: string;
}

type PermohonanFormData = {
    tanggal: string;
    mustahik_id: string;
    program_id: string;
    jenis_dana_id: string;
    asnaf_id: string;
    nominal_diajukan: number;
    uraian: string;
};

interface Props {
    permohonan: { data: PermohonanRow[] };
    filter: { cari: string; status: string };
    mustahik_list: { id: number; kode: string; nama: string }[];
    program_list: { id: number; kode: string; nama: string; sub_bidang: string | null }[];
    jenis_dana_list: { id: number; kode: string; nama: string; wajib_asnaf: boolean }[];
    asnaf_list: { id: number; kode: string; nama: string }[];
    bisa_kelola: boolean;
}

export default function PermohonanIndex({ permohonan, filter, mustahik_list, program_list, jenis_dana_list, asnaf_list, bisa_kelola }: Props) {
    const [cari, setCari] = React.useState(filter.cari);
    const [status, setStatus] = React.useState(filter.status || 'semua');
    const [dialogBuka, setDialogBuka] = React.useState(false);

    const form = useForm<PermohonanFormData>({
        tanggal: new Date().toISOString().slice(0, 10),
        mustahik_id: '',
        program_id: program_list[0] ? String(program_list[0].id) : '',
        jenis_dana_id: jenis_dana_list[0] ? String(jenis_dana_list[0].id) : '',
        asnaf_id: asnaf_list[0] ? String(asnaf_list[0].id) : '',
        nominal_diajukan: 0,
        uraian: '',
    });

    const jenisTerpilih = jenis_dana_list.find((j) => String(j.id) === form.data.jenis_dana_id);
    const wajibAsnaf = Boolean(jenisTerpilih?.wajib_asnaf);

    React.useEffect(() => {
        if (!wajibAsnaf && form.data.asnaf_id !== '') {
            form.setData('asnaf_id', '');
        }
    }, [wajibAsnaf, form]);

    const terapkanFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('distribusi.permohonan.index'), { cari, status: status === 'semua' ? '' : status }, { preserveState: true });
    };

    const simpan = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('distribusi.permohonan.store'), {
            preserveScroll: true,
            onSuccess: () => setDialogBuka(false),
        });
    };

    const kolom: DataTableColumn<PermohonanRow>[] = [
        {
            id: 'nomor',
            header: 'Permohonan',
            cell: (r) => (
                <div>
                    <Link href={route('distribusi.permohonan.show', r.id)} className="text-primary font-medium hover:underline">
                        {r.nomor}
                    </Link>
                    <p className="text-muted-foreground text-xs">
                        {r.tanggal} · Lacak: {r.kode_tracking}
                    </p>
                </div>
            ),
        },
        { id: 'mustahik', header: 'Mustahik', cell: (r) => r.mustahik ?? '-' },
        {
            id: 'program',
            header: 'Program & Dana',
            cell: (r) => (
                <div>
                    <p className="text-foreground text-sm">{r.program ?? '-'}</p>
                    <p className="text-muted-foreground text-xs">
                        {r.jenis_dana ?? '-'}
                        {r.asnaf ? ` (${r.asnaf})` : ''}
                    </p>
                </div>
            ),
        },
        {
            id: 'nominal',
            header: 'Nominal',
            align: 'right',
            cell: (r) => (
                <div className="text-right tabular-nums">
                    <p className="font-medium">{formatRupiah(r.nominal_disetujui ?? r.nominal_diajukan)}</p>
                    {r.nominal_disetujui && <p className="text-muted-foreground text-xs">ACC</p>}
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Tahap (K8)',
            cell: (r) => (
                <StatusBadge
                    status={r.status_kode}
                    variant={r.status_kode === 'diterima_mustahik' ? 'success' : r.status_kode === 'ditolak' ? 'ditolak' : 'aktif'}
                    label={r.langkah_sekarang > 0 ? `L${r.langkah_sekarang}: ${r.status_kode.replaceAll('_', ' ')}` : 'Ditolak'}
                />
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permohonan Bantuan" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <PageHeader
                    title="Permohonan Bantuan Mustahik"
                    description="Kelola alur disposisi 7 langkah mulai dari Pelayanan Mustahik hingga diterima penerima manfaat."
                    breadcrumbs={breadcrumbs}
                    actions={
                        bisa_kelola && (
                            <Button type="button" className="h-9 gap-1.5" onClick={() => setDialogBuka(true)}>
                                <Plus className="h-4 w-4" aria-hidden="true" />
                                <span>Catat Permohonan</span>
                            </Button>
                        )
                    }
                />

                <form onSubmit={terapkanFilter} className="bg-card border-border grid gap-3 rounded-2xl border p-4 sm:grid-cols-4 sm:items-end">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="cari-permohonan">Cari Nomor, Kode Lacak, atau Mustahik</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                            <Input
                                id="cari-permohonan"
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Nomor PRM atau TRK..."
                                className="h-9 pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-status-perm">Tahap Permohonan</Label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger id="filter-status-perm" className="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Tahap</SelectItem>
                                <SelectItem value="diajukan">1. Diajukan</SelectItem>
                                <SelectItem value="disposisi_ketua">2. Disposisi Ketua</SelectItem>
                                <SelectItem value="asesmen_kabag">3. Asesmen Kabag</SelectItem>
                                <SelectItem value="ajuan_waka_2">4. Ajuan Waka II</SelectItem>
                                <SelectItem value="acc_ketua">5. ACC Ketua</SelectItem>
                                <SelectItem value="minta_keuangan">6. Minta Keuangan</SelectItem>
                                <SelectItem value="diterima_mustahik">7. Diterima Mustahik</SelectItem>
                                <SelectItem value="ditolak">Ditolak</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" variant="outline" className="h-9">
                        Terapkan
                    </Button>
                </form>

                {permohonan.data.length === 0 ? (
                    <EmptyState title="Belum Ada Permohonan" description="Catat permohonan baru untuk memulai alur disposisi." />
                ) : (
                    <DataTable data={permohonan.data} columns={kolom} keyExtractor={(r) => r.id} />
                )}

                <Dialog open={dialogBuka} onOpenChange={setDialogBuka}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Catat Permohonan Bantuan</DialogTitle>
                            <DialogDescription>Permohonan otomatis memperoleh nomor agenda dan kode tracking publik.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={simpan} className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-tanggal">Tanggal</Label>
                                    <Input
                                        id="p-tanggal"
                                        type="date"
                                        value={form.data.tanggal}
                                        onChange={(e) => form.setData('tanggal', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-nominal">Nominal Diajukan</Label>
                                    <InputRupiah
                                        id="p-nominal"
                                        value={form.data.nominal_diajukan}
                                        onChange={(v) => form.setData('nominal_diajukan', v)}
                                    />
                                    {form.errors.nominal_diajukan && <p className="text-destructive text-xs">{form.errors.nominal_diajukan}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="p-mustahik">Mustahik Pemohon</Label>
                                <Combobox
                                    value={form.data.mustahik_id}
                                    onChange={(v) => form.setData('mustahik_id', v)}
                                    placeholder="Pilih mustahik..."
                                    options={mustahik_list.map((m) => ({ value: String(m.id), label: `${m.nama} (${m.kode})` }))}
                                />
                                {form.errors.mustahik_id && <p className="text-destructive text-xs">{form.errors.mustahik_id}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="p-program">Program Bantuan</Label>
                                <Select value={form.data.program_id} onValueChange={(v) => form.setData('program_id', v)}>
                                    <SelectTrigger id="p-program">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {program_list.map((pr) => (
                                            <SelectItem key={pr.id} value={String(pr.id)}>
                                                {pr.nama} ({pr.sub_bidang})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="p-jenis-dana">Rencana Jenis Dana</Label>
                                    <Select value={form.data.jenis_dana_id} onValueChange={(v) => form.setData('jenis_dana_id', v)}>
                                        <SelectTrigger id="p-jenis-dana">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {jenis_dana_list.map((j) => (
                                                <SelectItem key={j.id} value={String(j.id)}>
                                                    {j.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="p-asnaf">Asnaf {wajibAsnaf ? '(Wajib Zakat)' : '(Tidak Berlaku)'}</Label>
                                    <Select
                                        value={form.data.asnaf_id}
                                        onValueChange={(v) => form.setData('asnaf_id', v)}
                                        disabled={!wajibAsnaf}
                                    >
                                        <SelectTrigger id="p-asnaf">
                                            <SelectValue placeholder={wajibAsnaf ? 'Pilih asnaf' : 'Tanpa asnaf'} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {asnaf_list.map((a) => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.asnaf_id && <p className="text-destructive text-xs">{form.errors.asnaf_id}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="p-uraian">Uraian Kebutuhan Bantuan</Label>
                                <Input
                                    id="p-uraian"
                                    value={form.data.uraian}
                                    onChange={(e) => form.setData('uraian', e.target.value)}
                                    placeholder="Keperluan pengobatan, modal usaha, atau pendidikan..."
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setDialogBuka(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Menyimpan...' : 'Simpan Permohonan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
