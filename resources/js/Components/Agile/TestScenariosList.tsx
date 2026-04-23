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
import { AcceptanceCriterion, TestScenario, TestScenarioExecutionStatus } from '@/Types/Agile';
import { PlusIcon, PencilIcon, TrashIcon, PlayIcon } from '@heroicons/react/24/outline';

interface Props {
    criterion: AcceptanceCriterion;
    scenarios: TestScenario[];
}

type Mode = 'gherkin' | 'free_form';
type Modal = 'create' | 'edit' | 'run' | null;

export const TestScenariosList: React.FC<Props> = ({ criterion, scenarios }) => {
    const { t } = useTranslation();
    const [modal, setModal] = useState<Modal>(null);
    const [selected, setSelected] = useState<TestScenario | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TestScenario | null>(null);
    const [mode, setMode] = useState<Mode>('gherkin');
    const [pending, setPending] = useState(false);

    const createForm = useForm<{
        title: string;
        given: string;
        when: string;
        then: string;
        free_form: string;
        automated_test_ref: string;
    }>({
        title: '',
        given: '',
        when: '',
        then: '',
        free_form: '',
        automated_test_ref: '',
    });

    const editForm = useForm<{
        title: string;
        given: string;
        when: string;
        then: string;
        free_form: string;
        automated_test_ref: string;
    }>({
        title: '',
        given: '',
        when: '',
        then: '',
        free_form: '',
        automated_test_ref: '',
    });

    const [runStatus, setRunStatus] = useState<'passed' | 'failed' | 'blocked'>('passed');
    const [runNotes, setRunNotes] = useState('');

    const openCreate = () => {
        createForm.reset();
        setMode('gherkin');
        setModal('create');
    };

    const openEdit = (s: TestScenario) => {
        setSelected(s);
        editForm.setData({
            title: s.title,
            given: s.given ?? '',
            when: s.when ?? '',
            then: s.then ?? '',
            free_form: s.free_form ?? '',
            automated_test_ref: s.automated_test_ref ?? '',
        });
        setMode(s.is_gherkin ? 'gherkin' : 'free_form');
        setModal('edit');
    };

    const openRun = (s: TestScenario) => {
        setSelected(s);
        setRunStatus('passed');
        setRunNotes('');
        setModal('run');
    };

    const close = () => {
        setModal(null);
        setSelected(null);
    };

    const buildPayload = (
        formData: {
            title: string;
            given: string;
            when: string;
            then: string;
            free_form: string;
            automated_test_ref: string;
        },
        shape: Mode,
    ) => ({
        title: formData.title,
        given: shape === 'gherkin' ? formData.given || null : null,
        when: shape === 'gherkin' ? formData.when || null : null,
        then: shape === 'gherkin' ? formData.then || null : null,
        free_form: shape === 'free_form' ? formData.free_form || null : null,
        automated_test_ref: formData.automated_test_ref || null,
    });

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.transform((data) => buildPayload(data, mode) as unknown as typeof data);
        createForm.post(
            route('agile.acceptance-criteria.test-scenarios.store', criterion.id),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selected) return;
        editForm.transform((data) => buildPayload(data, mode) as unknown as typeof data);
        editForm.patch(route('agile.test-scenarios.update', selected.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('agile.test-scenarios.destroy', deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const submitRun = async (): Promise<void> => {
        if (!selected) return;
        if (runStatus === 'failed' && runNotes.trim() === '') {
            toast.error(t('agile.scenarios.failed_notes_required'));
            return;
        }
        setPending(true);
        try {
            await axios.post(route('api.agile.test-scenarios.record', selected.id), {
                status: runStatus,
                failure_notes: runStatus === 'failed' ? runNotes : runNotes || null,
            });
            toast.success(t('agile.scenarios.recorded'));
            close();
            router.reload({ only: ['story'] });
        } catch (e: unknown) {
            toast.error(axios.isAxiosError(e) ? e.response?.data?.message ?? t('agile.errors.generic') : t('agile.errors.generic'));
        } finally {
            setPending(false);
        }
    };

    return (
        <div className="mt-3 ml-10 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
            <div className="flex items-center justify-between mb-2">
                <h4 className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    {t('agile.scenarios.heading')}
                </h4>
                <Button size="sm" variant="outline" onClick={openCreate}>
                    <PlusIcon className="mr-1 h-3 w-3" />
                    {t('agile.scenarios.add_short')}
                </Button>
            </div>

            {scenarios.length === 0 ? (
                <p className="text-xs text-gray-400 italic">{t('agile.scenarios.none')}</p>
            ) : (
                <ul className="space-y-2">
                    {scenarios.map((s) => (
                        <li
                            key={s.id}
                            className="p-3 bg-gray-50 dark:bg-gray-900/50 rounded border border-gray-200 dark:border-gray-700"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                        <p className="font-medium text-sm">{s.title}</p>
                                        <StatusBadge
                                            status={s.execution_status}
                                            label={s.execution_status_label}
                                        />
                                    </div>
                                    {s.is_gherkin ? (
                                        <div className="mt-2 text-xs text-gray-700 dark:text-gray-300 space-y-0.5 font-mono">
                                            {s.given && <p><b>Given</b> {s.given}</p>}
                                            {s.when && <p><b>When</b> {s.when}</p>}
                                            {s.then && <p><b>Then</b> {s.then}</p>}
                                        </div>
                                    ) : (
                                        <p className="mt-1 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {s.free_form}
                                        </p>
                                    )}
                                    {s.automated_test_ref && (
                                        <p className="mt-1 text-xs text-gray-500 font-mono">
                                            🤖 {s.automated_test_ref}
                                        </p>
                                    )}
                                    {s.failure_notes && s.execution_status === TestScenarioExecutionStatus.FAILED && (
                                        <p className="mt-1 text-xs text-red-600 italic">
                                            ❌ {s.failure_notes}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1">
                                    <Button size="sm" variant="ghost" onClick={() => openRun(s)} aria-label={t('agile.actions.execute')}>
                                        <PlayIcon className="h-3 w-3" />
                                    </Button>
                                    <Button size="sm" variant="ghost" onClick={() => openEdit(s)} aria-label={t('agile.actions.edit')}>
                                        <PencilIcon className="h-3 w-3" />
                                    </Button>
                                    <Button size="sm" variant="ghost" onClick={() => setDeleteTarget(s)} aria-label={t('agile.actions.delete')}>
                                        <TrashIcon className="h-3 w-3 text-red-500" />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {/* Create / Edit dialog (shared layout) */}
            <Dialog
                open={modal === 'create' || modal === 'edit'}
                onOpenChange={(o) => (!o ? close() : undefined)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {modal === 'edit' ? t('agile.scenarios.edit_title') : t('agile.scenarios.create_title')}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={modal === 'edit' ? submitEdit : submitCreate} className="space-y-4">
                        {/* Mode toggle */}
                        <div>
                            <Label className="text-xs mr-4">{t('agile.common.type')}</Label>
                            <label className="mr-4 inline-flex items-center text-sm">
                                <input
                                    type="radio"
                                    className="mr-1"
                                    checked={mode === 'gherkin'}
                                    onChange={() => setMode('gherkin')}
                                />
                                {t('agile.scenarios.type_gherkin')}
                            </label>
                            <label className="inline-flex items-center text-sm">
                                <input
                                    type="radio"
                                    className="mr-1"
                                    checked={mode === 'free_form'}
                                    onChange={() => setMode('free_form')}
                                />
                                {t('agile.scenarios.type_freeform')}
                            </label>
                        </div>

                        <div>
                            <Label htmlFor="ts-title">{t('agile.common.title')} <span className="text-red-500">*</span></Label>
                            <Input
                                id="ts-title"
                                value={modal === 'edit' ? editForm.data.title : createForm.data.title}
                                onChange={(e) =>
                                    (modal === 'edit' ? editForm : createForm).setData(
                                        'title',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                        </div>

                        {mode === 'gherkin' ? (
                            <div className="grid grid-cols-1 gap-2">
                                {(['given', 'when', 'then'] as const).map((key) => (
                                    <div key={key}>
                                        <Label htmlFor={`ts-${key}`}>{key.charAt(0).toUpperCase() + key.slice(1)}</Label>
                                        <Textarea
                                            id={`ts-${key}`}
                                            rows={2}
                                            value={
                                                modal === 'edit'
                                                    ? editForm.data[key]
                                                    : createForm.data[key]
                                            }
                                            onChange={(e) =>
                                                (modal === 'edit' ? editForm : createForm).setData(
                                                    key,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div>
                                <Label htmlFor="ts-freeform">{t('agile.scenarios.freeform_label')} <span className="text-red-500">*</span></Label>
                                <Textarea
                                    id="ts-freeform"
                                    rows={5}
                                    value={
                                        modal === 'edit'
                                            ? editForm.data.free_form
                                            : createForm.data.free_form
                                    }
                                    onChange={(e) =>
                                        (modal === 'edit' ? editForm : createForm).setData(
                                            'free_form',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}

                        <div>
                            <Label htmlFor="ts-ref">{t('agile.scenarios.automated_ref')}</Label>
                            <Input
                                id="ts-ref"
                                placeholder="Tests\\Feature\\XxxTest::test_yyy"
                                value={
                                    modal === 'edit'
                                        ? editForm.data.automated_test_ref
                                        : createForm.data.automated_test_ref
                                }
                                onChange={(e) =>
                                    (modal === 'edit' ? editForm : createForm).setData(
                                        'automated_test_ref',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={close}>{t('agile.actions.cancel')}</Button>
                            <Button
                                type="submit"
                                disabled={
                                    modal === 'edit' ? editForm.processing : createForm.processing
                                }
                            >
                                {t('agile.actions.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Record run */}
            <Dialog open={modal === 'run'} onOpenChange={(o) => (!o ? close() : undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('agile.scenarios.run_title')}</DialogTitle>
                        <DialogDescription>{t('agile.scenarios.run_description')}</DialogDescription>
                    </DialogHeader>
                    <div className="py-2 space-y-3">
                        <div>
                            <Label className="mr-4">{t('agile.scenarios.result')}</Label>
                            {(['passed', 'failed', 'blocked'] as const).map((s) => (
                                <label key={s} className="mr-4 inline-flex items-center text-sm">
                                    <input
                                        type="radio"
                                        className="mr-1"
                                        checked={runStatus === s}
                                        onChange={() => setRunStatus(s)}
                                    />
                                    {t(`agile.scenarios.result.${s}`)}
                                </label>
                            ))}
                        </div>
                        <div>
                            <Label htmlFor="run-notes">
                                {t('agile.ac.notes_label')} {runStatus === 'failed' && <span className="text-red-500">*</span>}
                            </Label>
                            <Textarea
                                id="run-notes"
                                rows={3}
                                value={runNotes}
                                onChange={(e) => setRunNotes(e.target.value)}
                                required={runStatus === 'failed'}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={close}>{t('agile.actions.cancel')}</Button>
                        <Button onClick={submitRun} disabled={pending}>
                            <PlayIcon className="mr-2 h-4 w-4" />
                            {t('agile.actions.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmationDialog
                open={deleteTarget !== null}
                onOpenChange={(o) => (!o ? setDeleteTarget(null) : undefined)}
                onConfirm={handleDelete}
                title={deleteTarget?.title ?? ''}
                description={t('agile.scenarios.delete_desc')}
            />
        </div>
    );
};
