import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Squares2X2Icon, ListBulletIcon, TableCellsIcon, ChevronUpIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

interface Project {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  description?: string;
  status: string;
  priority: string;
  color?: string;
  tasks_count: number;
  progress: number;
  manager?: {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
  };
}

interface Props {
  projects: Project[];
  filters: {
    sort_by?: string;
    sort_direction?: string;
  };
}

type ViewMode = 'grid' | 'list' | 'table';

export default function ProjectsIndex({ projects, filters }: Props) {
  const [viewMode, setViewMode] = useState<ViewMode>('grid');

  const handleSort = (column: string) => {
    const currentDirection = filters.sort_by === column ? filters.sort_direction : null;
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

    router.get(route('projects.index'), {
      ...filters,
      sort_by: column,
      sort_direction: newDirection,
    }, {
      preserveState: true,
      replace: true,
    });
  };

  const getSortIcon = (column: string) => {
    if (filters.sort_by !== column) {
      return null;
    }
    return filters.sort_direction === 'asc' ? (
      <ChevronUpIcon className="w-4 h-4 inline ml-1" />
    ) : (
      <ChevronDownIcon className="w-4 h-4 inline ml-1" />
    );
  };

  return (
    <DashboardLayout
      title="Projets"
      description="Gérez vos projets et suivez leur progression"
      actions={
        <>
          <div className="flex gap-2 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
            <button
              onClick={() => setViewMode('grid')}
              className={`p-2 rounded ${
                viewMode === 'grid'
                  ? 'bg-white dark:bg-gray-700 shadow'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              }`}
              title="Vue grille"
            >
              <Squares2X2Icon className="h-5 w-5 dark:text-white" />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`p-2 rounded ${
                viewMode === 'list'
                  ? 'bg-white dark:bg-gray-700 shadow'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              }`}
              title="Vue liste"
            >
              <ListBulletIcon className="h-5 w-5 dark:text-white" />
            </button>
            <button
              onClick={() => setViewMode('table')}
              className={`p-2 rounded ${
                viewMode === 'table'
                  ? 'bg-white dark:bg-gray-700 shadow'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-700'
              }`}
              title="Vue tableau"
            >
              <TableCellsIcon className="h-5 w-5 dark:text-white" />
            </button>
          </div>
          <Link
            href="/projects/create"
            className="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary"
          >
            Nouveau projet
          </Link>
        </>
      }
    >
      <Head title="Projets" />

      {/* Grid View */}
        {viewMode === 'grid' && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {projects.map((project) => (
                <Link
                  key={project.id}
                  href={route('projects.show', project.uuid)}
                  className="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow"
                >
                  <div
                    className="h-2 rounded-t-lg"
                    style={{ backgroundColor: project.color }}
                  ></div>

                  <div className="p-6">
                    <div className="flex items-start justify-between mb-4">
                      <h3 className="text-lg font-semibold dark:text-white">
                        {project.name}
                      </h3>
                      <span
                        className={`px-2 py-1 text-xs rounded ${
                          project.status === 'active'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                            : project.status === 'planning'
                            ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {project.status}
                      </span>
                    </div>

                    {project.description && (
                      <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                        {project.description}
                      </p>
                    )}

                    <div className="flex items-center justify-between text-sm">
                      <div className="text-gray-500 dark:text-gray-400">
                        {project.tasks_count || 0} tâches
                      </div>
                      <div className="flex items-center gap-2">
                        <div className="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                          <div
                            className="bg-primary h-2 rounded-full"
                            style={{ width: `${project.progress || 0}%` }}
                          ></div>
                        </div>
                        <span className="text-gray-600 dark:text-gray-400">
                          {project.progress || 0}%
                        </span>
                      </div>
                    </div>

                    {project.manager && (
                      <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                            {project.manager.name?.charAt(0) || project.manager.first_name?.charAt(0) || '?'}
                          </div>
                          <span className="text-xs text-gray-600 dark:text-gray-400">
                            {project.manager.name || `${project.manager.first_name} ${project.manager.last_name}`}
                          </span>
                        </div>
                      </div>
                    )}
                  </div>
                </Link>
              ))}
          </div>
        )}

        {/* List View */}
        {viewMode === 'list' && (
          <div className="space-y-4">
            {projects.map((project) => (
              <Link
                key={project.id}
                href={route('projects.show', project.uuid)}
                className="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center p-6 gap-4">
                  <div
                    className="w-2 h-16 rounded"
                    style={{ backgroundColor: project.color }}
                  ></div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="text-lg font-semibold dark:text-white">
                        {project.name}
                      </h3>
                      <span
                        className={`px-2 py-1 text-xs rounded ${
                          project.status === 'active'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                            : project.status === 'planning'
                            ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {project.status}
                      </span>
                    </div>

                    {project.description && (
                      <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-1 mb-2">
                        {project.description}
                      </p>
                    )}

                    <div className="flex items-center gap-4 text-sm">
                      <div className="text-gray-500 dark:text-gray-400">
                        {project.tasks_count || 0} tâches
                      </div>
                      {project.manager && (
                        <div className="flex items-center gap-2">
                          <div className="w-5 h-5 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                            {project.manager.name?.charAt(0) || project.manager.first_name?.charAt(0) || '?'}
                          </div>
                          <span className="text-xs text-gray-600 dark:text-gray-400">
                            {project.manager.name || `${project.manager.first_name} ${project.manager.last_name}`}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <div className="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        className="bg-primary h-2 rounded-full"
                        style={{ width: `${project.progress || 0}%` }}
                      ></div>
                    </div>
                    <span className="text-sm font-medium text-gray-600 dark:text-gray-400 w-12 text-right">
                      {project.progress || 0}%
                    </span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}

        {/* Table View */}
        {viewMode === 'table' && (
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                    onClick={() => handleSort('name')}
                  >
                    Projet {getSortIcon('name')}
                  </th>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                    onClick={() => handleSort('status')}
                  >
                    Statut {getSortIcon('status')}
                  </th>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                    onClick={() => handleSort('manager')}
                  >
                    Manager {getSortIcon('manager')}
                  </th>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                    onClick={() => handleSort('tasks_count')}
                  >
                    Tâches {getSortIcon('tasks_count')}
                  </th>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                    onClick={() => handleSort('progress')}
                  >
                    Progression {getSortIcon('progress')}
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {projects.map((project) => (
                  <tr
                    key={project.id}
                    className="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    onClick={() => window.location.href = route('projects.show', project.uuid)}
                  >
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-3">
                        <div
                          className="w-1 h-10 rounded"
                          style={{ backgroundColor: project.color }}
                        ></div>
                        <div>
                          <div className="text-sm font-medium text-gray-900 dark:text-white">
                            {project.name}
                          </div>
                          {project.description && (
                            <div className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                              {project.description}
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-1 text-xs rounded ${
                          project.status === 'active'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                            : project.status === 'planning'
                            ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {project.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {project.manager && (
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                            {project.manager.name?.charAt(0) || project.manager.first_name?.charAt(0) || '?'}
                          </div>
                          <span className="text-sm text-gray-600 dark:text-gray-400">
                            {project.manager.name || `${project.manager.first_name} ${project.manager.last_name}`}
                          </span>
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        {project.tasks_count || 0}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-3">
                        <div className="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                          <div
                            className="bg-primary h-2 rounded-full"
                            style={{ width: `${project.progress || 0}%` }}
                          ></div>
                        </div>
                        <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                          {project.progress || 0}%
                        </span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

      {projects.length === 0 && (
        <div className="text-center py-12">
          <p className="text-gray-500 dark:text-gray-400">
            Aucun projet trouvé. Créez votre premier projet !
          </p>
        </div>
      )}
    </DashboardLayout>
  );
}
