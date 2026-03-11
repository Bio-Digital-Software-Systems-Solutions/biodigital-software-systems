import { useState, useRef, useEffect, useCallback } from 'react';
import axios from 'axios';
import { format } from 'date-fns';
import { toast } from 'sonner';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { DatePicker } from '@/Components/ui/date-picker';
import { SearchableSelect, SearchableMultiSelect } from '@/Components/ui/searchable-select';
import type { SelectOption } from '@/Components/ui/searchable-select';
import type { DepartmentTodo } from '@/Types/scheduling';

interface TodoEditableCellBaseProps {
    field: string;
    todo: DepartmentTodo;
    departmentUuid: string;
    onUpdate: (todo: DepartmentTodo) => void;
    className?: string;
}

interface TextCellProps extends TodoEditableCellBaseProps {
    type: 'text';
    value: string;
    options?: never;
    multiValue?: never;
    renderDisplay?: (value: string) => React.ReactNode;
}

interface SelectCellProps extends TodoEditableCellBaseProps {
    type: 'select';
    value: string;
    options: { value: string; label: string }[];
    multiValue?: never;
    renderDisplay?: (value: string) => React.ReactNode;
}

interface DateCellProps extends TodoEditableCellBaseProps {
    type: 'date';
    value: string;
    options?: never;
    multiValue?: never;
    renderDisplay?: (value: string) => React.ReactNode;
}

interface SearchableSelectCellProps extends TodoEditableCellBaseProps {
    type: 'searchable-select';
    value: string;
    options: SelectOption[];
    multiValue?: never;
    renderDisplay?: (value: string) => React.ReactNode;
}

interface SearchableMultiSelectCellProps extends TodoEditableCellBaseProps {
    type: 'searchable-multi-select';
    value?: never;
    multiValue: (string | number)[];
    options: SelectOption[];
    renderDisplay?: (value: string) => React.ReactNode;
}

type TodoEditableCellProps =
    | TextCellProps
    | SelectCellProps
    | DateCellProps
    | SearchableSelectCellProps
    | SearchableMultiSelectCellProps;

export function TodoEditableCell(props: TodoEditableCellProps) {
    const {
        field,
        todo,
        departmentUuid,
        onUpdate,
        type,
        options,
        renderDisplay,
        className = '',
    } = props;

    const value = props.type === 'searchable-multi-select' ? '' : props.value;
    const multiValue = props.type === 'searchable-multi-select' ? props.multiValue : [];

    const [isEditing, setIsEditing] = useState(false);
    const [editValue, setEditValue] = useState(value ?? '');
    const [editMultiValue, setEditMultiValue] = useState<(string | number)[]>(multiValue);
    const [saving, setSaving] = useState(false);
    const inputRef = useRef<HTMLInputElement | HTMLSelectElement>(null);

    useEffect(() => {
        setEditValue(value ?? '');
    }, [value]);

    useEffect(() => {
        setEditMultiValue(multiValue);
    }, [JSON.stringify(multiValue)]);

    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            if (type === 'text' && inputRef.current instanceof HTMLInputElement) {
                inputRef.current.select();
            }
        }
    }, [isEditing, type]);

    const saveValue = useCallback(async (valueToSave?: string | null) => {
        const finalValue = valueToSave !== undefined ? valueToSave : editValue;
        if (finalValue === (value ?? '')) {
            setIsEditing(false);
            return;
        }

        setSaving(true);
        try {
            const response = await axios.patch(
                `/departments/${departmentUuid}/todos/${todo.uuid}/inline-update`,
                { field, value: finalValue || null }
            );
            onUpdate(response.data.todo);
            toast.success('Mis à jour avec succès');
            setIsEditing(false);
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error('Erreur lors de la mise à jour');
            }
            setEditValue(value ?? '');
            setIsEditing(false);
        } finally {
            setSaving(false);
        }
    }, [editValue, value, field, todo.uuid, departmentUuid, onUpdate]);

    const saveMultiValue = useCallback(async (valuesToSave: (string | number)[]) => {
        setSaving(true);
        try {
            const response = await axios.patch(
                `/departments/${departmentUuid}/todos/${todo.uuid}/inline-update`,
                { field, value: valuesToSave.length > 0 ? valuesToSave : null }
            );
            onUpdate(response.data.todo);
            toast.success('Mis à jour avec succès');
            setIsEditing(false);
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error('Erreur lors de la mise à jour');
            }
            setEditMultiValue(multiValue);
            setIsEditing(false);
        } finally {
            setSaving(false);
        }
    }, [field, todo.uuid, departmentUuid, onUpdate, multiValue]);

    const cancel = useCallback(() => {
        setEditValue(value ?? '');
        setEditMultiValue(multiValue);
        setIsEditing(false);
    }, [value, multiValue]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveValue();
            } else if (e.key === 'Escape') {
                cancel();
            }
        },
        [saveValue, cancel]
    );

    const handleDoubleClick = useCallback(() => {
        if (!saving) {
            setIsEditing(true);
        }
    }, [saving]);

    if (isEditing) {
        if (type === 'searchable-select') {
            return (
                <div className="flex items-center gap-1 min-w-[180px]" data-testid={`todo-editable-cell-editing-${field}`}>
                    <div className="flex-1">
                        <SearchableSelect
                            options={options ?? []}
                            value={editValue || null}
                            onChange={(val) => {
                                const newVal = val ? String(val) : '';
                                setEditValue(newVal);
                                saveValue(newVal || null);
                            }}
                            placeholder="Rechercher..."
                            isClearable
                            maxMenuHeight={180}
                            menuPortalTarget={document.body}
                        />
                    </div>
                    <button
                        onClick={cancel}
                        disabled={saving}
                        className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-0.5 flex-shrink-0"
                        title="Annuler"
                    >
                        <XMarkIcon className="w-4 h-4" />
                    </button>
                </div>
            );
        }

        if (type === 'searchable-multi-select') {
            return (
                <div className="flex items-center gap-1 min-w-[200px]" data-testid={`todo-editable-cell-editing-${field}`}>
                    <div className="flex-1">
                        <SearchableMultiSelect
                            options={options ?? []}
                            value={editMultiValue}
                            onChange={(vals) => setEditMultiValue(vals)}
                            placeholder="Rechercher..."
                            isClearable
                            maxMenuHeight={180}
                            menuPortalTarget={document.body}
                        />
                    </div>
                    <button
                        onClick={() => saveMultiValue(editMultiValue)}
                        disabled={saving}
                        className="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 p-0.5 flex-shrink-0"
                        title="Enregistrer"
                    >
                        <CheckIcon className="w-4 h-4" />
                    </button>
                    <button
                        onClick={cancel}
                        disabled={saving}
                        className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-0.5 flex-shrink-0"
                        title="Annuler"
                    >
                        <XMarkIcon className="w-4 h-4" />
                    </button>
                </div>
            );
        }

        if (type === 'date') {
            return (
                <div className="flex items-center gap-1 min-w-[180px]" data-testid={`todo-editable-cell-editing-${field}`}>
                    <div className="flex-1">
                        <DatePicker
                            value={editValue || undefined}
                            onChange={(date) => {
                                const formatted = date ? format(date, 'yyyy-MM-dd') : '';
                                setEditValue(formatted);
                                saveValue(formatted || null);
                            }}
                            placeholder="Choisir une date"
                            disabled={saving}
                            portal
                        />
                    </div>
                    <button
                        onClick={cancel}
                        disabled={saving}
                        className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-0.5 flex-shrink-0"
                        title="Annuler"
                    >
                        <XMarkIcon className="w-4 h-4" />
                    </button>
                </div>
            );
        }

        return (
            <div className="flex items-center gap-1" data-testid={`todo-editable-cell-editing-${field}`}>
                {type === 'select' ? (
                    <select
                        ref={inputRef as React.RefObject<HTMLSelectElement>}
                        value={editValue}
                        onChange={(e) => setEditValue(e.target.value)}
                        onBlur={() => {
                            setTimeout(() => {
                                if (editValue !== (value ?? '')) {
                                    saveValue();
                                } else {
                                    cancel();
                                }
                            }, 150);
                        }}
                        onKeyDown={handleKeyDown}
                        disabled={saving}
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-xs py-1 px-2"
                    >
                        {(options ?? []).map((opt) => (
                            <option key={opt.value} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                ) : (
                    <input
                        ref={inputRef as React.RefObject<HTMLInputElement>}
                        type="text"
                        value={editValue}
                        onChange={(e) => setEditValue(e.target.value)}
                        onKeyDown={handleKeyDown}
                        onBlur={() => setTimeout(() => {
                            if (editValue !== (value ?? '')) {
                                saveValue();
                            } else {
                                cancel();
                            }
                        }, 150)}
                        disabled={saving}
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-xs py-1 px-2"
                    />
                )}
                <button
                    onClick={() => saveValue()}
                    disabled={saving}
                    className="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 p-0.5"
                    title="Enregistrer"
                >
                    <CheckIcon className="w-4 h-4" />
                </button>
                <button
                    onClick={cancel}
                    disabled={saving}
                    className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-0.5"
                    title="Annuler"
                >
                    <XMarkIcon className="w-4 h-4" />
                </button>
            </div>
        );
    }

    const displayValue = type === 'searchable-multi-select' ? '' : value;

    return (
        <div
            onDoubleClick={handleDoubleClick}
            className={`cursor-pointer rounded px-1 py-0.5 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors ${className}`}
            title="Double-cliquez pour modifier"
            data-testid={`todo-editable-cell-${field}`}
        >
            {renderDisplay ? renderDisplay(displayValue) : (displayValue || '—')}
        </div>
    );
}
