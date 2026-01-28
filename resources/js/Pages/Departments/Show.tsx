import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import { useToast } from '@/Components/ui/toast';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    UserPlusIcon,
    UserMinusIcon,
    BanknotesIcon,
    UserGroupIcon,
    UserIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
    CalendarDaysIcon,
    PlusIcon,
    EyeIcon,
    PlayIcon,
    FolderIcon,
    ChartBarIcon,
    ClockIcon,
    InboxStackIcon,
    PresentationChartLineIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/solid';
import type { DepartmentWorkflow } from '@/Types/workflow';
import type { DepartmentForm } from '@/Types/form';
import type { DepartmentNeed } from '@/Types/need';
import type { Appointment } from '@/Types/appointment';
import DepartmentCalendar from '@/Components/Department/DepartmentCalendar';
import DepartmentDocuments from '@/Components/Department/DepartmentDocuments';
import DepartmentStatisticsOperational, { type DepartmentStatistics } from '@/Components/Department/DepartmentStatisticsOperational';
import DepartmentStatisticsAnalytical from '@/Components/Department/DepartmentStatisticsAnalytical';
import { Squares2X2Icon, ChartPieIcon } from '@heroicons/react/24/outline';

interface DepartmentMeeting {
    uuid: string;
    is_mandatory: boolean;
    notify_all_members: boolean;
    notes: string | null;
    notified_at: string | null;
    created_at: string;
    creator: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    appointment: Appointment | null;
}

interface DocumentData {
    uuid: string;
    title: string;
    original_name: string;
    file_name: string;
    file_url: string;
    preview_url: string;
    file_size: number;
    formatted_file_size: string;
    mime_type: string;
    extension: string;
    file_type: string;
    can_preview: boolean;
    preview_type: string;
    description: string | null;
    category: string | null;
    created_at: string;
    uploader: {
        id: number;
        name: string;
    } | null;
}

interface CategoryData {
    name: string;
    key: string;
    documents: DocumentData[];
}

interface MonthData {
    month: number;
    month_name: string;
    categories: CategoryData[];
    document_count: number;
}

interface YearData {
    year: number;
    months: MonthData[];
    document_count: number;
}

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
}

interface Assignable {
    id: number;
    uuid: string;
    name: string;
    email: string;
    type: 'user' | 'employee' | 'star';
    position?: string;
    title?: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description: string | null;
    budget: number | null;
    is_active: boolean;
    head_of_department: User | null;
    users: User[];
    users_count: number;
}

interface Props {
    department: Department;
    availableUsers: Assignable[];
    availableEmployees: Assignable[];
    availableStars: Assignable[];
    canManage: boolean;
    canViewStatistics: boolean;
    workflows?: DepartmentWorkflow[];
    forms?: DepartmentForm[];
    needs?: DepartmentNeed[];
    appointments?: Appointment[];
    meetings?: DepartmentMeeting[];
    documentsTree?: YearData[];
    documentsCount?: number;
    statistics?: DepartmentStatistics;
}

export default function ShowDepartment({ department, availableUsers, availableEmployees = [], availableStars = [], canManage, canViewStatistics, workflows = [], forms = [], needs = [], appointments = [], meetings = [], documentsTree = [], documentsCount = 0, statistics }: Props) {
    const [isAddMemberModalOpen, setIsAddMemberModalOpen] = useState(false);
    const [isDeactivateModalOpen, setIsDeactivateModalOpen] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState<string | number | null>(null);
    const [memberFilter, setMemberFilter] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [activeTab, setActiveTab] = useState('overview');
    const [statsViewMode, setStatsViewMode] = useState<'operational' | 'analytical'>('operational');
    const { confirm } = useConfirm();
    const { showSuccess, showError } = useToast();

    // Combine and filter assignees based on selected filter
    const filteredAssignees = React.useMemo(() => {
        const combined: Assignable[] = [];

        if (memberFilter === 'all' || memberFilter === 'user') {
            combined.push(...availableUsers);
        }
        if (memberFilter === 'all' || memberFilter === 'employee') {
            combined.push(...availableEmployees);
        }
        if (memberFilter === 'all' || memberFilter === 'star') {
            combined.push(...availableStars);
        }

        // Remove duplicates by user id
        const seen = new Set<number>();
        return combined.filter(a => {
            if (seen.has(a.id)) return false;
            seen.add(a.id);
            return true;
        });
    }, [availableUsers, availableEmployees, availableStars, memberFilter]);

    // Convert to options for SearchableSelect
    const selectOptions = React.useMemo(() => {
        return filteredAssignees.map(a => {
            const typeLabel = a.type === 'user' ? 'Utilisateur' : a.type === 'employee' ? 'Employé' : 'Star';
            const extra = a.position || a.title || '';
            return {
                value: a.id,
                label: `${a.name} - ${typeLabel}${extra ? ` (${extra})` : ''}`,
            };
        });
    }, [filteredAssignees]);

    const handleAddMember = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUserId) return;

        router.post(`/departments/${department.uuid}/assign-user`, {
            user_id: selectedUserId,
        }, {
            onSuccess: () => {
                setIsAddMemberModalOpen(false);
                setSelectedUserId(null);
                showSuccess('Membre ajouté avec succès au département');
            },
            onError: () => {
                showError('Erreur lors de l\'ajout du membre');
            },
        });
    };

    const handleRemoveMember = async (userUuid: string) => {
        const confirmed = await confirm({
            title: 'Retirer le membre',
            message: 'Êtes-vous sûr de vouloir retirer ce membre du département ?',
            confirmText: 'Retirer',
            cancelText: 'Annuler',
            type: 'warning'
        });

        if (!confirmed) return;

        router.delete(`/departments/${department.uuid}/users/${userUuid}`, {
            onSuccess: () => {
                showSuccess('Membre retiré avec succès du département');
            },
            onError: () => {
                showError('Erreur lors du retrait du membre');
            },
        });
    };

    const handleToggleStatus = () => {
        router.patch(`/departments/${department.uuid}`, {
            ...department,
            is_active: !department.is_active,
        } as any, {
            onSuccess: () => {
                setIsDeactivateModalOpen(false);
                showSuccess(`Département ${department.is_active ? 'désactivé' : 'activé'} avec succès`);
            },
            onError: () => {
                showError('Erreur lors du changement de statut');
            },
        });
    };

    const formatBudget = (budget: number | null) => {
        if (!budget) return 'Non défini';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
        }).format(budget);
    };

    return (
        <DashboardLayout>
            <Head title={department.name} />

            <div className="p-6">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/departments">
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour aux Départements
                    </Link>
                </Button>
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            {department.name}
                        </h1>
                        {department.is_active ? (
                            <Badge className="bg-green-500">
                                <CheckCircleIcon className="h-4 w-4 mr-1" />
                                Actif
                            </Badge>
                        ) : (
                            <Badge className="bg-gray-500">
                                <XCircleIcon className="h-4 w-4 mr-1" />
                                Inactif
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Code: {department.code}
                    </p>
                </div>
                    </div>

                    {canManage && (
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={`/departments/${department.uuid}/edit`}>
                            <PencilIcon className="h-4 w-4 mr-2" />
                            Modifier
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href={`/reports/create?department_id=${department.id}`}>
                            <ChartBarIcon className="h-4 w-4 mr-2" />
                            Créer un rapport
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => setIsDeactivateModalOpen(true)}
                    >
                        {department.is_active ? 'Désactiver' : 'Activer'}
                    </Button>
                </div>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Budget</CardTitle>
                    <BanknotesIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">
                        {formatBudget(department.budget)}
                    </div>
                </CardContent>
                    </Card>

                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Membres</CardTitle>
                    <UserGroupIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">
                        {department.users_count}
                    </div>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        membres dans ce département
                    </p>
                </CardContent>
                    </Card>

                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Chef de Département</CardTitle>
                    <UserIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    {department.head_of_department ? (
                        <div>
                            <div className="text-lg font-semibold text-gray-900 dark:text-white">
                                {department.head_of_department.name}
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {department.head_of_department.email}
                            </p>
                        </div>
                    ) : (
                        <div className="text-gray-400">Non assigné</div>
                    )}
                </CardContent>
                    </Card>
                </div>

                {/* Description */}
                {department.description && (
                    <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Description</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-gray-700 dark:text-gray-300">
                        {department.description}
                    </p>
                </CardContent>
                    </Card>
                )}

                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                    <TabsList className={`grid w-full ${canViewStatistics ? 'grid-cols-8' : 'grid-cols-7'}`}>
                        <TabsTrigger value="overview" className="flex items-center gap-2">
                            <UserGroupIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Membres</span>
                            <Badge variant="secondary" className="ml-1">{department.users_count}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="workflows" className="flex items-center gap-2">
                            <ArrowPathIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Workflows</span>
                            <Badge variant="secondary" className="ml-1">{workflows.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="forms" className="flex items-center gap-2">
                            <DocumentTextIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Formulaires</span>
                            <Badge variant="secondary" className="ml-1">{forms.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="needs" className="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Besoins</span>
                            <Badge variant="secondary" className="ml-1">{needs.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="calendar" className="flex items-center gap-2">
                            <CalendarDaysIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Agenda</span>
                            <Badge variant="secondary" className="ml-1">{meetings.length + appointments.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="documents" className="flex items-center gap-2">
                            <FolderIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Documents</span>
                            <Badge variant="secondary" className="ml-1">{documentsCount}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="schedule" className="flex items-center gap-2">
                            <ClockIcon className="h-4 w-4" />
                            <span className="hidden sm:inline">Planning</span>
                        </TabsTrigger>
                        {canViewStatistics && (
                            <TabsTrigger value="statistics" className="flex items-center gap-2">
                                <PresentationChartLineIcon className="h-4 w-4" />
                                <span className="hidden sm:inline">Statistiques</span>
                            </TabsTrigger>
                        )}
                    </TabsList>

                    {/* Members Tab */}
                    <TabsContent value="overview">
                <Card>
                    <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Membres du Département</CardTitle>
                        <CardDescription>
                            {department.users_count} membre(s)
                        </CardDescription>
                    </div>
                    {canManage && (
                        <Button onClick={() => setIsAddMemberModalOpen(true)}>
                            <UserPlusIcon className="h-4 w-4 mr-2" />
                            Ajouter un Membre
                        </Button>
                    )}
                </div>
                    </CardHeader>
                    <CardContent>
                {department.users.length > 0 ? (
                    <div className="space-y-2">
                        {department.users.map((user) => (
                            <div
                                key={user.id}
                                className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex items-center gap-3">
                                    {user.name && (
                                        <div className="h-10 w-10 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                                            {user.name.charAt(0).toUpperCase()}
                                        </div>
                                    )}
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={`/profile/${user.uuid}`}
                                                className="font-medium text-primary dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline"
                                            >
                                                {user.name || 'Sans nom'}
                                            </Link>
                                            {department.head_of_department?.id === user.id && (
                                                <Badge className="bg-purple-500">Chef</Badge>
                                            )}
                                        </div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {user.email}
                                        </p>
                                    </div>
                                </div>
                                {canManage && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleRemoveMember(user.uuid)}
                                        disabled={department.head_of_department?.id === user.id}
                                    >
                                        <UserMinusIcon className="h-4 w-4 mr-2" />
                                        Retirer
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                        <UserGroupIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p>Aucun membre dans ce département</p>
                    </div>
                )}
                    </CardContent>
                </Card>
                    </TabsContent>

                    {/* Workflows Tab */}
                    <TabsContent value="workflows">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Workflows du Département</CardTitle>
                                        <CardDescription>
                                            {workflows.length} workflow(s) configuré(s)
                                        </CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button asChild>
                                            <Link href={`/workflows/create?department_id=${department.id}`}>
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Nouveau Workflow
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {workflows.length > 0 ? (
                                    <div className="space-y-3">
                                        {workflows.map((workflow) => (
                                            <div
                                                key={workflow.uuid}
                                                className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                        <ArrowPathIcon className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium text-gray-900 dark:text-white">
                                                                {workflow.name}
                                                            </span>
                                                            <Badge
                                                                variant={workflow.status === 'active' ? 'default' : 'secondary'}
                                                                className={workflow.status === 'active' ? 'bg-green-500' : ''}
                                                            >
                                                                {workflow.status === 'active' ? 'Actif' : workflow.status === 'draft' ? 'Brouillon' : 'Obsolète'}
                                                            </Badge>
                                                        </div>
                                                        {workflow.description && (
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                                                                {workflow.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/workflows/${workflow.uuid}`}>
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {workflow.status === 'active' && (
                                                        <Button variant="outline" size="sm" asChild>
                                                            <Link href={`/workflows/${workflow.uuid}/start`}>
                                                                <PlayIcon className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <ArrowPathIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p>Aucun workflow configuré</p>
                                        {canManage && (
                                            <Button className="mt-4" asChild>
                                                <Link href={`/workflows/create?department_id=${department.id}`}>
                                                    <PlusIcon className="h-4 w-4 mr-2" />
                                                    Créer un workflow
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Forms Tab */}
                    <TabsContent value="forms">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Formulaires du Département</CardTitle>
                                        <CardDescription>
                                            {forms.length} formulaire(s) créé(s)
                                        </CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button asChild>
                                            <Link href={`/forms/create?department_id=${department.id}`}>
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Nouveau Formulaire
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {forms.length > 0 ? (
                                    <div className="space-y-3">
                                        {forms.map((form) => (
                                            <div
                                                key={form.uuid}
                                                className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                                        <DocumentTextIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium text-gray-900 dark:text-white">
                                                                {form.name}
                                                            </span>
                                                            <Badge
                                                                variant={form.status === 'published' ? 'default' : 'secondary'}
                                                                className={form.status === 'published' ? 'bg-green-500' : ''}
                                                            >
                                                                {form.status === 'published' ? 'Publié' : form.status === 'draft' ? 'Brouillon' : 'Archivé'}
                                                            </Badge>
                                                        </div>
                                                        {form.description && (
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                                                                {form.description}
                                                            </p>
                                                        )}
                                                        <div className="flex items-center gap-3 text-xs text-gray-400 mt-1">
                                                            <span>{form.fields_count || 0} champs</span>
                                                            <span>{form.submissions_count || 0} soumissions</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/forms/${form.uuid}`}>
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {form.status === 'published' && (
                                                        <Button variant="outline" size="sm" asChild>
                                                            <Link href={`/forms/${form.uuid}/render`}>
                                                                <PlayIcon className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <DocumentTextIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p>Aucun formulaire créé</p>
                                        {canManage && (
                                            <Button className="mt-4" asChild>
                                                <Link href={`/forms/create?department_id=${department.id}`}>
                                                    <PlusIcon className="h-4 w-4 mr-2" />
                                                    Créer un formulaire
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Needs Tab */}
                    <TabsContent value="needs">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Besoins du Département</CardTitle>
                                        <CardDescription>
                                            {needs.length} besoin(s) enregistré(s)
                                        </CardDescription>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button variant="outline" asChild>
                                            <Link href={`/needs/kanban?department_id=${department.id}`}>
                                                Voir Kanban
                                            </Link>
                                        </Button>
                                        <Button asChild>
                                            <Link href={`/needs/create?department_id=${department.id}`}>
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Nouveau Besoin
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {needs.length > 0 ? (
                                    <div className="space-y-3">
                                        {needs.map((need) => {
                                            const priorityColors: Record<string, string> = {
                                                critical: 'bg-red-500',
                                                high: 'bg-orange-500',
                                                medium: 'bg-yellow-500',
                                                low: 'bg-blue-500',
                                            };
                                            const statusLabels: Record<string, string> = {
                                                draft: 'Brouillon',
                                                submitted: 'Soumis',
                                                pending_approval: 'En attente',
                                                approved: 'Approuvé',
                                                rejected: 'Rejeté',
                                                in_progress: 'En cours',
                                                ordered: 'Commandé',
                                                delivered: 'Livré',
                                                completed: 'Terminé',
                                                cancelled: 'Annulé',
                                            };
                                            return (
                                                <div
                                                    key={need.uuid}
                                                    className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                                            <ClipboardDocumentCheckIcon className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                                        </div>
                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium text-gray-900 dark:text-white">
                                                                    {need.title}
                                                                </span>
                                                                <Badge variant="secondary">
                                                                    {statusLabels[need.status] || need.status}
                                                                </Badge>
                                                                <Badge className={priorityColors[need.priority] || 'bg-gray-500'}>
                                                                    {need.priority}
                                                                </Badge>
                                                            </div>
                                                            <div className="flex items-center gap-3 text-xs text-gray-400 mt-1">
                                                                <span>{need.reference}</span>
                                                                {need.estimated_cost !== undefined && need.estimated_cost !== null && (
                                                                    <span>
                                                                        {new Intl.NumberFormat('fr-FR', {
                                                                            style: 'currency',
                                                                            currency: need.currency || 'EUR',
                                                                        }).format(Number(need.estimated_cost))}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/needs/${need.uuid}`}>
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <ClipboardDocumentCheckIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p>Aucun besoin enregistré</p>
                                        <Button className="mt-4" asChild>
                                            <Link href={`/needs/create?department_id=${department.id}`}>
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Créer un besoin
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Calendar Tab */}
                    <TabsContent value="calendar">
                        <Card>
                            <CardContent className="pt-6">
                                <DepartmentCalendar
                                    appointments={appointments}
                                    meetings={meetings}
                                    departmentId={department.id}
                                    departmentUuid={department.uuid}
                                    canManage={canManage}
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Documents Tab */}
                    <TabsContent value="documents">
                        <DepartmentDocuments
                            departmentUuid={department.uuid}
                            initialTree={documentsTree}
                            canManage={canManage}
                        />
                    </TabsContent>

                    {/* Schedule Tab */}
                    <TabsContent value="schedule">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Planning du Département</CardTitle>
                                        <CardDescription>
                                            Gérez les horaires et les shifts des membres
                                        </CardDescription>
                                    </div>
                                    <Button asChild>
                                        <Link href={`/departments/${department.uuid}/schedule`}>
                                            <CalendarDaysIcon className="h-4 w-4 mr-2" />
                                            Accéder au Planning
                                        </Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <Card className="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                        <CardContent className="pt-6">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                                                    <ClockIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-blue-600 dark:text-blue-400 font-medium">Planning</p>
                                                    <p className="text-lg font-bold text-blue-900 dark:text-blue-100">Voir les shifts</p>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" className="w-full mt-4" asChild>
                                                <Link href={`/departments/${department.uuid}/schedule`}>
                                                    Ouvrir le calendrier
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>

                                    <Card className="bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
                                        <CardContent className="pt-6">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                                    <CheckCircleIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-green-600 dark:text-green-400 font-medium">Disponibilités</p>
                                                    <p className="text-lg font-bold text-green-900 dark:text-green-100">Gérer mes dispos</p>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" className="w-full mt-4" asChild>
                                                <Link href={`/departments/${department.uuid}/availability/my`}>
                                                    Mes disponibilités
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>

                                    <Card className="bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800">
                                        <CardContent className="pt-6">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                                                    <CalendarDaysIcon className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-orange-600 dark:text-orange-400 font-medium">Absences</p>
                                                    <p className="text-lg font-bold text-orange-900 dark:text-orange-100">Demander un congé</p>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" className="w-full mt-4" asChild>
                                                <Link href={`/departments/${department.uuid}/absences/my`}>
                                                    Mes absences
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </div>

                                {canManage && (
                                    <div className="mt-6 pt-6 border-t">
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-4">Administration</h4>
                                        <div className="flex flex-wrap gap-3">
                                            <Button variant="outline" asChild>
                                                <Link href={`/departments/${department.uuid}/availability`}>
                                                    Vue d'ensemble des disponibilités
                                                </Link>
                                            </Button>
                                            <Button variant="outline" asChild>
                                                <Link href={`/departments/${department.uuid}/absences`}>
                                                    Gérer les demandes d'absence
                                                </Link>
                                            </Button>
                                            <Button variant="outline" asChild>
                                                <Link href={`/departments/${department.uuid}/swap-requests`}>
                                                    Échanges de shifts
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Statistics Tab - Only visible to authorized users */}
                    {canViewStatistics && (
                    <TabsContent value="statistics">
                        <div className="space-y-6">
                            {/* View Mode Toggle */}
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Statistiques</h2>
                                <div className="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1">
                                    <button
                                        type="button"
                                        onClick={() => setStatsViewMode('operational')}
                                        className={`px-3 py-2 rounded-md transition-colors flex items-center gap-2 text-sm ${
                                            statsViewMode === 'operational'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                        title="Vue opérationnelle"
                                    >
                                        <Squares2X2Icon className="h-4 w-4" />
                                        Opérationnelle
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setStatsViewMode('analytical')}
                                        className={`px-3 py-2 rounded-md transition-colors flex items-center gap-2 text-sm ${
                                            statsViewMode === 'analytical'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                        title="Vue analytique"
                                    >
                                        <ChartPieIcon className="h-4 w-4" />
                                        Analytique
                                    </button>
                                </div>
                            </div>

                            {statsViewMode === 'operational' ? (
                                <DepartmentStatisticsOperational statistics={statistics} />
                            ) : (
                                <DepartmentStatisticsAnalytical statistics={statistics} />
                            )}
                        </div>
                    </TabsContent>
                    )}
                </Tabs>
            </div>

            {/* Add Member Modal */}
            {canManage && (
                <Dialog open={isAddMemberModalOpen} onOpenChange={(open) => {
                    setIsAddMemberModalOpen(open);
                    if (!open) {
                        setSelectedUserId(null);
                        setMemberFilter('all');
                    }
                }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajouter un Membre</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleAddMember}>
                        <div className="space-y-4 py-4 px-6">
                            <div className="space-y-3">
                                <label className="block text-sm font-medium">Filtrer par type</label>

                                {/* Filter tabs */}
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant={memberFilter === 'all' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setMemberFilter('all')}
                                    >
                                        Tous ({availableUsers.length + availableEmployees.length + availableStars.length})
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={memberFilter === 'employee' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setMemberFilter('employee')}
                                        className={memberFilter === 'employee' ? '' : 'border-green-300 text-green-700 hover:bg-green-50'}
                                    >
                                        Employés ({availableEmployees.length})
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={memberFilter === 'star' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setMemberFilter('star')}
                                        className={memberFilter === 'star' ? '' : 'border-yellow-300 text-yellow-700 hover:bg-yellow-50'}
                                    >
                                        Stars ({availableStars.length})
                                    </Button>
                                </div>

                                <label className="block text-sm font-medium">Utilisateur</label>
                                <SearchableSelect
                                    options={selectOptions}
                                    value={selectedUserId}
                                    onChange={setSelectedUserId}
                                    placeholder="Rechercher un utilisateur..."
                                    noOptionsMessage="Aucun utilisateur trouvé"
                                    isClearable
                                    maxMenuHeight={180}
                                    menuPortalTarget={document.body}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAddMemberModalOpen(false)}
                            >
                                Annuler
                            </Button>
                            <Button type="submit" disabled={!selectedUserId}>
                                Ajouter
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
            )}

            {/* Toggle Status Modal */}
            {canManage && (
                <Dialog open={isDeactivateModalOpen} onOpenChange={setIsDeactivateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                <DialogTitle>
                    {department.is_active ? 'Désactiver' : 'Activer'} le Département
                </DialogTitle>
                    </DialogHeader>
                    <div className="py-4 px-6">
                <p className="text-gray-700 dark:text-gray-300">
                    Êtes-vous sûr de vouloir {department.is_active ? 'désactiver' : 'activer'} ce département ?
                </p>
                    </div>
                    <DialogFooter>
                <Button
                    variant="outline"
                    onClick={() => setIsDeactivateModalOpen(false)}
                >
                    Annuler
                </Button>
                <Button onClick={handleToggleStatus}>
                    Confirmer
                </Button>
                    </DialogFooter>
                </DialogContent>
                </Dialog>
            )}
        </DashboardLayout>
    );
};
