import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'; // Used for priority
import { SearchableSelect, SearchableMultiSelect } from '@/Components/ui/searchable-select';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale';
import { format } from 'date-fns';
import 'react-datepicker/dist/react-datepicker.css';
import type { DepartmentMember, EnumOption, TodoPriority, Shift } from '@/Types/scheduling';

registerLocale('fr', fr);

interface TodoCreateModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    departmentUuid: string;
    members: DepartmentMember[];
    priorities: EnumOption<TodoPriority>[];
    shifts?: Shift[];
    preselectedShiftUuid?: string;
    defaultShiftUuid?: string;
    onSuccess?: () => void;
    reloadProps?: string[];
}

export default function TodoCreateModal({
    open,
    onOpenChange,
    departmentUuid,
    members,
    priorities,
    shifts,
    preselectedShiftUuid,
    defaultShiftUuid,
    onSuccess,
    reloadProps,
}: TodoCreateModalProps) {
    const initialShiftUuid = defaultShiftUuid || preselectedShiftUuid || '';
    const [isSubmitting, setIsSubmitting] = useState(false);

    const formatShiftLabel = (shift: Shift) => {
        // Format date
        const dateStr = shift.date ? new Date(shift.date).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }) : '';
        // Format time (remove seconds if present)
        const startTime = shift.start_time?.slice(0, 5) || '';
        const endTime = shift.end_time?.slice(0, 5) || '';
        const userName = shift.user ? ` (${shift.user.first_name || shift.user.name})` : '';
        return `${dateStr} - ${startTime} à ${endTime}${userName}`;
    };
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        priority: 'medium',
        due_date: null as Date | null,
        assigned_to: '',
        backup_assignees: [] as string[],
        shift_id: initialShiftUuid,
        estimated_minutes: '',
    });

    // Reset form when modal opens with default shift
    React.useEffect(() => {
        if (open && initialShiftUuid) {
            setFormData(prev => ({ ...prev, shift_id: initialShiftUuid }));
        }
    }, [open, initialShiftUuid]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.title.trim()) {
            toast.error('Le titre est requis');
            return;
        }

        setIsSubmitting(true);

        try {
            const response = await fetch(`/departments/${departmentUuid}/todos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    title: formData.title,
                    description: formData.description || null,
                    priority: formData.priority,
                    due_date: formData.due_date ? format(formData.due_date, 'yyyy-MM-dd') : null,
                    assigned_to: formData.assigned_to || null,
                    backup_assignees: formData.backup_assignees.length > 0 ? formData.backup_assignees : null,
                    shift_id: formData.shift_id || null,
                    estimated_minutes: formData.estimated_minutes ? parseInt(formData.estimated_minutes) : null,
                }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                toast.success(result.message || 'Tache creee avec succes');
                setFormData({
                    title: '',
                    description: '',
                    priority: 'medium',
                    due_date: null,
                    assigned_to: '',
                    backup_assignees: [],
                    shift_id: '',
                    estimated_minutes: '',
                });
                onOpenChange(false);
                onSuccess?.();
                // Reload specific props or default to todos and todoStats
                const propsToReload = reloadProps || ['todos', 'todoStats', 'shiftTodos'];
                router.reload({ only: propsToReload });
            } else {
                toast.error(result.message || 'Erreur lors de la creation');
            }
        } catch (error) {
            console.error('Todo creation error:', error);
            toast.error('Erreur lors de la creation de la tache');
        } finally {
            setIsSubmitting(false);
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'urgent':
                return 'text-red-600';
            case 'high':
                return 'text-orange-600';
            case 'medium':
                return 'text-blue-600';
            case 'low':
                return 'text-gray-600';
            default:
                return '';
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Nouvelle tache</DialogTitle>
                    <DialogDescription>
                        Creez une nouvelle tache pour le departement
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 px-3">
                    <div className="space-y-2">
                        <Label htmlFor="title">Titre *</Label>
                        <Input
                            id="title"
                            value={formData.title}
                            onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                            placeholder="Titre de la tache"
                            required
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Description optionnelle"
                            rows={3}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="priority">Priorite</Label>
                            <Select
                                value={formData.priority}
                                onValueChange={(value) => setFormData({ ...formData, priority: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Priorite" />
                                </SelectTrigger>
                                <SelectContent>
                                    {priorities.map((priority) => (
                                        <SelectItem key={priority.value} value={priority.value}>
                                            <span className={getPriorityColor(priority.value)}>
                                                {priority.label}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="due_date">Date d'echeance</Label>
                            <DatePicker
                                selected={formData.due_date}
                                onChange={(date) => setFormData({ ...formData, due_date: date })}
                                locale="fr"
                                dateFormat="dd/MM/yyyy"
                                placeholderText="Selectionner"
                                className="w-full px-3 py-2 rounded-md border text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
                                isClearable
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="assigned_to">Assigner a</Label>
                        <SearchableSelect
                            id="assigned_to"
                            options={members.map((member) => ({
                                value: member.uuid,
                                label: member.name,
                            }))}
                            value={formData.assigned_to || null}
                            onChange={(value) => setFormData({ ...formData, assigned_to: value as string || '' })}
                            placeholder="Rechercher un membre..."
                            noOptionsMessage="Aucun membre trouve"
                            isClearable
                            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="backup_assignees">Backup</Label>
                        <SearchableMultiSelect
                            id="backup_assignees"
                            options={members
                                .filter((member) => member.uuid !== formData.assigned_to)
                                .map((member) => ({
                                    value: member.uuid,
                                    label: member.name,
                                }))}
                            value={formData.backup_assignees}
                            onChange={(values) => setFormData({ ...formData, backup_assignees: values as string[] })}
                            placeholder="Selectionner les backups..."
                            noOptionsMessage="Aucun membre disponible"
                            isClearable
                            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
                        />
                    </div>

                    {shifts && shifts.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="shift_id">Lier a un shift</Label>
                            <SearchableSelect
                                id="shift_id"
                                options={[
                                    { value: '', label: 'Aucun shift' },
                                    ...shifts.map((shift) => ({
                                        value: shift.uuid,
                                        label: formatShiftLabel(shift),
                                    }))
                                ]}
                                value={formData.shift_id || null}
                                onChange={(value) => setFormData({ ...formData, shift_id: value as string || '' })}
                                placeholder="Selectionner un shift..."
                                noOptionsMessage="Aucun shift disponible"
                                isClearable
                                menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
                            />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="estimated_minutes">Duree estimee (minutes)</Label>
                        <Input
                            id="estimated_minutes"
                            type="number"
                            min="1"
                            value={formData.estimated_minutes}
                            onChange={(e) => setFormData({ ...formData, estimated_minutes: e.target.value })}
                            placeholder="ex: 30"
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? 'Creation...' : 'Creer'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
