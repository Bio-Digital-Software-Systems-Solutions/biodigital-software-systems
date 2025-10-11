import React, { createContext, useContext, useState, useCallback } from 'react';
import { ExclamationTriangleIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface ConfirmOptions {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    type?: 'danger' | 'warning' | 'info';
}

interface ConfirmDialogState extends ConfirmOptions {
    isOpen: boolean;
    resolve?: (value: boolean) => void;
}

interface ConfirmDialogContextType {
    confirm: (options: ConfirmOptions) => Promise<boolean>;
}

const ConfirmDialogContext = createContext<ConfirmDialogContextType | undefined>(undefined);

export const useConfirm = () => {
    const context = useContext(ConfirmDialogContext);
    if (!context) {
        throw new Error('useConfirm must be used within a ConfirmDialogProvider');
    }
    return context;
};

export const ConfirmDialogProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [state, setState] = useState<ConfirmDialogState>({
        isOpen: false,
        message: '',
    });

    const confirm = useCallback((options: ConfirmOptions): Promise<boolean> => {
        return new Promise((resolve) => {
            setState({
                isOpen: true,
                title: options.title || 'Confirmation',
                message: options.message,
                confirmText: options.confirmText || 'Confirmer',
                cancelText: options.cancelText || 'Annuler',
                type: options.type || 'warning',
                resolve,
            });
        });
    }, []);

    const handleConfirm = () => {
        state.resolve?.(true);
        setState({ ...state, isOpen: false });
    };

    const handleCancel = () => {
        state.resolve?.(false);
        setState({ ...state, isOpen: false });
    };

    const getButtonStyles = () => {
        switch (state.type) {
            case 'danger':
                return 'bg-red-600 hover:bg-red-700 focus:ring-red-500';
            case 'warning':
                return 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500';
            case 'info':
            default:
                return 'bg-primary hover:bg-primary focus:ring-primary';
        }
    };

    if (!state.isOpen) return <ConfirmDialogContext.Provider value={{ confirm }}>{children}</ConfirmDialogContext.Provider>;

    return (
        <ConfirmDialogContext.Provider value={{ confirm }}>
            {children}
            {/* Backdrop */}
            <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 animate-fade-in">
                {/* Dialog */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full animate-scale-in">
                    {/* Header */}
                    <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-3">
                            <div className={`flex-shrink-0 ${
                                state.type === 'danger' ? 'text-red-600' :
                                state.type === 'warning' ? 'text-yellow-600' :
                                'text-primary'
                            }`}>
                                <ExclamationTriangleIcon className="h-6 w-6" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                {state.title}
                            </h3>
                        </div>
                        <button
                            onClick={handleCancel}
                            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <XMarkIcon className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Body */}
                    <div className="p-6">
                        <p className="text-gray-700 dark:text-gray-300">
                            {state.message}
                        </p>
                    </div>

                    {/* Footer */}
                    <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                        <button
                            onClick={handleCancel}
                            className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                        >
                            {state.cancelText}
                        </button>
                        <button
                            onClick={handleConfirm}
                            className={`px-4 py-2 text-sm font-medium text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 ${getButtonStyles()}`}
                        >
                            {state.confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </ConfirmDialogContext.Provider>
    );
};
