import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import DashboardLayout from '../DashboardLayout';

let mockUser: any;

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    usePage: () => ({
        props: {
            app: { name: 'BioDigital' },
            auth: {
                user: mockUser,
            },
        },
    }),
}));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

describe('DashboardLayout - Contact messages navigation', () => {
    beforeEach(() => {
        mockUser = {
            id: 1,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            roles: [{ name: 'member' }],
            permissions: [],
        };
    });

    it('shows the contact messages link for a user with the "manage contacts" permission', () => {
        mockUser.permissions = ['manage contacts'];

        render(
            <DashboardLayout>
                <div>Content</div>
            </DashboardLayout>
        );

        const links = screen.getAllByText('Messages de contact');
        expect(links.length).toBeGreaterThan(0);
        expect(links[0].closest('a')).toHaveAttribute('href', '/contacts');
    });

    it('hides the contact messages link for a user without the permission', () => {
        render(
            <DashboardLayout>
                <div>Content</div>
            </DashboardLayout>
        );

        expect(screen.queryByText('Messages de contact')).not.toBeInTheDocument();
    });

    it('shows the contact messages link for an admin without the explicit permission', () => {
        mockUser.roles = [{ name: 'admin' }];

        render(
            <DashboardLayout>
                <div>Content</div>
            </DashboardLayout>
        );

        expect(screen.getAllByText('Messages de contact').length).toBeGreaterThan(0);
    });
});
