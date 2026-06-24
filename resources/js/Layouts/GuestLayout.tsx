import { PropsWithChildren } from 'react';
import { Head, usePage } from '@inertiajs/react';
import GuestNavbar from '@/Components/LandingPage/GuestNavbar';
import SiteFooter from '@/Components/LandingPage/SiteFooter';
import { PageProps } from '@/Types';

interface GuestLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function GuestLayout({ children, title }: GuestLayoutProps) {
    const { auth, app } = usePage<PageProps>().props;
    const appName = app.name;

    return (
        <>
            <Head title={title ?? appName} />
            <div className="flex min-h-screen flex-col bg-bd-bg font-body text-bd-ink antialiased">
                <GuestNavbar isAuthenticated={!!auth?.user} />

                <main className="flex-1">
                    {children}
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
