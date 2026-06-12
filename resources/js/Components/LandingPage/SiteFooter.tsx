import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import BrandMark from '@/Components/LandingPage/BrandMark';
import { SITE_CONTACT, mailtoHref } from '@/Components/LandingPage/siteContact';

const NAV_LINKS = [
    { href: '#services', key: 'home.nav.services' },
    { href: '#trainings', key: 'home.nav.trainings' },
    { href: '#process', key: 'home.nav.process' },
    { href: '#contact', key: 'home.nav.contact' },
] as const;

/**
 * Public footer matching the Bio-Digital landing proposal: brand + tagline,
 * navigation and contact columns, plus a legal bottom bar.
 */
export default function SiteFooter() {
    const { t } = useTranslation();

    return (
        <footer className="mx-auto max-w-[1140px] border-t border-bd-line px-5 pb-9 pt-12 sm:px-8 lg:px-10">
            <div className="flex flex-wrap justify-between gap-8">
                <div className="max-w-[32ch]">
                    <Link href="/" aria-label="Bio-Digital Software Systems">
                        <BrandMark />
                    </Link>
                    <p className="mt-3 text-[0.9rem] text-bd-ink-3">{t('home.footer.tagline')}</p>
                </div>

                <div>
                    <h6 className="mb-3 text-[11px] uppercase tracking-[0.13em] text-bd-ink-3">
                        {t('home.footer.navigation')}
                    </h6>
                    {NAV_LINKS.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="mb-2 block text-[0.92rem] text-bd-ink-2 transition-colors hover:text-bd-brand-deep"
                        >
                            {t(link.key)}
                        </a>
                    ))}
                </div>

                <div>
                    <h6 className="mb-3 text-[11px] uppercase tracking-[0.13em] text-bd-ink-3">
                        {t('home.footer.contactHeading')}
                    </h6>
                    <a
                        href={mailtoHref()}
                        className="mb-2 block text-[0.92rem] text-bd-ink-2 transition-colors hover:text-bd-brand-deep"
                    >
                        {SITE_CONTACT.email}
                    </a>
                    {SITE_CONTACT.addressLines.map((line) => (
                        <p key={line} className="mb-2 text-[0.92rem] text-bd-ink-2">
                            {line}
                        </p>
                    ))}
                </div>
            </div>

            <div className="mt-10 flex flex-wrap justify-between gap-3 border-t border-bd-line pt-5 text-[12.5px] text-bd-ink-3">
                <span>
                    © {SITE_CONTACT.copyrightYear} Bio-Digital Software Systems · {SITE_CONTACT.owner}
                </span>
                <span>{t('home.footer.legal')}</span>
            </div>
        </footer>
    );
}
