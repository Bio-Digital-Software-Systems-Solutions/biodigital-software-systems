/**
 * Public contact details shown across the Bio-Digital landing page.
 * Centralised so the CTA, footer and nav stay in sync.
 * Values come from the VITE_BUSINESS_* env vars (see .env.example); the
 * literals below are only fallbacks when a variable is missing at build time.
 */
const envAddressLines = String(import.meta.env.VITE_BUSINESS_ADDRESS ?? '')
    .split('|')
    .map((line) => line.trim())
    .filter(Boolean);

export const SITE_CONTACT = {
    company: import.meta.env.VITE_BUSINESS_COMPANY || 'Bio-Digital Software Systems Solutions UG (haftungsbeschränkt)',
    email: import.meta.env.VITE_BUSINESS_EMAIL || 'elmarce.bounda.ndinga@bio-digital-sss.com',
    owner: import.meta.env.VITE_BUSINESS_OWNER || 'Elmarce Bounda Ndinga',
    addressLines: envAddressLines.length > 0 ? envAddressLines : ['Van-Gogh-Straße 2', '85521 Ottobrunn'],
    copyrightYear: Number(import.meta.env.VITE_BUSINESS_COPYRIGHT_YEAR) || 2026,
    taxNumber: import.meta.env.VITE_BUSINESS_TAX_NUMBER || '143/120/60834',
    vatId: import.meta.env.VITE_BUSINESS_VAT_ID || 'DE462726846',
    registerCourt: import.meta.env.VITE_BUSINESS_REGISTER_COURT || '',
    registerNumber: import.meta.env.VITE_BUSINESS_REGISTER_NUMBER || '',
} as const;

export const mailtoHref = (subject?: string): string =>
    subject
        ? `mailto:${SITE_CONTACT.email}?subject=${encodeURIComponent(subject)}`
        : `mailto:${SITE_CONTACT.email}`;
