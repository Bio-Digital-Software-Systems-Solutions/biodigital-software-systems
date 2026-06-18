import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import Welcome from '@/Pages/Welcome';

// Mock the heavy homepage sub-sections so this test focuses on the navigation bar.
vi.mock('@/Components/LandingPage/HeroSlider', () => ({ default: () => <div data-testid="hero" /> }));
vi.mock('@/Components/LandingPage/AboutSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/ServicesSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/TrainingBrowseSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/ProcessSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/ContactSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/SiteFooter', () => ({ default: () => null }));

vi.mock('sonner', () => ({ Toaster: () => null }));

vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: React.ReactNode }) => <>{children}</>,
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: { app: { name: 'BioDigital' } } }),
}));

(global as unknown as { route: (name: string) => string }).route = (name: string) => `/${name}`;

const renderWelcome = (auth: { user: unknown }) => render(<Welcome auth={auth as never} />);

beforeEach(async () => {
    window.localStorage.clear();
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('Welcome navigation bar', () => {
    it('renders the redesigned navigation links and guest CTA in French', () => {
        renderWelcome({ user: null });

        expect(screen.getByRole('link', { name: 'À propos' })).toHaveAttribute('href', '#about');
        expect(screen.getByRole('link', { name: 'Services' })).toHaveAttribute('href', '#services');
        expect(screen.getByRole('link', { name: 'Formations' })).toHaveAttribute('href', '#trainings');
        expect(screen.getByRole('link', { name: 'Déroulé' })).toHaveAttribute('href', '#process');
        expect(screen.getByRole('link', { name: 'Contact' })).toHaveAttribute('href', '#contact');
        expect(screen.getByRole('link', { name: 'Se connecter' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Échange' })).toBeInTheDocument();
    });

    it('shows the dashboard link instead of auth buttons for an authenticated user', () => {
        renderWelcome({ user: { id: 1, name: 'Jane' } });

        expect(screen.getByRole('link', { name: 'Tableau de bord' })).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Se connecter' })).not.toBeInTheDocument();
    });

    it('translates the navigation when the user switches language from the nav switcher', async () => {
        const user = userEvent.setup();
        renderWelcome({ user: null });

        expect(screen.getByRole('link', { name: 'Formations' })).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Français' }));
        await user.click(screen.getByRole('button', { name: /English/ }));

        expect(screen.getByRole('link', { name: 'Trainings' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Sign in' })).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Formations' })).not.toBeInTheDocument();
        expect(window.localStorage.getItem('aig-app-language')).toBe('en');
    });
});
