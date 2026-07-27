import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import Imprint from './Imprint';
import { SITE_CONTACT } from '@/Components/LandingPage/siteContact';

vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: React.ReactNode }) => <>{children}</>,
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

describe('Imprint Page', () => {
    it('renders the French legal notice by default', () => {
        render(<Imprint />);

        expect(screen.getByRole('heading', { level: 1, name: 'Mentions légales' })).toBeInTheDocument();
        expect(screen.getByText('Éditeur du site (§ 5 DDG)')).toBeInTheDocument();
        expect(screen.getByText(SITE_CONTACT.company)).toBeInTheDocument();
    });

    it('displays the tax identifiers from SITE_CONTACT', () => {
        render(<Imprint />);

        expect(screen.getByText(`Numéro fiscal (Steuernummer) : ${SITE_CONTACT.taxNumber}`)).toBeInTheDocument();
        expect(
            screen.getByText(`Numéro de TVA intracommunautaire (§ 27a UStG) : ${SITE_CONTACT.vatId}`),
        ).toBeInTheDocument();
    });

    it('links the contact e-mail and the EU ODR platform', () => {
        render(<Imprint />);

        expect(screen.getByRole('link', { name: SITE_CONTACT.email })).toHaveAttribute(
            'href',
            `mailto:${SITE_CONTACT.email}`,
        );
        expect(screen.getByRole('link', { name: 'https://ec.europa.eu/consumers/odr/' })).toHaveAttribute(
            'href',
            'https://ec.europa.eu/consumers/odr/',
        );
    });

    it('translates to German and English', async () => {
        render(<Imprint />);

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByRole('heading', { level: 1, name: 'Impressum' })).toBeInTheDocument();
        expect(screen.getByText('Angaben gemäß § 5 DDG')).toBeInTheDocument();
        expect(screen.getByText(`Steuernummer: ${SITE_CONTACT.taxNumber}`)).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByRole('heading', { level: 1, name: 'Imprint' })).toBeInTheDocument();
        expect(screen.getByText('Site provider (Section 5 DDG)')).toBeInTheDocument();
    });
});
