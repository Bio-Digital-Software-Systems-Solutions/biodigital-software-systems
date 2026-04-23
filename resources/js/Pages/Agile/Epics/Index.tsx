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
import { Epic, EpicFilters, EpicStatus } from '@/Types/Agile';
import { PlusIcon, PencilIcon, TrashIcon, FunnelIcon } from '@heroicons/react/24/outline';

interface ProjectLite {
    id: number;
    name: string;
}

interface UserLite {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
}

interface Props {
    epics: PaginatedData<Epic>;
    projects: ProjectLite[];
    users: UserLite[];
    filters: EpicFilters;
}

const displayName = (user: UserLite): string => {
    const full = `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
    return full !== '' ? full : user.email;
};

const priorityLabel = (priority: number): string => {
    switch (priority) {
        case 1:
            return 'Critique';
        case 2:
            return 'Haute';
        case 3:
            return 'Moyenne';
        case 4:
            return 'Basse';
        default:
            return 'Très basse';
    }
};

export default function Index({ epics, projects, users, filters }: Props) {
    const { t } = useTranslation();

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Epic | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Epic | null>(null);

    const {
        data,
        setData,
        post,
        patch,
        processing,
        errors,
        reset,
    } = useForm({
        project_id: '' as string | number,
        owner_id: '' as string | number,
        title: '',
        description: '',
        business_value: '',
        status: EpicStatus.DRAFT as EpicStatus,
        priority: 3,
        target_date: '',
    });

    const openCreate = () => {
        reset();
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (epic: Epic) => {
        setData({
            project_id: epic.project_id,
            owner_id: epic.owner_id,
            title: epic.title,
            description: epic.description ?? '',
            business_value: epic.business_value ?? '',
            status: epic.status,
            priority: epic.priority,
            target_date: epic.target_date ?? '',
        });
        setEditing(epic);
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
            patch(route('agile.epics.update', editing.uuid), {
                onSuccess: () => closeForm(),
                preserveScroll: true,
            });
        } else {
            post(route('agile.epics.store'), {
                onSuccess: () => closeForm(),
                preserveScroll: true,
            });
        }
    };

    const applyFilter = (key: keyof EpicFilters, value: string) => {
        router.get(
            route('agile.epics.index'),
            { ...filters, [key]: value === '__all__' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        router.get(route('agile.epics.index'), {}, { preserveState: true, replace: true });
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('agile.epics.destroy', deleteTarget.uuid), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const ownerName = (epic: Epic): string => {
        if (epic.owner) return epic.owner.name;
        const user = users.find((u) => u.id === epic.owner_id);
        return user ? displayName(user) : '—';
    };

    const projectName = (epic: Epic): string => {
        const project = projects.find((p) => p.id === epic.project_id);
        return project ? project.name : `#${epic.project_id}`;
    };

    return (
        <DashboardLayout
            title={t('agile.epics.title')}
            description={t('agile.epics.description')}
            actions={
                <Button onClick={openCreate}>
                    <PlusIcon className="mr-2 h-4 w-4" />
                    {t('agile.epics.create')}
                </Button>
            }
        >
            <Head title={t('agile.epics.title')} />

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-end gap-3 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <FunnelIcon className="h-5 w-5 text-gray-400" />

                <div className="flex-1 min-w-[200px]">
                    <Label className="text-xs">{t('agile.form.project')}</Label>
                    <Select
                        value={(filters.project_id as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('project_id', v)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('agile.filter.all_projects')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">{t('agile.filter.all_projects')}</SelectItem>
                            {projects.map((p) => (
                                <SelectItem key={p.id} value={String(p.id)}>
                                    {p.name}
                                </SelectItem>
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
                        <SelectTrigger>
                            <SelectValue placeholder={t('agile.filter.all_statuses')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">{t('agile.filter.all_statuses')}</SelectItem>
                            {Object.values(EpicStatus).map((s) => (
                                <SelectItem key={s} value={s}>
                                    {t(`agile.status.${s}`)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex-1 min-w-[200px]">
                    <Label className="text-xs">{t('agile.form.owner')}</Label>
                    <Select
                        value={(filters.owner_id as string | undefined) ?? '__all__'}
                        onValueChange={(v) => applyFilter('owner_id', v)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder={t('agile.filter.all_owners')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">{t('agile.filter.all_owners')}</SelectItem>
                            {users.map((u) => (
                                <SelectItem key={u.id} value={String(u.id)}>
                                    {displayName(u)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Button variant="outline" size="sm" onClick={clearFilters}>
                    {t('clear')}
                </Button>
            </div>

            {/* Table */}
            <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('agile.form.title')}</TableHead>
                            <TableHead>{t('agile.form.project')}</TableHead>
                            <TableHead>{t('agile.form.status')}</TableHead>
                            <TableHead>{t('agile.form.owner')}</TableHead>
                            <TableHead>{t('agile.form.priority')}</TableHead>
                            <TableHead>{t('agile.epics.completion')}</TableHead>
                            <TableHead>{t('agile.epics.stories')}</TableHead>
                            <TableHead className="text-right">{t('view')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {epics.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={8} className="text-center text-gray-500 py-10">
                                    {t('agile.epics.none')}
                                </TableCell>
                            </TableRow>
                        )}
                        {epics.data.map((epic) => (
                            <TableRow key={epic.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={route('agile.epics.show', epic.uuid)}
                                        className="text-primary hover:underline"
                                    >
                                        {epic.title}
                                    </Link>
                                </TableCell>
                                <TableCell>{projectName(epic)}</TableCell>
                                <TableCell>
                                    <StatusBadge status={epic.status} label={epic.status_label} />
                                </TableCell>
                                <TableCell>{ownerName(epic)}</TableCell>
                                <TableCell>{priorityLabel(epic.priority)}</TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        <div className="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div
                                                className="bg-green-500 h-2 rounded-full"
                                                style={{ width: `${epic.completion_percentage}%` }}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-600 dark:text-gray-400">
                                            {epic.completion_percentage}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>{epic.user_stories_count ?? 0}</TableCell>
                                <TableCell className="text-right">
                                    <div className="inline-flex items-center gap-1">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => openEdit(epic)}
                                            aria-label={t('edit')}
                                        >
                                            <PencilIcon className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => setDeleteTarget(epic)}
                                            aria-label={t('delete')}
                                        >
                                            <TrashIcon className="h-4 w-4 text-red-500" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            {epics.meta.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        {epics.meta.from}-{epics.meta.to} / {epics.meta.total}
                    </span>
                    <div className="inline-flex gap-2">
                        {epics.links.prev && (
                            <Link href={epics.links.prev} preserveScroll>
                                <Button variant="outline" size="sm">
                                    {t('previous')}
                                </Button>
                            </Link>
                        )}
                        {epics.links.next && (
                            <Link href={epics.links.next} preserveScroll>
                                <Button variant="outline" size="sm">
                                    {t('next')}
                                </Button>
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
                            {editing ? t('agile.epics.edit') : t('agile.epics.create')}
                        </DialogTitle>
                        <DialogDescription>{t('agile.epics.description')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="project_id">
                                    {t('agile.form.project')} <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.project_id ? String(data.project_id) : ''}
                                    onValueChange={(v) => setData('project_id', Number(v))}
                                >
                                    <SelectTrigger id="project_id">
                                        <SelectValue placeholder={t('agile.form.project')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {projects.map((p) => (
                                            <SelectItem key={p.id} value={String(p.id)}>
                                                {p.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.project_id && (
                                    <p className="text-xs text-red-600 mt-1">{errors.project_id}</p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="owner_id">
                                    {t('agile.form.owner')} <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.owner_id ? String(data.owner_id) : ''}
                                    onValueChange={(v) => setData('owner_id', Number(v))}
                                >
                                    <SelectTrigger id="owner_id">
                                        <SelectValue placeholder={t('agile.form.owner')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                {displayName(u)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.owner_id && (
                                    <p className="text-xs text-red-600 mt-1">{errors.owner_id}</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="title">
                                {t('agile.form.title')} <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                required
                            />
                            {errors.title && (
                                <p className="text-xs text-red-600 mt-1">{errors.title}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="description">{t('agile.form.description')}</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                            />
                        </div>

                        <div>
                            <Label htmlFor="business_value">{t('agile.form.business_value')}</Label>
                            <Textarea
                                id="business_value"
                                value={data.business_value}
                                onChange={(e) => setData('business_value', e.target.value)}
                                rows={2}
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <Label htmlFor="status">{t('agile.form.status')}</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(v) => setData('status', v as EpicStatus)}
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.values(EpicStatus).map((s) => (
                                            <SelectItem key={s} value={s}>
                                                {t(`agile.status.${s}`)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="priority">{t('agile.form.priority')}</Label>
                                <Select
                                    value={String(data.priority)}
                                    onValueChange={(v) => setData('priority', Number(v))}
                                >
                                    <SelectTrigger id="priority">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[1, 2, 3, 4, 5].map((p) => (
                                            <SelectItem key={p} value={String(p)}>
                                                {p} — {priorityLabel(p)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="target_date">{t('agile.form.target_date')}</Label>
                                <Input
                                    id="target_date"
                                    type="date"
                                    value={data.target_date}
                                    onChange={(e) => setData('target_date', e.target.value)}
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeForm}>
                                {t('cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {t('save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DeleteConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => (!open ? setDeleteTarget(null) : undefined)}
                onConfirm={handleDelete}
                title={t('agile.epics.delete.title')}
                description={t('agile.epics.delete.description')}
            />
        </DashboardLayout>
    );
}
