export interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    name: string; // Full name for compatibility
    email: string;
    email_verified_at?: string;
    birth_date?: string;
    avatar?: string;
    roles: Role[];
    permissions: Permission[];
    departments: Department[];
    full_name: string;
}

export interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

export interface Permission {
    id: number;
    name: string;
}

export interface Article {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt?: string;
    content: string;
    status: 'draft' | 'published' | 'archived';
    featured_image?: string;
    tags?: string;
    author_id: number;
    author: User;
    category_id?: number;
    category?: Category;
    published_at?: string;
    created_at: string;
    updated_at: string;
}

export interface Event {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_date: string;
    end_date: string;
    location?: string;
    max_participants?: number;
    is_public: boolean;
    status: 'planned' | 'ongoing' | 'completed' | 'cancelled';
    user_id: number;
    address_id?: number;
    address?: Address;
    creator: User;
    participants: User[];
    participants_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    head_of_department?: number;
    budget?: number;
    is_active: boolean;
    users: User[];
    headOfDepartmentUser?: User;
    created_at: string;
    updated_at: string;
}

export interface Book {
    id: number;
    uuid: string;
    title: string;
    author: string;
    isbn?: string;
    description?: string;
    rental_price?: number;
    max_rental_days: number;
    stock_quantity: number;
    category_id?: number;
    category?: Category;
    libraries?: Library[];
    is_available?: boolean;
    created_at: string;
    updated_at: string;
}

export interface BookRental {
    id: number;
    uuid: string;
    book: Book;
    user: User;
    rental_date: string;
    due_date: string;
    returned_date?: string;
    extended: boolean;
    total_cost: number;
    status: 'active' | 'returned' | 'overdue';
}

export interface Image {
    id: number;
    filename: string;
    path: string;
    alt_text?: string;
}

export interface Video {
    id: number;
    filename: string;
    path: string;
    duration?: number;
    thumbnail?: string;
}

export interface Message {
    id: number;
    uuid: string;
    sender?: User;
    receiver?: User;
    receiver_id?: number;
    content: string;
    subject?: string;
    type: 'contact' | 'chat' | 'notification' | 'direct' | 'broadcast' | 'system' | 'appointment';
    type_label?: string;
    excerpt?: string;
    is_sent?: boolean;
    all_recipients?: User[];
    recipients_count?: number;
    read_at?: string;
    created_at: string;
    updated_at?: string;
}

export interface Address {
    id: number;
    street: string;
    city: string;
    postal_code: string;
    country: string;
    latitude?: number;
    longitude?: number;
}

export interface Category {
    id: number;
    name: string;
    description?: string;
    type: string;
}

export interface Status {
    id: number;
    name: string;
    description?: string;
    color: string;
}

export interface Program {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    start_date: string;
    end_date: string;
    budget?: number;
    status: 'planning' | 'active' | 'in_progress' | 'completed' | 'cancelled';
    priority: 'low' | 'medium' | 'high';
    progress_percentage?: number;
    user_id: number;
    user: User;
    tasks: Task[];
    created_at: string;
    updated_at: string;
}

export interface Task {
    id: number;
    uuid: string;
    title: string;
    description: string;
    due_date: string | null;
    priority: 'low' | 'medium' | 'high';
    estimated_hours: number | null;
    actual_hours: number | null;
    notes: string | null;
    status_id: number;
    program_id: number | null;
    project_id?: number | null;
    assigned_to: number;
    status: Status;
    program?: Program;
    assigned_user?: User;
    created_at: string;
    updated_at: string;
}

export interface Stock {
    id: number;
    uuid: string;
    name: string;
    sku: string;
    description?: string;
    quantity: number;
    minimum_quantity: number;
    unit_price: number;
    supplier?: string;
    supplier_contact?: string;
    expiry_date?: string;
    location?: string;
    is_active: boolean;
    category_id: number;
    category?: Category;
    created_at: string;
    updated_at: string;
}

export interface Library {
    id: number;
    name: string;
    code: string;
    description?: string;
    address?: string;
    contact_person?: string;
    contact_email?: string;
    contact_phone?: string;
    is_active: boolean;
    books: Book[];
    created_at: string;
    updated_at: string;
}

export interface Group {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    code: string;
    max_members?: number;
    leader_id?: number;
    leader?: User;
    is_active: boolean;
    members: User[];
    members_count: number;
    created_at: string;
    updated_at: string;
}

// Re-export all types from Project.ts
export * from './Project';