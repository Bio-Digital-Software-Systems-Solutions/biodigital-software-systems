import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Mail, MapPin } from 'lucide-react';
import { SITE_CONTACT, mailtoHref } from '@/Components/LandingPage/siteContact';

const NAV_LINKS = [
    { href: '#services', key: 'home.nav.services' },
    { href: '#trainings', key: 'home.nav.trainings' },
    { href: '#process', key: 'home.nav.process' },
    { href: '#contact', key: 'home.nav.contact' },
] as const;

const SERVICE_KEYS = [
    'home.footer.serviceMvp',
    'home.footer.serviceMiddleware',
    'home.footer.serviceSaas',
    'home.footer.serviceCustom',
] as const;

const headingClass = 'mb-4 text-[12px] font-semibold uppercase tracking-[0.13em] text-bd-accent';
const linkClass = 'text-[0.92rem] text-white/70 transition-colors hover:text-white';

/**
 * Public footer for the Bio-Digital landing page.
 *
 * Dark, multi-column layout mirroring the reference homepage style: a brand
 * wordmark + tagline + contact block, a navigation column, a services column,
 * and a legal bottom bar — themed with the Bio-Digital brand tokens.
 */
export default function SiteFooter() {
    const { t } = useTranslation();

    return (
        <footer className="bg-bd-deep text-white">
            <div className="mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:px-10">
                <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
                    <div className="lg:col-span-2">
                        <Link href="/" aria-label="Bio-Digital Software Systems" className="inline-block">
                            <span className="font-display text-2xl font-bold tracking-tight text-white">
                                Bio-<span className="text-bd-accent">Digital</span>
                            </span>
                            <span className="mt-1 block text-[11px] uppercase tracking-[0.2em] text-white/45">
                                Software Systems Solutions
                            </span>
                        </Link>

                        <p className="mt-5 max-w-md text-[0.95rem] leading-relaxed text-white/70">
                            {t('home.footer.tagline')}
                        </p>

                        <div className="mt-6 space-y-3">
                            <a href={mailtoHref()} className="flex items-center gap-3 text-white/70 transition-colors hover:text-white">
                                <Mail className="h-5 w-5 shrink-0 text-bd-accent" aria-hidden="true" />
                                <span className="text-[0.92rem]">{SITE_CONTACT.email}</span>
                            </a>
                            <div className="flex items-start gap-3 text-white/70">
                                <MapPin className="mt-0.5 h-5 w-5 shrink-0 text-bd-accent" aria-hidden="true" />
                                <address className="text-[0.92rem] not-italic">
                                    {SITE_CONTACT.addressLines.map((line) => (
                                        <span key={line} className="block">
                                            {line}
                                        </span>
                                    ))}
                                </address>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 className={headingClass}>{t('home.footer.navigation')}</h3>
                        <ul className="space-y-2.5">
                            {NAV_LINKS.map((link) => (
                                <li key={link.href}>
                                    <a href={link.href} className={linkClass}>
                                        {t(link.key)}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div>
                        <h3 className={headingClass}>{t('home.footer.col.services')}</h3>
                        <ul className="space-y-2.5">
                            {SERVICE_KEYS.map((key) => (
                                <li key={key}>
                                    <a href="#services" className={linkClass}>
                                        {t(key)}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-7 text-[12.5px] text-white/55 md:flex-row">
                    <span>
                        © {SITE_CONTACT.copyrightYear} Bio-Digital Software Systems · {SITE_CONTACT.owner} ·{' '}
                        {t('home.footer.copyright')}
                    </span>
                    <span>{t('home.footer.legal')}</span>
                </div>
            </div>
        </footer>
    );
}
