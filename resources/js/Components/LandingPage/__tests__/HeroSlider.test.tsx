import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import i18n from '@/i18n';
import HeroSlider from '@/Components/LandingPage/HeroSlider';

beforeEach(async () => {
    // Disable autoplay deterministically (reduce-motion) so the interval never fires.
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation((query: string) => ({
            matches: true,
            media: query,
            onchange: null,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            addListener: vi.fn(),
            removeListener: vi.fn(),
            dispatchEvent: vi.fn(),
        })),
    });
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('HeroSlider', () => {
    it('renders the three slide eyebrows, emphasised words and the trust strip', () => {
        render(<HeroSlider />);

        expect(screen.getByText('Développement logiciel')).toBeInTheDocument();
        expect(screen.getByText('Formations & coaching')).toBeInTheDocument();
        // "Bio-informatique" is both the slide 3 eyebrow and the trust label.
        expect(screen.getAllByText('Bio-informatique').length).toBeGreaterThanOrEqual(2);

        // Emphasised words rendered inside <em> via <Trans>.
        expect(screen.getByText('logiciel')).toBeInTheDocument();
        expect(screen.getByText('claires')).toBeInTheDocument();

        // Trust strip values.
        expect(screen.getByText('8+')).toBeInTheDocument();
        expect(screen.getByText('M.Sc.')).toBeInTheDocument();
        expect(screen.getByText('ISTQB')).toBeInTheDocument();
        expect(screen.getByText('München')).toBeInTheDocument();
    });

    it('starts on the first slide and exposes one tab per slide', () => {
        render(<HeroSlider />);

        const tabs = screen.getAllByRole('tab');
        expect(tabs).toHaveLength(3);
        expect(tabs[0]).toHaveAttribute('aria-current', 'true');
        expect(tabs[1]).toHaveAttribute('aria-current', 'false');
    });

    it('advances to the next slide when the next arrow is clicked', async () => {
        const user = userEvent.setup();
        render(<HeroSlider />);

        await user.click(screen.getByRole('button', { name: 'Next slide' }));

        const tabs = screen.getAllByRole('tab');
        expect(tabs[0]).toHaveAttribute('aria-current', 'false');
        expect(tabs[1]).toHaveAttribute('aria-current', 'true');
    });

    it('jumps to a slide when its dot is clicked', async () => {
        const user = userEvent.setup();
        render(<HeroSlider />);

        const tabs = screen.getAllByRole('tab');
        await user.click(tabs[2]);

        expect(screen.getAllByRole('tab')[2]).toHaveAttribute('aria-current', 'true');
    });

    it('wraps to the last slide when going back from the first', async () => {
        const user = userEvent.setup();
        render(<HeroSlider />);

        await user.click(screen.getByRole('button', { name: 'Previous slide' }));

        expect(screen.getAllByRole('tab')[2]).toHaveAttribute('aria-current', 'true');
    });

    it('translates slide content when the language changes', async () => {
        render(<HeroSlider />);

        expect(screen.getByText('Développement logiciel')).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });

        expect(screen.getByText('Software-Entwicklung')).toBeInTheDocument();
        expect(screen.queryByText('Développement logiciel')).not.toBeInTheDocument();
    });
});
