import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    UserPlusIcon,
    MagnifyingGlassIcon,
    TrashIcon,
    ClipboardDocumentCheckIcon,
} from '@heroicons/react/24/outline';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import AddVisitorDialog from './AddVisitorDialog';
import RecordAttendanceDialog from './RecordAttendanceDialog';
import IntegrationSuggestionBanner from './IntegrationSuggestionBanner';
import type { VisitorVisit } from '@/Types/visitor';

interface Props {
    groupUuid: string;
    canManage: boolean;
    pendingSuggestionsCount: number;
    meetings: Array<{
        uuid: string;
        appointment: { title: string; id: number } | null;
    }>;
    activities: Array<{
        uuid: string;
        title: string;
        id?: number;
    }>;
    groupMembers: Array<{ id: number; uuid: string; name: string }>;
}

const statusLabels: Record<string, string> = {
    visiting: 'Nouveau',
    progressing: 'En cours',
    ready: 'Prêt',
    integrated: 'Intégré',
};

const statusColors: Record<string, string> = {
    visiting: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    progressing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    ready: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    integrated: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
};

const sourceLabels: Record<string, string> = {
    friend: 'Ami',
    online: 'En ligne',
    event: 'Événement',
    walk_in: 'Visite spontanée',
    other: 'Autre',
};

function getScoreColor(score: number): string {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    if (score >= 30) return 'bg-orange-500';
    return 'bg-red-500';
}

export default function GroupVisitorsTab({
    groupUuid,
    canManage,
    pendingSuggestionsCount,
    meetings,
    activities,
    groupMembers,
}: Props) {
    const [visitors, setVisitors] = useState<VisitorVisit[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [showAttendanceDialog, setShowAttendanceDialog] = useState(false);
    const { confirm } = useConfirm();

    const fetchVisitors = async () => {
        try {
            const response = await axios.get(`/groups/${groupUuid}/visitors`);
            setVisitors(response.data.visitors);
        } catch {
            toast.error('Erreur lors du chargement des visiteurs.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchVisitors();
    }, [groupUuid]);

    const filteredVisitors = visitors.filter((v) => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            v.visitor.first_name.toLowerCase().includes(searchLower) ||
            v.visitor.last_name.toLowerCase().includes(searchLower) ||
            (v.visitor.email && v.visitor.email.toLowerCase().includes(searchLower))
        );
    });

    const handleRemoveVisitor = async (visitorUuid: string) => {
        const confirmed = await confirm({
            title: 'Retirer le visiteur',
            message: 'Êtes-vous sûr de vouloir retirer ce visiteur du groupe ? Son historique de présence sera supprimé.',
            confirmText: 'Retirer',
            cancelText: 'Annuler',
            type: 'warning',
        });

        if (!confirmed) return;

        try {
            await axios.delete(`/groups/${groupUuid}/visitors/${visitorUuid}`);
            toast.success('Visiteur retiré du groupe.');
            fetchVisitors();
        } catch {
            toast.error('Erreur lors de la suppression.');
        }
    };

    const stats = {
        total: visitors.length,
        visiting: visitors.filter((v) => v.integration_status === 'visiting').length,
        progressing: visitors.filter((v) => v.integration_status === 'progressing').length,
        ready: visitors.filter((v) => v.integration_status === 'ready').length,
        integrated: visitors.filter((v) => v.integration_status === 'integrated').length,
    };

    if (loading) {
        return (
            <div className="space-y-4">
                {[...Array(3)].map((_, i) => (
                    <div key={i} className="h-20 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-4 sm:space-y-6">
            {pendingSuggestionsCount > 0 && canManage && (
                <IntegrationSuggestionBanner
                    groupUuid={groupUuid}
                    count={pendingSuggestionsCount}
                    onResponded={fetchVisitors}
                />
            )}

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                <Card>
                    <CardContent className="p-3 text-center">
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">{stats.total}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">Total</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 text-center">
                        <div className="text-2xl font-bold text-blue-600">{stats.visiting}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">Nouveaux</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 text-center">
                        <div className="text-2xl font-bold text-yellow-600">{stats.progressing}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">En cours</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 text-center">
                        <div className="text-2xl font-bold text-green-600">{stats.ready}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">Prêts</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 text-center">
                        <div className="text-2xl font-bold text-purple-600">{stats.integrated}</div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">Intégrés</div>
                    </CardContent>
                </Card>
            </div>

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div className="relative flex-1 max-w-sm">
                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                        placeholder="Rechercher un visiteur..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-9"
                    />
                </div>
                {canManage && (
                    <div className="flex gap-2">
                        <Button size="sm" variant="outline" onClick={() => setShowAttendanceDialog(true)}>
                            <ClipboardDocumentCheckIcon className="h-4 w-4 mr-1" />
                            <span className="hidden sm:inline">Prendre les présences</span>
                        </Button>
                        <Button size="sm" onClick={() => setShowAddDialog(true)}>
                            <UserPlusIcon className="h-4 w-4 mr-1" />
                            <span className="hidden sm:inline">Ajouter un visiteur</span>
                        </Button>
                    </div>
                )}
            </div>

            {/* Visitors List */}
            {filteredVisitors.length === 0 ? (
                <Card>
                    <CardContent className="py-12 text-center">
                        <p className="text-gray-500 dark:text-gray-400">
                            {visitors.length === 0
                                ? 'Aucun visiteur dans ce groupe.'
                                : 'Aucun visiteur trouvé pour cette recherche.'}
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-3">
                    {filteredVisitors.map((visit) => (
                        <Card key={visit.uuid} className="hover:shadow-md transition-shadow">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div className="flex items-center gap-3 min-w-0 flex-1">
                                        <div className="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center shrink-0">
                                            <span className="text-sm font-semibold text-purple-700 dark:text-purple-300">
                                                {visit.visitor.first_name[0]}{visit.visitor.last_name[0]}
                                            </span>
                                        </div>
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h4 className="font-medium text-gray-900 dark:text-white truncate">
                                                    {visit.visitor.name}
                                                </h4>
                                                <Badge className={statusColors[visit.integration_status] || ''} variant="secondary">
                                                    {statusLabels[visit.integration_status] || visit.integration_status}
                                                </Badge>
                                                {visit.has_pending_suggestion && (
                                                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" variant="secondary">
                                                        Suggestion
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {visit.visitor.email && <span>{visit.visitor.email}</span>}
                                                <span>Depuis le {new Date(visit.first_visited_at).toLocaleDateString('fr-FR')}</span>
                                                {visit.visitor.source && (
                                                    <span>({sourceLabels[visit.visitor.source] || visit.visitor.source})</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 shrink-0">
                                        {/* Attendance count */}
                                        <div className="text-center hidden md:block">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                {visit.present_count}/{visit.attendance_count}
                                            </div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400">Présences</div>
                                        </div>

                                        {/* Integration Score */}
                                        <div className="w-24 hidden sm:block">
                                            <div className="flex items-center justify-between text-xs mb-1">
                                                <span className="text-gray-500 dark:text-gray-400">Score</span>
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {Math.round(visit.integration_score)}%
                                                </span>
                                            </div>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className={`h-2 rounded-full transition-all ${getScoreColor(visit.integration_score)}`}
                                                    style={{ width: `${Math.min(100, visit.integration_score)}%` }}
                                                />
                                            </div>
                                        </div>

                                        {canManage && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                                                onClick={() => handleRemoveVisitor(visit.visitor.uuid)}
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {/* Mobile score */}
                                <div className="sm:hidden mt-3">
                                    <div className="flex items-center justify-between text-xs mb-1">
                                        <span className="text-gray-500 dark:text-gray-400">
                                            Score d'intégration: {Math.round(visit.integration_score)}%
                                        </span>
                                        <span className="text-gray-500 dark:text-gray-400">
                                            {visit.present_count}/{visit.attendance_count} présences
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className={`h-2 rounded-full transition-all ${getScoreColor(visit.integration_score)}`}
                                            style={{ width: `${Math.min(100, visit.integration_score)}%` }}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {showAddDialog && (
                <AddVisitorDialog
                    groupUuid={groupUuid}
                    groupMembers={groupMembers}
                    onClose={() => setShowAddDialog(false)}
                    onAdded={() => {
                        setShowAddDialog(false);
                        fetchVisitors();
                    }}
                />
            )}

            {showAttendanceDialog && (
                <RecordAttendanceDialog
                    groupUuid={groupUuid}
                    visitors={visitors}
                    meetings={meetings}
                    activities={activities}
                    onClose={() => setShowAttendanceDialog(false)}
                    onRecorded={() => {
                        setShowAttendanceDialog(false);
                        fetchVisitors();
                    }}
                />
            )}
        </div>
    );
}
