import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import type { Visitor } from '@/Types/visitor';

interface Props {
    visitors: {
        data: Array<Visitor & { visits_count: number; creator?: { id: number; name: string } }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { status?: string; search?: string };
}

const statusLabels: Record<string, string> = {
    active: 'Actif',
    inactive: 'Inactif',
    integrated: 'Intégré',
    archived: 'Archivé',
};

const statusColors: Record<string, string> = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    integrated: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    archived: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

export default function VisitorsIndex({ visitors, filters }: Props) {
    return (
        <DashboardLayout>
            <Head title="Visiteurs" />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Visiteurs</h1>
                    <Button asChild>
                        <Link href="/visitors/create">
                            <PlusIcon className="h-4 w-4 mr-2" />
                            Nouveau visiteur
                        </Link>
                    </Button>
                </div>

                <div className="mb-4">
                    <div className="relative max-w-sm">
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            placeholder="Rechercher..."
                            defaultValue={filters.search}
                            className="pl-9"
                            onChange={(e) => {
                                const url = new URL(window.location.href);
                                url.searchParams.set('search', e.target.value);
                                window.history.replaceState({}, '', url.toString());
                            }}
                        />
                    </div>
                </div>

                <div className="space-y-3">
                    {visitors.data.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <p className="text-gray-500 dark:text-gray-400">Aucun visiteur trouvé.</p>
                            </CardContent>
                        </Card>
                    ) : (
                        visitors.data.map((visitor) => (
                            <Card key={visitor.uuid} className="hover:shadow-md transition-shadow">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                                <span className="text-sm font-semibold text-purple-700 dark:text-purple-300">
                                                    {visitor.first_name[0]}{visitor.last_name[0]}
                                                </span>
                                            </div>
                                            <div>
                                                <Link
                                                    href={`/visitors/${visitor.uuid}`}
                                                    className="font-medium text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400"
                                                >
                                                    {visitor.first_name} {visitor.last_name}
                                                </Link>
                                                <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {visitor.email && <span>{visitor.email}</span>}
                                                    <Badge className={statusColors[visitor.status] || ''} variant="secondary">
                                                        {statusLabels[visitor.status] || visitor.status}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                            {visitor.visits_count} groupe{visitor.visits_count !== 1 ? 's' : ''}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
