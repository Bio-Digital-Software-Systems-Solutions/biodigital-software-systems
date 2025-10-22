import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import { useToast } from '@/Components/ui/toast';
import {
    ArrowLeftIcon,
    PencilIcon,
    UserPlusIcon,
    UserMinusIcon,
    UserGroupIcon,
    UserIcon,
    ChartBarIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/solid';

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    joined_at?: string;
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
}

export default function ShowGroup({ group, availableUsers, canManage }: Props) {
    const [isAddMemberModalOpen, setIsAddMemberModalOpen] = useState(false);
    const [isToggleStatusModalOpen, setIsToggleStatusModalOpen] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState('');
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
            type: 'warning'
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
            ...group,
            is_active: !group.is_active,
        } as any, {
            onSuccess: () => {
                setIsToggleStatusModalOpen(false);
                showSuccess(`Groupe ${group.is_active ? 'désactivé' : 'activé'} avec succès`);
            },
            onError: () => {
                showError('Erreur lors du changement de statut');
            },
        });
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

            <div className="p-6">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/groups">
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour aux Groupes
                    </Link>
                </Button>
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
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
                            <Badge className="bg-orange-500">
                                Complet
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Code: {group.code}
                    </p>
                </div>
                    </div>

                    <div className="flex gap-2">
                {canManage && (
                    <>
                        <Button variant="outline" asChild>
                            <Link href={`/groups/${group.uuid}/edit`}>
                                <PencilIcon className="h-4 w-4 mr-2" />
                                Modifier
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => setIsToggleStatusModalOpen(true)}
                        >
                            {group.is_active ? 'Désactiver' : 'Activer'}
                        </Button>
                    </>
                )}
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Capacité</CardTitle>
                    <ChartBarIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">
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
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Membres</CardTitle>
                    <UserGroupIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">
                        {group.members_count}
                    </div>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        membres dans ce groupe
                    </p>
                </CardContent>
                    </Card>

                    <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Chef de Groupe</CardTitle>
                    <UserIcon className="h-5 w-5 text-gray-500" />
                </CardHeader>
                <CardContent>
                    {group.leader ? (
                        <div>
                            <div className="text-lg font-semibold text-gray-900 dark:text-white">
                                {group.leader.name}
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {group.leader.email}
                            </p>
                        </div>
                    ) : (
                        <div className="text-gray-400">Non assigné</div>
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

                {/* Members List */}
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
                                                href={`/profile/${user.id}`}
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
};
