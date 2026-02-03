import React from 'react';
import {
    FunnelIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    Squares2X2Icon,
    ListBulletIcon,
    TableCellsIcon,
    ViewColumnsIcon,
} from '@heroicons/react/24/outline';
import { useNeedStore, ViewMode } from '@/stores/needStore';
import type { NeedStatus, NeedCategory, NeedPriority } from '@/Types/need';

const statusOptions: { value: NeedStatus; label: string }[] = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'submitted', label: 'Soumis' },
    { value: 'under_review', label: 'En révision' },
    { value: 'approved', label: 'Approuvé' },
    { value: 'rejected', label: 'Rejeté' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'ordered', label: 'Commandé' },
    { value: 'delivered', label: 'Livré' },
    { value: 'completed', label: 'Terminé' },
    { value: 'cancelled', label: 'Annulé' },
];

const categoryOptions: { value: NeedCategory; label: string }[] = [
    { value: 'equipment', label: 'Équipement' },
    { value: 'software', label: 'Logiciel' },
    { value: 'furniture', label: 'Mobilier' },
    { value: 'supplies', label: 'Fournitures' },
    { value: 'services', label: 'Services' },
    { value: 'training', label: 'Formation' },
    { value: 'recruitment', label: 'Recrutement' },
    { value: 'other', label: 'Autre' },
];

const priorityOptions: { value: NeedPriority; label: string }[] = [
    { value: 'critical', label: 'Critique' },
    { value: 'high', label: 'Haute' },
    { value: 'medium', label: 'Moyenne' },
    { value: 'low', label: 'Basse' },
];

const viewModes: { value: ViewMode; label: string; icon: React.ElementType }[] = [
    { value: 'kanban', label: 'Kanban', icon: ViewColumnsIcon },
    { value: 'list', label: 'Liste', icon: ListBulletIcon },
    { value: 'table', label: 'Tableau', icon: TableCellsIcon },
    { value: 'grid', label: 'Grille', icon: Squares2X2Icon },
];

interface NeedFiltersProps {
    showViewToggle?: boolean;
}

export default function NeedFilters({ showViewToggle = true }: NeedFiltersProps) {
    const { filters, setFilters, clearFilters, viewMode, setViewMode } = useNeedStore();
    const [isExpanded, setIsExpanded] = React.useState(false);

    const hasActiveFilters =
        filters.search ||
        filters.category ||
        filters.priority ||
        (filters.status && filters.status.length > 0);

    const inputClasses = `
        w-full px-3 py-2 rounded-md border text-sm
        bg-white dark:bg-gray-800
        border-gray-300 dark:border-gray-600
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-primary focus:border-primary
    `;

    return (
        <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            {/* Main Filter Bar */}
            <div className="px-4 py-3 flex flex-wrap items-center gap-2 sm:gap-4">
                {/* Search */}
                <div className="w-full sm:flex-1 sm:max-w-md relative order-1">
                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Rechercher..."
                        value={filters.search || ''}
                        onChange={(e) => setFilters({ search: e.target.value })}
                        className={`${inputClasses} pl-10`}
                    />
                </div>

                {/* Quick Filters */}
                <select
                    value={filters.category || ''}
                    onChange={(e) =>
                        setFilters({ category: (e.target.value || undefined) as NeedCategory | undefined })
                    }
                    className={`${inputClasses} w-28 sm:w-40 order-3 sm:order-2`}
                    aria-label="Filtrer par catégorie"
                >
                    <option value="">Catégorie</option>
                    {categoryOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>

                <select
                    value={filters.priority || ''}
                    onChange={(e) =>
                        setFilters({ priority: (e.target.value || undefined) as NeedPriority | undefined })
                    }
                    className={`${inputClasses} w-24 sm:w-32 order-4 sm:order-3`}
                >
                    <option value="">Priorité</option>
                    {priorityOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>

                {/* Expand/Collapse */}
                <button
                    type="button"
                    onClick={() => setIsExpanded(!isExpanded)}
                    className={`
                        flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-2 rounded-md
                        border border-gray-300 dark:border-gray-600
                        text-sm text-gray-700 dark:text-gray-300
                        hover:bg-gray-50 dark:hover:bg-gray-700
                        order-5 sm:order-4
                        ${isExpanded ? 'bg-gray-100 dark:bg-gray-700' : ''}
                    `}
                >
                    <FunnelIcon className="h-4 w-4" />
                    <span className="hidden sm:inline">Filtres</span>
                    {hasActiveFilters && (
                        <span className="h-2 w-2 rounded-full bg-primary" />
                    )}
                </button>

                {/* Clear Filters */}
                {hasActiveFilters && (
                    <button
                        type="button"
                        onClick={clearFilters}
                        className="
                            flex items-center gap-1 px-2 sm:px-3 py-2 rounded-md
                            text-sm text-red-600 dark:text-red-400
                            hover:bg-red-50 dark:hover:bg-red-900/20
                            order-6 sm:order-5
                        "
                    >
                        <XMarkIcon className="h-4 w-4" />
                        <span className="hidden sm:inline">Effacer</span>
                    </button>
                )}

                {/* View Toggle */}
                {showViewToggle && (
                    <div className="flex items-center border border-gray-300 dark:border-gray-600 rounded-md order-2 sm:order-6 ml-auto sm:ml-0">
                        {viewModes.map((mode, index) => {
                            const Icon = mode.icon;
                            return (
                                <button
                                    key={mode.value}
                                    type="button"
                                    onClick={() => setViewMode(mode.value)}
                                    title={mode.label}
                                    className={`
                                        p-1.5 sm:p-2
                                        ${index > 0 ? 'border-l border-gray-300 dark:border-gray-600' : ''}
                                        ${viewMode === mode.value
                                            ? 'bg-primary text-white'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        }
                                    `}
                                >
                                    <Icon className="h-4 w-4 sm:h-5 sm:w-5" />
                                </button>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Expanded Filters */}
            {isExpanded && (
                <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {/* Status Filter */}
                        <div>
                            <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Statuts
                            </label>
                            <div className="space-y-1 max-h-40 overflow-y-auto">
                                {statusOptions.map((opt) => (
                                    <label
                                        key={opt.value}
                                        className="flex items-center gap-2 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={(filters.status || []).includes(opt.value)}
                                            onChange={(e) => {
                                                const current = filters.status || [];
                                                const updated = e.target.checked
                                                    ? [...current, opt.value]
                                                    : current.filter((s) => s !== opt.value);
                                                setFilters({
                                                    status: updated.length ? updated : undefined,
                                                });
                                            }}
                                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">
                                            {opt.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Date Range */}
                        <div>
                            <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Date de création
                            </label>
                            <div className="space-y-2">
                                <input
                                    type="date"
                                    value={filters.dateFrom || ''}
                                    onChange={(e) => setFilters({ dateFrom: e.target.value || undefined })}
                                    className={inputClasses}
                                    placeholder="Du"
                                />
                                <input
                                    type="date"
                                    value={filters.dateTo || ''}
                                    onChange={(e) => setFilters({ dateTo: e.target.value || undefined })}
                                    className={inputClasses}
                                    placeholder="Au"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
