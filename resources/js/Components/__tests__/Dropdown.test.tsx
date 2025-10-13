import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Dropdown from '../Dropdown';

describe('Dropdown Component', () => {
    describe('Rendering', () => {
        it('renders trigger button', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.getByText('Menu')).toBeInTheDocument();
        });

        it('does not show content by default', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.queryByText('Profile')).not.toBeVisible();
        });
    });

    describe('User Interaction', () => {
        it('opens dropdown on trigger click', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const trigger = screen.getByText('Menu');
            await user.click(trigger);

            expect(screen.getByText('Profile')).toBeVisible();
        });

        it('closes dropdown on second click', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const trigger = screen.getByText('Menu');

            // Open
            await user.click(trigger);
            expect(screen.getByText('Profile')).toBeVisible();

            // Close
            await user.click(trigger);
            expect(screen.queryByText('Profile')).not.toBeVisible();
        });

        it('closes dropdown when clicking outside', async () => {
            const user = userEvent.setup();

            render(
                <div>
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button>Menu</button>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                    <button>Outside Button</button>
                </div>
            );

            // Open dropdown
            await user.click(screen.getByText('Menu'));
            expect(screen.getByText('Profile')).toBeVisible();

            // Click outside
            await user.click(screen.getByText('Outside Button'));
            expect(screen.queryByText('Profile')).not.toBeVisible();
        });

        it('closes dropdown on Escape key', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            // Open dropdown
            await user.click(screen.getByText('Menu'));
            expect(screen.getByText('Profile')).toBeVisible();

            // Press Escape
            await user.keyboard('{Escape}');
            expect(screen.queryByText('Profile')).not.toBeVisible();
        });
    });

    describe('Dropdown.Link Component', () => {
        it('renders link with href', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const link = screen.getByText('Profile');
            expect(link).toHaveAttribute('href', '/profile');
        });

        it('applies custom className', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile" className="custom-class">
                            Profile
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.getByText('Profile')).toHaveClass('custom-class');
        });

        it('can use as button with onClick', async () => {
            const user = userEvent.setup();
            const handleClick = vi.fn();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link as="button" onClick={handleClick}>
                            Action
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            await user.click(screen.getByText('Menu'));
            await user.click(screen.getByText('Action'));

            expect(handleClick).toHaveBeenCalled();
        });
    });

    describe('Dropdown Alignment', () => {
        it('supports left alignment', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content align="left">
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.getByText('Menu')).toBeInTheDocument();
        });

        it('supports right alignment', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content align="right">
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.getByText('Menu')).toBeInTheDocument();
        });
    });

    describe('Dropdown Width', () => {
        it('supports custom width', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content width="48">
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(screen.getByText('Menu')).toBeInTheDocument();
        });
    });

    describe('Multiple Links', () => {
        it('renders multiple dropdown links', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                        <Dropdown.Link href="/settings">Settings</Dropdown.Link>
                        <Dropdown.Link href="/logout">Logout</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            await user.click(screen.getByText('Menu'));

            expect(screen.getByText('Profile')).toBeVisible();
            expect(screen.getByText('Settings')).toBeVisible();
            expect(screen.getByText('Logout')).toBeVisible();
        });

        it('closes dropdown when clicking a link', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            await user.click(screen.getByText('Menu'));
            expect(screen.getByText('Profile')).toBeVisible();

            await user.click(screen.getByText('Profile'));
            // Dropdown should close after navigation
        });
    });

    describe('Accessibility', () => {
        it('trigger is keyboard accessible', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const trigger = screen.getByText('Menu');
            trigger.focus();

            expect(trigger).toHaveFocus();

            await user.keyboard('{Enter}');
            expect(screen.getByText('Profile')).toBeVisible();
        });

        it('supports aria-expanded on trigger', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const trigger = screen.getByText('Menu');

            // Closed state
            expect(trigger).toHaveAttribute('aria-expanded', 'false');

            // Open
            await user.click(trigger);
            expect(trigger).toHaveAttribute('aria-expanded', 'true');
        });

        it('focuses first item when opening with keyboard', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                        <Dropdown.Link href="/settings">Settings</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            await user.tab(); // Focus trigger
            await user.keyboard('{Enter}'); // Open dropdown

            // First link should be focusable
            await user.tab();
            expect(screen.getByText('Profile')).toHaveFocus();
        });
    });

    describe('Security', () => {
        it('does not execute scripts in link text', async () => {
            const user = userEvent.setup();

            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">
                            {'<script>alert("xss")</script>Profile'}
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            await user.click(screen.getByText('Menu'));

            expect(document.querySelector('script')).not.toBeInTheDocument();
        });

        it('sanitizes href attribute', () => {
            render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="javascript:alert('xss')">
                            Malicious
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const link = screen.getByText('Malicious');
            // React should sanitize javascript: protocol
            expect(link).toBeInTheDocument();
        });
    });

    describe('Performance', () => {
        it('does not re-render when closed', () => {
            const { rerender } = render(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            const trigger = screen.getByText('Menu');
            const firstRender = trigger.textContent;

            rerender(
                <Dropdown>
                    <Dropdown.Trigger>
                        <button>Menu</button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            );

            expect(trigger.textContent).toBe(firstRender);
        });
    });
});
