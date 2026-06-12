import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach } from 'vitest';
import i18n from '@/i18n';
import ServicesSection from '@/Components/LandingPage/ServicesSection';

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('ServicesSection', () => {
    it('renders the kicker, title and four service cards', () => {
        const { container } = render(<ServicesSection />);

        expect(container.querySelector('#services')).not.toBeNull();
        expect(screen.getByText('Un logiciel qui comprend votre science.')).toBeInTheDocument();

        // Four service headings (h3).
        expect(screen.getByRole('heading', { level: 3, name: 'Bio-MVP Turbo' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 3, name: 'Solutions middleware' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 3, name: 'Développements de plateformes SaaS' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 3, name: 'Logiciel sur mesure' })).toBeInTheDocument();
    });

    it('translates the service titles to German', async () => {
        render(<ServicesSection />);

        await act(async () => {
            await i18n.changeLanguage('de');
        });

        expect(screen.getByText('Software, die Ihre Wissenschaft versteht.')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 3, name: 'Middleware-Lösungen' })).toBeInTheDocument();
    });
});
