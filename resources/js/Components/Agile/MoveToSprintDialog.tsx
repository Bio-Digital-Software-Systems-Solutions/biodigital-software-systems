import React, { useState } from 'react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';

interface SprintLite {
    id: number;
    name: string;
    status: string;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    storyUuid: string;
    currentSprintId: number | null;
    sprints: SprintLite[];
}

export const MoveToSprintDialog: React.FC<Props> = ({
    open,
    onOpenChange,
    storyUuid,
    currentSprintId,
    sprints,
}) => {
    const { t } = useTranslation();
    const [target, setTarget] = useState<string>(
        currentSprintId !== null ? String(currentSprintId) : '__none__',
    );
    const [saving, setSaving] = useState(false);

    const submit = async (): Promise<void> => {
        setSaving(true);
        try {
            await axios.post(route('api.agile.user-stories.move', storyUuid), {
                sprint_id: target === '__none__' ? null : Number(target),
            });
            toast.success(t('agile.sprint.moved'));
            onOpenChange(false);
            router.reload({ only: ['story'] });
        } catch (e: unknown) {
            if (axios.isAxiosError(e) && e.response?.status === 422) {
                toast.error(e.response.data?.message ?? t('agile.sprint.move_fail'));
            } else {
                toast.error(t('agile.errors.unexpected'));
            }
        } finally {
            setSaving(false);
        }
    };

    // Closed sprints are filtered out — the backend rejects them anyway, but the UI
    // prevents the attempt so users don't hit a 422.
    const eligibleSprints = sprints.filter((s) => s.status !== 'completed' && s.status !== 'cancelled');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('agile.sprint.move_title')}</DialogTitle>
                    <DialogDescription>{t('agile.sprint.move_description')}</DialogDescription>
                </DialogHeader>
                <div className="py-4 space-y-2">
                    <Label htmlFor="sprint">{t('agile.sprint.target_label')}</Label>
                    <Select value={target} onValueChange={setTarget}>
                        <SelectTrigger id="sprint">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">{t('agile.sprint.detach')}</SelectItem>
                            {eligibleSprints.map((s) => (
                                <SelectItem key={s.id} value={String(s.id)}>
                                    {s.name} · {s.status}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        {t('agile.actions.cancel')}
                    </Button>
                    <Button onClick={submit} disabled={saving}>
                        {saving ? '…' : t('agile.actions.confirm')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
