// Event Module TypeScript Types

// ===== Enums =====

export type EventStatus = 'draft' | 'published' | 'ongoing' | 'completed' | 'cancelled' | 'postponed';
export type EventType = 'conference' | 'workshop' | 'seminar' | 'webinar' | 'hybrid' | 'meetup' | 'retreat' | 'training' | 'celebration' | 'other';
export type EventVisibility = 'public' | 'private' | 'internal' | 'invite_only';
export type RegistrationStatus = 'pending' | 'confirmed' | 'waitlisted' | 'cancelled' | 'checked_in' | 'no_show';
export type PaymentStatus = 'pending' | 'completed' | 'failed' | 'refunded' | 'partial' | 'cancelled';
export type TicketType = 'free' | 'paid' | 'donation' | 'early_bird' | 'vip' | 'group' | 'student' | 'member';
export type SessionFormat = 'in_person' | 'virtual' | 'hybrid';
export type ParticipantRole = 'attendee' | 'speaker' | 'moderator' | 'volunteer' | 'staff' | 'sponsor' | 'exhibitor' | 'vip' | 'press' | 'observer';
export type BadgeStatus = 'pending' | 'generated' | 'printed' | 'collected' | 'lost' | 'replaced';
export type NotificationType = 'email' | 'sms' | 'push' | 'in_app';

// ===== Base Models =====

export interface Event {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    type: EventType;
    status: EventStatus;
    visibility: EventVisibility;
    start_date: string;
    end_date: string;
    registration_deadline?: string;
    early_bird_deadline?: string;
    location?: string;
    max_participants?: number;
    waitlist_capacity?: number;
    waitlist_enabled: boolean;
    requires_approval: boolean;
    timezone: string;
    streaming_url?: string;
    streaming_platform?: string;
    is_public: boolean;
    avatar?: string;
    images?: string[];
    settings?: Record<string, any>;
    metadata?: Record<string, any>;
    category_id?: number;
    department_id?: number;
    user_id: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Relationships
    category?: EventCategory;
    department?: Department;
    creator?: User;
    tickets?: EventTicket[];
    sessions?: EventSession[];
    registrations?: EventRegistration[];
    sponsors?: EventSponsor[];
    media?: EventMedia[];
    banners?: EventMedia[];
    gallery_images?: EventMedia[];
    gallery_videos?: EventMedia[];

    // Computed
    registrations_count?: number;
    checked_in_count?: number;
    waitlist_count?: number;
    total_revenue?: number;
    is_virtual?: boolean;
    is_hybrid?: boolean;
    has_early_bird?: boolean;
    is_full?: boolean;
    can_accept_registrations?: boolean;
}

export interface EventCategory {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string;
    color: string;
    icon?: string;
    is_active: boolean;
    sort_order: number;
    parent_id?: number;
    parent?: EventCategory;
    children?: EventCategory[];
    events_count?: number;
    created_at: string;
    updated_at: string;
}

export interface EventTicket {
    id: number;
    uuid: string;
    event_id: number;
    name: string;
    description?: string;
    type: TicketType;
    price: number;
    original_price?: number;
    currency: string;
    quantity_total?: number;
    quantity_sold: number;
    quantity_reserved: number;
    min_per_order: number;
    max_per_order?: number;
    sales_start?: string;
    sales_end?: string;
    benefits?: string[];
    restrictions?: Record<string, any>;
    is_visible: boolean;
    requires_approval: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;

    // Computed
    available_quantity?: number;
    is_available?: boolean;
    is_on_sale?: boolean;
    current_price?: number;
    confirmed_count?: number;
    pending_count?: number;
}

export interface EventSession {
    id: number;
    uuid: string;
    event_id: number;
    room_id?: number;
    title: string;
    description?: string;
    format: SessionFormat;
    start_time: string;
    end_time: string;
    capacity?: number;
    streaming_url?: string;
    recording_url?: string;
    resources?: string[];
    metadata?: Record<string, any>;
    is_mandatory: boolean;
    requires_registration: boolean;
    status: string;
    sort_order: number;
    created_at: string;
    updated_at: string;

    // Relationships
    room?: VenueRoom;
    speakers?: SessionSpeaker[];

    // Computed
    duration_in_minutes?: number;
    duration_for_humans?: string;
    attendees_count?: number;
    available_spots?: number;
    speakers_names?: string;
}

export interface SessionSpeaker {
    id: number;
    uuid: string;
    session_id: number;
    user_id?: number;
    name: string;
    email?: string;
    title?: string;
    company?: string;
    bio?: string;
    photo?: string;
    role: string;
    social_links?: Record<string, string>;
    sort_order: number;
    is_confirmed: boolean;
    created_at: string;
    updated_at: string;
}

export interface EventRegistration {
    id: number;
    uuid: string;
    registration_number: string;
    event_id: number;
    user_id?: number;
    ticket_id?: number;
    promo_code_id?: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    company?: string;
    job_title?: string;
    status: RegistrationStatus;
    participant_role: ParticipantRole;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    total_amount: number;
    currency: string;
    form_answers?: Record<string, any>;
    dietary_requirements?: string[];
    accessibility_needs?: string[];
    special_requests?: string;
    metadata?: Record<string, any>;
    qr_code?: string;
    registered_at?: string;
    confirmed_at?: string;
    cancelled_at?: string;
    cancellation_reason?: string;
    cancelled_by?: number;
    created_at: string;
    updated_at: string;

    // Relationships
    event?: Event;
    user?: User;
    ticket?: EventTicket;
    promo_code?: EventPromoCode;
    payments?: RegistrationPayment[];
    checkins?: EventCheckin[];
    badge?: EventBadge;

    // Computed
    full_name?: string;
    is_pending?: boolean;
    is_confirmed?: boolean;
    is_waitlisted?: boolean;
    is_cancelled?: boolean;
    is_checked_in?: boolean;
    has_paid?: boolean;
    balance_due?: number;
}

export interface RegistrationPayment {
    id: number;
    uuid: string;
    registration_id: number;
    payment_number: string;
    status: PaymentStatus;
    payment_method?: string;
    payment_provider?: string;
    transaction_id?: string;
    amount: number;
    fee: number;
    net_amount: number;
    currency: string;
    provider_response?: Record<string, any>;
    notes?: string;
    paid_at?: string;
    refunded_at?: string;
    refund_amount?: number;
    refund_reason?: string;
    created_at: string;
    updated_at: string;
}

export interface EventCheckin {
    id: number;
    uuid: string;
    registration_id: number;
    session_id?: number;
    checked_in_by?: number;
    check_type: string;
    method: string;
    device_id?: string;
    location?: string;
    metadata?: Record<string, any>;
    checked_in_at: string;
    checked_out_at?: string;
    created_at: string;
    updated_at: string;

    // Relationships
    registration?: EventRegistration;
    session?: EventSession;
    checked_in_by_user?: User;
}

export interface EventBadge {
    id: number;
    uuid: string;
    registration_id: number;
    badge_number: string;
    template: string;
    first_name: string;
    last_name: string;
    company?: string;
    job_title?: string;
    custom_fields?: Record<string, any>;
    qr_data: string;
    status: BadgeStatus;
    generated_at?: string;
    printed_at?: string;
    printed_by?: number;
    collected_at?: string;
    replaced_by?: number;
    created_at: string;
    updated_at: string;

    // Relationships
    registration?: EventRegistration;

    // Computed
    full_name?: string;
}

export interface EventPromoCode {
    id: number;
    uuid: string;
    event_id: number;
    code: string;
    description?: string;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    min_order_amount?: number;
    max_discount?: number;
    usage_limit?: number;
    usage_per_user: number;
    usage_count: number;
    valid_from?: string;
    valid_until?: string;
    applicable_tickets?: number[];
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Computed
    is_valid?: boolean;
    remaining_uses?: number;
}

export interface EventVenue {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    address_line1: string;
    address_line2?: string;
    city: string;
    state?: string;
    postal_code?: string;
    country: string;
    latitude?: number;
    longitude?: number;
    total_capacity?: number;
    contact_name?: string;
    contact_email?: string;
    contact_phone?: string;
    website?: string;
    amenities?: string[];
    images?: string[];
    access_instructions?: string;
    parking_info?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Relationships
    rooms?: VenueRoom[];

    // Computed
    full_address?: string;
    google_maps_url?: string;
}

export interface VenueRoom {
    id: number;
    uuid: string;
    venue_id: number;
    name: string;
    description?: string;
    capacity?: number;
    floor?: string;
    room_number?: string;
    equipment?: string[];
    layout_options?: string[];
    is_available: boolean;
    hourly_rate?: number;
    created_at: string;
    updated_at: string;

    // Relationships
    venue?: EventVenue;
}

export interface EventSponsor {
    id: number;
    uuid: string;
    event_id: number;
    name: string;
    description?: string;
    tier: 'platinum' | 'gold' | 'silver' | 'bronze' | 'partner' | 'media' | 'community';
    logo?: string;
    website?: string;
    contact_name?: string;
    contact_email?: string;
    contact_phone?: string;
    amount?: number;
    currency: string;
    benefits?: string[];
    is_visible: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;

    // Computed
    tier_color?: string;
}

export interface EventFeedback {
    id: number;
    uuid: string;
    event_id: number;
    registration_id?: number;
    session_id?: number;
    overall_rating?: number;
    content_rating?: number;
    speaker_rating?: number;
    venue_rating?: number;
    organization_rating?: number;
    nps_score?: number;
    would_recommend: boolean;
    highlights?: string;
    improvements?: string;
    comments?: string;
    is_anonymous: boolean;
    submitted_at: string;
    created_at: string;
    updated_at: string;
}

export interface EventWaitlist {
    id: number;
    uuid: string;
    event_id: number;
    ticket_id?: number;
    user_id?: number;
    email: string;
    first_name: string;
    last_name: string;
    phone?: string;
    quantity: number;
    position: number;
    status: 'waiting' | 'notified' | 'expired' | 'converted' | 'cancelled';
    notified_at?: string;
    expires_at?: string;
    converted_at?: string;
    registration_id?: number;
    created_at: string;
    updated_at: string;

    // Computed
    full_name?: string;
}

export type EventMediaType = 'image' | 'video';
export type EventMediaCollection = 'banner' | 'gallery';

export interface EventMedia {
    id: number;
    uuid: string;
    event_id: number;
    uploaded_by?: number;
    title?: string;
    description?: string;
    file_path: string;
    file_name: string;
    file_type: string;
    file_size: number;
    media_type: EventMediaType;
    collection: EventMediaCollection;
    is_featured: boolean;
    thumbnail_path?: string;
    width?: number;
    height?: number;
    duration?: number;
    sort_order: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    deleted_at?: string;

    // Computed
    file_url?: string;
    thumbnail_url?: string;
    file_size_for_humans?: string;
    duration_for_humans?: string;
    dimensions?: string;
    aspect_ratio?: number;

    // Relationships
    event?: Event;
    uploader?: User;
}

// ===== Event Programme =====

export interface EventProgramme {
    id: number;
    uuid: string;
    event_id: number;
    uploaded_by?: number;
    file_path: string;
    file_name: string;
    file_type: string;
    file_size: number;
    share_token?: string;
    share_token_expires_at?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Computed
    file_url?: string;
    file_size_for_humans?: string;
    is_pdf?: boolean;
    is_image?: boolean;
    can_preview?: boolean;
    share_url?: string;
}

// ===== API Response Types =====

export interface TicketStats {
    total_tickets: number;
    total_capacity?: number;
    total_sold: number;
    total_reserved: number;
    total_available: number;
    revenue: number;
    currency: string;
    by_type: Record<string, { count: number; sold: number; revenue: number }>;
}

export interface RegistrationStats {
    total: number;
    total_attendees: number;
    by_status: Record<RegistrationStatus, number>;
    by_role: Record<ParticipantRole, number>;
    by_company: Record<string, number>;
    conversion_rate: number;
}

export interface CheckInStats {
    total_expected: number;
    checked_in: number;
    not_checked_in: number;
    attendance_rate: number;
    by_hour: Record<string, number>;
    by_ticket: Record<string, { count: number; ticket_id: number }>;
}

export interface BadgeStats {
    total_expected: number;
    generated: number;
    printed: number;
    collected: number;
    pending_generation: number;
    lost: number;
    replaced: number;
}

export interface RevenueStats {
    expected: { amount: number; currency: string };
    collected: { amount: number; net_amount: number; fees: number };
    pending: { amount: number };
    refunded: { amount: number; count: number };
    by_ticket: Array<{ ticket_name: string; count: number; quantity: number; revenue: number }>;
    by_promo_code: Array<{ code: string; uses: number; discount_total: number }>;
}

export interface FeedbackStats {
    count: number;
    response_rate: number;
    overall_rating?: number;
    nps?: number;
    ratings: {
        content?: number;
        speaker?: number;
        venue?: number;
        organization?: number;
    };
    would_recommend: { yes: number; no: number; percentage: number };
    rating_distribution: Record<number, number>;
}

export interface EventOverview {
    event: {
        id: number;
        title: string;
        status: string;
        type?: string;
        visibility?: string;
        start_date: string;
        end_date: string;
        days_until: number;
        is_ongoing: boolean;
        has_ended: boolean;
    };
    capacity: {
        max?: number;
        registered: number;
        available?: number;
        utilization?: number;
    };
    waitlist: {
        enabled: boolean;
        count: number;
        capacity?: number;
    };
}

export interface EventDashboard {
    overview: EventOverview;
    registrations: RegistrationStats;
    tickets: TicketStats;
    checkins: CheckInStats;
    badges: BadgeStats;
    revenue: RevenueStats;
    feedback: FeedbackStats;
    trends: {
        daily: Array<{ date: string; registrations: number; attendees: number; revenue: number }>;
        cumulative: Array<{ date: string; registrations: number; attendees: number; revenue: number }>;
    };
}

export interface CheckInResult {
    success: boolean;
    message: string;
    registration?: EventRegistration;
    checkin?: EventCheckin;
}

export interface PriceCalculation {
    unit_price: number;
    quantity: number;
    subtotal: number;
    discount: number;
    total: number;
    currency: string;
    promo_code?: string;
}

export interface BadgeTemplate {
    name: string;
    size: string;
    fields: string[];
    color?: string;
}

export interface BadgePrintData {
    badge: {
        number: string;
        first_name: string;
        last_name: string;
        full_name: string;
        company?: string;
        job_title?: string;
        qr_code: string;
        custom_fields?: Record<string, any>;
    };
    registration: {
        number: string;
        role: string;
        ticket?: string;
    };
    event: {
        name: string;
        date: string;
        location?: string;
    };
    template: BadgeTemplate;
}

// ===== Form Types =====

export interface TicketFormData {
    name: string;
    description?: string;
    type: TicketType;
    price: number;
    original_price?: number;
    currency?: string;
    quantity_total?: number;
    min_per_order?: number;
    max_per_order?: number;
    sales_start?: string;
    sales_end?: string;
    benefits?: string[];
    restrictions?: Record<string, any>;
    is_visible?: boolean;
    requires_approval?: boolean;
    sort_order?: number;
}

export interface RegistrationFormData {
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    company?: string;
    job_title?: string;
    ticket_id?: number;
    promo_code?: string;
    quantity?: number;
    participant_role?: ParticipantRole;
    form_answers?: Record<string, any>;
    dietary_requirements?: string[];
    accessibility_needs?: string[];
    special_requests?: string;
}

// ===== Utility Types =====

export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    first_name?: string;
    last_name?: string;
}

export interface Department {
    id: number;
    uuid: string;
    name: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from?: number;
        to?: number;
    };
    links?: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}
