import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import PrivacyPolicy from './PrivacyPolicy';

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: any) => <>{children}</>,
    Link: ({ children, href, ...props }: any) => <a href={href} {...props}>{children}</a>,
}));

describe('PrivacyPolicy Page', () => {
    it('renders the privacy policy page correctly', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText('Politique de confidentialité')).toBeInTheDocument();
        expect(screen.getByText('Introduction')).toBeInTheDocument();
        expect(screen.getByText('Données que nous collectons')).toBeInTheDocument();
        expect(screen.getByText('Comment nous utilisons vos données')).toBeInTheDocument();
        expect(screen.getByText('Partage des données')).toBeInTheDocument();
        expect(screen.getByText('Sécurité de vos données')).toBeInTheDocument();
        expect(screen.getByText('Vos droits (RGPD)')).toBeInTheDocument();
        expect(screen.getByText('Nous contacter')).toBeInTheDocument();
    });

    it('has a back to home link', () => {
        render(<PrivacyPolicy />);

        const backLink = screen.getByText('Retour à l\'accueil');
        expect(backLink).toBeInTheDocument();
        expect(backLink.closest('a')).toHaveAttribute('href', '/');
    });

    it('displays contact information', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText('privacy@icc-munich.org')).toBeInTheDocument();
        expect(screen.getByText('+49 89 123 456 789')).toBeInTheDocument();
        expect(screen.getByText('Munich, Allemagne')).toBeInTheDocument();
    });

    it('displays GDPR rights information', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText('✓ Droit d\'accès')).toBeInTheDocument();
        expect(screen.getByText('✓ Droit de rectification')).toBeInTheDocument();
        expect(screen.getByText('✓ Droit à l\'effacement')).toBeInTheDocument();
        expect(screen.getByText('✓ Droit à la portabilité')).toBeInTheDocument();
    });

    it('has proper security information', () => {
        render(<PrivacyPolicy />);

        expect(screen.getByText('🔒 Nous ne vendons jamais vos données personnelles à des tiers.')).toBeInTheDocument();
        expect(screen.getByText('Mesures techniques')).toBeInTheDocument();
        expect(screen.getByText('Mesures organisationnelles')).toBeInTheDocument();
    });
});