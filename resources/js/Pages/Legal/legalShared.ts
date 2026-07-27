/**
 * Shared building blocks for the public legal pages (Imprint & Privacy Policy).
 * Content lives in imprintContent.ts / privacyContent.ts, one entry per language.
 */

export type LegalLanguage = 'fr' | 'en' | 'de';

export const LEGAL_LAST_UPDATED = '2026-07-27';

export interface LegalBlock {
    subtitle?: string;
    paragraphs?: string[];
    bullets?: string[];
    links?: Array<{ label: string; href: string }>;
}

export interface LegalSection {
    id: string;
    title: string;
    blocks: LegalBlock[];
}

export interface LegalPageContent {
    metaTitle: string;
    title: string;
    backToHome: string;
    lastUpdatedLabel: string;
    sections: LegalSection[];
}

const LOCALE_BY_LANGUAGE: Record<LegalLanguage, string> = {
    fr: 'fr-FR',
    en: 'en-GB',
    de: 'de-DE',
};

export const resolveLegalLanguage = (language: string | undefined): LegalLanguage => {
    const short = (language ?? 'fr').slice(0, 2).toLowerCase();
    return short === 'en' || short === 'de' ? short : 'fr';
};

export const formatLegalDate = (language: LegalLanguage): string =>
    new Date(LEGAL_LAST_UPDATED).toLocaleDateString(LOCALE_BY_LANGUAGE[language], {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
