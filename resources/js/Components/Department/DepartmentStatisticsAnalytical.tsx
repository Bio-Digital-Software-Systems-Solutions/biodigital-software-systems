import React, { useMemo, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    ClipboardDocumentCheckIcon,
    CheckBadgeIcon,
    ClockIcon,
    BoltIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentStatistics } from './DepartmentStatisticsOperational';

interface Props {
    statistics?: DepartmentStatistics;
}

const MEMBER_COLORS = [
    '#3B82F6', // blue
    '#10B981', // green
    '#F97316', // orange
    '#8B5CF6', // violet
    '#EF4444', // red
    '#F59E0B', // amber
    '#06B6D4', // cyan
    '#EC4899', // pink
];

const STATUS_COLORS: Record<string, { color: string; label: string }> = {
    completed: { color: '#10B981', label: 'Terminé' },
    in_progress: { color: '#3B82F6', label: 'En cours' },
    pending: { color: '#F59E0B', label: 'En attente' },
    overdue: { color: '#EF4444', label: 'En retard' },
};

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
            <div
                className="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-100"
            />
        </div>
    );
}

function DoughnutChart({ data, title, subtitle, icon: Icon }: {
    data: { label: string; value: number; color: string }[];
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

    // Build SVG arc segments
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
        // Handle full circle (360 degrees) - SVG arcs can't draw a complete circle
        const angleDiff = endAngle - startAngle;
        if (angleDiff >= 359.99) {
            // Draw as two semicircles for a full donut
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
                    <div>{segment.value} tâches ({segment.percentage}%)</div>
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
                                <span className="block text-xs text-muted-foreground">tâches</span>
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

function AreaChart({ data, title, subtitle }: {
    data: { label: string; created: number; completed: number }[];
    title: string;
    subtitle: string;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);

    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">{title}</CardTitle>
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
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

    // Build area paths for filled regions
    const createdAreaPath = [
        ...data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.created)}`),
        `L ${toX(data.length - 1)} ${baseY}`,
        `L ${toX(0)} ${baseY}`,
        'Z'
    ].join(' ');

    const completedAreaPath = [
        ...data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.completed)}`),
        `L ${toX(data.length - 1)} ${baseY}`,
        `L ${toX(0)} ${baseY}`,
        'Z'
    ].join(' ');

    const createdLinePath = data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.created)}`).join(' ');
    const completedLinePath = data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${toX(i)} ${toY(d.completed)}`).join(' ');

    // Y-axis gridlines
    const ySteps = 4;
    const gridLines = Array.from({ length: ySteps + 1 }, (_, i) => {
        const val = Math.round((maxVal / ySteps) * i);
        return { y: toY(val), label: val };
    });

    const handlePointHover = (e: React.MouseEvent, d: typeof data[0], type: 'created' | 'completed') => {
        const rect = e.currentTarget.closest('.chart-container')?.getBoundingClientRect();
        if (!rect) return;
        const svgRect = e.currentTarget.closest('svg')?.getBoundingClientRect();
        if (!svgRect) return;

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
                <CardTitle className="text-base">{title}</CardTitle>
                <p className="text-xs text-muted-foreground">{subtitle}</p>
            </CardHeader>
            <CardContent>
                <div className="relative chart-container">
                    <svg viewBox={`0 0 ${chartW} ${chartH + 30}`} className="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <linearGradient id="createdGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stopColor="#3B82F6" stopOpacity="0.4" />
                                <stop offset="100%" stopColor="#3B82F6" stopOpacity="0.05" />
                            </linearGradient>
                            <linearGradient id="completedGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stopColor="#10B981" stopOpacity="0.4" />
                                <stop offset="100%" stopColor="#10B981" stopOpacity="0.05" />
                            </linearGradient>
                        </defs>

                        {/* Grid */}
                        {gridLines.map((g, i) => (
                            <g key={i}>
                                <line x1={padX} y1={g.y} x2={chartW - padX} y2={g.y} stroke="currentColor" strokeOpacity={0.1} />
                                <text x={padX - 8} y={g.y + 4} textAnchor="end" className="fill-muted-foreground" fontSize={11}>{g.label}</text>
                            </g>
                        ))}

                        {/* Created area (blue) */}
                        <path d={createdAreaPath} fill="url(#createdGradient)" />
                        <path d={createdLinePath} fill="none" stroke="#3B82F6" strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />

                        {/* Completed area (green) */}
                        <path d={completedAreaPath} fill="url(#completedGradient)" />
                        <path d={completedLinePath} fill="none" stroke="#10B981" strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />

                        {/* Data points with hover areas */}
                        {data.map((d, i) => (
                            <g key={i}>
                                {/* Created point */}
                                <circle cx={toX(i)} cy={toY(d.created)} r={4} fill="#3B82F6" className="pointer-events-none" />
                                <circle
                                    cx={toX(i)}
                                    cy={toY(d.created)}
                                    r={12}
                                    fill="transparent"
                                    className="cursor-pointer"
                                    onMouseEnter={(e) => handlePointHover(e, d, 'created')}
                                    onMouseLeave={() => setTooltip(null)}
                                />

                                {/* Completed point */}
                                <circle cx={toX(i)} cy={toY(d.completed)} r={4} fill="#10B981" className="pointer-events-none" />
                                <circle
                                    cx={toX(i)}
                                    cy={toY(d.completed)}
                                    r={12}
                                    fill="transparent"
                                    className="cursor-pointer"
                                    onMouseEnter={(e) => handlePointHover(e, d, 'completed')}
                                    onMouseLeave={() => setTooltip(null)}
                                />

                                {/* X-axis label */}
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

function VelocityGauge({ velocityThisMonth, velocityLastMonth, velocityChange }: {
    velocityThisMonth: number;
    velocityLastMonth: number;
    velocityChange: number;
}) {
    const max = 300; // Fixed max scale
    const value = velocityThisMonth;
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

    // Generate tick marks
    const ticks: { val: number; angle: number }[] = [];
    for (let i = 0; i <= totalSteps; i++) {
        const val = i * steps;
        const tickAngle = (val / max) * 180 - 180;
        ticks.push({ val, angle: tickAngle });
    }

    const isPositiveChange = velocityChange >= 0;

    // Unique gradient ID
    const gradientId = `dept-gauge-gradient-${Math.random().toString(36).substring(2, 11)}`;

    return (
        <Card className="h-full">
            <CardContent className="pt-6 pb-4">
                <div className="flex items-center gap-3 mb-4">
                    <BoltIcon className="h-6 w-6 text-muted-foreground" />
                    <div>
                        <h3 className="font-semibold text-base">Vélocité</h3>
                        <p className="text-sm text-muted-foreground uppercase tracking-wide">Tâches terminées ce mois</p>
                    </div>
                </div>
                <div className="relative flex flex-col items-center justify-center h-full w-full pt-4">
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
                                tâches / mois
                            </p>
                        </div>
                    </div>

                    {/* Comparison with last month */}
                    <div className="flex items-center gap-2 mt-3 text-base">
                        <span className={`flex items-center gap-1 font-semibold ${isPositiveChange ? 'text-green-600' : 'text-red-600'}`}>
                            {isPositiveChange ? '↑' : '↓'} {Math.abs(velocityChange)}%
                        </span>
                        <span className="text-muted-foreground">
                            vs mois dernier ({velocityLastMonth})
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function HorizontalBarChart({ data, title, subtitle }: {
    data: { name: string; value: number; color: string; completed?: number; total?: number }[];
    title: string;
    subtitle: string;
}) {
    const [tooltip, setTooltip] = useState<TooltipData | null>(null);

    const handleBarHover = (e: React.MouseEvent, d: typeof data[0]) => {
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

export default function DepartmentStatisticsAnalytical({ statistics }: Props) {
    const members = statistics?.tasks_by_member?.filter(m => m.uuid !== null) ?? [];

    const memberLoadData = useMemo(() =>
        members.map((m, i) => ({
            label: m.name,
            value: m.total,
            color: MEMBER_COLORS[i % MEMBER_COLORS.length],
        })),
        [members]
    );

    const memberSuccessData = useMemo(() =>
        members.map((m, i) => ({
            label: m.name,
            value: m.completed,
            color: MEMBER_COLORS[i % MEMBER_COLORS.length],
        })),
        [members]
    );

    const pipelineData = useMemo(() => {
        if (!statistics) return [];
        return [
            { label: STATUS_COLORS.completed.label, value: statistics.todos.completed, color: STATUS_COLORS.completed.color },
            { label: STATUS_COLORS.in_progress.label, value: statistics.todos.in_progress, color: STATUS_COLORS.in_progress.color },
            { label: STATUS_COLORS.pending.label, value: statistics.todos.pending, color: STATUS_COLORS.pending.color },
            { label: STATUS_COLORS.overdue.label, value: statistics.todos.overdue, color: STATUS_COLORS.overdue.color },
        ];
    }, [statistics]);

    const weeklyEvolution = statistics?.task_evolution?.weekly ?? [];

    const efficiencyData = useMemo(() =>
        (statistics?.performance?.individual ?? []).map((m, i) => ({
            name: m.name,
            value: m.completion_rate,
            color: MEMBER_COLORS[i % MEMBER_COLORS.length],
            completed: m.completed_tasks,
            total: m.total_tasks,
        })),
        [statistics?.performance?.individual]
    );

    return (
        <div className="space-y-6">
            {/* Doughnut Charts Row */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <DoughnutChart
                    data={memberLoadData}
                    title="Tâches par Membre"
                    subtitle="Répartition des tâches assignées"
                    icon={ClipboardDocumentCheckIcon}
                />
                <DoughnutChart
                    data={memberSuccessData}
                    title="Tâches Terminées par Membre"
                    subtitle="Tâches complétées avec succès"
                    icon={CheckBadgeIcon}
                />
                <DoughnutChart
                    data={pipelineData}
                    title="Répartition par Statut"
                    subtitle="État actuel des tâches"
                    icon={ClockIcon}
                />
            </div>

            {/* Area Chart + Velocity Gauge + Horizontal Bars */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <AreaChart
                    data={weeklyEvolution}
                    title="Évolution Hebdomadaire"
                    subtitle="Tâches créées vs terminées"
                />
                {statistics?.performance?.collective && (
                    <VelocityGauge
                        velocityThisMonth={statistics.performance.collective.velocity_this_month}
                        velocityLastMonth={statistics.performance.collective.velocity_last_month}
                        velocityChange={statistics.performance.collective.velocity_change}
                    />
                )}
                <HorizontalBarChart
                    data={efficiencyData}
                    title="Taux de Complétion"
                    subtitle="Performance par membre"
                />
            </div>
        </div>
    );
}
