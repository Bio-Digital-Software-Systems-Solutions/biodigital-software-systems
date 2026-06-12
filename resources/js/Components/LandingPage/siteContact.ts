/**
 * Public contact details shown across the Bio-Digital landing page.
 * Centralised so the CTA, footer and nav stay in sync.
 */
export const SITE_CONTACT = {
    email: 'elmarce.bounda.ndinga@gmail.com',
    owner: 'Elmarce Bounda Ndinga',
    addressLines: ['Van-Gogh-Straße 2', '85521 Ottobrunn'],
    copyrightYear: 2026,
} as const;

export const mailtoHref = (subject?: string): string =>
    subject
        ? `mailto:${SITE_CONTACT.email}?subject=${encodeURIComponent(subject)}`
        : `mailto:${SITE_CONTACT.email}`;
