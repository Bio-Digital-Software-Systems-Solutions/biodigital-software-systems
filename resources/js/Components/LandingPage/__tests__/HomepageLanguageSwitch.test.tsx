import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import AboutSection from '@/Components/LandingPage/AboutSection';
import Footer from '@/Components/LandingPage/Footer';

// Footer relies on Inertia's usePage for the app name and Link for navigation.
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { app: { name: 'BioDigital' } } }),
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

// "Compétences clés" (fr) / "Core competencies" (en) / "Kernkompetenzen" (de)
const ABOUT_HEADINGS = {
    fr: 'Compétences clés',
    en: 'Core competencies',
    de: 'Kernkompetenzen',
} as const;

const FOOTER_LEGAL_HEADINGS = {
    fr: 'Légal',
    en: 'Legal',
    de: 'Rechtliches',
} as const;

beforeEach(async () => {
    window.localStorage.clear();
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('homepage language switching', () => {
    it('renders About section content in each supported language', async () => {
        render(<AboutSection />);

        expect(screen.getByText(ABOUT_HEADINGS.fr)).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByText(ABOUT_HEADINGS.en)).toBeInTheDocument();
        expect(screen.queryByText(ABOUT_HEADINGS.fr)).not.toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByText(ABOUT_HEADINGS.de)).toBeInTheDocument();
    });

    it('switches homepage content when the user picks a language in the switcher', async () => {
        const user = userEvent.setup();

        render(
            <div>
                <LanguageSwitcher />
                <AboutSection />
            </div>,
        );

        // Starts in French.
        expect(screen.getByText(ABOUT_HEADINGS.fr)).toBeInTheDocument();

        // Open the dropdown (toggle is exposed via its aria-label) and choose Deutsch.
        await user.click(screen.getByRole('button', { name: 'Français' }));
        await user.click(screen.getByRole('button', { name: /Deutsch/ }));

        // Content is now German and the choice is persisted.
        expect(screen.getByText(ABOUT_HEADINGS.de)).toBeInTheDocument();
        expect(screen.queryByText(ABOUT_HEADINGS.fr)).not.toBeInTheDocument();
        expect(window.localStorage.getItem('aig-app-language')).toBe('de');

        // Switch again to English from the switcher.
        await user.click(screen.getByRole('button', { name: 'Deutsch' }));
        await user.click(screen.getByRole('button', { name: /English/ }));

        expect(screen.getByText(ABOUT_HEADINGS.en)).toBeInTheDocument();
        expect(window.localStorage.getItem('aig-app-language')).toBe('en');
    });

    it('translates the footer headings for every language', async () => {
        render(<Footer />);

        expect(screen.getByText(FOOTER_LEGAL_HEADINGS.fr)).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByText(FOOTER_LEGAL_HEADINGS.en)).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByText(FOOTER_LEGAL_HEADINGS.de)).toBeInTheDocument();
    });
});
