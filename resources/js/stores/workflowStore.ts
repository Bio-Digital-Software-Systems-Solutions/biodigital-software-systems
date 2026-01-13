import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import type {
    DepartmentWorkflow,
    WorkflowStep,
    WorkflowTransition,
    StepType,
    WorkflowNodeData,
    WorkflowEdgeData,
} from '@/Types/workflow';

interface WorkflowBuilderState {
    workflow: DepartmentWorkflow | null;
    steps: WorkflowStep[];
    transitions: WorkflowTransition[];
    selectedStepId: string | null;
    selectedTransitionId: string | null;
    isDirty: boolean;
    isLoading: boolean;
    error: string | null;
}

interface WorkflowBuilderActions {
    setWorkflow: (workflow: DepartmentWorkflow) => void;
    setSteps: (steps: WorkflowStep[]) => void;
    setTransitions: (transitions: WorkflowTransition[]) => void;
    addStep: (type: StepType, position: { x: number; y: number }) => WorkflowStep;
    updateStep: (stepId: string, data: Partial<WorkflowStep>) => void;
    removeStep: (stepId: string) => void;
    addTransition: (fromStepUuid: string, toStepUuid: string) => WorkflowTransition | null;
    updateTransition: (transitionId: string, data: Partial<WorkflowTransition>) => void;
    removeTransition: (transitionId: string) => void;
    selectStep: (stepId: string | null) => void;
    selectTransition: (transitionId: string | null) => void;
    setIsDirty: (isDirty: boolean) => void;
    setIsLoading: (isLoading: boolean) => void;
    setError: (error: string | null) => void;
    reset: () => void;
}

const initialState: WorkflowBuilderState = {
    workflow: null,
    steps: [],
    transitions: [],
    selectedStepId: null,
    selectedTransitionId: null,
    isDirty: false,
    isLoading: false,
    error: null,
};

const getStepLabel = (type: StepType): string => {
    const labels: Record<StepType, string> = {
        start: 'Début',
        end: 'Fin',
        task: 'Tâche',
        approval: 'Approbation',
        form: 'Formulaire',
        condition: 'Condition',
        parallel: 'Parallèle',
        notification: 'Notification',
        delay: 'Délai',
        script: 'Script',
        sub_workflow: 'Sous-workflow',
    };
    return labels[type] || type;
};

export const useWorkflowStore = create<WorkflowBuilderState & WorkflowBuilderActions>()(
    immer((set, get) => ({
        ...initialState,

        setWorkflow: (workflow) =>
            set((state) => {
                state.workflow = workflow;
                state.steps = workflow.steps || [];
                state.transitions = workflow.transitions || [];
            }),

        setSteps: (steps) =>
            set((state) => {
                state.steps = steps;
            }),

        setTransitions: (transitions) =>
            set((state) => {
                state.transitions = transitions;
            }),

        addStep: (type, position) => {
            const currentSteps = get().steps || [];
            const newStep: WorkflowStep = {
                id: 0,
                uuid: crypto.randomUUID ? crypto.randomUUID() : `step-${Date.now()}`,
                workflow_id: get().workflow?.id || 0,
                name: getStepLabel(type),
                type,
                order: currentSteps.length,
                position_x: position.x,
                position_y: position.y,
                is_start: type === 'start',
                is_end: type === 'end',
            };

            set((state) => {
                if (!state.steps) state.steps = [];
                state.steps.push(newStep);
                state.isDirty = true;
            });

            return newStep;
        },

        updateStep: (stepId, data) =>
            set((state) => {
                if (!state.steps) state.steps = [];
                const index = state.steps.findIndex((s) => s.uuid === stepId);
                if (index !== -1) {
                    state.steps[index] = { ...state.steps[index], ...data };
                    state.isDirty = true;
                }
            }),

        removeStep: (stepId) =>
            set((state) => {
                if (!state.steps) state.steps = [];
                if (!state.transitions) state.transitions = [];
                state.steps = state.steps.filter((s) => s.uuid !== stepId);
                // Also remove connected transitions
                state.transitions = state.transitions.filter(
                    (t) => {
                        const fromStep = state.steps.find(s => s.id === t.from_step_id);
                        const toStep = state.steps.find(s => s.id === t.to_step_id);
                        return fromStep?.uuid !== stepId && toStep?.uuid !== stepId;
                    }
                );
                state.isDirty = true;
                if (state.selectedStepId === stepId) {
                    state.selectedStepId = null;
                }
            }),

        addTransition: (fromStepUuid, toStepUuid) => {
            const currentSteps = get().steps || [];
            const currentTransitions = get().transitions || [];

            const fromStep = currentSteps.find((s) => s.uuid === fromStepUuid);
            const toStep = currentSteps.find((s) => s.uuid === toStepUuid);

            if (!fromStep || !toStep) return null;

            // Check if transition already exists
            const exists = currentTransitions.some(
                (t) => {
                    const existingFromStep = currentSteps.find(s => s.id === t.from_step_id);
                    const existingToStep = currentSteps.find(s => s.id === t.to_step_id);
                    return existingFromStep?.uuid === fromStepUuid && existingToStep?.uuid === toStepUuid;
                }
            );

            if (exists) return null;

            const newTransition: WorkflowTransition = {
                id: 0,
                uuid: crypto.randomUUID ? crypto.randomUUID() : `trans-${Date.now()}`,
                workflow_id: get().workflow?.id || 0,
                from_step_id: fromStep.id,
                to_step_id: toStep.id,
                condition_type: 'always',
                priority: 0,
                is_default: false,
            };

            set((state) => {
                if (!state.transitions) state.transitions = [];
                state.transitions.push(newTransition);
                state.isDirty = true;
            });

            return newTransition;
        },

        updateTransition: (transitionId, data) =>
            set((state) => {
                if (!state.transitions) state.transitions = [];
                const index = state.transitions.findIndex((t) => t.uuid === transitionId);
                if (index !== -1) {
                    state.transitions[index] = { ...state.transitions[index], ...data };
                    state.isDirty = true;
                }
            }),

        removeTransition: (transitionId) =>
            set((state) => {
                if (!state.transitions) state.transitions = [];
                state.transitions = state.transitions.filter((t) => t.uuid !== transitionId);
                state.isDirty = true;
                if (state.selectedTransitionId === transitionId) {
                    state.selectedTransitionId = null;
                }
            }),

        selectStep: (stepId) =>
            set((state) => {
                state.selectedStepId = stepId;
                state.selectedTransitionId = null;
            }),

        selectTransition: (transitionId) =>
            set((state) => {
                state.selectedTransitionId = transitionId;
                state.selectedStepId = null;
            }),

        setIsDirty: (isDirty) =>
            set((state) => {
                state.isDirty = isDirty;
            }),

        setIsLoading: (isLoading) =>
            set((state) => {
                state.isLoading = isLoading;
            }),

        setError: (error) =>
            set((state) => {
                state.error = error;
            }),

        reset: () => set(initialState),
    }))
);
