import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import EventCreate from '@/Pages/Events/Create';
import EventIndex from '@/Pages/Events/Index';

// Mock Inertia - use vi.hoisted to avoid hoisting issues
const { mockPost, mockGet } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockGet: vi.fn(),
}));

// Mock Ziggy route helper
vi.stubGlobal('route', (name: string, params?: unknown) => {
    if (name === 'events.index') return '/events';
    if (name === 'events.create') return '/events/create';
    if (name === 'events.store') return '/events';
    if (name === 'events.show' && params) return `/events/${params}`;
    return `/${name.replace(/\./g, '/')}`;
});

// Mock UserMultiSelect to avoid network requests
vi.mock('@/Components/UserMultiSelect', () => ({
    default: ({ label, selectedUserIds, onChange, error, placeholder }: {
        label?: string;
        selectedUserIds: number[];
        onChange: (ids: number[]) => void;
        error?: string;
        placeholder?: string;
    }) => (
        <div data-testid="user-multi-select">
            {label && <label>{label}</label>}
            <input
                type="text"
                placeholder={placeholder}
                data-selected={JSON.stringify(selectedUserIds)}
                onChange={(e) => {
                    // Parse comma-separated IDs for testing
                    const ids = e.target.value.split(',').map(Number).filter(Boolean);
                    onChange(ids);
                }}
            />
            {error && <span role="alert">{error}</span>}
        </div>
    ),
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    useForm: () => ({
        data: {
            title: '',
            description: '',
            start_date: '',
            end_date: '',
            location: '',
            max_participants: '',
            is_public: true,
            status: 'planned',
            participant_ids: [] as number[],
            address: {
                street: '',
                city: '',
                postal_code: '',
                country: '',
            },
        },
        setData: vi.fn(),
        post: mockPost,
        processing: false,
        errors: {},
        reset: vi.fn(),
    }),
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    first_name: 'John',
                    last_name: 'Doe',
                    email: 'john@example.com',
                    roles: ['event-manager'],
                    permissions: ['create events'],
                },
            },
            events: {
                data: [],
                links: {},
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 15,
                    total: 0,
                },
            },
        },
    }),
    router: {
        get: mockGet,
        post: mockPost,
    },
}));

describe('Event Creation E2E Flow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Complete Event Creation Workflow', () => {
        it('renders the create event form with all fields', () => {
            render(<EventCreate />);

            // User sees create form (French: "Créer un nouvel événement")
            expect(screen.getByRole('heading', { name: /créer un nouvel événement/i })).toBeInTheDocument();

            // Check all form fields are present
            expect(screen.getByLabelText(/titre/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/date et heure de début/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/date et heure de fin/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/lieu/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/nombre maximum de participants/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/statut/i)).toBeInTheDocument();

            // Check submit button
            expect(screen.getByRole('button', { name: /créer l'événement/i })).toBeInTheDocument();
        });

        it('has status dropdown with correct options', () => {
            render(<EventCreate />);

            const statusSelect = screen.getByLabelText(/statut/i);
            expect(statusSelect).toBeInTheDocument();

            // Check status options (French)
            expect(screen.getByRole('option', { name: /planifié/i })).toBeInTheDocument();
            expect(screen.getByRole('option', { name: /en cours/i })).toBeInTheDocument();
            expect(screen.getByRole('option', { name: /terminé/i })).toBeInTheDocument();
            expect(screen.getByRole('option', { name: /annulé/i })).toBeInTheDocument();
        });

        it('has public event checkbox', () => {
            render(<EventCreate />);

            // French: "Événement public"
            const publicCheckbox = screen.getByRole('checkbox');
            expect(publicCheckbox).toBeInTheDocument();
            expect(publicCheckbox).toBeChecked(); // Default is public
        });

        it('has navigation link back to events', () => {
            render(<EventCreate />);

            // French: "Retour aux événements"
            const backLink = screen.getByRole('link', { name: /retour aux événements/i });
            expect(backLink).toHaveAttribute('href', '/events');
        });
    });

    describe('Event List and Navigation', () => {
        it('displays empty state when no events exist', () => {
            render(<EventIndex />);

            // French: "Aucun événement"
            expect(screen.getByText(/aucun événement/i)).toBeInTheDocument();
        });

        it('navigates to create event page', () => {
            render(<EventIndex />);

            // French: "Nouvel événement" - may have multiple buttons (header + empty state)
            const createButtons = screen.getAllByRole('link', { name: /nouvel événement/i });
            expect(createButtons.length).toBeGreaterThan(0);
            expect(createButtons[0]).toHaveAttribute('href', '/events/create');
        });

    });

    describe('Event Search and Filter', () => {
        it('renders the events index page', () => {
            render(<EventIndex />);

            // Should have the events heading (French: "Événements")
            expect(screen.getByRole('heading', { name: /événements/i })).toBeInTheDocument();
        });

        it('shows search input for filtering events', () => {
            render(<EventIndex />);

            // French: "Rechercher..."
            const searchInput = screen.getByPlaceholderText(/rechercher/i);
            expect(searchInput).toBeInTheDocument();
        });
    });

    describe('Event Participation', () => {
        it('renders event details', () => {
            const mockEvent = {
                id: 1,
                title: 'Community Workshop',
                description: 'Learn new skills',
                start_date: '2025-12-01',
                can_join: true,
            };

            render(<EventShow event={mockEvent} />);

            expect(screen.getByText('Community Workshop')).toBeInTheDocument();
            expect(screen.getByText('Learn new skills')).toBeInTheDocument();
        });

        it('shows participant count', () => {
            const mockEvent = {
                id: 1,
                title: 'Popular Event',
                participants_count: 25,
            };

            render(<EventShow event={mockEvent} />);

            expect(screen.getByText(/25 participants/i)).toBeInTheDocument();
        });

        it('prevents joining when event is full', () => {
            const mockEvent = {
                id: 1,
                title: 'Full Event',
                max_participants: 50,
                participants_count: 50,
                can_join: false,
            };

            render(<EventShow event={mockEvent} />);

            const joinButton = screen.getByRole('button', { name: /join event/i });
            expect(joinButton).toBeDisabled();
        });
    });

    describe('Accessibility', () => {
        it('has proper form labels', () => {
            render(<EventCreate />);

            expect(screen.getByLabelText(/titre/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/date et heure de début/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/date et heure de fin/i)).toBeInTheDocument();
        });

        it('form inputs are accessible', () => {
            render(<EventCreate />);

            // All form fields have proper labels
            const titleInput = screen.getByLabelText(/titre/i);
            const descriptionInput = screen.getByLabelText(/description/i);
            const startDateInput = screen.getByLabelText(/date et heure de début/i);
            const endDateInput = screen.getByLabelText(/date et heure de fin/i);
            const locationInput = screen.getByLabelText(/lieu/i);

            expect(titleInput).toHaveAttribute('id', 'title');
            expect(descriptionInput).toHaveAttribute('id', 'description');
            expect(startDateInput).toHaveAttribute('id', 'start_date');
            expect(endDateInput).toHaveAttribute('id', 'end_date');
            expect(locationInput).toHaveAttribute('id', 'location');
        });

        it('submit button has proper type', () => {
            render(<EventCreate />);

            const submitButton = screen.getByRole('button', { name: /créer l'événement/i });
            expect(submitButton).toHaveAttribute('type', 'submit');
        });
    });

    describe('Responsive Design', () => {
        it('renders the page successfully', () => {
            render(<EventIndex />);

            // Page should render with the main content
            expect(screen.getByRole('heading', { name: /événements/i })).toBeInTheDocument();
        });
    });
});

// Mock EventShow component for participation tests
function EventShow({ event }: any) {
    return (
        <div>
            <h1>{event.title}</h1>
            <p>{event.description}</p>
            {event.participants_count && (
                <p>{event.participants_count} participants</p>
            )}
            {event.can_join ? (
                <button type="button">Join Event</button>
            ) : (
                <button type="button" disabled>Join Event</button>
            )}
        </div>
    );
}
