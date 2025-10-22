import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    PlusIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    UserIcon,
    ClockIcon,
    EllipsisVerticalIcon,
    CheckIcon,
    ListBulletIcon,
    Bars3BottomLeftIcon
} from '@heroicons/react/24/outline';
import { Program, PageProps } from '@/Types';
import ProgramStepForm from '@/Components/Programs/ProgramStepForm';
import StepDetailModal from '@/Components/Programs/StepDetailModal';
import { TimelineView, ListView, KanbanView } from '@/Components/Programs/ProgramViews';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Progress } from '@/Components/ui/progress';
import { isAdmin } from '@/Enums/Role';
import { userHasPermission } from '@/Enums/Permission';

interface Props extends PageProps {
    program: Program & {
        steps?: any[];
    };
    users?: any[];
    statuses?: any[];
}

export default function Show({ program, users = [], statuses = [] }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [showStepForm, setShowStepForm] = useState(false);
    const [editingStep, setEditingStep] = useState<any>(null);
    const [showStepDetail, setShowStepDetail] = useState(false);
    const [selectedStep, setSelectedStep] = useState<any>(null);
    const [viewMode, setViewMode] = useState<'list' | 'timeline' | 'kanban'>('timeline');

    // Permission checks
    const canEditProgram = userHasPermission(auth.user, 'edit programs');

    const handleStepClick = (step: any) => {
        setSelectedStep(step);
        setShowStepDetail(true);
    };

    const handleStepEdit = () => {
        setEditingStep(selectedStep);
        setShowStepDetail(false);
        setShowStepForm(true);
    };

    const getStatusBadgeClass = (status: string) => {
        const classes: { [key: string]: string } = {
            'draft': 'bg-gray-100 text-gray-800 border border-gray-200',
            'active': 'bg-primary text-white',
            'paused': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            'completed': 'bg-green-600 text-white',
            'cancelled': 'bg-red-100 text-red-800 border border-red-200',
            'pending': 'bg-orange-100 text-orange-800 border border-orange-200',
            'in_progress': 'bg-primary text-white',
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    };

    const getPriorityBadgeClass = (priority: string) => {
        const classes: { [key: string]: string } = {
            'low': 'bg-green-100 text-green-800',
            'medium': 'bg-yellow-100 text-yellow-800',
            'high': 'bg-red-100 text-red-800',
        };
        return classes[priority] || 'bg-gray-100 text-gray-800';
    };

    const getTaskStatusBadgeClass = (status: string) => {
        const classes: { [key: string]: string } = {
            'todo': 'bg-orange-100 text-orange-800 border border-orange-200',
            'in_progress': 'bg-primary text-white',
            'completed': 'bg-green-600 text-white',
            'cancelled': 'bg-gray-100 text-gray-800',
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    const formatDateTime = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const totalSteps = program.steps?.length || 0;
    const totalTasks = program.steps?.reduce((sum, step) => sum + (step.tasks?.length || 0), 0) || 0;
    const totalParticipants = program.steps?.reduce((sum, step) => sum + (step.users?.length || 0), 0) || 0;
    const completedTasks = program.steps?.reduce((sum, step) =>
        sum + (step.tasks?.filter((t: any) => t.status?.name === 'completed').length || 0), 0) || 0;
    const progressPercentage = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

    return (
        <DashboardLayout>
            <Head title={program.name} />

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('programs.index')}
                        className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 flex items-center"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-1" />
                        Back to Programs
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {program.name}
                    </h1>
                </div>
                <div className="flex items-center gap-3">
                    {/* View Mode Switcher */}
                    <div className="flex items-center bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button
                            onClick={() => setViewMode('list')}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors ${
                                viewMode === 'list'
                                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                            }`}
                        >
                            <ListBulletIcon className="w-4 h-4" />
                            Liste
                        </button>
                        <button
                            onClick={() => setViewMode('timeline')}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium border-x border-gray-200 dark:border-gray-700 transition-colors ${
                                viewMode === 'timeline'
                                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                            }`}
                        >
                            <CalendarIcon className="w-4 h-4" />
                            Timeline
                        </button>
                        <button
                            onClick={() => setViewMode('kanban')}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors ${
                                viewMode === 'kanban'
                                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                            }`}
                        >
                            <Bars3BottomLeftIcon className="w-4 h-4" />
                            Kanban
                        </button>
                    </div>

                    {canEditProgram && (
                        <Link
                            href={route('programs.edit', program.uuid)}
                            className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-md"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Program
                        </Link>
                    )}
                </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Content - Left Side */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Description Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                {program.description || 'No description provided.'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Progress Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Progress</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">Overall Progress</span>
                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                    {progressPercentage}%
                                </span>
                            </div>
                            <Progress value={progressPercentage} className="h-2" />

                            {/* Statistics */}
                            <div className="grid grid-cols-3 gap-4 text-center pt-2">
                                <div>
                                    <div className="text-2xl font-bold text-primary">{totalSteps}</div>
                                    <div className="text-xs text-gray-500 dark:text-gray-400">Étapes</div>
                                </div>
                                <div>
                                    <div className="text-2xl font-bold text-green-600">{totalTasks}</div>
                                    <div className="text-xs text-gray-500 dark:text-gray-400">Tâches</div>
                                </div>
                                <div>
                                    <div className="text-2xl font-bold text-purple-600">{totalParticipants}</div>
                                    <div className="text-xs text-gray-500 dark:text-gray-400">Intervenants</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Steps Section - Changes based on viewMode */}
                    <Card>
                        <CardHeader className="pb-4">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">
                                    Programme {viewMode === 'timeline' ? 'Timeline' : viewMode === 'kanban' ? 'Kanban' : 'Liste'} ({totalSteps} étapes)
                                </CardTitle>
                                {viewMode !== 'kanban' && (
                                    <Button
                                        size="sm"
                                        onClick={() => setShowStepForm(true)}
                                        className="bg-primary hover:bg-primary text-white"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-1" />
                                        Ajouter une étape
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {program.steps && program.steps.length > 0 ? (
                                <>
                                    {viewMode === 'timeline' && (
                                        <TimelineView
                                            steps={program.steps}
                                            getStatusBadgeClass={getStatusBadgeClass}
                                            getPriorityBadgeClass={getPriorityBadgeClass}
                                            getTaskStatusBadgeClass={getTaskStatusBadgeClass}
                                            formatDateTime={formatDateTime}
                                            onStepClick={handleStepClick}
                                            statuses={statuses}
                                            programId={program.uuid}
                                        />
                                    )}

                                    {viewMode === 'list' && (
                                        <ListView
                                            steps={program.steps}
                                            getStatusBadgeClass={getStatusBadgeClass}
                                            getPriorityBadgeClass={getPriorityBadgeClass}
                                            getTaskStatusBadgeClass={getTaskStatusBadgeClass}
                                            formatDateTime={formatDateTime}
                                            onStepClick={handleStepClick}
                                            statuses={statuses}
                                            programId={program.uuid}
                                        />
                                    )}

                                    {viewMode === 'kanban' && (
                                        <KanbanView
                                            steps={program.steps}
                                            getStatusBadgeClass={getStatusBadgeClass}
                                            getPriorityBadgeClass={getPriorityBadgeClass}
                                            getTaskStatusBadgeClass={getTaskStatusBadgeClass}
                                            formatDateTime={formatDateTime}
                                            onStepClick={handleStepClick}
                                            statuses={statuses}
                                            programId={program.uuid}
                                        />
                                    )}
                                </>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">
                                        Aucune étape définie pour ce programme.
                                    </p>
                                    <Button
                                        size="sm"
                                        onClick={() => setShowStepForm(true)}
                                        className="bg-primary hover:bg-primary text-white"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-1" />
                                        Créer la première étape
                                    </Button>
                                </div>
                            )}

                            {/* Add Step Button at Bottom */}
                            {program.steps && program.steps.length > 0 && viewMode !== 'kanban' && (
                                <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <button
                                        onClick={() => setShowStepForm(true)}
                                        className="w-full flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
                                    >
                                        <PlusIcon className="w-4 h-4" />
                                        Ajouter une étape
                                    </button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Sidebar - Right Side */}
                <div className="space-y-6">
                    {/* Status & Priority */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Status & Priority</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                                <Badge className={`${getStatusBadgeClass(program.status)} text-sm px-3 py-1`}>
                                    {program.status}
                                </Badge>
                            </div>
                            <div>
                                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">Priority</div>
                                <Badge className={`${getPriorityBadgeClass(program.priority)} text-sm px-3 py-1`}>
                                    {program.priority}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center gap-1">
                                    <CalendarIcon className="w-3 h-3" />
                                    Duration:
                                </div>
                                <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {formatDate(program.start_date)} - {formatDate(program.end_date)}
                                </div>
                            </div>

                            {program.budget && (
                                <div>
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center gap-1">
                                        <CurrencyDollarIcon className="w-3 h-3" />
                                        Budget:
                                    </div>
                                    <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        ${program.budget.toLocaleString()}
                                    </div>
                                </div>
                            )}

                            <div>
                                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1 flex items-center gap-1">
                                    <UserIcon className="w-3 h-3" />
                                    Created by:
                                </div>
                                <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {program.user?.full_name || 'Unknown'}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>

            {/* Step Form Modal */}
            {showStepForm && (
                <ProgramStepForm
                    programId={program.uuid}
                    step={editingStep}
                    users={users}
                    onClose={() => {
                        setShowStepForm(false);
                        setEditingStep(null);
                    }}
                />
            )}

            {/* Step Detail Modal */}
            {showStepDetail && selectedStep && (
                <StepDetailModal
                    step={selectedStep}
                    programId={program.uuid}
                    users={users}
                    statuses={statuses}
                    onClose={() => {
                        setShowStepDetail(false);
                        setSelectedStep(null);
                    }}
                    onEdit={handleStepEdit}
                    getStatusBadgeClass={getStatusBadgeClass}
                    getPriorityBadgeClass={getPriorityBadgeClass}
                    getTaskStatusBadgeClass={getTaskStatusBadgeClass}
                    formatDateTime={formatDateTime}
                />
            )}
        </DashboardLayout>
    );
}
