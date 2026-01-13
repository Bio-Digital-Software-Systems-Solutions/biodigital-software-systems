import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    CalendarIcon,
    CurrencyEuroIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
    UserCircleIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentNeed } from '@/Types/need';
import { priorityConfig, categoryConfig, formatDateShort, formatCurrency } from './needConfig';

interface NeedCardProps {
    need: DepartmentNeed;
    onClick?: () => void;
    isDragging?: boolean;
}

export default function NeedCard({ need, onClick, isDragging = false }: NeedCardProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging: isSortableDragging,
    } = useSortable({
        id: need.uuid,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isSortableDragging ? 0.5 : 1,
    };

    const priority = priorityConfig[need.priority];
    const category = categoryConfig[need.category];

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            onClick={onClick}
            className={`
                bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700
                p-3 cursor-pointer shadow-sm
                hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600
                transition-all duration-200
                ${isDragging ? 'shadow-xl' : ''}
            `}
        >
            {/* Header */}
            <div className="flex items-start justify-between gap-2 mb-2">
                <div className="flex-1 min-w-0">
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {need.reference}
                    </p>
                    <h4 className="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {need.title}
                    </h4>
                </div>
                {priority && (
                    <span className={`flex-shrink-0 px-2 py-0.5 rounded text-xs font-medium ${priority.bgColor}`}>
                        {priority.label}
                    </span>
                )}
            </div>

            {/* Description */}
            {need.description && (
                <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">
                    {need.description}
                </p>
            )}

            {/* Category Badge */}
            {category && (
                <div className="flex items-center gap-1 mb-3">
                    <span className="text-sm">{category.icon}</span>
                    <span className="text-xs text-gray-600 dark:text-gray-400">
                        {category.label}
                    </span>
                </div>
            )}

            {/* Meta Info */}
            <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
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
            {need.assigned_to && (
                <div className="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    {need.assigned_to.avatar ? (
                        <img
                            src={need.assigned_to.avatar}
                            alt={need.assigned_to.full_name}
                            className="h-6 w-6 rounded-full"
                        />
                    ) : (
                        <UserCircleIcon className="h-6 w-6 text-gray-400" />
                    )}
                    <span className="text-xs text-gray-600 dark:text-gray-400">
                        {need.assigned_to.full_name}
                    </span>
                </div>
            )}
        </div>
    );
}
