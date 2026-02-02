import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CalendarIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';

interface User {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    avatar: string | null;
    created_at: string;
}

interface Props {
    user: User;
}

export default function PublicProfile({ user }: Props) {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            month: 'long',
            year: 'numeric',
        });
    };

    const getInitials = (name: string) => {
        if (!name) return '?';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return name.charAt(0).toUpperCase();
    };

    return (
        <DashboardLayout>
            <Head title={user.name} />

            <div className="p-6">
                <div className="max-w-md mx-auto">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="#" onClick={(e) => {
                                e.preventDefault();
                                window.history.back();
                            }}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                    </div>

                    {/* Profile Card */}
                    <Card className="overflow-hidden">
                        {/* Cover gradient */}
                        <div className="h-24 bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red" />

                        <CardContent className="pt-0 pb-8 -mt-12">
                            <div className="flex flex-col items-center text-center">
                                {/* Avatar */}
                                {user.avatar ? (
                                    <img
                                        src={`/storage/${user.avatar}`}
                                        alt={user.name}
                                        className="h-24 w-24 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow-lg"
                                    />
                                ) : (
                                    <div className="h-24 w-24 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-bold border-4 border-white dark:border-gray-800 shadow-lg">
                                        {getInitials(user.name)}
                                    </div>
                                )}

                                {/* Name */}
                                <h1 className="mt-4 text-2xl font-bold text-gray-900 dark:text-white">
                                    {user.name}
                                </h1>

                                {/* Member since */}
                                <div className="mt-3 flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                    <CalendarIcon className="h-4 w-4" />
                                    <span className="text-sm">
                                        Membre depuis {formatDate(user.created_at)}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}
