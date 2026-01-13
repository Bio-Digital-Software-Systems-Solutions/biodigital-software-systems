import { describe, it, expect, beforeEach, vi } from 'vitest';
import { act } from '@testing-library/react';
import { useWorkflowStore } from '../workflowStore';
import type { DepartmentWorkflow, WorkflowStep, WorkflowTransition, StepType } from '@/Types/workflow';

// Mock crypto.randomUUID
const mockUUID = vi.fn();
let uuidCounter = 0;
vi.stubGlobal('crypto', {
    randomUUID: () => {
        uuidCounter++;
        return `test-uuid-${uuidCounter}`;
    },
});

describe('workflowStore', () => {
    beforeEach(() => {
        // Reset store before each test
        useWorkflowStore.getState().reset();
        uuidCounter = 0;
    });

    describe('addStep', () => {
        it('should add a step with a unique UUID', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 100, y: 200 });

            expect(step).toBeDefined();
            expect(step.uuid).toBe('test-uuid-1');
            expect(step.type).toBe('task');
            expect(step.position_x).toBe(100);
            expect(step.position_y).toBe(200);
            expect(step.id).toBe(0); // New step has id 0
        });

        it('should add multiple steps with unique UUIDs', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });
            const step3 = store.addStep('end', { x: 200, y: 200 });

            expect(step1.uuid).toBe('test-uuid-1');
            expect(step2.uuid).toBe('test-uuid-2');
            expect(step3.uuid).toBe('test-uuid-3');

            const state = useWorkflowStore.getState();
            expect(state.steps).toHaveLength(3);
        });

        it('should mark step as start when type is start', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('start', { x: 0, y: 0 });

            expect(step.is_start).toBe(true);
            expect(step.is_end).toBe(false);
        });

        it('should mark step as end when type is end', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('end', { x: 0, y: 0 });

            expect(step.is_start).toBe(false);
            expect(step.is_end).toBe(true);
        });
    });

    describe('addTransition', () => {
        it('should create a transition between two steps using UUIDs', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });

            const transition = store.addTransition(step1.uuid, step2.uuid);

            expect(transition).toBeDefined();
            expect(transition!.from_step_uuid).toBe(step1.uuid);
            expect(transition!.to_step_uuid).toBe(step2.uuid);
            expect(transition!.from_step_id).toBe(0); // Both steps have id 0
            expect(transition!.to_step_id).toBe(0);
        });

        it('should not create duplicate transitions', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });

            const transition1 = store.addTransition(step1.uuid, step2.uuid);
            const transition2 = store.addTransition(step1.uuid, step2.uuid);

            expect(transition1).toBeDefined();
            expect(transition2).toBeNull(); // Duplicate should return null

            const state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(1);
        });

        it('should allow transitions to different targets from same source', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });
            const step3 = store.addStep('task', { x: 200, y: 100 });

            const transition1 = store.addTransition(step1.uuid, step2.uuid);
            const transition2 = store.addTransition(step1.uuid, step3.uuid);

            expect(transition1).toBeDefined();
            expect(transition2).toBeDefined();

            const state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(2);
        });

        it('should return null when source step does not exist', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 100, y: 100 });

            const transition = store.addTransition('non-existent-uuid', step.uuid);

            expect(transition).toBeNull();
        });

        it('should return null when target step does not exist', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 100, y: 100 });

            const transition = store.addTransition(step.uuid, 'non-existent-uuid');

            expect(transition).toBeNull();
        });
    });

    describe('removeStep', () => {
        it('should remove a step by UUID', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });

            store.removeStep(step1.uuid);

            const state = useWorkflowStore.getState();
            expect(state.steps).toHaveLength(1);
            expect(state.steps[0].uuid).toBe(step2.uuid);
        });

        it('should remove connected transitions when step is removed', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });
            const step3 = store.addStep('end', { x: 200, y: 200 });

            store.addTransition(step1.uuid, step2.uuid);
            store.addTransition(step2.uuid, step3.uuid);

            let state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(2);

            // Remove middle step
            store.removeStep(step2.uuid);

            state = useWorkflowStore.getState();
            expect(state.steps).toHaveLength(2);
            expect(state.transitions).toHaveLength(0); // Both transitions should be removed
        });

        it('should only remove transitions connected to the removed step', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });
            const step3 = store.addStep('task', { x: 200, y: 100 });
            const step4 = store.addStep('end', { x: 300, y: 200 });

            // Create path: step1 -> step2 -> step4
            //              step1 -> step3 -> step4
            store.addTransition(step1.uuid, step2.uuid);
            store.addTransition(step1.uuid, step3.uuid);
            store.addTransition(step2.uuid, step4.uuid);
            store.addTransition(step3.uuid, step4.uuid);

            let state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(4);

            // Remove step2
            store.removeStep(step2.uuid);

            state = useWorkflowStore.getState();
            expect(state.steps).toHaveLength(3);
            expect(state.transitions).toHaveLength(2); // Only step1->step3 and step3->step4 remain
        });
    });

    describe('removeTransition', () => {
        it('should remove a transition by UUID', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 100 });

            const transition = store.addTransition(step1.uuid, step2.uuid);

            let state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(1);

            store.removeTransition(transition!.uuid);

            state = useWorkflowStore.getState();
            expect(state.transitions).toHaveLength(0);
        });
    });

    describe('updateStep', () => {
        it('should update step properties by UUID', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 100, y: 200 });

            store.updateStep(step.uuid, {
                name: 'Updated Task',
                position_x: 150,
                position_y: 250,
            });

            const state = useWorkflowStore.getState();
            const updatedStep = state.steps.find(s => s.uuid === step.uuid);

            expect(updatedStep?.name).toBe('Updated Task');
            expect(updatedStep?.position_x).toBe(150);
            expect(updatedStep?.position_y).toBe(250);
        });
    });

    describe('complex workflow scenarios', () => {
        it('should handle a complete workflow with multiple steps and transitions', () => {
            const store = useWorkflowStore.getState();

            // Create a workflow: Start -> Task1 -> Approval -> End
            const startStep = store.addStep('start', { x: 0, y: 100 });
            const taskStep = store.addStep('task', { x: 200, y: 100 });
            const approvalStep = store.addStep('approval', { x: 400, y: 100 });
            const endStep = store.addStep('end', { x: 600, y: 100 });

            store.addTransition(startStep.uuid, taskStep.uuid);
            store.addTransition(taskStep.uuid, approvalStep.uuid);
            store.addTransition(approvalStep.uuid, endStep.uuid);

            const state = useWorkflowStore.getState();

            expect(state.steps).toHaveLength(4);
            expect(state.transitions).toHaveLength(3);

            // Verify all transitions use UUIDs correctly
            state.transitions.forEach(t => {
                expect(t.from_step_uuid).toBeDefined();
                expect(t.to_step_uuid).toBeDefined();
                // The corresponding steps should exist
                expect(state.steps.find(s => s.uuid === t.from_step_uuid)).toBeDefined();
                expect(state.steps.find(s => s.uuid === t.to_step_uuid)).toBeDefined();
            });
        });

        it('should handle branching workflow (condition step)', () => {
            const store = useWorkflowStore.getState();

            // Create: Start -> Condition -> (yes) End1
            //                            -> (no) End2
            const startStep = store.addStep('start', { x: 0, y: 100 });
            const conditionStep = store.addStep('condition', { x: 200, y: 100 });
            const end1Step = store.addStep('end', { x: 400, y: 0 });
            const end2Step = store.addStep('end', { x: 400, y: 200 });

            store.addTransition(startStep.uuid, conditionStep.uuid);
            store.addTransition(conditionStep.uuid, end1Step.uuid);
            store.addTransition(conditionStep.uuid, end2Step.uuid);

            const state = useWorkflowStore.getState();

            expect(state.steps).toHaveLength(4);
            expect(state.transitions).toHaveLength(3);

            // Two transitions should originate from condition step
            const conditionTransitions = state.transitions.filter(
                t => t.from_step_uuid === conditionStep.uuid
            );
            expect(conditionTransitions).toHaveLength(2);
        });

        it('should correctly identify transitions even when all steps have id=0', () => {
            const store = useWorkflowStore.getState();

            // This is the core bug we're fixing
            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('task', { x: 100, y: 0 });
            const step3 = store.addStep('task', { x: 200, y: 0 });
            const step4 = store.addStep('end', { x: 300, y: 0 });

            // All steps have id=0
            expect(step1.id).toBe(0);
            expect(step2.id).toBe(0);
            expect(step3.id).toBe(0);
            expect(step4.id).toBe(0);

            // But they have unique UUIDs
            expect(step1.uuid).not.toBe(step2.uuid);
            expect(step2.uuid).not.toBe(step3.uuid);
            expect(step3.uuid).not.toBe(step4.uuid);

            // Create transitions
            const t1 = store.addTransition(step1.uuid, step2.uuid);
            const t2 = store.addTransition(step2.uuid, step3.uuid);
            const t3 = store.addTransition(step3.uuid, step4.uuid);

            // All transitions should be created successfully
            expect(t1).not.toBeNull();
            expect(t2).not.toBeNull();
            expect(t3).not.toBeNull();

            // Transitions should reference the correct steps via UUID
            expect(t1!.from_step_uuid).toBe(step1.uuid);
            expect(t1!.to_step_uuid).toBe(step2.uuid);

            expect(t2!.from_step_uuid).toBe(step2.uuid);
            expect(t2!.to_step_uuid).toBe(step3.uuid);

            expect(t3!.from_step_uuid).toBe(step3.uuid);
            expect(t3!.to_step_uuid).toBe(step4.uuid);
        });
    });

    describe('isDirty flag', () => {
        it('should set isDirty when adding step', () => {
            const store = useWorkflowStore.getState();
            expect(store.isDirty).toBe(false);

            store.addStep('task', { x: 0, y: 0 });

            expect(useWorkflowStore.getState().isDirty).toBe(true);
        });

        it('should set isDirty when adding transition', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('end', { x: 100, y: 0 });

            store.setIsDirty(false);
            expect(useWorkflowStore.getState().isDirty).toBe(false);

            store.addTransition(step1.uuid, step2.uuid);

            expect(useWorkflowStore.getState().isDirty).toBe(true);
        });

        it('should set isDirty when removing step', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 0, y: 0 });
            store.setIsDirty(false);

            store.removeStep(step.uuid);

            expect(useWorkflowStore.getState().isDirty).toBe(true);
        });
    });

    describe('selection', () => {
        it('should select and deselect steps', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 0, y: 0 });

            store.selectStep(step.uuid);
            expect(useWorkflowStore.getState().selectedStepId).toBe(step.uuid);

            store.selectStep(null);
            expect(useWorkflowStore.getState().selectedStepId).toBeNull();
        });

        it('should clear step selection when selecting transition', () => {
            const store = useWorkflowStore.getState();

            const step1 = store.addStep('start', { x: 0, y: 0 });
            const step2 = store.addStep('end', { x: 100, y: 0 });
            const transition = store.addTransition(step1.uuid, step2.uuid);

            store.selectStep(step1.uuid);
            expect(useWorkflowStore.getState().selectedStepId).toBe(step1.uuid);

            store.selectTransition(transition!.uuid);
            expect(useWorkflowStore.getState().selectedStepId).toBeNull();
            expect(useWorkflowStore.getState().selectedTransitionId).toBe(transition!.uuid);
        });

        it('should clear selection when selected step is removed', () => {
            const store = useWorkflowStore.getState();

            const step = store.addStep('task', { x: 0, y: 0 });
            store.selectStep(step.uuid);

            expect(useWorkflowStore.getState().selectedStepId).toBe(step.uuid);

            store.removeStep(step.uuid);

            expect(useWorkflowStore.getState().selectedStepId).toBeNull();
        });
    });
});
