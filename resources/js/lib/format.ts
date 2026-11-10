export const formatAngka = (nilai: number | null | undefined): string => (nilai === null || nilai === undefined ? '—' : new Intl.NumberFormat('id-ID').format(nilai));

export const formatRupiah = (nilai: number | null | undefined, ringkas = false): string => {
    if (nilai === null || nilai === undefined) return '—';
    if (ringkas && Math.abs(nilai) >= 1_000_000_000) return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(nilai / 1_000_000_000)} M`;
    if (ringkas && Math.abs(nilai) >= 1_000_000) return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(nilai / 1_000_000)} Jt`;
    return `Rp ${formatAngka(nilai)}`;
};

/** BR-12: persen dihitung dari nilai asli, tampil 1 desimal. */
export const formatPersen = (pembilang: number | null | undefined, penyebut: number | null | undefined): string => {
    if (!penyebut || pembilang === null || pembilang === undefined) return 'n/a';
    return `${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format((pembilang / penyebut) * 100)}%`;
};
