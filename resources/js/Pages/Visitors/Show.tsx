import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeftIcon, PencilIcon } from '@heroicons/react/24/outline';

interface VisitorDetail {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    source: string | null;
    first_visit_date: string;
    status: string;
    notes: string | null;
    visits: Array<{
        uuid: string;
        visitable: { name: string } | null;
        integration_score: number;
        integration_status: string;
        first_visited_at: string;
    }>;
}

interface Props {
    visitor: VisitorDetail;
}

const statusColors: Record<string, string> = {
    visiting: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    progressing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    ready: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    integrated: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
};

function getScoreColor(score: number): string {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    if (score >= 30) return 'bg-orange-500';
    return 'bg-red-500';
}

export default function VisitorShow({ visitor }: Props) {
    return (
        <DashboardLayout>
            <Head title={`${visitor.first_name} ${visitor.last_name}`} />
            <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/visitors">
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            {visitor.first_name} {visitor.last_name}
                        </h1>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/visitors/${visitor.uuid}/edit`}>
                            <PencilIcon className="h-4 w-4 mr-2" />
                            Modifier
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {visitor.email && <p className="text-sm"><span className="font-medium">Email:</span> {visitor.email}</p>}
                            {visitor.phone && <p className="text-sm"><span className="font-medium">Téléphone:</span> {visitor.phone}</p>}
                            <p className="text-sm"><span className="font-medium">Première visite:</span> {new Date(visitor.first_visit_date).toLocaleDateString('fr-FR')}</p>
                            {visitor.source && <p className="text-sm"><span className="font-medium">Source:</span> {visitor.source}</p>}
                            {visitor.notes && <p className="text-sm"><span className="font-medium">Notes:</span> {visitor.notes}</p>}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Groupes / Départements</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {visitor.visits.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Aucun groupe associé.</p>
                            ) : (
                                <div className="space-y-3">
                                    {visitor.visits.map((visit) => (
                                        <div key={visit.uuid} className="flex items-center justify-between p-3 border dark:border-gray-700 rounded-lg">
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">
                                                    {visit.visitable?.name || 'N/A'}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    Depuis le {new Date(visit.first_visited_at).toLocaleDateString('fr-FR')}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <div className="w-20">
                                                    <div className="flex items-center justify-between text-xs mb-1">
                                                        <span className="text-gray-500">Score</span>
                                                        <span className="font-medium">{Math.round(visit.integration_score)}%</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                        <div
                                                            className={`h-1.5 rounded-full ${getScoreColor(visit.integration_score)}`}
                                                            style={{ width: `${Math.min(100, visit.integration_score)}%` }}
                                                        />
                                                    </div>
                                                </div>
                                                <Badge className={statusColors[visit.integration_status] || ''} variant="secondary">
                                                    {visit.integration_status}
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}
