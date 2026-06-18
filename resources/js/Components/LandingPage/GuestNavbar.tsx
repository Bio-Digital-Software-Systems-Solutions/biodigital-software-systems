import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Menu, X } from 'lucide-react';
import BrandMark from '@/Components/LandingPage/BrandMark';
import LanguageSwitcher from '@/Components/LanguageSwitcher';

interface GuestNavbarProps {
    isAuthenticated: boolean;
}

const NAV_LINKS = [
    { href: '#about', key: 'home.nav.about' },
    { href: '#services', key: 'home.nav.services' },
    { href: '#trainings', key: 'home.nav.trainings' },
    { href: '#process', key: 'home.nav.process' },
    { href: '#contact', key: 'home.nav.contact' },
] as const;

/**
 * Sticky public navigation matching the Bio-Digital landing proposal:
 * brand lockup, anchor links, language switcher and a primary call-to-action,
 * with auth-aware buttons (dashboard vs login/register) and a mobile menu.
 */
export default function GuestNavbar({ isAuthenticated }: GuestNavbarProps) {
    const { t } = useTranslation();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <header className="sticky top-0 z-[60] border-b border-bd-line bg-bd-bg/80 backdrop-blur-md">
            <div className="mx-auto flex h-[66px] max-w-none items-center justify-between gap-4 px-5 sm:px-8 lg:px-10">
                <Link href="/" aria-label="Bio-Digital Software Systems">
                    <BrandMark />
                </Link>

                <div className="flex items-center gap-4">
                    <nav className="hidden items-center gap-7 lg:flex">
                        {NAV_LINKS.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                className="text-[14.5px] text-bd-ink-2 transition-colors hover:text-bd-brand-deep"
                            >
                                {t(link.key)}
                            </a>
                        ))}
                    </nav>

                    <LanguageSwitcher />

                    <div className="hidden items-center gap-3 md:flex">
                        {isAuthenticated ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center rounded-[10px] bg-bd-brand px-4 py-2.5 text-[14.5px] font-semibold text-white transition-all hover:-translate-y-px hover:bg-bd-brand-deep"
                            >
                                {t('home.nav.dashboard')}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="inline-flex items-center rounded-[10px] border border-bd-line bg-bd-surface px-4 py-2.5 text-[14.5px] font-semibold text-bd-ink transition-colors hover:border-bd-brand hover:text-bd-brand-deep"
                                >
                                    {t('home.nav.login')}
                                </Link>
                                <a
                                    href="#contact"
                                    className="inline-flex items-center rounded-[10px] bg-bd-brand px-4 py-2.5 text-[14.5px] font-semibold text-white transition-all hover:-translate-y-px hover:bg-bd-brand-deep"
                                >
                                    {t('home.nav.cta')}
                                </a>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={() => setMobileMenuOpen((open) => !open)}
                        aria-label={t('home.nav.toggleMenu')}
                        aria-expanded={mobileMenuOpen}
                        className="grid h-10 w-10 place-items-center rounded-[10px] border border-bd-line text-bd-ink-2 transition-colors hover:border-bd-brand lg:hidden"
                    >
                        {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>
            </div>

            {mobileMenuOpen && (
                <div className="border-t border-bd-line bg-bd-bg lg:hidden">
                    <nav className="mx-auto flex max-w-none flex-col gap-1 px-5 py-3 sm:px-8">
                        {NAV_LINKS.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                onClick={() => setMobileMenuOpen(false)}
                                className="rounded-lg px-3 py-2 text-[15px] text-bd-ink-2 transition-colors hover:bg-bd-surface-2 hover:text-bd-brand-deep"
                            >
                                {t(link.key)}
                            </a>
                        ))}
                        <div className="mt-2 flex flex-col gap-2 border-t border-bd-line pt-3">
                            {isAuthenticated ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center justify-center rounded-[10px] bg-bd-brand px-4 py-2.5 text-[14.5px] font-semibold text-white"
                                >
                                    {t('home.nav.dashboard')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center justify-center rounded-[10px] border border-bd-line bg-bd-surface px-4 py-2.5 text-[14.5px] font-semibold text-bd-ink"
                                    >
                                        {t('home.nav.login')}
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="inline-flex items-center justify-center rounded-[10px] bg-bd-brand px-4 py-2.5 text-[14.5px] font-semibold text-white"
                                    >
                                        {t('home.nav.register')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </nav>
                </div>
            )}
        </header>
    );
}
