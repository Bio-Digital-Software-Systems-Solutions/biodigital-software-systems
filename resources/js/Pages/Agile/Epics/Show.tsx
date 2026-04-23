import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { StatusBadge } from '@/Components/Agile/StatusBadge';
import { Epic, UserStory } from '@/Types/Agile';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface Props {
    epic: Epic & { user_stories?: UserStory[] };
}

export default function Show({ epic }: Props) {
    const { t } = useTranslation();
    const stories = epic.user_stories ?? [];

    return (
        <DashboardLayout
            title={epic.title}
            description={epic.business_value ?? ''}
            actions={
                <Link href={route('agile.epics.index')}>
                    <Button variant="outline" size="sm">
                        <ArrowLeftIcon className="mr-2 h-4 w-4" />
                        {t('back')}
                    </Button>
                </Link>
            }
        >
            <Head title={epic.title} />

            {/* Header card */}
            <Card className="mb-6">
                <CardHeader>
                    <div className="flex items-start justify-between">
                        <div className="space-y-1">
                            <CardTitle className="flex items-center gap-3">
                                {epic.title}
                                <StatusBadge status={epic.status} label={epic.status_label} />
                            </CardTitle>
                            {epic.description && (
                                <CardDescription>{epic.description}</CardDescription>
                            )}
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">
                                {t('agile.form.owner')}
                            </dt>
                            <dd className="font-medium">{epic.owner?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">
                                {t('agile.form.priority')}
                            </dt>
                            <dd className="font-medium">{epic.priority}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">
                                {t('agile.form.target_date')}
                            </dt>
                            <dd className="font-medium">{epic.target_date ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500 dark:text-gray-400">
                                {t('agile.epics.completion')}
                            </dt>
                            <dd>
                                <div className="flex items-center gap-2">
                                    <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-green-500 h-2 rounded-full"
                                            style={{ width: `${epic.completion_percentage}%` }}
                                        />
                                    </div>
                                    <span className="text-xs font-medium">
                                        {epic.completion_percentage}%
                                    </span>
                                </div>
                            </dd>
                        </div>
                    </dl>

                    {epic.business_value && (
                        <div className="mt-6">
                            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {t('agile.form.business_value')}
                            </h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                {epic.business_value}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* User stories list */}
            <Card>
                <CardHeader>
                    <CardTitle>{t('agile.epics.stories')}</CardTitle>
                    <CardDescription>
                        {stories.length > 0
                            ? `${stories.length} ${t('agile.epics.stories').toLowerCase()}`
                            : t('agile.epics.no_stories')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {stories.length === 0 && (
                        <p className="text-center text-gray-500 py-8">
                            {t('agile.epics.no_stories')}
                        </p>
                    )}
                    {stories.length > 0 && (
                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                            {stories.map((story) => (
                                <li key={story.id} className="py-3">
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <Link
                                                href={route('agile.user-stories.show', story.uuid)}
                                                className="font-medium text-primary hover:underline truncate block"
                                            >
                                                {story.title}
                                            </Link>
                                            <p className="text-xs text-gray-500 mt-0.5 italic">
                                                {t('agile.user_stories.description')}
                                            </p>
                                            <p className="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                                <span className="font-semibold">En tant que</span>{' '}
                                                {story.as_a},{' '}
                                                <span className="font-semibold">je veux</span>{' '}
                                                {story.i_want},{' '}
                                                <span className="font-semibold">afin de</span>{' '}
                                                {story.so_that}.
                                            </p>
                                        </div>
                                        <div className="flex flex-shrink-0 items-center gap-3">
                                            {story.story_points !== null && (
                                                <span className="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    {story.story_points} pts
                                                </span>
                                            )}
                                            <StatusBadge
                                                status={story.status}
                                                label={story.status_label}
                                            />
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </DashboardLayout>
    );
}
