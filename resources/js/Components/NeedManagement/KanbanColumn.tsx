import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { PlusIcon } from '@heroicons/react/24/outline';

interface KanbanColumnProps {
    id: string;
    title: string;
    count: number;
    color: string;
    headerColor: string;
    children: React.ReactNode;
    onAddClick?: () => void;
}

export default function KanbanColumn({
    id,
    title,
    count,
    color,
    headerColor,
    children,
    onAddClick,
}: KanbanColumnProps) {
    const { isOver, setNodeRef } = useDroppable({
        id,
    });

    return (
        <div
            ref={setNodeRef}
            className={`
                flex flex-col rounded-lg min-h-[300px] lg:min-h-[500px]
                ${color}
                ${isOver ? 'ring-2 ring-primary ring-offset-2' : ''}
                transition-all duration-200
            `}
        >
            {/* Column Header */}
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div className="flex items-center gap-2">
                    <h3 className={`font-semibold text-sm ${headerColor}`}>
                        {title}
                    </h3>
                    <span className={`
                        px-2 py-0.5 rounded-full text-xs font-medium
                        bg-white dark:bg-gray-800
                        text-gray-600 dark:text-gray-400
                        border border-gray-200 dark:border-gray-700
                    `}>
                        {count}
                    </span>
                </div>
                {onAddClick && (
                    <button
                        type="button"
                        onClick={onAddClick}
                        className={`
                            p-1 rounded hover:bg-white dark:hover:bg-gray-700
                            ${headerColor}
                            transition-colors
                        `}
                    >
                        <PlusIcon className="h-5 w-5" />
                    </button>
                )}
            </div>

            {/* Column Content */}
            <div className="flex-1 overflow-y-auto p-3 space-y-3">
                {children}
                {count === 0 && (
                    <div className="
                        p-4 border-2 border-dashed rounded-lg
                        border-gray-300 dark:border-gray-600
                        text-center
                    ">
                        <p className="text-sm text-gray-400 dark:text-gray-500">
                            Aucun élément
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
