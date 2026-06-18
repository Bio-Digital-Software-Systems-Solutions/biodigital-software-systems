import { PropsWithChildren, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import Footer from '@/Components/LandingPage/Footer';
import { PageProps } from '@/Types';
import { Menu, X } from 'lucide-react';

interface GuestLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function GuestLayout({ children, title }: GuestLayoutProps) {
    const { auth, app } = usePage<PageProps>().props;
    const { t } = useTranslation();
    const appName = app.name;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <>
            <Head title={title ?? appName} />
            <div className="min-h-screen bg-background flex flex-col">
                {/* Navigation Header */}
                <nav className="border-b bg-card/50 backdrop-blur supports-[backdrop-filter]:bg-card/50 sticky top-0 z-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <Link className="flex-shrink-0 flex items-center" href="/">
                                    <img src="/Logo.png" alt={appName} className="h-9 sm:h-11 w-auto object-contain" />
                                </Link>
                                {/* Desktop Navigation */}
                                <div className="hidden lg:ml-10 lg:flex lg:space-x-2">
                                    <Button variant="ghost" asChild>
                                        <Link href="/#about">{t('home.nav.about')}</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#features">{t('home.nav.activities')}</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#trainings">{t('home.nav.trainings')}</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#contact">{t('home.nav.contact')}</Link>
                                    </Button>
                                </div>
                            </div>
                            {/* Desktop Auth Buttons */}
                            <div className="hidden md:flex items-center gap-3">
                                <LanguageSwitcher />
                                {auth?.user ? (
                                    <Button asChild>
                                        <Link href={route('dashboard')}>
                                            {t('home.nav.dashboard')}
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button variant="ghost" asChild>
                                            <Link href={route('login')}>
                                                {t('home.nav.login')}
                                            </Link>
                                        </Button>
                                        <Button asChild>
                                            <Link href={route('register')}>
                                                {t('home.nav.register')}
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                            {/* Mobile Menu Button */}
                            <div className="flex md:hidden items-center gap-2">
                                <LanguageSwitcher />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                    aria-label={t('home.nav.toggleMenu')}
                                >
                                    {mobileMenuOpen ? (
                                        <X className="h-6 w-6" />
                                    ) : (
                                        <Menu className="h-6 w-6" />
                                    )}
                                </Button>
                            </div>
                        </div>
                    </div>
                    {/* Mobile Menu */}
                    {mobileMenuOpen && (
                        <div className="md:hidden border-t">
                            <div className="px-2 pt-2 pb-3 space-y-1">
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#about" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.about')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#features" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.activities')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#trainings" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.trainings')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#contact" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.contact')}
                                    </Link>
                                </Button>
                                <div className="pt-4 border-t mt-2 space-y-2">
                                    {auth?.user ? (
                                        <Button className="w-full" asChild>
                                            <Link href={route('dashboard')}>
                                                {t('home.nav.dashboard')}
                                            </Link>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button variant="outline" className="w-full" asChild>
                                                <Link href={route('login')}>
                                                    {t('home.nav.login')}
                                                </Link>
                                            </Button>
                                            <Button className="w-full" asChild>
                                                <Link href={route('register')}>
                                                    {t('home.nav.register')}
                                                </Link>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </nav>

                {/* Main Content */}
                <main className="flex-1">
                    {children}
                </main>

                {/* Footer */}
                <Footer />
            </div>
        </>
    );
}
