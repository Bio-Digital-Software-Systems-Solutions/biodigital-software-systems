import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout';

let pageProps: { auth: { user: unknown }; app: { name: string } } = {
    auth: { user: null },
    app: { name: 'BioDigital' },
};

vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: React.ReactNode }) => <>{children}</>,
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: pageProps }),
}));

vi.mock('@/Components/LandingPage/Footer', () => ({ default: () => null }));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

beforeEach(async () => {
    window.localStorage.clear();
    pageProps = { auth: { user: null }, app: { name: 'BioDigital' } };
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('GuestLayout navigation bar', () => {
    it('renders translated nav links and the language switcher', () => {
        render(<GuestLayout>content</GuestLayout>);

        expect(screen.getByText('À propos')).toBeInTheDocument();
        expect(screen.getByText('Activités')).toBeInTheDocument();
        expect(screen.getByText('Formations')).toBeInTheDocument();
        expect(screen.getByText('Contact')).toBeInTheDocument();

        // The language switcher is present (its toggle is exposed via aria-label).
        expect(screen.getAllByRole('button', { name: 'Français' }).length).toBeGreaterThan(0);
    });

    it('switches the guest navbar language from the switcher and persists it', async () => {
        const user = userEvent.setup();
        render(<GuestLayout>content</GuestLayout>);

        const [toggle] = screen.getAllByRole('button', { name: 'Français' });
        await user.click(toggle);
        await user.click(screen.getByRole('button', { name: /Deutsch/ }));

        expect(screen.getByText('Über uns')).toBeInTheDocument();
        expect(screen.getByText('Schulungen')).toBeInTheDocument();
        expect(screen.queryByText('À propos')).not.toBeInTheDocument();
        expect(window.localStorage.getItem('aig-app-language')).toBe('de');
    });

    it('shows the dashboard link for an authenticated user', () => {
        pageProps = { auth: { user: { id: 1 } }, app: { name: 'BioDigital' } };

        render(<GuestLayout>content</GuestLayout>);

        expect(screen.getByText('Tableau de bord')).toBeInTheDocument();
        expect(screen.queryByText('Se connecter')).not.toBeInTheDocument();
    });
});
