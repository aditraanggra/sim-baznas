import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import * as React from 'react';

export interface PageHeaderProps {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: React.ReactNode;
    className?: string;
}

export function PageHeader({ title, description, breadcrumbs = [], actions, className }: PageHeaderProps) {
    return (
        <div className={cn('flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between', className)}>
            <div className="min-w-0 space-y-1">
                {breadcrumbs.length > 0 && (
                    <nav aria-label="Breadcrumb" className="text-muted-foreground flex flex-wrap items-center gap-1 text-xs font-medium">
                        {breadcrumbs.map((item, idx) => {
                            const terakhir = idx === breadcrumbs.length - 1;
                            return (
                                <React.Fragment key={`${item.href}-${idx}`}>
                                    {idx > 0 && <ChevronRight className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />}
                                    {terakhir ? (
                                        <span className="text-foreground">{item.title}</span>
                                    ) : (
                                        <Link href={item.href} className="hover:text-foreground transition-colors">
                                            {item.title}
                                        </Link>
                                    )}
                                </React.Fragment>
                            );
                        })}
                    </nav>
                )}
                <h1 className="text-foreground text-[22px] leading-[30px] font-semibold tracking-[-0.01em]">{title}</h1>
                {description && <p className="text-muted-foreground text-sm">{description}</p>}
            </div>

            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
