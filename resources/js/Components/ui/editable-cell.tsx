import { useState, useRef, useEffect, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface EditableCellProps {
    value: string;
    field: string;
    taskUuid: string;
    onUpdate: (task: Record<string, unknown>) => void;
    type?: 'text' | 'select' | 'custom';
    options?: { value: string; label: string }[];
    renderDisplay?: (value: string) => React.ReactNode;
    renderEditor?: (props: {
        value: string;
        onChange: (value: string) => void;
        onSave: (valueToSave?: string) => void;
        onCancel: () => void;
        saving: boolean;
    }) => React.ReactNode;
    className?: string;
}

export function EditableCell({
    value,
    field,
    taskUuid,
    onUpdate,
    type = 'text',
    options = [],
    renderDisplay,
    renderEditor,
    className = '',
}: EditableCellProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [editValue, setEditValue] = useState(value ?? '');
    const [saving, setSaving] = useState(false);
    const inputRef = useRef<HTMLInputElement | HTMLSelectElement>(null);

    useEffect(() => {
        setEditValue(value ?? '');
    }, [value]);

    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            if (type === 'text' && inputRef.current instanceof HTMLInputElement) {
                inputRef.current.select();
            }
        }
    }, [isEditing, type]);

    const saveValue = useCallback(async (valueToSave?: string) => {
        const finalValue = valueToSave !== undefined ? valueToSave : editValue;
        if (finalValue === (value ?? '')) {
            setIsEditing(false);
            return;
        }

        setSaving(true);
        try {
            const response = await axios.patch(
                route('tasks.inline-update', taskUuid),
                { field, value: finalValue || null }
            );
            onUpdate(response.data.task);
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
    }, [editValue, value, field, taskUuid, onUpdate]);

    const cancel = useCallback(() => {
        setEditValue(value ?? '');
        setIsEditing(false);
    }, [value]);

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

    const handleEditorChange = useCallback((newValue: string) => {
        setEditValue(newValue);
    }, []);

    if (isEditing) {
        if (renderEditor) {
            return (
                <div data-testid={`editable-cell-editing-${field}`}>
                    {renderEditor({
                        value: editValue,
                        onChange: handleEditorChange,
                        onSave: (v?: string) => saveValue(v),
                        onCancel: cancel,
                        saving,
                    })}
                </div>
            );
        }

        return (
            <div className="flex items-center gap-1" data-testid={`editable-cell-editing-${field}`}>
                {type === 'select' ? (
                    <select
                        ref={inputRef as React.RefObject<HTMLSelectElement>}
                        value={editValue}
                        onChange={(e) => {
                            setEditValue(e.target.value);
                        }}
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
                        {options.map((opt) => (
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

    return (
        <div
            onDoubleClick={handleDoubleClick}
            className={`cursor-pointer rounded px-1 py-0.5 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors ${className}`}
            title="Double-cliquez pour modifier"
            data-testid={`editable-cell-${field}`}
        >
            {renderDisplay ? renderDisplay(value) : (value || '-')}
        </div>
    );
}
