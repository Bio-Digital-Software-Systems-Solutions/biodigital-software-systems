import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    Bars3Icon,
    TrashIcon,
    DocumentDuplicateIcon,
} from '@heroicons/react/24/outline';
import { useFormBuilderStore } from '@/stores/formBuilderStore';
import type { FormField } from '@/Types/form';
import FieldRenderer from './FieldRenderer';

interface DraggableFieldProps {
    field: FormField;
    isSelected: boolean;
    onClick: () => void;
    disabled?: boolean;
}

export default function DraggableField({
    field,
    isSelected,
    onClick,
    disabled = false,
}: DraggableFieldProps) {
    const { removeField, duplicateField } = useFormBuilderStore();

    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: field.uuid,
        disabled,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const handleDelete = (e: React.MouseEvent) => {
        e.stopPropagation();
        removeField(field.uuid);
    };

    const handleDuplicate = (e: React.MouseEvent) => {
        e.stopPropagation();
        duplicateField(field.uuid);
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            onClick={onClick}
            className={`
                group relative rounded-lg border-2 transition-all duration-200
                ${isSelected
                    ? 'border-primary ring-2 ring-primary/20'
                    : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                }
                ${isDragging ? 'z-50' : ''}
            `}
        >
            {/* Field Actions */}
            {!disabled && (
                <div className={`
                    absolute -top-3 right-2 flex items-center gap-1
                    opacity-0 group-hover:opacity-100 transition-opacity
                    ${isSelected ? 'opacity-100' : ''}
                `}>
                    <button
                        type="button"
                        onClick={handleDuplicate}
                        className="
                            p-1 rounded bg-white dark:bg-gray-800
                            border border-gray-200 dark:border-gray-700
                            text-gray-500 hover:text-primary
                            shadow-sm
                        "
                        title="Dupliquer"
                    >
                        <DocumentDuplicateIcon className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={handleDelete}
                        className="
                            p-1 rounded bg-white dark:bg-gray-800
                            border border-gray-200 dark:border-gray-700
                            text-gray-500 hover:text-red-500
                            shadow-sm
                        "
                        title="Supprimer"
                    >
                        <TrashIcon className="h-4 w-4" />
                    </button>
                </div>
            )}

            {/* Drag Handle */}
            {!disabled && (
                <div
                    {...attributes}
                    {...listeners}
                    className={`
                        absolute -left-3 top-1/2 -translate-y-1/2
                        p-1 rounded bg-white dark:bg-gray-800
                        border border-gray-200 dark:border-gray-700
                        text-gray-400 cursor-grab active:cursor-grabbing
                        opacity-0 group-hover:opacity-100 transition-opacity
                        ${isSelected ? 'opacity-100' : ''}
                    `}
                >
                    <Bars3Icon className="h-4 w-4" />
                </div>
            )}

            {/* Field Content */}
            <div className="p-4 bg-white dark:bg-gray-800 rounded-lg">
                <FieldRenderer field={field} />
            </div>

            {/* Required Indicator */}
            {field.is_required && (
                <div className="absolute -top-1 -right-1">
                    <span className="flex h-3 w-3">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                </div>
            )}
        </div>
    );
}
