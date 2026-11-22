import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

export interface EmptyStateProps {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
    onAction?: () => void;
    className?: string;
}

export function EmptyState({ title, description, actionLabel, actionHref, onAction, className }: EmptyStateProps) {
    return (
        <div className={cn('bg-card border-border rounded-xl border px-6 py-10 text-center', className)}>
            <h3 className="text-foreground text-base font-semibold">{title}</h3>
            <p className="text-muted-foreground mx-auto mt-1.5 max-w-md text-sm">{description}</p>
            {actionLabel && (actionHref || onAction) && (
                <div className="mt-4 flex justify-center">
                    {actionHref ? (
                        <Button asChild className="h-9">
                            <Link href={actionHref}>{actionLabel}</Link>
                        </Button>
                    ) : (
                        <Button type="button" onClick={onAction} className="h-9">
                            {actionLabel}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
