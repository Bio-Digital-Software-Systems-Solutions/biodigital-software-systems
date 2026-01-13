// Workflow Types
import type { FormField } from './form';

export type WorkflowStatus = 'draft' | 'active' | 'deprecated';
export type WorkflowTriggerType = 'manual' | 'event' | 'scheduled' | 'form_submission' | 'webhook' | 'api';
export type WorkflowScope = 'department' | 'enterprise';

export type StepType =
    | 'start'
    | 'end'
    | 'approval'
    | 'condition'
    | 'action'
    | 'wait'
    | 'notification'
    | 'form'
    | 'subprocess'
    | 'parallel_split'
    | 'parallel_join';

export type StepInstanceStatus =
    | 'pending'
    | 'active'
    | 'completed'
    | 'skipped'
    | 'failed'
    | 'cancelled'
    | 'waiting';

export type WorkflowInstanceStatus =
    | 'pending'
    | 'active'
    | 'paused'
    | 'completed'
    | 'cancelled'
    | 'failed';

export type ApprovalType = 'any' | 'all' | 'majority' | 'sequential';

export type ApprovalDecision =
    | 'approved'
    | 'rejected'
    | 'abstained'
    | 'delegated'
    | 'requested_changes';

export type TimeoutAction =
    | 'escalate'
    | 'skip'
    | 'fail'
    | 'auto_approve'
    | 'auto_reject'
    | 'notify'
    | 'reassign';

export type TransitionConditionType =
    | 'always'
    | 'expression'
    | 'approval_result'
    | 'form_field'
    | 'variable';

export interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
}

export interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    avatar?: string;
    full_name: string;
}

export interface Approver {
    type: 'user' | 'role';
    id?: number;
    name?: string;
}

export interface WorkflowStep {
    id: number;
    uuid: string;
    workflow_id: number;
    form_id?: number;
    name: string;
    description?: string;
    type: StepType;
    order: number;
    config?: Record<string, any>;
    position_x: number;
    position_y: number;
    is_start: boolean;
    is_end: boolean;
    approval_type?: ApprovalType;
    approvers?: Approver[];
    min_approvals?: number;
    timeout_hours?: number;
    timeout_action?: TimeoutAction;
    escalation_user_id?: number;
    retry_count?: number;
    retry_delay_minutes?: number;
    conditions?: Record<string, any>;
    metadata?: Record<string, any>;
    form?: DepartmentForm;
}

export interface WorkflowTransition {
    id: number;
    uuid: string;
    workflow_id: number;
    from_step_id: number;
    to_step_id: number;
    from_step_uuid?: string;  // Used for frontend connections before saving
    to_step_uuid?: string;    // Used for frontend connections before saving
    name?: string;
    condition_type: TransitionConditionType;
    condition_config?: Record<string, any>;
    priority: number;
    is_default: boolean;
    metadata?: Record<string, any>;
}

export interface DepartmentWorkflow {
    id: number;
    uuid: string;
    department_id: number;
    created_by: number;
    name: string;
    description?: string;
    status: WorkflowStatus;
    trigger_type: WorkflowTriggerType;
    scope: WorkflowScope;
    trigger_config?: Record<string, any>;
    variables?: Record<string, any>;
    settings?: Record<string, any>;
    version: number;
    is_template: boolean;
    parent_workflow_id?: number;
    activated_at?: string;
    deprecated_at?: string;
    created_at: string;
    updated_at: string;
    department?: Department;
    creator?: User;
    steps?: WorkflowStep[];
    transitions?: WorkflowTransition[];
    steps_count?: number;
    instances_count?: number;
}

export interface WorkflowInstance {
    id: number;
    uuid: string;
    workflow_id: number;
    department_id: number;
    started_by: number;
    name?: string;
    status: WorkflowInstanceStatus;
    context?: Record<string, any>;
    input_data?: Record<string, any>;
    output_data?: Record<string, any>;
    cancellation_reason?: string;
    failure_reason?: string;
    parent_instance_id?: number;
    parent_step_instance_id?: number;
    started_at?: string;
    completed_at?: string;
    cancelled_at?: string;
    failed_at?: string;
    created_at: string;
    updated_at: string;
    workflow?: DepartmentWorkflow;
    department?: Department;
    starter?: User;
    step_instances?: WorkflowStepInstance[];
    step_instances_count?: number;
}

export interface WorkflowStepInstance {
    id: number;
    uuid: string;
    workflow_instance_id: number;
    workflow_step_id: number;
    status: StepInstanceStatus;
    input_data?: Record<string, any>;
    output_data?: Record<string, any>;
    context?: Record<string, any>;
    attempt_count: number;
    max_attempts: number;
    error_message?: string;
    error_details?: Record<string, any>;
    assigned_to?: number;
    completed_by?: number;
    started_at?: string;
    completed_at?: string;
    due_at?: string;
    escalated_at?: string;
    escalated_to?: number;
    created_at: string;
    updated_at: string;
    step?: WorkflowStep;
    assigned_user?: User;
    completed_by_user?: User;
    approvals?: StepApproval[];
}

export interface StepApproval {
    id: number;
    uuid: string;
    step_instance_id: number;
    approver_id: number;
    decision?: ApprovalDecision;
    comments?: string;
    requested_changes?: Record<string, any>;
    delegated_to?: number;
    delegation_reason?: string;
    order: number;
    is_required: boolean;
    notified_at?: string;
    decided_at?: string;
    due_at?: string;
    created_at: string;
    updated_at: string;
    approver?: User;
    delegated_user?: User;
    step_instance?: WorkflowStepInstance;
}

export interface WorkflowActivityLog {
    id: number;
    workflow_instance_id?: number;
    step_instance_id?: number;
    user_id?: number;
    action: string;
    entity_type: string;
    entity_id: number;
    old_values?: Record<string, any>;
    new_values?: Record<string, any>;
    metadata?: Record<string, any>;
    ip_address?: string;
    user_agent?: string;
    created_at: string;
    user?: User;
}

// React Flow Types
export interface WorkflowNodeData {
    label: string;
    type: StepType;
    description?: string;
    config?: Record<string, any>;
    isStart?: boolean;
    isEnd?: boolean;
    formId?: number;
    approvalType?: ApprovalType;
    approvers?: Approver[];
    timeoutHours?: number;
    timeoutAction?: TimeoutAction;
}

export interface WorkflowEdgeData {
    label?: string;
    conditionType: TransitionConditionType;
    conditionConfig?: Record<string, any>;
    isDefault?: boolean;
    priority?: number;
}

// Step Type Configuration
export interface StepTypeConfig {
    value: StepType;
    label: string;
    description: string;
    icon: string;
    color: string;
    category: 'flow' | 'action' | 'logic' | 'integration';
    requiresConfig: boolean;
}

// Form Types (for form steps)
export interface DepartmentForm {
    id: number;
    uuid: string;
    department_id: number;
    created_by: number;
    name: string;
    description?: string;
    status: 'draft' | 'published' | 'archived';
    is_multi_step: boolean;
    settings?: Record<string, any>;
    validation_rules?: Record<string, any>;
    conditional_logic?: Record<string, any>;
    success_message?: string;
    redirect_url?: string;
    is_template: boolean;
    parent_form_id?: number;
    version: number;
    published_at?: string;
    created_at: string;
    updated_at: string;
    department?: Department;
    creator?: User;
    fields?: FormField[];
    fields_count?: number;
    submissions_count?: number;
}
