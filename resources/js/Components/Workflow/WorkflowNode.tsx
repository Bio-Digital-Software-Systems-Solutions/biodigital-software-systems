import React, { memo } from 'react';
import { Handle, Position } from '@xyflow/react';
import type { NodeProps } from '@xyflow/react';
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
import type { StepType, WorkflowNodeData } from '@/Types/workflow';

const getStepIcon = (type: StepType) => {
    const iconClass = 'h-5 w-5';
    switch (type) {
        case 'start':
            return <PlayIcon className={iconClass} />;
        case 'end':
            return <StopIcon className={iconClass} />;
        case 'task':
            return <ClipboardDocumentCheckIcon className={iconClass} />;
        case 'approval':
            return <CheckCircleIcon className={iconClass} />;
        case 'form':
            return <DocumentTextIcon className={iconClass} />;
        case 'condition':
            return <AdjustmentsHorizontalIcon className={iconClass} />;
        case 'parallel':
            return <ArrowsPointingOutIcon className={iconClass} />;
        case 'notification':
            return <BellIcon className={iconClass} />;
        case 'delay':
            return <ClockIcon className={iconClass} />;
        case 'script':
            return <CodeBracketIcon className={iconClass} />;
        case 'sub_workflow':
            return <ArrowPathIcon className={iconClass} />;
        default:
            return <ClipboardDocumentCheckIcon className={iconClass} />;
    }
};

const getStepColor = (type: StepType): string => {
    switch (type) {
        case 'start':
            return 'bg-green-100 border-green-500 dark:bg-green-900/30 dark:border-green-400';
        case 'end':
            return 'bg-red-100 border-red-500 dark:bg-red-900/30 dark:border-red-400';
        case 'task':
            return 'bg-blue-100 border-blue-500 dark:bg-blue-900/30 dark:border-blue-400';
        case 'approval':
            return 'bg-amber-100 border-amber-500 dark:bg-amber-900/30 dark:border-amber-400';
        case 'form':
            return 'bg-indigo-100 border-indigo-500 dark:bg-indigo-900/30 dark:border-indigo-400';
        case 'condition':
            return 'bg-purple-100 border-purple-500 dark:bg-purple-900/30 dark:border-purple-400';
        case 'parallel':
            return 'bg-cyan-100 border-cyan-500 dark:bg-cyan-900/30 dark:border-cyan-400';
        case 'notification':
            return 'bg-pink-100 border-pink-500 dark:bg-pink-900/30 dark:border-pink-400';
        case 'delay':
            return 'bg-gray-100 border-gray-500 dark:bg-gray-700 dark:border-gray-400';
        case 'script':
            return 'bg-slate-100 border-slate-500 dark:bg-slate-900/30 dark:border-slate-400';
        case 'sub_workflow':
            return 'bg-teal-100 border-teal-500 dark:bg-teal-900/30 dark:border-teal-400';
        default:
            return 'bg-gray-100 border-gray-500 dark:bg-gray-700 dark:border-gray-400';
    }
};

const getIconColor = (type: StepType): string => {
    switch (type) {
        case 'start':
            return 'text-green-600 dark:text-green-400';
        case 'end':
            return 'text-red-600 dark:text-red-400';
        case 'task':
            return 'text-blue-600 dark:text-blue-400';
        case 'approval':
            return 'text-amber-600 dark:text-amber-400';
        case 'form':
            return 'text-indigo-600 dark:text-indigo-400';
        case 'condition':
            return 'text-purple-600 dark:text-purple-400';
        case 'parallel':
            return 'text-cyan-600 dark:text-cyan-400';
        case 'notification':
            return 'text-pink-600 dark:text-pink-400';
        case 'delay':
            return 'text-gray-600 dark:text-gray-400';
        case 'script':
            return 'text-slate-600 dark:text-slate-400';
        case 'sub_workflow':
            return 'text-teal-600 dark:text-teal-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

function WorkflowNode({ data, selected }: NodeProps) {
    const nodeData = data as unknown as WorkflowNodeData;

    return (
        <div
            className={`
                px-4 py-3 rounded-lg border-2 shadow-md min-w-[160px] max-w-[200px]
                ${getStepColor(nodeData.type)}
                ${selected ? 'ring-2 ring-primary ring-offset-2' : ''}
                transition-all duration-200
            `}
        >
            {/* Input Handle - always render for proper edge connections */}
            <Handle
                type="target"
                position={Position.Top}
                id="target"
                className={`!w-3 !h-3 !bg-gray-400 !border-2 !border-white dark:!border-gray-800 ${nodeData.isStart ? '!opacity-0 !pointer-events-none' : ''}`}
            />

            <div className="flex items-center gap-2">
                <div className={`flex-shrink-0 ${getIconColor(nodeData.type)}`}>
                    {getStepIcon(nodeData.type)}
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {nodeData.label}
                    </p>
                    {nodeData.description && (
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {nodeData.description}
                        </p>
                    )}
                </div>
            </div>

            {/* Output Handle - always render for proper edge connections */}
            <Handle
                type="source"
                position={Position.Bottom}
                id="source"
                className={`!w-3 !h-3 !bg-gray-400 !border-2 !border-white dark:!border-gray-800 ${nodeData.isEnd ? '!opacity-0 !pointer-events-none' : ''}`}
            />
        </div>
    );
}

export default memo(WorkflowNode);
