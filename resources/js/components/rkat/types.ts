export type KodeBidangRkat = 'pengumpulan' | 'distribusi' | 'umum';

export interface TahapStatusInfo {
    status: 'menunggu' | 'draft' | 'diajukan' | 'terverifikasi' | string;
    catatan_verifikasi: string | null;
    diajukan_pada: string | null;
    diverifikasi_pada: string | null;
}

export interface TahapBidangDetail extends TahapStatusInfo {
    bidang_kode: KodeBidangRkat;
    label: string;
    pengaju_nama: string | null;
    verifikator_nama: string | null;
}

export interface AturanAmilItem {
    id: number;
    pos_anggaran_id: number;
    kode_pos: string;
    urutan: number;
    uraian: string;
    kelompok_penyaluran: string;
    persen_bp: number;
    persen_maks_bp: number;
    penerimaan: number;
    hak_amil: number;
    alokasi_penyaluran: number;
}

export interface KelompokPenyaluranItem {
    kode: string;
    label: string;
    kode_pos_l3: string[];
    pagu: number;
    terpakai: number;
    selisih: number;
    terpenuhi: boolean;
}

export interface RingkasanAlokasi {
    tahap: Record<string, TahapStatusInfo>;
    pengumpulan: {
        target: number;
        total_neraca: number;
        selisih: number;
        terpenuhi: boolean;
        total_hak_amil: number;
        total_alokasi_penyaluran: number;
        total_luar_neraca: number;
        rincian_amil: AturanAmilItem[];
    };
    distribusi: {
        terbuka: boolean;
        total_pagu: number;
        total_terpakai: number;
        selisih: number;
        terpenuhi: boolean;
        kelompok: KelompokPenyaluranItem[];
    };
    umum: {
        terbuka: boolean;
        pagu_hak_amil: number;
        tambahan_hibah_saldo: number;
        total_pagu: number;
        terpakai: number;
        selisih: number;
        terpenuhi: boolean;
    };
}

export interface JenisKegiatanItem {
    id: number;
    kode: string | null;
    nama: string;
    pos_anggaran_id: number;
    pos_kode: string | null;
    pos_nama: string | null;
    pos_program_id: number | null;
    program_kode: string | null;
    program_nama: string | null;
    satuan_default: string;
    keterangan: string | null;
    aktif: boolean;
}

export interface KegiatanRencanaItem {
    id: number;
    rkat_jenis_kegiatan_id: number;
    nama_kegiatan: string;
    pos_anggaran_id: number;
    pos_kode: string | null;
    pos_nama: string | null;
    pos_program_id: number | null;
    program_kode: string | null;
    program_nama: string | null;
    jumlah: number;
    satuan: string;
    frekuensi: number;
    besaran: number;
    total: number;
    keterangan: string | null;
}

export interface OpsiPosItem {
    id: number;
    kode: string;
    nama: string;
    kelompok?: string | null;
    kelompok_label?: string | null;
}

export interface OpsiPelengkapItem {
    id: number;
    kode: string;
    nama: string;
    tipe_nilai: string;
}

export interface BarisKegiatanGabungan {
    id: number;
    bidang_kode: KodeBidangRkat;
    bidang_label: string;
    pos_kode: string;
    pos_nama: string;
    program_kode: string | null;
    program_nama: string | null;
    jenis_nama: string;
    nama_kegiatan: string;
    jumlah: number;
    satuan: string;
    frekuensi: number;
    besaran: number;
    total: number;
    keterangan: string | null;
}

export interface KelompokKegiatanGabungan {
    bidang_kode: KodeBidangRkat;
    bidang_label: string;
    subtotal: number;
    baris: BarisKegiatanGabungan[];
}
