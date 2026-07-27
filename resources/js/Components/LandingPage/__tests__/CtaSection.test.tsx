import { render, screen, act } from '@testing-library/react';
import { describe, it, expect, beforeEach } from 'vitest';
import i18n from '@/i18n';
import CtaSection from '@/Components/LandingPage/CtaSection';
import { SITE_CONTACT } from '@/Components/LandingPage/siteContact';

beforeEach(async () => {
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
});

describe('CtaSection', () => {
    it('renders the contact section with a mailto request button', () => {
        const { container } = render(<CtaSection />);

        expect(container.querySelector('#contact')).not.toBeNull();
        expect(screen.getByText('Parlons-en.')).toBeInTheDocument();

        const requestLink = screen.getByRole('link', { name: 'Demander un échange' });
        expect(requestLink.getAttribute('href')).toContain(`mailto:${SITE_CONTACT.email}?subject=`);

        expect(
            screen.getByRole('link', { name: SITE_CONTACT.email }),
        ).toHaveAttribute('href', `mailto:${SITE_CONTACT.email}`);
    });

    it('translates the heading to German', async () => {
        render(<CtaSection />);

        await act(async () => {
            await i18n.changeLanguage('de');
        });

        expect(screen.getByText('Lassen Sie uns sprechen.')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Erstgespräch anfragen' })).toBeInTheDocument();
    });
});
