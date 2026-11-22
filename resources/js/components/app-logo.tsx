import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="bg-brand text-white flex aspect-square size-8 items-center justify-center rounded-md">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-1.5 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">SISFOZIS</span>
                <span className="text-muted-foreground truncate text-[11px] leading-tight">BAZNAS Kab. Cianjur</span>
            </div>
        </>
    );
}
