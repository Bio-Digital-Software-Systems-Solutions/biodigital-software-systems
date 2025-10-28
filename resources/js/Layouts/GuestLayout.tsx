import { PropsWithChildren, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import Footer from '@/Components/LandingPage/Footer';
import { PageProps } from '@/Types';
import { Menu, X } from 'lucide-react';

interface GuestLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function GuestLayout({ children, title = 'ICC München' }: GuestLayoutProps) {
    const { auth } = usePage<PageProps>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-background flex flex-col">
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
                            {/* Desktop Auth Buttons */}
                            <div className="hidden md:flex items-center gap-3">
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
                                    <Link href="/#about" onClick={() => setMobileMenuOpen(false)}>
                                        À propos
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#features" onClick={() => setMobileMenuOpen(false)}>
                                        Activitités
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#trainings" onClick={() => setMobileMenuOpen(false)}>
                                        Formations
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start"
                                    asChild
                                >
                                    <Link href="/#contact" onClick={() => setMobileMenuOpen(false)}>
                                        Contact
                                    </Link>
                                </Button>
                                <div className="pt-4 border-t mt-2 space-y-2">
                                    {auth?.user ? (
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
