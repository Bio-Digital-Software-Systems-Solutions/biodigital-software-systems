import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import GuestNavbar from '@/Components/LandingPage/GuestNavbar';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('GuestNavbar', () => {
    it('renders the translated anchor links', () => {
        render(<GuestNavbar isAuthenticated={false} />);

        expect(screen.getByRole('link', { name: 'Services' })).toHaveAttribute('href', '#services');
        expect(screen.getByRole('link', { name: 'Formations' })).toHaveAttribute('href', '#trainings');
        expect(screen.getByRole('link', { name: 'Déroulé' })).toHaveAttribute('href', '#process');
        expect(screen.getByRole('link', { name: 'Contact' })).toHaveAttribute('href', '#contact');
    });

    it('shows login and the intro-call CTA for guests', () => {
        render(<GuestNavbar isAuthenticated={false} />);

        expect(screen.getByRole('link', { name: 'Se connecter' })).toHaveAttribute('href', '/login');
        expect(screen.getByRole('link', { name: 'Échange' })).toHaveAttribute('href', '#contact');
        expect(screen.queryByRole('link', { name: 'Tableau de bord' })).not.toBeInTheDocument();
    });

    it('shows the dashboard button for authenticated users', () => {
        render(<GuestNavbar isAuthenticated={true} />);

        expect(screen.getByRole('link', { name: 'Tableau de bord' })).toHaveAttribute('href', '/dashboard');
        expect(screen.queryByRole('link', { name: 'Se connecter' })).not.toBeInTheDocument();
    });

    it('toggles the mobile menu, revealing the register link', async () => {
        const user = userEvent.setup();
        render(<GuestNavbar isAuthenticated={false} />);

        expect(screen.queryByRole('link', { name: "S'inscrire" })).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Ouvrir/fermer le menu' }));

        expect(screen.getByRole('link', { name: "S'inscrire" })).toHaveAttribute('href', '/register');
    });
});
