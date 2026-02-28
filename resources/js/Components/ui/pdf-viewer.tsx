import React, { useState, useRef, useEffect, useCallback } from 'react';
import '@/lib/pdf-worker';
import { Document, Page } from 'react-pdf';
import 'react-pdf/dist/Page/AnnotationLayer.css';
import 'react-pdf/dist/Page/TextLayer.css';
import { ChevronLeftIcon, ChevronRightIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface PdfViewerProps {
    fileUrl: string;
    height?: string;
    showNavigation?: boolean;
    className?: string;
    onError?: (error: Error) => void;
    onLoadSuccess?: (numPages: number) => void;
    downloadUrl?: string;
}

function LoadingSkeleton() {
    return (
        <div className="flex flex-col items-center justify-center p-8 space-y-4">
            <div className="w-full max-w-md space-y-3">
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4" />
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/2" />
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6" />
                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-2/3" />
            </div>
            <p className="text-sm text-gray-500 dark:text-gray-400">
                Chargement du PDF...
            </p>
        </div>
    );
}

export default function PdfViewer({
    fileUrl,
    height = '100%',
    showNavigation = true,
    className = '',
    onError,
    onLoadSuccess: onLoadSuccessProp,
    downloadUrl,
}: PdfViewerProps) {
    const [numPages, setNumPages] = useState<number>(0);
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [containerWidth, setContainerWidth] = useState<number>(0);

    const containerRef = useRef<HTMLDivElement>(null);
    const scrollContainerRef = useRef<HTMLDivElement>(null);
    const pageRefs = useRef<Map<number, HTMLDivElement>>(new Map());

    // Track container width for responsive rendering
    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        const observer = new ResizeObserver((entries) => {
            for (const entry of entries) {
                setContainerWidth(entry.contentRect.width);
            }
        });

        observer.observe(container);
        return () => observer.disconnect();
    }, []);

    // Track current visible page via IntersectionObserver
    useEffect(() => {
        if (numPages <= 1) return;

        const scrollContainer = scrollContainerRef.current;
        if (!scrollContainer) return;

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        const pageNum = Number(entry.target.getAttribute('data-page'));
                        if (pageNum) {
                            setCurrentPage(pageNum);
                        }
                    }
                }
            },
            {
                root: scrollContainer,
                threshold: 0.5,
            }
        );

        pageRefs.current.forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, [numPages]);

    const handleDocumentLoadSuccess = useCallback(({ numPages: total }: { numPages: number }) => {
        setNumPages(total);
        setLoadError(null);
        onLoadSuccessProp?.(total);
    }, [onLoadSuccessProp]);

    const handleDocumentLoadError = useCallback((error: Error) => {
        setLoadError('Impossible de charger le PDF.');
        onError?.(error);
    }, [onError]);

    const goToPage = useCallback((page: number) => {
        const el = pageRefs.current.get(page);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, []);

    const setPageRef = useCallback((pageNum: number) => (el: HTMLDivElement | null) => {
        if (el) {
            pageRefs.current.set(pageNum, el);
        } else {
            pageRefs.current.delete(pageNum);
        }
    }, []);

    if (loadError) {
        return (
            <div className={`flex flex-col items-center justify-center p-8 text-center ${className}`} style={{ height }}>
                <ExclamationTriangleIcon className="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                <p className="text-gray-500 dark:text-gray-400 mb-4">{loadError}</p>
                {downloadUrl && (
                    <a
                        href={downloadUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Télécharger le fichier
                    </a>
                )}
            </div>
        );
    }

    return (
        <div ref={containerRef} className={`relative flex flex-col ${className}`} style={{ height }}>
            {/* Scrollable PDF pages */}
            <div
                ref={scrollContainerRef}
                className="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900"
            >
                <Document
                    file={fileUrl}
                    onLoadSuccess={handleDocumentLoadSuccess}
                    onLoadError={handleDocumentLoadError}
                    loading={<LoadingSkeleton />}
                >
                    {containerWidth > 0 && Array.from({ length: numPages }, (_, index) => (
                        <div
                            key={`page_${index + 1}`}
                            ref={setPageRef(index + 1)}
                            data-page={index + 1}
                            className="flex justify-center py-2"
                        >
                            <Page
                                pageNumber={index + 1}
                                width={Math.min(containerWidth - 16, containerWidth)}
                                loading={
                                    <div className="flex items-center justify-center p-8">
                                        <div className="animate-pulse text-sm text-gray-400 dark:text-gray-500">
                                            Page {index + 1}...
                                        </div>
                                    </div>
                                }
                                className="shadow-sm [&_canvas]:mx-auto"
                            />
                        </div>
                    ))}
                </Document>
            </div>

            {/* Page navigation bar */}
            {showNavigation && numPages > 1 && (
                <div className="flex-shrink-0 flex items-center justify-center gap-4 py-2 px-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-t border-gray-200 dark:border-gray-700">
                    <button
                        onClick={() => goToPage(Math.max(1, currentPage - 1))}
                        disabled={currentPage <= 1}
                        className="p-1 rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        aria-label="Page précédente"
                    >
                        <ChevronLeftIcon className="h-5 w-5" />
                    </button>
                    <span className="text-sm text-gray-600 dark:text-gray-400 tabular-nums">
                        {currentPage} / {numPages}
                    </span>
                    <button
                        onClick={() => goToPage(Math.min(numPages, currentPage + 1))}
                        disabled={currentPage >= numPages}
                        className="p-1 rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        aria-label="Page suivante"
                    >
                        <ChevronRightIcon className="h-5 w-5" />
                    </button>
                </div>
            )}
        </div>
    );
}
