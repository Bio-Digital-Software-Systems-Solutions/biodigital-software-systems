import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import AppointmentCreate from '@/Pages/Appointments/Create';

// Mock Inertia
const mockUsePage = jest.fn();
const mockUseForm = jest.fn();
const mockPost = jest.fn();

jest.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href, className, ...props }: any) => (
        <a href={href} className={className} {...props}>
            {children}
        </a>
    ),
    router: {
        get: jest.fn(),
        post: jest.fn(),
    },
    usePage: () => mockUsePage(),
    useForm: () => mockUseForm(),
    route: (name: string, params?: any) => `/${name.replace('.', '/')}${params ? `/${params}` : ''}`,
}));

// Mock Layouts
jest.mock('@/Layouts/DashboardLayout', () => {
    return function DashboardLayout({ children }: { children: React.ReactNode }) {
        return <div data-testid="dashboard-layout">{children}</div>;
    };
});

// Mock sonner
jest.mock('sonner', () => ({
    toast: {
        success: jest.fn(),
        error: jest.fn(),
    },
}));

// Mock date-fns
jest.mock('date-fns', () => ({
    format: jest.fn((date, formatStr) => '2025-11-15T09:00'),
    addDays: jest.fn((date, days) => new Date()),
    addHours: jest.fn((date, hours) => new Date()),
    startOfHour: jest.fn((date) => new Date()),
}));

describe('AppointmentCreate', () => {
    const mockUsers = [
        { id: 2, name: 'John Doe', email: 'john@example.com' },
        { id: 3, name: 'Jane Smith', email: 'jane@example.com' },
    ];

    const mockTypes = ['individual', 'group', 'consultation', 'meeting'];

    const defaultFormData = {
        title: '',
        description: '',
        start_datetime: '2025-11-15T09:00',
        end_datetime: '2025-11-15T10:00',
        location: '',
        type: 'individual',
        visibility: 'private',
        participant_ids: [],
    };

    beforeEach(() => {
        jest.clearAllMocks();

        mockUseForm.mockReturnValue({
            data: defaultFormData,
            setData: jest.fn(),
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });
    });

    it('should render without preselected participants', () => {
        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {},
            preselectedParticipants: [],
        });

        render(<AppointmentCreate />);

        expect(screen.getByText('Nouveau rendez-vous')).toBeInTheDocument();
        expect(screen.getByText('Utilisateurs disponibles')).toBeInTheDocument();
        expect(screen.queryByText('Participants sélectionnés')).not.toBeInTheDocument();
    });

    it('should render with preselected participants from agenda click', () => {
        const preselectedUser = { id: 2, name: 'John Doe', email: 'john@example.com' };

        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {
                date: '2025-11-15',
                time: '14:30',
                participant_ids: [2],
            },
            preselectedParticipants: [preselectedUser],
        });

        const mockSetData = jest.fn();
        mockUseForm.mockReturnValue({
            data: {
                ...defaultFormData,
                participant_ids: [2],
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });

        render(<AppointmentCreate />);

        expect(screen.getByText('Nouveau rendez-vous')).toBeInTheDocument();
        expect(screen.getByText('Participants sélectionnés (1)')).toBeInTheDocument();
        expect(screen.getByText('John Doe')).toBeInTheDocument();
    });

    it('should render with multiple preselected participants', () => {
        const preselectedUsers = [
            { id: 2, name: 'John Doe', email: 'john@example.com' },
            { id: 3, name: 'Jane Smith', email: 'jane@example.com' },
        ];

        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {
                participant_ids: [2, 3],
            },
            preselectedParticipants: preselectedUsers,
        });

        const mockSetData = jest.fn();
        mockUseForm.mockReturnValue({
            data: {
                ...defaultFormData,
                participant_ids: [2, 3],
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });

        render(<AppointmentCreate />);

        expect(screen.getByText('Participants sélectionnés (2)')).toBeInTheDocument();
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    });

    it('should initialize form with preselected participant IDs', () => {
        const preselectedUser = { id: 2, name: 'John Doe', email: 'john@example.com' };

        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {
                participant_ids: [2],
            },
            preselectedParticipants: [preselectedUser],
        });

        const mockSetData = jest.fn();
        mockUseForm.mockReturnValue({
            data: {
                ...defaultFormData,
                participant_ids: [2],
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });

        render(<AppointmentCreate />);

        // Verify that the form was initialized with the participant IDs
        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                participant_ids: [2],
            })
        );
    });

    it('should allow removing preselected participants', () => {
        const preselectedUser = { id: 2, name: 'John Doe', email: 'john@example.com' };

        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {
                participant_ids: [2],
            },
            preselectedParticipants: [preselectedUser],
        });

        const mockSetData = jest.fn();
        mockUseForm.mockReturnValue({
            data: {
                ...defaultFormData,
                participant_ids: [2],
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });

        render(<AppointmentCreate />);

        // Find and click the remove button for John Doe
        const removeButton = screen.getByRole('button', { name: /remove/i });
        fireEvent.click(removeButton);

        // Should call setData to remove the participant
        expect(mockSetData).toHaveBeenCalledWith('participant_ids', []);
    });

    it('should prefill date and time from query parameters', () => {
        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {
                date: '2025-11-15',
                time: '14:30',
            },
            preselectedParticipants: [],
        });

        const mockSetData = jest.fn();
        mockUseForm.mockReturnValue({
            data: {
                ...defaultFormData,
                start_datetime: '2025-11-15T14:30',
                end_datetime: '2025-11-15T15:30',
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
            errors: {},
            transform: jest.fn(),
        });

        render(<AppointmentCreate />);

        expect(screen.getByText('Nouveau rendez-vous')).toBeInTheDocument();
        // Form should be initialized with the correct datetime
        expect(mockUseForm).toHaveBeenCalledWith(
            expect.objectContaining({
                start_datetime: expect.stringContaining('2025-11-15T'),
            })
        );
    });

    it('should handle empty preselected participants gracefully', () => {
        mockUsePage.mockReturnValue({
            users: mockUsers,
            types: mockTypes,
            prefilledData: {},
            preselectedParticipants: undefined, // Test undefined case
        });

        render(<AppointmentCreate />);

        expect(screen.getByText('Nouveau rendez-vous')).toBeInTheDocument();
        expect(screen.queryByText('Participants sélectionnés')).not.toBeInTheDocument();
    });
});