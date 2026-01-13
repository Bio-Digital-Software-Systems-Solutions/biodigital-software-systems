import React from 'react';
import {
    CalendarIcon,
    CurrencyEuroIcon,
    UserCircleIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentNeed } from '@/Types/need';
import { statusConfig, priorityConfig, categoryConfig, formatDate, formatCurrency } from './needConfig';

interface NeedTableViewProps {
    needs: DepartmentNeed[];
    onNeedClick: (need: DepartmentNeed) => void;
}

type SortField = 'reference' | 'title' | 'category' | 'priority' | 'status' | 'estimated_cost' | 'needed_by_date' | 'created_at';
type SortDirection = 'asc' | 'desc';

export default function NeedTableView({ needs, onNeedClick }: NeedTableViewProps) {
    const [sortField, setSortField] = React.useState<SortField>('created_at');
    const [sortDirection, setSortDirection] = React.useState<SortDirection>('desc');

    const handleSort = (field: SortField) => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const sortedNeeds = React.useMemo(() => {
        return [...needs].sort((a, b) => {
            let comparison = 0;

            switch (sortField) {
                case 'reference':
                case 'title':
                case 'category':
                case 'priority':
                case 'status':
                    comparison = (a[sortField] || '').localeCompare(b[sortField] || '');
                    break;
                case 'estimated_cost':
                    comparison = (Number(a.estimated_cost) || 0) - (Number(b.estimated_cost) || 0);
                    break;
                case 'needed_by_date':
                case 'created_at':
                    const dateA = a[sortField] ? new Date(a[sortField]).getTime() : 0;
                    const dateB = b[sortField] ? new Date(b[sortField]).getTime() : 0;
                    comparison = dateA - dateB;
                    break;
            }

            return sortDirection === 'asc' ? comparison : -comparison;
        });
    }, [needs, sortField, sortDirection]);

    const SortIcon = ({ field }: { field: SortField }) => {
        if (sortField !== field) {
            return <div className="w-4 h-4" />;
        }
        return sortDirection === 'asc'
            ? <ChevronUpIcon className="w-4 h-4" />
            : <ChevronDownIcon className="w-4 h-4" />;
    };

    const TableHeader = ({ field, children, className = '' }: { field: SortField; children: React.ReactNode; className?: string }) => (
        <th
            scope="col"
            onClick={() => handleSort(field)}
            className={`px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 select-none ${className}`}
        >
            <div className="flex items-center gap-1">
                {children}
                <SortIcon field={field} />
            </div>
        </th>
    );

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
                <thead className="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                    <tr>
                        <TableHeader field="reference" className="w-32">Référence</TableHeader>
                        <TableHeader field="title">Titre</TableHeader>
                        <TableHeader field="category" className="w-32">Catégorie</TableHeader>
                        <TableHeader field="priority" className="w-28">Priorité</TableHeader>
                        <TableHeader field="status" className="w-32">Statut</TableHeader>
                        <TableHeader field="estimated_cost" className="w-32">Montant</TableHeader>
                        <TableHeader field="needed_by_date" className="w-32">Date souhaitée</TableHeader>
                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-40">
                            Assigné à
                        </th>
                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                            Activité
                        </th>
                    </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    {sortedNeeds.map((need) => {
                        const status = statusConfig[need.status];
                        const priority = priorityConfig[need.priority];
                        const category = categoryConfig[need.category];

                        return (
                            <tr
                                key={need.uuid}
                                onClick={() => onNeedClick(need)}
                                className="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                            >
                                <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {need.reference}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                        {need.title}
                                    </div>
                                    {need.description && (
                                        <div className="text-xs text-gray-500 dark:text-gray-400 truncate max-w-md">
                                            {need.description}
                                        </div>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {category && (
                                        <span className="text-sm">
                                            {category.icon} {category.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {priority && (
                                        <span className={`px-2 py-1 rounded text-xs font-medium ${priority.bgColor}`}>
                                            {priority.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {status && (
                                        <span className={`px-2 py-1 rounded text-xs font-medium ${status.color}`}>
                                            {status.label}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {need.estimated_cost !== undefined && need.estimated_cost !== null && (
                                        <div className="flex items-center gap-1">
                                            <CurrencyEuroIcon className="h-4 w-4 text-gray-400" />
                                            {formatCurrency(Number(need.estimated_cost))}
                                        </div>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {need.needed_by_date && (
                                        <div className="flex items-center gap-1">
                                            <CalendarIcon className="h-4 w-4" />
                                            {formatDate(need.needed_by_date)}
                                        </div>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
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
                                            <span className="text-sm text-gray-900 dark:text-white truncate max-w-[100px]">
                                                {need.assigned_to.full_name}
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-gray-400">
                                            Non assigné
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    <div className="flex items-center gap-3 text-gray-400">
                                        {need.comments_count !== undefined && need.comments_count > 0 && (
                                            <div className="flex items-center gap-1 text-xs">
                                                <ChatBubbleLeftIcon className="h-4 w-4" />
                                                <span>{need.comments_count}</span>
                                            </div>
                                        )}
                                        {need.attachments_count !== undefined && need.attachments_count > 0 && (
                                            <div className="flex items-center gap-1 text-xs">
                                                <PaperClipIcon className="h-4 w-4" />
                                                <span>{need.attachments_count}</span>
                                            </div>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
