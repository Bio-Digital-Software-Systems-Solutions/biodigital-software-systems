import { PageProps } from '@/Types';
import { Head, Link, usePage } from '@inertiajs/react';
import HeroCarousel, { HeroSlide } from '@/Components/HeroCarousel';
import AboutSection from '@/Components/LandingPage/AboutSection';
import OurValues from '@/Components/LandingPage/OurValues';
import FeaturesSection from '@/Components/LandingPage/FeaturesSection';
import TrainingBrowseSection from '@/Components/LandingPage/TrainingBrowseSection';
import ContactSection from '@/Components/LandingPage/ContactSection';
import SectionRenderer, { type HomepageSectionData } from '@/Components/LandingPage/SectionRenderer';
import Footer from '@/Components/LandingPage/Footer';
import { Button } from '@/Components/ui/button';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { Toaster } from 'sonner';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

interface GlobalStats {
    total_churches: number;
    total_countries: number;
    total_members: number;
    europe: number;
    africa: number;
    americas: number;
    asia: number;
    oceania: number;
}

interface WelcomeProps extends PageProps {
    laravelVersion: string;
    phpVersion: string;
    heroSlides: HeroSlide[];
    globalStats: GlobalStats;
    sections?: HomepageSectionData[];
    hasConfiguredSections?: boolean;
}

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
    heroSlides,
    globalStats,
    sections = [],
    hasConfiguredSections = false,
}: WelcomeProps) {
    const appName = usePage<PageProps>().props.app.name;
    const { t } = useTranslation();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Fallback slides if no hero slides in database
    const defaultSlides: HeroSlide[] = [
        {
            id: 1,
            title: "",
            description: t('home.hero.slide1.description'),
            media_type: 'image',
            media_url: "1.png",
            cta_text: auth.user ? t('home.hero.cta.dashboard') : t('home.hero.cta.start'),
            cta_link: auth.user ? "/dashboard" : "/register",
            overlay_opacity: 0.5,
        },
        {
            id: 2,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "2.png",
            cta_text: t('home.hero.cta.learnMore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 3,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "3.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 4,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "4.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 5,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "5.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 11,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "11.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 15,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "15.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 17,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "17.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 20,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "20.png",
            cta_text: t('home.hero.cta.explore'),
            cta_link: "#features",
            overlay_opacity: 0.5,
        }
    ];

    const slides = heroSlides.length > 0 ? heroSlides : defaultSlides;
    hasConfiguredSections = false; // Force fallback to hard-coded sections for now, until we seed some default sections in the DB

    return (
        <>
            <Head title={`${t('home.meta.title')} - ${appName}`} />
            <div className="min-h-screen bg-background">
                {/* Navigation Header */}
                <nav className="border-b bg-card/50 backdrop-blur supports-[backdrop-filter]:bg-card/50 sticky top-0 z-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <Link className="flex-shrink-0 flex items-center gap-2 sm:gap-3" href="/">
                                    <img src="/Logo.png" alt={appName} className="h-8 w-8 sm:h-10 sm:w-10 object-contain" />
                                    <h1 className="text-lg sm:text-2xl font-bold bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red bg-clip-text text-transparent">
                                        {appName}
                                    </h1>
                                </Link>
                                {/* Desktop Navigation */}
                                <div className="hidden lg:ml-10 lg:flex lg:space-x-2">
                                    <Button variant="ghost" asChild>
                                        <a href="#about">{t('home.nav.about')}</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#features">{t('home.nav.activities')}</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#trainings">{t('home.nav.trainings')}</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#contact">{t('home.nav.contact')}</a>
                                    </Button>
                                </div>
                            </div>
                            {/* Desktop Auth Buttons */}
                            <div className="hidden md:flex items-center gap-3">
                                <LanguageSwitcher />
                                {auth.user ? (
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
                                    <a href="#about" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.about')}
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#features" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.activities')}
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#trainings" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.trainings')}
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#contact" onClick={() => setMobileMenuOpen(false)}>
                                        {t('home.nav.contact')}
                                    </a>
                                </Button>
                                <div className="pt-4 border-t mt-2 space-y-2">
                                    {auth.user ? (
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

                {/* Hero Section with Carousel */}
                <HeroCarousel slides={slides} />

                {/* Our Values Section (always rendered above dynamic sections) */}
                {/* <OurValues /> */}

                {/* Dynamic sections from DB.
                    - If the admin has configured sections (even if all inactive), respect that — render only the active ones.
                    - Only fall back to hard-coded defaults when the DB has zero sections at all (e.g. fresh install before seeding). */}
                   
                {hasConfiguredSections ? (
                    sections.map((section) => (
                        <SectionRenderer
                            key={section.id}
                            section={section}
                            globalStats={globalStats}
                            isAuthenticated={!!auth.user}
                        />
                    ))
                ) : (
                    <>
                        <AboutSection globalStats={globalStats} />
                        <FeaturesSection />
                        <TrainingBrowseSection />
                        <ContactSection isAuthenticated={!!auth.user} />
                    </>
                )}

                {/* Footer */}
                <Footer />

                {/* Toast notifications */}
                <Toaster position="top-right" richColors closeButton />
            </div>
        </>
    );
}
