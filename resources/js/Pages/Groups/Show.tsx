import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import { useToast } from '@/Components/ui/toast';
import GroupCalendar from '@/Components/Group/GroupCalendar';
import GroupStatisticsOperational, { type GroupStatistics } from '@/Components/Group/GroupStatisticsOperational';
import GroupStatisticsAnalytical from '@/Components/Group/GroupStatisticsAnalytical';
import GroupVisitorsTab from '@/Components/Group/GroupVisitorsTab';
import {
    ArrowLeftIcon,
    PencilIcon,
    UserPlusIcon,
    UserMinusIcon,
    UserGroupIcon,
    UserIcon,
    ChartBarIcon,
    CalendarDaysIcon,
    ClockIcon,
    PresentationChartLineIcon,
    PlusIcon,
    MapPinIcon,
    EyeIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, XCircleIcon, Squares2X2Icon, ChartPieIcon } from '@heroicons/react/24/solid';
import type { Appointment } from '@/Types/appointment';
import axios from 'axios';
import { toast } from 'sonner';

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    joined_at?: string;
}

interface GroupMeeting {
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

interface GroupActivityData {
    uuid: string;
    title: string;
    description: string | null;
    activity_date: string;
    start_time: string | null;
    end_time: string | null;
    status: string;
    type: string;
    location: string | null;
    notes: string | null;
    assignee: { id: number; uuid: string; name: string } | null;
    creator: { id: number; uuid: string; name: string } | null;
    created_at: string;
}

interface Group {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description: string | null;
    max_members: number | null;
    is_active: boolean;
    leader: User | null;
    users: User[];
    members_count: number;
    can_join: boolean;
    is_at_capacity: boolean;
}

interface Props {
    group: Group;
    availableUsers: User[];
    canManage: boolean;
    meetings?: GroupMeeting[];
    appointments?: Appointment[];
    activities?: GroupActivityData[];
    statistics?: GroupStatistics;
    visitorsCount?: number;
    pendingSuggestionsCount?: number;
}

interface ActivityFormData {
    title: string;
    description: string;
    activity_date: string;
    start_time: string;
    end_time: string;
    type: string;
    location: string;
    notes: string;
    assigned_to: string;
}

const defaultActivityForm: ActivityFormData = {
    title: '',
    description: '',
    activity_date: '',
    start_time: '09:00',
    end_time: '10:00',
    type: 'task',
    location: '',
    notes: '',
    assigned_to: '',
};

const statusLabels: Record<string, string> = {
    planned: 'Planifié',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé',
};

const statusColors: Record<string, string> = {
    planned: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
};

const typeLabels: Record<string, string> = {
    meeting: 'Réunion',
    task: 'Tâche',
    event: 'Événement',
    other: 'Autre',
};

export default function ShowGroup({
    group,
    availableUsers,
    canManage,
    meetings = [],
    appointments = [],
    activities = [],
    statistics,
    visitorsCount = 0,
    pendingSuggestionsCount = 0,
}: Props) {
    const [activeTab, setActiveTab] = useState('overview');
    const [statsViewMode, setStatsViewMode] = useState<'operational' | 'analytical'>('operational');
    const [isAddMemberModalOpen, setIsAddMemberModalOpen] = useState(false);
    const [isToggleStatusModalOpen, setIsToggleStatusModalOpen] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState('');
    const [showActivityForm, setShowActivityForm] = useState(false);
    const [activityFormData, setActivityFormData] = useState<ActivityFormData>({ ...defaultActivityForm });
    const [activityProcessing, setActivityProcessing] = useState(false);
    const { confirm } = useConfirm();
    const { showSuccess, showError } = useToast();

    const handleAddMember = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUserId) return;

        router.post(`/groups/${group.uuid}/add-member`, {
            user_id: selectedUserId,
        }, {
            onSuccess: () => {
                setIsAddMemberModalOpen(false);
                setSelectedUserId('');
                showSuccess('Membre ajouté avec succès au groupe');
            },
            onError: () => {
                showError('Erreur lors de l\'ajout du membre');
            },
        });
    };

    const handleRemoveMember = async (userUuid: string) => {
        const confirmed = await confirm({
            title: 'Retirer le membre',
            message: 'Êtes-vous sûr de vouloir retirer ce membre du groupe ?',
            confirmText: 'Retirer',
            cancelText: 'Annuler',
            type: 'warning',
        });

        if (!confirmed) return;

        router.delete(`/groups/${group.uuid}/users/${userUuid}`, {
            onSuccess: () => {
                showSuccess('Membre retiré avec succès du groupe');
            },
            onError: () => {
                showError('Erreur lors du retrait du membre');
            },
        });
    };

    const handleToggleStatus = () => {
        router.patch(`/groups/${group.uuid}`, {
            name: group.name,
            code: group.code,
            description: group.description,
            max_members: group.max_members,
            is_active: !group.is_active,
        }, {
            onSuccess: () => {
                setIsToggleStatusModalOpen(false);
                showSuccess(`Groupe ${group.is_active ? 'désactivé' : 'activé'} avec succès`);
            },
            onError: () => {
                showError('Erreur lors du changement de statut');
            },
        });
    };

    const handleCreateActivity = async () => {
        if (!activityFormData.title || !activityFormData.activity_date) {
            toast.error('Veuillez remplir tous les champs obligatoires.');
            return;
        }

        setActivityProcessing(true);
        try {
            await axios.post(`/api/groups/${group.uuid}/activities`, {
                title: activityFormData.title,
                description: activityFormData.description || null,
                activity_date: activityFormData.activity_date,
                start_time: activityFormData.start_time || null,
                end_time: activityFormData.end_time || null,
                type: activityFormData.type,
                location: activityFormData.location || null,
                notes: activityFormData.notes || null,
                assigned_to: activityFormData.assigned_to ? parseInt(activityFormData.assigned_to) : null,
            });

            toast.success('Activité créée avec succès.');
            setShowActivityForm(false);
            setActivityFormData({ ...defaultActivityForm });
            window.location.reload();
        } catch {
            toast.error('Erreur lors de la création de l\'activité.');
        } finally {
            setActivityProcessing(false);
        }
    };

    const handleUpdateActivityStatus = async (activityUuid: string, newStatus: string) => {
        try {
            await axios.patch(`/api/groups/${group.uuid}/activities/${activityUuid}`, {
                status: newStatus,
            });
            toast.success('Statut mis à jour.');
            window.location.reload();
        } catch {
            toast.error('Erreur lors de la mise à jour.');
        }
    };

    const handleDeleteActivity = async (activityUuid: string) => {
        const confirmed = await confirm({
            title: 'Supprimer l\'activité',
            message: 'Êtes-vous sûr de vouloir supprimer cette activité ?',
            confirmText: 'Supprimer',
            cancelText: 'Annuler',
            type: 'warning',
        });

        if (!confirmed) return;

        try {
            await axios.delete(`/api/groups/${group.uuid}/activities/${activityUuid}`);
            toast.success('Activité supprimée.');
            window.location.reload();
        } catch {
            toast.error('Erreur lors de la suppression.');
        }
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    return (
        <DashboardLayout>
            <Head title={group.name} />

            <div className="p-3 sm:p-6">
                {/* Header */}
                <div className="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <Button variant="outline" size="sm" asChild className="w-fit">
                            <Link href="/groups">
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Retour aux Groupes</span>
                                <span className="sm:hidden">Retour</span>
                            </Link>
                        </Button>
                        <div>
                            <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                                <h1 className="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                    {group.name}
                                </h1>
                                {group.is_active ? (
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
                                {group.is_at_capacity && (
                                    <Badge className="bg-orange-500">Complet</Badge>
                                )}
                            </div>
                            <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Code: {group.code}
                            </p>
                        </div>
                    </div>

                    {canManage && (
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/groups/${group.uuid}/edit`}>
                                    <PencilIcon className="h-4 w-4 sm:mr-2" />
                                    <span className="hidden sm:inline">Modifier</span>
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setIsToggleStatusModalOpen(true)}
                            >
                                {group.is_active ? 'Désactiver' : 'Activer'}
                            </Button>
                        </div>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                            <CardTitle className="text-xs sm:text-sm font-medium">Capacité</CardTitle>
                            <ChartBarIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                        </CardHeader>
                        <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                            <div className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                {group.members_count} / {group.max_members || '∞'}
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {group.max_members
                                    ? `${Math.round((group.members_count / group.max_members) * 100)}% rempli`
                                    : 'Capacité illimitée'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                            <CardTitle className="text-xs sm:text-sm font-medium">Membres</CardTitle>
                            <UserGroupIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                        </CardHeader>
                        <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                            <div className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                {group.members_count}
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">
                                membres dans ce groupe
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 p-3 sm:p-6 sm:pb-2">
                            <CardTitle className="text-xs sm:text-sm font-medium">Chef</CardTitle>
                            <UserIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-500" />
                        </CardHeader>
                        <CardContent className="p-3 pt-0 sm:p-6 sm:pt-0">
                            {group.leader ? (
                                <div>
                                    <div className="text-sm sm:text-lg font-semibold text-gray-900 dark:text-white truncate">
                                        {group.leader.name}
                                    </div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate hidden sm:block">
                                        {group.leader.email}
                                    </p>
                                </div>
                            ) : (
                                <div className="text-gray-400 text-sm">Non assigné</div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Description */}
                {group.description && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-gray-700 dark:text-gray-300">
                                {group.description}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                    <div className="overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">
                        <TabsList className="flex w-max sm:grid sm:w-full sm:grid-cols-5 gap-1 p-1">
                            <TabsTrigger value="overview" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <UserGroupIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Membres</span>
                                <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{group.members_count}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="visitors" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <EyeIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Visiteurs</span>
                                <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{visitorsCount}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="agenda" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <CalendarDaysIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Agenda</span>
                                <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{meetings.length + appointments.length}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="planning" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <ClockIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Planning</span>
                                <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1 sm:px-1.5">{activities.length}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="statistics" className="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 whitespace-nowrap">
                                <PresentationChartLineIcon className="h-4 w-4" />
                                <span className="hidden md:inline">Stats</span>
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    {/* Visitors Tab */}
                    <TabsContent value="visitors">
                        <GroupVisitorsTab
                            groupUuid={group.uuid}
                            canManage={canManage}
                            pendingSuggestionsCount={pendingSuggestionsCount}
                            meetings={meetings}
                            activities={activities}
                            groupMembers={group.users.map((u) => ({ id: u.id, uuid: u.uuid, name: u.name }))}
                        />
                    </TabsContent>

                    {/* Members Tab */}
                    <TabsContent value="overview">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Membres du Groupe</CardTitle>
                                        <CardDescription>
                                            {group.members_count} membre(s)
                                        </CardDescription>
                                    </div>
                                    {canManage && !group.is_at_capacity && (
                                        <Button onClick={() => setIsAddMemberModalOpen(true)}>
                                            <UserPlusIcon className="h-4 w-4 mr-2" />
                                            Ajouter un Membre
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {group.users.length > 0 ? (
                                    <div className="space-y-2">
                                        {group.users.map((user) => (
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
                                                            {group.leader?.id === user.id && (
                                                                <Badge className="bg-purple-500">Chef</Badge>
                                                            )}
                                                        </div>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                                            {user.email}
                                                            {user.joined_at && (
                                                                <span className="ml-2">• Rejoint le {formatDate(user.joined_at)}</span>
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                                {canManage && group.leader?.id !== user.id && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleRemoveMember(user.uuid)}
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
                                        <p>Aucun membre dans ce groupe</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Agenda Tab */}
                    <TabsContent value="agenda">
                        <GroupCalendar
                            appointments={appointments}
                            meetings={meetings}
                            groupId={group.id}
                            groupUuid={group.uuid}
                            canManage={canManage}
                            groupUsers={group.users.map(u => ({ id: u.id, name: u.name, email: u.email }))}
                        />
                    </TabsContent>

                    {/* Planning Tab */}
                    <TabsContent value="planning">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Planning du Groupe</h2>
                                {canManage && (
                                    <Button size="sm" onClick={() => setShowActivityForm(!showActivityForm)}>
                                        <PlusIcon className="h-4 w-4 mr-2" />
                                        Nouvelle Activité
                                    </Button>
                                )}
                            </div>

                            {/* Activity Creation Form */}
                            {showActivityForm && canManage && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">Nouvelle activité</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <Label>Titre *</Label>
                                                    <Input
                                                        value={activityFormData.title}
                                                        onChange={(e) => setActivityFormData({ ...activityFormData, title: e.target.value })}
                                                        placeholder="Titre de l'activité"
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Type</Label>
                                                    <Select
                                                        value={activityFormData.type}
                                                        onValueChange={(v) => setActivityFormData({ ...activityFormData, type: v })}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="task">Tâche</SelectItem>
                                                            <SelectItem value="meeting">Réunion</SelectItem>
                                                            <SelectItem value="event">Événement</SelectItem>
                                                            <SelectItem value="other">Autre</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                            <div>
                                                <Label>Description</Label>
                                                <Textarea
                                                    value={activityFormData.description}
                                                    onChange={(e) => setActivityFormData({ ...activityFormData, description: e.target.value })}
                                                    rows={2}
                                                />
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <Label>Date *</Label>
                                                    <Input
                                                        type="date"
                                                        value={activityFormData.activity_date}
                                                        onChange={(e) => setActivityFormData({ ...activityFormData, activity_date: e.target.value })}
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Heure début</Label>
                                                    <Input
                                                        type="time"
                                                        value={activityFormData.start_time}
                                                        onChange={(e) => setActivityFormData({ ...activityFormData, start_time: e.target.value })}
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Heure fin</Label>
                                                    <Input
                                                        type="time"
                                                        value={activityFormData.end_time}
                                                        onChange={(e) => setActivityFormData({ ...activityFormData, end_time: e.target.value })}
                                                    />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <Label>Lieu</Label>
                                                    <Input
                                                        value={activityFormData.location}
                                                        onChange={(e) => setActivityFormData({ ...activityFormData, location: e.target.value })}
                                                        placeholder="Lieu de l'activité"
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Assigné à</Label>
                                                    <Select
                                                        value={activityFormData.assigned_to}
                                                        onValueChange={(v) => setActivityFormData({ ...activityFormData, assigned_to: v })}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Sélectionner un membre" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {group.users.map(user => (
                                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                                    {user.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                            <div>
                                                <Label>Notes</Label>
                                                <Textarea
                                                    value={activityFormData.notes}
                                                    onChange={(e) => setActivityFormData({ ...activityFormData, notes: e.target.value })}
                                                    rows={2}
                                                />
                                            </div>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" onClick={() => setShowActivityForm(false)}>
                                                    Annuler
                                                </Button>
                                                <Button size="sm" onClick={handleCreateActivity} disabled={activityProcessing}>
                                                    {activityProcessing ? 'Création...' : 'Créer l\'activité'}
                                                </Button>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Activities List */}
                            {activities.length > 0 ? (
                                <div className="space-y-3">
                                    {activities.map(activity => (
                                        <Card key={activity.uuid}>
                                            <CardContent className="p-4">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            <h3 className="font-medium text-gray-900 dark:text-white">
                                                                {activity.title}
                                                            </h3>
                                                            <Badge className={statusColors[activity.status] || 'bg-gray-100'}>
                                                                {statusLabels[activity.status] || activity.status}
                                                            </Badge>
                                                            <Badge variant="outline">
                                                                {typeLabels[activity.type] || activity.type}
                                                            </Badge>
                                                        </div>
                                                        {activity.description && (
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                                {activity.description}
                                                            </p>
                                                        )}
                                                        <div className="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                            <span className="flex items-center gap-1">
                                                                <CalendarDaysIcon className="h-4 w-4" />
                                                                {formatDate(activity.activity_date)}
                                                            </span>
                                                            {activity.start_time && (
                                                                <span className="flex items-center gap-1">
                                                                    <ClockIcon className="h-4 w-4" />
                                                                    {activity.start_time}{activity.end_time ? ` - ${activity.end_time}` : ''}
                                                                </span>
                                                            )}
                                                            {activity.location && (
                                                                <span className="flex items-center gap-1">
                                                                    <MapPinIcon className="h-4 w-4" />
                                                                    {activity.location}
                                                                </span>
                                                            )}
                                                            {activity.assignee && (
                                                                <span className="flex items-center gap-1">
                                                                    <UserIcon className="h-4 w-4" />
                                                                    {activity.assignee.name}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    {canManage && (
                                                        <div className="flex items-center gap-1 ml-4">
                                                            {activity.status !== 'completed' && activity.status !== 'cancelled' && (
                                                                <Select
                                                                    value={activity.status}
                                                                    onValueChange={(v) => handleUpdateActivityStatus(activity.uuid, v)}
                                                                >
                                                                    <SelectTrigger className="w-32 h-8 text-xs">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem value="planned">Planifié</SelectItem>
                                                                        <SelectItem value="in_progress">En cours</SelectItem>
                                                                        <SelectItem value="completed">Terminé</SelectItem>
                                                                        <SelectItem value="cancelled">Annulé</SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            )}
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-red-600 hover:text-red-700"
                                                                onClick={() => handleDeleteActivity(activity.uuid)}
                                                            >
                                                                Suppr.
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <ClockIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>Aucune activité planifiée</p>
                                    {canManage && !showActivityForm && (
                                        <Button className="mt-4" size="sm" onClick={() => setShowActivityForm(true)}>
                                            <PlusIcon className="h-4 w-4 mr-2" />
                                            Créer une activité
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>
                    </TabsContent>

                    {/* Statistics Tab */}
                    <TabsContent value="statistics">
                        <div className="space-y-6">
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
                                <GroupStatisticsOperational statistics={statistics} />
                            ) : (
                                <GroupStatisticsAnalytical statistics={statistics} />
                            )}
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Add Member Modal */}
            {canManage && (
                <Dialog open={isAddMemberModalOpen} onOpenChange={setIsAddMemberModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Ajouter un Membre</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleAddMember}>
                            <div className="space-y-4 py-4 px-6">
                                <div>
                                    <label className="block text-sm font-medium mb-2">Utilisateur</label>
                                    <Select
                                        value={selectedUserId}
                                        onValueChange={setSelectedUserId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un utilisateur">
                                                {selectedUserId ? availableUsers.find(u => u.id.toString() === selectedUserId)?.name : ''}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableUsers.map((user) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                    {user.name} ({user.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
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
                <Dialog open={isToggleStatusModalOpen} onOpenChange={setIsToggleStatusModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                {group.is_active ? 'Désactiver' : 'Activer'} le Groupe
                            </DialogTitle>
                        </DialogHeader>
                        <div className="py-4 px-6">
                            <p className="text-gray-700 dark:text-gray-300">
                                Êtes-vous sûr de vouloir {group.is_active ? 'désactiver' : 'activer'} ce groupe ?
                            </p>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setIsToggleStatusModalOpen(false)}
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
}
