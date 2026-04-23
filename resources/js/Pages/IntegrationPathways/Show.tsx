import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import type { IntegrationPathwayTemplate } from '@/Types/visitor';

interface Props {
    template: IntegrationPathwayTemplate;
}

const typeLabels: Record<string, string> = {
    attendance_count: 'Nombre de présences',
    activity_participation: 'Participation aux activités',
    meeting_attendance: 'Présence aux réunions',
    training_completion: 'Formations complétées',
    manual_approval: 'Approbation manuelle',
    custom: 'Personnalisé',
};

export default function IntegrationPathwayShow({ template }: Props) {
    return (
        <DashboardLayout>
            <Head title={template.name} />
            <div className="max-w-3xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex items-center gap-4 mb-6">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/integration-pathways">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{template.name}</h1>
                        {template.description && (
                            <p className="text-sm text-gray-500 dark:text-gray-400">{template.description}</p>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Étapes d'intégration</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {template.steps.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Aucune étape définie.</p>
                        ) : (
                            <div className="space-y-3">
                                {template.steps
                                    .sort((a, b) => a.order_index - b.order_index)
                                    .map((step, index) => (
                                        <div key={step.uuid} className="flex items-start gap-3 p-3 border dark:border-gray-700 rounded-lg">
                                            <div className="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center shrink-0">
                                                <span className="text-sm font-bold text-purple-700 dark:text-purple-300">{index + 1}</span>
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="font-medium text-gray-900 dark:text-white">{step.name}</h4>
                                                    <Badge variant="secondary">{typeLabels[step.type] || step.type}</Badge>
                                                    {!step.is_required && <Badge variant="outline">Optionnel</Badge>}
                                                </div>
                                                {step.description && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{step.description}</p>
                                                )}
                                                <div className="flex gap-4 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    <span>Poids: {step.weight}</span>
                                                    {step.criteria && Object.entries(step.criteria).map(([key, value]) => (
                                                        <span key={key}>{key}: {value}</span>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
