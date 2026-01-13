import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    PlusIcon,
    DocumentTextIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    ArchiveBoxIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import type {
    DepartmentReport,
    ReportStatus,
    ReportType,
    ReportPeriodType,
    SelectOption,
    Department,
    PaginatedData,
} from '@/Types/report';

interface Props {
    reports: PaginatedData<DepartmentReport>;
    departments: Department[];
    statuses: SelectOption[];
    types: SelectOption[];
    periodTypes: SelectOption[];
    filters: {
        department_id?: number;
        status?: ReportStatus;
        type?: ReportType;
        period_type?: ReportPeriodType;
        year?: number;
        search?: string;
    };
}

const statusConfig: Record<ReportStatus, { label: string; color: string; icon: React.ElementType }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', icon: ClockIcon },
    pending_review: { label: 'En attente', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', icon: ClockIcon },
    under_review: { label: 'En révision', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: ClockIcon },
    revision_requested: { label: 'Révision demandée', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', icon: XCircleIcon },
    approved: { label: 'Approuvé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: CheckCircleIcon },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: XCircleIcon },
    published: { label: 'Publié', color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', icon: CheckCircleIcon },
    archived: { label: 'Archivé', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', icon: ArchiveBoxIcon },
};

export default function ReportsIndex({ reports, departments, statuses, types, periodTypes, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [showFilters, setShowFilters] = useState(false);
    const [deleteReport, setDeleteReport] = useState<DepartmentReport | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('reports.index'), { ...filters, search }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string | number | undefined) => {
        router.get(route('reports.index'), { ...filters, [key]: value || undefined }, { preserveState: true });
    };

    const handleDelete = () => {
        if (!deleteReport) return;
        setIsDeleting(true);
        router.delete(route('reports.destroy', deleteReport.uuid), {
            onSuccess: () => {
                toast.success('Rapport supprimé avec succès');
                setDeleteReport(null);
            },
            onError: () => toast.error('Erreur lors de la suppression'),
            onFinish: () => setIsDeleting(false),
        });
    };

    const handleGeneratePdf = (report: DepartmentReport) => {
        router.post(route('reports.generate-pdf', report.uuid), {}, {
            onSuccess: () => toast.success('PDF généré et stocké avec succès'),
            onError: () => toast.error('Erreur lors de la génération du PDF'),
        });
    };

    const handleDownloadPdf = (report: DepartmentReport) => {
        window.open(route('reports.download-pdf', report.uuid), '_blank');
    };

    return (
        <DashboardLayout>
            <Head title="Rapports" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Rapports Départementaux
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Gérez et générez vos rapports d'activité
                        </p>
                    </div>
                    <Link
                        href={route('reports.create')}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        <PlusIcon className="w-5 h-5" />
                        Nouveau Rapport
                    </Link>
                </div>

                {/* Search and Filters */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div className="flex flex-col sm:flex-row gap-4">
                        <form onSubmit={handleSearch} className="flex-1">
                            <div className="relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Rechercher un rapport..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </form>
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg border transition ${
                                showFilters
                                    ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-400'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                            }`}
                        >
                            <FunnelIcon className="w-5 h-5" />
                            Filtres
                        </button>
                    </div>

                    {showFilters && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <select
                                value={filters.department_id || ''}
                                onChange={(e) => handleFilterChange('department_id', e.target.value ? Number(e.target.value) : undefined)}
                                className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Tous les départements</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>{dept.name}</option>
                                ))}
                            </select>
                            <select
                                value={filters.status || ''}
                                onChange={(e) => handleFilterChange('status', e.target.value || undefined)}
                                className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Tous les statuts</option>
                                {statuses.map((s) => (
                                    <option key={s.value} value={s.value}>{s.label}</option>
                                ))}
                            </select>
                            <select
                                value={filters.type || ''}
                                onChange={(e) => handleFilterChange('type', e.target.value || undefined)}
                                className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Tous les types</option>
                                {types.map((t) => (
                                    <option key={t.value} value={t.value}>{t.label}</option>
                                ))}
                            </select>
                            <select
                                value={filters.period_type || ''}
                                onChange={(e) => handleFilterChange('period_type', e.target.value || undefined)}
                                className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Toutes les périodes</option>
                                {periodTypes.map((p) => (
                                    <option key={p.value} value={p.value}>{p.label}</option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>

                {/* Reports List */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {reports.data.length === 0 ? (
                        <div className="p-12 text-center">
                            <DocumentTextIcon className="w-12 h-12 mx-auto text-gray-400" />
                            <h3 className="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                                Aucun rapport
                            </h3>
                            <p className="mt-2 text-gray-500 dark:text-gray-400">
                                Commencez par créer votre premier rapport.
                            </p>
                            <Link
                                href={route('reports.create')}
                                className="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                <PlusIcon className="w-5 h-5" />
                                Créer un rapport
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Rapport
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Département
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Période
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Progression
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {reports.data.map((report) => {
                                        const status = statusConfig[report.status] || statusConfig.draft;
                                        const StatusIcon = status.icon;
                                        return (
                                            <tr key={report.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <td className="px-6 py-4">
                                                    <div>
                                                        <Link
                                                            href={route('reports.show', report.uuid)}
                                                            className="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400"
                                                        >
                                                            {report.title}
                                                        </Link>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            {report.author?.full_name}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {report.department?.name}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {report.period_label}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${status.color}`}>
                                                        <StatusIcon className="w-3 h-3" />
                                                        {status.label}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="w-24">
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                <div
                                                                    className="bg-indigo-600 h-2 rounded-full"
                                                                    style={{ width: `${report.progress || 0}%` }}
                                                                />
                                                            </div>
                                                            <span className="text-xs text-gray-500">{report.progress || 0}%</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link
                                                            href={route('reports.show', report.uuid)}
                                                            className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                            title="Voir"
                                                        >
                                                            <EyeIcon className="w-5 h-5" />
                                                        </Link>
                                                        {report.can_edit && (
                                                            <Link
                                                                href={route('reports.edit', report.uuid)}
                                                                className="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="w-5 h-5" />
                                                            </Link>
                                                        )}
                                                        <button
                                                            onClick={() => handleDownloadPdf(report)}
                                                            className="p-1 text-gray-400 hover:text-green-600 dark:hover:text-green-400"
                                                            title="Télécharger PDF"
                                                        >
                                                            <ArrowDownTrayIcon className="w-5 h-5" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleGeneratePdf(report)}
                                                            className="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                                            title="Générer et stocker PDF"
                                                        >
                                                            <DocumentDuplicateIcon className="w-5 h-5" />
                                                        </button>
                                                        {(report.status === 'draft' || report.status === 'archived') && (
                                                            <button
                                                                onClick={() => setDeleteReport(report)}
                                                                className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                                                title="Supprimer"
                                                            >
                                                                <TrashIcon className="w-5 h-5" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    {(reports.meta?.last_page ?? reports.last_page ?? 1) > 1 && (
                        <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Affichage de {reports.meta?.from ?? reports.from} à {reports.meta?.to ?? reports.to} sur {reports.meta?.total ?? reports.total} rapports
                            </p>
                            <div className="flex gap-2">
                                {(reports.links?.prev || reports.prev_page_url) && (
                                    <Link
                                        href={(reports.links?.prev ?? reports.prev_page_url) as string}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Précédent
                                    </Link>
                                )}
                                {(reports.links?.next || reports.next_page_url) && (
                                    <Link
                                        href={(reports.links?.next ?? reports.next_page_url) as string}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Suivant
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteReport}
                onOpenChange={(open) => !open && setDeleteReport(null)}
                onConfirm={handleDelete}
                title="Supprimer le rapport"
                description={`Êtes-vous sûr de vouloir supprimer le rapport "${deleteReport?.title}" ? Cette action est irréversible.`}
                isDeleting={isDeleting}
            />
        </DashboardLayout>
    );
}
