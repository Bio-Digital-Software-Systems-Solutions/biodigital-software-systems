// Report Types

export type ReportStatus =
    | 'draft'
    | 'pending_review'
    | 'under_review'
    | 'revision_requested'
    | 'approved'
    | 'rejected'
    | 'published'
    | 'archived';

export type ReportType =
    | 'monthly_activity'
    | 'monthly_objectives'
    | 'quarterly_review'
    | 'annual_summary'
    | 'project_status'
    | 'budget_report'
    | 'kpi_dashboard'
    | 'custom';

export type ReportSectionType =
    | 'text'
    | 'metrics'
    | 'chart'
    | 'table'
    | 'checklist'
    | 'list'
    | 'budget'
    | 'timeline'
    | 'gallery'
    | 'custom';

export type ReportPeriodType =
    | 'weekly'
    | 'monthly'
    | 'quarterly'
    | 'annual'
    | 'custom';

export type ObjectiveStatus =
    | 'not_started'
    | 'in_progress'
    | 'at_risk'
    | 'on_hold'
    | 'completed'
    | 'cancelled';

export type ActivityCategory =
    | 'meeting'
    | 'training'
    | 'event'
    | 'project_work'
    | 'administrative'
    | 'communication'
    | 'planning'
    | 'other';

export type ApprovalStatus = 'pending' | 'approved' | 'rejected';

export type CommentType = 'comment' | 'suggestion' | 'question' | 'issue';

export type ReminderType = 'submission' | 'review' | 'deadline' | 'follow_up';

export type TrendDirection = 'higher_is_better' | 'lower_is_better' | 'target_is_best';

// Base interfaces
export interface Department {
    id: number;
    uuid: string;
    name: string;
    code?: string;
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

// Report Template
export interface ReportTemplate {
    id: number;
    uuid: string;
    department_id?: number;
    name: string;
    description?: string;
    type: ReportType;
    period_type: ReportPeriodType;
    sections_config?: SectionConfig[];
    default_approvers?: number[];
    is_active: boolean;
    auto_generate: boolean;
    auto_generate_day?: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    department?: Department;
    type_label?: string;
    period_type_label?: string;
}

export interface SectionConfig {
    type: ReportSectionType;
    title?: string;
    description?: string;
    is_required?: boolean;
    config?: Record<string, any>;
}

// Department Report
export interface DepartmentReport {
    id: number;
    uuid: string;
    department_id: number;
    template_id?: number;
    author_id: number;
    approver_id?: number;
    title: string;
    type: ReportType;
    status: ReportStatus;
    period_type: ReportPeriodType;
    period_start: string;
    period_end: string;
    executive_summary?: string;
    submission_notes?: string;
    approval_notes?: string;
    rejection_reason?: string;
    submitted_at?: string;
    approved_at?: string;
    published_at?: string;
    version: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    period_label?: string;
    progress?: number;
    can_edit?: boolean;
    can_submit?: boolean;
    status_label?: string;
    status_color?: string;
    status_icon?: string;

    // Relations
    department?: Department;
    template?: ReportTemplate;
    author?: User;
    approver?: User;
    sections?: ReportSection[];
    approvals?: ReportApproval[];
    comments?: ReportComment[];
    versions?: ReportVersion[];
    attachments?: ReportAttachment[];
    tags?: ReportTag[];
    kpi_values?: DepartmentKpiValue[];

    // Counts
    sections_count?: number;
    comments_count?: number;
    attachments_count?: number;
}

// Report Section
export interface ReportSection {
    id: number;
    uuid: string;
    report_id: number;
    type: ReportSectionType;
    title: string;
    description?: string;
    content?: SectionContent;
    order: number;
    is_required: boolean;
    is_visible: boolean;
    config?: Record<string, any>;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;

    // Computed properties
    is_complete?: boolean;
    type_label?: string;
    type_icon?: string;

    // Relations
    comments?: ReportComment[];
    attachments?: ReportAttachment[];
}

export interface SectionContent {
    text?: string;
    metrics?: MetricItem[];
    data?: any[];
    labels?: string[];
    datasets?: ChartDataset[];
    headers?: string[];
    rows?: any[][];
    items?: ChecklistItem[] | ListItem[];
    total?: number;
    spent?: number;
    remaining?: number;
    events?: TimelineEvent[];
    images?: string[];
    [key: string]: any;
}

export interface MetricItem {
    label: string;
    value: number | string;
    unit?: string;
    trend?: TrendData;
    total?: number;
}

export interface ChartDataset {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
}

export interface ChecklistItem {
    label: string;
    completed: boolean;
    progress?: number;
}

export interface ListItem {
    title: string;
    subtitle?: string;
    metadata?: string;
}

export interface TimelineEvent {
    date: string;
    title: string;
    description?: string;
}

// Department Activity
export interface DepartmentActivity {
    id: number;
    uuid: string;
    department_id: number;
    user_id: number;
    category: ActivityCategory;
    title: string;
    description?: string;
    date: string;
    duration_hours?: number;
    participants?: number[];
    outcomes?: string;
    metrics?: Record<string, any>;
    related_project_id?: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    category_label?: string;
    category_icon?: string;
    category_color?: string;
    participant_count?: number;

    // Relations
    department?: Department;
    user?: User;
    related_project?: Project;
}

export interface Project {
    id: number;
    uuid?: string;
    name: string;
    status?: string;
    progress?: number;
    start_date?: string;
    end_date?: string;
    manager?: User;
    tasks_total?: number;
    tasks_completed?: number;
}

// Department Objective
export interface DepartmentObjective {
    id: number;
    uuid: string;
    department_id: number;
    parent_id?: number;
    assigned_to?: number;
    title: string;
    description?: string;
    status: ObjectiveStatus;
    priority: string;
    progress_percentage: number;
    target_date?: string;
    completed_at?: string;
    period_start: string;
    period_end: string;
    key_results?: KeyResult[];
    success_criteria?: string[];
    blockers?: Blocker[];
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    status_label?: string;
    status_color?: string;
    status_icon?: string;
    priority_label?: string;
    is_overdue?: boolean;
    days_remaining?: number;

    // Relations
    department?: Department;
    parent?: DepartmentObjective;
    children?: DepartmentObjective[];
    assignee?: User;
}

export interface KeyResult {
    description: string;
    target?: number;
    current?: number;
    unit?: string;
    completed?: boolean;
}

export interface Blocker {
    description: string;
    added_at: string;
    resolved: boolean;
    resolved_at?: string;
}

// Department KPI
export interface DepartmentKpi {
    id: number;
    uuid: string;
    department_id: number;
    name: string;
    description?: string;
    unit: string;
    target_value: number;
    warning_threshold?: number;
    critical_threshold?: number;
    trend_direction: TrendDirection;
    calculation_method?: string;
    data_source?: string;
    is_active: boolean;
    display_order: number;
    config?: Record<string, any>;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    trend_direction_label?: string;
    current_value?: number;
    performance_status?: 'good' | 'warning' | 'critical' | 'unknown';
    status_color?: string;
    trend?: TrendData;

    // Relations
    department?: Department;
    values?: DepartmentKpiValue[];
}

export interface DepartmentKpiValue {
    id: number;
    kpi_id: number;
    report_id?: number;
    value: number;
    recorded_at: string;
    recorded_by?: number;
    notes?: string;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;

    // Relations
    kpi?: DepartmentKpi;
    report?: DepartmentReport;
    recorder?: User;

    // Computed
    formatted_value?: string;
}

// Report Approval
export interface ReportApproval {
    id: number;
    report_id: number;
    user_id: number;
    step: number;
    role: string;
    status: ApprovalStatus;
    comments?: string;
    decided_at?: string;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;

    // Computed properties
    status_label?: string;
    status_color?: string;
    is_pending?: boolean;

    // Relations
    report?: DepartmentReport;
    user?: User;
}

// Report Comment
export interface ReportComment {
    id: number;
    uuid: string;
    report_id: number;
    section_id?: number;
    user_id: number;
    parent_id?: number;
    type: CommentType;
    content: string;
    is_resolved: boolean;
    resolved_by?: number;
    resolved_at?: string;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    type_label?: string;
    type_icon?: string;
    type_color?: string;

    // Relations
    report?: DepartmentReport;
    section?: ReportSection;
    user?: User;
    parent?: ReportComment;
    replies?: ReportComment[];
    resolver?: User;
}

// Report Version
export interface ReportVersion {
    id: number;
    report_id: number;
    version_number: number;
    snapshot: Record<string, any>;
    change_summary?: string;
    created_by: number;
    created_at: string;
    updated_at: string;

    // Relations
    report?: DepartmentReport;
    creator?: User;
}

// Report Attachment
export interface ReportAttachment {
    id: number;
    uuid: string;
    report_id: number;
    section_id?: number;
    uploaded_by: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    size: number;
    path: string;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed properties
    url?: string;
    size_formatted?: string;
    is_image?: boolean;
    is_pdf?: boolean;
    extension?: string;

    // Relations
    report?: DepartmentReport;
    section?: ReportSection;
    uploader?: User;
}

// Report Reminder
export interface ReportReminder {
    id: number;
    department_id: number;
    template_id?: number;
    type: ReminderType;
    scheduled_at: string;
    sent_at?: string;
    recipient_id: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;

    // Computed properties
    type_label?: string;
    is_sent?: boolean;
    is_pending?: boolean;

    // Relations
    department?: Department;
    template?: ReportTemplate;
    recipient?: User;
}

// Report Tag
export interface ReportTag {
    id: number;
    report_id: number;
    tag: string;
    created_at: string;
    updated_at: string;
}

// Aggregated Data Types
export interface ReportAggregatedData {
    summary: ReportSummary;
    activities: ActivitiesData;
    objectives: ObjectivesData;
    kpis: KpiData[];
    projects: ProjectsData;
    tasks: TasksData;
    members: MembersData;
    trends: TrendsData;
}

export interface ReportSummary {
    total_activities: number;
    total_hours: number;
    objectives_completed: number;
    objectives_total: number;
    completion_rate: number;
    unique_participants: number;
    projects_active: number;
}

export interface ActivitiesData {
    total: number;
    total_hours: number;
    by_category: Record<string, CategoryStats>;
    recent: ActivitySummary[];
    timeline: TimelineDataPoint[];
}

export interface CategoryStats {
    label: string;
    icon: string;
    color: string;
    count: number;
    hours: number;
    participants: number;
}

export interface ActivitySummary {
    id: number;
    title: string;
    category: string;
    category_label: string;
    date: string;
    duration?: number;
    user?: string;
}

export interface TimelineDataPoint {
    date: string;
    count: number;
    hours: number;
}

export interface ObjectivesData {
    total: number;
    average_progress: number;
    by_status: Record<string, StatusStats>;
    overdue_count: number;
    at_risk_count: number;
    list: ObjectiveSummary[];
}

export interface StatusStats {
    label: string;
    color: string;
    icon: string;
    count: number;
    avg_progress: number;
}

export interface ObjectiveSummary {
    id: number;
    uuid: string;
    title: string;
    status: string;
    status_label: string;
    status_color: string;
    progress: number;
    target_date?: string;
    is_overdue: boolean;
    assignee?: string;
    key_results?: KeyResult[];
    children_count: number;
}

export interface KpiData {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    unit: string;
    target: number;
    current?: number;
    previous?: number;
    performance_status: string;
    status_color: string;
    trend: TrendData;
    values: KpiValueData[];
}

export interface KpiValueData {
    value: number;
    date: string;
}

export interface TrendData {
    current: number;
    previous: number;
    change: number;
    percentage: number;
    direction: 'up' | 'down' | 'stable';
    is_positive?: boolean;
}

export interface ProjectsData {
    total: number;
    by_status: Record<string, number>;
    list: ProjectSummary[];
}

export interface ProjectSummary {
    id: number;
    name: string;
    status: string;
    progress: number;
    start_date?: string;
    end_date?: string;
    manager?: string;
    tasks_total: number;
    tasks_completed: number;
}

export interface TasksData {
    created: number;
    completed: number;
    by_status: Record<string, number>;
    by_priority: Record<string, number>;
    completion_rate: number;
}

export interface MembersData {
    total_members: number;
    list: MemberStats[];
    top_contributors: MemberStats[];
}

export interface MemberStats {
    id: number;
    name: string;
    activities_count: number;
    total_hours: number;
    categories: Record<string, number>;
}

export interface TrendsData {
    period_type: string;
    previous_period: {
        start: string;
        end: string;
    };
    activities: TrendData;
    objectives_completed: TrendData;
    hours: TrendData;
}

// Filter Types
export interface ReportFilters {
    department_id?: number;
    status?: ReportStatus;
    type?: ReportType;
    period_type?: ReportPeriodType;
    year?: number;
    search?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    per_page?: number;
}

// Select Option Type
export interface SelectOption {
    value: string;
    label: string;
    color?: string;
    icon?: string;
}

// Status Configuration
export interface StatusConfig {
    value: ReportStatus;
    label: string;
    color: string;
    icon: string;
    bgColor: string;
    textColor: string;
}

// Page Props
export interface ReportIndexPageProps {
    reports: PaginatedData<DepartmentReport>;
    departments: Department[];
    statuses: SelectOption[];
    types: SelectOption[];
    periodTypes: SelectOption[];
    filters: ReportFilters;
}

export interface ReportShowPageProps {
    report: DepartmentReport;
    aggregatedData?: ReportAggregatedData;
    canEdit: boolean;
    canSubmit: boolean;
    canApprove: boolean;
    canPublish: boolean;
}

export interface ReportCreatePageProps {
    departments: Department[];
    templates: ReportTemplate[];
    types: SelectOption[];
    periodTypes: SelectOption[];
    departmentId?: number;
}

export interface ReportEditPageProps {
    report: DepartmentReport;
    types: SelectOption[];
    periodTypes: SelectOption[];
    popularTags: Record<string, number>;
}

export interface PaginatedData<T> {
    data: T[];
    // Laravel API Resource format
    links?: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    // Laravel paginate() format (flat structure)
    current_page?: number;
    from?: number | null;
    last_page?: number;
    per_page?: number;
    to?: number | null;
    total?: number;
    first_page_url?: string;
    last_page_url?: string;
    prev_page_url?: string | null;
    next_page_url?: string | null;
}
