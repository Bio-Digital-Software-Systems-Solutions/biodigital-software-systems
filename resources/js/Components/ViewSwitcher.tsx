import React from 'react';
import { Squares2X2Icon, ListBulletIcon, CalendarDaysIcon } from '@heroicons/react/24/outline';

type ViewMode = 'grid' | 'list' | 'calendar';

interface ViewSwitcherProps {
    currentView: ViewMode;
    onViewChange: (view: ViewMode) => void;
    showCalendar?: boolean;
}

export default function ViewSwitcher({ currentView, onViewChange, showCalendar = false }: ViewSwitcherProps) {
    return (
        <div className="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1">
            <button
                onClick={() => onViewChange('grid')}
                className={`
                    px-3 py-2 rounded-md transition-colors
                    ${currentView === 'grid'
                        ? 'bg-icc-blue text-white'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }
                `}
                title="Vue grille"
            >
                <Squares2X2Icon className="h-5 w-5" />
            </button>
            <button
                onClick={() => onViewChange('list')}
                className={`
                    px-3 py-2 rounded-md transition-colors
                    ${currentView === 'list'
                        ? 'bg-icc-blue text-white'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }
                `}
                title="Vue liste"
            >
                <ListBulletIcon className="h-5 w-5" />
            </button>
            {showCalendar && (
                <button
                    onClick={() => onViewChange('calendar')}
                    className={`
                        px-3 py-2 rounded-md transition-colors
                        ${currentView === 'calendar'
                            ? 'bg-icc-blue text-white'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        }
                    `}
                    title="Vue calendrier"
                >
                    <CalendarDaysIcon className="h-5 w-5" />
                </button>
            )}
        </div>
    );
}
