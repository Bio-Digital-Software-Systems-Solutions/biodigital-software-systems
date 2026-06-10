import { describe, it, expect } from 'vitest';
import i18n from '@/i18n';

const LANGUAGES = ['fr', 'en', 'de'] as const;

const bundleFor = (lang: string): Record<string, string> =>
    (i18n.getResourceBundle(lang, 'translation') ?? {}) as Record<string, string>;

const homepageKeys = (lang: string): string[] =>
    Object.keys(bundleFor(lang)).filter((key) => key.startsWith('home.'));

describe('homepage i18n resources', () => {
    it('ships homepage translations for every supported language', () => {
        for (const lang of LANGUAGES) {
            expect(homepageKeys(lang).length).toBeGreaterThan(100);
        }
    });

    it('exposes the exact same set of home.* keys in fr, en and de', () => {
        const french = homepageKeys('fr').sort();

        for (const lang of LANGUAGES) {
            expect(homepageKeys(lang).sort()).toEqual(french);
        }
    });

    it('never leaves a homepage value empty or untranslated (equal to its key)', () => {
        for (const lang of LANGUAGES) {
            const bundle = bundleFor(lang);

            for (const key of homepageKeys(lang)) {
                const value = bundle[key];
                expect(value, `${lang} / ${key}`).toBeTruthy();
                expect(value, `${lang} / ${key}`).not.toBe(key);
            }
        }
    });

    it('actually returns a different translation per language for representative keys', () => {
        const sampleKeys = [
            'home.nav.about',
            'home.contact.heading',
            'home.training.heading',
            'home.about.competencies.heading',
        ];

        for (const key of sampleKeys) {
            const values = LANGUAGES.map((lang) => bundleFor(lang)[key]);
            const unique = new Set(values);
            expect(unique.size, `${key} should differ across languages`).toBe(LANGUAGES.length);
        }
    });
});
