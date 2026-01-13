import React, { useCallback } from 'react';
import {
    DndContext,
    DragEndEvent,
    DragOverEvent,
    DragStartEvent,
    DragOverlay,
    closestCorners,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useNeedStore, selectNeedsByKanbanColumn, kanbanColumns } from '@/stores/needStore';
import type { DepartmentNeed, NeedStatus } from '@/Types/need';
import KanbanColumn from './KanbanColumn';
import NeedCard from './NeedCard';

const columnConfig = [
    {
        id: 'pending',
        title: 'En attente',
        color: 'bg-gray-100 dark:bg-gray-800',
        headerColor: 'text-gray-600 dark:text-gray-400',
    },
    {
        id: 'review',
        title: 'En révision',
        color: 'bg-blue-50 dark:bg-blue-900/20',
        headerColor: 'text-blue-600 dark:text-blue-400',
    },
    {
        id: 'approved',
        title: 'Approuvé / En cours',
        color: 'bg-green-50 dark:bg-green-900/20',
        headerColor: 'text-green-600 dark:text-green-400',
    },
    {
        id: 'rejected',
        title: 'Rejeté',
        color: 'bg-red-50 dark:bg-red-900/20',
        headerColor: 'text-red-600 dark:text-red-400',
    },
    {
        id: 'completed',
        title: 'Terminé',
        color: 'bg-purple-50 dark:bg-purple-900/20',
        headerColor: 'text-purple-600 dark:text-purple-400',
    },
];

interface KanbanBoardProps {
    onNeedClick?: (need: DepartmentNeed) => void;
    onStatusChange?: (needId: string, newStatus: NeedStatus) => void;
}

export default function KanbanBoard({ onNeedClick, onStatusChange }: KanbanBoardProps) {
    const { needs, moveNeed, selectNeed } = useNeedStore();
    const [activeNeed, setActiveNeed] = React.useState<DepartmentNeed | null>(null);

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

    const handleDragStart = useCallback(
        (event: DragStartEvent) => {
            const need = needs.find((n) => n.uuid === event.active.id);
            if (need) {
                setActiveNeed(need);
            }
        },
        [needs]
    );

    const handleDragOver = useCallback((event: DragOverEvent) => {
        // Handle drag over logic if needed
    }, []);

    const handleDragEnd = useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;
            setActiveNeed(null);

            if (!over) return;

            const activeId = active.id as string;
            const overId = over.id as string;

            // Check if dropped on a column
            const targetColumn = columnConfig.find((col) => col.id === overId);
            if (targetColumn) {
                const statuses = kanbanColumns[targetColumn.id as keyof typeof kanbanColumns];
                const newStatus = statuses[0]; // Use first status of column

                const need = needs.find((n) => n.uuid === activeId);
                if (need && need.status !== newStatus) {
                    moveNeed(activeId, newStatus);
                    onStatusChange?.(activeId, newStatus);
                }
            }
        },
        [needs, moveNeed, onStatusChange]
    );

    const handleNeedClick = useCallback(
        (need: DepartmentNeed) => {
            selectNeed(need.uuid);
            onNeedClick?.(need);
        },
        [selectNeed, onNeedClick]
    );

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
        >
            <div className="h-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 p-4">
                {columnConfig.map((column) => {
                    const columnNeeds = selectNeedsByKanbanColumn(
                        needs,
                        column.id as keyof typeof kanbanColumns
                    );

                    return (
                        <KanbanColumn
                            key={column.id}
                            id={column.id}
                            title={column.title}
                            count={columnNeeds.length}
                            color={column.color}
                            headerColor={column.headerColor}
                        >
                            <SortableContext
                                items={columnNeeds.map((n) => n.uuid)}
                                strategy={verticalListSortingStrategy}
                            >
                                {columnNeeds.map((need) => (
                                    <NeedCard
                                        key={need.uuid}
                                        need={need}
                                        onClick={() => handleNeedClick(need)}
                                    />
                                ))}
                            </SortableContext>
                        </KanbanColumn>
                    );
                })}
            </div>

            <DragOverlay>
                {activeNeed && (
                    <div className="opacity-80 rotate-3">
                        <NeedCard need={activeNeed} isDragging />
                    </div>
                )}
            </DragOverlay>
        </DndContext>
    );
}
