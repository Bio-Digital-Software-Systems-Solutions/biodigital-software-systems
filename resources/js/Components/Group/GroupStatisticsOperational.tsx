import React, { useState, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    BarChart, Bar, AreaChart, Area, LineChart, Line,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import {
    ChartBarIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentCheckIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';

interface PeriodData {
    label: string;
    period: string;
    [key: string]: string | number;
}

interface WeekData {
    week_number: number;
    label: string;
    start_date: string;
    end_date: string;
    days: PeriodData[];
}

interface YearGranularityData {
    weekly: WeekData[];
    monthly: PeriodData[];
    quarterly: PeriodData[];
    semester: PeriodData[];
    yearly: PeriodData[];
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

export interface GroupStatistics {
    members: { total: number; has_leader: boolean };
    todos: {
        total: number; completed: number; in_progress: number; pending: number; overdue: number;
        by_priority: { critical: number; high: number; medium: number; low: number };
    };
    performance: {
        collective: {
            total_tasks: number; completed_tasks: number; completion_rate: number;
            overdue_tasks: number; overdue_rate: number;
            velocity_this_month: number; velocity_last_month: number; velocity_change: number;
            avg_completion_days: number;
        };
    };
    member_distribution?: {
        by_status: { active_members: number; inactive_members: number; visitors: number };
        by_gender: { male: number; female: number; other: number; unknown: number };
        by_age: Record<string, number>;
    };
    tasks_by_member?: TaskByMember[];
    available_years?: number[];
    task_evolution?: Record<string, YearGranularityData>;
    member_growth?: Record<string, YearGranularityData>;
}

interface Props {
    statistics?: GroupStatistics;
}

type ChartType = 'bar' | 'area' | 'line';
type PeriodView = 'weekly' | 'monthly' | 'quarterly' | 'semester' | 'yearly';

const periodLabels: Record<PeriodView, string> = {
    weekly: 'Semaine',
    monthly: 'Mois',
    quarterly: 'Trimestre',
    semester: 'Semestre',
    yearly: 'Année',
};

interface SeriesConfig {
    dataKey: string;
    name: string;
    color: string;
}

function ChartControls({ label, years, selectedYear, setSelectedYear, periodView, setPeriodView, chartType, setChartType, weekIndex, setWeekIndex, totalWeeks, weekLabel }: {
    label: string;
    years: number[];
    selectedYear: number;
    setSelectedYear: (y: number) => void;
    periodView: PeriodView;
    setPeriodView: (v: PeriodView) => void;
    chartType: ChartType;
    setChartType: (v: ChartType) => void;
    weekIndex?: number;
    setWeekIndex?: (v: number) => void;
    totalWeeks?: number;
    weekLabel?: string;
}) {
    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <CardTitle className="text-base">{label}</CardTitle>
                <div className="flex items-center gap-2 flex-wrap">
                    {/* Chart type */}
                    <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-0.5">
                        {(['bar', 'area', 'line'] as ChartType[]).map(type => (
                            <button
                                key={type}
                                type="button"
                                onClick={() => setChartType(type)}
                                className={`px-2 py-1 rounded text-xs font-medium transition-colors ${
                                    chartType === type
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                }`}
                            >
                                {type === 'bar' ? 'Barres' : type === 'area' ? 'Aire' : 'Ligne'}
                            </button>
                        ))}
                    </div>
                    {/* Year selector */}
                    <Select value={String(selectedYear)} onValueChange={(v) => setSelectedYear(Number(v))}>
                        <SelectTrigger className="w-24 h-8 text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map(y => (
                                <SelectItem key={y} value={String(y)}>{y}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div className="flex items-center gap-2 flex-wrap">
                {/* Period view */}
                <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-0.5">
                    {(Object.keys(periodLabels) as PeriodView[]).map(pv => (
                        <button
                            key={pv}
                            type="button"
                            onClick={() => setPeriodView(pv)}
                            className={`px-2.5 py-1 rounded text-xs font-medium transition-colors ${
                                periodView === pv
                                    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            }`}
                        >
                            {periodLabels[pv]}
                        </button>
                    ))}
                </div>
                {/* Week navigator */}
                {periodView === 'weekly' && setWeekIndex && totalWeeks !== undefined && weekIndex !== undefined && (
                    <div className="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setWeekIndex(Math.max(0, weekIndex - 1))}
                            disabled={weekIndex <= 0}
                        >
                            <ChevronLeftIcon className="h-4 w-4" />
                        </Button>
                        <span className="text-xs text-gray-600 dark:text-gray-400 min-w-[120px] text-center font-medium">
                            {weekLabel || `Semaine ${weekIndex + 1}`}
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setWeekIndex(Math.min(totalWeeks - 1, weekIndex + 1))}
                            disabled={weekIndex >= totalWeeks - 1}
                        >
                            <ChevronRightIcon className="h-4 w-4" />
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}

function MultiChart<T extends { label: string }>({ data, series, chartType, height = 300 }: {
    data: T[];
    series: SeriesConfig[];
    chartType: ChartType;
    height?: number;
}) {
    if (data.length === 0) {
        return (
            <div className="flex items-center justify-center h-[200px] text-gray-400 dark:text-gray-500 text-sm">
                Aucune donnée pour cette période
            </div>
        );
    }

    const commonProps = { data };
    const axisElements = (
        <>
            <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
            <XAxis dataKey="label" tick={{ fontSize: 11 }} angle={data.length > 12 ? -45 : 0} textAnchor={data.length > 12 ? 'end' : 'middle'} height={data.length > 12 ? 60 : 30} />
            <YAxis tick={{ fontSize: 12 }} />
            <Tooltip />
            <Legend />
        </>
    );

    return (
        <ResponsiveContainer width="100%" height={height}>
            {chartType === 'bar' ? (
                <BarChart {...commonProps}>
                    {axisElements}
                    {series.map(s => (
                        <Bar key={s.dataKey} dataKey={s.dataKey} name={s.name} fill={s.color} radius={[4, 4, 0, 0]} />
                    ))}
                </BarChart>
            ) : chartType === 'area' ? (
                <AreaChart {...commonProps}>
                    {axisElements}
                    {series.map(s => (
                        <Area key={s.dataKey} type="monotone" dataKey={s.dataKey} name={s.name} stroke={s.color} fill={`${s.color}80`} />
                    ))}
                </AreaChart>
            ) : (
                <LineChart {...commonProps}>
                    {axisElements}
                    {series.map(s => (
                        <Line key={s.dataKey} type="monotone" dataKey={s.dataKey} name={s.name} stroke={s.color} strokeWidth={2} dot={{ r: 3 }} />
                    ))}
                </LineChart>
            )}
        </ResponsiveContainer>
    );
}

type DistributionView = 'donut' | 'stack' | 'bar';

interface DistributionItem {
    label: string;
    value: number;
    color: string;
}

function DistributionChart({ data, centerLabel, view }: {
    data: DistributionItem[];
    centerLabel: string;
    view: DistributionView;
}) {
    const [tooltip, setTooltip] = useState<{ x: number; y: number; content: React.ReactNode } | null>(null);
    const total = data.reduce((sum, d) => sum + d.value, 0);

    if (total === 0) {
        return (
            <div className="flex items-center justify-center h-[200px] text-gray-400 dark:text-gray-500 text-sm">
                Aucune donnée
            </div>
        );
    }

    const segments = data
        .filter(d => d.value > 0)
        .map(d => ({ ...d, percentage: Math.round((d.value / total) * 100) }));

    const legend = (
        <div className="flex flex-wrap justify-center gap-x-4 gap-y-1 mt-4">
            {segments.map((d) => (
                <div key={d.label} className="flex items-center gap-1.5 text-xs">
                    <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: d.color }} />
                    <span className="text-muted-foreground">{d.label}</span>
                    <span className="font-medium">{d.value}</span>
                    <span className="text-muted-foreground">({d.percentage}%)</span>
                </div>
            ))}
        </div>
    );

    if (view === 'stack') {
        return (
            <div className="flex flex-col items-center gap-3">
                <div className="text-center">
                    <span className="text-2xl font-bold text-gray-900 dark:text-white">{total}</span>
                    <span className="ml-1 text-xs text-muted-foreground">{centerLabel}</span>
                </div>
                <div className="w-full relative" style={{ height: 32 }}>
                    <div className="flex w-full h-full rounded-lg overflow-hidden">
                        {segments.map((seg) => (
                            <div
                                key={seg.label}
                                className="h-full relative cursor-pointer transition-opacity hover:opacity-80"
                                style={{ width: `${(seg.value / total) * 100}%`, backgroundColor: seg.color }}
                                onMouseMove={(e) => {
                                    const rect = e.currentTarget.getBoundingClientRect();
                                    setTooltip({
                                        x: e.clientX - rect.left,
                                        y: e.clientY - rect.top,
                                        content: (
                                            <div className="text-center">
                                                <div className="font-semibold">{seg.label}</div>
                                                <div>{seg.value} ({seg.percentage}%)</div>
                                            </div>
                                        ),
                                    });
                                }}
                                onMouseLeave={() => setTooltip(null)}
                            >
                                {seg.percentage >= 10 && (
                                    <span className="absolute inset-0 flex items-center justify-center text-white text-xs font-medium">
                                        {seg.percentage}%
                                    </span>
                                )}
                                {tooltip && (
                                    <div
                                        className="absolute z-50 pointer-events-none bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs px-2.5 py-1.5 rounded-md shadow-lg whitespace-nowrap"
                                        style={{
                                            left: tooltip.x,
                                            top: tooltip.y,
                                            transform: 'translate(-50%, -100%) translateY(-8px)',
                                        }}
                                    >
                                        {tooltip.content}
                                        <div className="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-100" />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
                {legend}
            </div>
        );
    }

    if (view === 'bar') {
        const maxValue = Math.max(...segments.map(s => s.value));
        return (
            <div className="flex flex-col items-center gap-3">
                <div className="text-center">
                    <span className="text-2xl font-bold text-gray-900 dark:text-white">{total}</span>
                    <span className="ml-1 text-xs text-muted-foreground">{centerLabel}</span>
                </div>
                <div className="w-full flex items-end justify-center gap-6" style={{ height: 160 }}>
                    {segments.map((seg) => (
                        <div key={seg.label} className="flex flex-col items-center gap-1 flex-1 max-w-[100px]">
                            <span className="text-xs font-medium text-gray-900 dark:text-white">{seg.value}</span>
                            <div
                                className="w-full rounded-t-md cursor-pointer transition-opacity hover:opacity-80 relative"
                                style={{
                                    height: `${maxValue > 0 ? (seg.value / maxValue) * 130 : 0}px`,
                                    backgroundColor: seg.color,
                                    minHeight: seg.value > 0 ? 8 : 0,
                                }}
                                onMouseMove={(e) => {
                                    const rect = e.currentTarget.getBoundingClientRect();
                                    setTooltip({
                                        x: e.clientX - rect.left,
                                        y: e.clientY - rect.top,
                                        content: (
                                            <div className="text-center">
                                                <div className="font-semibold">{seg.label}</div>
                                                <div>{seg.value} ({seg.percentage}%)</div>
                                            </div>
                                        ),
                                    });
                                }}
                                onMouseLeave={() => setTooltip(null)}
                            >
                                {tooltip && (
                                    <div
                                        className="absolute z-50 pointer-events-none bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs px-2.5 py-1.5 rounded-md shadow-lg whitespace-nowrap"
                                        style={{
                                            left: tooltip.x,
                                            top: tooltip.y,
                                            transform: 'translate(-50%, -100%) translateY(-8px)',
                                        }}
                                    >
                                        {tooltip.content}
                                        <div className="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-100" />
                                    </div>
                                )}
                            </div>
                            <span className="text-[10px] text-muted-foreground text-center leading-tight">{seg.label}</span>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    // Donut view (default)
    const size = 180;
    const cx = size / 2;
    const cy = size / 2;
    const outerR = size / 2;
    const innerR = size / 2 - 28;

    let cumulative = 0;
    const arcs = segments.map(d => {
        const startAngle = cumulative;
        const angle = (d.value / total) * 360;
        cumulative += angle;
        return { ...d, startAngle, angle };
    });

    const polarToCartesian = (angle: number, r: number) => {
        const rad = (angle - 90) * (Math.PI / 180);
        return { x: cx + r * Math.cos(rad), y: cy + r * Math.sin(rad) };
    };

    const createArcPath = (startAngle: number, endAngle: number) => {
        const angleDiff = endAngle - startAngle;
        if (angleDiff >= 359.99) {
            const top = polarToCartesian(0, outerR);
            const bottom = polarToCartesian(180, outerR);
            const topInner = polarToCartesian(0, innerR);
            const bottomInner = polarToCartesian(180, innerR);
            return `M ${top.x} ${top.y} A ${outerR} ${outerR} 0 1 1 ${bottom.x} ${bottom.y} A ${outerR} ${outerR} 0 1 1 ${top.x} ${top.y} L ${topInner.x} ${topInner.y} A ${innerR} ${innerR} 0 1 0 ${bottomInner.x} ${bottomInner.y} A ${innerR} ${innerR} 0 1 0 ${topInner.x} ${topInner.y} Z`;
        }
        const start1 = polarToCartesian(startAngle, outerR);
        const end1 = polarToCartesian(endAngle, outerR);
        const start2 = polarToCartesian(endAngle, innerR);
        const end2 = polarToCartesian(startAngle, innerR);
        const largeArc = angleDiff > 180 ? 1 : 0;
        return `M ${start1.x} ${start1.y} A ${outerR} ${outerR} 0 ${largeArc} 1 ${end1.x} ${end1.y} L ${start2.x} ${start2.y} A ${innerR} ${innerR} 0 ${largeArc} 0 ${end2.x} ${end2.y} Z`;
    };

    return (
        <div className="flex flex-col items-center">
            <div className="relative" style={{ width: size, height: size }}>
                <svg width={size} height={size} className="overflow-visible">
                    {arcs.map((seg, i) => (
                        <path
                            key={i}
                            d={createArcPath(seg.startAngle, seg.startAngle + seg.angle)}
                            fill={seg.color}
                            className="cursor-pointer transition-opacity hover:opacity-80"
                            onMouseMove={(e) => {
                                const rect = e.currentTarget.getBoundingClientRect();
                                setTooltip({
                                    x: e.clientX - rect.left,
                                    y: e.clientY - rect.top,
                                    content: (
                                        <div className="text-center">
                                            <div className="font-semibold">{seg.label}</div>
                                            <div>{seg.value} ({seg.percentage}%)</div>
                                        </div>
                                    ),
                                });
                            }}
                            onMouseLeave={() => setTooltip(null)}
                        />
                    ))}
                </svg>
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div className="text-center">
                        <span className="text-2xl font-bold text-gray-900 dark:text-white">{total}</span>
                        <span className="block text-xs text-muted-foreground">{centerLabel}</span>
                    </div>
                </div>
                {tooltip && (
                    <div
                        className="absolute z-50 pointer-events-none bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs px-2.5 py-1.5 rounded-md shadow-lg whitespace-nowrap"
                        style={{
                            left: tooltip.x,
                            top: tooltip.y,
                            transform: 'translate(-50%, -100%) translateY(-8px)',
                        }}
                    >
                        {tooltip.content}
                        <div className="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-100" />
                    </div>
                )}
            </div>
            {legend}
        </div>
    );
}

export default function GroupStatisticsOperational({ statistics }: Props) {
    const currentYear = new Date().getFullYear();
    const [evolutionYear, setEvolutionYear] = useState(currentYear);
    const [evolutionPeriod, setEvolutionPeriod] = useState<PeriodView>('monthly');
    const [evolutionChartType, setEvolutionChartType] = useState<ChartType>('bar');
    const [evolutionWeekIdx, setEvolutionWeekIdx] = useState(0);
    const [growthYear, setGrowthYear] = useState(currentYear);
    const [growthPeriod, setGrowthPeriod] = useState<PeriodView>('monthly');
    const [growthChartType, setGrowthChartType] = useState<ChartType>('area');
    const [growthWeekIdx, setGrowthWeekIdx] = useState(0);
    const [distributionView, setDistributionView] = useState<DistributionView>('donut');
    const [distributionCategory, setDistributionCategory] = useState<'status' | 'gender' | 'age'>('status');

    if (!statistics) {
        return (
            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                <ChartBarIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                <p>Aucune donnée statistique disponible</p>
            </div>
        );
    }

    const { performance, todos, tasks_by_member, task_evolution, member_growth, member_distribution, available_years } = statistics;
    const collective = performance?.collective;
    const years = available_years ?? [currentYear];

    // Task evolution data
    const evolutionYearData = task_evolution?.[evolutionYear];
    const evolutionWeeks = (evolutionYearData?.weekly ?? []) as WeekData[];
    const evolutionData = useMemo(() => {
        if (evolutionPeriod === 'weekly') {
            const week = evolutionWeeks[evolutionWeekIdx];
            return week?.days ?? [];
        }
        return (evolutionYearData?.[evolutionPeriod] ?? []) as PeriodData[];
    }, [evolutionYearData, evolutionPeriod, evolutionWeekIdx, evolutionWeeks]);

    const evolutionWeekLabel = useMemo(() => {
        const week = evolutionWeeks[evolutionWeekIdx];
        return week ? `${week.label} (${week.start_date} - ${week.end_date})` : '';
    }, [evolutionWeeks, evolutionWeekIdx]);

    // Member growth data
    const growthYearData = member_growth?.[growthYear];
    const growthWeeks = (growthYearData?.weekly ?? []) as WeekData[];
    const growthData = useMemo(() => {
        if (growthPeriod === 'weekly') {
            const week = growthWeeks[growthWeekIdx];
            return week?.days ?? [];
        }
        return (growthYearData?.[growthPeriod] ?? []) as PeriodData[];
    }, [growthYearData, growthPeriod, growthWeekIdx, growthWeeks]);

    const growthWeekLabel = useMemo(() => {
        const week = growthWeeks[growthWeekIdx];
        return week ? `${week.label} (${week.start_date} - ${week.end_date})` : '';
    }, [growthWeeks, growthWeekIdx]);

    const hasEvolutionData = task_evolution && Object.keys(task_evolution).length > 0;
    const hasGrowthData = member_growth && Object.keys(member_growth).length > 0;

    // Reset week index when year changes
    const handleEvolutionYearChange = (y: number) => { setEvolutionYear(y); setEvolutionWeekIdx(0); };
    const handleGrowthYearChange = (y: number) => { setGrowthYear(y); setGrowthWeekIdx(0); };

    return (
        <div className="space-y-6">
            {/* Collective Performance Overview */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Taux de complétion</CardTitle>
                        <ClipboardDocumentCheckIcon className="h-5 w-5 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">
                            {collective?.completion_rate ?? 0}%
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {collective?.completed_tasks ?? 0} / {collective?.total_tasks ?? 0} tâches
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Vélocité</CardTitle>
                        {(collective?.velocity_change ?? 0) >= 0
                            ? <ArrowTrendingUpIcon className="h-5 w-5 text-green-500" />
                            : <ArrowTrendingDownIcon className="h-5 w-5 text-red-500" />
                        }
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">
                            {collective?.velocity_this_month ?? 0} <span className="text-sm font-normal">tâches/mois</span>
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {(collective?.velocity_change ?? 0) >= 0 ? '↑' : '↓'} {Math.abs(collective?.velocity_change ?? 0)}% vs mois dernier ({collective?.velocity_last_month ?? 0})
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">En retard</CardTitle>
                        <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">
                            {collective?.overdue_tasks ?? 0}
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {collective?.overdue_rate ?? 0}% du total
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Temps moyen</CardTitle>
                        <ClockIcon className="h-5 w-5 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">
                            {collective?.avg_completion_days ?? 0} <span className="text-sm font-normal">jours</span>
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            durée moyenne de complétion
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Task Distribution by Status */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Distribution par statut</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {[
                                { label: 'Terminé', value: todos.completed, color: 'bg-green-500', total: todos.total },
                                { label: 'En cours', value: todos.in_progress, color: 'bg-blue-500', total: todos.total },
                                { label: 'En attente', value: todos.pending, color: 'bg-yellow-500', total: todos.total },
                                { label: 'En retard', value: todos.overdue, color: 'bg-red-500', total: todos.total },
                            ].map(item => (
                                <div key={item.label}>
                                    <div className="flex items-center justify-between text-sm mb-1">
                                        <span className="text-gray-600 dark:text-gray-400">{item.label}</span>
                                        <span className="font-medium text-gray-900 dark:text-white">{item.value}</span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className={`${item.color} h-2 rounded-full transition-all`}
                                            style={{ width: `${item.total > 0 ? (item.value / item.total) * 100 : 0}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Distribution par priorité</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {[
                                { label: 'Critique', value: todos.by_priority.critical, color: 'bg-red-500' },
                                { label: 'Haute', value: todos.by_priority.high, color: 'bg-orange-500' },
                                { label: 'Moyenne', value: todos.by_priority.medium, color: 'bg-yellow-500' },
                                { label: 'Basse', value: todos.by_priority.low, color: 'bg-green-500' },
                            ].map(item => (
                                <div key={item.label}>
                                    <div className="flex items-center justify-between text-sm mb-1">
                                        <span className="text-gray-600 dark:text-gray-400">{item.label}</span>
                                        <span className="font-medium text-gray-900 dark:text-white">{item.value}</span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className={`${item.color} h-2 rounded-full transition-all`}
                                            style={{ width: `${todos.total > 0 ? (item.value / todos.total) * 100 : 0}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Task Evolution Chart */}
            {hasEvolutionData && (
                <Card>
                    <CardHeader>
                        <ChartControls
                            label="Évolution des tâches"
                            years={years}
                            selectedYear={evolutionYear}
                            setSelectedYear={handleEvolutionYearChange}
                            periodView={evolutionPeriod}
                            setPeriodView={setEvolutionPeriod}
                            chartType={evolutionChartType}
                            setChartType={setEvolutionChartType}
                            weekIndex={evolutionWeekIdx}
                            setWeekIndex={setEvolutionWeekIdx}
                            totalWeeks={evolutionWeeks.length}
                            weekLabel={evolutionWeekLabel}
                        />
                    </CardHeader>
                    <CardContent>
                        <MultiChart
                            data={evolutionData}
                            series={[
                                { dataKey: 'created', name: 'Créées', color: '#3B82F6' },
                                { dataKey: 'completed', name: 'Terminées', color: '#10B981' },
                            ]}
                            chartType={evolutionChartType}
                            height={300}
                        />
                    </CardContent>
                </Card>
            )}

            {/* Member Distribution */}
            {member_distribution && (
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-3">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <CardTitle className="text-base">Répartition des personnes</CardTitle>
                                <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-0.5">
                                    {([
                                        { key: 'donut' as DistributionView, label: 'Donut' },
                                        { key: 'stack' as DistributionView, label: 'Stack' },
                                        { key: 'bar' as DistributionView, label: 'Barres' },
                                    ]).map(({ key, label }) => (
                                        <button
                                            key={key}
                                            type="button"
                                            onClick={() => setDistributionView(key)}
                                            className={`px-2 py-1 rounded text-xs font-medium transition-colors ${
                                                distributionView === key
                                                    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                            }`}
                                        >
                                            {label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-0.5 self-start">
                                {([
                                    { key: 'status' as const, label: 'Statut' },
                                    { key: 'age' as const, label: 'Âge' },
                                    { key: 'gender' as const, label: 'Sexe' },
                                ]).map(({ key, label }) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => setDistributionCategory(key)}
                                        className={`px-2.5 py-1 rounded text-xs font-medium transition-colors ${
                                            distributionCategory === key
                                                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {distributionCategory === 'status' && (
                            <DistributionChart
                                data={[
                                    { label: 'Membres actifs', value: member_distribution.by_status.active_members, color: '#10B981' },
                                    { label: 'Membres inactifs', value: member_distribution.by_status.inactive_members, color: '#EF4444' },
                                    { label: 'Visiteurs', value: member_distribution.by_status.visitors, color: '#F59E0B' },
                                ]}
                                centerLabel="personnes"
                                view={distributionView}
                            />
                        )}
                        {distributionCategory === 'gender' && (
                            <DistributionChart
                                data={[
                                    { label: 'Hommes', value: member_distribution.by_gender.male, color: '#3B82F6' },
                                    { label: 'Femmes', value: member_distribution.by_gender.female, color: '#EC4899' },
                                    { label: 'Autre', value: member_distribution.by_gender.other, color: '#8B5CF6' },
                                    { label: 'Non renseigné', value: member_distribution.by_gender.unknown, color: '#9CA3AF' },
                                ]}
                                centerLabel="personnes"
                                view={distributionView}
                            />
                        )}
                        {distributionCategory === 'age' && (
                            <DistributionChart
                                data={(() => {
                                    const ageColors: Record<string, string> = {
                                        '0-17': '#06B6D4', '18-25': '#3B82F6', '26-35': '#8B5CF6',
                                        '36-45': '#EC4899', '46-55': '#F59E0B', '56-65': '#F97316',
                                        '65+': '#EF4444', 'unknown': '#9CA3AF',
                                    };
                                    const ageLabels: Record<string, string> = {
                                        '0-17': '0-17 ans', '18-25': '18-25 ans', '26-35': '26-35 ans',
                                        '36-45': '36-45 ans', '46-55': '46-55 ans', '56-65': '56-65 ans',
                                        '65+': '65+ ans', 'unknown': 'Non renseigné',
                                    };
                                    return Object.entries(member_distribution.by_age).map(([key, value]) => ({
                                        label: ageLabels[key] ?? key,
                                        value,
                                        color: ageColors[key] ?? '#9CA3AF',
                                    }));
                                })()}
                                centerLabel="personnes"
                                view={distributionView}
                            />
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Member Growth Chart */}
            {hasGrowthData && (
                <Card>
                    <CardHeader>
                        <ChartControls
                            label="Croissance des membres"
                            years={years}
                            selectedYear={growthYear}
                            setSelectedYear={handleGrowthYearChange}
                            periodView={growthPeriod}
                            setPeriodView={setGrowthPeriod}
                            chartType={growthChartType}
                            setChartType={setGrowthChartType}
                            weekIndex={growthWeekIdx}
                            setWeekIndex={setGrowthWeekIdx}
                            totalWeeks={growthWeeks.length}
                            weekLabel={growthWeekLabel}
                        />
                    </CardHeader>
                    <CardContent>
                        <MultiChart
                            data={growthData}
                            series={[
                                { dataKey: 'total_members', name: 'Total membres', color: '#8B5CF6' },
                                { dataKey: 'new_members', name: 'Nouveaux membres', color: '#10B981' },
                                { dataKey: 'total_visitors', name: 'Total visiteurs', color: '#F59E0B' },
                                { dataKey: 'new_visitors', name: 'Nouveaux visiteurs', color: '#3B82F6' },
                            ]}
                            chartType={growthChartType}
                            height={250}
                        />
                    </CardContent>
                </Card>
            )}

            {/* Performance by Member */}
            {tasks_by_member && tasks_by_member.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Performance par membre</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-gray-700">
                                        <th className="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Membre</th>
                                        <th className="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Total</th>
                                        <th className="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Terminé</th>
                                        <th className="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">En cours</th>
                                        <th className="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">En retard</th>
                                        <th className="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Taux</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tasks_by_member.map((member, idx) => (
                                        <tr key={idx} className="border-b border-gray-100 dark:border-gray-800">
                                            <td className="py-2 px-3 font-medium text-gray-900 dark:text-white">{member.name}</td>
                                            <td className="text-center py-2 px-3">{member.total}</td>
                                            <td className="text-center py-2 px-3 text-green-600">{member.completed}</td>
                                            <td className="text-center py-2 px-3 text-blue-600">{member.in_progress}</td>
                                            <td className="text-center py-2 px-3 text-red-600">{member.overdue}</td>
                                            <td className="text-center py-2 px-3">
                                                <Badge variant={member.completion_rate >= 70 ? 'default' : 'secondary'}>
                                                    {member.completion_rate}%
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
