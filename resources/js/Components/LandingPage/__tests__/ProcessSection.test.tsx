import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach } from 'vitest';
import i18n from '@/i18n';
import ProcessSection from '@/Components/LandingPage/ProcessSection';

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('ProcessSection', () => {
    it('renders the four ordered steps with numbers and translated titles', () => {
        const { container } = render(<ProcessSection />);

        expect(container.querySelector('#process')).not.toBeNull();
        expect(screen.getByText('Quatre étapes vers un résultat.')).toBeInTheDocument();

        expect(container.querySelectorAll('ol > li')).toHaveLength(4);
        expect(screen.getByText('01')).toBeInTheDocument();
        expect(screen.getByText('04')).toBeInTheDocument();

        expect(screen.getByRole('heading', { level: 4, name: 'Devis' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 4, name: 'Livraison' })).toBeInTheDocument();
    });

    it('translates the steps to English', async () => {
        render(<ProcessSection />);

        await act(async () => {
            await i18n.changeLanguage('en');
        });

        expect(screen.getByText('Four steps to a result.')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 4, name: 'Handover' })).toBeInTheDocument();
    });
});
