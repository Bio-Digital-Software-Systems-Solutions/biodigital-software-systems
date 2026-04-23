import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    ClipboardDocumentCheckIcon,
    CheckBadgeIcon,
    ClockIcon,
    BoltIcon,
} from '@heroicons/react/24/outline';
import type { GroupStatistics } from './GroupStatisticsOperational';

interface Props {
    statistics?: GroupStatistics;
}

const STATUS_COLORS: Record<string, { color: string; label: string }> = {
    completed: { color: '#10B981', label: 'Terminé' },
    in_progress: { color: '#3B82F6', label: 'En cours' },
    pending: { color: '#F59E0B', label: 'En attente' },
    overdue: { color: '#EF4444', label: 'En retard' },
};

const PRIORITY_COLORS: Record<string, { color: string; label: string }> = {
    critical: { color: '#EF4444', label: 'Critique' },
    high: { color: '#F97316', label: 'Haute' },
    medium: { color: '#F59E0B', label: 'Moyenne' },
    low: { color: '#10B981', label: 'Basse' },
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

    const describeArc = (startAngle: number, endAngle: number, outerRadius: number, innerRadius: number) => {
        const startRad = (startAngle - 90) * Math.PI / 180;
        const endRad = (endAngle - 90) * Math.PI / 180;
        const x1 = cx + outerRadius * Math.cos(startRad);
        const y1 = cy + outerRadius * Math.sin(startRad);
        const x2 = cx + outerRadius * Math.cos(endRad);
        const y2 = cy + outerRadius * Math.sin(endRad);
        const x3 = cx + innerRadius * Math.cos(endRad);
        const y3 = cy + innerRadius * Math.sin(endRad);
        const x4 = cx + innerRadius * Math.cos(startRad);
        const y4 = cy + innerRadius * Math.sin(startRad);
        const largeArc = endAngle - startAngle > 180 ? 1 : 0;
        return `M ${x1} ${y1} A ${outerRadius} ${outerRadius} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4} Z`;
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
                <div className="flex items-center justify-center gap-6">
                    <div className="relative">
                        <svg width={size} height={size}>
                            {segments.map((seg, i) => (
                                <path
                                    key={i}
                                    d={describeArc(seg.startAngle, seg.startAngle + seg.angle, outerR - 2, innerR)}
                                    fill={seg.color}
                                    className="transition-opacity hover:opacity-80 cursor-pointer"
                                    onMouseEnter={(e) => {
                                        const rect = e.currentTarget.getBoundingClientRect();
                                        const svgRect = e.currentTarget.closest('svg')!.getBoundingClientRect();
                                        setTooltip({
                                            x: rect.left + rect.width / 2 - svgRect.left,
                                            y: rect.top - svgRect.top,
                                            content: <span>{seg.label}: {seg.value} ({seg.percentage}%)</span>,
                                        });
                                    }}
                                    onMouseLeave={() => setTooltip(null)}
                                />
                            ))}
                            <text x={cx} y={cy - 6} textAnchor="middle" className="fill-gray-900 dark:fill-white text-2xl font-bold">
                                {total}
                            </text>
                            <text x={cx} y={cy + 14} textAnchor="middle" className="fill-gray-500 dark:fill-gray-400 text-xs">
                                Total
                            </text>
                        </svg>
                        <ChartTooltip tooltip={tooltip} />
                    </div>
                    <div className="space-y-2">
                        {segments.map((seg, i) => (
                            <div key={i} className="flex items-center gap-2 text-sm">
                                <div className="w-3 h-3 rounded-full" style={{ backgroundColor: seg.color }} />
                                <span className="text-gray-600 dark:text-gray-400">{seg.label}</span>
                                <span className="font-medium text-gray-900 dark:text-white ml-auto">{seg.value}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function VelocityGauge({ value, max = 300 }: { value: number; max?: number }) {
    const ratio = Math.min(value / max, 1);
    const angle = -90 + ratio * 180;
    const needleColor = ratio < 0.33 ? '#10b981' : ratio < 0.66 ? '#f59e0b' : '#ef4444';

    const size = 200;
    const cx = size / 2;
    const cy = size / 2 + 10;
    const radius = 70;

    const ticks = [];
    for (let i = 0; i <= max; i += 20) {
        const tickAngle = -90 + (i / max) * 180;
        const rad = (tickAngle * Math.PI) / 180;
        const isLabel = i % 100 === 0;
        const innerTick = radius - (isLabel ? 12 : 6);
        ticks.push({
            x1: cx + innerTick * Math.cos(rad),
            y1: cy + innerTick * Math.sin(rad),
            x2: cx + radius * Math.cos(rad),
            y2: cy + radius * Math.sin(rad),
            label: isLabel ? i.toString() : null,
            labelX: cx + (radius + 14) * Math.cos(rad),
            labelY: cy + (radius + 14) * Math.sin(rad),
            isLabel,
        });
    }

    const needleRad = (angle * Math.PI) / 180;
    const needleLen = radius - 15;
    const nx = cx + needleLen * Math.cos(needleRad);
    const ny = cy + needleLen * Math.sin(needleRad);

    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex items-center gap-3 mb-4">
                    <BoltIcon className="h-5 w-5 text-muted-foreground" />
                    <div>
                        <h3 className="font-semibold text-sm">Vélocité</h3>
                        <p className="text-xs text-muted-foreground uppercase tracking-wide">Tâches/mois</p>
                    </div>
                </div>
                <div className="flex justify-center">
                    <svg width={size} height={size / 2 + 30} viewBox={`0 0 ${size} ${size / 2 + 30}`}>
                        {/* Background arc */}
                        <path
                            d={`M ${cx - radius} ${cy} A ${radius} ${radius} 0 0 1 ${cx + radius} ${cy}`}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="4"
                            className="text-gray-200 dark:text-gray-700"
                        />
                        {/* Ticks */}
                        {ticks.map((tick, i) => (
                            <g key={i}>
                                <line
                                    x1={tick.x1} y1={tick.y1}
                                    x2={tick.x2} y2={tick.y2}
                                    stroke="currentColor"
                                    strokeWidth={tick.isLabel ? 2 : 1}
                                    className="text-gray-400 dark:text-gray-500"
                                />
                                {tick.label && (
                                    <text
                                        x={tick.labelX} y={tick.labelY}
                                        textAnchor="middle"
                                        dominantBaseline="middle"
                                        className="fill-gray-500 dark:fill-gray-400"
                                        fontSize="10"
                                        fontWeight={tick.isLabel ? 'bold' : 'normal'}
                                    >
                                        {tick.label}
                                    </text>
                                )}
                            </g>
                        ))}
                        {/* Needle */}
                        <line x1={cx} y1={cy} x2={nx} y2={ny} stroke={needleColor} strokeWidth="3" strokeLinecap="round" />
                        <circle cx={cx} cy={cy} r="5" fill={needleColor} />
                        {/* Value */}
                        <text x={cx} y={cy + 25} textAnchor="middle" className="fill-gray-900 dark:fill-white text-lg font-bold">
                            {value}
                        </text>
                    </svg>
                </div>
            </CardContent>
        </Card>
    );
}

export default function GroupStatisticsAnalytical({ statistics }: Props) {
    if (!statistics) {
        return (
            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                <ClipboardDocumentCheckIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                <p>Aucune donnée statistique disponible</p>
            </div>
        );
    }

    const { todos, performance, tasks_by_member } = statistics;

    const statusData = [
        { label: STATUS_COLORS.completed.label, value: todos.completed, color: STATUS_COLORS.completed.color },
        { label: STATUS_COLORS.in_progress.label, value: todos.in_progress, color: STATUS_COLORS.in_progress.color },
        { label: STATUS_COLORS.pending.label, value: todos.pending, color: STATUS_COLORS.pending.color },
        { label: STATUS_COLORS.overdue.label, value: todos.overdue, color: STATUS_COLORS.overdue.color },
    ];

    const priorityData = [
        { label: PRIORITY_COLORS.critical.label, value: todos.by_priority.critical, color: PRIORITY_COLORS.critical.color },
        { label: PRIORITY_COLORS.high.label, value: todos.by_priority.high, color: PRIORITY_COLORS.high.color },
        { label: PRIORITY_COLORS.medium.label, value: todos.by_priority.medium, color: PRIORITY_COLORS.medium.color },
        { label: PRIORITY_COLORS.low.label, value: todos.by_priority.low, color: PRIORITY_COLORS.low.color },
    ];

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <DoughnutChart
                    data={statusData}
                    title="Tâches par statut"
                    subtitle="Répartition"
                    icon={ClipboardDocumentCheckIcon}
                />
                <DoughnutChart
                    data={priorityData}
                    title="Tâches par priorité"
                    subtitle="Répartition"
                    icon={CheckBadgeIcon}
                />
                <VelocityGauge value={performance?.collective?.velocity_this_month ?? 0} />
            </div>

            {/* Individual Performance Cards */}
            {tasks_by_member && tasks_by_member.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Performance individuelle</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {tasks_by_member.filter(m => m.uuid).map((member, idx) => (
                                <div key={idx} className="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                    <div className="flex items-center gap-3 mb-3">
                                        <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white text-sm font-medium">
                                            {member.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="font-medium text-sm text-gray-900 dark:text-white">{member.name}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">{member.total} tâches</p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                        <div className="text-green-600">Terminé: {member.completed}</div>
                                        <div className="text-blue-600">En cours: {member.in_progress}</div>
                                        <div className="text-yellow-600">En attente: {member.pending}</div>
                                        <div className="text-red-600">En retard: {member.overdue}</div>
                                    </div>
                                    <div className="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div
                                            className="bg-green-500 h-1.5 rounded-full"
                                            style={{ width: `${member.completion_rate}%` }}
                                        />
                                    </div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{member.completion_rate}% complété</p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
