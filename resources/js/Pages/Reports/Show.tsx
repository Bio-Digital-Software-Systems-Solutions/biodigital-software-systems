import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    PaperAirplaneIcon,
    CheckIcon,
    XMarkIcon,
    ArrowPathIcon,
    ArchiveBoxIcon,
    DocumentTextIcon,
    ChartBarIcon,
    TableCellsIcon,
    ListBulletIcon,
    ClockIcon,
    UserIcon,
    CalendarIcon,
    TagIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type {
    DepartmentReport,
    ReportSection,
    ReportAggregatedData,
    ReportStatus,
} from '@/Types/report';

interface Props {
    report: DepartmentReport;
    aggregatedData?: ReportAggregatedData;
    canEdit: boolean;
    canSubmit: boolean;
    canApprove: boolean;
    canPublish: boolean;
}

const statusConfig: Record<ReportStatus, { label: string; color: string; bgColor: string }> = {
    draft: { label: 'Brouillon', color: 'text-gray-700', bgColor: 'bg-gray-100 dark:bg-gray-700' },
    pending_review: { label: 'En attente de révision', color: 'text-yellow-700', bgColor: 'bg-yellow-100 dark:bg-yellow-900/30' },
    under_review: { label: 'En cours de révision', color: 'text-blue-700', bgColor: 'bg-blue-100 dark:bg-blue-900/30' },
    revision_requested: { label: 'Révision demandée', color: 'text-orange-700', bgColor: 'bg-orange-100 dark:bg-orange-900/30' },
    approved: { label: 'Approuvé', color: 'text-green-700', bgColor: 'bg-green-100 dark:bg-green-900/30' },
    rejected: { label: 'Rejeté', color: 'text-red-700', bgColor: 'bg-red-100 dark:bg-red-900/30' },
    published: { label: 'Publié', color: 'text-emerald-700', bgColor: 'bg-emerald-100 dark:bg-emerald-900/30' },
    archived: { label: 'Archivé', color: 'text-purple-700', bgColor: 'bg-purple-100 dark:bg-purple-900/30' },
};

const sectionIcons: Record<string, React.ElementType> = {
    text: DocumentTextIcon,
    metrics: ChartBarIcon,
    chart: ChartBarIcon,
    table: TableCellsIcon,
    checklist: ListBulletIcon,
    list: ListBulletIcon,
    timeline: ClockIcon,
};

export default function ReportShow({ report, aggregatedData, canEdit, canSubmit, canApprove, canPublish }: Props) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [showApprovalModal, setShowApprovalModal] = useState(false);
    const [approvalComments, setApprovalComments] = useState('');

    const status = statusConfig[report.status] || statusConfig.draft;

    const handleAction = (action: string, data: Record<string, any> = {}) => {
        setIsProcessing(true);
        router.post(route(`reports.${action}`, report.uuid), data, {
            onSuccess: () => {
                toast.success('Action effectuée avec succès');
                setShowApprovalModal(false);
            },
            onError: () => toast.error('Erreur lors de l\'action'),
            onFinish: () => setIsProcessing(false),
        });
    };

    const handleGeneratePdf = () => {
        setIsProcessing(true);
        router.post(route('reports.generate-pdf', report.uuid), {}, {
            onSuccess: () => toast.success('PDF généré et stocké dans les documents'),
            onError: () => toast.error('Erreur lors de la génération'),
            onFinish: () => setIsProcessing(false),
        });
    };

    const handleDownloadPdf = () => {
        window.open(route('reports.download-pdf', report.uuid), '_blank');
    };

    const handleApprove = (approved: boolean) => {
        handleAction('approve', { approved, comments: approvalComments });
    };

    return (
        <DashboardLayout>
            <Head title={report.title} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-start gap-4">
                        <Link
                            href={route('reports.index')}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {report.title}
                                </h1>
                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${status.bgColor} ${status.color}`}>
                                    {status.label}
                                </span>
                            </div>
                            <div className="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span className="flex items-center gap-1">
                                    <CalendarIcon className="w-4 h-4" />
                                    {report.period_label}
                                </span>
                                <span className="flex items-center gap-1">
                                    <UserIcon className="w-4 h-4" />
                                    {report.author?.full_name}
                                </span>
                                {report.department && (
                                    <span>{report.department.name}</span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {canEdit && (
                            <Link
                                href={route('reports.edit', report.uuid)}
                                className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                <PencilIcon className="w-4 h-4" />
                                Modifier
                            </Link>
                        )}
                        <button
                            onClick={handleDownloadPdf}
                            className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4" />
                            Télécharger PDF
                        </button>
                        <button
                            onClick={handleGeneratePdf}
                            disabled={isProcessing}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                        >
                            <DocumentDuplicateIcon className="w-4 h-4" />
                            Générer & Stocker
                        </button>
                    </div>
                </div>

                {/* Action Buttons based on status */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Actions:</span>
                        </div>
                        <div className="flex items-center gap-2">
                            {canEdit && report.status === 'draft' && (
                                <button
                                    onClick={() => handleAction('populate')}
                                    disabled={isProcessing}
                                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-blue-300 text-blue-700 dark:border-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                >
                                    <ArrowPathIcon className="w-4 h-4" />
                                    Remplir avec données
                                </button>
                            )}
                            {canSubmit && (
                                <button
                                    onClick={() => handleAction('submit')}
                                    disabled={isProcessing}
                                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    <PaperAirplaneIcon className="w-4 h-4" />
                                    Soumettre
                                </button>
                            )}
                            {canApprove && (
                                <>
                                    <button
                                        onClick={() => setShowApprovalModal(true)}
                                        className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                                    >
                                        <CheckIcon className="w-4 h-4" />
                                        Approuver
                                    </button>
                                    <button
                                        onClick={() => {
                                            setShowApprovalModal(true);
                                        }}
                                        className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                    >
                                        <XMarkIcon className="w-4 h-4" />
                                        Demander révision
                                    </button>
                                </>
                            )}
                            {canPublish && (
                                <button
                                    onClick={() => handleAction('publish')}
                                    disabled={isProcessing}
                                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition"
                                >
                                    <CheckIcon className="w-4 h-4" />
                                    Publier
                                </button>
                            )}
                            {report.status === 'published' && (
                                <button
                                    onClick={() => handleAction('archive')}
                                    disabled={isProcessing}
                                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    <ArchiveBoxIcon className="w-4 h-4" />
                                    Archiver
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Progress Bar */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Progression du rapport
                        </span>
                        <span className="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                            {report.progress || 0}%
                        </span>
                    </div>
                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div
                            className="bg-indigo-600 h-3 rounded-full transition-all duration-300"
                            style={{ width: `${report.progress || 0}%` }}
                        />
                    </div>
                </div>

                {/* Executive Summary */}
                {report.executive_summary && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Résumé Exécutif
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
                            {report.executive_summary}
                        </p>
                    </div>
                )}

                {/* Key Metrics */}
                {aggregatedData?.summary && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Indicateurs Clés
                        </h2>
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <MetricCard
                                label="Activités"
                                value={aggregatedData.summary.total_activities}
                                trend={aggregatedData.trends?.activities}
                            />
                            <MetricCard
                                label="Heures"
                                value={aggregatedData.summary.total_hours}
                                unit="h"
                                trend={aggregatedData.trends?.hours}
                            />
                            <MetricCard
                                label="Taux de réalisation"
                                value={aggregatedData.summary.completion_rate}
                                unit="%"
                            />
                            <MetricCard
                                label="Objectifs complétés"
                                value={`${aggregatedData.summary.objectives_completed}/${aggregatedData.summary.objectives_total}`}
                            />
                            <MetricCard
                                label="Participants"
                                value={aggregatedData.summary.unique_participants}
                            />
                            <MetricCard
                                label="Projets actifs"
                                value={aggregatedData.summary.projects_active}
                            />
                        </div>
                    </div>
                )}

                {/* Sections */}
                {report.sections && report.sections.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Sections du rapport
                        </h2>
                        {report.sections.map((section) => (
                            <SectionCard key={section.uuid} section={section} />
                        ))}
                    </div>
                )}

                {/* Tags */}
                {report.tags && report.tags.length > 0 && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <div className="flex items-center gap-2">
                            <TagIcon className="w-5 h-5 text-gray-400" />
                            <div className="flex flex-wrap gap-2">
                                {report.tags.map((tag) => (
                                    <span
                                        key={tag.id}
                                        className="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full text-sm"
                                    >
                                        {tag.tag}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Approval Modal */}
            {showApprovalModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Décision d'approbation
                        </h3>
                        <textarea
                            value={approvalComments}
                            onChange={(e) => setApprovalComments(e.target.value)}
                            placeholder="Commentaires (optionnel)..."
                            rows={4}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white mb-4"
                        />
                        <div className="flex justify-end gap-2">
                            <button
                                onClick={() => setShowApprovalModal(false)}
                                className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleApprove(false)}
                                disabled={isProcessing}
                                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                            >
                                Demander révision
                            </button>
                            <button
                                onClick={() => handleApprove(true)}
                                disabled={isProcessing}
                                className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                            >
                                Approuver
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}

function MetricCard({ label, value, unit, trend }: {
    label: string;
    value: number | string;
    unit?: string;
    trend?: { direction: string; percentage: number };
}) {
    return (
        <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
            <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                {value}{unit}
            </div>
            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">{label}</div>
            {trend && (
                <div className={`text-xs mt-1 ${
                    trend.direction === 'up' ? 'text-green-600' :
                    trend.direction === 'down' ? 'text-red-600' : 'text-gray-500'
                }`}>
                    {trend.percentage > 0 ? '+' : ''}{trend.percentage}%
                </div>
            )}
        </div>
    );
}

function SectionCard({ section }: { section: ReportSection }) {
    const Icon = sectionIcons[section.type] || DocumentTextIcon;

    return (
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <Icon className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                            {section.title}
                        </h3>
                        {section.description && (
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {section.description}
                            </p>
                        )}
                    </div>
                </div>
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    section.is_complete
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                }`}>
                    {section.is_complete ? 'Complété' : 'En cours'}
                </span>
            </div>

            {/* Section Content Preview */}
            {section.content && (
                <div className="text-sm text-gray-600 dark:text-gray-300">
                    {section.type === 'text' && section.content.text && (
                        <p className="whitespace-pre-wrap">{section.content.text}</p>
                    )}
                    {section.type === 'checklist' && section.content.items && (
                        <ul className="space-y-1">
                            {(section.content.items as any[]).slice(0, 5).map((item, idx) => (
                                <li key={idx} className="flex items-center gap-2">
                                    <span className={item.completed ? 'text-green-600' : 'text-gray-400'}>
                                        {item.completed ? '✓' : '○'}
                                    </span>
                                    {item.label}
                                </li>
                            ))}
                            {(section.content.items as any[]).length > 5 && (
                                <li className="text-gray-400">
                                    + {(section.content.items as any[]).length - 5} autres...
                                </li>
                            )}
                        </ul>
                    )}
                    {section.type === 'metrics' && section.content.metrics && (
                        <div className="grid grid-cols-3 gap-4">
                            {(section.content.metrics as any[]).slice(0, 6).map((metric, idx) => (
                                <div key={idx} className="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                    <div className="font-bold text-indigo-600 dark:text-indigo-400">
                                        {metric.value}{metric.unit}
                                    </div>
                                    <div className="text-xs text-gray-500">{metric.label}</div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
