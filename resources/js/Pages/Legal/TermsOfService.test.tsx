import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import TermsOfService from './TermsOfService';

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: any) => <>{children}</>,
    Link: ({ children, href, ...props }: any) => <a href={href} {...props}>{children}</a>,
    usePage: () => ({ props: { app: { name: 'ICC Munich' } } }),
}));

describe('TermsOfService Page', () => {
    it('renders the terms of service page correctly', () => {
        render(<TermsOfService />);

        expect(screen.getByText('Conditions d\'utilisation')).toBeInTheDocument();
        expect(screen.getByText('Bienvenue sur ICC Munich')).toBeInTheDocument();
        expect(screen.getByText('Acceptation des conditions')).toBeInTheDocument();
        expect(screen.getByText('Utilisation du service')).toBeInTheDocument();
        expect(screen.getByText('Conduites interdites')).toBeInTheDocument();
        expect(screen.getByText('Contenu utilisateur')).toBeInTheDocument();
        expect(screen.getByText('Propriété intellectuelle')).toBeInTheDocument();
        expect(screen.getByText('Limitation de responsabilité')).toBeInTheDocument();
        expect(screen.getByText('Résiliation')).toBeInTheDocument();
        expect(screen.getByText('Droit applicable')).toBeInTheDocument();
    });

    it('has a back to home link', () => {
        render(<TermsOfService />);

        const backLink = screen.getByText('Retour à l\'accueil');
        expect(backLink).toBeInTheDocument();
        expect(backLink.closest('a')).toHaveAttribute('href', '/');
    });

    it('displays service information', () => {
        render(<TermsOfService />);

        expect(screen.getByText('Gestion d\'événements et inscriptions')).toBeInTheDocument();
        expect(screen.getByText('Système de prêt de livres')).toBeInTheDocument();
        expect(screen.getByText('Publication et lecture d\'articles')).toBeInTheDocument();
        expect(screen.getByText('Messagerie et chat en temps réel')).toBeInTheDocument();
        expect(screen.getByText('Gestion de projets et tâches')).toBeInTheDocument();
        expect(screen.getByText('Formations et supports de cours')).toBeInTheDocument();
    });

    it('displays prohibited activities', () => {
        render(<TermsOfService />);

        expect(screen.getByText('Il est strictement interdit de :')).toBeInTheDocument();
        expect(screen.getByText('Utiliser le service à des fins illégales')).toBeInTheDocument();
        expect(screen.getByText('Publier du contenu offensant ou diffamatoire')).toBeInTheDocument();
        expect(screen.getByText('Violer les droits d\'auteur')).toBeInTheDocument();
        expect(screen.getByText('Partager des informations confidentielles')).toBeInTheDocument();
    });

    it('has contact information', () => {
        render(<TermsOfService />);

        const contactLink = screen.getByText('legal@icc-munich.org');
        expect(contactLink).toBeInTheDocument();
        expect(contactLink.closest('a')).toHaveAttribute('href', 'mailto:legal@icc-munich.org');
    });

    it('displays German law information', () => {
        render(<TermsOfService />);

        expect(screen.getByText(/Ces conditions sont régies par le droit allemand/)).toBeInTheDocument();
        expect(screen.getByText(/compétence exclusive des tribunaux de Munich, Allemagne/)).toBeInTheDocument();
    });

    it('explains termination procedures', () => {
        render(<TermsOfService />);

        expect(screen.getByText('Par l\'utilisateur')).toBeInTheDocument();
        expect(screen.getByText('Par ICC Munich')).toBeInTheDocument();
        expect(screen.getByText('Vous pouvez fermer votre compte à tout moment')).toBeInTheDocument();
        expect(screen.getByText('Violation des conditions d\'utilisation')).toBeInTheDocument();
    });

    it('shows liability limitation warning', () => {
        render(<TermsOfService />);

        expect(screen.getByText('⚠️ Le service est fourni "en l\'état" sans garantie d\'aucune sorte.')).toBeInTheDocument();
        expect(screen.getByText('Nous ne garantissons pas un service ininterrompu')).toBeInTheDocument();
        expect(screen.getByText('Vous utilisez le service à vos propres risques')).toBeInTheDocument();
    });
});