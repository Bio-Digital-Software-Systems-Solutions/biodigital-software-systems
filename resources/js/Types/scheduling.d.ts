// ============================================
// Scheduling System Types
// ============================================

// Enums
export type ShiftStatus =
    | 'draft'
    | 'published'
    | 'confirmed'
    | 'in_progress'
    | 'completed'
    | 'cancelled'
    | 'no_show';

export type ShiftType =
    | 'morning'
    | 'afternoon'
    | 'evening'
    | 'night'
    | 'full_day'
    | 'split'
    | 'on_call'
    | 'custom';

export type AvailabilityStatus =
    | 'available'
    | 'partially_available'
    | 'unavailable'
    | 'preferred'
    | 'if_needed';

export type AbsenceType =
    | 'vacation'
    | 'sick_leave'
    | 'family_leave'
    | 'maternity_leave'
    | 'paternity_leave'
    | 'bereavement'
    | 'personal'
    | 'training'
    | 'unpaid_leave'
    | 'other';

export type AbsenceStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

export type SwapRequestStatus =
    | 'pending_colleague'
    | 'pending_manager'
    | 'approved'
    | 'rejected_colleague'
    | 'rejected_manager'
    | 'cancelled'
    | 'expired';

export type ShiftTaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type ShiftTaskStatus =
    | 'todo'
    | 'in_progress'
    | 'completed'
    | 'blocked'
    | 'cancelled';

export type RecurrenceType = 'none' | 'daily' | 'weekly' | 'biweekly' | 'monthly';

export type DayOfWeek = 0 | 1 | 2 | 3 | 4 | 5 | 6;

export type ScheduleStatus = 'draft' | 'published' | 'locked';

// Enum Option type for dropdowns
export interface EnumOption<T extends string = string> {
    value: T;
    label: string;
    color?: string;
}

// ============================================
// Models
// ============================================

export interface DepartmentSchedulingSettings {
    id: number;
    uuid: string;
    department_id: number;
    default_shift_duration: number;
    min_rest_between_shifts: number;
    max_hours_per_week: number;
    max_hours_per_day: number;
    max_consecutive_days: number;
    overtime_threshold: number;
    allow_self_assignment: boolean;
    allow_shift_swap: boolean;
    require_swap_approval: boolean;
    advance_schedule_weeks: number;
    auto_publish_enabled: boolean;
    auto_publish_day: DayOfWeek | null;
    notifications_enabled: boolean;
    notification_settings: Record<string, boolean> | null;
    created_at: string;
    updated_at: string;
}

export interface WeeklySchedule {
    id: number;
    uuid: string;
    department_id: number;
    week_start: string;
    week_end: string;
    status: ScheduleStatus;
    notes: string | null;
    published_at: string | null;
    published_by: number | null;
    locked_at: string | null;
    created_by: number | null;
    created_at: string;
    updated_at: string;
    // Relations
    shifts?: Shift[];
    department?: Department;
    publishedBy?: User;
    createdBy?: User;
    // Computed
    is_published?: boolean;
    is_locked?: boolean;
    is_editable?: boolean;
    week_label?: string;
    week_number?: number;
}

export interface Shift {
    id: number;
    uuid: string;
    weekly_schedule_id: number;
    department_id: number;
    position_id: number | null;
    user_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    break_duration: number;
    type: ShiftType;
    status: ShiftStatus;
    title: string | null;
    description: string | null;
    location: string | null;
    color: string | null;
    min_employees: number;
    max_employees: number;
    required_skills: number[];
    hourly_rate: number | null;
    is_overtime: boolean;
    requires_approval: boolean;
    checked_in_at: string | null;
    check_in_location: string | null;
    checked_out_at: string | null;
    check_out_location: string | null;
    actual_start_time: string | null;
    actual_end_time: string | null;
    assigned_by: number | null;
    assigned_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    users?: User[];
    weeklySchedule?: WeeklySchedule;
    department?: Department;
    position?: Position;
    tasks?: ShiftTask[];
    assignedByUser?: User;
    // Computed
    duration_hours?: number;
    duration_minutes?: number;
    is_assigned?: boolean;
    can_check_in?: boolean;
    can_check_out?: boolean;
    is_today?: boolean;
}

export interface ShiftTask {
    id: number;
    uuid: string;
    shift_id: number;
    title: string;
    description: string | null;
    priority: ShiftTaskPriority;
    status: ShiftTaskStatus;
    estimated_duration: number | null;
    order: number;
    checklist: ChecklistItem[] | null;
    completed_at: string | null;
    completed_by: number | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    shift?: Shift;
    completedBy?: User;
    // Computed
    is_completed?: boolean;
    checklist_progress?: number;
}

export interface ChecklistItem {
    text: string;
    completed: boolean;
    completed_at: string | null;
}

export interface EmployeeAvailability {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number;
    day_of_week: DayOfWeek | null;
    specific_date: string | null;
    status: AvailabilityStatus;
    start_time: string | null;
    end_time: string | null;
    recurrence_type: RecurrenceType;
    effective_from: string | null;
    effective_until: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    department?: Department;
}

export interface Absence {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number;
    type: AbsenceType;
    status: AbsenceStatus;
    start_date: string;
    end_date: string;
    total_days: number;
    reason: string | null;
    is_half_day: boolean;
    half_day_period: 'morning' | 'afternoon' | null;
    approved_by: number | null;
    approved_at: string | null;
    rejection_reason: string | null;
    attachment_path: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    department?: Department;
    approvedByUser?: User;
    // Computed
    is_pending?: boolean;
    is_approved?: boolean;
    can_cancel?: boolean;
}

export interface ShiftSwapRequest {
    id: number;
    uuid: string;
    requester_id: number;
    target_user_id: number;
    requested_shift_id: number;
    offered_shift_id: number | null;
    status: SwapRequestStatus;
    reason: string | null;
    colleague_response_at: string | null;
    manager_response_at: string | null;
    approved_by: number | null;
    rejection_reason: string | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    requester?: User;
    targetUser?: User;
    requestedShift?: Shift;
    offeredShift?: Shift | null;
    approvedByUser?: User;
    // Computed
    is_pending?: boolean;
    is_expired?: boolean;
}

export interface Skill {
    id: number;
    uuid: string;
    department_id: number | null;
    name: string;
    description: string | null;
    category: string | null;
    is_certification: boolean;
    validity_period_months: number | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    department?: Department;
    users?: User[];
}

export interface EmployeeWorkPreferences {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number;
    preferred_shift_types: ShiftType[];
    preferred_days: DayOfWeek[];
    preferred_start_time: string | null;
    preferred_end_time: string | null;
    max_hours_per_week: number | null;
    min_hours_per_week: number | null;
    max_consecutive_days: number | null;
    avoid_days: DayOfWeek[];
    notes: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    department?: Department;
}

export interface LeaveBalance {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number;
    year: number;
    leave_type: AbsenceType;
    entitled_days: number;
    used_days: number;
    pending_days: number;
    carried_over: number;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    department?: Department;
    // Computed
    remaining?: number;
}

export interface TimeEntry {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number;
    shift_id: number | null;
    date: string;
    clock_in: string;
    clock_out: string | null;
    break_duration: number;
    total_minutes: number | null;
    status: 'pending' | 'approved' | 'rejected';
    clock_in_location: string | null;
    clock_out_location: string | null;
    notes: string | null;
    approved_by: number | null;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
    department?: Department;
    shift?: Shift;
    approvedByUser?: User;
    // Computed
    total_hours?: number;
}

export interface ScheduleTemplate {
    id: number;
    uuid: string;
    department_id: number;
    name: string;
    description: string | null;
    template_data: TemplateShiftData[];
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    department?: Department;
}

export interface TemplateShiftData {
    day_of_week: DayOfWeek;
    start_time: string;
    end_time: string;
    type: ShiftType;
    position_id?: number | null;
    title?: string | null;
    min_employees?: number;
    max_employees?: number;
}

export interface TaskTemplate {
    id: number;
    uuid: string;
    department_id: number;
    title: string;
    description: string | null;
    priority: ShiftTaskPriority;
    estimated_duration: number | null;
    checklist_template: string[];
    required_skills: number[];
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    department?: Department;
}

// ============================================
// Service Response Types
// ============================================

export interface ScheduleStats {
    total_shifts: number;
    assigned_shifts: number;
    unassigned_shifts: number;
    assignment_rate: number;
    total_hours: number;
    assigned_hours: number;
    unassigned_hours: number;
    employee_distribution: EmployeeWorkloadSummary[];
    by_status: Record<ShiftStatus, number>;
    by_type: Record<ShiftType, number>;
    total_schedules?: number;
}

export interface EmployeeWorkloadSummary {
    employee: User;
    shifts_count: number;
    total_hours: number;
}

export interface AvailabilityInfo {
    is_available: boolean;
    status: AvailabilityStatus | null;
    reason: string | null;
    absence: Absence | null;
    availability: EmployeeAvailability | null;
    time_slots: TimeSlot[];
}

export interface TimeSlot {
    start: string;
    end: string;
}

export interface WeeklyPattern {
    [day: number]: {
        day: DayOfWeek;
        label: string;
        status: AvailabilityStatus;
        start_time: string | null;
        end_time: string | null;
        notes: string | null;
    };
}

export interface ConflictResult {
    has_blocking_conflicts: boolean;
    has_warnings: boolean;
    conflicts: Conflict[];
    warnings: Conflict[];
}

export interface Conflict {
    type: ConflictType;
    severity: 'blocking' | 'warning';
    message: string;
    [key: string]: unknown;
}

export type ConflictType =
    | 'overlap'
    | 'rest_period'
    | 'max_hours_day'
    | 'max_hours_week'
    | 'consecutive_days'
    | 'absence'
    | 'skills'
    | 'availability';

export interface WorkloadInfo {
    total_hours: number;
    regular_hours: number;
    overtime_hours: number;
    max_hours: number;
    remaining_hours: number;
    utilization_rate: number;
    shifts_count: number;
    by_day: Record<string, DayWorkload>;
}

export interface DayWorkload {
    date: string;
    day_name: string;
    shifts_count: number;
    hours: number;
}

export interface AvailableEmployee {
    employee: User;
    availability: AvailabilityInfo;
    is_available: boolean;
    conflicts: Conflict[];
    warnings: Conflict[];
    current_hours: number;
    remaining_hours: number;
    score: number;
}

export interface AutoAssignSuggestion {
    shift: Shift;
    suggested_employee: User | null;
    alternatives: AvailableEmployee[];
    can_auto_assign: boolean;
}

export interface FairnessScore {
    score: number;
    rating: 'excellent' | 'good' | 'fair' | 'poor' | 'very_poor';
    standard_deviation: number;
    coefficient_of_variation: number;
    average_hours: number;
    min_hours: number;
    max_hours: number;
    message: string;
}

// ============================================
// Page Props Types
// ============================================

export interface ScheduleIndexProps {
    department: Department;
    schedule: WeeklySchedule;
    stats: ScheduleStats;
    globalStats: ScheduleStats;
    settings: DepartmentSchedulingSettings;
    weeks: Pick<WeeklySchedule, 'uuid' | 'week_start' | 'week_end' | 'status'>[];
    currentWeek: string;
    prevWeek: string;
    nextWeek: string;
}

export interface ShiftFormProps {
    department: Department;
    schedule: WeeklySchedule;
    employees: User[];
    positions: Position[];
    shiftTypes: EnumOption<ShiftType>[];
}

export interface AvailabilityIndexProps {
    department: Department;
    availabilityMatrix: {
        employee: User;
        dates: Record<string, AvailabilityInfo>;
    }[];
    weekStart: string;
    weekEnd: string;
    prevWeek: string;
    nextWeek: string;
    availabilityStatuses: EnumOption<AvailabilityStatus>[];
}

export interface AbsenceIndexProps {
    department: Department;
    absences: PaginatedData<Absence>;
    pendingCount: number;
    absenceTypes: EnumOption<AbsenceType>[];
    absenceStatuses: EnumOption<AbsenceStatus>[];
    filters: {
        status?: string;
        type?: string;
        from?: string;
        to?: string;
    };
}

export interface SwapRequestIndexProps {
    department: Department;
    swapRequests: PaginatedData<ShiftSwapRequest>;
    pendingColleague: number;
    pendingManager: number;
    swapStatuses: EnumOption<SwapRequestStatus>[];
    filters: {
        status?: string;
    };
}

// ============================================
// Utility Types
// ============================================

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Import external types (these should exist in your project)
import type { User } from './models';

export interface Department {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    // Add other department fields as needed
}

export interface Position {
    id: number;
    name: string;
    description: string | null;
    // Add other position fields as needed
}
