import { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { ShieldAlert, Home, ArrowLeft, Lock, AlertTriangle } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

interface UnauthorizedModalProps {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export default function UnauthorizedModal({ open: controlledOpen, onOpenChange }: UnauthorizedModalProps) {
    const { flash } = usePage().props as { flash: { unauthorized?: number } };
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (controlledOpen !== undefined) {
            setOpen(controlledOpen);
        } else if (flash?.unauthorized) {
            setOpen(true);
        }
    }, [flash?.unauthorized, controlledOpen]);

    const handleOpenChange = (newOpen: boolean) => {
        setOpen(newOpen);
        onOpenChange?.(newOpen);
    };

    const handleGoHome = () => {
        setOpen(false);
        router.visit('/');
    };

    const handleGoBack = () => {
        setOpen(false);
        window.history.back();
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-2xl p-0 gap-0 overflow-hidden">
                <DialogHeader className="text-center space-y-6 px-6 pt-8 pb-4">
                    <div className="flex justify-center">
                        <div className="rounded-full bg-red-100 dark:bg-red-900/30 p-4">
                            <ShieldAlert className="h-12 w-12 text-red-600 dark:text-red-400" />
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Badge variant="destructive" className="text-base px-4 py-1">
                            403
                        </Badge>
                        <DialogTitle className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Accès Non Autorisé
                        </DialogTitle>
                        <DialogDescription className="text-base text-gray-600 dark:text-gray-400 px-4">
                            Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.
                        </DialogDescription>
                    </div>
                </DialogHeader>

                <div className="space-y-5 px-6 py-4">
                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mx-2">
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            Cette action nécessite des permissions spécifiques que vous ne possédez pas actuellement.
                            Si vous pensez que c'est une erreur, veuillez contacter votre administrateur.
                        </p>
                    </div>

                    <div className="space-y-4 mx-2">
                        <h4 className="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-amber-500" />
                            Ce que vous pouvez faire :
                        </h4>
                        <ul className="space-y-3 ml-7 mr-2">
                            <li className="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                <span className="text-primary dark:text-blue-400 font-bold">•</span>
                                <span>Vérifiez que vous êtes connecté avec le bon compte</span>
                            </li>
                            <li className="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                <span className="text-primary dark:text-blue-400 font-bold">•</span>
                                <span>Contactez un administrateur pour obtenir les permissions nécessaires</span>
                            </li>
                            <li className="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                <span className="text-primary dark:text-blue-400 font-bold">•</span>
                                <span>Retournez à la page d'accueil pour accéder aux ressources autorisées</span>
                            </li>
                        </ul>
                    </div>

                    <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mx-2">
                        <p className="text-sm text-amber-800 dark:text-amber-200 flex items-start gap-2">
                            <Lock className="h-4 w-4 mt-0.5 flex-shrink-0" />
                            <span>
                                <strong>Besoin d'aide ?</strong> Contactez votre administrateur système ou envoyez un email à{' '}
                                <a
                                    href="mailto:support@icc-muenchen.de"
                                    className="underline hover:text-amber-900 dark:hover:text-amber-100"
                                >
                                    support@icc-muenchen.de
                                </a>
                            </span>
                        </p>
                    </div>
                </div>

                <DialogFooter className="flex flex-col sm:flex-row gap-3 px-6 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        onClick={handleGoBack}
                        variant="outline"
                        className="flex-1 gap-2"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Page précédente
                    </Button>
                    <Button
                        onClick={handleGoHome}
                        className="flex-1 gap-2"
                    >
                        <Home className="h-4 w-4" />
                        Retour à l'accueil
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
