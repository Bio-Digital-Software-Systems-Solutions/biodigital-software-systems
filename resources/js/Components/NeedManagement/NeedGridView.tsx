import React from 'react';
import {
    CalendarIcon,
    CurrencyEuroIcon,
    UserCircleIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentNeed } from '@/Types/need';
import { statusConfig, priorityConfig, categoryConfig, formatDateShort, formatCurrency } from './needConfig';

interface NeedGridViewProps {
    needs: DepartmentNeed[];
    onNeedClick: (need: DepartmentNeed) => void;
}

export default function NeedGridView({ needs, onNeedClick }: NeedGridViewProps) {
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
        <div className="h-full overflow-auto p-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                {needs.map((need) => {
                    const status = statusConfig[need.status];
                    const priority = priorityConfig[need.priority];
                    const category = categoryConfig[need.category];

                    return (
                        <div
                            key={need.uuid}
                            onClick={() => onNeedClick(need)}
                            className="
                                bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700
                                p-4 cursor-pointer shadow-sm
                                hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600
                                transition-all duration-200 flex flex-col
                            "
                        >
                            {/* Header with Status and Priority */}
                            <div className="flex items-center justify-between gap-2 mb-3">
                                {status && (
                                    <span className={`px-2 py-1 rounded text-xs font-medium ${status.color}`}>
                                        {status.label}
                                    </span>
                                )}
                                {priority && (
                                    <span className={`px-2 py-1 rounded text-xs font-medium ${priority.bgColor}`}>
                                        {priority.label}
                                    </span>
                                )}
                            </div>

                            {/* Reference and Title */}
                            <div className="mb-3">
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1 font-mono">
                                    {need.reference}
                                </p>
                                <h4 className="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2">
                                    {need.title}
                                </h4>
                            </div>

                            {/* Description */}
                            {need.description && (
                                <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-3 flex-grow">
                                    {need.description}
                                </p>
                            )}

                            {/* Category */}
                            {category && (
                                <div className="flex items-center gap-1 mb-3">
                                    <span className="text-sm">{category.icon}</span>
                                    <span className="text-xs text-gray-600 dark:text-gray-400">
                                        {category.label}
                                    </span>
                                </div>
                            )}

                            {/* Meta Info */}
                            <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <div className="flex items-center gap-3">
                                    {need.estimated_cost !== undefined && need.estimated_cost !== null && (
                                        <div className="flex items-center gap-1">
                                            <CurrencyEuroIcon className="h-3.5 w-3.5" />
                                            <span>{formatCurrency(Number(need.estimated_cost))}</span>
                                        </div>
                                    )}
                                    {need.needed_by_date && (
                                        <div className="flex items-center gap-1">
                                            <CalendarIcon className="h-3.5 w-3.5" />
                                            <span>{formatDateShort(need.needed_by_date)}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    {need.comments_count !== undefined && need.comments_count > 0 && (
                                        <div className="flex items-center gap-1">
                                            <ChatBubbleLeftIcon className="h-3.5 w-3.5" />
                                            <span>{need.comments_count}</span>
                                        </div>
                                    )}
                                    {need.attachments_count !== undefined && need.attachments_count > 0 && (
                                        <div className="flex items-center gap-1">
                                            <PaperClipIcon className="h-3.5 w-3.5" />
                                            <span>{need.attachments_count}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Footer - Assignee */}
                            <div className="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
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
                                        <span className="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {need.assigned_to.full_name}
                                        </span>
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-2">
                                        <UserCircleIcon className="h-6 w-6 text-gray-300 dark:text-gray-600" />
                                        <span className="text-xs text-gray-400">
                                            Non assigné
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
