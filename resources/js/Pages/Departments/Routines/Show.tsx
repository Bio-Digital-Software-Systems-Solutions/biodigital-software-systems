import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    ArrowLeftIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    UserIcon,
    DocumentTextIcon,
    ClockIcon,
    ArrowUpTrayIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface RoutineSop {
    id: number;
    uuid: string;
    routine_id: number;
    routine_step_id: number | null;
    title: string;
    description: string | null;
    original_name: string;
    file_url: string;
    mime_type: string;
    formatted_file_size: string;
    extension: string;
    file_type: string;
    status: string;
    uploader: User | null;
}

interface RoutineAssignee {
    id: number;
    routine_id: number | null;
    routine_step_id: number | null;
    user: User;
    role: string;
    assigned_at: string | null;
}

interface RoutineStep {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    instructions: string | null;
    duration_minutes: number | null;
    sort_order: number;
    is_required: boolean;
    requires_validation: boolean;
    validation_status: string;
    validator: User | null;
    validated_at: string | null;
    validation_notes: string | null;
    children: RoutineStep[];
    assignees: RoutineAssignee[];
    sops: RoutineSop[];
}

interface Routine {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    status: string;
    frequency: string;
    responsible: User | null;
    creator: User;
    approver: User | null;
    approved_at: string | null;
    activated_at: string | null;
    estimated_duration_minutes: number | null;
    is_active: boolean;
    is_editable: boolean;
    steps: RoutineStep[];
    assignees: RoutineAssignee[];
    sops: RoutineSop[];
}

interface EnumOption {
    value: string;
    label: string;
    color?: string;
    icon?: string;
}

interface Props {
    department: Department;
    routine: Routine;
    departmentUsers: User[];
    statuses: EnumOption[];
    frequencies: EnumOption[];
    assigneeRoles: EnumOption[];
    sopStatuses: EnumOption[];
    canManage: boolean;
}

const statusColorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    pending_approval: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    archived: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
};

const validationStatusColorMap: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    validated: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
};

const validationStatusLabel: Record<string, string> = {
    pending: 'En attente',
    validated: 'Validée',
    rejected: 'Rejetée',
};

const sopStatusColorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    validated: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    obsolete: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    inactive: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
};

const fileTypeIcons: Record<string, string> = {
    pdf: 'PDF',
    word: 'DOC',
    presentation: 'PPT',
    spreadsheet: 'XLS',
    image: 'IMG',
    video: 'VID',
    audio: 'AUD',
    other: 'FILE',
};

export default function RoutineShow({
    department,
    routine,
    departmentUsers,
    statuses,
    frequencies,
    assigneeRoles,
    sopStatuses,
    canManage,
}: Props) {
    const [showStepDialog, setShowStepDialog] = useState(false);
    const [editingStep, setEditingStep] = useState<RoutineStep | null>(null);
    const [parentStepId, setParentStepId] = useState<number | null>(null);
    const [showSopDialog, setShowSopDialog] = useState(false);
    const [sopStepId, setSopStepId] = useState<number | null>(null);
    const [showAssigneeDialog, setShowAssigneeDialog] = useState(false);
    const [assigneeStepId, setAssigneeStepId] = useState<number | null>(null);
    const [deletingStep, setDeletingStep] = useState<RoutineStep | null>(null);
    const [deletingSop, setDeletingSop] = useState<RoutineSop | null>(null);
    const [showValidateDialog, setShowValidateDialog] = useState(false);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [validatingStep, setValidatingStep] = useState<RoutineStep | null>(null);

    const basePath = `/departments/${department.uuid}/routines/${routine.uuid}`;

    const stepForm = useForm({
        name: '',
        description: '',
        instructions: '',
        duration_minutes: '' as string | number,
        parent_id: null as number | null,
        is_required: true,
        requires_validation: true,
    });

    const sopForm = useForm({
        title: '',
        description: '',
        file: null as File | null,
        routine_step_id: null as number | null,
    });

    const assigneeForm = useForm({
        user_id: '',
        role: 'assignee',
        routine_step_id: null as number | null,
    });

    const validationForm = useForm({ notes: '' });

    const getStatusLabel = (value: string) => statuses.find(s => s.value === value)?.label ?? value;
    const getFrequencyLabel = (value: string) => frequencies.find(f => f.value === value)?.label ?? value;
    const getRoleLabel = (value: string) => assigneeRoles.find(r => r.value === value)?.label ?? value;
    const getSopStatusLabel = (value: string) => sopStatuses.find(s => s.value === value)?.label ?? value;

    const handleSopStatusChange = (sop: RoutineSop, newStatus: string) => {
        router.put(`${basePath}/sops/${sop.uuid}/status`, { status: newStatus }, {
            onSuccess: () => toast.success('Statut du SOP mis à jour'),
            onError: () => toast.error('Erreur lors de la mise à jour'),
        });
    };

    // Status actions
    const handleStatusAction = (action: string) => {
        router.post(`${basePath}/${action}`, {}, {
            onSuccess: () => toast.success('Statut mis à jour'),
            onError: () => toast.error('Erreur lors du changement de statut'),
        });
    };

    // Step CRUD
    const openAddStep = (parentId: number | null = null) => {
        setEditingStep(null);
        setParentStepId(parentId);
        stepForm.reset();
        stepForm.setData('parent_id', parentId);
        setShowStepDialog(true);
    };

    const openEditStep = (step: RoutineStep) => {
        setEditingStep(step);
        setParentStepId(null);
        stepForm.setData({
            name: step.name,
            description: step.description ?? '',
            instructions: step.instructions ?? '',
            duration_minutes: step.duration_minutes ?? '',
            parent_id: null,
            is_required: step.is_required,
            requires_validation: step.requires_validation,
        });
        setShowStepDialog(true);
    };

    const handleStepSubmit = () => {
        if (editingStep) {
            stepForm.put(`${basePath}/steps/${editingStep.uuid}`, {
                onSuccess: () => {
                    toast.success('Étape mise à jour');
                    setShowStepDialog(false);
                },
                onError: () => toast.error('Erreur'),
            });
        } else {
            stepForm.post(`${basePath}/steps`, {
                onSuccess: () => {
                    toast.success('Étape ajoutée');
                    setShowStepDialog(false);
                },
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const handleDeleteStep = () => {
        if (!deletingStep) return;
        router.delete(`${basePath}/steps/${deletingStep.uuid}`, {
            onSuccess: () => {
                toast.success('Étape supprimée');
                setDeletingStep(null);
            },
        });
    };

    // Step validation
    const openValidateStep = (step: RoutineStep) => {
        setValidatingStep(step);
        validationForm.reset();
        setShowValidateDialog(true);
    };

    const openRejectStep = (step: RoutineStep) => {
        setValidatingStep(step);
        validationForm.reset();
        setShowRejectDialog(true);
    };

    const handleValidateStep = () => {
        if (!validatingStep) return;
        validationForm.post(`${basePath}/steps/${validatingStep.uuid}/validate`, {
            onSuccess: () => {
                toast.success('Étape validée');
                setShowValidateDialog(false);
            },
        });
    };

    const handleRejectStep = () => {
        if (!validatingStep) return;
        validationForm.post(`${basePath}/steps/${validatingStep.uuid}/reject`, {
            onSuccess: () => {
                toast.success('Étape rejetée');
                setShowRejectDialog(false);
            },
        });
    };

    // SOP upload
    const openAddSop = (stepId: number | null = null) => {
        setSopStepId(stepId);
        sopForm.reset();
        sopForm.setData('routine_step_id', stepId);
        setShowSopDialog(true);
    };

    const handleSopSubmit = () => {
        sopForm.post(`${basePath}/sops`, {
            forceFormData: true,
            onSuccess: () => {
                toast.success('SOP ajoutée');
                setShowSopDialog(false);
            },
            onError: () => toast.error('Erreur lors de l\'upload'),
        });
    };

    const handleDeleteSop = () => {
        if (!deletingSop) return;
        router.delete(`${basePath}/sops/${deletingSop.uuid}`, {
            onSuccess: () => {
                toast.success('SOP supprimée');
                setDeletingSop(null);
            },
        });
    };

    // Assignee
    const openAddAssignee = (stepId: number | null = null) => {
        setAssigneeStepId(stepId);
        assigneeForm.reset();
        assigneeForm.setData('routine_step_id', stepId);
        setShowAssigneeDialog(true);
    };

    const handleAssigneeSubmit = () => {
        assigneeForm.post(`${basePath}/assignees`, {
            onSuccess: () => {
                toast.success('Personne assignée');
                setShowAssigneeDialog(false);
            },
            onError: () => toast.error('Erreur'),
        });
    };

    const handleRemoveAssignee = (assigneeId: number) => {
        router.delete(`${basePath}/assignees/${assigneeId}`, {
            onSuccess: () => toast.success('Assignation retirée'),
        });
    };

    // Render step card
    const renderStep = (step: RoutineStep, depth: number = 0) => (
        <div key={step.uuid} className={depth > 0 ? 'ml-6 border-l-2 border-gray-200 dark:border-gray-700 pl-4' : ''}>
            <Card className="mb-3">
                <CardContent className="py-3">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                                <span className="font-medium text-gray-900 dark:text-white">{step.name}</span>
                                {step.is_required && (
                                    <Badge variant="outline" className="text-xs">Requis</Badge>
                                )}
                                {step.requires_validation && (
                                    <Badge className={validationStatusColorMap[step.validation_status] ?? ''}>
                                        {validationStatusLabel[step.validation_status] ?? step.validation_status}
                                    </Badge>
                                )}
                                {step.duration_minutes && (
                                    <span className="text-xs text-gray-500 flex items-center gap-1">
                                        <ClockIcon className="h-3 w-3" />{step.duration_minutes} min
                                    </span>
                                )}
                            </div>
                            {step.description && (
                                <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">{step.description}</p>
                            )}
                            {step.instructions && (
                                <div className="text-xs text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800 rounded p-2 mb-2">
                                    <span className="font-medium">Instructions:</span> {step.instructions}
                                </div>
                            )}

                            {/* Step assignees */}
                            {step.assignees.length > 0 && (
                                <div className="flex items-center gap-2 mb-2">
                                    <UserIcon className="h-3.5 w-3.5 text-gray-400" />
                                    {step.assignees.map(a => (
                                        <Badge key={a.id} variant="outline" className="text-xs">
                                            {a.user.first_name} {a.user.last_name}
                                            <span className="ml-1 opacity-60">({getRoleLabel(a.role)})</span>
                                            {canManage && (
                                                <button
                                                    onClick={() => handleRemoveAssignee(a.id)}
                                                    className="ml-1 hover:text-red-600"
                                                >
                                                    <XCircleIcon className="h-3 w-3" />
                                                </button>
                                            )}
                                        </Badge>
                                    ))}
                                </div>
                            )}

                            {/* Step SOPs */}
                            {step.sops.length > 0 && (
                                <div className="flex flex-wrap gap-1 mb-2">
                                    {step.sops.map(sop => (
                                        <a
                                            key={sop.uuid}
                                            href={sop.file_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded px-2 py-0.5 hover:bg-blue-100"
                                        >
                                            <DocumentTextIcon className="h-3 w-3" />
                                            {sop.title}
                                        </a>
                                    ))}
                                </div>
                            )}

                            {/* Validation notes */}
                            {step.validation_notes && (
                                <p className="text-xs text-gray-500 italic">Note: {step.validation_notes}</p>
                            )}
                        </div>

                        {canManage && (
                            <div className="flex items-center gap-1 ml-2">
                                {step.validation_status === 'pending' && step.requires_validation && (
                                    <>
                                        <Button variant="ghost" size="sm" onClick={() => openValidateStep(step)} title="Valider">
                                            <CheckCircleIcon className="h-4 w-4 text-green-600" />
                                        </Button>
                                        <Button variant="ghost" size="sm" onClick={() => openRejectStep(step)} title="Rejeter">
                                            <XCircleIcon className="h-4 w-4 text-red-600" />
                                        </Button>
                                    </>
                                )}
                                <Button variant="ghost" size="sm" onClick={() => openAddStep(step.id)} title="Sous-étape">
                                    <PlusIcon className="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" onClick={() => openAddSop(step.id)} title="Ajouter SOP">
                                    <ArrowUpTrayIcon className="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" onClick={() => openAddAssignee(step.id)} title="Assigner">
                                    <UserIcon className="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" onClick={() => openEditStep(step)}>
                                    <PencilIcon className="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" onClick={() => setDeletingStep(step)}>
                                    <TrashIcon className="h-4 w-4 text-red-500" />
                                </Button>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Sub-steps */}
            {step.children?.map(child => renderStep(child, depth + 1))}
        </div>
    );

    return (
        <DashboardLayout>
            <Head title={`${routine.name} - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Link href={`/departments/${department.uuid}/routines`}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeftIcon className="h-4 w-4 mr-1" />
                                Retour
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{routine.name}</h1>
                                <Badge className={statusColorMap[routine.status] ?? ''}>
                                    {getStatusLabel(routine.status)}
                                </Badge>
                            </div>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {getFrequencyLabel(routine.frequency)}
                                {routine.estimated_duration_minutes && ` - ~${routine.estimated_duration_minutes} min`}
                            </p>
                        </div>
                    </div>

                    {canManage && (
                        <div className="flex items-center gap-2">
                            {routine.is_editable && (
                                <Link href={`${basePath}/edit`}>
                                    <Button variant="outline" size="sm">
                                        <PencilIcon className="h-4 w-4 mr-1" />
                                        Modifier
                                    </Button>
                                </Link>
                            )}
                            {routine.status === 'draft' && (
                                <Button size="sm" onClick={() => handleStatusAction('submit')}>
                                    Soumettre pour approbation
                                </Button>
                            )}
                            {routine.status === 'pending_approval' && (
                                <>
                                    <Button size="sm" variant="default" onClick={() => handleStatusAction('approve')}>
                                        <CheckCircleIcon className="h-4 w-4 mr-1" />
                                        Approuver
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={() => handleStatusAction('reject')}>
                                        <XCircleIcon className="h-4 w-4 mr-1" />
                                        Rejeter
                                    </Button>
                                </>
                            )}
                            {routine.status === 'approved' && (
                                <Button size="sm" onClick={() => handleStatusAction('activate')}>
                                    Activer
                                </Button>
                            )}
                            {routine.status === 'active' && (
                                <Button size="sm" variant="outline" onClick={() => handleStatusAction('archive')}>
                                    Archiver
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="overview">Vue d'ensemble</TabsTrigger>
                        <TabsTrigger value="steps">
                            Étapes ({routine.steps.length})
                        </TabsTrigger>
                        <TabsTrigger value="sops">
                            SOPs ({routine.sops.length})
                        </TabsTrigger>
                    </TabsList>

                    {/* Overview */}
                    <TabsContent value="overview">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Détails</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {routine.description && (
                                        <div>
                                            <Label className="text-xs text-gray-500">Description</Label>
                                            <p className="text-sm">{routine.description}</p>
                                        </div>
                                    )}
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label className="text-xs text-gray-500">Fréquence</Label>
                                            <p className="text-sm font-medium">{getFrequencyLabel(routine.frequency)}</p>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-500">Durée estimée</Label>
                                            <p className="text-sm font-medium">
                                                {routine.estimated_duration_minutes ? `${routine.estimated_duration_minutes} min` : '-'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label className="text-xs text-gray-500">Responsable</Label>
                                            <p className="text-sm font-medium">
                                                {routine.responsible ? `${routine.responsible.first_name} ${routine.responsible.last_name}` : '-'}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-500">Créé par</Label>
                                            <p className="text-sm font-medium">
                                                {routine.creator.first_name} {routine.creator.last_name}
                                            </p>
                                        </div>
                                    </div>
                                    {routine.approver && (
                                        <div>
                                            <Label className="text-xs text-gray-500">Approuvé par</Label>
                                            <p className="text-sm font-medium">
                                                {routine.approver.first_name} {routine.approver.last_name}
                                                {routine.approved_at && (
                                                    <span className="text-gray-400 ml-2">
                                                        le {new Date(routine.approved_at).toLocaleDateString('fr-FR')}
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Assignees */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>Personnes assignées</CardTitle>
                                        {canManage && (
                                            <Button size="sm" variant="outline" onClick={() => openAddAssignee(null)}>
                                                <PlusIcon className="h-4 w-4 mr-1" />
                                                Assigner
                                            </Button>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {routine.assignees.length === 0 ? (
                                        <p className="text-sm text-gray-500">Aucune personne assignée.</p>
                                    ) : (
                                        <div className="space-y-2">
                                            {routine.assignees.map(a => (
                                                <div key={a.id} className="flex items-center justify-between py-1">
                                                    <div className="flex items-center gap-2">
                                                        <UserIcon className="h-4 w-4 text-gray-400" />
                                                        <span className="text-sm">
                                                            {a.user.first_name} {a.user.last_name}
                                                        </span>
                                                        <Badge variant="outline" className="text-xs">
                                                            {getRoleLabel(a.role)}
                                                        </Badge>
                                                    </div>
                                                    {canManage && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleRemoveAssignee(a.id)}
                                                        >
                                                            <TrashIcon className="h-3.5 w-3.5 text-red-500" />
                                                        </Button>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Steps */}
                    <TabsContent value="steps">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Étapes de la routine</CardTitle>
                                        <CardDescription>
                                            Définissez et organisez les étapes. Chaque étape peut avoir des sous-étapes.
                                        </CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button size="sm" onClick={() => openAddStep(null)}>
                                            <PlusIcon className="h-4 w-4 mr-1" />
                                            Ajouter une étape
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {routine.steps.length === 0 ? (
                                    <div className="text-center py-8">
                                        <ArrowPathIcon className="h-10 w-10 text-gray-400 mx-auto mb-3" />
                                        <p className="text-gray-500">Aucune étape définie.</p>
                                        {canManage && (
                                            <Button size="sm" variant="outline" className="mt-3" onClick={() => openAddStep(null)}>
                                                <PlusIcon className="h-4 w-4 mr-1" />
                                                Ajouter la première étape
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {routine.steps.map(step => renderStep(step))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* SOPs */}
                    <TabsContent value="sops">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Procédures opérationnelles (SOPs)</CardTitle>
                                        <CardDescription>
                                            Documents, vidéos et guides associés à cette routine.
                                        </CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button size="sm" onClick={() => openAddSop(null)}>
                                            <ArrowUpTrayIcon className="h-4 w-4 mr-1" />
                                            Ajouter un SOP
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {routine.sops.length === 0 ? (
                                    <div className="text-center py-8">
                                        <DocumentTextIcon className="h-10 w-10 text-gray-400 mx-auto mb-3" />
                                        <p className="text-gray-500">Aucun SOP associé à cette routine.</p>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        {routine.sops.map(sop => (
                                            <div
                                                key={sop.uuid}
                                                className={`flex items-center gap-3 p-3 rounded-lg border dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer ${sop.status === 'obsolete' ? 'opacity-50' : ''}`}
                                                onClick={() => window.open(sop.file_url, '_blank')}
                                            >
                                                <div className="flex-shrink-0 w-10 h-10 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-xs font-bold text-blue-700 dark:text-blue-300">
                                                    {fileTypeIcons[sop.file_type] ?? 'FILE'}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline truncate">
                                                            {sop.title}
                                                        </span>
                                                        <Badge className={`text-[10px] px-1.5 py-0 ${sopStatusColorMap[sop.status] ?? ''}`}>
                                                            {getSopStatusLabel(sop.status)}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-xs text-gray-500">
                                                        {sop.original_name} - {sop.formatted_file_size}
                                                    </p>
                                                </div>
                                                {canManage && (
                                                    <div className="flex items-center gap-1" onClick={e => e.stopPropagation()}>
                                                        <Select
                                                            value={sop.status}
                                                            onValueChange={v => handleSopStatusChange(sop, v)}
                                                        >
                                                            <SelectTrigger className="h-7 w-[120px] text-xs">
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {sopStatuses.map(s => (
                                                                    <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <Button variant="ghost" size="sm" onClick={() => setDeletingSop(sop)}>
                                                            <TrashIcon className="h-4 w-4 text-red-500" />
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Step Dialog */}
            <Dialog open={showStepDialog} onOpenChange={setShowStepDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingStep ? 'Modifier l\'étape' : parentStepId ? 'Ajouter une sous-étape' : 'Ajouter une étape'}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 px-6 pb-4">
                        <div>
                            <Label htmlFor="step-name">Nom *</Label>
                            <Input
                                id="step-name"
                                value={stepForm.data.name}
                                onChange={e => stepForm.setData('name', e.target.value)}
                            />
                            {stepForm.errors.name && <p className="text-sm text-red-600 mt-1">{stepForm.errors.name}</p>}
                        </div>
                        <div>
                            <Label htmlFor="step-desc">Description</Label>
                            <Textarea
                                id="step-desc"
                                value={stepForm.data.description}
                                onChange={e => stepForm.setData('description', e.target.value)}
                                rows={3}
                            />
                        </div>
                        <div>
                            <Label htmlFor="step-instr">Instructions</Label>
                            <Textarea
                                id="step-instr"
                                value={stepForm.data.instructions}
                                onChange={e => stepForm.setData('instructions', e.target.value)}
                                rows={3}
                            />
                        </div>
                        <div>
                            <Label htmlFor="step-duration">Durée (minutes)</Label>
                            <Input
                                id="step-duration"
                                type="number"
                                min={1}
                                value={stepForm.data.duration_minutes}
                                onChange={e => stepForm.setData('duration_minutes', e.target.value ? parseInt(e.target.value) : '')}
                            />
                        </div>
                        <div className="flex items-center gap-6 pb-4">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="step-required"
                                    checked={stepForm.data.is_required}
                                    onCheckedChange={(checked) => stepForm.setData('is_required', !!checked)}
                                />
                                <Label htmlFor="step-required">Requis</Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="step-validation"
                                    checked={stepForm.data.requires_validation}
                                    onCheckedChange={(checked) => stepForm.setData('requires_validation', !!checked)}
                                />
                                <Label htmlFor="step-validation">Nécessite validation</Label>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowStepDialog(false)}>Annuler</Button>
                        <Button onClick={handleStepSubmit} disabled={stepForm.processing}>
                            {editingStep ? 'Enregistrer' : 'Ajouter'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* SOP Upload Dialog */}
            <Dialog open={showSopDialog} onOpenChange={setShowSopDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajouter un SOP</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 px-6 pb-4">
                        <div>
                            <Label htmlFor="sop-title">Titre *</Label>
                            <Input
                                id="sop-title"
                                value={sopForm.data.title}
                                onChange={e => sopForm.setData('title', e.target.value)}
                            />
                            {sopForm.errors.title && <p className="text-sm text-red-600 mt-1">{sopForm.errors.title}</p>}
                        </div>
                        <div>
                            <Label htmlFor="sop-desc">Description</Label>
                            <Textarea
                                id="sop-desc"
                                value={sopForm.data.description}
                                onChange={e => sopForm.setData('description', e.target.value)}
                                rows={2}
                            />
                        </div>
                        <div>
                            <Label htmlFor="sop-file">Fichier * (PDF, Word, PowerPoint, Image, Vidéo - max 50MB)</Label>
                            <Input
                                id="sop-file"
                                type="file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov,.avi"
                                onChange={e => sopForm.setData('file', e.target.files?.[0] ?? null)}
                                className="mt-1"
                            />
                            {sopForm.errors.file && <p className="text-sm text-red-600 mt-1">{sopForm.errors.file}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowSopDialog(false)}>Annuler</Button>
                        <Button onClick={handleSopSubmit} disabled={sopForm.processing}>
                            {sopForm.processing ? 'Upload...' : 'Ajouter'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Assignee Dialog */}
            <Dialog open={showAssigneeDialog} onOpenChange={setShowAssigneeDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Assigner une personne</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 px-6 pb-4">
                        <div>
                            <Label>Personne *</Label>
                            <Select
                                value={assigneeForm.data.user_id}
                                onValueChange={v => assigneeForm.setData('user_id', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Choisir une personne" />
                                </SelectTrigger>
                                <SelectContent>
                                    {departmentUsers.map(u => (
                                        <SelectItem key={u.id} value={String(u.id)}>
                                            {u.first_name} {u.last_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Rôle *</Label>
                            <Select
                                value={assigneeForm.data.role}
                                onValueChange={v => assigneeForm.setData('role', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {assigneeRoles.map(r => (
                                        <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowAssigneeDialog(false)}>Annuler</Button>
                        <Button onClick={handleAssigneeSubmit} disabled={assigneeForm.processing}>
                            Assigner
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Validate Step Dialog */}
            <Dialog open={showValidateDialog} onOpenChange={setShowValidateDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Valider l'étape "{validatingStep?.name}"</DialogTitle>
                    </DialogHeader>
                    <div className="px-6 pb-4">
                        <Label>Notes (optionnel)</Label>
                        <Textarea
                            value={validationForm.data.notes}
                            onChange={e => validationForm.setData('notes', e.target.value)}
                            rows={3}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowValidateDialog(false)}>Annuler</Button>
                        <Button onClick={handleValidateStep}>Valider</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject Step Dialog */}
            <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rejeter l'étape "{validatingStep?.name}"</DialogTitle>
                    </DialogHeader>
                    <div className="px-6 pb-4">
                        <Label>Raison du rejet *</Label>
                        <Textarea
                            value={validationForm.data.notes}
                            onChange={e => validationForm.setData('notes', e.target.value)}
                            rows={3}
                            placeholder="Expliquez la raison du rejet..."
                        />
                        {validationForm.errors.notes && <p className="text-sm text-red-600 mt-1">{validationForm.errors.notes}</p>}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowRejectDialog(false)}>Annuler</Button>
                        <Button variant="destructive" onClick={handleRejectStep}>Rejeter</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Step Confirmation */}
            <DeleteConfirmationDialog
                open={!!deletingStep}
                onOpenChange={() => setDeletingStep(null)}
                onConfirm={handleDeleteStep}
                title="Supprimer l'étape"
                description={`Êtes-vous sûr de vouloir supprimer l'étape "${deletingStep?.name}" et toutes ses sous-étapes ?`}
            />

            {/* Delete SOP Confirmation */}
            <DeleteConfirmationDialog
                open={!!deletingSop}
                onOpenChange={() => setDeletingSop(null)}
                onConfirm={handleDeleteSop}
                title="Supprimer le SOP"
                description={`Êtes-vous sûr de vouloir supprimer le SOP "${deletingSop?.title}" ?`}
            />
        </DashboardLayout>
    );
}
