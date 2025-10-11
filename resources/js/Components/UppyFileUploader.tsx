import React, { useEffect, useRef } from 'react';
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';
import { logger } from '@/utils/logger';

interface UppyFileUploaderProps {
    uploadType: 'event_image' | 'event_avatar' | 'article_image' | 'article_document' | 'book_cover' | 'message_attachment' | 'training_image' | 'profile_avatar' | 'task_image' | 'project_image' | 'department_image' | 'group_image' | 'stock_image' | 'library_image' | 'general';
    onUploadComplete?: (files: any[]) => void;
    onUploadError?: (error: any) => void;
    maxFileSize?: number; // in MB
    maxNumberOfFiles?: number;
    allowedFileTypes?: string[];
    height?: number;
    note?: string;
}

export default function UppyFileUploader({
    uploadType,
    onUploadComplete,
    onUploadError,
    maxFileSize = 100, // 100MB default
    maxNumberOfFiles = 10,
    allowedFileTypes,
    height = 400,
    note = 'Images and documents up to ' + maxFileSize + ' MB',
}: UppyFileUploaderProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const uppyRef = useRef<Uppy | null>(null);

    useEffect(() => {
        if (!containerRef.current) return;

        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Initialize Uppy
        const uppy = new Uppy({
            restrictions: {
                maxFileSize: maxFileSize * 1024 * 1024, // Convert MB to bytes
                maxNumberOfFiles,
                allowedFileTypes,
            },
            autoProceed: false,
        });

        // Add Dashboard plugin
        uppy.use(Dashboard, {
            target: containerRef.current,
            inline: true,
            height: height,
            theme: 'auto',
            proudlyDisplayPoweredByUppy: false,
            note: note,
        });

        // Add TUS plugin for resumable uploads
        uppy.use(Tus, {
            endpoint: '/api/files',
            chunkSize: 5 * 1024 * 1024, // 5MB chunks
            retryDelays: [0, 1000, 3000, 5000],
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            onBeforeRequest: (req) => {
                // Add metadata to the request
                req.setHeader('X-CSRF-TOKEN', csrfToken);
            },
            // Allow TUS to send metadata
            storeFingerprintForResuming: false,
        });

        // Set metadata for all files
        uppy.on('file-added', (file) => {
            uppy.setFileMeta(file.id, {
                upload_type: uploadType,
            });
        });

        // Event listeners
        uppy.on('complete', (result) => {
            if (result.successful && result.successful.length > 0) {
                const uploadedFiles = result.successful.map((file) => ({
                    id: file.id,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    uploadURL: file.uploadURL,
                }));

                if (onUploadComplete) {
                    onUploadComplete(uploadedFiles);
                }
            }

            if (result.failed && result.failed.length > 0 && onUploadError) {
                onUploadError(result.failed);
            }
        });

        uppy.on('upload-error', (file, error) => {
            logger.error('Upload error', { file: file?.name, error });
            if (onUploadError) {
                onUploadError({ file, error });
            }
        });

        uppyRef.current = uppy;

        // Cleanup
        return () => {
            uppy.cancelAll();
            uppy.clear();
        };
    }, [uploadType, maxFileSize, maxNumberOfFiles, allowedFileTypes?.join(','), onUploadComplete, onUploadError]);

    return (
        <div ref={containerRef} className="uppy-wrapper"></div>
    );
}
