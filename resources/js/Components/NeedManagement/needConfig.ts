import type { NeedStatus, NeedPriority, NeedCategory } from '@/Types/need';

export const statusConfig: Record<NeedStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    under_review: { label: 'En révision', color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' },
    approved: { label: 'Approuvé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    in_progress: { label: 'En cours', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
    ordered: { label: 'Commandé', color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400' },
    delivered: { label: 'Livré', color: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' },
    completed: { label: 'Terminé', color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    cancelled: { label: 'Annulé', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
};

export const priorityConfig: Record<NeedPriority, { label: string; color: string; bgColor: string }> = {
    critical: { label: 'Critique', color: 'text-red-600 dark:text-red-400', bgColor: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    high: { label: 'Haute', color: 'text-orange-600 dark:text-orange-400', bgColor: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
    medium: { label: 'Moyenne', color: 'text-yellow-600 dark:text-yellow-400', bgColor: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    low: { label: 'Basse', color: 'text-green-600 dark:text-green-400', bgColor: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
};

export const categoryConfig: Record<NeedCategory, { label: string; icon: string }> = {
    equipment: { label: 'Équipement', icon: '🖥️' },
    software: { label: 'Logiciel', icon: '💻' },
    furniture: { label: 'Mobilier', icon: '🪑' },
    supplies: { label: 'Fournitures', icon: '📦' },
    services: { label: 'Services', icon: '🛠️' },
    training: { label: 'Formation', icon: '📚' },
    recruitment: { label: 'Recrutement', icon: '👥' },
    other: { label: 'Autre', icon: '📋' },
};

export const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

export const formatDateShort = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
};

export const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(amount);
};
