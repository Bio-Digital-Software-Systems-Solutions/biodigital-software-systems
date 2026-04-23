import React, { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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
import { StoryTask, StoryTaskType } from '@/Types/Agile';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';

interface Props {
    storyUuid: string;
    storyId: number;
    tasks: StoryTask[];
    users: Array<{ id: number; name: string }>;
    statuses: Array<{ id: number; name: string; color: string }>;
}

const workTypeColor: Record<StoryTaskType, string> = {
    [StoryTaskType.DEV]: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    [StoryTaskType.TEST]: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    [StoryTaskType.DEVOPS]: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
    [StoryTaskType.DESIGN]: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-100',
    [StoryTaskType.DOC]: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};

export const StoryTasksList: React.FC<Props> = ({ storyUuid, tasks, users, statuses }) => {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<StoryTask | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<StoryTask | null>(null);

    const form = useForm<{
        title: string;
        description: string;
        work_type: StoryTaskType;
        assigned_to: number | null;
        status_id: number | null;
        priority: 'low' | 'medium' | 'high';
        estimated_hours: number | null;
        actual_hours: number | null;
    }>({
        title: '',
        description: '',
        work_type: StoryTaskType.DEV,
        assigned_to: null,
        status_id: null,
        priority: 'medium',
        estimated_hours: null,
        actual_hours: null,
    });

    const openCreate = () => {
        form.reset();
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (task: StoryTask) => {
        form.setData({
            title: task.title,
            description: task.description ?? '',
            work_type: task.work_type ?? StoryTaskType.DEV,
            assigned_to: task.assigned_to,
            status_id: task.status_id,
            priority: (task.priority as 'low' | 'medium' | 'high') ?? 'medium',
            estimated_hours: task.estimated_hours,
            actual_hours: task.actual_hours,
        });
        setEditing(task);
        setFormOpen(true);
    };

    const close = () => {
        setFormOpen(false);
        setEditing(null);
        form.reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editing) {
            form.patch(route('agile.story-tasks.update', editing.uuid), {
                preserveScroll: true,
                onSuccess: () => close(),
            });
        } else {
            form.post(route('agile.user-stories.story-tasks.store', storyUuid), {
                preserveScroll: true,
                onSuccess: () => close(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('agile.story-tasks.destroy', deleteTarget.uuid), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const assigneeName = (id: number | null): string => {
        if (id === null) return '—';
        return users.find((u) => u.id === id)?.name ?? `#${id}`;
    };

    const statusName = (id: number | null): string => {
        if (id === null) return '—';
        return statuses.find((s) => s.id === id)?.name ?? `#${id}`;
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Tâches techniques
                </h3>
                <Button size="sm" onClick={openCreate}>
                    <PlusIcon className="mr-1 h-4 w-4" />
                    Ajouter
                </Button>
            </div>

            {tasks.length === 0 ? (
                <p className="text-center text-gray-500 py-10 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    Aucune tâche technique.
                </p>
            ) : (
                <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Titre</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead>Assignation</TableHead>
                                <TableHead className="text-right">Heures</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tasks.map((t) => (
                                <TableRow key={t.id}>
                                    <TableCell className="font-medium">{t.title}</TableCell>
                                    <TableCell>
                                        {t.work_type ? (
                                            <span
                                                className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${workTypeColor[t.work_type]}`}
                                            >
                                                {t.work_type_label ?? t.work_type}
                                            </span>
                                        ) : '—'}
                                    </TableCell>
                                    <TableCell>{statusName(t.status_id)}</TableCell>
                                    <TableCell>{assigneeName(t.assigned_to)}</TableCell>
                                    <TableCell className="text-right text-sm text-gray-600 dark:text-gray-400">
                                        {t.actual_hours ?? 0}
                                        {t.estimated_hours !== null ? ` / ${t.estimated_hours}` : ''}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="inline-flex gap-1">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => openEdit(t)}
                                                aria-label="Modifier"
                                            >
                                                <PencilIcon className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => setDeleteTarget(t)}
                                                aria-label="Supprimer"
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
            )}

            <Dialog open={formOpen} onOpenChange={(o) => (!o ? close() : undefined)}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Modifier la tâche' : 'Nouvelle tâche technique'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="st-title">Titre <span className="text-red-500">*</span></Label>
                            <Input
                                id="st-title"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                required
                            />
                            {form.errors.title && (
                                <p className="text-xs text-red-600 mt-1">{form.errors.title}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="st-desc">Description</Label>
                            <Textarea
                                id="st-desc"
                                rows={3}
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="st-work-type">
                                    Type <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={form.data.work_type}
                                    onValueChange={(v) => form.setData('work_type', v as StoryTaskType)}
                                >
                                    <SelectTrigger id="st-work-type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.values(StoryTaskType).map((t) => (
                                            <SelectItem key={t} value={t}>{t}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="st-priority">Priorité</Label>
                                <Select
                                    value={form.data.priority}
                                    onValueChange={(v) => form.setData('priority', v as 'low' | 'medium' | 'high')}
                                >
                                    <SelectTrigger id="st-priority">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Basse</SelectItem>
                                        <SelectItem value="medium">Moyenne</SelectItem>
                                        <SelectItem value="high">Haute</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="st-assignee">Assignation</Label>
                                <Select
                                    value={form.data.assigned_to ? String(form.data.assigned_to) : '__none__'}
                                    onValueChange={(v) =>
                                        form.setData('assigned_to', v === '__none__' ? null : Number(v))
                                    }
                                >
                                    <SelectTrigger id="st-assignee">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">—</SelectItem>
                                        {users.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="st-status">Statut</Label>
                                <Select
                                    value={form.data.status_id ? String(form.data.status_id) : '__none__'}
                                    onValueChange={(v) =>
                                        form.setData('status_id', v === '__none__' ? null : Number(v))
                                    }
                                >
                                    <SelectTrigger id="st-status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">—</SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="st-est">Heures estimées</Label>
                                <Input
                                    id="st-est"
                                    type="number"
                                    min="0"
                                    step="0.25"
                                    value={form.data.estimated_hours ?? ''}
                                    onChange={(e) =>
                                        form.setData(
                                            'estimated_hours',
                                            e.target.value === '' ? null : Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                            {editing && (
                                <div>
                                    <Label htmlFor="st-actual">Heures réelles</Label>
                                    <Input
                                        id="st-actual"
                                        type="number"
                                        min="0"
                                        step="0.25"
                                        value={form.data.actual_hours ?? ''}
                                        onChange={(e) =>
                                            form.setData(
                                                'actual_hours',
                                                e.target.value === '' ? null : Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={close}>Annuler</Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Enregistrer' : 'Créer'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DeleteConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(o) => (!o ? setDeleteTarget(null) : undefined)}
                onConfirm={handleDelete}
                title={deleteTarget?.title ?? ''}
                description="Cette tâche technique sera supprimée."
            />
        </div>
    );
};
