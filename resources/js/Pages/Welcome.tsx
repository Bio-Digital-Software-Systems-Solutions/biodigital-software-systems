import { PageProps } from '@/Types';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Toaster } from 'sonner';
import GuestNavbar from '@/Components/LandingPage/GuestNavbar';
import HeroCarousel from '@/Components/LandingPage/HeroCarousel';
import AboutSection from '@/Components/LandingPage/AboutSection';
import ServicesSection from '@/Components/LandingPage/ServicesSection';
import TrainingBrowseSection from '@/Components/LandingPage/TrainingBrowseSection';
import ProcessSection from '@/Components/LandingPage/ProcessSection';
import ContactSection from '@/Components/LandingPage/ContactSection';
import SiteFooter from '@/Components/LandingPage/SiteFooter';

type WelcomeProps = PageProps;

/**
 * Public landing page (Bio-Digital Software Systems).
 *
 * Layout follows the approved proposal: sticky nav, hero slider, services,
 * the dynamic training browser (enrollment stays fully functional), the
 * process steps, a closing CTA and the footer.
 */
export default function Welcome({ auth }: WelcomeProps) {
    const appName = usePage<PageProps>().props.app.name;
    const { t } = useTranslation();

    return (
        <>
            <Head title={`${t('home.meta.title')} - ${appName}`} />
            <div id="top" className="min-h-screen bg-bd-bg font-body text-bd-ink antialiased">
                <GuestNavbar isAuthenticated={!!auth.user} />

                <main>
                    <HeroCarousel />
                    <AboutSection />
                    <ServicesSection />
                    {/* Formations: the dynamic browser keeps real enrollment working. */}
                    <TrainingBrowseSection />
                    <ProcessSection />
                    <ContactSection isAuthenticated={!!auth.user} />
                </main>

                <SiteFooter />

                <Toaster position="top-right" richColors closeButton />
            </div>
        </>
    );
}
