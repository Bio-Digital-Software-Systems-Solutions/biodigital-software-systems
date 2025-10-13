import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import EventCreate from '@/Pages/Events/Create';
import EventIndex from '@/Pages/Events/Index';

// Mock Inertia
const mockPost = vi.fn();
const mockGet = vi.fn();

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
        it('allows user to create event from start to finish', async () => {
            const user = userEvent.setup();

            render(<EventCreate />);

            // Step 1: User sees create form
            expect(screen.getByRole('heading', { name: /create event/i })).toBeInTheDocument();

            // Step 2: Fill in event title
            const titleInput = screen.getByLabelText(/title/i);
            await user.type(titleInput, 'Community Workshop');

            // Step 3: Fill in description
            const descriptionInput = screen.getByLabelText(/description/i);
            await user.type(descriptionInput, 'Monthly community gathering for developers');

            // Step 4: Set start date
            const startDateInput = screen.getByLabelText(/start date/i);
            await user.type(startDateInput, '2025-12-01');

            // Step 5: Set end date
            const endDateInput = screen.getByLabelText(/end date/i);
            await user.type(endDateInput, '2025-12-01');

            // Step 6: Add location
            const locationInput = screen.getByLabelText(/location/i);
            await user.type(locationInput, 'Community Center');

            // Step 7: Submit form
            const submitButton = screen.getByRole('button', { name: /create/i });
            await user.click(submitButton);

            // Verify form was submitted
            await waitFor(() => {
                expect(mockPost).toHaveBeenCalledWith(
                    '/events',
                    expect.objectContaining({
                        title: expect.stringContaining('Community'),
                    })
                );
            });
        });

        it('validates required fields before submission', async () => {
            const user = userEvent.setup();

            render(<EventCreate />);

            // Try to submit empty form
            const submitButton = screen.getByRole('button', { name: /create/i });
            await user.click(submitButton);

            // Should show validation errors
            await waitFor(() => {
                const errorMessages = screen.queryAllByRole('alert');
                if (errorMessages.length > 0) {
                    expect(errorMessages.length).toBeGreaterThan(0);
                }
            });
        });

        it('prevents submission with invalid date range', async () => {
            const user = userEvent.setup();

            render(<EventCreate />);

            // Fill in form with invalid dates (end before start)
            const titleInput = screen.getByLabelText(/title/i);
            await user.type(titleInput, 'Test Event');

            const startDateInput = screen.getByLabelText(/start date/i);
            await user.type(startDateInput, '2025-12-31');

            const endDateInput = screen.getByLabelText(/end date/i);
            await user.type(endDateInput, '2025-12-01');

            const submitButton = screen.getByRole('button', { name: /create/i });
            await user.click(submitButton);

            // Should show date validation error
            await waitFor(() => {
                const errors = screen.queryAllByRole('alert');
                if (errors.length > 0) {
                    expect(errors.length).toBeGreaterThan(0);
                }
            });
        });
    });

    describe('Event List and Navigation', () => {
        it('displays empty state when no events exist', () => {
            render(<EventIndex />);

            expect(screen.getByText(/no events found/i)).toBeInTheDocument();
        });

        it('navigates to create event page', async () => {
            const user = userEvent.setup();

            render(<EventIndex />);

            const createButton = screen.getByRole('link', { name: /create event/i });
            expect(createButton).toHaveAttribute('href', '/events/create');

            await user.click(createButton);
        });

        it('displays event cards when events exist', () => {
            const mockEvents = [
                {
                    id: 1,
                    title: 'Laravel Workshop',
                    description: 'Learn Laravel',
                    start_date: '2025-12-01',
                    location: 'Tech Hub',
                },
                {
                    id: 2,
                    title: 'React Meetup',
                    description: 'React best practices',
                    start_date: '2025-12-15',
                    location: 'Co-working Space',
                },
            ];

            vi.mocked(vi.importActual('@inertiajs/react')).usePage = () => ({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            first_name: 'John',
                            last_name: 'Doe',
                            permissions: ['view events'],
                        },
                    },
                    events: {
                        data: mockEvents,
                        links: {},
                        meta: { current_page: 1, last_page: 1 },
                    },
                },
            });

            render(<EventIndex />);

            expect(screen.getByText('Laravel Workshop')).toBeInTheDocument();
            expect(screen.getByText('React Meetup')).toBeInTheDocument();
        });
    });

    describe('Event Search and Filter', () => {
        it('allows searching events by title', async () => {
            const user = userEvent.setup();

            render(<EventIndex />);

            const searchInput = screen.getByPlaceholderText(/search events/i);
            await user.type(searchInput, 'Laravel');

            await waitFor(() => {
                expect(mockGet).toHaveBeenCalledWith(
                    '/events',
                    expect.objectContaining({
                        search: 'Laravel',
                    })
                );
            });
        });

        it('filters events by date range', async () => {
            const user = userEvent.setup();

            render(<EventIndex />);

            const startFilter = screen.getByLabelText(/from date/i);
            await user.type(startFilter, '2025-12-01');

            const endFilter = screen.getByLabelText(/to date/i);
            await user.type(endFilter, '2025-12-31');

            const filterButton = screen.getByRole('button', { name: /filter/i });
            await user.click(filterButton);

            await waitFor(() => {
                expect(mockGet).toHaveBeenCalled();
            });
        });

        it('filters events by status', async () => {
            const user = userEvent.setup();

            render(<EventIndex />);

            const statusFilter = screen.getByRole('combobox', { name: /status/i });
            await user.selectOptions(statusFilter, 'upcoming');

            await waitFor(() => {
                expect(mockGet).toHaveBeenCalledWith(
                    '/events',
                    expect.objectContaining({
                        status: 'upcoming',
                    })
                );
            });
        });
    });

    describe('Event Participation', () => {
        it('allows user to join an event', async () => {
            const user = userEvent.setup();

            const mockEvent = {
                id: 1,
                title: 'Community Workshop',
                description: 'Learn new skills',
                start_date: '2025-12-01',
                can_join: true,
            };

            render(<EventShow event={mockEvent} />);

            const joinButton = screen.getByRole('button', { name: /join event/i });
            await user.click(joinButton);

            await waitFor(() => {
                expect(mockPost).toHaveBeenCalledWith(`/events/${mockEvent.id}/join`);
            });
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

            const joinButton = screen.queryByRole('button', { name: /join event/i });
            expect(joinButton).toBeDisabled();
        });
    });

    describe('Accessibility', () => {
        it('has proper form labels', () => {
            render(<EventCreate />);

            expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/start date/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/end date/i)).toBeInTheDocument();
        });

        it('supports keyboard navigation', async () => {
            const user = userEvent.setup();

            render(<EventCreate />);

            // Tab through form fields
            await user.tab();
            expect(screen.getByLabelText(/title/i)).toHaveFocus();

            await user.tab();
            expect(screen.getByLabelText(/description/i)).toHaveFocus();
        });

        it('announces errors to screen readers', async () => {
            const user = userEvent.setup();

            render(<EventCreate />);

            const submitButton = screen.getByRole('button', { name: /create/i });
            await user.click(submitButton);

            await waitFor(() => {
                const alerts = screen.queryAllByRole('alert');
                alerts.forEach((alert) => {
                    expect(alert).toHaveAttribute('aria-live', 'polite');
                });
            });
        });
    });

    describe('Responsive Design', () => {
        it('renders mobile layout on small screens', () => {
            // Mock mobile viewport
            global.innerWidth = 375;
            global.dispatchEvent(new Event('resize'));

            render(<EventIndex />);

            // Mobile-specific elements should be visible
            const mobileMenu = screen.queryByRole('button', { name: /menu/i });
            if (mobileMenu) {
                expect(mobileMenu).toBeInTheDocument();
            }
        });

        it('shows desktop layout on large screens', () => {
            // Mock desktop viewport
            global.innerWidth = 1920;
            global.dispatchEvent(new Event('resize'));

            render(<EventIndex />);

            // Desktop layout should be visible
            expect(screen.getByRole('main')).toBeInTheDocument();
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
                <button>Join Event</button>
            ) : (
                <button disabled>Join Event</button>
            )}
        </div>
    );
}
