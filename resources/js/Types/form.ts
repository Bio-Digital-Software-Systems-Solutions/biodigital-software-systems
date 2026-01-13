// Form Types

export type FormStatus = 'draft' | 'published' | 'archived';
export type SubmissionStatus = 'draft' | 'submitted' | 'processing' | 'completed' | 'rejected';

export type FormFieldType =
    // Text inputs
    | 'text'
    | 'textarea'
    | 'rich_text'
    | 'number'
    | 'email'
    | 'phone'
    | 'url'
    | 'password'
    // Selection
    | 'select'
    | 'multi_select'
    | 'radio'
    | 'checkbox'
    | 'checkbox_group'
    | 'toggle'
    // Date & Time
    | 'date'
    | 'time'
    | 'datetime'
    | 'date_range'
    // File
    | 'file'
    | 'image'
    | 'signature'
    // Layout
    | 'section'
    | 'group'
    | 'repeater'
    | 'columns'
    | 'tabs'
    | 'accordion'
    // Special
    | 'hidden'
    | 'computed'
    | 'lookup'
    | 'rating'
    | 'slider'
    | 'color'
    | 'tags'
    | 'location'
    | 'user_select'
    | 'department_select';

export type FieldCategory = 'text' | 'selection' | 'datetime' | 'file' | 'layout' | 'special';

export interface FieldOption {
    label: string;
    value: string;
    disabled?: boolean;
    color?: string;
    icon?: string;
}

export interface ValidationRule {
    rule: string;
    value?: any;
    message?: string;
}

export interface ConditionalLogic {
    conditions: FieldCondition[];
    logic: 'and' | 'or';
    action: 'show' | 'hide' | 'enable' | 'disable' | 'require';
}

export interface FieldCondition {
    field: string;
    operator:
        | 'equals'
        | 'not_equals'
        | 'contains'
        | 'not_contains'
        | 'starts_with'
        | 'ends_with'
        | 'greater_than'
        | 'less_than'
        | 'greater_or_equal'
        | 'less_or_equal'
        | 'is_empty'
        | 'is_not_empty'
        | 'in'
        | 'not_in';
    value?: any;
}

export interface FormField {
    id: number;
    uuid: string;
    form_id: number;
    parent_field_id?: number;
    name: string;
    label: string;
    type: FormFieldType;
    order: number;
    step?: number;
    placeholder?: string;
    helper_text?: string;
    description?: string;
    default_value?: any;
    options?: FieldOption[];
    validation?: Record<string, any>;
    conditional_logic?: ConditionalLogic;
    settings?: Record<string, any>;
    is_required: boolean;
    is_readonly: boolean;
    is_hidden: boolean;
    width?: 'full' | 'half' | 'third' | 'quarter';
    children?: FormField[];
    value?: any;
}

export interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
}

export interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    full_name: string;
}

export interface DepartmentForm {
    id: number;
    uuid: string;
    department_id: number;
    created_by: number;
    name: string;
    description?: string;
    status: FormStatus;
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
    deleted_at?: string;
    department?: Department;
    creator?: User;
    fields?: FormField[];
    fields_count?: number;
    submissions_count?: number;
}

export interface DepartmentFormSubmission {
    id: number;
    uuid: string;
    form_id: number;
    user_id: number;
    workflow_instance_id?: number;
    step_instance_id?: number;
    status: SubmissionStatus;
    data?: Record<string, any>;
    current_step?: number;
    metadata?: Record<string, any>;
    ip_address?: string;
    user_agent?: string;
    submitted_at?: string;
    processed_at?: string;
    created_at: string;
    updated_at: string;
    form?: DepartmentForm;
    user?: User;
}

// Field Type Configuration
export interface FieldTypeConfig {
    value: FormFieldType;
    label: string;
    description?: string;
    icon: string;
    category: FieldCategory;
    isInput: boolean;
    hasOptions: boolean;
    defaultValidation: string[];
}

// Field type options grouped by category
export interface FieldTypeGroup {
    category: FieldCategory;
    label: string;
    fields: FieldTypeConfig[];
}

// Form Builder State
export interface FormBuilderState {
    form: DepartmentForm | null;
    fields: FormField[];
    selectedFieldId: string | null;
    isDirty: boolean;
    dragOverFieldId: string | null;
}

// Form Renderer State
export interface FormRendererState {
    form: DepartmentForm;
    fields: FormField[];
    values: Record<string, any>;
    errors: Record<string, string[]>;
    currentStep: number;
    isSubmitting: boolean;
}

// Validation result
export interface ValidationResult {
    valid: boolean;
    errors: Record<string, string[]>;
}
