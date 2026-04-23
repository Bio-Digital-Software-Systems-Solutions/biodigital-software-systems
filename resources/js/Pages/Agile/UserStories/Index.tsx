import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { StatusBadge } from '@/Components/Agile/StatusBadge';
import { PaginatedData } from '@/Types';
import { UserStory, UserStoryFilters, UserStoryStatus } from '@/Types/Agile';
import { PlusIcon, PencilIcon, TrashIcon, FunnelIcon } from '@heroicons/react/24/outline';

interface EpicLite {
    id: number;
    title: string;
}

interface SprintLite {
    id: number;
    name: string;
    status: string;
}

interface UserLite {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
}

interface Props {
    stories: PaginatedData<UserStory>;
    epics: EpicLite[];
    sprints: SprintLite[];
    users: UserLite[];
    filters: UserStoryFilters;
}

const displayName = (user: UserLite): string => {
    const full = `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
    return full !== '' ? full : user.email;
};

export default function Index({ stories, epics, sprints, users, filters }: Props) {
    const { t } = useTranslation();
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<UserStory | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<UserStory | null>(null);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        epic_id: null as number | null,
        sprint_id: null as number | null,
        assignee_id: null as number | null,
        reporter_id: 0,
        title: '',
        as_a: '',
        i_want: '',
        so_that: '',
        story_points: null as number | null,
        priority: 3,
    });

    const openCreate = () => {
        reset();
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (story: UserStory) => {
        setData({
            epic_id: story.epic_id,
            sprint_id: story.sprint_id,
            assignee_id: story.assignee_id,
            reporter_id: story.reporter_id,
            title: story.title,
            as_a: story.as_a,
            i_want: story.i_want,
            so_that: story.so_that,
            story_points: story.story_points,
            priority: story.priority,
        });
        setEditing(story);
        setFormOpen(true);
    };

    const closeForm = () => {
        setFormOpen(false);
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            patch(route('agile.user-stories.update', editing.uuid), {
                onSuccess: () => closeForm(),
                preserveScroll: true,
            });
        } else {
            post(route('agile.user-stories.store'), {
                onSuccess: () => closeForm(),
                preserveScroll: true,
            });
        }
    };

    const applyFilter = (key: keyof UserStoryFilters, value: string) => {
        router.get(
            route('agile.user-stories.index'),
            { ...filters, [key]: value === '__all__' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        router.get(route('agile.user-stories.index'), {}, { preserveState: true, replace: true });
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('agile.user-stories.destroy', deleteTarget.uuid), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const epicTitle = (story: UserStory): string => {
        if (!story.epic_id) return '—';
        const epic = epics.find((e) => e.id === story.epic_id);
        return epic ? epic.title : `#${story.epic_id}`;
    };

    const sprintName = (story: UserStory): string => {
        if (!story.sprint_id) return '—';
        const sprint = sprints.find((s) => s.id === story.sprint_id);
        return sprint ? sprint.name : `#${story.sprint_id}`;
    };

    const assigneeName = (story: UserStory): string => {
        if (!story.assignee_id) return '—';
        const user = users.find((u) => u.id === story.assignee_id);
        return user ? displayName(user) : `#${story.assignee_id}`;
    };

    return (
        <DashboardLayout
            title={t('agile.user_stories.title')}
            description={t('agile.user_stories.description')}
            actions={
                <Button onClick={openCreate}>
                    <PlusIcon className="mr-2 h-4 w-4" />
                    {t('create')}
                </Button>
            }
        >
            <Head title={t('agile.user_stories.title')} />

            <div className="mb-4 flex flex-wrap items-end gap-3 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <FunnelIcon className="h-5 w-5 text-gray-400" />

                <div className="flex-1 min-w-[180px]">
                    <Label className="text-xs">{t('agile.common.epic')}</Label>
                    <Select
                        value={(filters.epic_id as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('epic_id', v)}
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">—</SelectItem>
                            {epics.map((e) => (
                                <SelectItem key={e.id} value={String(e.id)}>{e.title}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex-1 min-w-[180px]">
                    <Label className="text-xs">{t('agile.common.sprint')}</Label>
                    <Select
                        value={(filters.sprint_id as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('sprint_id', v)}
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">—</SelectItem>
                            {sprints.map((s) => (
                                <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex-1 min-w-[180px]">
                    <Label className="text-xs">{t('agile.form.status')}</Label>
                    <Select
                        value={(filters.status as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('status', v)}
                    >
                        <SelectTrigger><SelectValue placeholder={t('agile.filter.all_statuses')} /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">{t('agile.filter.all_statuses')}</SelectItem>
                            {Object.values(UserStoryStatus).map((s) => (
                                <SelectItem key={s} value={s}>{t(`agile.status.${s}`)}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex-1 min-w-[200px]">
                    <Label className="text-xs">{t('agile.form.owner')}</Label>
                    <Select
                        value={(filters.assignee_id as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('assignee_id', v)}
                    >
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">—</SelectItem>
                            {users.map((u) => (
                                <SelectItem key={u.id} value={String(u.id)}>{displayName(u)}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Button variant="outline" size="sm" onClick={clearFilters}>{t('clear')}</Button>
            </div>

            <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('agile.common.title')}</TableHead>
                            <TableHead>{t('agile.common.epic')}</TableHead>
                            <TableHead>{t('agile.common.sprint')}</TableHead>
                            <TableHead>{t('agile.common.status')}</TableHead>
                            <TableHead>{t('agile.common.assignee')}</TableHead>
                            <TableHead>{t('agile.common.points')}</TableHead>
                            <TableHead>{t('agile.common.ac_short')}</TableHead>
                            <TableHead className="text-right">{t('agile.actions.view')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {stories.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} className="text-center text-gray-500 py-10">
                                    —
                                </TableCell>
                            </TableRow>
                        )}
                        {stories.data.map((story) => (
                            <TableRow key={story.id}>
                                <TableCell className="font-medium">
                                    <Link href={route('agile.user-stories.show', story.uuid)} className="text-primary hover:underline">
                                        {story.title}
                                    </Link>
                                </TableCell>
                                <TableCell>{epicTitle(story)}</TableCell>
                                <TableCell>{sprintName(story)}</TableCell>
                                <TableCell>
                                    <StatusBadge status={story.status} label={story.status_label} />
                                </TableCell>
                                <TableCell>{assigneeName(story)}</TableCell>
                                <TableCell>{story.story_points ?? '—'}</TableCell>
                                <TableCell>{story.acceptance_criteria_count ?? 0}</TableCell>
                                <TableCell className="text-right">
                                    <div className="inline-flex items-center gap-1">
                                        <Button size="sm" variant="ghost" onClick={() => openEdit(story)} aria-label={t('edit')}>
                                            <PencilIcon className="h-4 w-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" onClick={() => setDeleteTarget(story)} aria-label={t('delete')}>
                                            <TrashIcon className="h-4 w-4 text-red-500" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {stories.meta.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        {stories.meta.from}-{stories.meta.to} / {stories.meta.total}
                    </span>
                    <div className="inline-flex gap-2">
                        {stories.links.prev && (
                            <Link href={stories.links.prev} preserveScroll>
                                <Button variant="outline" size="sm">{t('previous')}</Button>
                            </Link>
                        )}
                        {stories.links.next && (
                            <Link href={stories.links.next} preserveScroll>
                                <Button variant="outline" size="sm">{t('next')}</Button>
                            </Link>
                        )}
                    </div>
                </div>
            )}

            {/* Create / Edit dialog */}
            <Dialog open={formOpen} onOpenChange={(open) => (open ? setFormOpen(true) : closeForm())}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? t('edit') : t('create')}
                        </DialogTitle>
                        <DialogDescription>{t('agile.user_stories.description')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="title">{t('agile.form.title')} <span className="text-red-500">*</span></Label>
                            <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                            {errors.title && <p className="text-xs text-red-600 mt-1">{errors.title}</p>}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <Label htmlFor="as_a">{t('agile.narrative.as_a')} <span className="text-red-500">*</span></Label>
                                <Input id="as_a" value={data.as_a} onChange={(e) => setData('as_a', e.target.value)} required />
                                {errors.as_a && <p className="text-xs text-red-600 mt-1">{errors.as_a}</p>}
                            </div>
                            <div>
                                <Label htmlFor="i_want">{t('agile.narrative.i_want')} <span className="text-red-500">*</span></Label>
                                <Input id="i_want" value={data.i_want} onChange={(e) => setData('i_want', e.target.value)} required />
                                {errors.i_want && <p className="text-xs text-red-600 mt-1">{errors.i_want}</p>}
                            </div>
                            <div>
                                <Label htmlFor="so_that">{t('agile.narrative.so_that')} <span className="text-red-500">*</span></Label>
                                <Input id="so_that" value={data.so_that} onChange={(e) => setData('so_that', e.target.value)} required />
                                {errors.so_that && <p className="text-xs text-red-600 mt-1">{errors.so_that}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="epic_id">{t('agile.common.epic')}</Label>
                                <Select
                                    value={data.epic_id ? String(data.epic_id) : '__none__'}
                                    onValueChange={(v) => setData('epic_id', v === '__none__' ? null : Number(v))}
                                >
                                    <SelectTrigger id="epic_id"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">—</SelectItem>
                                        {epics.map((e) => (
                                            <SelectItem key={e.id} value={String(e.id)}>{e.title}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="assignee_id">{t('agile.common.assignee')}</Label>
                                <Select
                                    value={data.assignee_id ? String(data.assignee_id) : '__none__'}
                                    onValueChange={(v) => setData('assignee_id', v === '__none__' ? null : Number(v))}
                                >
                                    <SelectTrigger id="assignee_id"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">—</SelectItem>
                                        {users.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>{displayName(u)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <Label htmlFor="reporter_id">{t('agile.common.reporter')} <span className="text-red-500">*</span></Label>
                                <Select
                                    value={data.reporter_id ? String(data.reporter_id) : ''}
                                    onValueChange={(v) => setData('reporter_id', Number(v))}
                                >
                                    <SelectTrigger id="reporter_id"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {users.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>{displayName(u)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.reporter_id && <p className="text-xs text-red-600 mt-1">{errors.reporter_id}</p>}
                            </div>
                            <div>
                                <Label htmlFor="story_points">{t('agile.common.points')}</Label>
                                <Input
                                    id="story_points"
                                    type="number"
                                    min="0"
                                    max="999"
                                    value={data.story_points ?? ''}
                                    onChange={(e) => setData('story_points', e.target.value === '' ? null : Number(e.target.value))}
                                />
                            </div>
                            <div>
                                <Label htmlFor="priority">{t('agile.form.priority')}</Label>
                                <Select value={String(data.priority)} onValueChange={(v) => setData('priority', Number(v))}>
                                    <SelectTrigger id="priority"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {[1, 2, 3, 4, 5].map((p) => (
                                            <SelectItem key={p} value={String(p)}>{p}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeForm}>{t('cancel')}</Button>
                            <Button type="submit" disabled={processing}>{t('save')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DeleteConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => (!open ? setDeleteTarget(null) : undefined)}
                onConfirm={handleDelete}
                title={deleteTarget?.title ?? ''}
                description={t('agile.delete.irreversible')}
            />
        </DashboardLayout>
    );
}
