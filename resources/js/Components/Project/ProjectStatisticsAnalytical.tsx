import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    ClipboardDocumentCheckIcon,
    ChartPieIcon,
    ClockIcon,
    FlagIcon,
    UserGroupIcon,
    RocketLaunchIcon,
} from '@heroicons/react/24/outline';

export interface ProjectAnalyticsData {
    projects_by_status?: ChartDataItem[];
    tasks_by_status: ChartDataItem[];
    tasks_by_priority: ChartDataItem[];
    sprints_by_status?: ChartDataItem[];
    epics_by_status?: ChartDataItem[];
    task_evolution: MultiPeriodEvolution;
    completion_by_project?: CompletionDataItem[];
    completion_by_assignee?: CompletionDataItem[];
    projects_by_member?: ChartDataItem[];
    tasks_by_member?: ChartDataItem[];
    global_progress?: GlobalProgressData;
    velocity?: VelocityData;
}

interface GlobalProgressData {
    percentage: number;
    completed: number;
    total: number;
}

interface VelocityPeriodData {
    value: number;
    total: number;
    period_count: number;
    max: number;
    label: string;
}

interface VelocityData {
    daily: VelocityPeriodData;
    weekly: VelocityPeriodData;
    monthly: VelocityPeriodData;
}

interface MultiPeriodEvolution {
    weekly: EvolutionDataItem[];
    monthly: EvolutionDataItem[];
    quarterly: EvolutionDataItem[];
    semester: EvolutionDataItem[];
    yearly: EvolutionDataItem[];
}

interface ChartDataItem {
    label: string;
    value: number;
    color: string;
}

interface EvolutionDataItem {
    label: string;
    created: number;
    completed: number;
}

interface CompletionDataItem {
    name: string;
    value: number;
    color: string;
    completed?: number;
    total?: number;
}

interface TooltipData {
    x: number;
    y: number;
    content: React.ReactNode;
}

function ChartTooltip({ tooltip }: { tooltip: TooltipData | null }) {
    if (!tooltip) return null;
    return (
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
    );
}

function GlobalProgressChart({ data, title }: {
    data: GlobalProgressData;
    title: string;
}) {
    const { percentage, completed, total } = data;
    const size = 160;
    const strokeWidth = 12;
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (percentage / 100) * circumference;

    // Gradient colors for the progress ring
    const gradientId = `progress-gradient-${Math.random().toString(36).substr(2, 9)}`;

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-base text-center">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col items-center justify-center">
                    <div className="relative" style={{ width: size, height: size }}>
                        <svg width={size} height={size} className="transform -rotate-90">
                            <defs>
                                <linearGradient id={gradientId} x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stopColor="#3B82F6" />
                                    <stop offset="100%" stopColor="#8B5CF6" />
                                </linearGradient>
                            </defs>
                            {/* Background circle */}
                            <circle
                                cx={size / 2}
                                cy={size / 2}
                                r={radius}
                                fill="none"
                                stroke="currentColor"
                                strokeWidth={strokeWidth}
                                className="text-gray-200 dark:text-gray-700"
                            />
                            {/* Progress circle */}
                            <circle
                                cx={size / 2}
                                cy={size / 2}
                                r={radius}
                                fill="none"
                                stroke={`url(#${gradientId})`}
                                strokeWidth={strokeWidth}
                                strokeLinecap="round"
                                strokeDasharray={circumference}
                                strokeDashoffset={strokeDashoffset}
                                className="transition-all duration-500 ease-out"
                            />
                        </svg>
                        {/* Center text */}
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-purple-500">
                                {percentage}%
                            </span>
                            <span className="text-xs text-muted-foreground">Complété</span>
                        </div>
                    </div>
                    <p className="text-sm text-muted-foreground mt-3">
                        {completed} sur {total} tâches terminées
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

type VelocityPeriod = 'daily' | 'weekly' | 'monthly';

const velocityPeriodLabels: Record<VelocityPeriod, string> = {
    daily: 'Jour',
    weekly: 'Semaine',
    monthly: 'Mois',
};

function VelocityGauge({ data, title }: {
    data: VelocityData;
    title: string;
}) {
    const [period, setPeriod] = useState<VelocityPeriod>('monthly');
    const currentData = data[period];

    const max = 300; // Fixed max scale
    const value = currentData.value;
    const steps = 20;
    const totalSteps = max / steps;

    // Calculate needle color based on value ratio
    const getNeedleColor = (val: number) => {
        const ratio = val / max;
        if (ratio < 0.33) return '#10b981'; // Green
        if (ratio < 0.66) return '#f59e0b'; // Orange/Yellow
        return '#ef4444'; // Red
    };

    const needleColor = getNeedleColor(value);
    const clampedValue = Math.min(value, max); // Clamp value to max
    const rotation = (clampedValue / max) * 180 - 90; // From -90 (left) to 90 (right) degrees

    // Generate tick marks (angles for cos/sin positioning, not rotation)
    const ticks: { val: number; angle: number }[] = [];
    for (let i = 0; i <= totalSteps; i++) {
        const val = i * steps;
        const tickAngle = (val / max) * 180 - 180; // From -180 (left) to 0 (right) for cos/sin
        ticks.push({ val, angle: tickAngle });
    }

    // Unique gradient ID
    const gradientId = `gauge-gradient-${Math.random().toString(36).substring(2, 11)}`;

    return (
        <Card className="h-full overflow-hidden">
            <CardContent className="pt-6 pb-8">
                <div className="flex items-center gap-3 mb-4">
                    <RocketLaunchIcon className="h-6 w-6 text-muted-foreground shrink-0" />
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                            <h3 className="font-semibold text-base">{title}</h3>
                            <div className="relative">
                                <select
                                    value={period}
                                    onChange={(e) => setPeriod(e.target.value as VelocityPeriod)}
                                    aria-label="Sélectionner la période de vélocité"
                                    className="appearance-none bg-gray-100 dark:bg-gray-700 text-xs font-medium px-2 py-1 pr-6 rounded-md cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    {(Object.keys(velocityPeriodLabels) as VelocityPeriod[]).map((p) => (
                                        <option key={p} value={p}>{velocityPeriodLabels[p]}</option>
                                    ))}
                                </select>
                                <svg className="absolute right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 pointer-events-none text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p className="text-xs text-muted-foreground uppercase tracking-wide">Tâches terminées</p>
                    </div>
                </div>
                <div className="relative flex flex-col items-center justify-center h-full w-full pt-4 pb-4">
                    <div className="relative w-full max-w-[360px] h-[200px]">
                        <svg viewBox="0 0 200 120" className="w-full h-full overflow-visible">
                            {/* Gradient definition for arc */}
                            <defs>
                                <linearGradient id={gradientId} x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stopColor="#10b981" />
                                    <stop offset="50%" stopColor="#f59e0b" />
                                    <stop offset="100%" stopColor="#ef4444" />
                                </linearGradient>
                            </defs>

                            {/* Background arc (track) */}
                            <path
                                d="M20 100 A 80 80 0 0 1 180 100"
                                fill="none"
                                stroke="#f1f5f9"
                                strokeWidth="10"
                                strokeLinecap="round"
                                className="dark:stroke-gray-700"
                            />

                            {/* Colored arc (Gradient) */}
                            <path
                                d="M20 100 A 80 80 0 0 1 180 100"
                                fill="none"
                                stroke={`url(#${gradientId})`}
                                strokeWidth="10"
                                strokeLinecap="round"
                                opacity="0.8"
                            />

                            {/* Tick marks (graduations) - show all labels */}
                            {ticks.map((tick, i) => {
                                const isMajor = tick.val % 100 === 0;
                                const isMedium = tick.val % 50 === 0;
                                const rad = (tick.angle * Math.PI) / 180;
                                const x1 = 100 + Math.cos(rad) * 80;
                                const y1 = 100 + Math.sin(rad) * 80;
                                const x2 = 100 + Math.cos(rad) * (isMajor ? 66 : isMedium ? 70 : 74);
                                const y2 = 100 + Math.sin(rad) * (isMajor ? 66 : isMedium ? 70 : 74);

                                // Text position - closer for minor ticks
                                const tx = 100 + Math.cos(rad) * (isMajor ? 52 : 56);
                                const ty = 100 + Math.sin(rad) * (isMajor ? 52 : 56);

                                return (
                                    <g key={i}>
                                        <line
                                            x1={x1}
                                            y1={y1}
                                            x2={x2}
                                            y2={y2}
                                            stroke="#94a3b8"
                                            strokeWidth={isMajor ? 2 : isMedium ? 1 : 0.5}
                                            className="dark:stroke-gray-500"
                                        />
                                        <text
                                            x={tx}
                                            y={ty}
                                            fontSize={isMajor ? '8' : '5'}
                                            fontWeight={isMajor ? 'bold' : 'normal'}
                                            fill={isMajor ? '#475569' : '#94a3b8'}
                                            textAnchor="middle"
                                            alignmentBaseline="middle"
                                            className={isMajor ? 'dark:fill-gray-300' : 'dark:fill-gray-500'}
                                        >
                                            {Math.round(tick.val)}
                                        </text>
                                    </g>
                                );
                            })}

                            {/* Needle (triangular instrument-style) */}
                            <g
                                transform={`rotate(${rotation}, 100, 100)`}
                                className="transition-transform duration-1000 ease-out"
                            >
                                <path
                                    d="M97 100 L100 35 L103 100 Z"
                                    fill={needleColor}
                                    stroke="#fff"
                                    strokeWidth="0.5"
                                />
                                <circle
                                    cx="100"
                                    cy="100"
                                    r="4"
                                    fill="#1e293b"
                                    stroke="#fff"
                                    strokeWidth="1"
                                    className="dark:fill-gray-200"
                                />
                            </g>
                        </svg>

                        {/* Central value display */}
                        <div className="absolute -bottom-6 left-0 right-0 text-center">
                            <p
                                className="text-4xl font-black tracking-tighter"
                                style={{ color: needleColor }}
                            >
                                {value}
                            </p>
                            <p className="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                tâches / {currentData.label}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function DoughnutChart({ data, title, subtitle, icon: Icon }: {
    data: ChartDataItem[];
    title: string;
    subtitle: string;
    icon: React.ComponentType<{ className?: string }>;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);
    const total = data.reduce((sum, d) => sum + d.value, 0);

    if (total === 0) {
        return (
            <Card>
                <CardContent className="pt-6">
                    <div className="flex items-center gap-3 mb-4">
                        <Icon className="h-5 w-5 text-muted-foreground" />
                        <div>
                            <h3 className="font-semibold text-sm">{title}</h3>
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
                        </div>
                    </div>
                    <div className="flex items-center justify-center h-40 text-muted-foreground text-sm">
                        Aucune donnée
                    </div>
                </CardContent>
            </Card>
        );
    }

    const size = 160;
    const cx = size / 2;
    const cy = size / 2;
    const outerR = size / 2;
    const innerR = size / 2 - 24;

    let cumulative = 0;
    const segments = data
        .filter(d => d.value > 0)
        .map(d => {
            const startAngle = cumulative;
            const angle = (d.value / total) * 360;
            cumulative += angle;
            const percentage = Math.round((d.value / total) * 100);
            return { ...d, startAngle, angle, percentage };
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

    const handleMouseMove = (e: React.MouseEvent, segment: typeof segments[0]) => {
        const rect = e.currentTarget.getBoundingClientRect();
        setTooltip({
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            content: (
                <div className="text-center">
                    <div className="font-semibold">{segment.label}</div>
                    <div>{segment.value} ({segment.percentage}%)</div>
                </div>
            ),
        });
    };

    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex items-center gap-3 mb-4">
                    <Icon className="h-5 w-5 text-muted-foreground" />
                    <div>
                        <h3 className="font-semibold text-sm">{title}</h3>
                        <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
                    </div>
                </div>
                <div className="flex flex-col items-center">
                    <div className="relative" style={{ width: size, height: size }}>
                        <svg width={size} height={size} className="overflow-visible">
                            {segments.map((seg, i) => (
                                <path
                                    key={i}
                                    d={createArcPath(seg.startAngle, seg.startAngle + seg.angle)}
                                    fill={seg.color}
                                    className="cursor-pointer transition-opacity hover:opacity-80"
                                    onMouseMove={(e) => handleMouseMove(e, seg)}
                                    onMouseLeave={() => setTooltip(null)}
                                />
                            ))}
                        </svg>
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="text-center">
                                <span className="text-2xl font-bold">{total}</span>
                                <span className="block text-xs text-muted-foreground">total</span>
                            </div>
                        </div>
                        <ChartTooltip tooltip={tooltip} />
                    </div>
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
                </div>
            </CardContent>
        </Card>
    );
}

type EvolutionPeriod = 'weekly' | 'monthly' | 'quarterly' | 'semester' | 'yearly';

const periodLabels: Record<EvolutionPeriod, string> = {
    weekly: 'Semaine',
    monthly: 'Mois',
    quarterly: 'Trimestre',
    semester: 'Semestre',
    yearly: 'Année',
};

function PeriodSelector({ period, onChange }: { period: EvolutionPeriod; onChange: (p: EvolutionPeriod) => void }) {
    const periods: EvolutionPeriod[] = ['weekly', 'monthly', 'quarterly', 'semester', 'yearly'];
    return (
        <div className="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-md p-0.5">
            {periods.map((p) => (
                <button
                    key={p}
                    type="button"
                    onClick={() => onChange(p)}
                    className={`px-2 py-1 text-xs font-medium rounded transition-colors ${
                        period === p
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white'
                    }`}
                >
                    {periodLabels[p]}
                </button>
            ))}
        </div>
    );
}

function AreaChart({ evolutionData, title }: {
    evolutionData: MultiPeriodEvolution;
    title: string;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);
    const [period, setPeriod] = useState<EvolutionPeriod>('weekly');

    const data = evolutionData[period] || [];

    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-base">{title}</CardTitle>
                        <PeriodSelector period={period} onChange={setPeriod} />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="h-48 flex items-center justify-center text-muted-foreground text-sm">
                        Aucune donnée disponible
                    </div>
                </CardContent>
            </Card>
        );
    }

    const allValues = data.flatMap(d => [d.created, d.completed]);
    const maxVal = Math.max(...allValues, 1);
    const chartW = 600;
    const chartH = 200;
    const padX = 50;
    const padY = 20;
    const innerW = chartW - padX * 2;
    const innerH = chartH - padY * 2;

    const toX = (i: number) => padX + (i / Math.max(data.length - 1, 1)) * innerW;
    const toY = (v: number) => padY + innerH - (v / maxVal) * innerH;
    const baseY = padY + innerH;

    const createdAreaPath = [
        ...data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.created)}`),
        `L ${toX(data.length - 1)} ${baseY}`, `L ${toX(0)} ${baseY}`, 'Z'
    ].join(' ');

    const completedAreaPath = [
        ...data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.completed)}`),
        `L ${toX(data.length - 1)} ${baseY}`, `L ${toX(0)} ${baseY}`, 'Z'
    ].join(' ');

    const createdLinePath = data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.created)}`).join(' ');
    const completedLinePath = data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.completed)}`).join(' ');

    const ySteps = 4;
    const gridLines = Array.from({ length: ySteps + 1 }, (_, i) => {
        const val = Math.round((maxVal / ySteps) * i);
        return { y: toY(val), label: val };
    });

    const handlePointHover = (e: React.MouseEvent, d: EvolutionDataItem, type: 'created' | 'completed') => {
        const rect = e.currentTarget.closest('.chart-container')?.getBoundingClientRect();
        if (!rect) return;
        setTooltip({
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            content: (
                <div className="text-center">
                    <div className="font-semibold">{d.label}</div>
                    <div className="flex items-center gap-2">
                        <span className={type === 'created' ? 'text-blue-300' : 'text-emerald-300'}>
                            {type === 'created' ? 'Créées' : 'Terminées'}:
                        </span>
                        <span className="font-bold">{type === 'created' ? d.created : d.completed}</span>
                    </div>
                </div>
            ),
        });
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between flex-wrap gap-2">
                    <CardTitle className="text-base">{title}</CardTitle>
                    <PeriodSelector period={period} onChange={setPeriod} />
                </div>
                <p className="text-xs text-muted-foreground">Tâches créées vs terminées</p>
            </CardHeader>
            <CardContent>
                <div className="relative chart-container">
                    <svg viewBox={`0 0 ${chartW} ${chartH + 30}`} className="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <linearGradient id="projCreatedGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.4" />
                                <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.05" />
                            </linearGradient>
                            <linearGradient id="projCompletedGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stopColor="#10B981" stopOpacity="0.4" />
                                <stop offset="100%" stopColor="#10B981" stopOpacity="0.05" />
                            </linearGradient>
                        </defs>

                        {gridLines.map((g, i) => (
                            <g key={i}>
                                <line x1={padX} y1={g.y} x2={chartW - padX} y2={g.y} stroke="currentColor" strokeOpacity={0.1} />
                                <text x={padX - 8} y={g.y + 4} textAnchor="end" className="fill-muted-foreground" fontSize={11}>{g.label}</text>
                            </g>
                        ))}

                        <path d={createdAreaPath} fill="url(#projCreatedGrad)" />
                        <path d={createdLinePath} fill="none" stroke="#3B82F6" strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />
                        <path d={completedAreaPath} fill="url(#projCompletedGrad)" />
                        <path d={completedLinePath} fill="none" stroke="#10B981" strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />

                        {data.map((d, i) => (
                            <g key={i}>
                                <circle cx={toX(i)} cy={toY(d.created)} r={4} fill="#3B82F6" className="pointer-events-none" />
                                <circle cx={toX(i)} cy={toY(d.created)} r={12} fill="transparent" className="cursor-pointer"
                                    onMouseEnter={(e) => handlePointHover(e, d, 'created')}
                                    onMouseLeave={() => setTooltip(null)} />
                                <circle cx={toX(i)} cy={toY(d.completed)} r={4} fill="#10B981" className="pointer-events-none" />
                                <circle cx={toX(i)} cy={toY(d.completed)} r={12} fill="transparent" className="cursor-pointer"
                                    onMouseEnter={(e) => handlePointHover(e, d, 'completed')}
                                    onMouseLeave={() => setTooltip(null)} />
                                <text x={toX(i)} y={chartH + 10} textAnchor="middle" className="fill-muted-foreground" fontSize={11}>{d.label}</text>
                            </g>
                        ))}
                    </svg>
                    <ChartTooltip tooltip={tooltip} />
                </div>
                <div className="flex justify-center gap-6 mt-2 text-xs">
                    <div className="flex items-center gap-1.5">
                        <div className="w-4 h-3 bg-blue-500/30 border border-blue-500 rounded-sm" />
                        <span className="text-muted-foreground">Tâches créées</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="w-4 h-3 bg-emerald-500/30 border border-emerald-500 rounded-sm" />
                        <span className="text-muted-foreground">Tâches terminées</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function HorizontalBarChart({ data, title, subtitle }: {
    data: CompletionDataItem[];
    title: string;
    subtitle: string;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);

    const handleBarHover = (e: React.MouseEvent, d: CompletionDataItem) => {
        const rect = e.currentTarget.closest('.bar-chart-container')?.getBoundingClientRect();
        if (!rect) return;
        setTooltip({
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            content: (
                <div className="text-center">
                    <div className="font-semibold">{d.name}</div>
                    {d.completed !== undefined && d.total !== undefined && (
                        <div>{d.completed}/{d.total} tâches terminées</div>
                    )}
                    <div>Taux: <span className="font-bold">{Math.round(d.value)}%</span></div>
                </div>
            ),
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
                <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
            </CardHeader>
            <CardContent>
                <div className="space-y-4 relative bar-chart-container">
                    {data.length > 0 ? data.map((d) => (
                        <div key={d.name} className="space-y-1">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium truncate">{d.name}</span>
                                <span className="text-sm font-semibold" style={{ color: d.color }}>{Math.round(d.value)}%</span>
                            </div>
                            <div
                                className="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden relative cursor-pointer"
                                onMouseMove={(e) => handleBarHover(e, d)}
                                onMouseLeave={() => setTooltip(null)}
                            >
                                <div
                                    className="h-5 rounded-full transition-all hover:opacity-80"
                                    style={{
                                        width: `${Math.min(d.value, 100)}%`,
                                        backgroundColor: d.color,
                                        minWidth: d.value > 0 ? '8px' : '0',
                                    }}
                                />
                            </div>
                        </div>
                    )) : (
                        <p className="text-center text-muted-foreground py-8 text-sm">Aucune donnée</p>
                    )}
                    <ChartTooltip tooltip={tooltip} />
                </div>
            </CardContent>
        </Card>
    );
}

type TopNLimit = 10 | 20 | 30 | 50 | 100;

function TopNSelector({ value, onChange, maxItems }: { value: TopNLimit; onChange: (v: TopNLimit) => void; maxItems: number }) {
    const options: TopNLimit[] = [10, 20, 30, 50, 100];
    const availableOptions = options.filter(opt => opt <= Math.max(maxItems, 10) || opt === 10);

    return (
        <div className="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-md p-0.5">
            {availableOptions.map((opt) => (
                <button
                    key={opt}
                    type="button"
                    onClick={() => onChange(opt)}
                    className={`px-2 py-1 text-xs font-medium rounded transition-colors ${
                        value === opt
                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white'
                    }`}
                >
                    Top {opt}
                </button>
            ))}
        </div>
    );
}

const barColors = [
    '#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444',
    '#F59E0B', '#06B6D4', '#EC4899', '#84CC16', '#6366F1',
    '#14B8A6', '#F43F5E', '#A855F7', '#22C55E', '#0EA5E9',
    '#D946EF', '#EAB308', '#64748B', '#FB923C', '#4ADE80'
];

function MemberDistributionBarChart({ data, title, subtitle, icon: Icon }: {
    data: ChartDataItem[];
    title: string;
    subtitle: string;
    icon: React.ComponentType<{ className?: string }>;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);
    const [topN, setTopN] = useState<TopNLimit>(10);

    const total = data.reduce((sum, d) => sum + d.value, 0);
    const maxValue = Math.max(...data.map(d => d.value), 1);
    const displayData = data.slice(0, topN).map((d, i) => ({
        ...d,
        color: barColors[i % barColors.length],
        percentage: total > 0 ? Math.round((d.value / total) * 100) : 0,
    }));

    const handleBarHover = (e: React.MouseEvent, d: typeof displayData[0]) => {
        const rect = e.currentTarget.closest('.member-bar-chart-container')?.getBoundingClientRect();
        if (!rect) return;
        setTooltip({
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            content: (
                <div className="text-center">
                    <div className="font-semibold">{d.label}</div>
                    <div>{d.value} tâches ({d.percentage}%)</div>
                </div>
            ),
        });
    };

    if (data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <Icon className="h-5 w-5 text-muted-foreground" />
                        <div>
                            <CardTitle className="text-base">{title}</CardTitle>
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-center h-40 text-muted-foreground text-sm">
                        Aucune donnée
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between flex-wrap gap-2">
                    <div className="flex items-center gap-3">
                        <Icon className="h-5 w-5 text-muted-foreground" />
                        <div>
                            <CardTitle className="text-base">{title}</CardTitle>
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
                        </div>
                    </div>
                    {data.length > 10 && (
                        <TopNSelector value={topN} onChange={setTopN} maxItems={data.length} />
                    )}
                </div>
                <div className="text-sm text-muted-foreground mt-2">
                    Total: <span className="font-semibold text-foreground">{total}</span> tâches
                    {data.length > topN && (
                        <span className="ml-2">
                            (affichage {topN}/{data.length} membres)
                        </span>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-2 relative member-bar-chart-container max-h-[400px] overflow-y-auto pr-2">
                    {displayData.map((d, index) => (
                        <div
                            key={d.label}
                            className="flex items-center gap-3 group cursor-pointer"
                            onMouseMove={(e) => handleBarHover(e, d)}
                            onMouseLeave={() => setTooltip(null)}
                        >
                            <span className="w-5 text-xs text-muted-foreground text-right shrink-0">
                                {index + 1}.
                            </span>
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-0.5">
                                    <span className="text-sm font-medium truncate">{d.label}</span>
                                    <span className="text-xs text-muted-foreground shrink-0">
                                        {d.value} ({d.percentage}%)
                                    </span>
                                </div>
                                <div className="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                    <div
                                        className="h-4 rounded-full transition-all group-hover:opacity-80"
                                        style={{
                                            width: `${(d.value / maxValue) * 100}%`,
                                            backgroundColor: d.color,
                                            minWidth: d.value > 0 ? '4px' : '0',
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                    <ChartTooltip tooltip={tooltip} />
                </div>
            </CardContent>
        </Card>
    );
}

interface Props {
    statistics: ProjectAnalyticsData;
    context?: 'dashboard' | 'project' | 'tasks';
}

export default function ProjectStatisticsAnalytical({ statistics, context = 'dashboard' }: Props) {
    const statusDoughnuts = [];
    const memberDoughnuts = [];

    // Status-based doughnuts
    if (context === 'dashboard' && statistics.projects_by_status) {
        statusDoughnuts.push(
            <DoughnutChart
                key="projects"
                data={statistics.projects_by_status}
                title="Projets par Statut"
                subtitle="Répartition des projets"
                icon={ChartPieIcon}
            />
        );
    }

    statusDoughnuts.push(
        <DoughnutChart
            key="tasks-status"
            data={statistics.tasks_by_status}
            title="Tâches par Statut"
            subtitle="État actuel des tâches"
            icon={ClipboardDocumentCheckIcon}
        />
    );

    statusDoughnuts.push(
        <DoughnutChart
            key="tasks-priority"
            data={statistics.tasks_by_priority}
            title="Tâches par Priorité"
            subtitle="Répartition par niveau"
            icon={FlagIcon}
        />
    );

    // Global progress for tasks context (after "Tâches par Priorité")
    if (context === 'tasks' && statistics.global_progress) {
        statusDoughnuts.push(
            <GlobalProgressChart
                key="global-progress"
                data={statistics.global_progress}
                title="Progression Globale"
            />
        );
    }

    if (statistics.sprints_by_status) {
        statusDoughnuts.push(
            <DoughnutChart
                key="sprints"
                data={statistics.sprints_by_status}
                title="Sprints par Statut"
                subtitle="État des sprints"
                icon={ClockIcon}
            />
        );
    }

    // Member-based doughnuts (only for projects_by_member)
    if (context === 'dashboard' && statistics.projects_by_member && statistics.projects_by_member.length > 0) {
        memberDoughnuts.push(
            <DoughnutChart
                key="projects-member"
                data={statistics.projects_by_member}
                title="Projets par Responsable"
                subtitle="Répartition des projets"
                icon={UserGroupIcon}
            />
        );
    }

    // Epics by status chart
    const hasEpics = statistics.epics_by_status && statistics.epics_by_status.some(e => e.value > 0);
    if (hasEpics) {
        memberDoughnuts.push(
            <DoughnutChart
                key="epics-status"
                data={statistics.epics_by_status!}
                title="Epics par Statut"
                subtitle="Répartition des epics"
                icon={RocketLaunchIcon}
            />
        );
    }

    const completionData = statistics.completion_by_project || statistics.completion_by_assignee || [];
    const completionTitle = statistics.completion_by_project
        ? 'Taux de Complétion par Projet'
        : 'Taux de Complétion par Membre';
    const completionSubtitle = statistics.completion_by_project
        ? 'Performance par projet'
        : 'Performance par assigné';

    return (
        <div className="space-y-6">
            {/* Status-based charts */}
            <div className={`grid grid-cols-1 ${statusDoughnuts.length >= 4 ? 'md:grid-cols-2 xl:grid-cols-4' : statusDoughnuts.length === 3 ? 'md:grid-cols-3' : 'md:grid-cols-2'} gap-6`}>
                {statusDoughnuts}
            </div>

            {/* Member-based distribution charts with global progress and velocity */}
            {(memberDoughnuts.length > 0 || statistics.velocity || (context === 'dashboard' && statistics.global_progress)) && (
                <div className={`grid grid-cols-1 ${memberDoughnuts.length + (context === 'dashboard' && statistics.global_progress ? 1 : 0) + (statistics.velocity ? 1 : 0) >= 4 ? 'md:grid-cols-2 xl:grid-cols-4' : 'md:grid-cols-3'} gap-6`}>
                    {memberDoughnuts}
                    {statistics.velocity && (
                        <VelocityGauge
                            data={statistics.velocity}
                            title="Vélocité"
                        />
                    )}
                    {context === 'dashboard' && statistics.global_progress && (
                        <GlobalProgressChart
                            data={statistics.global_progress}
                            title="Progression Globale"
                        />
                    )}
                </div>
            )}

            {/* Tasks by member bar chart - full width */}
            {statistics.tasks_by_member && statistics.tasks_by_member.length > 0 && (
                <MemberDistributionBarChart
                    data={statistics.tasks_by_member}
                    title="Tâches par Membre"
                    subtitle="Répartition des tâches assignées"
                    icon={UserGroupIcon}
                />
            )}

            {/* Evolution and completion charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <AreaChart
                    evolutionData={statistics.task_evolution}
                    title="Évolution des Tâches"
                />
                <HorizontalBarChart
                    data={completionData}
                    title={completionTitle}
                    subtitle={completionSubtitle}
                />
            </div>
        </div>
    );
}
