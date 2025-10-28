import { PageProps } from '@/Types';
import { Head, Link } from '@inertiajs/react';
import HeroCarousel, { HeroSlide } from '@/Components/HeroCarousel';
import AboutSection from '@/Components/LandingPage/AboutSection';
import OurValues from '@/Components/LandingPage/OurValues';
import FeaturesSection from '@/Components/LandingPage/FeaturesSection';
import TrainingBrowseSection from '@/Components/LandingPage/TrainingBrowseSection';
import ContactSection from '@/Components/LandingPage/ContactSection';
import Footer from '@/Components/LandingPage/Footer';
import { Button } from '@/Components/ui/button';
import { Toaster } from 'sonner';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';

interface WelcomeProps extends PageProps {
    laravelVersion: string;
    phpVersion: string;
    heroSlides: HeroSlide[];
}

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
    heroSlides,
}: WelcomeProps) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Fallback slides if no hero slides in database
    const defaultSlides: HeroSlide[] = [
        {
            id: 1,
            title: "",
            description: "Une eglise ordinaire oú l'amour de Dieu nous transforme en de vraies disciples de Christ",
            media_type: 'image',
            media_url: "1.png",
            cta_text: auth.user ? "Accéder au Dashboard" : "Commencer",
            cta_link: auth.user ? "/dashboard" : "/register",
            overlay_opacity: 0.5,
        },
        {
            id: 2,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "2.png",
            cta_text: "En savoir plus",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 3,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "3.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 4,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "4.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 5,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "5.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 11,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "11.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 15,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "15.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 17,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "17.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        },
        {
            id: 20,
            title: "",
            description: "",
            media_type: 'image',
            media_url: "20.png",
            cta_text: "Explorer",
            cta_link: "#features",
            overlay_opacity: 0.5,
        }
    ];

    const slides = heroSlides.length > 0 ? heroSlides : defaultSlides;

    return (
        <>
            <Head title="Bienvenue - ICC München" />
            <div className="min-h-screen bg-background">
                {/* Navigation Header */}
                <nav className="border-b bg-card/50 backdrop-blur supports-[backdrop-filter]:bg-card/50 sticky top-0 z-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <Link className="flex-shrink-0 flex items-center gap-2 sm:gap-3" href="/">
                                    <img src="/Logo.png" alt="ICC München" className="h-8 w-8 sm:h-10 sm:w-10 object-contain" />
                                    <h1 className="text-lg sm:text-2xl font-bold bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red bg-clip-text text-transparent">
                                        ICC München
                                    </h1>
                                </Link>
                                {/* Desktop Navigation */}
                                <div className="hidden lg:ml-10 lg:flex lg:space-x-2">
                                    <Button variant="ghost" asChild>
                                        <a href="#about">À propos</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#features">Activitités</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#trainings">Formations</a>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <a href="#contact">Contact</a>
                                    </Button>
                                </div>
                            </div>
                            {/* Desktop Auth Buttons */}
                            <div className="hidden md:flex items-center gap-3">
                                {auth.user ? (
                                    <Button asChild>
                                        <Link href={route('dashboard')}>
                                            Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button variant="ghost" asChild>
                                            <Link href={route('login')}>
                                                Se connecter
                                            </Link>
                                        </Button>
                                        <Button asChild>
                                            <Link href={route('register')}>
                                                S'inscrire
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                            {/* Mobile Menu Button */}
                            <div className="flex md:hidden items-center">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                    aria-label="Toggle menu"
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
                                        À propos
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#features" onClick={() => setMobileMenuOpen(false)}>
                                        Activitités
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#trainings" onClick={() => setMobileMenuOpen(false)}>
                                        Formations
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <a href="#contact" onClick={() => setMobileMenuOpen(false)}>
                                        Contact
                                    </a>
                                </Button>
                                <div className="pt-4 border-t mt-2 space-y-2">
                                    {auth.user ? (
                                        <Button className="w-full" asChild>
                                            <Link href={route('dashboard')}>
                                                Dashboard
                                            </Link>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button variant="outline" className="w-full" asChild>
                                                <Link href={route('login')}>
                                                    Se connecter
                                                </Link>
                                            </Button>
                                            <Button className="w-full" asChild>
                                                <Link href={route('register')}>
                                                    S'inscrire
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

                {/* About Section */}
                <AboutSection />

                {/* Our Values Section */}
                <OurValues />

                {/* Features Section */}
                <FeaturesSection />

                {/* Formations Section */}
                <TrainingBrowseSection />

                {/* Contact Section */}
                <ContactSection isAuthenticated={!!auth.user} />

                {/* Footer */}
                <Footer />

                {/* Toast notifications */}
                <Toaster position="top-right" richColors closeButton />
            </div>
        </>
    );
}
