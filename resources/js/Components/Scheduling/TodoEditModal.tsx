import React, { useState, useEffect } from 'react';
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
import { format, parseISO } from 'date-fns';
import 'react-datepicker/dist/react-datepicker.css';
import type { DepartmentMember, EnumOption, TodoPriority, Shift, DepartmentTodo } from '@/Types/scheduling';

registerLocale('fr', fr);

interface TodoEditModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    todo: DepartmentTodo | null;
    departmentUuid: string;
    members: DepartmentMember[];
    priorities: EnumOption<TodoPriority>[];
    shifts?: Shift[];
    onSuccess?: () => void;
    reloadProps?: string[];
}

export default function TodoEditModal({
    open,
    onOpenChange,
    todo,
    departmentUuid,
    members,
    priorities,
    shifts,
    onSuccess,
    reloadProps,
}: TodoEditModalProps) {
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
        shift_id: '',
        estimated_minutes: '',
    });

    // Reset form when modal opens with todo data
    useEffect(() => {
        if (open && todo) {
            setFormData({
                title: todo.title,
                description: todo.description || '',
                priority: todo.priority,
                due_date: todo.due_date ? parseISO(todo.due_date) : null,
                assigned_to: todo.assignee?.uuid || '',
                backup_assignees: todo.backup_assignees?.map(u => u.uuid) || [],
                shift_id: todo.shift?.uuid || '',
                estimated_minutes: todo.estimated_minutes?.toString() || '',
            });
        }
    }, [open, todo]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!todo) return;

        if (!formData.title.trim()) {
            toast.error('Le titre est requis');
            return;
        }

        setIsSubmitting(true);

        try {
            const response = await fetch(`/departments/${departmentUuid}/todos/${todo.uuid}`, {
                method: 'PUT',
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
                toast.success(result.message || 'Tache modifiee avec succes');
                onOpenChange(false);
                onSuccess?.();
                // Reload specific props or default to todos, todoStats, and shiftTodos
                const propsToReload = reloadProps || ['todos', 'todoStats', 'shiftTodos'];
                router.reload({ only: propsToReload });
            } else {
                toast.error(result.message || 'Erreur lors de la modification');
            }
        } catch (error) {
            console.error('Todo update error:', error);
            toast.error('Erreur lors de la modification de la tache');
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

    if (!todo) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Modifier la tache</DialogTitle>
                    <DialogDescription>
                        Modifiez les informations de la tache
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 px-6 pb-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-title">Titre *</Label>
                        <Input
                            id="edit-title"
                            value={formData.title}
                            onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                            placeholder="Titre de la tache"
                            required
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-description">Description</Label>
                        <Textarea
                            id="edit-description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Description optionnelle"
                            rows={3}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit-priority">Priorite</Label>
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
                            <Label htmlFor="edit-due_date">Date d'echeance</Label>
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
                        <Label htmlFor="edit-assigned_to">Assigner a</Label>
                        <SearchableSelect
                            id="edit-assigned_to"
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
                        <Label htmlFor="edit-backup_assignees">Backup</Label>
                        <SearchableMultiSelect
                            id="edit-backup_assignees"
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
                            <Label htmlFor="edit-shift_id">Lier a un shift</Label>
                            <SearchableSelect
                                id="edit-shift_id"
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
                        <Label htmlFor="edit-estimated_minutes">Duree estimee (minutes)</Label>
                        <Input
                            id="edit-estimated_minutes"
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
                            {isSubmitting ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
