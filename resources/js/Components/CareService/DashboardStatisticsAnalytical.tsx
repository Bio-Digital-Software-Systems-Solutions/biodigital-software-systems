import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    ChartPieIcon,
    UserGroupIcon,
    ArrowsRightLeftIcon,
    ArrowPathIcon,
    ArrowsUpDownIcon,
} from '@heroicons/react/24/outline';

export interface DashboardAnalyticsData {
    appointments_by_status: ChartDataItem[];
    appointments_by_theme: ChartDataItem[];
    appointments_by_pastor: ChartDataItem[];
    appointments_by_mode: ChartDataItem[];
    appointment_evolution: MultiPeriodEvolution;
    global_progress: GlobalProgressData;
    velocity: VelocityData;
    completion_by_pastor: CompletionDataItem[];
    follow_ups?: FollowUpData;
    transfers?: TransferData;
}

interface FollowUpData {
    total: number;
    follow_ups: number;
    initial: number;
    follow_up_rate: number;
    average_follow_ups_per_initial: number;
}

interface TransferData {
    total: number;
    transferred: number;
    transfer_rate: number;
    by_destination: TransferDestination[];
}

interface TransferDestination {
    user_id: number;
    user_name: string;
    count: number;
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

function RateCard({
    title,
    subtitle,
    rate,
    numerator,
    denominator,
    icon: Icon,
    color,
    additionalInfo
}: {
    title: string;
    subtitle: string;
    rate: number;
    numerator: number;
    denominator: number;
    icon: React.ComponentType<{ className?: string }>;
    color: string;
    additionalInfo?: string;
}) {
    const size = 140;
    const strokeWidth = 12;
    const radius = (size - strokeWidth) / 2;
    const circumference = Math.PI * radius; // Semi-circle
    const strokeDashoffset = circumference - (rate / 100) * circumference;

    const gradientId = `rate-gradient-${Math.random().toString(36).substr(2, 9)}`;

    return (
        <Card className="h-full">
            <CardContent className="pt-6">
                <div className="flex items-center gap-3 mb-4">
                    <Icon className="h-5 w-5 text-muted-foreground" />
                    <div>
                        <h3 className="font-semibold text-sm">{title}</h3>
                        <p className="text-xs text-muted-foreground uppercase tracking-wide">{subtitle}</p>
                    </div>
                </div>
                <div className="flex flex-col items-center justify-center">
                    <div className="relative" style={{ width: size, height: size / 2 + 20 }}>
                        <svg width={size} height={size / 2 + 20} className="overflow-visible">
                            <defs>
                                <linearGradient id={gradientId} x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stopColor={color} stopOpacity="0.6" />
                                    <stop offset="100%" stopColor={color} />
                                </linearGradient>
                            </defs>
                            {/* Background arc */}
                            <path
                                d={`M ${strokeWidth / 2} ${size / 2} A ${radius} ${radius} 0 0 1 ${size - strokeWidth / 2} ${size / 2}`}
                                fill="none"
                                stroke="currentColor"
                                strokeWidth={strokeWidth}
                                strokeLinecap="round"
                                className="text-gray-200 dark:text-gray-700"
                            />
                            {/* Progress arc */}
                            <path
                                d={`M ${strokeWidth / 2} ${size / 2} A ${radius} ${radius} 0 0 1 ${size - strokeWidth / 2} ${size / 2}`}
                                fill="none"
                                stroke={`url(#${gradientId})`}
                                strokeWidth={strokeWidth}
                                strokeLinecap="round"
                                strokeDasharray={circumference}
                                strokeDashoffset={strokeDashoffset}
                                className="transition-all duration-700 ease-out"
                            />
                        </svg>
                        <div className="absolute inset-0 flex flex-col items-center justify-end pb-0">
                            <span className="text-3xl font-bold" style={{ color }}>
                                {rate}%
                            </span>
                        </div>
                    </div>
                    <div className="text-center mt-2">
                        <p className="text-sm text-muted-foreground">
                            <span className="font-semibold text-foreground">{numerator}</span> sur {denominator} RDV
                        </p>
                        {additionalInfo && (
                            <p className="text-xs text-muted-foreground mt-1">{additionalInfo}</p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function TransferDetailsCard({ data }: { data: TransferData }) {
    if (data.by_destination.length === 0) {
        return null;
    }

    const colors = ['#8B5CF6', '#10B981', '#F97316', '#3B82F6', '#EF4444'];

    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex items-center gap-3 mb-4">
                    <ArrowsUpDownIcon className="h-5 w-5 text-muted-foreground" />
                    <div>
                        <h3 className="font-semibold text-sm">Destinations des Transferts</h3>
                        <p className="text-xs text-muted-foreground uppercase tracking-wide">Par pasteur destinataire</p>
                    </div>
                </div>
                <div className="space-y-3">
                    {data.by_destination.slice(0, 5).map((dest, i) => (
                        <div key={dest.user_id} className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div
                                    className="w-3 h-3 rounded-full"
                                    style={{ backgroundColor: colors[i % colors.length] }}
                                />
                                <span className="text-sm">{dest.user_name}</span>
                            </div>
                            <span className="text-sm font-semibold" style={{ color: colors[i % colors.length] }}>
                                {dest.count} RDV
                            </span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

type EvolutionPeriod = 'weekly' | 'monthly' | 'quarterly';

const periodLabels: Record<EvolutionPeriod, string> = {
    weekly: 'Semaine',
    monthly: 'Mois',
    quarterly: 'Trimestre',
};

function PeriodSelector({ period, onChange }: { period: EvolutionPeriod; onChange: (p: EvolutionPeriod) => void }) {
    const periods: EvolutionPeriod[] = ['weekly', 'monthly', 'quarterly'];
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
                        <span className={type === 'created' ? 'text-purple-300' : 'text-emerald-300'}>
                            {type === 'created' ? 'Créés' : 'Terminés'}:
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
                <p className="text-xs text-muted-foreground mt-1">RDV créés vs terminés</p>
            </CardHeader>
            <CardContent>
                <div className="chart-container relative" style={{ width: '100%', height: chartH }}>
                    <svg viewBox={`0 0 ${chartW} ${chartH}`} className="w-full h-full overflow-visible">
                        {gridLines.map((g, i) => (
                            <g key={i}>
                                <line
                                    x1={padX}
                                    y1={g.y}
                                    x2={chartW - padX}
                                    y2={g.y}
                                    stroke="#e2e8f0"
                                    strokeWidth="1"
                                    className="dark:stroke-gray-700"
                                />
                                <text
                                    x={padX - 10}
                                    y={g.y}
                                    textAnchor="end"
                                    alignmentBaseline="middle"
                                    fontSize="12"
                                    fill="#94a3b8"
                                    className="dark:fill-gray-500"
                                >
                                    {g.label}
                                </text>
                            </g>
                        ))}

                        <path d={createdAreaPath} fill="#8B5CF6" fillOpacity="0.2" />
                        <path d={completedAreaPath} fill="#10B981" fillOpacity="0.3" />

                        <path d={createdLinePath} fill="none" stroke="#8B5CF6" strokeWidth="2" />
                        <path d={completedLinePath} fill="none" stroke="#10B981" strokeWidth="2" />

                        {data.map((d, i) => (
                            <g key={i}>
                                <circle
                                    cx={toX(i)}
                                    cy={toY(d.created)}
                                    r="4"
                                    fill="#8B5CF6"
                                    className="cursor-pointer"
                                    onMouseMove={(e) => handlePointHover(e, d, 'created')}
                                    onMouseLeave={() => setTooltip(null)}
                                />
                                <circle
                                    cx={toX(i)}
                                    cy={toY(d.completed)}
                                    r="4"
                                    fill="#10B981"
                                    className="cursor-pointer"
                                    onMouseMove={(e) => handlePointHover(e, d, 'completed')}
                                    onMouseLeave={() => setTooltip(null)}
                                />
                                <text
                                    x={toX(i)}
                                    y={baseY + 15}
                                    textAnchor="middle"
                                    fontSize="10"
                                    fill="#64748b"
                                    className="dark:fill-gray-400"
                                >
                                    {d.label}
                                </text>
                            </g>
                        ))}
                    </svg>
                    <ChartTooltip tooltip={tooltip} />
                </div>
                <div className="flex items-center justify-center gap-6 mt-4">
                    <div className="flex items-center gap-1.5">
                        <div className="w-4 h-3 bg-purple-500/30 border border-purple-500 rounded-sm" />
                        <span className="text-muted-foreground text-sm">RDV créés</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="w-4 h-3 bg-emerald-500/30 border border-emerald-500 rounded-sm" />
                        <span className="text-muted-foreground text-sm">RDV terminés</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

const barColors = [
    '#8B5CF6', '#10B981', '#F97316', '#3B82F6', '#EF4444',
    '#F59E0B', '#06B6D4', '#EC4899', '#84CC16', '#6366F1',
];

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
                        <div>{d.completed}/{d.total} RDV terminés</div>
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
                    {data.length > 0 ? data.map((d, i) => (
                        <div key={d.name} className="space-y-1">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium truncate">{d.name}</span>
                                <span className="text-sm font-semibold" style={{ color: d.color || barColors[i % barColors.length] }}>{Math.round(d.value)}%</span>
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
                                        backgroundColor: d.color || barColors[i % barColors.length],
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

interface Props {
    data: DashboardAnalyticsData;
}

export default function DashboardStatisticsAnalytical({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Row 1: Donut Charts */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <DoughnutChart
                    data={data.appointments_by_status}
                    title="RDV par Statut"
                    subtitle="Répartition des RDV"
                    icon={ChartPieIcon}
                />
                <DoughnutChart
                    data={data.appointments_by_pastor}
                    title="RDV par Pasteur"
                    subtitle="Répartition par agent"
                    icon={UserGroupIcon}
                />
                <DoughnutChart
                    data={data.appointments_by_mode}
                    title="Mode de Consultation"
                    subtitle="Présentiel vs En ligne"
                    icon={ArrowsRightLeftIcon}
                />
            </div>

            {/* Row 2: Follow-up and Transfer Rates */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {data.follow_ups && (
                    <RateCard
                        title="Taux de Suivi"
                        subtitle="RDV de suivi vs initiaux"
                        rate={data.follow_ups.follow_up_rate}
                        numerator={data.follow_ups.follow_ups}
                        denominator={data.follow_ups.total}
                        icon={ArrowPathIcon}
                        color="#8B5CF6"
                        additionalInfo={`Moyenne: ${data.follow_ups.average_follow_ups_per_initial} suivis par RDV initial`}
                    />
                )}
                {data.transfers && (
                    <RateCard
                        title="Taux de Transfert"
                        subtitle="RDV transférés"
                        rate={data.transfers.transfer_rate}
                        numerator={data.transfers.transferred}
                        denominator={data.transfers.total}
                        icon={ArrowsUpDownIcon}
                        color="#F97316"
                    />
                )}
                {data.transfers && data.transfers.by_destination.length > 0 && (
                    <TransferDetailsCard data={data.transfers} />
                )}
            </div>

            {/* Row 3: Evolution Chart */}
            <AreaChart
                evolutionData={data.appointment_evolution}
                title="Évolution des Rendez-vous"
            />

            {/* Row 4: Completion by Pastor */}
            <HorizontalBarChart
                data={data.completion_by_pastor}
                title="Taux de Complétion par Pasteur"
                subtitle="Performance par agent"
            />
        </div>
    );
}
