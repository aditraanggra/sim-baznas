import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { Search } from 'lucide-react';
import * as React from 'react';

interface CommandContextValue {
    query: string;
    setQuery: (q: string) => void;
    rootRef: React.RefObject<HTMLDivElement | null>;
}

const CommandContext = React.createContext<CommandContextValue | null>(null);

function useCommandContext(): CommandContextValue {
    const ctx = React.useContext(CommandContext);
    if (!ctx) {
        throw new Error('Komponen Command harus berada di dalam <Command>.');
    }
    return ctx;
}

export function Command({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    const [query, setQuery] = React.useState('');
    const rootRef = React.useRef<HTMLDivElement>(null);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
            return;
        }
        const items = Array.from(rootRef.current?.querySelectorAll<HTMLButtonElement>('[data-command-item="true"]:not(:disabled)') ?? []);
        if (items.length === 0) {
            return;
        }
        e.preventDefault();
        const aktif = items.findIndex((el) => el === document.activeElement);
        const berikut = e.key === 'ArrowDown' ? (aktif + 1) % items.length : (aktif - 1 + items.length) % items.length;
        items[berikut]?.focus();
    };

    return (
        <CommandContext.Provider value={{ query, setQuery, rootRef }}>
            <div
                ref={rootRef}
                onKeyDown={handleKeyDown}
                className={cn('bg-popover text-popover-foreground flex h-full w-full flex-col overflow-hidden rounded-xl', className)}
                {...props}
            >
                {children}
            </div>
        </CommandContext.Provider>
    );
}

export interface CommandDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    children: React.ReactNode;
}

export function CommandDialog({
    open,
    onOpenChange,
    title = 'Pencarian Cepat',
    description = 'Cari modul atau menu tujuan dengan mengetik kata kunci.',
    children,
}: CommandDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="overflow-hidden p-0 sm:max-w-lg">
                <DialogTitle className="sr-only">{title}</DialogTitle>
                <DialogDescription className="sr-only">{description}</DialogDescription>
                <Command>{children}</Command>
            </DialogContent>
        </Dialog>
    );
}

export interface CommandInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    onQueryChange?: (query: string) => void;
}

export function CommandInput({
    className,
    placeholder = 'Ketik perintah atau menu tujuan...',
    onQueryChange,
    onChange,
    ...props
}: CommandInputProps) {
    const { query, setQuery } = useCommandContext();

    return (
        <div className="border-border flex items-center gap-2 border-b px-3.5">
            <Search className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
            <input
                type="text"
                value={query}
                onChange={(e) => {
                    const val = e.target.value;
                    setQuery(val);
                    onQueryChange?.(val);
                    onChange?.(e);
                }}
                placeholder={placeholder}
                className={cn(
                    'placeholder:text-muted-foreground h-11 w-full bg-transparent text-sm outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                    className,
                )}
                {...props}
            />
        </div>
    );
}

export function CommandList({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return (
        <div role="listbox" className={cn('max-h-72 overflow-y-auto p-2', className)} {...props}>
            {children}
        </div>
    );
}

export function CommandEmpty({ children = 'Tidak ada hasil yang cocok.', className }: { children?: React.ReactNode; className?: string }) {
    return <p className={cn('text-muted-foreground py-6 text-center text-sm', className)}>{children}</p>;
}

export function CommandGroup({ heading, className, children, ...props }: React.HTMLAttributes<HTMLDivElement> & { heading?: string }) {
    return (
        <div className={cn('py-1', className)} {...props}>
            {heading && <div className="text-muted-foreground px-2 py-1.5 text-[11px] font-semibold tracking-wider uppercase">{heading}</div>}
            <div className="space-y-0.5">{children}</div>
        </div>
    );
}

export interface CommandItemProps extends Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, 'onSelect'> {
    value?: string;
    onSelect?: () => void;
}

export function CommandItem({ value = '', onSelect, className, children, ...props }: CommandItemProps) {
    const { query } = useCommandContext();
    const cocok = !query.trim() || value.toLowerCase().includes(query.trim().toLowerCase());

    if (!cocok) {
        return null;
    }

    return (
        <button
            type="button"
            role="option"
            data-command-item="true"
            onClick={onSelect}
            className={cn(
                'hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm transition-colors outline-hidden',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
