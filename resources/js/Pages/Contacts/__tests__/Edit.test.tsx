import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import Edit from '../Edit';

// Mock Inertia (useForm reste l'implémentation réelle)
vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react');
    return {
        ...actual,
        Head: ({ title }: { title: string }) => <title>{title}</title>,
        Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    };
});

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

(global as unknown as { route: (name: string, params?: unknown) => string }).route = (
    name: string,
    params?: unknown
) => `/${name.replace(/\./g, '/')}/${params ?? ''}`;

const contact = {
    id: 7,
    uuid: 'a1b2c3d4-0000-0000-0000-000000000007',
    name: 'Jane Visitor',
    email: 'jane@example.com',
    phone: null,
    subject: 'Demande de renseignements',
    message: 'Bonjour, je souhaite en savoir plus.',
    status: 'new' as const,
    read_at: null,
    created_at: '2026-07-30T10:00:00.000000Z',
    assigned_to: null,
};

const users = [
    { id: 1, name: 'Admin One' },
    { id: 2, name: 'Admin Two' },
];

describe('Contacts/Edit', () => {
    it('renders the contact summary', () => {
        render(<Edit contact={contact} users={users} />);

        expect(screen.getByText('Demande de renseignements')).toBeInTheDocument();
        expect(screen.getByText('Jane Visitor')).toBeInTheDocument();
        expect(screen.getByText('jane@example.com')).toBeInTheDocument();
    });

    it('renders the status and assignment fields', () => {
        render(<Edit contact={contact} users={users} />);

        expect(screen.getByText('Statut')).toBeInTheDocument();
        expect(screen.getByText('Assigné à')).toBeInTheDocument();
    });

    it('renders the submit and cancel actions', () => {
        render(<Edit contact={contact} users={users} />);

        expect(screen.getByRole('button', { name: 'Enregistrer' })).toBeInTheDocument();
        const cancelLink = screen.getByText('Annuler').closest('a');
        expect(cancelLink).toHaveAttribute('href', `/contacts/show/${contact.uuid}`);
    });

    it('links back to the contact details page using the uuid', () => {
        render(<Edit contact={contact} users={users} />);

        const backLink = screen.getByText('Retour').closest('a');
        expect(backLink).toHaveAttribute('href', `/contacts/show/${contact.uuid}`);
    });
});
