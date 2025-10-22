import React, { useMemo } from 'react';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import { ProjectTask, TaskStatus, KanbanColumn } from '@/Types/Project';
import { useTasks } from '@/Hooks/useTasks';
import { apiLogger } from '@/utils/logger';

interface KanbanBoardProps {
  projectId: string | number;
}

export const KanbanBoard: React.FC<KanbanBoardProps> = ({ projectId }) => {
  const { tasks, updateTaskStatus } = useTasks(projectId);

  const columns: KanbanColumn[] = useMemo(() => [
    { status: TaskStatus.TODO, label: 'À faire', color: 'gray' },
    { status: TaskStatus.IN_PROGRESS, label: 'En cours', color: 'blue' },
    { status: TaskStatus.IN_REVIEW, label: 'En révision', color: 'yellow' },
    { status: TaskStatus.DONE, label: 'Terminé', color: 'green' },
  ], []);

  const getTasksByStatus = (status: TaskStatus) => {
    return tasks.filter(task => task.status === status);
  };

  const handleDragEnd = async (result: DropResult) => {
    const { destination, source, draggableId } = result;

    if (!destination) return;
    if (destination.droppableId === source.droppableId && destination.index === source.index) return;

    const taskId = parseInt(draggableId);
    const newStatus = destination.droppableId as TaskStatus;

    try {
      await updateTaskStatus(taskId, newStatus);
    } catch (error) {
      apiLogger.error('Error updating task status via drag-and-drop', error);
    }
  };

  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      <div className="flex gap-6 overflow-x-auto p-6">
        {columns.map((column) => {
          const columnTasks = getTasksByStatus(column.status);

          return (
            <div key={column.status} className="flex flex-col min-w-[300px]">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                  <h3 className="font-semibold text-sm dark:text-white">
                    {column.label}
                  </h3>
                  <span className="px-2 py-1 text-xs rounded-full bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                    {columnTasks.length}
                  </span>
                </div>
              </div>

              <Droppable droppableId={column.status}>
                {(provided, snapshot) => (
                  <div
                    ref={provided.innerRef}
                    {...provided.droppableProps}
                    className={`flex-1 space-y-3 rounded-lg p-3 min-h-[200px] transition-colors ${
                      snapshot.isDraggingOver
                        ? 'bg-blue-50 dark:bg-blue-900/20'
                        : 'bg-gray-50 dark:bg-gray-800'
                    }`}
                  >
                    {columnTasks.map((task, index) => (
                      <Draggable
                        key={task.id}
                        draggableId={task.id.toString()}
                        index={index}
                      >
                        {(provided, snapshot) => (
                          <div
                            ref={provided.innerRef}
                            {...provided.draggableProps}
                            {...provided.dragHandleProps}
                            className={`bg-white dark:bg-gray-700 rounded-lg p-4 shadow hover:shadow-md transition-all cursor-grab active:cursor-grabbing ${
                              snapshot.isDragging ? 'shadow-xl rotate-2' : ''
                            }`}
                          >
                            <div className="flex items-start justify-between mb-2">
                              <h4 className="font-medium text-sm dark:text-white line-clamp-2">
                                {task.title}
                              </h4>
                            </div>

                            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                              <span className="font-mono">{task.key}</span>
                              {task.priority && (
                                <span className={`px-2 py-0.5 rounded ${
                                  task.priority === 'highest' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' :
                                  task.priority === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' :
                                  task.priority === 'medium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' :
                                  'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
                                }`}>
                                  {task.priority}
                                </span>
                              )}
                            </div>

                            {task.assignee && (
                              <div className="mt-3 flex items-center gap-2">
                                <div className="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                                  {task.assignee.name?.charAt(0) || task.assignee.first_name?.charAt(0) || '?'}
                                </div>
                                <span className="text-xs text-gray-600 dark:text-gray-400">
                                  {task.assignee.name || `${task.assignee.first_name} ${task.assignee.last_name}`}
                                </span>
                              </div>
                            )}

                            {task.labels && task.labels.length > 0 && (
                              <div className="mt-2 flex flex-wrap gap-1">
                                {task.labels.slice(0, 3).map((label, idx) => (
                                  <span
                                    key={idx}
                                    className="px-2 py-0.5 text-xs rounded bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300"
                                  >
                                    {label}
                                  </span>
                                ))}
                                {task.labels.length > 3 && (
                                  <span className="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300">
                                    +{task.labels.length - 3}
                                  </span>
                                )}
                              </div>
                            )}
                          </div>
                        )}
                      </Draggable>
                    ))}
                    {provided.placeholder}
                  </div>
                )}
              </Droppable>
            </div>
          );
        })}
      </div>
    </DragDropContext>
  );
};
