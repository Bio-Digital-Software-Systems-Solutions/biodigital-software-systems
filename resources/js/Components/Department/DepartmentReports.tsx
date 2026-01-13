import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    PlusIcon,
    EyeIcon,
    DocumentArrowDownIcon,
    ArrowPathIcon,
    ChartBarIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface Report {
    uuid: string;
    title: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    status_color: string;
    period_label: string;
    period_start: string;
    period_end: string;
    author: {
        id: number;
        name: string;
    } | null;
    created_at: string;
}

interface Props {
    departmentId: number;
    departmentUuid: string;
    reports: Report[];
    canManage: boolean;
}

export default function DepartmentReports({ departmentId, departmentUuid, reports, canManage }: Props) {
    const [search, setSearch] = useState('');
    const [generatingPdf, setGeneratingPdf] = useState<string | null>(null);

    const filteredReports = reports.filter(report =>
        report.title.toLowerCase().includes(search.toLowerCase()) ||
        report.period_label.toLowerCase().includes(search.toLowerCase())
    );

    const handleGeneratePdf = async (reportUuid: string) => {
        setGeneratingPdf(reportUuid);
        router.post(
            `/reports/${reportUuid}/generate-pdf`,
            {},
            {
                onSuccess: () => {
                    toast.success('PDF généré avec succès');
                    setGeneratingPdf(null);
                },
                onError: () => {
                    toast.error('Erreur lors de la génération du PDF');
                    setGeneratingPdf(null);
                },
            }
        );
    };

    const handleDownloadPdf = (reportUuid: string) => {
        window.open(`/reports/${reportUuid}/download-pdf`, '_blank');
    };

    const getStatusBadgeClass = (status: string) => {
        switch (status) {
            case 'draft':
                return 'bg-gray-500';
            case 'pending_review':
            case 'under_review':
                return 'bg-yellow-500';
            case 'approved':
                return 'bg-blue-500';
            case 'published':
                return 'bg-green-500';
            case 'revision_requested':
                return 'bg-orange-500';
            case 'rejected':
                return 'bg-red-500';
            default:
                return 'bg-gray-500';
        }
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Rapports du Département</CardTitle>
                        <CardDescription>
                            {reports.length} rapport(s) créé(s)
                        </CardDescription>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/reports?department_id=${departmentId}`}>
                                Voir tous
                            </Link>
                        </Button>
                        {canManage && (
                            <Button asChild>
                                <Link href={`/reports/create?department_id=${departmentId}`}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouveau Rapport
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {/* Search */}
                {reports.length > 0 && (
                    <div className="mb-4">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Rechercher un rapport..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </div>
                )}

                {filteredReports.length > 0 ? (
                    <div className="space-y-3">
                        {filteredReports.map((report) => (
                            <div
                                key={report.uuid}
                                className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <ChartBarIcon className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {report.title}
                                            </span>
                                            <Badge className={getStatusBadgeClass(report.status)}>
                                                {report.status_label}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center gap-3 text-xs text-gray-400 mt-1">
                                            <span>{report.type_label}</span>
                                            <span>{report.period_label}</span>
                                            {report.author && (
                                                <span>par {report.author.name}</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/reports/${report.uuid}`}>
                                            <EyeIcon className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    {canManage && (
                                        <>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleGeneratePdf(report.uuid)}
                                                disabled={generatingPdf === report.uuid}
                                            >
                                                {generatingPdf === report.uuid ? (
                                                    <ArrowPathIcon className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <DocumentTextIcon className="h-4 w-4" />
                                                )}
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDownloadPdf(report.uuid)}
                                            >
                                                <DocumentArrowDownIcon className="h-4 w-4" />
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                        <ChartBarIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p>
                            {search ? 'Aucun rapport trouvé' : 'Aucun rapport créé pour ce département'}
                        </p>
                        {canManage && !search && (
                            <Button className="mt-4" asChild>
                                <Link href={`/reports/create?department_id=${departmentId}`}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Créer un rapport
                                </Link>
                            </Button>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
