import React from 'react';
import { StoryTask } from '@/Types/Agile';

interface Props {
    storyUuid: string;
    storyId: number;
    tasks: StoryTask[];
    users: Array<{ id: number; name: string }>;
    statuses: Array<{ id: number; name: string; color: string }>;
}

// Placeholder — full implementation in F6.
export const StoryTasksList: React.FC<Props> = ({ tasks }) => {
    if (tasks.length === 0) {
        return (
            <p className="text-center text-gray-500 py-10">
                Aucune tâche technique.
            </p>
        );
    }

    return (
        <ul className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            {tasks.map((t) => (
                <li key={t.id} className="p-4 flex items-center justify-between">
                    <span className="font-medium">{t.title}</span>
                    <span className="text-xs text-gray-500">{t.work_type_label ?? '—'}</span>
                </li>
            ))}
        </ul>
    );
};
