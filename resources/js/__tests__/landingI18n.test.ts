import { describe, it, expect } from 'vitest';
import i18n from '@/i18n';

/**
 * Keys introduced by the Bio-Digital landing redesign. Every one must resolve
 * to a real translation (not the key itself, not empty) in all supported langs.
 */
const NEW_KEYS = [
    'home.nav.services',
    'home.nav.process',
    'home.nav.cta',
    'home.hero.s1.eyebrow',
    'home.hero.s1.title',
    'home.hero.s1.desc',
    'home.hero.s1.cta1',
    'home.hero.s1.cta2',
    'home.hero.s2.eyebrow',
    'home.hero.s2.title',
    'home.hero.s2.desc',
    'home.hero.s2.cta1',
    'home.hero.s2.cta2',
    'home.hero.s3.eyebrow',
    'home.hero.s3.title',
    'home.hero.s3.desc',
    'home.hero.s3.cta1',
    'home.hero.s3.cta2',
    'home.hero.trust.years',
    'home.hero.trust.bioinfo',
    'home.hero.trust.trainer',
    'home.hero.trust.location',
    'home.services.kicker',
    'home.services.title',
    'home.services.subtitle',
    'home.process.kicker',
    'home.process.title',
    'home.process.step1.title',
    'home.process.step1.desc',
    'home.process.step2.title',
    'home.process.step2.desc',
    'home.process.step3.title',
    'home.process.step3.desc',
    'home.process.step4.title',
    'home.process.step4.desc',
    'home.cta.title',
    'home.cta.subtitle',
    'home.cta.button',
    'home.footer.navigation',
    'home.footer.contactHeading',
    'home.footer.legal',
];

const LANGS = ['fr', 'en', 'de'] as const;

describe('Bio-Digital landing i18n', () => {
    it.each(LANGS)('defines every new landing key in %s', (lng) => {
        const t = i18n.getFixedT(lng);

        for (const key of NEW_KEYS) {
            const value = t(key);
            expect(value, `${key} missing in ${lng}`).toBeTruthy();
            expect(value, `${key} unresolved in ${lng}`).not.toBe(key);
        }
    });

    it('keeps the emphasised <1> marker in every hero title', () => {
        for (const lng of LANGS) {
            const t = i18n.getFixedT(lng);
            for (const key of ['home.hero.s1.title', 'home.hero.s2.title', 'home.hero.s3.title']) {
                expect(t(key), `${key} (${lng}) should contain <1></1>`).toContain('<1>');
            }
        }
    });
});
