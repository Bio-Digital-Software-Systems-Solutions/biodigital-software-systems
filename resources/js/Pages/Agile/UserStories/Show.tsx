import React, { useState } from 'react';
import axios from 'axios';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { StatusBadge } from '@/Components/Agile/StatusBadge';
import { AcceptanceCriteriaList } from '@/Components/Agile/AcceptanceCriteriaList';
import { StoryTasksList } from '@/Components/Agile/StoryTasksList';
import { MoveToSprintDialog } from '@/Components/Agile/MoveToSprintDialog';
import { StoryTask, UserStory } from '@/Types/Agile';
import { ArrowLeftIcon, CheckCircleIcon, ArrowsRightLeftIcon } from '@heroicons/react/24/outline';

interface UserLite {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
}

interface SprintLite {
    id: number;
    name: string;
    status: string;
    start_date?: string | null;
    end_date?: string | null;
}

interface StatusLite {
    id: number;
    name: string;
    color: string;
}

type LoadedUserStory = UserStory & {
    epic?: { id: number; uuid: string; title: string; status: string } | null;
    sprint?: { id: number; name: string; status: string } | null;
    assignee?: { id: number; name: string } | null;
    reporter?: { id: number; name: string } | null;
    story_tasks?: StoryTask[];
};

interface Props {
    story: LoadedUserStory;
    sprints: SprintLite[];
    users: UserLite[];
    statuses: StatusLite[];
}

const displayName = (u: UserLite): string => {
    const full = `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
    return full !== '' ? full : u.email;
};

export default function Show({ story, sprints, users, statuses }: Props) {
    const { t } = useTranslation();
    const [completing, setCompleting] = useState(false);
    const [moveOpen, setMoveOpen] = useState(false);

    const criteria = story.acceptance_criteria ?? [];
    const tasks = story.story_tasks ?? [];

    const complete = async (): Promise<void> => {
        setCompleting(true);
        try {
            await axios.post(route('api.agile.user-stories.complete', story.uuid));
            toast.success('Story terminée.');
            router.reload({ only: ['story'] });
        } catch (e: unknown) {
            if (axios.isAxiosError(e) && e.response?.status === 422) {
                toast.error(e.response.data?.message ?? 'Impossible de terminer la story.');
            } else {
                toast.error('Erreur inattendue.');
            }
        } finally {
            setCompleting(false);
        }
    };

    return (
        <DashboardLayout
            title={story.title}
            actions={
                <div className="flex items-center gap-2">
                    <Link href={route('agile.user-stories.index')}>
                        <Button variant="outline" size="sm">
                            <ArrowLeftIcon className="mr-2 h-4 w-4" />
                            {t('back')}
                        </Button>
                    </Link>
                    <Button variant="outline" size="sm" onClick={() => setMoveOpen(true)}>
                        <ArrowsRightLeftIcon className="mr-2 h-4 w-4" />
                        Sprint
                    </Button>
                    {story.can_be_completed && story.status !== 'done' && (
                        <Button size="sm" onClick={complete} disabled={completing}>
                            <CheckCircleIcon className="mr-2 h-4 w-4" />
                            {completing ? '…' : 'Terminer'}
                        </Button>
                    )}
                </div>
            }
        >
            <Head title={story.title} />

            {/* Header card */}
            <Card className="mb-6">
                <CardHeader>
                    <div className="flex items-start justify-between gap-4">
                        <div className="space-y-2 flex-1">
                            <div className="flex items-center gap-3">
                                <CardTitle>{story.title}</CardTitle>
                                <StatusBadge status={story.status} label={story.status_label} />
                                {story.story_points !== null && (
                                    <span className="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {story.story_points} pts
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {/* Narrative */}
                    <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg mb-6">
                        <p className="italic text-gray-500 text-xs mb-2">
                            {t('agile.user_stories.description')}
                        </p>
                        <p className="text-gray-800 dark:text-gray-200 leading-relaxed">
                            <span className="font-semibold">En tant que</span> {story.as_a},{' '}
                            <span className="font-semibold">je veux</span> {story.i_want},{' '}
                            <span className="font-semibold">afin de</span> {story.so_that}.
                        </p>
                    </div>

                    <dl className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">Epic</dt>
                            <dd className="font-medium">
                                {story.epic ? (
                                    <Link
                                        href={route('agile.epics.show', story.epic.uuid)}
                                        className="text-primary hover:underline"
                                    >
                                        {story.epic.title}
                                    </Link>
                                ) : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">Sprint</dt>
                            <dd className="font-medium">{story.sprint?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">{t('agile.form.owner')}</dt>
                            <dd className="font-medium">{story.assignee?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">Reporter</dt>
                            <dd className="font-medium">{story.reporter?.name ?? '—'}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            {/* Tabs */}
            <Tabs defaultValue="ac">
                <TabsList>
                    <TabsTrigger value="ac">
                        Critères d'acceptation ({criteria.length})
                    </TabsTrigger>
                    <TabsTrigger value="tasks">
                        Tâches techniques ({tasks.length})
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="ac" className="mt-4">
                    <AcceptanceCriteriaList story={story} criteria={criteria} />
                </TabsContent>
                <TabsContent value="tasks" className="mt-4">
                    <StoryTasksList
                        storyUuid={story.uuid}
                        storyId={story.id}
                        tasks={tasks}
                        users={users.map((u) => ({ id: u.id, name: displayName(u) }))}
                        statuses={statuses}
                    />
                </TabsContent>
            </Tabs>

            <MoveToSprintDialog
                open={moveOpen}
                onOpenChange={setMoveOpen}
                storyUuid={story.uuid}
                currentSprintId={story.sprint_id}
                sprints={sprints}
            />
        </DashboardLayout>
    );
}
