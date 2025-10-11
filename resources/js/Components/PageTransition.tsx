import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PageTransitionProps {
    children: ReactNode;
    className?: string;
}

export default function PageTransition({ children, className }: PageTransitionProps) {
    // Animations désactivées pour une navigation instantanée
    return (
        <div className={cn(className)}>
            {children}
        </div>
    );
}
