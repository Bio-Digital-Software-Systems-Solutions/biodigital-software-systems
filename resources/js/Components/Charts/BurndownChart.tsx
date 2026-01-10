import React, { useState, useEffect } from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
    ReferenceLine,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowTrendingDownIcon,
    ArrowTrendingUpIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

interface ChartDataPoint {
    date: string;
    dayNumber: number;
    formattedDate: string;
    ideal: number;
    actual: number | null;
    completed: number | null;
    totalScope: number;
}

interface BurndownSummary {
    totalStoryPoints: number;
    completedPoints: number;
    remainingPoints: number;
    progressPercentage: number;
    velocity: number;
    daysElapsed: number;
    totalDays: number;
    estimatedCompletionDate: string | null;
    isOnTrack: boolean;
}

interface BurndownData {
    chartData: ChartDataPoint[];
    summary: BurndownSummary;
    sprint: {
        id: number;
        uuid: string;
        name: string;
        startDate: string;
        endDate: string;
    };
}

interface Props {
    sprintUuid: string;
    chartType?: 'burndown' | 'burnup' | 'both';
}

export default function BurndownChart({ sprintUuid, chartType = 'both' }: Props) {
    const [data, setData] = useState<BurndownData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [activeChart, setActiveChart] = useState<'burndown' | 'burnup'>(chartType === 'burnup' ? 'burnup' : 'burndown');

    useEffect(() => {
        if (sprintUuid) {
            fetchBurndownData();
        }
    }, [sprintUuid]);

    const fetchBurndownData = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await fetch(`/api/sprints/${sprintUuid}/burndown`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch burndown data');
            }

            const result = await response.json();
            if (result.success) {
                setData(result.data);
            } else {
                throw new Error('Invalid response format');
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'An error occurred');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <ExclamationTriangleIcon className="h-8 w-8 mx-auto mb-2 text-yellow-500" />
                <p>{error || 'Aucune donnée disponible'}</p>
            </div>
        );
    }

    const { chartData, summary } = data;

    // Custom tooltip component
    const CustomTooltip = ({ active, payload, label }: any) => {
        if (active && payload && payload.length) {
            const dataPoint = payload[0]?.payload as ChartDataPoint;
            return (
                <div className="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                    <p className="font-semibold text-gray-900 dark:text-white mb-2">
                        Jour {dataPoint.dayNumber} - {dataPoint.formattedDate}
                    </p>
                    {activeChart === 'burndown' ? (
                        <>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                <span className="inline-block w-3 h-3 rounded-full bg-gray-400 mr-2" />
                                Idéal: <span className="font-medium">{dataPoint.ideal} pts</span>
                            </p>
                            {dataPoint.actual !== null && (
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    <span className="inline-block w-3 h-3 rounded-full bg-primary mr-2" />
                                    Restant: <span className="font-medium">{dataPoint.actual} pts</span>
                                </p>
                            )}
                        </>
                    ) : (
                        <>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                <span className="inline-block w-3 h-3 rounded-full bg-gray-400 mr-2" />
                                Objectif: <span className="font-medium">{dataPoint.totalScope} pts</span>
                            </p>
                            {dataPoint.completed !== null && (
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    <span className="inline-block w-3 h-3 rounded-full bg-green-500 mr-2" />
                                    Complété: <span className="font-medium">{dataPoint.completed} pts</span>
                                </p>
                            )}
                        </>
                    )}
                </div>
            );
        }
        return null;
    };

    return (
        <div className="space-y-4">
            {/* Chart Type Toggle */}
            {chartType === 'both' && (
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setActiveChart('burndown')}
                        className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                            activeChart === 'burndown'
                                ? 'bg-primary text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                        }`}
                    >
                        <ArrowTrendingDownIcon className="h-4 w-4" />
                        Burn-down
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveChart('burnup')}
                        className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                            activeChart === 'burnup'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                        }`}
                    >
                        <ArrowTrendingUpIcon className="h-4 w-4" />
                        Burn-up
                    </button>
                </div>
            )}

            {/* Summary Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 dark:text-gray-400">Total Points</p>
                    <p className="text-xl font-bold text-gray-900 dark:text-white">
                        {summary.totalStoryPoints}
                    </p>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 dark:text-gray-400">Complétés</p>
                    <p className="text-xl font-bold text-green-600">
                        {summary.completedPoints}
                    </p>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 dark:text-gray-400">Restants</p>
                    <p className="text-xl font-bold text-orange-600">
                        {summary.remainingPoints}
                    </p>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 dark:text-gray-400">Vélocité</p>
                    <p className="text-xl font-bold text-primary">
                        {summary.velocity} pts/j
                    </p>
                </div>
            </div>

            {/* Status Badge */}
            <div className="flex items-center gap-2">
                {summary.isOnTrack ? (
                    <Badge className="bg-green-500 text-white">
                        <CheckCircleIcon className="h-3 w-3 mr-1" />
                        En bonne voie
                    </Badge>
                ) : (
                    <Badge className="bg-yellow-500 text-white">
                        <ExclamationTriangleIcon className="h-3 w-3 mr-1" />
                        Retard potentiel
                    </Badge>
                )}
                <span className="text-sm text-gray-500 dark:text-gray-400">
                    Jour {summary.daysElapsed} sur {summary.totalDays}
                </span>
                {summary.estimatedCompletionDate && !summary.isOnTrack && (
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                        · Fin estimée: {new Date(summary.estimatedCompletionDate).toLocaleDateString('fr-FR')}
                    </span>
                )}
            </div>

            {/* Chart */}
            <div className="h-80">
                <ResponsiveContainer width="100%" height="100%">
                    {activeChart === 'burndown' ? (
                        <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                            <XAxis
                                dataKey="formattedDate"
                                tick={{ fontSize: 12 }}
                                className="text-gray-600 dark:text-gray-400"
                            />
                            <YAxis
                                tick={{ fontSize: 12 }}
                                className="text-gray-600 dark:text-gray-400"
                                domain={[0, 'auto']}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend />
                            <ReferenceLine y={0} stroke="#9ca3af" strokeDasharray="3 3" />
                            <Line
                                type="monotone"
                                dataKey="ideal"
                                name="Idéal"
                                stroke="#9ca3af"
                                strokeDasharray="5 5"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="actual"
                                name="Restant"
                                stroke="#6366f1"
                                strokeWidth={3}
                                dot={{ fill: '#6366f1', strokeWidth: 2, r: 4 }}
                                activeDot={{ r: 6, fill: '#6366f1' }}
                                connectNulls={false}
                            />
                        </LineChart>
                    ) : (
                        <LineChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                            <XAxis
                                dataKey="formattedDate"
                                tick={{ fontSize: 12 }}
                                className="text-gray-600 dark:text-gray-400"
                            />
                            <YAxis
                                tick={{ fontSize: 12 }}
                                className="text-gray-600 dark:text-gray-400"
                                domain={[0, 'auto']}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend />
                            <ReferenceLine
                                y={summary.totalStoryPoints}
                                stroke="#9ca3af"
                                strokeDasharray="3 3"
                                label={{ value: 'Objectif', position: 'right', fill: '#9ca3af' }}
                            />
                            <Line
                                type="monotone"
                                dataKey="totalScope"
                                name="Objectif"
                                stroke="#9ca3af"
                                strokeDasharray="5 5"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="completed"
                                name="Complété"
                                stroke="#22c55e"
                                strokeWidth={3}
                                dot={{ fill: '#22c55e', strokeWidth: 2, r: 4 }}
                                activeDot={{ r: 6, fill: '#22c55e' }}
                                connectNulls={false}
                            />
                        </LineChart>
                    )}
                </ResponsiveContainer>
            </div>

            {/* Legend explanation */}
            <div className="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                {activeChart === 'burndown' ? (
                    <>
                        <p>
                            <span className="inline-block w-8 h-0.5 bg-gray-400 mr-2 align-middle" style={{ borderTop: '2px dashed' }} />
                            <strong>Ligne idéale:</strong> Progression théorique si le travail est distribué uniformément.
                        </p>
                        <p>
                            <span className="inline-block w-8 h-0.5 bg-primary mr-2 align-middle" />
                            <strong>Ligne réelle:</strong> Points restants basés sur les tâches complétées.
                        </p>
                    </>
                ) : (
                    <>
                        <p>
                            <span className="inline-block w-8 h-0.5 bg-gray-400 mr-2 align-middle" style={{ borderTop: '2px dashed' }} />
                            <strong>Objectif:</strong> Total des points à atteindre.
                        </p>
                        <p>
                            <span className="inline-block w-8 h-0.5 bg-green-500 mr-2 align-middle" />
                            <strong>Complété:</strong> Points accumulés au fil du temps.
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}
