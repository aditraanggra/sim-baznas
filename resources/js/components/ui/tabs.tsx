import { cn } from '@/lib/utils';
import * as React from 'react';

interface TabsContextValue {
    nilaiAktif: string;
    ubahNilai: (nilai: string) => void;
    daftarId: string;
}

const TabsContext = React.createContext<TabsContextValue | null>(null);

function useTabsContext(): TabsContextValue {
    const ctx = React.useContext(TabsContext);
    if (!ctx) {
        throw new Error('Komponen Tab harus digunakan di dalam <Tabs>.');
    }
    return ctx;
}

export interface TabsProps extends React.HTMLAttributes<HTMLDivElement> {
    defaultValue?: string;
    value?: string;
    onValueChange?: (value: string) => void;
}

export function Tabs({ defaultValue = '', value, onValueChange, className, children, ...props }: TabsProps) {
    const [internalValue, setInternalValue] = React.useState(defaultValue);
    const daftarId = React.useId();
    const nilaiAktif = value !== undefined ? value : internalValue;

    const ubahNilai = React.useCallback(
        (nilaiBaru: string) => {
            if (value === undefined) {
                setInternalValue(nilaiBaru);
            }
            onValueChange?.(nilaiBaru);
        },
        [value, onValueChange],
    );

    return (
        <TabsContext.Provider value={{ nilaiAktif, ubahNilai, daftarId }}>
            <div className={cn('w-full', className)} {...props}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

export function TabsList({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    const listRef = React.useRef<HTMLDivElement>(null);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') {
            return;
        }

        const pemicu = Array.from(listRef.current?.querySelectorAll<HTMLButtonElement>('[role="tab"]:not(:disabled)') ?? []);
        const idx = pemicu.findIndex((el) => el === document.activeElement);
        if (idx === -1 || pemicu.length === 0) {
            return;
        }

        e.preventDefault();
        const berikut = e.key === 'ArrowRight' ? (idx + 1) % pemicu.length : (idx - 1 + pemicu.length) % pemicu.length;
        pemicu[berikut]?.focus();
        pemicu[berikut]?.click();
    };

    return (
        <div
            ref={listRef}
            role="tablist"
            onKeyDown={handleKeyDown}
            className={cn('border-border inline-flex w-full items-center gap-1 overflow-x-auto border-b', className)}
            {...props}
        >
            {children}
        </div>
    );
}

export interface TabsTriggerProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    value: string;
}

export function TabsTrigger({ value, className, children, ...props }: TabsTriggerProps) {
    const { nilaiAktif, ubahNilai, daftarId } = useTabsContext();
    const aktif = nilaiAktif === value;

    return (
        <button
            type="button"
            role="tab"
            id={`${daftarId}-tab-${value}`}
            aria-selected={aktif}
            aria-controls={`${daftarId}-panel-${value}`}
            tabIndex={aktif ? 0 : -1}
            onClick={() => ubahNilai(value)}
            className={cn(
                'focus-visible:ring-ring inline-flex h-10 shrink-0 items-center justify-center border-b-2 px-3.5 text-sm font-medium whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:outline-hidden',
                aktif ? 'border-primary text-primary' : 'text-muted-foreground hover:text-foreground border-transparent',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}

export interface TabsContentProps extends React.HTMLAttributes<HTMLDivElement> {
    value: string;
}

export function TabsContent({ value, className, children, ...props }: TabsContentProps) {
    const { nilaiAktif, daftarId } = useTabsContext();

    if (nilaiAktif !== value) {
        return null;
    }

    return (
        <div
            role="tabpanel"
            id={`${daftarId}-panel-${value}`}
            aria-labelledby={`${daftarId}-tab-${value}`}
            tabIndex={0}
            className={cn('focus-visible:ring-ring mt-4 focus-visible:ring-2 focus-visible:outline-hidden', className)}
            {...props}
        >
            {children}
        </div>
    );
}
