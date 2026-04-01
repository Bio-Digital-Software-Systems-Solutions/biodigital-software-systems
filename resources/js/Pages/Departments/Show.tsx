import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/Components/ui/accordion';
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
    BriefcaseIcon,
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

interface DepartmentPosition {
    id: number;
    uuid: string;
    name: string;
    code: string | null;
    description: string | null;
    color: string | null;
    hourly_rate: string | null;
    is_active: boolean;
}

interface Nomination {
    uuid: string;
    position: { id: number; uuid: string; name: string; color: string | null };
    user: User;
    nominated_by: { id: number; name: string } | null;
    start_date: string | null;
    end_date: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
}

interface DepartmentChild {
    id: number;
    uuid: string;
    name: string;
    code: string;
    is_active: boolean;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description: string | null;
    budget: number | null;
    is_active: boolean;
    parent_id: number | null;
    parent: { id: number; uuid: string; name: string } | null;
    children: DepartmentChild[];
    head_of_department: User | null;
    first_deputy: User | null;
    second_deputy: User | null;
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
    positions?: DepartmentPosition[];
    nominations?: Nomination[];
    statistics?: DepartmentStatistics;
}

interface PositionFormData {
    name: string;
    code: string;
    description: string;
    color: string;
    hourly_rate: string;
    is_active: boolean;
}

function PositionForm({ departmentUuid, editingPosition, onCancel }: { departmentUuid: string; editingPosition?: DepartmentPosition | null; onCancel?: () => void }) {
    const [formData, setFormData] = useState<PositionFormData>({
        name: editingPosition?.name || '',
        code: editingPosition?.code || '',
        description: editingPosition?.description || '',
        color: editingPosition?.color || '#3b82f6',
        hourly_rate: editingPosition?.hourly_rate || '',
        is_active: editingPosition?.is_active ?? true,
    });
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!formData.name.trim()) return;
        setProcessing(true);

        const url = editingPosition
            ? `/departments/${departmentUuid}/positions/${editingPosition.uuid}`
            : `/departments/${departmentUuid}/positions`;

        const method = editingPosition ? 'put' : 'post';

        router[method](url, {
            ...formData,
            hourly_rate: formData.hourly_rate ? parseFloat(formData.hourly_rate) : null,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                if (!editingPosition) {
                    setFormData({
                        name: '',
                        code: '',
                        description: '',
                        color: '#3b82f6',
                        hourly_rate: '',
                        is_active: true,
                    });
                }
                onCancel?.();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du poste *</label>
                    <input
                        type="text"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        placeholder="Ex: Responsable technique"
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                        required
                    />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                    <input
                        type="text"
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                        placeholder="Ex: RT"
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                    />
                </div>
                <div>
                    <label htmlFor="position-color" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Couleur</label>
                    <div className="flex items-center gap-2">
                        <input
                            type="color"
                            id="position-color"
                            value={formData.color}
                            onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                            className="h-9 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                            aria-label="Sélectionner une couleur"
                        />
                        <input
                            type="text"
                            value={formData.color}
                            onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            placeholder="#3b82f6"
                            aria-label="Code couleur hexadécimal"
                        />
                    </div>
                </div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Taux horaire (EUR)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={formData.hourly_rate}
                        onChange={(e) => setFormData({ ...formData, hourly_rate: e.target.value })}
                        placeholder="Ex: 15.00"
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                    />
                </div>
                <div className="flex items-center">
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={formData.is_active}
                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                            className="rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-700 dark:text-gray-300">Poste actif</span>
                    </label>
                </div>
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    rows={2}
                    placeholder="Description des responsabilités..."
                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                />
            </div>
            <div className="flex justify-end gap-2">
                {onCancel && (
                    <Button type="button" variant="outline" size="sm" onClick={onCancel}>
                        Annuler
                    </Button>
                )}
                <Button type="submit" disabled={processing || !formData.name.trim()} size="sm">
                    <PlusIcon className="h-4 w-4 mr-1" />
                    {processing ? 'Enregistrement...' : editingPosition ? 'Mettre à jour' : 'Créer le poste'}
                </Button>
            </div>
        </form>
    );
}

function NominationForm({ departmentUuid, positions, members }: { departmentUuid: string; positions: DepartmentPosition[]; members: User[] }) {
    const [positionId, setPositionId] = useState<string | number | null>(null);
    const [userId, setUserId] = useState<string | number | null>(null);
    const [startDate, setStartDate] = useState('');
    const [notes, setNotes] = useState('');
    const [processing, setProcessing] = useState(false);

    const positionOptions = positions.filter(p => p.is_active).map(p => ({ value: p.id, label: p.name }));
    const memberOptions = members.map(u => ({ value: u.id, label: `${u.name} (${u.email})` }));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!positionId || !userId) return;
        setProcessing(true);
        router.post(`/departments/${departmentUuid}/nominations`, {
            department_position_id: positionId,
            user_id: userId,
            start_date: startDate || null,
            notes: notes || null,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setPositionId(null);
                setUserId(null);
                setStartDate('');
                setNotes('');
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Poste *</label>
                <SearchableSelect
                    options={positionOptions}
                    value={positionId ? Number(positionId) : null}
                    onChange={(v) => setPositionId(v)}
                    placeholder="Sélectionner un poste..."
                    noOptionsMessage="Aucun poste"
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Membre *</label>
                <SearchableSelect
                    options={memberOptions}
                    value={userId ? Number(userId) : null}
                    onChange={(v) => setUserId(v)}
                    placeholder="Sélectionner un membre..."
                    noOptionsMessage="Aucun membre"
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début</label>
                <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                />
            </div>
            <div>
                <Button type="submit" disabled={processing || !positionId || !userId} size="sm" className="w-full">
                    <PlusIcon className="h-4 w-4 mr-1" />
                    {processing ? 'Nomination...' : 'Nommer'}
                </Button>
            </div>
        </form>
    );
}

export default function ShowDepartment({ department, availableUsers, availableEmployees = [], availableStars = [], canManage, canViewStatistics, workflows = [], forms = [], needs = [], appointments = [], meetings = [], documentsTree = [], documentsCount = 0, positions = [], nominations = [], statistics }: Props) {
    const [isAddMemberModalOpen, setIsAddMemberModalOpen] = useState(false);
    const [isDeactivateModalOpen, setIsDeactivateModalOpen] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState<string | number | null>(null);
    const [memberFilter, setMemberFilter] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [activeTab, setActiveTab] = useState('overview');
    const [statsViewMode, setStatsViewMode] = useState<'operational' | 'analytical'>('operational');
    const [showPositionForm, setShowPositionForm] = useState(false);
    const [editingPosition, setEditingPosition] = useState<DepartmentPosition | null>(null);
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

            <div className="p-3 sm:p-6">
                {/* Header */}
                <div className="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                <Button variant="outline" size="sm" asChild className="w-fit">
                    <Link href="/departments">
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        <span className="hidden sm:inline">Retour aux Départements</span>
                        <span className="sm:hidden">Retour</span>
                    </Link>
                </Button>
                <div>
                    <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                        <h1 className="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">
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
                    <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Code: {department.code}
                    </p>
                    {department.parent && (
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Département parent :{' '}
                            <Link
                                href={`/departments/${department.parent.uuid}`}
                                className="text-primary hover:underline font-medium"
                            >
                                {department.parent.name}
                            </Link>
                        </p>
                    )}
                </div>
                    </div>

                    {canManage && (
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/departments/${department.uuid}/edit`}>
                            <PencilIcon className="h-4 w-4 sm:mr-2" />
                            <span className="hidden sm:inline">Modifier</span>
                        </Link>
                    </Button>
                    <Button size="sm" asChild>
                        <Link href={`/reports/create?department_id=${department.id}`}>
                            <ChartBarIcon className="h-4 w-4 sm:mr-2" />
                            <span className="hidden sm:inline">Créer un rapport</span>
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setIsDeactivateModalOpen(true)}
                        className="text-xs sm:text-sm"
                    >
                        <span className="hidden sm:inline">{department.is_active ? 'Désactiver' : 'Activer'}</span>
                        <span className="sm:hidden">{department.is_active ? 'Off' : 'On'}</span>
                    </Button>
                </div>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                    <CardTitle className="text-xs sm:text-sm font-medium">Budget</CardTitle>
                    <BanknotesIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                </CardHeader>
                <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                    <div className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {formatBudget(department.budget)}
                    </div>
                </CardContent>
                    </Card>

                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                    <CardTitle className="text-xs sm:text-sm font-medium">Membres</CardTitle>
                    <UserGroupIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                </CardHeader>
                <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                    <div className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {department.users_count}
                    </div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">
                        membres dans ce département
                    </p>
                </CardContent>
                    </Card>

                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                    <CardTitle className="text-xs sm:text-sm font-medium">Chef</CardTitle>
                    <UserIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                </CardHeader>
                <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                    {department.head_of_department ? (
                        <div>
                            <div className="text-sm sm:text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {department.head_of_department.name}
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 truncate hidden sm:block">
                                {department.head_of_department.email}
                            </p>
                        </div>
                    ) : (
                        <div className="text-gray-400 text-sm">Non assigné</div>
                    )}
                </CardContent>
                    </Card>

                    {/* Deputies */}
                    {(department.first_deputy || department.second_deputy) && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Adjoints</CardTitle>
                                <UserGroupIcon className="h-5 w-5 text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {department.first_deputy && (
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">1er Adjoint</p>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">{department.first_deputy.name}</p>
                                        </div>
                                    )}
                                    {department.second_deputy && (
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">2ème Adjoint</p>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">{department.second_deputy.name}</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}
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
                    <div className="overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">
                    <TabsList className={`flex w-max sm:grid sm:w-full ${canViewStatistics ? 'sm:grid-cols-9' : 'sm:grid-cols-8'} gap-1 p-1`}>
                        <TabsTrigger value="overview" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <UserGroupIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Membres</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{department.users_count}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="workflows" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <ArrowPathIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Workflows</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{workflows.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="forms" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <DocumentTextIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Formulaires</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{forms.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="needs" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <ClipboardDocumentCheckIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Besoins</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{needs.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="calendar" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <CalendarDaysIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Agenda</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{meetings.length + appointments.length}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="documents" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <FolderIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Documents</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{documentsCount}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="schedule" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <ClockIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Planning</span>
                        </TabsTrigger>
                        <TabsTrigger value="nominations" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                            <BriefcaseIcon className="h-4 w-4" />
                            <span className="hidden md:inline">Nominations</span>
                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{nominations.length}</Badge>
                        </TabsTrigger>
                        {canViewStatistics && (
                            <TabsTrigger value="statistics" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <PresentationChartLineIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Stats</span>
                            </TabsTrigger>
                        )}
                    </TabsList>
                    </div>

                    {/* Members Tab */}
                    <TabsContent value="overview">
                {/* Sub-departments accordion */}
                {department.children && department.children.length > 0 && (
                    <div className="mb-4">
                        <Accordion>
                            <AccordionItem value="sub-departments">
                                <AccordionTrigger>
                                    <div className="flex items-center gap-2">
                                        <span>Sous-départements</span>
                                        <Badge variant="outline" className="ml-1">{department.children.length}</Badge>
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent>
                                    <div className="space-y-2">
                                        {department.children.map((child) => (
                                            <div
                                                key={child.id}
                                                className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                                                        {child.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <Link
                                                            href={`/departments/${child.uuid}`}
                                                            className="font-medium text-primary dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline"
                                                        >
                                                            {child.name}
                                                        </Link>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                                            Code: {child.code}
                                                        </p>
                                                    </div>
                                                </div>
                                                <Badge className={child.is_active ? 'bg-green-500' : 'bg-gray-500'}>
                                                    {child.is_active ? 'Actif' : 'Inactif'}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>
                )}

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
                                        <Link href={`/departments/${department.uuid}/schedule?showTasks=1`}>
                                            <CalendarDaysIcon className="h-4 w-4 mr-2" />
                                            Accéder aux tâches
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

                                    <Card className="bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800">
                                        <CardContent className="pt-6">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                                                    <ArrowPathIcon className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-purple-600 dark:text-purple-400 font-medium">Routines</p>
                                                    <p className="text-lg font-bold text-purple-900 dark:text-purple-100">Processus standards</p>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" className="w-full mt-4" asChild>
                                                <Link href={`/departments/${department.uuid}/routines`}>
                                                    Voir les routines
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
                                            <Button variant="outline" asChild>
                                                <Link href={`/departments/${department.uuid}/routines`}>
                                                    Gérer les routines
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Nominations Tab */}
                    <TabsContent value="nominations">
                        <div className="space-y-6">
                            {/* Positions Management Card */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Postes du Département</CardTitle>
                                            <CardDescription>
                                                Gérez les postes disponibles dans ce département
                                            </CardDescription>
                                        </div>
                                        {canManage && !showPositionForm && !editingPosition && (
                                            <Button onClick={() => setShowPositionForm(true)} size="sm">
                                                <PlusIcon className="h-4 w-4 mr-1" />
                                                Nouveau poste
                                            </Button>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {/* Position Form (Create/Edit) */}
                                    {canManage && (showPositionForm || editingPosition) && (
                                        <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                            <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                                {editingPosition ? 'Modifier le poste' : 'Créer un poste'}
                                            </h3>
                                            <PositionForm
                                                departmentUuid={department.uuid}
                                                editingPosition={editingPosition}
                                                onCancel={() => {
                                                    setShowPositionForm(false);
                                                    setEditingPosition(null);
                                                }}
                                            />
                                        </div>
                                    )}

                                    {/* Positions List */}
                                    {positions.length > 0 ? (
                                        <div className="space-y-2">
                                            {positions.map((position) => (
                                                <div
                                                    key={position.uuid}
                                                    className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <div
                                                            className="w-3 h-3 rounded-full"
                                                            style={{ backgroundColor: position.color || '#6b7280' }}
                                                        />
                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium text-gray-900 dark:text-white">
                                                                    {position.name}
                                                                </span>
                                                                {position.code && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        {position.code}
                                                                    </Badge>
                                                                )}
                                                                {!position.is_active && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        Inactif
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            {position.description && (
                                                                <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">
                                                                    {position.description}
                                                                </p>
                                                            )}
                                                            {position.hourly_rate && (
                                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                    {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(parseFloat(position.hourly_rate))}/h
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    {canManage && (
                                                        <div className="flex items-center gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => {
                                                                    setShowPositionForm(false);
                                                                    setEditingPosition(position);
                                                                }}
                                                            >
                                                                <PencilIcon className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-red-600 hover:text-red-700"
                                                                onClick={async () => {
                                                                    const confirmed = await confirm({
                                                                        title: 'Supprimer le poste',
                                                                        message: `Êtes-vous sûr de vouloir supprimer le poste "${position.name}" ? Les nominations associées seront également supprimées.`,
                                                                        confirmText: 'Supprimer',
                                                                        cancelText: 'Annuler',
                                                                        type: 'danger'
                                                                    });
                                                                    if (confirmed) {
                                                                        router.delete(
                                                                            `/departments/${department.uuid}/positions/${position.uuid}`,
                                                                            { preserveScroll: true }
                                                                        );
                                                                    }
                                                                }}
                                                            >
                                                                <TrashIcon className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <BriefcaseIcon className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                            <p>Aucun poste défini pour ce département.</p>
                                            {canManage && !showPositionForm && (
                                                <Button className="mt-4" size="sm" onClick={() => setShowPositionForm(true)}>
                                                    <PlusIcon className="h-4 w-4 mr-1" />
                                                    Créer un poste
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Nominations Card */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Nominations aux Postes</CardTitle>
                                            <CardDescription>
                                                Attribuez des postes aux membres du département
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {/* Nomination Form */}
                                    {canManage && positions.filter(p => p.is_active).length > 0 && department.users.length > 0 && (
                                        <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                            <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-3">Nouvelle nomination</h3>
                                            <NominationForm
                                                departmentUuid={department.uuid}
                                                positions={positions}
                                                members={department.users}
                                            />
                                        </div>
                                    )}

                                    {positions.filter(p => p.is_active).length === 0 && (
                                        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <BriefcaseIcon className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                            <p>Aucun poste actif. Créez d'abord des postes ci-dessus.</p>
                                        </div>
                                    )}

                                    {/* Active Nominations List */}
                                    {nominations.length > 0 ? (
                                        <div className="space-y-3">
                                            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">Nominations actives</h3>
                                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                                {nominations.map((nomination) => (
                                                    <div key={nomination.uuid} className="py-3 flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <span
                                                                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                                style={{
                                                                    backgroundColor: nomination.position.color ? `${nomination.position.color}20` : '#e5e7eb',
                                                                    color: nomination.position.color || '#374151',
                                                                }}
                                                            >
                                                                {nomination.position.name}
                                                            </span>
                                                            <div>
                                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {nomination.user.name}
                                                                </p>
                                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                    {nomination.user.email}
                                                                    {nomination.start_date && ` — depuis le ${new Date(nomination.start_date).toLocaleDateString('fr-FR')}`}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        {canManage && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-red-600 hover:text-red-700"
                                                                onClick={() => {
                                                                    router.delete(
                                                                        `/departments/${department.uuid}/nominations/${nomination.uuid}`,
                                                                        { preserveScroll: true }
                                                                    );
                                                                }}
                                                            >
                                                                <TrashIcon className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ) : positions.filter(p => p.is_active).length > 0 ? (
                                        <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                            <p>Aucune nomination active.</p>
                                        </div>
                                    ) : null}
                                </CardContent>
                            </Card>
                        </div>
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
