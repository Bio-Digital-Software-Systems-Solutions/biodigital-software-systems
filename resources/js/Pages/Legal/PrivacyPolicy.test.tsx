import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import PrivacyPolicy from './PrivacyPolicy';
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

describe('PrivacyPolicy Page', () => {
    it('renders the French GDPR privacy policy by default', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByRole('heading', { level: 1, name: 'Politique de confidentialité' })).toBeInTheDocument();
        expect(screen.getByText('Responsable du traitement')).toBeInTheDocument();
        expect(screen.getByText('Cookies et stockage local')).toBeInTheDocument();
        expect(screen.getByText('Vos droits')).toBeInTheDocument();
        expect(screen.getByText('Surveillance des erreurs (Sentry)')).toBeInTheDocument();
    });

    it('displays the controller identity from SITE_CONTACT', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText(SITE_CONTACT.company)).toBeInTheDocument();
        expect(screen.getByText(`E-mail : ${SITE_CONTACT.email}`)).toBeInTheDocument();
    });

    it('lists the GDPR rights', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText("Droit d'accès (art. 15 RGPD)")).toBeInTheDocument();
        expect(screen.getByText('Droit de rectification (art. 16 RGPD)')).toBeInTheDocument();
        expect(screen.getByText("Droit à l'effacement (art. 17 RGPD)")).toBeInTheDocument();
        expect(screen.getByText('Droit à la portabilité des données (art. 20 RGPD)')).toBeInTheDocument();
    });

    it('links to the Bavarian supervisory authority', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByRole('link', { name: 'https://www.lda.bayern.de' })).toHaveAttribute(
            'href',
            'https://www.lda.bayern.de',
        );
    });

    it('has a back to home link', () => {
        render(<PrivacyPolicy />);

        const backLink = screen.getByText("Retour à l'accueil");
        expect(backLink.closest('a')).toHaveAttribute('href', '/');
    });

    it('translates to German and English', async () => {
        render(<PrivacyPolicy />);

        await act(async () => {
            await i18n.changeLanguage('de');
        });
        expect(screen.getByRole('heading', { level: 1, name: 'Datenschutzerklärung' })).toBeInTheDocument();
        expect(screen.getByText('Verantwortlicher')).toBeInTheDocument();
        expect(screen.getByText('Ihre Rechte')).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('en');
        });
        expect(screen.getByRole('heading', { level: 1, name: 'Privacy Policy' })).toBeInTheDocument();
        expect(screen.getByText('Controller')).toBeInTheDocument();
        expect(screen.getByText('Your rights')).toBeInTheDocument();
    });
});
