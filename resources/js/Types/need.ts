// Need Types

export type NeedStatus =
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'approved'
    | 'rejected'
    | 'ordered'
    | 'in_progress'
    | 'delivered'
    | 'completed'
    | 'cancelled';

export type NeedCategory =
    | 'equipment'
    | 'software'
    | 'furniture'
    | 'supplies'
    | 'services'
    | 'training'
    | 'recruitment'
    | 'other';

export type NeedPriority = 'critical' | 'high' | 'medium' | 'low';

export type AttachmentType = 'document' | 'quote' | 'invoice' | 'receipt' | 'image' | 'other';

export interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
    head_of_department?: number;
    budget?: number;
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

export interface DepartmentNeed {
    id: number;
    uuid: string;
    reference?: string;
    department_id: number;
    created_by_id: number;
    assigned_to_id?: number;
    title: string;
    description?: string;
    justification?: string;
    category: NeedCategory;
    priority: NeedPriority;
    status: NeedStatus;
    estimated_cost?: number;
    approved_budget?: number;
    actual_cost?: number;
    currency: string;
    quantity: number;
    unit?: string;
    specifications?: Record<string, any>;
    vendor_info?: VendorInfo;
    approved_by?: number;
    rejected_by?: number;
    rejection_reason?: string;
    workflow_instance_id?: number;
    form_submission_id?: number;
    needed_by_date?: string;
    expected_delivery?: string;
    actual_delivery?: string;
    submitted_at?: string;
    approved_at?: string;
    rejected_at?: string;
    ordered_at?: string;
    delivered_at?: string;
    completed_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    department?: Department;
    created_by?: User;
    assigned_to?: User;
    approver?: User;
    rejecter?: User;
    attachments?: NeedAttachment[];
    comments?: NeedComment[];
    status_history?: NeedStatusHistory[];
    attachments_count?: number;
    comments_count?: number;
}

export interface VendorInfo {
    name?: string;
    contact?: string;
    email?: string;
    phone?: string;
    website?: string;
    notes?: string;
}

export interface NeedAttachment {
    id: number;
    uuid: string;
    need_id: number;
    uploaded_by: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    size: number;
    path: string;
    disk: string;
    type: AttachmentType;
    description?: string;
    created_at: string;
    updated_at: string;
    uploader?: User;
    url?: string;
    formatted_size?: string;
}

export interface NeedComment {
    id: number;
    uuid: string;
    need_id: number;
    user_id: number;
    content: string;
    is_internal: boolean;
    parent_id?: number;
    mentions?: number[];
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    user?: User;
    replies?: NeedComment[];
}

export interface NeedStatusHistory {
    id: number;
    uuid?: string;
    need_id?: number;
    user_id?: number;
    from_status: NeedStatus | null;
    to_status: NeedStatus;
    comment?: string;
    reason?: string;
    metadata?: Record<string, any>;
    created_at: string;
    user?: User;
}

// Kanban Types
export interface KanbanColumn {
    id: string;
    title: string;
    items: DepartmentNeed[];
}

export interface KanbanState {
    columns: KanbanColumn[];
    departmentId: number;
}

// Stats Types
export interface DepartmentNeedStats {
    total_needs: number;
    pending_needs: number;
    approved_needs: number;
    completed_needs: number;
    rejected_needs: number;
    total_budget: number;
    spent_budget: number;
    remaining_budget: number;
    overdue_needs: number;
}

export interface UserNeedStats {
    requested: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    assigned: {
        total: number;
        active: number;
    };
}

// Filter Types
export interface NeedFilters {
    department_id?: number;
    status?: NeedStatus;
    category?: NeedCategory;
    priority?: NeedPriority;
    requester_id?: number;
    assigned_to?: number;
    search?: string;
    date_from?: string;
    date_to?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    per_page?: number;
}

// Status Configuration
export interface StatusConfig {
    value: NeedStatus;
    label: string;
    color: string;
    icon: string;
    kanbanColumn: string;
    allowedTransitions: NeedStatus[];
}

// Category Configuration
export interface CategoryConfig {
    value: NeedCategory;
    label: string;
    icon: string;
    color: string;
}

// Priority Configuration
export interface PriorityConfig {
    value: NeedPriority;
    label: string;
    color: string;
    icon: string;
    sortOrder: number;
}

// Select Option Type
export interface SelectOption {
    value: string;
    label: string;
    color?: string;
    icon?: string;
}
