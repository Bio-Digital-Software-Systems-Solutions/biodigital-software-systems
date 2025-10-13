import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import axios from 'axios';
import Show from '../Show';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    router: {
        visit: vi.fn(),
    },
}));

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

// Mock Components
vi.mock('@/Components/ui/button', () => ({
    Button: ({ children, onClick, disabled }: any) => (
        <button onClick={onClick} disabled={disabled} data-testid="button">
            {children}
        </button>
    ),
}));

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
    ArrowLeft: () => <div data-testid="arrow-left-icon" />,
    Edit: () => <div data-testid="edit-icon" />,
    Calendar: () => <div data-testid="calendar-icon" />,
    Clock: () => <div data-testid="clock-icon" />,
    MapPin: () => <div data-testid="mappin-icon" />,
    Users: () => <div data-testid="users-icon" />,
    UserCheck: () => <div data-testid="usercheck-icon" />,
    UserX: () => <div data-testid="userx-icon" />,
    ChevronLeft: () => <div data-testid="chevronleft-icon" />,
    ChevronRight: () => <div data-testid="chevronright-icon" />,
}));

// Mock sonner toast
vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

// Mock logger
vi.mock('@/utils/logger', () => ({
    apiLogger: {
        error: vi.fn(),
    },
}));

// Mock route helper
global.route = vi.fn((name: string, params?: any) => {
    return `/route/${name}${params ? `/${params}` : ''}`;
}) as any;

describe('Show Component', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.get.mockResolvedValue({ data: [] });
    });

    const mockTrainingClass = {
        id: 1,
        uuid: 'test-uuid-123',
        training_id: 1,
        training_name: 'Formation Test',
        teacher_id: 1,
        teacher_name: 'John Doe',
        date: '2025-10-15',
        start_time: '09:00',
        end_time: '12:00',
        room: 'Salle A',
        max_students: 20,
        notes: 'Notes de test',
        students_count: 10,
        status: 'À venir',
    };

    const mockStudents = [
        {
            id: 1,
            name: 'Student One',
            email: 'student1@example.com',
            grade: 85,
            progress: 75,
            attendance_rate: 90,
            attendance_status: 'present' as const,
            attendance_reason: null,
        },
        {
            id: 2,
            name: 'Student Two',
            email: 'student2@example.com',
            grade: 90,
            progress: 80,
            attendance_rate: 95,
            attendance_status: null,
            attendance_reason: null,
        },
    ];

    describe('Rendering', () => {
        it('renders training class information correctly', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check if training name is displayed
            expect(screen.getByText('Formation Test')).toBeInTheDocument();

            // Check if teacher name is displayed
            expect(screen.getByText('John Doe')).toBeInTheDocument();

            // Check if room is displayed
            expect(screen.getByText('Salle A')).toBeInTheDocument();

            // Check if status is displayed
            expect(screen.getByText('À venir')).toBeInTheDocument();
        });

        it('renders statistics section with correct student count', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check statistics title
            expect(screen.getByText('Statistiques de Présence')).toBeInTheDocument();

            // Check total students count
            expect(screen.getByText('2')).toBeInTheDocument(); // Total students
        });

        it('renders students list', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check if students are listed
            expect(screen.getByText('Student One')).toBeInTheDocument();
            expect(screen.getByText('Student Two')).toBeInTheDocument();

            // Check emails
            expect(screen.getByText('student1@example.com')).toBeInTheDocument();
            expect(screen.getByText('student2@example.com')).toBeInTheDocument();
        });

        it('renders attendance controls for each student', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check for radio buttons (present, absent, excused)
            const radioButtons = screen.getAllByRole('radio');
            expect(radioButtons.length).toBeGreaterThan(0);

            // Should have at least 6 radio buttons (3 per student)
            expect(radioButtons.length).toBeGreaterThanOrEqual(6);
        });

        it('renders save attendance button', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            expect(screen.getByText('Enregistrer les présences')).toBeInTheDocument();
        });

        it('renders back link to training classes', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            const backLink = screen.getByText('Retour aux classes');
            expect(backLink).toBeInTheDocument();
        });

        it('renders when notes are provided', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            expect(screen.getByText('Notes')).toBeInTheDocument();
            expect(screen.getByText('Notes de test')).toBeInTheDocument();
        });

        it('renders without students', () => {
            render(<Show class={mockTrainingClass} students={[]} />);

            expect(screen.getByText('Aucun étudiant inscrit à cette formation')).toBeInTheDocument();
        });
    });

    describe('Attendance Display', () => {
        it('displays attendance rate for students', () => {
            // Use students without attendance status to display their base attendance_rate
            const studentsWithoutAttendance = [
                {
                    id: 1,
                    name: 'Student One',
                    email: 'student1@example.com',
                    grade: 85,
                    progress: 75,
                    attendance_rate: 90,
                    attendance_status: null,
                    attendance_reason: null,
                },
                {
                    id: 2,
                    name: 'Student Two',
                    email: 'student2@example.com',
                    grade: 90,
                    progress: 80,
                    attendance_rate: 95,
                    attendance_status: null,
                    attendance_reason: null,
                },
            ];

            render(<Show class={mockTrainingClass} students={studentsWithoutAttendance} />);

            // Check if attendance rates are displayed
            expect(screen.getByText('90%')).toBeInTheDocument();
            expect(screen.getByText('95%')).toBeInTheDocument();
        });

        it('marks present student correctly', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Find the radio button for present status for first student
            const radios = screen.getAllByRole('radio');
            const presentRadio = radios.find(
                (radio: HTMLInputElement) => radio.name === 'attendance-1' && radio.checked
            );

            expect(presentRadio).toBeTruthy();
        });

        it('shows reason input field for each student', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            const reasonInputs = screen.getAllByPlaceholderText('Raison (si absent)');
            expect(reasonInputs).toHaveLength(2); // One for each student
        });
    });

    describe('Statistics', () => {
        it('calculates and displays attendance statistics correctly', () => {
            const studentsWithAttendance = [
                { ...mockStudents[0], attendance_status: 'present' as const },
                { ...mockStudents[1], attendance_status: 'absent' as const },
            ];

            render(<Show class={mockTrainingClass} students={studentsWithAttendance} />);

            // Check Total
            expect(screen.getByText('Total')).toBeInTheDocument();

            // Check Présents label
            expect(screen.getByText('Présents')).toBeInTheDocument();

            // Check Absents label
            expect(screen.getByText('Absents')).toBeInTheDocument();

            // Check Excusés label
            expect(screen.getByText('Excusés')).toBeInTheDocument();
        });
    });

    describe('Accessibility', () => {
        it('uses semantic HTML elements', () => {
            const { container } = render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check for table
            const table = container.querySelector('table');
            expect(table).toBeInTheDocument();

            // Check for thead
            const thead = container.querySelector('thead');
            expect(thead).toBeInTheDocument();

            // Check for tbody
            const tbody = container.querySelector('tbody');
            expect(tbody).toBeInTheDocument();
        });

        it('has proper table headers', () => {
            render(<Show class={mockTrainingClass} students={mockStudents} />);

            expect(screen.getByText('Étudiant')).toBeInTheDocument();
            expect(screen.getByText('Email')).toBeInTheDocument();
            expect(screen.getByText('Présent')).toBeInTheDocument();
            expect(screen.getByText('Absent')).toBeInTheDocument();
            expect(screen.getByText('Excusé')).toBeInTheDocument();
            expect(screen.getByText('Raison')).toBeInTheDocument();
            expect(screen.getByText('Taux de présence')).toBeInTheDocument();
        });
    });

    describe('Dark Mode Support', () => {
        it('renders with dark mode classes', () => {
            const { container } = render(<Show class={mockTrainingClass} students={mockStudents} />);

            // Check for dark mode classes
            const darkModeElements = container.querySelectorAll('.dark\\:bg-gray-800, .dark\\:text-white');
            expect(darkModeElements.length).toBeGreaterThan(0);
        });
    });
});
