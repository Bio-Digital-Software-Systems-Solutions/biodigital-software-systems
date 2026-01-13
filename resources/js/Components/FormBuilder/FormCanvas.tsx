import React, { useCallback } from 'react';
import {
    DndContext,
    DragEndEvent,
    DragOverEvent,
    DragStartEvent,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragOverlay,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useFormBuilderStore } from '@/stores/formBuilderStore';
import type { FormFieldType, FormField } from '@/Types/form';
import FieldRenderer from './FieldRenderer';
import DraggableField from './DraggableField';

interface FormCanvasProps {
    readOnly?: boolean;
}

export default function FormCanvas({ readOnly = false }: FormCanvasProps) {
    const {
        fields,
        selectedFieldId,
        addField,
        moveField,
        selectField,
        setDragOverField,
        previewMode,
    } = useFormBuilderStore();

    const [activeId, setActiveId] = React.useState<string | null>(null);
    const activeField = activeId
        ? fields.find((f) => f.uuid === activeId)
        : null;

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const handleDragStart = useCallback((event: DragStartEvent) => {
        setActiveId(event.active.id as string);
    }, []);

    const handleDragOver = useCallback(
        (event: DragOverEvent) => {
            const { over } = event;
            setDragOverField(over?.id as string | null);
        },
        [setDragOverField]
    );

    const handleDragEnd = useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;
            setActiveId(null);
            setDragOverField(null);

            if (!over) return;

            const activeIndex = fields.findIndex((f) => f.uuid === active.id);
            const overIndex = fields.findIndex((f) => f.uuid === over.id);

            if (activeIndex !== overIndex) {
                moveField(active.id as string, overIndex);
            }
        },
        [fields, moveField, setDragOverField]
    );

    const handleDrop = useCallback(
        (event: React.DragEvent) => {
            event.preventDefault();
            const type = event.dataTransfer.getData('application/form-field-type') as FormFieldType;
            if (type) {
                addField(type);
            }
        },
        [addField]
    );

    const handleDragOverCanvas = useCallback((event: React.DragEvent) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    }, []);

    const handleFieldClick = useCallback(
        (fieldId: string) => {
            if (!readOnly && !previewMode) {
                selectField(fieldId);
            }
        },
        [readOnly, previewMode, selectField]
    );

    const handleCanvasClick = useCallback(
        (event: React.MouseEvent) => {
            if (event.target === event.currentTarget) {
                selectField(null);
            }
        },
        [selectField]
    );

    if (previewMode) {
        return (
            <div className="h-full overflow-y-auto p-6 bg-white dark:bg-gray-900">
                <div className="max-w-2xl mx-auto space-y-6">
                    {fields.map((field) => (
                        <FieldRenderer
                            key={field.uuid}
                            field={field}
                            preview
                        />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div
            className="h-full overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900"
            onDrop={handleDrop}
            onDragOver={handleDragOverCanvas}
            onClick={handleCanvasClick}
        >
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragStart={handleDragStart}
                onDragOver={handleDragOver}
                onDragEnd={handleDragEnd}
            >
                <SortableContext
                    items={fields.map((f) => f.uuid)}
                    strategy={verticalListSortingStrategy}
                >
                    <div className="max-w-2xl mx-auto space-y-3">
                        {fields.length === 0 ? (
                            <div className="
                                border-2 border-dashed border-gray-300 dark:border-gray-700
                                rounded-lg p-12 text-center
                            ">
                                <p className="text-gray-500 dark:text-gray-400">
                                    Glissez des champs ici pour commencer
                                </p>
                            </div>
                        ) : (
                            fields.map((field) => (
                                <DraggableField
                                    key={field.uuid}
                                    field={field}
                                    isSelected={selectedFieldId === field.uuid}
                                    onClick={() => handleFieldClick(field.uuid)}
                                    disabled={readOnly}
                                />
                            ))
                        )}
                    </div>
                </SortableContext>

                <DragOverlay>
                    {activeField && (
                        <div className="opacity-80">
                            <FieldRenderer field={activeField} />
                        </div>
                    )}
                </DragOverlay>
            </DndContext>
        </div>
    );
}
