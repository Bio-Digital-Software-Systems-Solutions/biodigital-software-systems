import React, { useState } from 'react';
import axios from 'axios';
import { router, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { StatusBadge } from '@/Components/Agile/StatusBadge';
import { TestScenariosList } from '@/Components/Agile/TestScenariosList';
import { AcceptanceCriterion, AcceptanceCriterionStatus, UserStory } from '@/Types/Agile';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowUpIcon,
    ArrowDownIcon,
} from '@heroicons/react/24/outline';

interface Props {
    story: UserStory;
    criteria: AcceptanceCriterion[];
}

type Modal = 'create' | 'edit' | 'validate' | 'reject' | null;

export const AcceptanceCriteriaList: React.FC<Props> = ({ story, criteria }) => {
    const { t } = useTranslation();
    const [modal, setModal] = useState<Modal>(null);
    const [selected, setSelected] = useState<AcceptanceCriterion | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AcceptanceCriterion | null>(null);

    const createForm = useForm<{ title: string; description: string }>({
        title: '',
        description: '',
    });

    const editForm = useForm<{ title: string; description: string }>({
        title: '',
        description: '',
    });

    const [validateNotes, setValidateNotes] = useState('');
    const [rejectNotes, setRejectNotes] = useState('');
    const [pending, setPending] = useState(false);

    const openCreate = () => {
        createForm.reset();
        setModal('create');
    };

    const openEdit = (ac: AcceptanceCriterion) => {
        setSelected(ac);
        editForm.setData({ title: ac.title, description: ac.description });
        setModal('edit');
    };

    const openValidate = (ac: AcceptanceCriterion) => {
        setSelected(ac);
        setValidateNotes('');
        setModal('validate');
    };

    const openReject = (ac: AcceptanceCriterion) => {
        setSelected(ac);
        setRejectNotes('');
        setModal('reject');
    };

    const closeModal = () => {
        setModal(null);
        setSelected(null);
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(
            route('agile.user-stories.acceptance-criteria.store', story.uuid),
            {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            },
        );
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selected) return;
        editForm.patch(route('agile.acceptance-criteria.update', selected.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('agile.acceptance-criteria.destroy', deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const submitValidate = async (): Promise<void> => {
        if (!selected) return;
        setPending(true);
        try {
            await axios.post(route('api.agile.acceptance-criteria.validate', selected.id), {
                notes: validateNotes || null,
            });
            toast.success(t('agile.ac.validated'));
            closeModal();
            router.reload({ only: ['story'] });
        } catch (e: unknown) {
            toast.error(axios.isAxiosError(e) ? e.response?.data?.message ?? t('agile.errors.generic') : t('agile.errors.generic'));
        } finally {
            setPending(false);
        }
    };

    const submitReject = async (): Promise<void> => {
        if (!selected || rejectNotes.trim() === '') return;
        setPending(true);
        try {
            await axios.post(route('api.agile.acceptance-criteria.reject', selected.id), {
                notes: rejectNotes,
            });
            toast.success(t('agile.ac.rejected'));
            closeModal();
            router.reload({ only: ['story'] });
        } catch (e: unknown) {
            toast.error(axios.isAxiosError(e) ? e.response?.data?.message ?? t('agile.errors.generic') : t('agile.errors.generic'));
        } finally {
            setPending(false);
        }
    };

    const swap = async (index: number, direction: -1 | 1): Promise<void> => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= criteria.length) return;

        const orderedIds = criteria.map((c) => c.id);
        [orderedIds[index], orderedIds[targetIndex]] = [orderedIds[targetIndex], orderedIds[index]];

        try {
            await axios.post(route('api.agile.acceptance-criteria.reorder', story.uuid), {
                ordered_ids: orderedIds,
            });
            router.reload({ only: ['story'] });
        } catch {
            toast.error(t('agile.ac.reorder_fail'));
        }
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {t('agile.ac.heading')}
                </h3>
                <Button size="sm" onClick={openCreate}>
                    <PlusIcon className="mr-1 h-4 w-4" />
                    {t('agile.actions.add')}
                </Button>
            </div>

            {criteria.length === 0 ? (
                <p className="text-center text-gray-500 py-10 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    {t('agile.ac.none_cta')}
                </p>
            ) : (
                <ul className="space-y-3">
                    {criteria.map((ac, index) => (
                        <li
                            key={ac.id}
                            className="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <div className="flex items-start gap-3">
                                <div className="flex flex-col items-center gap-1 pt-1">
                                    <button
                                        onClick={() => swap(index, -1)}
                                        disabled={index === 0}
                                        className="text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                        aria-label={t('agile.actions.move_up')}
                                    >
                                        <ArrowUpIcon className="h-4 w-4" />
                                    </button>
                                    <span className="text-xs text-gray-500 font-mono">{ac.position}</span>
                                    <button
                                        onClick={() => swap(index, 1)}
                                        disabled={index === criteria.length - 1}
                                        className="text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                        aria-label={t('agile.actions.move_down')}
                                    >
                                        <ArrowDownIcon className="h-4 w-4" />
                                    </button>
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-medium text-gray-900 dark:text-gray-100">
                                            {ac.title}
                                        </p>
                                        <StatusBadge status={ac.status} label={ac.status_label} />
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-wrap">
                                        {ac.description}
                                    </p>
                                    {ac.validation_notes && (
                                        <p className="text-xs text-gray-500 italic mt-2">
                                            “{ac.validation_notes}”
                                            {ac.validator && ` — ${ac.validator.name}`}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1">
                                    {ac.status !== AcceptanceCriterionStatus.VALIDATED && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => openValidate(ac)}
                                            aria-label={t('agile.actions.validate')}
                                            title={t('agile.actions.validate')}
                                        >
                                            <CheckCircleIcon className="h-4 w-4 text-green-500" />
                                        </Button>
                                    )}
                                    {ac.status !== AcceptanceCriterionStatus.REJECTED && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => openReject(ac)}
                                            aria-label={t('agile.actions.reject')}
                                            title={t('agile.actions.reject')}
                                        >
                                            <XCircleIcon className="h-4 w-4 text-red-500" />
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => openEdit(ac)}
                                        aria-label={t('agile.actions.edit')}
                                    >
                                        <PencilIcon className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => setDeleteTarget(ac)}
                                        aria-label={t('agile.actions.delete')}
                                    >
                                        <TrashIcon className="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </div>

                            <TestScenariosList
                                criterion={ac}
                                scenarios={ac.test_scenarios ?? []}
                            />
                        </li>
                    ))}
                </ul>
            )}

            {/* Create */}
            <Dialog open={modal === 'create'} onOpenChange={(o) => (!o ? closeModal() : undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('agile.ac.create_title')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div>
                            <Label htmlFor="ac-create-title">{t('agile.common.title')} <span className="text-red-500">*</span></Label>
                            <Input
                                id="ac-create-title"
                                value={createForm.data.title}
                                onChange={(e) => createForm.setData('title', e.target.value)}
                                required
                            />
                            {createForm.errors.title && (
                                <p className="text-xs text-red-600 mt-1">{createForm.errors.title}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="ac-create-desc">{t('agile.common.description')} <span className="text-red-500">*</span></Label>
                            <Textarea
                                id="ac-create-desc"
                                rows={4}
                                value={createForm.data.description}
                                onChange={(e) => createForm.setData('description', e.target.value)}
                                required
                            />
                            {createForm.errors.description && (
                                <p className="text-xs text-red-600 mt-1">{createForm.errors.description}</p>
                            )}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeModal}>{t('agile.actions.cancel')}</Button>
                            <Button type="submit" disabled={createForm.processing}>{t('agile.actions.create')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit */}
            <Dialog open={modal === 'edit'} onOpenChange={(o) => (!o ? closeModal() : undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('agile.ac.edit_title')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div>
                            <Label htmlFor="ac-edit-title">{t('agile.common.title')}</Label>
                            <Input
                                id="ac-edit-title"
                                value={editForm.data.title}
                                onChange={(e) => editForm.setData('title', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label htmlFor="ac-edit-desc">{t('agile.common.description')}</Label>
                            <Textarea
                                id="ac-edit-desc"
                                rows={4}
                                value={editForm.data.description}
                                onChange={(e) => editForm.setData('description', e.target.value)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeModal}>{t('agile.actions.cancel')}</Button>
                            <Button type="submit" disabled={editForm.processing}>{t('agile.actions.save')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Validate */}
            <Dialog open={modal === 'validate'} onOpenChange={(o) => (!o ? closeModal() : undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('agile.ac.validate_title')}</DialogTitle>
                        <DialogDescription>{t('agile.ac.validate_description')}</DialogDescription>
                    </DialogHeader>
                    <div className="py-2">
                        <Label htmlFor="validate-notes">{t('agile.ac.notes_label')}</Label>
                        <Textarea
                            id="validate-notes"
                            rows={3}
                            value={validateNotes}
                            onChange={(e) => setValidateNotes(e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={closeModal}>{t('agile.actions.cancel')}</Button>
                        <Button onClick={submitValidate} disabled={pending}>
                            <CheckCircleIcon className="mr-2 h-4 w-4" />
                            {t('agile.actions.validate')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject */}
            <Dialog open={modal === 'reject'} onOpenChange={(o) => (!o ? closeModal() : undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('agile.ac.reject_title')}</DialogTitle>
                        <DialogDescription>{t('agile.ac.reject_description')}</DialogDescription>
                    </DialogHeader>
                    <div className="py-2">
                        <Label htmlFor="reject-notes">{t('agile.ac.notes_label')} <span className="text-red-500">*</span></Label>
                        <Textarea
                            id="reject-notes"
                            rows={4}
                            value={rejectNotes}
                            onChange={(e) => setRejectNotes(e.target.value)}
                            required
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={closeModal}>{t('agile.actions.cancel')}</Button>
                        <Button
                            onClick={submitReject}
                            disabled={pending || rejectNotes.trim() === ''}
                            variant="destructive"
                        >
                            <XCircleIcon className="mr-2 h-4 w-4" />
                            {t('agile.actions.reject')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(o) => (!o ? setDeleteTarget(null) : undefined)}
                onConfirm={handleDelete}
                title={deleteTarget?.title ?? ''}
                description={t('agile.ac.delete_desc')}
            />
        </div>
    );
};
