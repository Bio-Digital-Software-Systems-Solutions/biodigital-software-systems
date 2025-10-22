import React, { Suspense } from 'react';
import { logger } from '@/utils/logger';

// Lazy load heavy components for better performance
export const LazyVideoPlayer = React.lazy(() => import('./VideoJSPlayer'));
export const LazyRichTextEditor = React.lazy(() => import('./RichTextEditor'));

// Loading fallback component
export const ComponentLoader = ({ message = 'Chargement...' }: { message?: string }) => (
    <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <span className="ml-3 text-gray-600 dark:text-gray-400">{message}</span>
    </div>
);

// Wrapper component with error boundary
class ErrorBoundary extends React.Component<
    { children: React.ReactNode },
    { hasError: boolean }
> {
    constructor(props: { children: React.ReactNode }) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        logger.error('Component loading error', { error, errorInfo });
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p className="text-red-600 dark:text-red-400">
                        Une erreur est survenue lors du chargement du composant.
                    </p>
                </div>
            );
        }

        return this.props.children;
    }
}

// HOC for lazy loaded components with error boundary and loading state
export const withLazyLoad = <P extends object>(
    LazyComponent: React.LazyExoticComponent<React.ComponentType<P>>,
    loadingMessage?: string
) => {
    return (props: P) => (
        <ErrorBoundary>
            <Suspense fallback={<ComponentLoader message={loadingMessage} />}>
                <LazyComponent {...props as any} />
            </Suspense>
        </ErrorBoundary>
    );
};
