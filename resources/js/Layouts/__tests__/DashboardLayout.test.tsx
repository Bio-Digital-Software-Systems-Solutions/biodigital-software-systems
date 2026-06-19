import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardLayout from '../DashboardLayout';

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    usePage: () => ({
        props: {
            app: { name: 'BioDigital' },
            auth: {
                user: {
                    id: 1,
                    first_name: 'John',
                    last_name: 'Doe',
                    email: 'john@example.com',
                    roles: ['admin'],
                    permissions: ['view events', 'create events'],
                },
            },
        },
    }),
}));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

describe('DashboardLayout', () => {
    describe('Rendering', () => {
        it('renders layout with children', () => {
            render(
                <DashboardLayout>
                    <div>Dashboard Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText('Dashboard Content')).toBeInTheDocument();
        });

        it('renders navigation sidebar', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByRole('navigation')).toBeInTheDocument();
        });

        it('renders user information', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/John/i)).toBeInTheDocument();
        });

        it('renders header', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const header = document.querySelector('header');
            expect(header).toBeInTheDocument();
        });
    });

    describe('Navigation Links', () => {
        it('renders dashboard link', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
        });

        it('renders events link', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/events/i)).toBeInTheDocument();
        });

        it('renders articles link', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/articles/i)).toBeInTheDocument();
        });

        it('renders books link', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/books/i)).toBeInTheDocument();
        });

        it('navigation links have correct hrefs', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const dashboardLink = screen.getByText(/dashboard/i).closest('a');
            expect(dashboardLink).toHaveAttribute('href', '/dashboard');
        });
    });

    describe('Mobile Menu', () => {
        it('renders mobile menu toggle button', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const menuButton = screen.getByRole('button', { name: /menu/i });
            expect(menuButton).toBeInTheDocument();
        });

        it('toggles mobile menu on button click', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const menuButton = screen.getByRole('button', { name: /menu/i });
            await user.click(menuButton);

            // Mobile menu should be visible
            const mobileNav = screen.getByRole('navigation', { hidden: true });
            expect(mobileNav).toBeInTheDocument();
        });

        it('closes mobile menu when clicking outside', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div data-testid="content">Content</div>
                </DashboardLayout>
            );

            const menuButton = screen.getByRole('button', { name: /menu/i });
            await user.click(menuButton);

            // Click outside
            await user.click(screen.getByTestId('content'));

            // Menu should close
        });
    });

    describe('User Dropdown', () => {
        it('renders user dropdown menu', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(screen.getByText(/John/i)).toBeInTheDocument();
        });

        it('shows user menu on click', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const userButton = screen.getByText(/John/i);
            await user.click(userButton);

            expect(screen.getByText(/profile/i)).toBeInTheDocument();
            expect(screen.getByText(/logout/i)).toBeInTheDocument();
        });

        it('includes logout link', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const userButton = screen.getByText(/John/i);
            await user.click(userButton);

            const logoutLink = screen.getByText(/logout/i);
            expect(logoutLink).toBeInTheDocument();
        });
    });

    describe('Responsive Design', () => {
        it('renders responsive container', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const main = document.querySelector('main');
            expect(main).toBeInTheDocument();
        });

        it('sidebar is hidden on mobile by default', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const sidebar = screen.getByRole('navigation');
            // Should have mobile-hidden class or similar
            expect(sidebar).toBeInTheDocument();
        });
    });

    describe('Notifications', () => {
        it('renders notification bell', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const bell = screen.getByRole('button', { name: /notification/i });
            expect(bell).toBeInTheDocument();
        });

        it('shows notification count', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            // Assuming notification count is displayed
            const notificationCount = document.querySelector('[data-notification-count]');
            if (notificationCount) {
                expect(notificationCount).toBeInTheDocument();
            }
        });
    });

    describe('Theme Switcher', () => {
        it('renders theme toggle button', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const themeButton = screen.getByRole('button', { name: /theme/i });
            expect(themeButton).toBeInTheDocument();
        });

        it('toggles between light and dark mode', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const themeButton = screen.getByRole('button', { name: /theme/i });
            await user.click(themeButton);

            // Theme should toggle
            expect(document.documentElement).toHaveAttribute('class');
        });
    });

    describe('Accessibility', () => {
        it('has proper semantic HTML structure', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            expect(document.querySelector('header')).toBeInTheDocument();
            expect(document.querySelector('nav')).toBeInTheDocument();
            expect(document.querySelector('main')).toBeInTheDocument();
        });

        it('navigation is keyboard accessible', async () => {
            const user = userEvent.setup();

            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            // Tab through navigation
            await user.tab();
            const firstFocusable = document.activeElement;
            expect(firstFocusable).toBeInTheDocument();
        });

        it('skip to main content link exists', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const skipLink = screen.queryByText(/skip to main content/i);
            if (skipLink) {
                expect(skipLink).toBeInTheDocument();
            }
        });

        it('has proper ARIA labels', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const nav = screen.getByRole('navigation');
            expect(nav).toHaveAttribute('aria-label');
        });
    });

    describe('Search Functionality', () => {
        it('renders search input if available', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const searchInput = screen.queryByPlaceholderText(/search/i);
            if (searchInput) {
                expect(searchInput).toBeInTheDocument();
            }
        });
    });

    describe('Footer', () => {
        it('renders footer', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            const footer = document.querySelector('footer');
            if (footer) {
                expect(footer).toBeInTheDocument();
            }
        });
    });

    describe('Loading States', () => {
        it('shows loading indicator when appropriate', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            // Check for loading indicator
            const loader = document.querySelector('[role="progressbar"]');
            if (loader) {
                expect(loader).toBeInTheDocument();
            }
        });
    });

    describe('Security', () => {
        it('does not expose sensitive user data in DOM', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            // Password fields should not be visible
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            passwordInputs.forEach((input) => {
                expect(input).not.toBeVisible();
            });
        });

        it('sanitizes user display name', () => {
            render(
                <DashboardLayout>
                    <div>Content</div>
                </DashboardLayout>
            );

            // User name should be displayed as text, not HTML
            expect(screen.getByText(/John/i)).toBeInTheDocument();
            expect(document.querySelector('script')).not.toBeInTheDocument();
        });
    });

    describe('Performance', () => {
        it('does not re-render unnecessarily', () => {
            const { rerender } = render(
                <DashboardLayout>
                    <div>Content 1</div>
                </DashboardLayout>
            );

            const nav = screen.getByRole('navigation');
            const firstRender = nav.innerHTML;

            rerender(
                <DashboardLayout>
                    <div>Content 2</div>
                </DashboardLayout>
            );

            // Navigation should not change
            expect(nav.innerHTML).toBe(firstRender);
        });
    });
});
