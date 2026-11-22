import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, Columns3, Rows3 } from 'lucide-react';
import * as React from 'react';

const Table = React.forwardRef<HTMLTableElement, React.HTMLAttributes<HTMLTableElement>>(({ className, ...props }, ref) => (
    <div className="relative w-full overflow-x-auto">
        <table ref={ref} className={cn('w-full caption-bottom text-sm', className)} {...props} />
    </div>
));
Table.displayName = 'Table';

const TableHeader = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(({ className, ...props }, ref) => (
    <thead ref={ref} className={cn('bg-surface-subtle sticky top-0 z-10 [&_tr]:border-b', className)} {...props} />
));
TableHeader.displayName = 'TableHeader';

const TableBody = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(({ className, ...props }, ref) => (
    <tbody ref={ref} className={cn('[&_tr:last-child]:border-0', className)} {...props} />
));
TableBody.displayName = 'TableBody';

const TableFooter = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(({ className, ...props }, ref) => (
    <tfoot ref={ref} className={cn('bg-surface-subtle border-t font-medium [&>tr]:last:border-b-0', className)} {...props} />
));
TableFooter.displayName = 'TableFooter';

const TableRow = React.forwardRef<HTMLTableRowElement, React.HTMLAttributes<HTMLTableRowElement>>(({ className, ...props }, ref) => (
    <tr ref={ref} className={cn('hover:bg-surface-subtle border-border border-b transition-colors', className)} {...props} />
));
TableRow.displayName = 'TableRow';

const TableHead = React.forwardRef<HTMLTableCellElement, React.ThHTMLAttributes<HTMLTableCellElement>>(({ className, ...props }, ref) => (
    <th
        ref={ref}
        className={cn(
            'text-muted-foreground px-3 py-2.5 text-left align-middle text-[11px] font-semibold tracking-wider uppercase [&:has([role=checkbox])]:pr-0',
            className,
        )}
        {...props}
    />
));
TableHead.displayName = 'TableHead';

const TableCell = React.forwardRef<HTMLTableCellElement, React.TdHTMLAttributes<HTMLTableCellElement>>(({ className, ...props }, ref) => (
    <td ref={ref} className={cn('px-3 py-2.5 align-middle [&:has([role=checkbox])]:pr-0', className)} {...props} />
));
TableCell.displayName = 'TableCell';

const TableCaption = React.forwardRef<HTMLTableCaptionElement, React.HTMLAttributes<HTMLTableCaptionElement>>(({ className, ...props }, ref) => (
    <caption ref={ref} className={cn('text-muted-foreground mt-3 text-xs', className)} {...props} />
));
TableCaption.displayName = 'TableCaption';

export interface DataTableColumn<T> {
    id: string;
    header: string;
    cell: (row: T) => React.ReactNode;
    sortValue?: (row: T) => string | number;
    sortable?: boolean;
    align?: 'left' | 'right' | 'center';
    hideable?: boolean;
    className?: string;
}

export interface DataTableProps<T> {
    columns: DataTableColumn<T>[];
    data: T[];
    keyExtractor: (row: T, index: number) => string | number;
    loading?: boolean;
    emptyMessage?: string;
    emptyAction?: React.ReactNode;
    footerRow?: React.ReactNode;
    defaultDensity?: 'nyaman' | 'padat';
    enableDensityToggle?: boolean;
    enableColumnVisibility?: boolean;
    page?: number;
    perPage?: number;
    total?: number;
    onPageChange?: (page: number) => void;
    onRowClick?: (row: T) => void;
    className?: string;
}

export function DataTable<T>({
    columns,
    data,
    keyExtractor,
    loading = false,
    emptyMessage = 'Belum ada data untuk ditampilkan.',
    emptyAction,
    footerRow,
    defaultDensity = 'nyaman',
    enableDensityToggle = true,
    enableColumnVisibility = true,
    page = 1,
    perPage = 15,
    total,
    onPageChange,
    onRowClick,
    className,
}: DataTableProps<T>) {
    const [density, setDensity] = React.useState<'nyaman' | 'padat'>(defaultDensity);
    const [sortState, setSortState] = React.useState<{ id: string; arah: 'asc' | 'desc' } | null>(null);
    const [kolomTersembunyi, setKolomTersembunyi] = React.useState<string[]>([]);

    const kolomTampak = React.useMemo(() => columns.filter((kol) => !kolomTersembunyi.includes(kol.id)), [columns, kolomTersembunyi]);

    const dataTerurut = React.useMemo(() => {
        if (!sortState) {
            return data;
        }
        const kolom = columns.find((k) => k.id === sortState.id);
        if (!kolom || !kolom.sortValue) {
            return data;
        }
        const ambilNilai = kolom.sortValue;
        const salinan = [...data];
        salinan.sort((a, b) => {
            const va = ambilNilai(a);
            const vb = ambilNilai(b);
            if (va === vb) {
                return 0;
            }
            const hasil = va > vb ? 1 : -1;
            return sortState.arah === 'asc' ? hasil : -hasil;
        });
        return salinan;
    }, [data, columns, sortState]);

    const toggleSort = (kolom: DataTableColumn<T>) => {
        if (!kolom.sortable) {
            return;
        }
        setSortState((prev) => {
            if (!prev || prev.id !== kolom.id) {
                return { id: kolom.id, arah: 'asc' };
            }
            if (prev.arah === 'asc') {
                return { id: kolom.id, arah: 'desc' };
            }
            return null;
        });
    };

    const toggleKolom = (id: string) => {
        setKolomTersembunyi((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]));
    };

    const totalItem = total ?? data.length;
    const totalHalaman = Math.max(1, Math.ceil(totalItem / perPage));
    const adaToolbar = enableDensityToggle || enableColumnVisibility;

    return (
        <div className={cn('bg-card border-border rounded-xl border', className)}>
            {adaToolbar && (
                <div className="border-border flex flex-wrap items-center justify-end gap-2 border-b px-4 py-2.5">
                    {enableDensityToggle && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-8 gap-1.5 text-xs"
                            onClick={() => setDensity((prev) => (prev === 'nyaman' ? 'padat' : 'nyaman'))}
                        >
                            <Rows3 className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Kepadatan: {density === 'nyaman' ? 'Nyaman' : 'Padat'}</span>
                        </Button>
                    )}

                    {enableColumnVisibility && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="outline" size="sm" className="h-8 gap-1.5 text-xs">
                                    <Columns3 className="h-3.5 w-3.5" aria-hidden="true" />
                                    <span>Kolom</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                {columns
                                    .filter((kol) => kol.hideable !== false)
                                    .map((kol) => (
                                        <DropdownMenuCheckboxItem
                                            key={kol.id}
                                            checked={!kolomTersembunyi.includes(kol.id)}
                                            onCheckedChange={() => toggleKolom(kol.id)}
                                        >
                                            {kol.header}
                                        </DropdownMenuCheckboxItem>
                                    ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            )}

            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        {kolomTampak.map((kol) => (
                            <TableHead
                                key={kol.id}
                                className={cn(
                                    kol.align === 'right' && 'text-right',
                                    kol.align === 'center' && 'text-center',
                                    kol.className,
                                )}
                            >
                                {kol.sortable ? (
                                    <button
                                        type="button"
                                        onClick={() => toggleSort(kol)}
                                        className={cn(
                                            'hover:text-foreground inline-flex items-center gap-1 uppercase',
                                            kol.align === 'right' && 'ml-auto',
                                        )}
                                    >
                                        <span>{kol.header}</span>
                                        {sortState?.id === kol.id ? (
                                            sortState.arah === 'asc' ? (
                                                <ArrowUp className="h-3.5 w-3.5" aria-hidden="true" />
                                            ) : (
                                                <ArrowDown className="h-3.5 w-3.5" aria-hidden="true" />
                                            )
                                        ) : (
                                            <ArrowUpDown className="h-3.5 w-3.5 opacity-60" aria-hidden="true" />
                                        )}
                                    </button>
                                ) : (
                                    kol.header
                                )}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>

                <TableBody>
                    {loading ? (
                        Array.from({ length: 5 }).map((_, idx) => (
                            <TableRow key={`skeleton-${idx}`}>
                                {kolomTampak.map((kol) => (
                                    <TableCell key={kol.id} className={density === 'padat' ? 'py-2' : 'py-3'}>
                                        <Skeleton className="h-4 w-full max-w-[180px]" />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : dataTerurut.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={kolomTampak.length} className="py-10 text-center">
                                <p className="text-muted-foreground text-sm">{emptyMessage}</p>
                                {emptyAction && <div className="mt-3 flex justify-center">{emptyAction}</div>}
                            </TableCell>
                        </TableRow>
                    ) : (
                        dataTerurut.map((baris, idx) => (
                            <TableRow
                                key={keyExtractor(baris, idx)}
                                onClick={onRowClick ? () => onRowClick(baris) : undefined}
                                className={cn(
                                    density === 'padat' ? 'h-9 text-[13px]' : 'h-12 text-sm',
                                    onRowClick && 'cursor-pointer',
                                )}
                            >
                                {kolomTampak.map((kol) => (
                                    <TableCell
                                        key={kol.id}
                                        className={cn(
                                            density === 'padat' ? 'py-1.5' : 'py-2.5',
                                            kol.align === 'right' && 'text-right tabular-nums',
                                            kol.align === 'center' && 'text-center',
                                            kol.className,
                                        )}
                                    >
                                        {kol.cell(baris)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    )}
                </TableBody>

                {footerRow && <TableFooter>{footerRow}</TableFooter>}
            </Table>

            {onPageChange && totalHalaman > 1 && (
                <div className="border-border flex flex-wrap items-center justify-between gap-2 border-t px-4 py-3 text-xs">
                    <span className="text-muted-foreground">
                        Halaman {page} dari {totalHalaman} ({totalItem} data)
                    </span>
                    <div className="flex items-center gap-1.5">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-8 gap-1 px-2.5"
                            disabled={page <= 1}
                            onClick={() => onPageChange(page - 1)}
                        >
                            <ChevronLeft className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Sebelumnya</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-8 gap-1 px-2.5"
                            disabled={page >= totalHalaman}
                            onClick={() => onPageChange(page + 1)}
                        >
                            <span>Berikutnya</span>
                            <ChevronRight className="h-3.5 w-3.5" aria-hidden="true" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

export { Table, TableBody, TableCaption, TableCell, TableFooter, TableHead, TableHeader, TableRow };
