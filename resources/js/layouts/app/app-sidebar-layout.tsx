import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Toaster, toast } from '@/components/ui/sonner';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import * as React from 'react';

interface FlashProps {
    sukses?: string | null;
    peringatan?: string | null;
    error?: string | null;
}

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    const { flash } = usePage<{ flash?: FlashProps }>().props;
    const terakhirRef = React.useRef<{ sukses?: string | null; peringatan?: string | null; error?: string | null }>({});

    React.useEffect(() => {
        if (flash?.sukses && flash.sukses !== terakhirRef.current.sukses) {
            toast.success(flash.sukses);
            terakhirRef.current.sukses = flash.sukses;
        } else if (!flash?.sukses) {
            terakhirRef.current.sukses = null;
        }

        if (flash?.peringatan && flash.peringatan !== terakhirRef.current.peringatan) {
            toast.warning(flash.peringatan);
            terakhirRef.current.peringatan = flash.peringatan;
        } else if (!flash?.peringatan) {
            terakhirRef.current.peringatan = null;
        }

        if (flash?.error && flash.error !== terakhirRef.current.error) {
            toast.error(flash.error);
            terakhirRef.current.error = flash.error;
        } else if (!flash?.error) {
            terakhirRef.current.error = null;
        }
    }, [flash?.sukses, flash?.peringatan, flash?.error]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <main className="mx-auto w-full max-w-[1440px] flex-1">{children}</main>
            </AppContent>
            <Toaster />
        </AppShell>
    );
}
