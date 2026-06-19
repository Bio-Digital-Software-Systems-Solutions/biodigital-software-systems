import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import type { ReactNode } from 'react';
import i18n from '@/i18n';
import HeroCarousel from '@/Components/LandingPage/HeroCarousel';

// Swiper relies on layout measurement that happy-dom can't provide, so render
// its slides as plain markup to focus the test on the carousel's own content.
vi.mock('swiper/react', () => ({
    Swiper: ({ children }: { children: ReactNode }) => <div>{children}</div>,
    SwiperSlide: ({ children }: { children: ReactNode }) => <div>{children}</div>,
}));
vi.mock('swiper/modules', () => ({
    Autoplay: {},
    EffectFade: {},
    Navigation: {},
    Pagination: {},
}));
vi.mock('swiper/css', () => ({}));
vi.mock('swiper/css/effect-fade', () => ({}));
vi.mock('swiper/css/navigation', () => ({}));
vi.mock('swiper/css/pagination', () => ({}));

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('HeroCarousel', () => {
    it('renders every slide eyebrow, the emphasised words and the trust strip', () => {
        render(<HeroCarousel />);

        expect(screen.getByText('Développement logiciel')).toBeInTheDocument();
        expect(screen.getByText('Formations & coaching')).toBeInTheDocument();
        // "Bio-informatique" is both the slide 3 eyebrow and a trust label.
        expect(screen.getAllByText('Bio-informatique').length).toBeGreaterThanOrEqual(2);

        // Emphasised words rendered inside <em> via <Trans>.
        expect(screen.getByText('logiciel')).toBeInTheDocument();
        expect(screen.getByText('claires')).toBeInTheDocument();

        // Trust strip values.
        expect(screen.getByText('8+')).toBeInTheDocument();
        expect(screen.getByText('ISTQB')).toBeInTheDocument();
    });

    it('exposes accessible previous / next controls', () => {
        render(<HeroCarousel />);

        expect(screen.getByRole('button', { name: 'Previous slide' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Next slide' })).toBeInTheDocument();
    });

    it('translates slide content when the language changes', async () => {
        render(<HeroCarousel />);

        expect(screen.getByText('Développement logiciel')).toBeInTheDocument();

        await act(async () => {
            await i18n.changeLanguage('de');
        });

        expect(screen.getByText('Software-Entwicklung')).toBeInTheDocument();
        expect(screen.queryByText('Développement logiciel')).not.toBeInTheDocument();
    });
});
