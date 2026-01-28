import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    ChartBarIcon,
    ClockIcon,
    PresentationChartLineIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentCheckIcon,
    DocumentDuplicateIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    TrophyIcon,
    FireIcon,
    UserGroupIcon,
    UserIcon,
} from '@heroicons/react/24/outline';

interface TaskEvolutionPeriod {
    label: string;
    period: string;
    created: number;
    completed: number;
}

interface TaskByMember {
    uuid: string | null;
    name: string;
    total: number;
    completed: number;
    in_progress: number;
    pending: number;
    overdue: number;
    completion_rate: number;
}

interface IndividualPerformance {
    uuid: string;
    name: string;
    total_tasks: number;
    completed_tasks: number;
    overdue_tasks: number;
    completion_rate: number;
    overdue_rate: number;
    avg_completion_days: number;
    completed_this_month: number;
}

export interface DepartmentStatistics {
    members: { total: number; has_head: boolean };
    workflows: { total: number; active: number; draft: number; deprecated: number };
    forms: { total: number; published: number; draft: number; archived: number; total_submissions: number };
    needs: { total: number; by_status: Record<string, number>; by_priority: Record<string, number>; total_cost: number };
    documents: { total: number; total_size: number; formatted_size: string };
    scheduling: { total_shifts: number; upcoming_shifts: number; pending_absences: number; approved_absences: number; pending_swap_requests: number };
    todos: {
        total: number; completed: number; in_progress: number; pending: number; overdue: number;
        by_priority: { critical: number; high: number; medium: number; low: number };
    };
    task_evolution?: { weekly: TaskEvolutionPeriod[]; monthly: TaskEvolutionPeriod[]; quarterly: TaskEvolutionPeriod[]; semester: TaskEvolutionPeriod[] };
    tasks_by_member?: TaskByMember[];
    performance?: {
        collective: {
            total_tasks: number; completed_tasks: number; completion_rate: number;
            overdue_tasks: number; overdue_rate: number;
            velocity_this_month: number; velocity_last_month: number; velocity_change: number;
            avg_completion_days: number;
        };
        individual: IndividualPerformance[];
    };
}

interface Props {
    statistics?: DepartmentStatistics;
}

export default function DepartmentStatisticsOperational({ statistics }: Props) {
    const [evolutionPeriod, setEvolutionPeriod] = useState<'weekly' | 'monthly' | 'quarterly' | 'semester'>('weekly');

    return (
        <div className="space-y-6">
            {/* Collective Performance Overview */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Taux de Complétion</CardTitle>
                        <TrophyIcon className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-green-600">
                            {statistics?.performance?.collective.completion_rate ?? 0}%
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {statistics?.performance?.collective.completed_tasks ?? 0} / {statistics?.performance?.collective.total_tasks ?? 0} tâches
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Vélocité ce mois</CardTitle>
                        {(statistics?.performance?.collective.velocity_change ?? 0) >= 0 ? (
                            <ArrowTrendingUpIcon className="h-4 w-4 text-green-500" />
                        ) : (
                            <ArrowTrendingDownIcon className="h-4 w-4 text-red-500" />
                        )}
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {statistics?.performance?.collective.velocity_this_month ?? 0}
                        </div>
                        <p className={`text-xs ${(statistics?.performance?.collective.velocity_change ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {(statistics?.performance?.collective.velocity_change ?? 0) >= 0 ? '+' : ''}{statistics?.performance?.collective.velocity_change ?? 0}% vs mois dernier
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Tâches en retard</CardTitle>
                        <ExclamationTriangleIcon className="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-red-600">
                            {statistics?.todos.overdue ?? 0}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {statistics?.performance?.collective.overdue_rate ?? 0}% du total
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Temps moyen</CardTitle>
                        <ClockIcon className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {statistics?.performance?.collective.avg_completion_days ?? 0}j
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Durée moyenne de complétion
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Task Distribution by Status and Priority */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ChartBarIcon className="h-5 w-5" />
                            Répartition par Statut
                        </CardTitle>
                        <CardDescription>Distribution des tâches selon leur état</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="space-y-3">
                                {[
                                    { label: 'Terminées', value: statistics?.todos.completed ?? 0, color: 'bg-green-500', textColor: 'text-green-600' },
                                    { label: 'En cours', value: statistics?.todos.in_progress ?? 0, color: 'bg-blue-500', textColor: 'text-blue-600' },
                                    { label: 'En attente', value: statistics?.todos.pending ?? 0, color: 'bg-gray-400', textColor: 'text-gray-600' },
                                    { label: 'En retard', value: statistics?.todos.overdue ?? 0, color: 'bg-red-500', textColor: 'text-red-600' },
                                ].map((item) => {
                                    const total = statistics?.todos.total || 1;
                                    const percentage = Math.round((item.value / total) * 100);
                                    return (
                                        <div key={item.label} className="space-y-1">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">{item.label}</span>
                                                <span className={`font-medium ${item.textColor}`}>{item.value}</span>
                                            </div>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className={`${item.color} h-2 rounded-full transition-all`}
                                                    style={{ width: `${percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FireIcon className="h-5 w-5" />
                            Répartition par Priorité
                        </CardTitle>
                        <CardDescription>Distribution des tâches selon leur urgence</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="space-y-3">
                                {[
                                    { label: 'Critique', value: statistics?.todos.by_priority?.critical ?? 0, color: 'bg-red-600', textColor: 'text-red-600' },
                                    { label: 'Haute', value: statistics?.todos.by_priority?.high ?? 0, color: 'bg-orange-500', textColor: 'text-orange-600' },
                                    { label: 'Moyenne', value: statistics?.todos.by_priority?.medium ?? 0, color: 'bg-yellow-500', textColor: 'text-yellow-600' },
                                    { label: 'Basse', value: statistics?.todos.by_priority?.low ?? 0, color: 'bg-blue-400', textColor: 'text-blue-600' },
                                ].map((item) => {
                                    const total = statistics?.todos.total || 1;
                                    const percentage = Math.round((item.value / total) * 100);
                                    return (
                                        <div key={item.label} className="space-y-1">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">{item.label}</span>
                                                <span className={`font-medium ${item.textColor}`}>{item.value}</span>
                                            </div>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className={`${item.color} h-2 rounded-full transition-all`}
                                                    style={{ width: `${percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Task Evolution Over Time */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <PresentationChartLineIcon className="h-5 w-5" />
                                Évolution des Tâches
                            </CardTitle>
                            <CardDescription>Tâches créées et complétées au fil du temps</CardDescription>
                        </div>
                        <div className="flex gap-1">
                            {[
                                { value: 'weekly', label: 'Semaine' },
                                { value: 'monthly', label: 'Mois' },
                                { value: 'quarterly', label: 'Trimestre' },
                                { value: 'semester', label: 'Semestre' },
                            ].map((period) => (
                                <Button
                                    key={period.value}
                                    variant={evolutionPeriod === period.value ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setEvolutionPeriod(period.value as typeof evolutionPeriod)}
                                >
                                    {period.label}
                                </Button>
                            ))}
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        <div className="flex gap-4 text-sm">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 bg-blue-500 rounded" />
                                <span>Créées</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 bg-green-500 rounded" />
                                <span>Complétées</span>
                            </div>
                        </div>

                        <div className="flex items-end gap-2 h-48">
                            {(statistics?.task_evolution?.[evolutionPeriod] || []).map((period, index) => {
                                const maxValue = Math.max(
                                    ...(statistics?.task_evolution?.[evolutionPeriod] || []).flatMap(p => [p.created, p.completed]),
                                    1
                                );
                                const createdHeight = (period.created / maxValue) * 100;
                                const completedHeight = (period.completed / maxValue) * 100;

                                return (
                                    <div key={index} className="flex-1 flex flex-col items-center gap-1">
                                        <div className="flex-1 w-full flex items-end justify-center gap-1">
                                            <div
                                                className="w-1/3 bg-blue-500 rounded-t transition-all hover:bg-blue-600"
                                                style={{ height: `${createdHeight}%`, minHeight: period.created > 0 ? '8px' : '0' }}
                                                title={`Créées: ${period.created}`}
                                            />
                                            <div
                                                className="w-1/3 bg-green-500 rounded-t transition-all hover:bg-green-600"
                                                style={{ height: `${completedHeight}%`, minHeight: period.completed > 0 ? '8px' : '0' }}
                                                title={`Complétées: ${period.completed}`}
                                            />
                                        </div>
                                        <div className="text-xs text-muted-foreground text-center truncate w-full" title={period.period}>
                                            {period.label}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {(!statistics?.task_evolution?.[evolutionPeriod] || statistics.task_evolution[evolutionPeriod].length === 0) && (
                            <div className="h-48 flex items-center justify-center text-muted-foreground">
                                Aucune donnée disponible pour cette période
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Tasks by Member */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <UserGroupIcon className="h-5 w-5" />
                        Répartition par Membre
                    </CardTitle>
                    <CardDescription>Distribution des tâches entre les membres du département</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {(statistics?.tasks_by_member || []).length > 0 ? (
                            statistics?.tasks_by_member?.map((member) => {
                                const total = member.total || 1;
                                return (
                                    <div key={member.uuid || 'unassigned'} className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <UserIcon className="h-4 w-4 text-muted-foreground" />
                                                <span className="font-medium">{member.name}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm text-muted-foreground">{member.total} tâches</span>
                                                <Badge variant={member.completion_rate >= 75 ? 'default' : member.completion_rate >= 50 ? 'secondary' : 'destructive'}>
                                                    {member.completion_rate}%
                                                </Badge>
                                            </div>
                                        </div>
                                        <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 flex overflow-hidden">
                                            <div className="bg-green-500 h-3 transition-all" style={{ width: `${(member.completed / total) * 100}%` }} title={`Terminées: ${member.completed}`} />
                                            <div className="bg-blue-500 h-3 transition-all" style={{ width: `${(member.in_progress / total) * 100}%` }} title={`En cours: ${member.in_progress}`} />
                                            <div className="bg-gray-400 h-3 transition-all" style={{ width: `${(member.pending / total) * 100}%` }} title={`En attente: ${member.pending}`} />
                                            {member.overdue > 0 && (
                                                <div className="bg-red-500 h-3 transition-all" style={{ width: `${(member.overdue / total) * 100}%` }} title={`En retard: ${member.overdue}`} />
                                            )}
                                        </div>
                                    </div>
                                );
                            })
                        ) : (
                            <p className="text-center text-muted-foreground py-8">Aucune tâche assignée</p>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Individual Performance */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <TrophyIcon className="h-5 w-5" />
                        Performances Individuelles
                    </CardTitle>
                    <CardDescription>Métriques de performance par membre</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b">
                                    <th className="text-left py-3 px-2 text-sm font-medium text-muted-foreground">Membre</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">Tâches</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">Complétées</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">Taux</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">En retard</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">Temps moy.</th>
                                    <th className="text-center py-3 px-2 text-sm font-medium text-muted-foreground">Ce mois</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(statistics?.performance?.individual || []).map((member, index) => (
                                    <tr key={member.uuid} className={index % 2 === 0 ? 'bg-muted/50' : ''}>
                                        <td className="py-3 px-2">
                                            <div className="flex items-center gap-2">
                                                {index < 3 && member.total_tasks > 0 && (
                                                    <span className={`text-lg ${index === 0 ? 'text-yellow-500' : index === 1 ? 'text-gray-400' : 'text-amber-700'}`}>
                                                        {index === 0 ? '🥇' : index === 1 ? '🥈' : '🥉'}
                                                    </span>
                                                )}
                                                <span className="font-medium">{member.name}</span>
                                            </div>
                                        </td>
                                        <td className="text-center py-3 px-2">{member.total_tasks}</td>
                                        <td className="text-center py-3 px-2 text-green-600 font-medium">{member.completed_tasks}</td>
                                        <td className="text-center py-3 px-2">
                                            <Badge variant={member.completion_rate >= 75 ? 'default' : member.completion_rate >= 50 ? 'secondary' : 'destructive'}>
                                                {member.completion_rate}%
                                            </Badge>
                                        </td>
                                        <td className="text-center py-3 px-2">
                                            {member.overdue_tasks > 0 ? (
                                                <Badge variant="destructive">{member.overdue_tasks}</Badge>
                                            ) : (
                                                <span className="text-green-600">0</span>
                                            )}
                                        </td>
                                        <td className="text-center py-3 px-2 text-muted-foreground">
                                            {member.avg_completion_days > 0 ? `${member.avg_completion_days}j` : '-'}
                                        </td>
                                        <td className="text-center py-3 px-2">
                                            <Badge variant="outline">{member.completed_this_month}</Badge>
                                        </td>
                                    </tr>
                                ))}
                                {(!statistics?.performance?.individual || statistics.performance.individual.length === 0) && (
                                    <tr>
                                        <td colSpan={7} className="text-center py-8 text-muted-foreground">
                                            Aucune donnée de performance disponible
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {/* Other Stats - Scheduling, Needs, Forms */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ClockIcon className="h-5 w-5" />
                            Planning
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                <span className="text-sm">Shifts à venir</span>
                                <Badge className="bg-blue-500">{statistics?.scheduling.upcoming_shifts ?? 0}</Badge>
                            </div>
                            <div className="flex items-center justify-between p-2 bg-orange-50 dark:bg-orange-900/20 rounded">
                                <span className="text-sm">Absences en attente</span>
                                <Badge className="bg-orange-500">{statistics?.scheduling.pending_absences ?? 0}</Badge>
                            </div>
                            <div className="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                <span className="text-sm">Absences approuvées</span>
                                <Badge className="bg-green-500">{statistics?.scheduling.approved_absences ?? 0}</Badge>
                            </div>
                            <div className="flex items-center justify-between p-2 bg-purple-50 dark:bg-purple-900/20 rounded">
                                <span className="text-sm">Échanges en attente</span>
                                <Badge className="bg-purple-500">{statistics?.scheduling.pending_swap_requests ?? 0}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon className="h-5 w-5" />
                            Besoins
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {statistics?.needs.by_status && Object.entries(statistics.needs.by_status).length > 0 ? (
                                Object.entries(statistics.needs.by_status).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground capitalize">{status.replace('_', ' ')}</span>
                                        <Badge variant="secondary">{count}</Badge>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground text-center py-4">Aucun besoin</p>
                            )}
                            {statistics?.needs.total_cost !== undefined && statistics.needs.total_cost > 0 && (
                                <div className="pt-2 border-t">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm">Coût total</span>
                                        <span className="font-bold text-green-600">
                                            {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(statistics.needs.total_cost)}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <DocumentDuplicateIcon className="h-5 w-5" />
                            Formulaires & Workflows
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div>
                                <h4 className="text-xs font-medium mb-2 text-muted-foreground">Formulaires</h4>
                                <div className="grid grid-cols-3 gap-1 text-center">
                                    <div className="p-1 bg-green-50 dark:bg-green-900/20 rounded">
                                        <div className="text-sm font-bold text-green-600">{statistics?.forms.published ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Publiés</div>
                                    </div>
                                    <div className="p-1 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                                        <div className="text-sm font-bold text-yellow-600">{statistics?.forms.draft ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Brouillons</div>
                                    </div>
                                    <div className="p-1 bg-gray-50 dark:bg-gray-900/20 rounded">
                                        <div className="text-sm font-bold text-gray-600">{statistics?.forms.archived ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Archivés</div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 className="text-xs font-medium mb-2 text-muted-foreground">Workflows</h4>
                                <div className="grid grid-cols-3 gap-1 text-center">
                                    <div className="p-1 bg-green-50 dark:bg-green-900/20 rounded">
                                        <div className="text-sm font-bold text-green-600">{statistics?.workflows.active ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Actifs</div>
                                    </div>
                                    <div className="p-1 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                                        <div className="text-sm font-bold text-yellow-600">{statistics?.workflows.draft ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Brouillons</div>
                                    </div>
                                    <div className="p-1 bg-gray-50 dark:bg-gray-900/20 rounded">
                                        <div className="text-sm font-bold text-gray-600">{statistics?.workflows.deprecated ?? 0}</div>
                                        <div className="text-[10px] text-muted-foreground">Obsolètes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
