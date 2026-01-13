import React from 'react';
import { Link } from '@inertiajs/react';
import {
    CalendarIcon,
    CurrencyEuroIcon,
    UserCircleIcon,
    EyeIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentNeed } from '@/Types/need';
import { statusConfig, priorityConfig, categoryConfig, formatDate, formatCurrency } from './needConfig';

interface NeedListViewProps {
    needs: DepartmentNeed[];
    onNeedClick: (need: DepartmentNeed) => void;
}

export default function NeedListView({ needs, onNeedClick }: NeedListViewProps) {
    if (needs.length === 0) {
        return (
            <div className="h-full flex items-center justify-center">
                <p className="text-gray-500 dark:text-gray-400">
                    Aucun besoin trouvé
                </p>
            </div>
        );
    }

    return (
        <div className="h-full overflow-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-800 sticky top-0">
                    <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Référence
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Titre
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Catégorie
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Priorité
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Statut
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Montant
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Date souhaitée
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Assigné à
                        </th>
                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    {needs.map((need) => {
                        const status = statusConfig[need.status];
                        const priority = priorityConfig[need.priority];
                        const category = categoryConfig[need.category];

                        return (
                            <tr
                                key={need.uuid}
                                onClick={() => onNeedClick(need)}
                                className="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                            >
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {need.reference}
                                </td>
                                <td className="px-6 py-4">
                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                        {need.title}
                                    </div>
                                    {need.description && (
                                        <div className="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            {need.description}
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {category && (
                                        <span className="text-sm">
                                            {category.icon} {category.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {priority && (
                                        <span className={`text-sm font-medium ${priority.color}`}>
                                            {priority.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {status && (
                                        <span className={`px-2 py-1 rounded text-xs font-medium ${status.color}`}>
                                            {status.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {need.estimated_cost !== undefined && need.estimated_cost !== null && (
                                        <div className="flex items-center gap-1">
                                            <CurrencyEuroIcon className="h-4 w-4 text-gray-400" />
                                            {formatCurrency(Number(need.estimated_cost))}
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {need.needed_by_date && (
                                        <div className="flex items-center gap-1">
                                            <CalendarIcon className="h-4 w-4" />
                                            {formatDate(need.needed_by_date)}
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {need.assigned_to ? (
                                        <div className="flex items-center gap-2">
                                            {need.assigned_to.avatar ? (
                                                <img
                                                    src={need.assigned_to.avatar}
                                                    alt={need.assigned_to.full_name}
                                                    className="h-6 w-6 rounded-full"
                                                />
                                            ) : (
                                                <UserCircleIcon className="h-6 w-6 text-gray-400" />
                                            )}
                                            <span className="text-sm text-gray-900 dark:text-white">
                                                {need.assigned_to.full_name}
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-gray-400">
                                            Non assigné
                                        </span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right">
                                    <Link
                                        href={route('needs.show', need.uuid)}
                                        onClick={(e) => e.stopPropagation()}
                                        className="inline-flex items-center gap-1 px-2 py-1 text-sm text-primary hover:text-primary/80 hover:bg-primary/10 rounded transition-colors"
                                    >
                                        <EyeIcon className="h-4 w-4" />
                                        Détails
                                    </Link>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
