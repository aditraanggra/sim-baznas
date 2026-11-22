import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Plus, Search } from 'lucide-react';
import * as React from 'react';

export interface ComboboxOption {
    value: string;
    label: string;
    sublabel?: string;
}

export interface ComboboxProps {
    options: ComboboxOption[];
    value?: string;
    onChange: (value: string) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    onCreateNew?: (query: string) => void;
    createNewLabel?: string;
    disabled?: boolean;
    id?: string;
    'aria-label'?: string;
    className?: string;
}

export function Combobox({
    options,
    value,
    onChange,
    placeholder = 'Pilih opsi...',
    searchPlaceholder = 'Cari data...',
    emptyText = 'Data tidak ditemukan.',
    onCreateNew,
    createNewLabel = 'Tambah baru',
    disabled = false,
    id,
    'aria-label': ariaLabel,
    className,
}: ComboboxProps) {
    const [terbuka, setTerbuka] = React.useState(false);
    const [kataKunci, setKataKunci] = React.useState('');
    const wadahRef = React.useRef<HTMLDivElement>(null);
    const inputCariRef = React.useRef<HTMLInputElement>(null);

    const terpilih = React.useMemo(() => options.find((opt) => opt.value === value) ?? null, [options, value]);

    const opsiTersaring = React.useMemo(() => {
        const q = kataKunci.trim().toLowerCase();
        if (!q) {
            return options;
        }
        return options.filter((opt) => opt.label.toLowerCase().includes(q) || (opt.sublabel && opt.sublabel.toLowerCase().includes(q)));
    }, [options, kataKunci]);

    React.useEffect(() => {
        if (terbuka) {
            inputCariRef.current?.focus();
        }
    }, [terbuka]);

    React.useEffect(() => {
        if (!terbuka) {
            return;
        }
        const klikDiLuar = (e: MouseEvent) => {
            if (wadahRef.current && !wadahRef.current.contains(e.target as Node)) {
                setTerbuka(false);
            }
        };
        const tekanEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setTerbuka(false);
            }
        };
        document.addEventListener('mousedown', klikDiLuar);
        document.addEventListener('keydown', tekanEscape);
        return () => {
            document.removeEventListener('mousedown', klikDiLuar);
            document.removeEventListener('keydown', tekanEscape);
        };
    }, [terbuka]);

    return (
        <div ref={wadahRef} className={cn('relative w-full', className)}>
            <button
                id={id}
                type="button"
                aria-label={ariaLabel}
                aria-expanded={terbuka}
                aria-haspopup="listbox"
                disabled={disabled}
                onClick={() => setTerbuka((prev) => !prev)}
                className={cn(
                    'border-border-strong bg-card text-foreground hover:bg-surface-subtle focus-visible:ring-ring flex h-9 w-full items-center justify-between gap-2 rounded-md border px-3 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                )}
            >
                <span className={cn('truncate text-left', !terpilih && 'text-muted-foreground')}>
                    {terpilih ? (
                        <span>
                            {terpilih.label}
                            {terpilih.sublabel ? <span className="text-muted-foreground ml-1.5 text-xs">{terpilih.sublabel}</span> : null}
                        </span>
                    ) : (
                        placeholder
                    )}
                </span>
                <ChevronsUpDown className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
            </button>

            {terbuka && (
                <div className="bg-popover border-border absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border shadow-lg">
                    <div className="border-border flex items-center gap-2 border-b px-3 py-2">
                        <Search className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        <input
                            ref={inputCariRef}
                            type="text"
                            value={kataKunci}
                            onChange={(e) => setKataKunci(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="placeholder:text-muted-foreground w-full bg-transparent text-sm outline-hidden"
                        />
                    </div>

                    <div role="listbox" className="max-h-60 overflow-y-auto p-1.5">
                        {opsiTersaring.length === 0 ? (
                            <p className="text-muted-foreground px-2 py-4 text-center text-xs">{emptyText}</p>
                        ) : (
                            opsiTersaring.map((opsi) => {
                                const aktif = opsi.value === value;
                                return (
                                    <button
                                        key={opsi.value}
                                        type="button"
                                        role="option"
                                        aria-selected={aktif}
                                        onClick={() => {
                                            onChange(opsi.value);
                                            setTerbuka(false);
                                            setKataKunci('');
                                        }}
                                        className={cn(
                                            'hover:bg-accent hover:text-accent-foreground flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm',
                                            aktif && 'bg-primary-soft text-primary-hover font-medium',
                                        )}
                                    >
                                        <Check className={cn('h-4 w-4 shrink-0', aktif ? 'opacity-100' : 'opacity-0')} aria-hidden="true" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate">{opsi.label}</p>
                                            {opsi.sublabel && <p className="text-muted-foreground truncate text-xs">{opsi.sublabel}</p>}
                                        </div>
                                    </button>
                                );
                            })
                        )}

                        {onCreateNew && (
                            <button
                                type="button"
                                onClick={() => {
                                    const q = kataKunci.trim();
                                    setTerbuka(false);
                                    onCreateNew(q);
                                }}
                                className="border-border text-primary hover:bg-primary-soft mt-1 flex w-full items-center gap-2 border-t px-2.5 py-2 text-left text-sm font-medium"
                            >
                                <Plus className="h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>
                                    {createNewLabel}
                                    {kataKunci.trim() ? `: "${kataKunci.trim()}"` : ''}
                                </span>
                            </button>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
