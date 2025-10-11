import { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import Footer from '@/Components/LandingPage/Footer';
import { PageProps } from '@/Types';

interface GuestLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function GuestLayout({ children, title = 'ICC München' }: GuestLayoutProps) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-background flex flex-col">
                {/* Navigation Header */}
                <nav className="border-b bg-card/50 backdrop-blur supports-[backdrop-filter]:bg-card/50 sticky top-0 z-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <Link className="flex-shrink-0 flex items-center gap-3" href="/">
                                    <img src="/Logo.png" alt="ICC München" className="h-10 w-10 object-contain" />
                                    <h1 className="text-2xl font-bold bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red bg-clip-text text-transparent">
                                        ICC München
                                    </h1>
                                </Link>
                                <div className="hidden md:ml-10 md:flex md:space-x-2">
                                    <Button variant="ghost" asChild>
                                        <Link href="/#about">À propos</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#features">Activitités</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#trainings">Formations</Link>
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href="/#contact">Contact</Link>
                                    </Button>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                {auth?.user ? (
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
                        </div>
                    </div>
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
