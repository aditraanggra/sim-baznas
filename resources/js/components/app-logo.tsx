export default function AppLogo() {
    return (
        <>
            <div className="border-border/80 flex h-8 shrink-0 items-center justify-center rounded-md border bg-white px-1.5 py-0.5 shadow-sm">
                <img src="/images/logo-baznas.png" alt="Logo BAZNAS" className="h-6 w-auto object-contain" />
            </div>
            <div className="ml-1.5 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">SISFOZIS</span>
                <span className="text-muted-foreground truncate text-[11px] leading-tight">BAZNAS Kab. Cianjur</span>
            </div>
        </>
    );
}
