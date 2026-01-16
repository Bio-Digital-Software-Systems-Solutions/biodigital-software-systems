import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import type {
    DepartmentForm,
    FormField,
    FormFieldType,
    FieldOption,
    ConditionalLogic,
} from '@/Types/form';

interface FormBuilderState {
    form: DepartmentForm | null;
    fields: FormField[];
    selectedFieldId: string | null;
    dragOverFieldId: string | null;
    isDirty: boolean;
    isLoading: boolean;
    error: string | null;
    previewMode: boolean;
}

interface FormBuilderActions {
    setForm: (form: DepartmentForm) => void;
    setFields: (fields: FormField[]) => void;
    addField: (type: FormFieldType, parentId?: string | null, index?: number) => FormField;
    updateField: (fieldId: string, data: Partial<FormField>) => void;
    removeField: (fieldId: string) => void;
    duplicateField: (fieldId: string) => FormField | null;
    moveField: (fieldId: string, targetIndex: number, newParentId?: string | null) => void;
    selectField: (fieldId: string | null) => void;
    setDragOverField: (fieldId: string | null) => void;
    setIsDirty: (isDirty: boolean) => void;
    setIsLoading: (isLoading: boolean) => void;
    setError: (error: string | null) => void;
    setPreviewMode: (previewMode: boolean) => void;
    updateFormSettings: (settings: Record<string, any>) => void;
    reset: () => void;
}

const initialState: FormBuilderState = {
    form: null,
    fields: [],
    selectedFieldId: null,
    dragOverFieldId: null,
    isDirty: false,
    isLoading: false,
    error: null,
    previewMode: false,
};

const getFieldLabel = (type: FormFieldType): string => {
    const labels: Record<FormFieldType, string> = {
        text: 'Champ texte',
        textarea: 'Zone de texte',
        rich_text: 'Texte enrichi',
        number: 'Nombre',
        email: 'Email',
        phone: 'Téléphone',
        url: 'URL',
        password: 'Mot de passe',
        select: 'Liste déroulante',
        multi_select: 'Sélection multiple',
        radio: 'Boutons radio',
        checkbox: 'Case à cocher',
        checkbox_group: 'Groupe de cases',
        toggle: 'Interrupteur',
        date: 'Date',
        time: 'Heure',
        datetime: 'Date et heure',
        date_range: 'Plage de dates',
        file: 'Fichier',
        image: 'Image',
        signature: 'Signature',
        section: 'Section',
        group: 'Groupe',
        repeater: 'Répéteur',
        columns: 'Colonnes',
        tabs: 'Onglets',
        accordion: 'Accordéon',
        hidden: 'Champ caché',
        computed: 'Champ calculé',
        lookup: 'Recherche',
        rating: 'Évaluation',
        slider: 'Curseur',
        color: 'Couleur',
        tags: 'Tags',
        location: 'Localisation',
        user_select: 'Sélection utilisateur',
        department_select: 'Sélection département',
    };
    return labels[type] || type;
};

const generateFieldName = (type: FormFieldType): string => {
    return `${type}_${Date.now()}`;
};

export const useFormBuilderStore = create<FormBuilderState & FormBuilderActions>()(
    immer((set, get) => ({
        ...initialState,

        setForm: (form) =>
            set((state) => {
                state.form = form;
                state.fields = form.fields || [];
            }),

        setFields: (fields) =>
            set((state) => {
                state.fields = fields;
            }),

        addField: (type, parentId = null, index) => {
            const newField: FormField = {
                id: 0,
                uuid: crypto.randomUUID ? crypto.randomUUID() : `field-${Date.now()}`,
                form_id: get().form?.id || 0,
                parent_field_id: parentId ? parseInt(parentId) : undefined,
                name: generateFieldName(type),
                label: getFieldLabel(type),
                type,
                order: index ?? get().fields.filter((f) => !f.parent_field_id).length,
                step: 1,
                is_required: false,
                is_readonly: false,
                is_hidden: false,
                column_span: 12,
            };

            set((state) => {
                if (parentId) {
                    // Add to parent's children
                    const addToParent = (fields: FormField[]): boolean => {
                        for (const field of fields) {
                            if (field.uuid === parentId) {
                                if (!field.children) field.children = [];
                                field.children.push(newField);
                                return true;
                            }
                            if (field.children && addToParent(field.children)) {
                                return true;
                            }
                        }
                        return false;
                    };
                    addToParent(state.fields);
                } else {
                    if (index !== undefined) {
                        state.fields.splice(index, 0, newField);
                    } else {
                        state.fields.push(newField);
                    }
                }
                state.isDirty = true;
            });

            return newField;
        },

        updateField: (fieldId, data) =>
            set((state) => {
                const updateInArray = (fields: FormField[]): boolean => {
                    for (let i = 0; i < fields.length; i++) {
                        if (fields[i].uuid === fieldId) {
                            fields[i] = { ...fields[i], ...data };
                            return true;
                        }
                        if (fields[i].children && updateInArray(fields[i].children!)) {
                            return true;
                        }
                    }
                    return false;
                };
                updateInArray(state.fields);
                state.isDirty = true;
            }),

        removeField: (fieldId) =>
            set((state) => {
                const removeFromArray = (fields: FormField[]): boolean => {
                    for (let i = 0; i < fields.length; i++) {
                        if (fields[i].uuid === fieldId) {
                            fields.splice(i, 1);
                            return true;
                        }
                        if (fields[i].children && removeFromArray(fields[i].children!)) {
                            return true;
                        }
                    }
                    return false;
                };
                removeFromArray(state.fields);
                state.isDirty = true;
                if (state.selectedFieldId === fieldId) {
                    state.selectedFieldId = null;
                }
            }),

        duplicateField: (fieldId) => {
            const findField = (fields: FormField[]): FormField | null => {
                for (const field of fields) {
                    if (field.uuid === fieldId) return field;
                    if (field.children) {
                        const found = findField(field.children);
                        if (found) return found;
                    }
                }
                return null;
            };

            const original = findField(get().fields);
            if (!original) return null;

            const duplicate: FormField = {
                ...original,
                id: 0,
                uuid: crypto.randomUUID ? crypto.randomUUID() : `field-${Date.now()}`,
                name: `${original.name}_copy`,
                label: `${original.label} (copie)`,
                order: original.order + 1,
                children: undefined,
            };

            set((state) => {
                const insertAfter = (fields: FormField[]): boolean => {
                    for (let i = 0; i < fields.length; i++) {
                        if (fields[i].uuid === fieldId) {
                            fields.splice(i + 1, 0, duplicate);
                            return true;
                        }
                        if (fields[i].children && insertAfter(fields[i].children!)) {
                            return true;
                        }
                    }
                    return false;
                };
                insertAfter(state.fields);
                state.isDirty = true;
            });

            return duplicate;
        },

        moveField: (fieldId, targetIndex, newParentId = null) =>
            set((state) => {
                // Find and remove field
                let movedField: FormField | null = null;

                const removeField = (fields: FormField[]): boolean => {
                    for (let i = 0; i < fields.length; i++) {
                        if (fields[i].uuid === fieldId) {
                            movedField = fields.splice(i, 1)[0];
                            return true;
                        }
                        if (fields[i].children && removeField(fields[i].children!)) {
                            return true;
                        }
                    }
                    return false;
                };

                removeField(state.fields);

                if (!movedField) return;

                (movedField as FormField).order = targetIndex;

                // Insert at new position
                if (newParentId) {
                    const addToParent = (fields: FormField[]): boolean => {
                        for (const field of fields) {
                            if (field.uuid === newParentId) {
                                if (!field.children) field.children = [];
                                field.children.splice(targetIndex, 0, movedField!);
                                return true;
                            }
                            if (field.children && addToParent(field.children)) {
                                return true;
                            }
                        }
                        return false;
                    };
                    addToParent(state.fields);
                } else {
                    state.fields.splice(targetIndex, 0, movedField);
                }

                state.isDirty = true;
            }),

        selectField: (fieldId) =>
            set((state) => {
                state.selectedFieldId = fieldId;
            }),

        setDragOverField: (fieldId) =>
            set((state) => {
                state.dragOverFieldId = fieldId;
            }),

        setIsDirty: (isDirty) =>
            set((state) => {
                state.isDirty = isDirty;
            }),

        setIsLoading: (isLoading) =>
            set((state) => {
                state.isLoading = isLoading;
            }),

        setError: (error) =>
            set((state) => {
                state.error = error;
            }),

        setPreviewMode: (previewMode) =>
            set((state) => {
                state.previewMode = previewMode;
            }),

        updateFormSettings: (settings) =>
            set((state) => {
                if (state.form) {
                    state.form.settings = { ...state.form.settings, ...settings };
                    state.isDirty = true;
                }
            }),

        reset: () => set(initialState),
    }))
);
