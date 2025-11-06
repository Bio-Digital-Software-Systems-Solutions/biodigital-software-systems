import React, { useState, useRef } from 'react';
import { TrashIcon, DocumentIcon, PhotoIcon, FilmIcon, MusicalNoteIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { PaperClipIcon } from '@heroicons/react/24/solid';

export interface FileUploadFile {
    file: File;
    id: string;
    preview?: string;
}

interface FileUploadProps {
    files: FileUploadFile[];
    onFilesChange: (files: FileUploadFile[]) => void;
    maxFiles?: number;
    maxSizeBytes?: number;
    acceptedFileTypes?: string[];
    className?: string;
}

const FileUpload: React.FC<FileUploadProps> = ({
    files,
    onFilesChange,
    maxFiles = 5,
    maxSizeBytes = 10 * 1024 * 1024, // 10MB
    acceptedFileTypes = [
        'image/*',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/*'
    ],
    className = '',
}) => {
    const [dragOver, setDragOver] = useState(false);
    const [errors, setErrors] = useState<string[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const getFileIcon = (file: File) => {
        const type = file.type;

        if (type.startsWith('image/')) {
            return <PhotoIcon className="h-8 w-8 text-blue-500" />;
        } else if (type.startsWith('video/')) {
            return <FilmIcon className="h-8 w-8 text-purple-500" />;
        } else if (type.startsWith('audio/')) {
            return <MusicalNoteIcon className="h-8 w-8 text-green-500" />;
        } else if (
            type === 'application/zip' ||
            type === 'application/x-rar-compressed' ||
            type === 'application/x-7z-compressed'
        ) {
            return <ArchiveBoxIcon className="h-8 w-8 text-yellow-500" />;
        } else {
            return <DocumentIcon className="h-8 w-8 text-gray-500" />;
        }
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const validateFiles = (fileList: File[]): { valid: File[]; errors: string[] } => {
        const newErrors: string[] = [];
        const validFiles: File[] = [];

        fileList.forEach(file => {
            // Check file size
            if (file.size > maxSizeBytes) {
                newErrors.push(`${file.name} est trop volumineux (max: ${formatFileSize(maxSizeBytes)})`);
                return;
            }

            // Check file type if specified
            if (acceptedFileTypes.length > 0) {
                const isAccepted = acceptedFileTypes.some(type => {
                    if (type.endsWith('/*')) {
                        const baseType = type.replace('/*', '');
                        return file.type.startsWith(baseType + '/');
                    }
                    return file.type === type;
                });

                if (!isAccepted) {
                    newErrors.push(`${file.name} n'est pas un type de fichier autorisé`);
                    return;
                }
            }

            validFiles.push(file);
        });

        return { valid: validFiles, errors: newErrors };
    };

    const handleFileSelect = (selectedFiles: File[]) => {
        const { valid, errors: validationErrors } = validateFiles(selectedFiles);

        if (files.length + valid.length > maxFiles) {
            validationErrors.push(`Vous ne pouvez télécharger que ${maxFiles} fichiers au maximum`);
        }

        setErrors(validationErrors);

        if (valid.length > 0 && files.length + valid.length <= maxFiles) {
            const newFiles: FileUploadFile[] = valid.map(file => ({
                file,
                id: Math.random().toString(36).substring(7),
                preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined,
            }));

            onFilesChange([...files, ...newFiles]);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragOver(false);

        const droppedFiles = Array.from(e.dataTransfer.files);
        handleFileSelect(droppedFiles);
    };

    const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const selectedFiles = Array.from(e.target.files);
            handleFileSelect(selectedFiles);
        }
        // Reset input value to allow selecting the same file again
        e.target.value = '';
    };

    const removeFile = (fileId: string) => {
        const fileToRemove = files.find(f => f.id === fileId);
        if (fileToRemove?.preview) {
            URL.revokeObjectURL(fileToRemove.preview);
        }
        onFilesChange(files.filter(f => f.id !== fileId));
    };

    const openFileDialog = () => {
        fileInputRef.current?.click();
    };

    return (
        <div className={`space-y-4 ${className}`}>
            {/* Upload Area */}
            <div
                onDrop={handleDrop}
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragOver(true);
                }}
                onDragLeave={() => setDragOver(false)}
                onClick={openFileDialog}
                className={`
                    relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors
                    ${dragOver
                        ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                    }
                `}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept={acceptedFileTypes.join(',')}
                    onChange={handleFileInputChange}
                    className="hidden"
                />

                <PaperClipIcon className="mx-auto h-12 w-12 text-gray-400" />
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <span className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        Cliquez pour télécharger
                    </span>{' '}
                    ou glissez-déposez vos fichiers ici
                </p>
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Maximum {maxFiles} fichiers, {formatFileSize(maxSizeBytes)} par fichier
                </p>
            </div>

            {/* Error Messages */}
            {errors.length > 0 && (
                <div className="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                    <div className="text-sm text-red-700 dark:text-red-400">
                        <ul className="space-y-1">
                            {errors.map((error, index) => (
                                <li key={index}>• {error}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            {/* File List */}
            {files.length > 0 && (
                <div className="space-y-2">
                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                        Fichiers sélectionnés ({files.length}/{maxFiles})
                    </h4>
                    <div className="space-y-2">
                        {files.map((uploadFile) => (
                            <div
                                key={uploadFile.id}
                                className="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                            >
                                {/* File Icon/Preview */}
                                <div className="flex-shrink-0 mr-3">
                                    {uploadFile.preview ? (
                                        <img
                                            src={uploadFile.preview}
                                            alt={uploadFile.file.name}
                                            className="h-10 w-10 object-cover rounded"
                                        />
                                    ) : (
                                        getFileIcon(uploadFile.file)
                                    )}
                                </div>

                                {/* File Info */}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {uploadFile.file.name}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {formatFileSize(uploadFile.file.size)}
                                    </p>
                                </div>

                                {/* Remove Button */}
                                <button
                                    onClick={() => removeFile(uploadFile.id)}
                                    className="ml-3 p-1 text-gray-400 hover:text-red-500 transition-colors"
                                    title="Supprimer le fichier"
                                >
                                    <TrashIcon className="h-5 w-5" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

export default FileUpload;