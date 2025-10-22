import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function LoadingBar() {
    const [isLoading, setIsLoading] = useState(false);
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        let progressInterval: NodeJS.Timeout;

        const handleStart = () => {
            setIsLoading(true);
            setProgress(0);

            // Simulate progress
            progressInterval = setInterval(() => {
                setProgress((prev) => {
                    if (prev >= 90) return prev;
                    return prev + Math.random() * 10;
                });
            }, 200);
        };

        const handleProgress = (event: any) => {
            if (event.detail.progress?.percentage) {
                setProgress(event.detail.progress.percentage);
            }
        };

        const handleFinish = () => {
            clearInterval(progressInterval);
            setProgress(100);

            setTimeout(() => {
                setIsLoading(false);
                setProgress(0);
            }, 300);
        };

        const removeStart = router.on('start', handleStart);
        const removeProgress = router.on('progress', handleProgress);
        const removeFinish = router.on('finish', handleFinish);

        return () => {
            clearInterval(progressInterval);
            removeStart();
            removeProgress();
            removeFinish();
        };
    }, []);

    if (!isLoading && progress === 0) return null;

    return (
        <>
            {/* Progress Bar */}
            <div className="fixed top-0 left-0 right-0 z-[9999] h-1 bg-transparent">
                <div
                    className={cn(
                        'h-full bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red transition-all duration-300 ease-out',
                        progress === 100 && 'opacity-0'
                    )}
                    style={{ width: `${progress}%` }}
                />
            </div>

            {/* Spinner Overlay */}
            {isLoading && (
                <div className="fixed inset-0 z-[9998] pointer-events-none flex items-start justify-center pt-20">
                    <div className="bg-card/90 backdrop-blur-sm rounded-full p-3 shadow-lg animate-in fade-in zoom-in duration-200">
                        <div className="w-8 h-8 border-4 border-icc-blue border-t-transparent rounded-full animate-spin" />
                    </div>
                </div>
            )}
        </>
    );
}
