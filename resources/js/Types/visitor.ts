export interface Visitor {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    name: string;
    email: string | null;
    phone: string | null;
    photo: string | null;
    source: string | null;
    status: string;
}

export interface VisitorVisit {
    uuid: string;
    visitor: Visitor;
    first_visited_at: string;
    integration_score: number;
    integration_status: 'visiting' | 'progressing' | 'ready' | 'integrated';
    attendance_count: number;
    present_count: number;
    invited_by: { id: number; name: string } | null;
    notes: string | null;
    has_pending_suggestion: boolean;
}

export interface VisitorAttendance {
    date: string;
    status: 'present' | 'absent' | 'excused' | 'late';
    type: string;
}

export interface IntegrationStepProgress {
    step_name: string;
    step_type: string;
    progress: number;
    status: string;
}

export interface IntegrationSuggestion {
    uuid: string;
    visitor_name: string;
    visitor_uuid: string;
    group_or_department: string;
    visitable_type: string;
    score: number;
    status: string;
    created_at: string;
}

export interface VisitorDashboardStats {
    total_visitors: number;
    visiting: number;
    progressing: number;
    ready: number;
    integrated: number;
    average_score: number;
}

export interface IntegrationPathwayTemplate {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    target_type: string | null;
    is_default: boolean;
    is_active: boolean;
    steps: IntegrationPathwayStep[];
    steps_count?: number;
    creator?: { id: number; name: string };
}

export interface IntegrationPathwayStep {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    order_index: number;
    type: string;
    criteria: Record<string, number> | null;
    weight: number;
    is_required: boolean;
}
