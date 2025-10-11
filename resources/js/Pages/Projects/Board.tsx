import React from 'react';
import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { KanbanBoard } from '@/Components/Projects/KanbanBoard';

interface Project {
    id: number;
    uuid: string;
    name: string;
    tasks?: Array<{
        id: number;
        title: string;
        status: string;
        priority: string;
        assignee?: {
            id: number;
            first_name: string;
            last_name: string;
        };
    }>;
}

interface Props {
    project: Project;
}

export default function ProjectBoard({ project }: Props) {
    return (
        <DashboardLayout>
            <Head title={`Kanban - ${project.name}`} />

            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold dark:text-white">{project.name}</h1>
                    <p className="text-gray-500 dark:text-gray-400">Vue Kanban</p>
                </div>

                <KanbanBoard projectId={project.uuid} />
            </div>
        </DashboardLayout>
    );
}
