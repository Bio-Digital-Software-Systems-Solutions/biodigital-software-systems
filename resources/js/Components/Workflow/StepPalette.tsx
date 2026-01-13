import React from 'react';
import {
    PlayIcon,
    StopIcon,
    ClipboardDocumentCheckIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    AdjustmentsHorizontalIcon,
    ArrowsPointingOutIcon,
    BellIcon,
    ClockIcon,
    CodeBracketIcon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';
import type { StepType } from '@/Types/workflow';

interface StepTypeItem {
    type: StepType;
    label: string;
    description: string;
    icon: React.ReactNode;
    color: string;
    category: 'flow' | 'action' | 'logic' | 'integration';
}

const stepTypes: StepTypeItem[] = [
    {
        type: 'start',
        label: 'Début',
        description: 'Point de départ du workflow',
        icon: <PlayIcon className="h-5 w-5" />,
        color: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        category: 'flow',
    },
    {
        type: 'end',
        label: 'Fin',
        description: 'Point de terminaison',
        icon: <StopIcon className="h-5 w-5" />,
        color: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        category: 'flow',
    },
    {
        type: 'task',
        label: 'Tâche',
        description: 'Tâche à accomplir',
        icon: <ClipboardDocumentCheckIcon className="h-5 w-5" />,
        color: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        category: 'action',
    },
    {
        type: 'approval',
        label: 'Approbation',
        description: 'Demande d\'approbation',
        icon: <CheckCircleIcon className="h-5 w-5" />,
        color: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        category: 'action',
    },
    {
        type: 'form',
        label: 'Formulaire',
        description: 'Formulaire à remplir',
        icon: <DocumentTextIcon className="h-5 w-5" />,
        color: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
        category: 'action',
    },
    {
        type: 'condition',
        label: 'Condition',
        description: 'Branchement conditionnel',
        icon: <AdjustmentsHorizontalIcon className="h-5 w-5" />,
        color: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
        category: 'logic',
    },
    {
        type: 'parallel',
        label: 'Parallèle',
        description: 'Exécution parallèle',
        icon: <ArrowsPointingOutIcon className="h-5 w-5" />,
        color: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
        category: 'logic',
    },
    {
        type: 'notification',
        label: 'Notification',
        description: 'Envoyer une notification',
        icon: <BellIcon className="h-5 w-5" />,
        color: 'bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400',
        category: 'integration',
    },
    {
        type: 'delay',
        label: 'Délai',
        description: 'Attendre un délai',
        icon: <ClockIcon className="h-5 w-5" />,
        color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        category: 'logic',
    },
    {
        type: 'script',
        label: 'Script',
        description: 'Exécuter un script',
        icon: <CodeBracketIcon className="h-5 w-5" />,
        color: 'bg-slate-100 text-slate-600 dark:bg-slate-900/30 dark:text-slate-400',
        category: 'integration',
    },
    {
        type: 'sub_workflow',
        label: 'Sous-workflow',
        description: 'Déclencher un autre workflow',
        icon: <ArrowPathIcon className="h-5 w-5" />,
        color: 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
        category: 'integration',
    },
];

const categories = [
    { id: 'flow', label: 'Flux' },
    { id: 'action', label: 'Actions' },
    { id: 'logic', label: 'Logique' },
    { id: 'integration', label: 'Intégration' },
];

export default function StepPalette() {
    const onDragStart = (event: React.DragEvent, type: StepType) => {
        event.dataTransfer.setData('application/workflow-step-type', type);
        event.dataTransfer.effectAllowed = 'move';
    };

    return (
        <div className="h-full overflow-y-auto p-4 space-y-4">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                Éléments du workflow
            </h3>
            <p className="text-xs text-gray-500 dark:text-gray-400">
                Glissez-déposez les éléments sur le canvas
            </p>

            {categories.map((category) => (
                <div key={category.id} className="space-y-2">
                    <h4 className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {category.label}
                    </h4>
                    <div className="space-y-1">
                        {stepTypes
                            .filter((step) => step.category === category.id)
                            .map((step) => (
                                <div
                                    key={step.type}
                                    draggable
                                    onDragStart={(e) => onDragStart(e, step.type)}
                                    className={`
                                        flex items-center gap-3 p-2 rounded-lg cursor-grab
                                        border border-transparent
                                        hover:border-gray-200 dark:hover:border-gray-700
                                        hover:shadow-sm
                                        transition-all duration-150
                                        ${step.color}
                                    `}
                                >
                                    <div className="flex-shrink-0">{step.icon}</div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium truncate">
                                            {step.label}
                                        </p>
                                        <p className="text-xs opacity-75 truncate">
                                            {step.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
