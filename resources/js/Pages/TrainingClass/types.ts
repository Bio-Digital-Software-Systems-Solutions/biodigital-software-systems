export interface ClassSchedule {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    room: string | null;
}

export interface TrainingClass {
    id: number;
    uuid: string;
    training_id: number;
    training_name: string;
    teacher_id: number | null;
    teacher_name: string;
    date: string;
    start_time: string;
    end_time: string;
    room: string | null;
    max_students: number | null;
    notes: string | null;
    students_count: number;
    status: string;
    schedules?: ClassSchedule[];
}

export interface Training {
    id: number;
    title: string;
}

export interface Teacher {
    id: number;
    first_name: string;
    last_name: string;
}

export interface Student {
    id: number;
    name: string;
    email: string;
    grade: number | null;
    attendance_rate: number;
    attendance_status: string | null;
    attendance_reason: string | null;
}

export interface Schedule {
    training_id: number;
    training_name: string;
    classes: ScheduleClass[];
}

export interface ScheduleClass {
    id: number;
    date: string;
    day: string;
    start_time: string;
    end_time: string;
    room: string | null;
    teacher: string;
}

export interface Statistics {
    total_classes: number;
    upcoming_classes: number;
    total_students: number;
    average_grade: number;
    attendance_rate: number;
    top_students: TopStudent[];
    grade_distribution: GradeDistribution[];
}

export interface TopStudent {
    id: number;
    name: string;
    email: string;
    average_grade: number;
}

export interface GradeDistribution {
    training_id: number;
    training_name: string;
    students_count: number;
    average_grade: number;
}

export interface AttendanceRecord {
    student_id: number;
    status: 'present' | 'absent' | 'excused';
    reason?: string;
}
