import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Download, ExternalLink, Printer } from 'lucide-react';
import * as React from 'react';

export interface PratinjauPdfModalProps {
    terbuka: boolean;
    onTutup: () => void;
    judul: string;
    subjudul?: string;
    urlLihat: string;
    urlUnduh: string;
    urlCetak?: string;
    aksiOtomatisCetak?: boolean;
}

export function PratinjauPdfModal({
    terbuka,
    onTutup,
    judul,
    subjudul,
    urlLihat,
    urlUnduh,
    urlCetak,
    aksiOtomatisCetak = false,
}: PratinjauPdfModalProps) {
    const iframeRef = React.useRef<HTMLIFrameElement>(null);

    const tanganiCetak = () => {
        try {
            const win = iframeRef.current?.contentWindow;
            if (win) {
                win.focus();
                win.print();
                return;
            }
        } catch {
            // Fallback bila iframe dibatasi browser saat print
        }
        window.open(urlCetak ?? urlLihat, '_blank', 'noopener,noreferrer');
    };

    const onIframeLoad = () => {
        if (aksiOtomatisCetak) {
            tanganiCetak();
        }
    };

    return (
        <Dialog open={terbuka} onOpenChange={(open) => !open && onTutup()}>
            <DialogContent className="flex h-[88vh] w-[calc(100vw-2rem)] max-w-5xl flex-col gap-3 p-4 sm:max-w-5xl sm:p-5">
                <DialogHeader className="border-border shrink-0 border-b pb-3 pr-8 text-left">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                            <DialogTitle className="truncate text-base font-semibold">{judul}</DialogTitle>
                            {subjudul && <DialogDescription className="mt-0.5 truncate text-xs">{subjudul}</DialogDescription>}
                        </div>
                        <div className="flex shrink-0 flex-wrap items-center gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={tanganiCetak} className="h-9 gap-1.5">
                                <Printer className="h-4 w-4" aria-hidden="true" />
                                <span>Cetak</span>
                            </Button>
                            <Button asChild variant="outline" size="sm" className="h-9 gap-1.5">
                                <a href={urlLihat} target="_blank" rel="noopener noreferrer">
                                    <ExternalLink className="h-4 w-4" aria-hidden="true" />
                                    <span>Tab Baru</span>
                                </a>
                            </Button>
                            <Button asChild size="sm" className="h-9 gap-1.5">
                                <a href={urlUnduh}>
                                    <Download className="h-4 w-4" aria-hidden="true" />
                                    <span>Unduh PDF</span>
                                </a>
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                <div className="border-border bg-surface-subtle relative min-h-0 flex-1 overflow-hidden rounded-xl border">
                    {urlLihat && (
                        <iframe
                            ref={iframeRef}
                            src={urlLihat}
                            title={`Pratinjau ${judul}`}
                            onLoad={onIframeLoad}
                            className="h-full w-full border-0 bg-white"
                        />
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
