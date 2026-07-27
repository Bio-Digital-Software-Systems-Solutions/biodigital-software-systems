import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import SiteFooter from '@/Components/LandingPage/SiteFooter';
import { SITE_CONTACT } from '@/Components/LandingPage/siteContact';

const registerConfigured = Boolean(SITE_CONTACT.registerCourt && SITE_CONTACT.registerNumber);

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
        expect(screen.getByRole('link', { name: 'Mentions légales' })).toHaveAttribute('href', '/imprint');
        expect(screen.getByRole('link', { name: 'Protection des données' })).toHaveAttribute('href', '/privacy-policy');
        expect(screen.getByText(/Elmarce Bounda Ndinga/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: SITE_CONTACT.email })).toHaveAttribute(
            'href',
            `mailto:${SITE_CONTACT.email}`,
        );
    });

    it('displays the tax identifiers, and the register only when configured', () => {
        render(<SiteFooter />);

        expect(
            screen.getByText(`Numéro fiscal (Steuernummer) : ${SITE_CONTACT.taxNumber}`),
        ).toBeInTheDocument();
        expect(screen.getByText(`N° TVA intracommunautaire : ${SITE_CONTACT.vatId}`)).toBeInTheDocument();

        const registerLine = screen.queryByText(/Registre du commerce/);
        if (registerConfigured) {
            expect(registerLine).toBeInTheDocument();
        } else {
            expect(registerLine).not.toBeInTheDocument();
        }
    });

    it('translates the legal links per language', async () => {
        render(<SiteFooter />);

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByRole('link', { name: 'Legal notice' })).toHaveAttribute('href', '/imprint');
        expect(screen.getByRole('link', { name: 'Data protection' })).toHaveAttribute('href', '/privacy-policy');

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByRole('link', { name: 'Impressum' })).toHaveAttribute('href', '/imprint');
        expect(screen.getByRole('link', { name: 'Datenschutz' })).toHaveAttribute('href', '/privacy-policy');
    });
});
