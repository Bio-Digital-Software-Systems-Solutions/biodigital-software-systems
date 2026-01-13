import React, { useCallback, useMemo } from 'react';
import {
    ReactFlow,
    MiniMap,
    Controls,
    Background,
    useNodesState,
    useEdgesState,
    Connection,
    Edge,
    Node,
    BackgroundVariant,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useWorkflowStore } from '@/stores/workflowStore';
import type { WorkflowStep, WorkflowTransition, StepType } from '@/Types/workflow';
import WorkflowNode from './WorkflowNode';
import WorkflowEdge from './WorkflowEdge';

interface WorkflowCanvasProps {
    readOnly?: boolean;
}

const nodeTypes = {
    workflowStep: WorkflowNode,
} as const;

const edgeTypes = {
    workflowEdge: WorkflowEdge,
} as const;

export default function WorkflowCanvas({ readOnly = false }: WorkflowCanvasProps) {
    const {
        steps = [],
        transitions = [],
        addStep,
        updateStep,
        removeStep,
        addTransition,
        removeTransition,
        selectStep,
        selectTransition,
        selectedStepId,
    } = useWorkflowStore();

    // Convert steps to React Flow nodes
    const nodes = useMemo<Node[]>(() => {
        return (steps || []).map((step) => ({
            id: step.uuid,
            type: 'workflowStep',
            position: { x: step.position_x, y: step.position_y },
            data: {
                label: step.name,
                type: step.type,
                description: step.description,
                isStart: step.is_start,
                isEnd: step.is_end,
                config: step.config,
            },
            selected: selectedStepId === step.uuid,
        }));
    }, [steps, selectedStepId]);

    // Convert transitions to React Flow edges
    const edges = useMemo<Edge[]>(() => {
        const result: Edge[] = [];

        (transitions || []).forEach((transition) => {
            // Use UUID references if available (for new steps), fallback to ID lookup (for saved steps)
            let sourceUuid: string | undefined;
            let targetUuid: string | undefined;

            if (transition.from_step_uuid && transition.to_step_uuid) {
                // New transitions store UUIDs directly
                sourceUuid = transition.from_step_uuid;
                targetUuid = transition.to_step_uuid;
            } else {
                // Legacy transitions from database use ID references
                const fromStep = steps.find((s) => s.id === transition.from_step_id);
                const toStep = steps.find((s) => s.id === transition.to_step_id);
                sourceUuid = fromStep?.uuid;
                targetUuid = toStep?.uuid;
            }

            // Skip edges where we can't find the source or target step
            if (!sourceUuid || !targetUuid) {
                return;
            }

            result.push({
                id: transition.uuid,
                type: 'workflowEdge',
                source: sourceUuid,
                sourceHandle: 'source',
                target: targetUuid,
                targetHandle: 'target',
                data: {
                    label: transition.name,
                    conditionType: transition.condition_type,
                    conditionConfig: transition.condition_config,
                    isDefault: transition.is_default,
                },
            });
        });

        return result;
    }, [transitions, steps]);

    const [flowNodes, setNodes, onNodesChange] = useNodesState(nodes);
    const [flowEdges, setEdges, onEdgesChange] = useEdgesState(edges);

    // Sync React Flow state with store
    React.useEffect(() => {
        setNodes(nodes);
    }, [nodes, setNodes]);

    React.useEffect(() => {
        setEdges(edges);
    }, [edges, setEdges]);

    const onConnect = useCallback(
        (connection: Connection) => {
            if (readOnly) return;
            if (connection.source && connection.target) {
                addTransition(connection.source, connection.target);
            }
        },
        [addTransition, readOnly]
    );

    const onNodeClick = useCallback(
        (_: React.MouseEvent, node: Node) => {
            selectStep(node.id);
        },
        [selectStep]
    );

    const onEdgeClick = useCallback(
        (_: React.MouseEvent, edge: Edge) => {
            selectTransition(edge.id);
        },
        [selectTransition]
    );

    const onNodesDelete = useCallback(
        (nodesToDelete: Node[]) => {
            if (readOnly) return;
            nodesToDelete.forEach((node) => removeStep(node.id));
        },
        [removeStep, readOnly]
    );

    const onEdgesDelete = useCallback(
        (edgesToDelete: Edge[]) => {
            if (readOnly) return;
            edgesToDelete.forEach((edge) => removeTransition(edge.id));
        },
        [removeTransition, readOnly]
    );

    const onNodeDragStop = useCallback(
        (_: React.MouseEvent, node: Node) => {
            if (readOnly) return;
            updateStep(node.id, {
                position_x: node.position.x,
                position_y: node.position.y,
            });
        },
        [updateStep, readOnly]
    );

    const onDrop = useCallback(
        (event: React.DragEvent) => {
            if (readOnly) return;

            event.preventDefault();
            const type = event.dataTransfer.getData('application/workflow-step-type') as StepType;

            if (!type) return;

            const position = {
                x: event.clientX - 200,
                y: event.clientY - 100,
            };

            addStep(type, position);
        },
        [addStep, readOnly]
    );

    const onDragOver = useCallback((event: React.DragEvent) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }, []);

    const onPaneClick = useCallback(() => {
        selectStep(null);
        selectTransition(null);
    }, [selectStep, selectTransition]);

    return (
        <div className="h-full w-full" onDrop={onDrop} onDragOver={onDragOver}>
            <ReactFlow
                nodes={flowNodes}
                edges={flowEdges}
                onNodesChange={readOnly ? undefined : onNodesChange}
                onEdgesChange={readOnly ? undefined : onEdgesChange}
                onConnect={onConnect}
                onNodeClick={onNodeClick}
                onEdgeClick={onEdgeClick}
                onNodesDelete={onNodesDelete}
                onEdgesDelete={onEdgesDelete}
                onNodeDragStop={onNodeDragStop}
                onPaneClick={onPaneClick}
                nodeTypes={nodeTypes}
                edgeTypes={edgeTypes}
                fitView
                snapToGrid
                snapGrid={[15, 15]}
                deleteKeyCode={readOnly ? null : 'Delete'}
                className="bg-gray-50 dark:bg-gray-900"
            >
                <Controls />
                <MiniMap
                    nodeColor={(node) => {
                        switch (node.data?.type) {
                            case 'start':
                                return '#22c55e';
                            case 'end':
                                return '#ef4444';
                            case 'approval':
                                return '#f59e0b';
                            case 'form':
                                return '#3b82f6';
                            case 'condition':
                                return '#8b5cf6';
                            default:
                                return '#6b7280';
                        }
                    }}
                />
                <Background variant={BackgroundVariant.Dots} gap={20} size={1} />
            </ReactFlow>
        </div>
    );
}
