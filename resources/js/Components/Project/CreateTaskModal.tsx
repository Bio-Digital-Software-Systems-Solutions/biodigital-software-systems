import React, { useState, useEffect, FormEvent } from 'react';
import { XMarkIcon, CalendarIcon, UserIcon, FlagIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
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
import axios from 'axios';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
}

interface Status {
    id: number;
    name: string;
    label?: string;
    color?: string;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
    projectId: number;
    projectUuid: string;
    users?: User[];
    statuses?: Status[];
}

type Priority = 'low' | 'medium' | 'high';

export default function CreateTaskModal({
    isOpen,
    onClose,
    onSuccess,
    projectId,
    projectUuid,
    users = [],
    statuses = [],
}: Props) {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    // Form state
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [priority, setPriority] = useState<Priority>('medium');
    const [statusId, setStatusId] = useState<string>('');
    const [assignedTo, setAssignedTo] = useState<string>('');
    const [estimatedHours, setEstimatedHours] = useState<string>('');

    // Set default status when statuses are loaded
    useEffect(() => {
        if (statuses.length > 0 && !statusId) {
            const pendingStatus = statuses.find(s => s.name === 'pending' || s.name === 'todo');
            if (pendingStatus) {
                setStatusId(String(pendingStatus.id));
            } else {
                setStatusId(String(statuses[0].id));
            }
        }
    }, [statuses, statusId]);

    // Reset form when modal closes
    useEffect(() => {
        if (!isOpen) {
            setTitle('');
            setDescription('');
            setDueDate('');
            setPriority('medium');
            setStatusId('');
            setAssignedTo('');
            setEstimatedHours('');
            setErrors({});
        }
    }, [isOpen]);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setErrors({});

        if (!title.trim()) {
            setErrors({ title: ['Le titre est requis'] });
            return;
        }

        if (!description.trim() || description.length < 10) {
            setErrors({ description: ['La description est requise (minimum 10 caractères)'] });
            return;
        }

        if (!statusId) {
            setErrors({ status_id: ['Le statut est requis'] });
            return;
        }

        setLoading(true);

        try {
            const payload: Record<string, unknown> = {
                title: title.trim(),
                description: description.trim(),
                priority,
                status_id: Number(statusId),
                project_id: projectId,
                taskable_type: 'App\\Models\\Project',
                taskable_id: projectId,
                from_project: true,
            };

            if (dueDate) {
                payload.due_date = dueDate;
            }

            if (assignedTo) {
                payload.assigned_to = Number(assignedTo);
            }

            if (estimatedHours) {
                payload.estimated_hours = Number(estimatedHours);
            }

            await axios.post(`/api/projects/${projectUuid}/tasks`, payload);

            toast.success('Tâche créée avec succès');
            onSuccess();
            onClose();
        } catch (error: unknown) {
            console.error('Error creating task:', error);
            if (axios.isAxiosError(error) && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else if (axios.isAxiosError(error) && error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error('Erreur lors de la création de la tâche');
            }
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    const getPriorityColor = (p: Priority) => {
        switch (p) {
            case 'high':
                return 'text-red-600';
            case 'medium':
                return 'text-yellow-600';
            case 'low':
                return 'text-green-600';
            default:
                return 'text-gray-600';
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Ajouter une tâche
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                        title="Fermer"
                        aria-label="Fermer le modal"
                    >
                        <XMarkIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-4 space-y-4">
                    {/* Title */}
                    <div>
                        <Label htmlFor="task-title" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Titre <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="task-title"
                            type="text"
                            placeholder="Titre de la tâche"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            className={`mt-1 ${errors.title ? 'border-red-500' : ''}`}
                        />
                        {errors.title && (
                            <p className="mt-1 text-sm text-red-500">{errors.title[0]}</p>
                        )}
                    </div>

                    {/* Description */}
                    <div>
                        <Label htmlFor="task-description" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description <span className="text-red-500">*</span>
                        </Label>
                        <Textarea
                            id="task-description"
                            placeholder="Décrivez la tâche en détail (minimum 10 caractères)"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            rows={4}
                            className={`mt-1 resize-none ${errors.description ? 'border-red-500' : ''}`}
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-red-500">{errors.description[0]}</p>
                        )}
                    </div>

                    {/* Priority & Status */}
                    <div className="grid grid-cols-2 gap-4">
                        {/* Priority */}
                        <div>
                            <Label htmlFor="task-priority" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Priorité <span className="text-red-500">*</span>
                            </Label>
                            <div className="mt-1 flex items-center gap-2">
                                <FlagIcon className={`h-5 w-5 ${getPriorityColor(priority)}`} />
                                <Select value={priority} onValueChange={(v) => setPriority(v as Priority)}>
                                    <SelectTrigger id="task-priority" className="flex-1">
                                        <SelectValue placeholder="Sélectionner la priorité" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Basse</SelectItem>
                                        <SelectItem value="medium">Moyenne</SelectItem>
                                        <SelectItem value="high">Haute</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Status */}
                        <div>
                            <Label htmlFor="task-status" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Statut <span className="text-red-500">*</span>
                            </Label>
                            <Select value={statusId} onValueChange={setStatusId}>
                                <SelectTrigger id="task-status" className="mt-1">
                                    <SelectValue placeholder="Sélectionner le statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((status) => (
                                        <SelectItem key={status.id} value={String(status.id)}>
                                            {status.label || status.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.status_id && (
                                <p className="mt-1 text-sm text-red-500">{errors.status_id[0]}</p>
                            )}
                        </div>
                    </div>

                    {/* Due Date & Estimated Hours */}
                    <div className="grid grid-cols-2 gap-4">
                        {/* Due Date */}
                        <div>
                            <Label htmlFor="task-due-date" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Date d'échéance
                            </Label>
                            <div className="mt-1 flex items-center gap-2">
                                <CalendarIcon className="h-5 w-5 text-gray-400" />
                                <Input
                                    id="task-due-date"
                                    type="date"
                                    value={dueDate}
                                    onChange={(e) => setDueDate(e.target.value)}
                                    min={new Date().toISOString().split('T')[0]}
                                    className="flex-1"
                                />
                            </div>
                            {errors.due_date && (
                                <p className="mt-1 text-sm text-red-500">{errors.due_date[0]}</p>
                            )}
                        </div>

                        {/* Estimated Hours */}
                        <div>
                            <Label htmlFor="task-hours" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Heures estimées
                            </Label>
                            <Input
                                id="task-hours"
                                type="number"
                                placeholder="Ex: 4"
                                value={estimatedHours}
                                onChange={(e) => setEstimatedHours(e.target.value)}
                                min="0"
                                step="0.5"
                                className="mt-1"
                            />
                            {errors.estimated_hours && (
                                <p className="mt-1 text-sm text-red-500">{errors.estimated_hours[0]}</p>
                            )}
                        </div>
                    </div>

                    {/* Assigned To */}
                    <div>
                        <Label htmlFor="task-assignee" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assigné à
                        </Label>
                        <div className="mt-1 flex items-center gap-2">
                            <UserIcon className="h-5 w-5 text-gray-400" />
                            <Select value={assignedTo} onValueChange={setAssignedTo}>
                                <SelectTrigger id="task-assignee" className="flex-1">
                                    <SelectValue placeholder="Non assigné">
                                        {(() => {
                                            const selectedUser = users.find(u => String(u.id) === assignedTo);
                                            return selectedUser
                                                ? `${selectedUser.first_name} ${selectedUser.last_name}`
                                                : 'Non assigné';
                                        })()}
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Non assigné</SelectItem>
                                    {users.map((user) => (
                                        <SelectItem key={user.id} value={String(user.id)}>
                                            {user.first_name} {user.last_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {errors.assigned_to && (
                            <p className="mt-1 text-sm text-red-500">{errors.assigned_to[0]}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            disabled={loading}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            disabled={loading}
                            className="bg-primary hover:bg-primary/90"
                        >
                            {loading ? 'Création...' : 'Créer la tâche'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
