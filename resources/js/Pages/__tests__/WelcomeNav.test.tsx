import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import Welcome from '@/Pages/Welcome';

// Mock the heavy homepage sub-sections so this test focuses on the navigation bar.
vi.mock('@/Components/HeroCarousel', () => ({ default: () => <div data-testid="hero" /> }));
vi.mock('@/Components/LandingPage/AboutSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/OurValues', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/FeaturesSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/TrainingBrowseSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/ContactSection', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/SectionRenderer', () => ({ default: () => null }));
vi.mock('@/Components/LandingPage/Footer', () => ({ default: () => null }));

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

const baseProps = {
    laravelVersion: '12',
    phpVersion: '8.4',
    heroSlides: [],
    globalStats: {
        total_churches: 0,
        total_countries: 0,
        total_members: 0,
        europe: 0,
        africa: 0,
        americas: 0,
        asia: 0,
        oceania: 0,
    },
    sections: [],
    hasConfiguredSections: false,
} as const;

const renderWelcome = (auth: { user: unknown }) =>
    render(<Welcome auth={auth as never} {...(baseProps as never)} />);

beforeEach(async () => {
    window.localStorage.clear();
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('Welcome navigation bar', () => {
    it('renders the navigation links and guest auth buttons in French', () => {
        renderWelcome({ user: null });

        expect(screen.getByText('À propos')).toBeInTheDocument();
        expect(screen.getByText('Activités')).toBeInTheDocument();
        expect(screen.getByText('Formations')).toBeInTheDocument();
        expect(screen.getByText('Contact')).toBeInTheDocument();
        expect(screen.getByText('Se connecter')).toBeInTheDocument();
        expect(screen.getByText("S'inscrire")).toBeInTheDocument();
    });

    it('shows the dashboard link instead of auth buttons for an authenticated user', () => {
        renderWelcome({ user: { id: 1, name: 'Jane' } });

        // "Tableau de bord" is the FR dashboard label, rendered in the nav.
        expect(screen.getByText('Tableau de bord')).toBeInTheDocument();
        expect(screen.queryByText('Se connecter')).not.toBeInTheDocument();
    });

    it('translates the navigation when the user switches language from the nav switcher', async () => {
        const user = userEvent.setup();
        renderWelcome({ user: null });

        expect(screen.getByText('À propos')).toBeInTheDocument();

        // The nav renders a switcher for desktop and one for mobile; both share the global i18n.
        const [toggle] = screen.getAllByRole('button', { name: 'Français' });
        await user.click(toggle);
        await user.click(screen.getByRole('button', { name: /English/ }));

        expect(screen.getByText('About')).toBeInTheDocument();
        expect(screen.getByText('Trainings')).toBeInTheDocument();
        expect(screen.getByText('Sign in')).toBeInTheDocument();
        expect(screen.queryByText('À propos')).not.toBeInTheDocument();
        expect(window.localStorage.getItem('aig-app-language')).toBe('en');
    });
});
