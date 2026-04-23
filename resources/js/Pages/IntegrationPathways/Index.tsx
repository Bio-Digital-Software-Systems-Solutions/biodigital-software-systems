import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import { toast } from 'sonner';
import { PlusIcon, TrashIcon, PencilIcon } from '@heroicons/react/24/outline';
import type { IntegrationPathwayTemplate } from '@/Types/visitor';

interface Props {
    templates: IntegrationPathwayTemplate[];
}

export default function IntegrationPathwaysIndex({ templates }: Props) {
    const { confirm } = useConfirm();

    const handleDelete = async (uuid: string) => {
        const confirmed = await confirm({
            title: 'Supprimer le parcours',
            message: 'Êtes-vous sûr de vouloir supprimer ce parcours d\'intégration ?',
            confirmText: 'Supprimer',
            cancelText: 'Annuler',
            type: 'danger',
        });
        if (!confirmed) return;
        router.delete(`/integration-pathways/${uuid}`, {
            onSuccess: () => toast.success('Parcours supprimé.'),
        });
    };

    return (
        <DashboardLayout>
            <Head title="Parcours d'intégration" />
            <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Parcours d'intégration</h1>
                </div>

                <div className="space-y-4">
                    {templates.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <p className="text-gray-500 dark:text-gray-400">Aucun parcours créé.</p>
                            </CardContent>
                        </Card>
                    ) : (
                        templates.map((template) => (
                            <Card key={template.uuid}>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <Link
                                                href={`/integration-pathways/${template.uuid}`}
                                                className="font-medium text-gray-900 dark:text-white hover:text-purple-600"
                                            >
                                                {template.name}
                                            </Link>
                                            <div className="flex items-center gap-2 mt-1">
                                                {template.is_default && <Badge className="bg-blue-100 text-blue-800">Par défaut</Badge>}
                                                {!template.is_active && <Badge className="bg-gray-100 text-gray-800">Inactif</Badge>}
                                                {template.target_type && (
                                                    <Badge variant="secondary">{template.target_type === 'group' ? 'Groupes' : 'Départements'}</Badge>
                                                )}
                                                <span className="text-xs text-gray-500">{template.steps_count || template.steps?.length || 0} étape(s)</span>
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/integration-pathways/${template.uuid}`}>
                                                    <PencilIcon className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-red-600"
                                                onClick={() => handleDelete(template.uuid)}
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </Button>
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
