import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: {
    children: React.ReactNode;
    title?: string;
    description?: string;
}) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={route('home')} className="flex flex-col items-center gap-2 font-medium">
                            <div className="mb-1 flex items-center justify-center rounded-lg bg-white px-2.5 py-1.5 shadow-sm">
                                <img src="/images/logo-baznas.png" alt="Logo BAZNAS" className="h-9 w-auto object-contain" />
                            </div>
                            <span className="sr-only">{name}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-muted-foreground text-center text-sm">{description}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
