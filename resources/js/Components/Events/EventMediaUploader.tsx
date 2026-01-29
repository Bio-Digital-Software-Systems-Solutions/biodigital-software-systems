import React, { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { router } from '@inertiajs/react';
import { PhotoIcon, VideoCameraIcon, XMarkIcon, ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { EventMedia, EventMediaCollection } from '@/Types/event.d';

interface EventMediaUploaderProps {
    eventUuid: string;
    collection?: EventMediaCollection;
    onUploadComplete?: (media: EventMedia[]) => void;
    maxFiles?: number;
    acceptedTypes?: 'images' | 'videos' | 'all';
    className?: string;
}

interface UploadingFile {
    id: string;
    file: File;
    preview: string;
    progress: number;
    error?: string;
}

const ACCEPTED_IMAGE_TYPES = {
    'image/jpeg': ['.jpg', '.jpeg'],
    'image/png': ['.png'],
    'image/gif': ['.gif'],
    'image/webp': ['.webp'],
};

const ACCEPTED_VIDEO_TYPES = {
    'video/mp4': ['.mp4'],
    'video/webm': ['.webm'],
    'video/ogg': ['.ogg'],
    'video/quicktime': ['.mov'],
};

export default function EventMediaUploader({
    eventUuid,
    collection = 'gallery',
    onUploadComplete,
    maxFiles = 10,
    acceptedTypes = 'all',
    className = '',
}: EventMediaUploaderProps) {
    const [uploadingFiles, setUploadingFiles] = useState<UploadingFile[]>([]);
    const [isUploading, setIsUploading] = useState(false);

    const getAcceptedTypes = () => {
        if (acceptedTypes === 'images') return ACCEPTED_IMAGE_TYPES;
        if (acceptedTypes === 'videos') return ACCEPTED_VIDEO_TYPES;
        return { ...ACCEPTED_IMAGE_TYPES, ...ACCEPTED_VIDEO_TYPES };
    };

    const onDrop = useCallback(async (acceptedFiles: File[]) => {
        if (acceptedFiles.length === 0) return;

        const newFiles: UploadingFile[] = acceptedFiles.map((file) => ({
            id: Math.random().toString(36).substring(7),
            file,
            preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : '',
            progress: 0,
        }));

        setUploadingFiles((prev) => [...prev, ...newFiles]);
        setIsUploading(true);

        const uploadedMedia: EventMedia[] = [];

        for (const uploadingFile of newFiles) {
            try {
                const formData = new FormData();
                formData.append('file', uploadingFile.file);
                formData.append('collection', collection);

                const response = await fetch(route('events.media.store', eventUuid), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Erreur lors de l\'upload');
                }

                const data = await response.json();
                uploadedMedia.push(data.media);

                setUploadingFiles((prev) =>
                    prev.map((f) =>
                        f.id === uploadingFile.id ? { ...f, progress: 100 } : f
                    )
                );

                // Remove the file after a short delay
                setTimeout(() => {
                    setUploadingFiles((prev) => prev.filter((f) => f.id !== uploadingFile.id));
                }, 1000);
            } catch (error) {
                const errorMessage = error instanceof Error ? error.message : 'Erreur lors de l\'upload';
                setUploadingFiles((prev) =>
                    prev.map((f) =>
                        f.id === uploadingFile.id ? { ...f, error: errorMessage } : f
                    )
                );
                toast.error(errorMessage);
            }
        }

        setIsUploading(false);

        if (uploadedMedia.length > 0) {
            toast.success(`${uploadedMedia.length} fichier(s) uploadé(s) avec succès`);
            onUploadComplete?.(uploadedMedia);
        }
    }, [eventUuid, collection, onUploadComplete]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: getAcceptedTypes(),
        maxFiles,
        maxSize: 100 * 1024 * 1024, // 100MB
    });

    const removeFile = (id: string) => {
        setUploadingFiles((prev) => prev.filter((f) => f.id !== id));
    };

    return (
        <div className={className}>
            <div
                {...getRootProps()}
                className={`
                    border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors
                    ${isDragActive
                        ? 'border-primary bg-primary/5 dark:bg-primary/10'
                        : 'border-gray-300 dark:border-gray-600 hover:border-primary dark:hover:border-primary'
                    }
                `}
            >
                <input {...getInputProps()} />
                <div className="flex flex-col items-center justify-center space-y-2">
                    <div className="flex space-x-2">
                        {(acceptedTypes === 'all' || acceptedTypes === 'images') && (
                            <PhotoIcon className="h-8 w-8 text-gray-400" />
                        )}
                        {(acceptedTypes === 'all' || acceptedTypes === 'videos') && (
                            <VideoCameraIcon className="h-8 w-8 text-gray-400" />
                        )}
                    </div>
                    {isDragActive ? (
                        <p className="text-sm text-primary font-medium">
                            Déposez les fichiers ici...
                        </p>
                    ) : (
                        <>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                <span className="font-medium text-primary">Cliquez pour sélectionner</span> ou glissez-déposez
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-500">
                                {acceptedTypes === 'images' && 'Images: JPG, PNG, GIF, WebP'}
                                {acceptedTypes === 'videos' && 'Vidéos: MP4, WebM, OGG, MOV'}
                                {acceptedTypes === 'all' && 'Images et vidéos (max 100 MB)'}
                            </p>
                        </>
                    )}
                </div>
            </div>

            {/* Uploading Files Preview */}
            {uploadingFiles.length > 0 && (
                <div className="mt-4 space-y-3">
                    {uploadingFiles.map((file) => (
                        <div
                            key={file.id}
                            className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            {file.preview ? (
                                <img
                                    src={file.preview}
                                    alt={file.file.name}
                                    className="h-12 w-12 object-cover rounded"
                                />
                            ) : (
                                <div className="h-12 w-12 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded">
                                    <VideoCameraIcon className="h-6 w-6 text-gray-500 dark:text-gray-400" />
                                </div>
                            )}
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {file.file.name}
                                </p>
                                {file.error ? (
                                    <p className="text-xs text-red-500">{file.error}</p>
                                ) : (
                                    <div className="mt-1 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                        <div
                                            className="bg-primary h-1.5 rounded-full transition-all duration-300"
                                            style={{ width: `${file.progress}%` }}
                                        />
                                    </div>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={() => removeFile(file.id)}
                                className="p-1 text-gray-400 hover:text-red-500"
                            >
                                <XMarkIcon className="h-5 w-5" />
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
