import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './dialog';
import { Button } from './button';
import { AlertTriangle, HelpCircle } from 'lucide-react';

export interface DeleteConfirmationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    isDeleting?: boolean;
    variant?: 'destructive' | 'default';
    children?: React.ReactNode;
}

export function DeleteConfirmationDialog({
    open,
    onOpenChange,
    onConfirm,
    title,
    description,
    confirmText = 'Supprimer',
    cancelText = 'Annuler',
    isDeleting = false,
    variant = 'destructive',
    children,
}: DeleteConfirmationDialogProps) {
    const handleConfirm = () => {
        onConfirm();
    };

    const isDestructive = variant === 'destructive';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className={`flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center ${
                            isDestructive
                                ? 'bg-red-100 dark:bg-red-900/20'
                                : 'bg-blue-100 dark:bg-blue-900/20'
                        }`}>
                            {isDestructive ? (
                                <AlertTriangle className="w-6 h-6 text-red-600 dark:text-red-400" />
                            ) : (
                                <HelpCircle className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            )}
                        </div>
                        <DialogTitle>{title}</DialogTitle>
                    </div>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {children}

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={isDeleting}
                    >
                        {cancelText}
                    </Button>
                    <Button
                        variant={isDestructive ? 'destructive' : 'default'}
                        onClick={handleConfirm}
                        disabled={isDeleting}
                    >
                        {confirmText}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
