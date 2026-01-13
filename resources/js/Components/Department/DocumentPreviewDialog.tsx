import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import {
    ArrowDownTrayIcon,
    XMarkIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    DocumentTextIcon,
    PlayIcon,
    PauseIcon,
    SpeakerWaveIcon,
    SpeakerXMarkIcon,
} from '@heroicons/react/24/outline';

interface DocumentData {
    uuid: string;
    title: string;
    original_name: string;
    file_name: string;
    file_url: string;
    preview_url: string;
    file_size: number;
    formatted_file_size: string;
    mime_type: string;
    extension: string;
    file_type: string;
    can_preview: boolean;
    preview_type: string;
    description: string | null;
    category: string | null;
}

interface DocumentPreviewDialogProps {
    document: DocumentData | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    departmentUuid: string;
}

export default function DocumentPreviewDialog({
    document,
    open,
    onOpenChange,
    departmentUuid,
}: DocumentPreviewDialogProps) {
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [videoPlaying, setVideoPlaying] = useState(false);
    const [audioPlaying, setAudioPlaying] = useState(false);
    const [isMuted, setIsMuted] = useState(false);

    useEffect(() => {
        if (open) {
            setIsLoading(true);
            setError(null);
        }
    }, [open, document?.uuid]);

    const handleDownload = () => {
        if (document) {
            window.open(`/api/departments/${departmentUuid}/documents/${document.uuid}/download`, '_blank');
        }
    };

    const toggleFullscreen = () => {
        setIsFullscreen(!isFullscreen);
    };

    const handleMediaLoad = () => {
        setIsLoading(false);
    };

    const handleMediaError = () => {
        setIsLoading(false);
        setError('Impossible de charger le fichier');
    };

    const renderPreview = () => {
        if (!document) return null;

        const previewType = document.preview_type;
        // Use preview_url for secure API access, fallback to file_url for direct storage access
        const fileUrl = document.preview_url || document.file_url;

        switch (previewType) {
            case 'image':
                return (
                    <div className="flex items-center justify-center w-full h-full bg-black/5 dark:bg-black/20 rounded-lg overflow-hidden">
                        {isLoading && (
                            <div className="absolute inset-0 flex items-center justify-center bg-muted">
                                <div className="animate-pulse text-muted-foreground">Chargement...</div>
                            </div>
                        )}
                        <img
                            src={fileUrl}
                            alt={document.title}
                            className={`max-w-full max-h-full object-contain ${isLoading ? 'opacity-0' : 'opacity-100'} transition-opacity`}
                            onLoad={handleMediaLoad}
                            onError={handleMediaError}
                        />
                    </div>
                );

            case 'video':
                return (
                    <div className="flex items-center justify-center w-full h-full bg-black rounded-lg overflow-hidden">
                        {isLoading && (
                            <div className="absolute inset-0 flex items-center justify-center bg-muted">
                                <div className="animate-pulse text-muted-foreground">Chargement de la vidéo...</div>
                            </div>
                        )}
                        <video
                            src={fileUrl}
                            controls
                            className={`max-w-full max-h-full ${isLoading ? 'opacity-0' : 'opacity-100'} transition-opacity`}
                            onLoadedData={handleMediaLoad}
                            onError={handleMediaError}
                            onPlay={() => setVideoPlaying(true)}
                            onPause={() => setVideoPlaying(false)}
                            muted={isMuted}
                        >
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    </div>
                );

            case 'audio':
                return (
                    <div className="flex flex-col items-center justify-center w-full h-full p-8 space-y-6">
                        <div className="w-32 h-32 rounded-full bg-gradient-to-br from-primary/20 to-primary/40 flex items-center justify-center">
                            <SpeakerWaveIcon className="w-16 h-16 text-primary" />
                        </div>
                        <div className="text-center">
                            <h3 className="font-medium text-lg">{document.title}</h3>
                            <p className="text-sm text-muted-foreground">{document.formatted_file_size}</p>
                        </div>
                        <audio
                            src={fileUrl}
                            controls
                            className="w-full max-w-md"
                            onLoadedData={handleMediaLoad}
                            onError={handleMediaError}
                            onPlay={() => setAudioPlaying(true)}
                            onPause={() => setAudioPlaying(false)}
                        >
                            Votre navigateur ne supporte pas la lecture audio.
                        </audio>
                    </div>
                );

            case 'pdf':
                return (
                    <div className="w-full h-full rounded-lg overflow-hidden">
                        {isLoading && (
                            <div className="absolute inset-0 flex items-center justify-center bg-muted">
                                <div className="animate-pulse text-muted-foreground">Chargement du PDF...</div>
                            </div>
                        )}
                        <iframe
                            src={`${fileUrl}#toolbar=1&navpanes=0&scrollbar=1`}
                            className="w-full h-full border-0"
                            title={document.title}
                            onLoad={handleMediaLoad}
                            onError={handleMediaError}
                        />
                    </div>
                );

            case 'text':
                return (
                    <div className="w-full h-full rounded-lg overflow-hidden">
                        <iframe
                            src={fileUrl}
                            className="w-full h-full border-0 bg-white dark:bg-gray-900"
                            title={document.title}
                            onLoad={handleMediaLoad}
                            onError={handleMediaError}
                        />
                    </div>
                );

            case 'office':
                // Use Office Online Viewer for Word, Excel, PowerPoint
                const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
                return (
                    <div className="w-full h-full flex flex-col items-center justify-center space-y-4">
                        <DocumentTextIcon className="w-20 h-20 text-muted-foreground" />
                        <div className="text-center">
                            <h3 className="font-medium text-lg">{document.title}</h3>
                            <p className="text-sm text-muted-foreground mb-4">
                                {document.extension.toUpperCase()} • {document.formatted_file_size}
                            </p>
                            <p className="text-sm text-muted-foreground mb-4">
                                La prévisualisation des documents Office nécessite un accès en ligne.
                            </p>
                            <div className="flex gap-2 justify-center">
                                <Button
                                    variant="outline"
                                    onClick={() => window.open(officeViewerUrl, '_blank')}
                                >
                                    Ouvrir avec Office Online
                                </Button>
                                <Button onClick={handleDownload}>
                                    <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                    Télécharger
                                </Button>
                            </div>
                        </div>
                    </div>
                );

            default:
                return (
                    <div className="w-full h-full flex flex-col items-center justify-center space-y-4">
                        <DocumentTextIcon className="w-20 h-20 text-muted-foreground" />
                        <div className="text-center">
                            <h3 className="font-medium text-lg">{document.title}</h3>
                            <p className="text-sm text-muted-foreground mb-4">
                                {document.extension.toUpperCase()} • {document.formatted_file_size}
                            </p>
                            <p className="text-sm text-muted-foreground mb-4">
                                Ce type de fichier ne peut pas être prévisualisé.
                            </p>
                            <Button onClick={handleDownload}>
                                <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                Télécharger le fichier
                            </Button>
                        </div>
                    </div>
                );
        }
    };

    if (!document) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className={`${
                    isFullscreen
                        ? 'max-w-[95vw] w-[95vw] max-h-[95vh] h-[95vh]'
                        : 'max-w-4xl w-full max-h-[85vh]'
                } flex flex-col p-0 gap-0`}
            >
                {/* Header */}
                <DialogHeader className="flex flex-row items-center justify-between px-4 py-3 border-b shrink-0">
                    <div className="flex-1 min-w-0 mr-4">
                        <DialogTitle className="truncate">{document.title}</DialogTitle>
                        <p className="text-sm text-muted-foreground truncate">
                            {document.original_name} • {document.formatted_file_size}
                        </p>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={toggleFullscreen}
                            title={isFullscreen ? 'Réduire' : 'Plein écran'}
                        >
                            {isFullscreen ? (
                                <ArrowsPointingInIcon className="w-4 h-4" />
                            ) : (
                                <ArrowsPointingOutIcon className="w-4 h-4" />
                            )}
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={handleDownload}
                            title="Télécharger"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4" />
                        </Button>
                    </div>
                </DialogHeader>

                {/* Preview Content */}
                <div className="flex-1 overflow-hidden p-4 relative min-h-0">
                    {error ? (
                        <div className="w-full h-full flex flex-col items-center justify-center space-y-4">
                            <div className="text-destructive text-center">
                                <p className="font-medium">{error}</p>
                                <p className="text-sm text-muted-foreground mt-2">
                                    Essayez de télécharger le fichier directement.
                                </p>
                            </div>
                            <Button onClick={handleDownload}>
                                <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                Télécharger
                            </Button>
                        </div>
                    ) : (
                        renderPreview()
                    )}
                </div>

                {/* Footer with document info */}
                {document.description && (
                    <div className="px-4 py-3 border-t bg-muted/50 shrink-0">
                        <p className="text-sm text-muted-foreground">{document.description}</p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
