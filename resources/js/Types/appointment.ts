import { PageProps } from './';

export interface Appointment {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_datetime: string;
    end_datetime: string;
    location?: string;
    meeting_mode?: MeetingMode;
    meeting_link?: string;
    meeting_platform?: MeetingPlatform;
    status: AppointmentStatus;
    type: AppointmentType;
    visibility: AppointmentVisibility;
    user_id: number;
    appointmentable_type?: string;
    appointmentable_id?: number;
    metadata?: Record<string, any>;
    notification_channels?: NotificationChannel[];
    reminder_sent_at?: string;
    sms_reminder_sent_at?: string;
    whatsapp_reminder_sent_at?: string;
    created_at: string;
    updated_at: string;

    // Computed attributes
    duration_minutes: number;
    is_past: boolean;
    is_future: boolean;
    is_today: boolean;
    can_be_cancelled: boolean;
    can_be_modified: boolean;
    formatted_date: string;
    formatted_time_range: string;
    participants_count: number;

    // Relationships
    organizer?: User;
    participants?: AppointmentParticipant[];
    appointmentable?: any;
}

export type AppointmentStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed';
export type AppointmentType = 'individual' | 'group' | 'consultation' | 'meeting';
export type AppointmentVisibility = 'private' | 'public';
export type MeetingMode = 'in_person' | 'online' | 'hybrid';
export type MeetingPlatform = 'zoom' | 'google_meet' | 'ms_teams' | 'other';
export type ParticipantStatus = 'pending' | 'accepted' | 'declined' | 'cancelled';

export interface AppointmentParticipant extends User {
    pivot: {
        status: ParticipantStatus;
        invited_at?: string;
        responded_at?: string;
        attended: boolean;
        notes?: string;
        created_at: string;
        updated_at: string;
    };
}

export interface User {
    id: number;
    uuid?: string;
    name: string;
    email: string;
    avatar?: string;
}

export type NotificationChannel = 'email' | 'sms' | 'whatsapp';

export interface AppointmentFormData {
    title: string;
    description: string;
    start_datetime: string;
    end_datetime: string;
    location: string;
    meeting_mode: MeetingMode;
    meeting_link?: string;
    meeting_platform?: MeetingPlatform;
    type: AppointmentType;
    visibility: AppointmentVisibility;
    participant_ids: number[];
    notification_channels?: NotificationChannel[];
}

export interface AvailableSlot {
    start_datetime: string;
    end_datetime: string;
    formatted_time: string;
}

export interface AvailableSlotsResponse {
    date: string;
    duration_minutes: number;
    available_slots: AvailableSlot[];
    total_slots: number;
}

export interface AppointmentStats {
    total: number;
    upcoming: number;
    today: number;
    pending: number;
    confirmed: number;
}

export interface AppointmentFilters {
    search?: string;
    status?: AppointmentStatus;
    type?: AppointmentType;
    date?: string;
    view?: 'list' | 'calendar';
}

export interface CalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    backgroundColor: string;
    borderColor: string;
    url: string;
    extendedProps: {
        status: AppointmentStatus;
        type: AppointmentType;
        location?: string;
        organizer: string;
        participants_count: number;
    };
}

export interface AppointmentPageProps extends PageProps {
    appointments: {
        data: Appointment[];
        links: any;
        meta: any;
    };
    stats: AppointmentStats;
    filters: AppointmentFilters;
    statuses: AppointmentStatus[];
    types: AppointmentType[];
}

export interface AppointmentShowProps extends PageProps {
    appointment: Appointment;
    canModify: boolean;
    canCancel: boolean;
}

export interface AppointmentCreateEditProps extends PageProps {
    appointment?: Appointment;
    users: User[];
    types: AppointmentType[];
    prefilledData?: {
        date?: string;
        time?: string;
        participant_ids?: number[];
    };
    preselectedParticipants?: User[];
}

export interface AppointmentCalendarProps extends PageProps {
    appointments: CalendarEvent[];
    currentMonth: string;
}