import React, { memo } from 'react';
import { getBezierPath, EdgeLabelRenderer, BaseEdge } from '@xyflow/react';
import type { EdgeProps } from '@xyflow/react';
import type { WorkflowEdgeData } from '@/Types/workflow';

function WorkflowEdge({
    id,
    sourceX,
    sourceY,
    targetX,
    targetY,
    sourcePosition,
    targetPosition,
    data,
    selected,
}: EdgeProps) {
    const edgeData = data as WorkflowEdgeData | undefined;

    const [edgePath, labelX, labelY] = getBezierPath({
        sourceX,
        sourceY,
        sourcePosition,
        targetX,
        targetY,
        targetPosition,
    });

    const getEdgeColor = () => {
        if (selected) return '#3b82f6';
        if (edgeData?.isDefault) return '#22c55e';
        if (edgeData?.conditionType === 'approval_result') return '#f59e0b';
        return '#94a3b8';
    };

    return (
        <>
            <BaseEdge
                id={id}
                path={edgePath}
                style={{
                    stroke: getEdgeColor(),
                    strokeWidth: selected ? 3 : 2,
                }}
                markerEnd="url(#arrow)"
            />
            {edgeData?.label && (
                <EdgeLabelRenderer>
                    <div
                        style={{
                            position: 'absolute',
                            transform: `translate(-50%, -50%) translate(${labelX}px, ${labelY}px)`,
                            pointerEvents: 'all',
                        }}
                        className={`
                            px-2 py-1 text-xs rounded-md shadow-sm
                            ${selected
                                ? 'bg-primary text-white'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'
                            }
                        `}
                    >
                        {edgeData.label}
                    </div>
                </EdgeLabelRenderer>
            )}
            {/* Arrow marker definition */}
            <svg style={{ position: 'absolute', width: 0, height: 0 }}>
                <defs>
                    <marker
                        id="arrow"
                        viewBox="0 0 10 10"
                        refX="8"
                        refY="5"
                        markerWidth="6"
                        markerHeight="6"
                        orient="auto-start-reverse"
                    >
                        <path
                            d="M 0 0 L 10 5 L 0 10 z"
                            fill={getEdgeColor()}
                        />
                    </marker>
                </defs>
            </svg>
        </>
    );
}

export default memo(WorkflowEdge);
