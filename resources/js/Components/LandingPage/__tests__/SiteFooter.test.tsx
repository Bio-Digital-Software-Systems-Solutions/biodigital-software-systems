import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import SiteFooter from '@/Components/LandingPage/SiteFooter';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('SiteFooter', () => {
    it('renders the navigation and contact columns plus the legal bar', () => {
        render(<SiteFooter />);

        expect(screen.getByText('Navigation')).toBeInTheDocument();
        expect(screen.getByText('Mentions légales · Confidentialité')).toBeInTheDocument();
        expect(screen.getByText(/Elmarce Bounda Ndinga/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'elmarce.bounda.ndinga@gmail.com' })).toHaveAttribute(
            'href',
            'mailto:elmarce.bounda.ndinga@gmail.com',
        );
    });

    it('translates the legal line per language', async () => {
        render(<SiteFooter />);

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByText('Imprint · Privacy')).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByText('Impressum · Datenschutz')).toBeInTheDocument();
    });
});
