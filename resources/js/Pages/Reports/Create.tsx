import React, { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    DocumentTextIcon,
    ArrowLeftIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    DocumentIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import type {
    ReportType,
    ReportPeriodType,
    SelectOption,
    Department,
    ReportTemplate,
} from '@/Types/report';

interface Props {
    departments: Department[];
    templates: ReportTemplate[];
    types: SelectOption[];
    periodTypes: SelectOption[];
    departmentId?: number;
}

export default function ReportsCreate({ departments, templates, types, periodTypes, departmentId }: Props) {
    const [selectedTemplate, setSelectedTemplate] = useState<ReportTemplate | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        department_id: departmentId || '',
        template_id: '',
        title: '',
        type: 'monthly_activity' as ReportType,
        period_type: 'monthly' as ReportPeriodType,
        period_start: '',
        period_end: '',
        executive_summary: '',
    });

    // Filter templates by selected department
    const filteredTemplates = templates.filter(
        (t) => !t.department_id || t.department_id === Number(data.department_id)
    );

    // When template is selected, update form
    useEffect(() => {
        if (selectedTemplate) {
            setData((prev) => ({
                ...prev,
                type: selectedTemplate.type,
                period_type: selectedTemplate.period_type,
                title: selectedTemplate.name,
            }));
        }
    }, [selectedTemplate]);

    // Calculate period dates based on period type
    const handlePeriodTypeChange = (periodType: ReportPeriodType) => {
        setData('period_type', periodType);

        const today = new Date();
        let start: Date;
        let end: Date;

        switch (periodType) {
            case 'weekly':
                const dayOfWeek = today.getDay();
                start = new Date(today);
                start.setDate(today.getDate() - dayOfWeek + 1);
                end = new Date(start);
                end.setDate(start.getDate() + 6);
                break;
            case 'monthly':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'quarterly':
                const quarter = Math.floor(today.getMonth() / 3);
                start = new Date(today.getFullYear(), quarter * 3, 1);
                end = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
                break;
            case 'annual':
                start = new Date(today.getFullYear(), 0, 1);
                end = new Date(today.getFullYear(), 11, 31);
                break;
            default:
                return;
        }

        setData((prev) => ({
            ...prev,
            period_start: start.toISOString().split('T')[0],
            period_end: end.toISOString().split('T')[0],
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('reports.store'), {
            onSuccess: () => toast.success('Rapport créé avec succès'),
            onError: () => toast.error('Erreur lors de la création du rapport'),
        });
    };

    return (
        <DashboardLayout>
            <Head title="Créer un rapport" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link
                        href={route('reports.index')}
                        className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Créer un rapport
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Configurez les détails de votre nouveau rapport
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Department Selection */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div className="flex items-center gap-3 mb-4">
                            <BuildingOfficeIcon className="w-5 h-5 text-indigo-600" />
                            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                                Département
                            </h2>
                        </div>

                        <select
                            value={data.department_id}
                            onChange={(e) => setData('department_id', e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Sélectionnez un département</option>
                            {departments.map((dept) => (
                                <option key={dept.id} value={dept.id}>{dept.name}</option>
                            ))}
                        </select>
                        {errors.department_id && (
                            <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>
                        )}
                    </div>

                    {/* Template Selection (Optional) */}
                    {filteredTemplates.length > 0 && (
                        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <DocumentIcon className="w-5 h-5 text-indigo-600" />
                                <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                                    Modèle (optionnel)
                                </h2>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSelectedTemplate(null);
                                        setData('template_id', '');
                                    }}
                                    className={`p-4 border rounded-lg text-left transition ${
                                        !data.template_id
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                    }`}
                                >
                                    <DocumentTextIcon className="w-8 h-8 text-gray-400 mb-2" />
                                    <p className="font-medium text-gray-900 dark:text-white">
                                        Rapport personnalisé
                                    </p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Créer de zéro
                                    </p>
                                </button>
                                {filteredTemplates.map((template) => (
                                    <button
                                        key={template.id}
                                        type="button"
                                        onClick={() => {
                                            setSelectedTemplate(template);
                                            setData('template_id', String(template.id));
                                        }}
                                        className={`p-4 border rounded-lg text-left transition ${
                                            data.template_id === String(template.id)
                                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                        }`}
                                    >
                                        <DocumentTextIcon className="w-8 h-8 text-indigo-500 mb-2" />
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            {template.name}
                                        </p>
                                        {template.description && (
                                            <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                                {template.description}
                                            </p>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Report Details */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div className="flex items-center gap-3 mb-4">
                            <DocumentTextIcon className="w-5 h-5 text-indigo-600" />
                            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                                Détails du rapport
                            </h2>
                        </div>

                        <div className="space-y-4">
                            {/* Title */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Titre du rapport
                                </label>
                                <input
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Ex: Rapport d'activité - Janvier 2024"
                                />
                                {errors.title && (
                                    <p className="mt-1 text-sm text-red-600">{errors.title}</p>
                                )}
                            </div>

                            {/* Type */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Type de rapport
                                </label>
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value as ReportType)}
                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                >
                                    {types.map((t) => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                    ))}
                                </select>
                                {errors.type && (
                                    <p className="mt-1 text-sm text-red-600">{errors.type}</p>
                                )}
                            </div>

                            {/* Executive Summary */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Résumé exécutif (optionnel)
                                </label>
                                <textarea
                                    value={data.executive_summary}
                                    onChange={(e) => setData('executive_summary', e.target.value)}
                                    rows={4}
                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Résumé des points clés du rapport..."
                                />
                            </div>
                        </div>
                    </div>

                    {/* Period Configuration */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div className="flex items-center gap-3 mb-4">
                            <CalendarIcon className="w-5 h-5 text-indigo-600" />
                            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                                Période du rapport
                            </h2>
                        </div>

                        <div className="space-y-4">
                            {/* Period Type */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Type de période
                                </label>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    {periodTypes.map((p) => (
                                        <button
                                            key={p.value}
                                            type="button"
                                            onClick={() => handlePeriodTypeChange(p.value as ReportPeriodType)}
                                            className={`px-4 py-2 rounded-lg border text-sm font-medium transition ${
                                                data.period_type === p.value
                                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400'
                                                    : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            }`}
                                        >
                                            {p.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Date Range */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Date de début
                                    </label>
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={cn(
                                                    "w-full justify-start text-left font-normal h-10 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600",
                                                    !data.period_start && "text-gray-500 dark:text-gray-400"
                                                )}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {data.period_start
                                                    ? format(new Date(data.period_start), "dd MMMM yyyy", { locale: fr })
                                                    : "Sélectionner une date"}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.period_start ? new Date(data.period_start) : undefined}
                                                onSelect={(date) => setData('period_start', date ? format(date, 'yyyy-MM-dd') : '')}
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.period_start && (
                                        <p className="mt-1 text-sm text-red-600">{errors.period_start}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Date de fin
                                    </label>
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={cn(
                                                    "w-full justify-start text-left font-normal h-10 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600",
                                                    !data.period_end && "text-gray-500 dark:text-gray-400"
                                                )}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {data.period_end
                                                    ? format(new Date(data.period_end), "dd MMMM yyyy", { locale: fr })
                                                    : "Sélectionner une date"}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.period_end ? new Date(data.period_end) : undefined}
                                                onSelect={(date) => setData('period_end', date ? format(date, 'yyyy-MM-dd') : '')}
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.period_end && (
                                        <p className="mt-1 text-sm text-red-600">{errors.period_end}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end gap-4">
                        <Link
                            href={route('reports.index')}
                            className="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                        >
                            {processing ? 'Création...' : 'Créer le rapport'}
                        </button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
