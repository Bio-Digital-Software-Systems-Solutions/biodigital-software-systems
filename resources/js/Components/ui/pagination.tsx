import React from 'react';
import { ChevronLeftIcon, ChevronRightIcon, ChevronDoubleLeftIcon, ChevronDoubleRightIcon } from '@heroicons/react/24/outline';
import { cn } from '@/lib/utils';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationData<T = unknown> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
}

interface PaginationProps {
    /** Current page number */
    currentPage: number;
    /** Total number of pages */
    lastPage: number;
    /** Total number of items */
    total: number;
    /** Items per page */
    perPage: number;
    /** Index of first item on current page */
    from: number | null;
    /** Index of last item on current page */
    to: number | null;
    /** Callback when page changes */
    onPageChange: (page: number) => void;
    /** Additional class names */
    className?: string;
    /** Show page size selector */
    showPageSizeSelector?: boolean;
    /** Available page sizes */
    pageSizes?: number[];
    /** Current page size */
    pageSize?: number;
    /** Callback when page size changes */
    onPageSizeChange?: (size: number) => void;
    /** Number of page buttons to show around current page */
    siblingCount?: number;
    /** Loading state */
    isLoading?: boolean;
}

export function Pagination({
    currentPage,
    lastPage,
    total,
    perPage,
    from,
    to,
    onPageChange,
    className,
    showPageSizeSelector = false,
    pageSizes = [10, 20, 50, 100],
    pageSize = perPage,
    onPageSizeChange,
    siblingCount = 1,
    isLoading = false,
}: PaginationProps) {
    // Generate page numbers to display
    const generatePageNumbers = (): (number | 'ellipsis')[] => {
        const pages: (number | 'ellipsis')[] = [];

        // Always show first page
        pages.push(1);

        // Calculate range around current page
        const leftSibling = Math.max(2, currentPage - siblingCount);
        const rightSibling = Math.min(lastPage - 1, currentPage + siblingCount);

        // Add ellipsis after first page if needed
        if (leftSibling > 2) {
            pages.push('ellipsis');
        }

        // Add pages around current page
        for (let i = leftSibling; i <= rightSibling; i++) {
            if (i !== 1 && i !== lastPage) {
                pages.push(i);
            }
        }

        // Add ellipsis before last page if needed
        if (rightSibling < lastPage - 1) {
            pages.push('ellipsis');
        }

        // Always show last page if more than 1 page
        if (lastPage > 1) {
            pages.push(lastPage);
        }

        return pages;
    };

    const pages = generatePageNumbers();

    if (lastPage <= 1) {
        return null;
    }

    return (
        <div className={cn('flex flex-col sm:flex-row items-center justify-between gap-4', className)}>
            {/* Info text */}
            <div className="text-sm text-gray-600 dark:text-gray-400">
                {from !== null && to !== null ? (
                    <>
                        Affichage de <span className="font-medium">{from}</span> à{' '}
                        <span className="font-medium">{to}</span> sur{' '}
                        <span className="font-medium">{total}</span> résultats
                    </>
                ) : (
                    <span className="font-medium">{total}</span>
                )}
            </div>

            <div className="flex items-center gap-4">
                {/* Page size selector */}
                {showPageSizeSelector && onPageSizeChange && (
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-gray-600 dark:text-gray-400">Par page:</span>
                        <select
                            value={pageSize}
                            onChange={(e) => onPageSizeChange(Number(e.target.value))}
                            className="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            disabled={isLoading}
                        >
                            {pageSizes.map((size) => (
                                <option key={size} value={size}>
                                    {size}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                {/* Pagination controls */}
                <nav className="flex items-center gap-1" aria-label="Pagination">
                    {/* First page button */}
                    <button
                        onClick={() => onPageChange(1)}
                        disabled={currentPage === 1 || isLoading}
                        className={cn(
                            'p-2 rounded-md transition-colors',
                            currentPage === 1 || isLoading
                                ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        )}
                        aria-label="Première page"
                    >
                        <ChevronDoubleLeftIcon className="h-4 w-4" />
                    </button>

                    {/* Previous page button */}
                    <button
                        onClick={() => onPageChange(currentPage - 1)}
                        disabled={currentPage === 1 || isLoading}
                        className={cn(
                            'p-2 rounded-md transition-colors',
                            currentPage === 1 || isLoading
                                ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        )}
                        aria-label="Page précédente"
                    >
                        <ChevronLeftIcon className="h-4 w-4" />
                    </button>

                    {/* Page numbers */}
                    <div className="hidden sm:flex items-center gap-1">
                        {pages.map((page, index) => {
                            if (page === 'ellipsis') {
                                return (
                                    <span
                                        key={`ellipsis-${index}`}
                                        className="px-2 py-1 text-gray-400 dark:text-gray-500"
                                    >
                                        ...
                                    </span>
                                );
                            }

                            return (
                                <button
                                    key={page}
                                    onClick={() => onPageChange(page)}
                                    disabled={isLoading}
                                    className={cn(
                                        'min-w-[2rem] px-3 py-1 rounded-md text-sm font-medium transition-colors',
                                        page === currentPage
                                            ? 'bg-purple-600 text-white'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                                        isLoading && 'cursor-not-allowed opacity-50'
                                    )}
                                    aria-current={page === currentPage ? 'page' : undefined}
                                >
                                    {page}
                                </button>
                            );
                        })}
                    </div>

                    {/* Mobile: Current page indicator */}
                    <span className="sm:hidden px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                        Page {currentPage} / {lastPage}
                    </span>

                    {/* Next page button */}
                    <button
                        onClick={() => onPageChange(currentPage + 1)}
                        disabled={currentPage === lastPage || isLoading}
                        className={cn(
                            'p-2 rounded-md transition-colors',
                            currentPage === lastPage || isLoading
                                ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        )}
                        aria-label="Page suivante"
                    >
                        <ChevronRightIcon className="h-4 w-4" />
                    </button>

                    {/* Last page button */}
                    <button
                        onClick={() => onPageChange(lastPage)}
                        disabled={currentPage === lastPage || isLoading}
                        className={cn(
                            'p-2 rounded-md transition-colors',
                            currentPage === lastPage || isLoading
                                ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        )}
                        aria-label="Dernière page"
                    >
                        <ChevronDoubleRightIcon className="h-4 w-4" />
                    </button>
                </nav>
            </div>
        </div>
    );
}

/**
 * Helper function to extract pagination props from Laravel pagination response
 */
export function extractPaginationProps<T>(paginatedData: PaginationData<T>) {
    return {
        currentPage: paginatedData.current_page,
        lastPage: paginatedData.last_page,
        total: paginatedData.total,
        perPage: paginatedData.per_page,
        from: paginatedData.from,
        to: paginatedData.to,
    };
}
