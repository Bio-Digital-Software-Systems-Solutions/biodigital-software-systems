import React from 'react';
import { useFormBuilderStore } from '@/stores/formBuilderStore';
import { XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import type { FormField, FieldOption } from '@/Types/form';

const fieldTypeLabels: Record<string, string> = {
    text: 'Texte',
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

const hasOptions = (type: string): boolean => {
    return ['select', 'multi_select', 'radio', 'checkbox_group'].includes(type);
};

export default function FieldPropertiesPanel() {
    const { fields, selectedFieldId, updateField, selectField } = useFormBuilderStore();

    const findField = (fields: FormField[], id: string): FormField | null => {
        for (const field of fields) {
            if (field.uuid === id) return field;
            if (field.children) {
                const found = findField(field.children, id);
                if (found) return found;
            }
        }
        return null;
    };

    const selectedField = selectedFieldId ? findField(fields, selectedFieldId) : null;

    if (!selectedField) {
        return (
            <div className="h-full flex items-center justify-center p-4">
                <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                    Sélectionnez un champ pour modifier ses propriétés
                </p>
            </div>
        );
    }

    const handleChange = (key: keyof FormField, value: any) => {
        updateField(selectedField.uuid, { [key]: value });
    };

    const handleValidationChange = (key: string, value: any) => {
        updateField(selectedField.uuid, {
            validation: {
                ...selectedField.validation,
                [key]: value,
            },
        });
    };

    const handleAddOption = () => {
        const newOption: FieldOption = {
            value: `option_${Date.now()}`,
            label: 'Nouvelle option',
        };
        updateField(selectedField.uuid, {
            options: [...(selectedField.options || []), newOption],
        });
    };

    const handleUpdateOption = (index: number, key: keyof FieldOption, value: string) => {
        const options = [...(selectedField.options || [])];
        options[index] = { ...options[index], [key]: value };
        updateField(selectedField.uuid, { options });
    };

    const handleRemoveOption = (index: number) => {
        const options = [...(selectedField.options || [])];
        options.splice(index, 1);
        updateField(selectedField.uuid, { options });
    };

    const inputClasses = `
        w-full px-3 py-2 rounded-md border text-sm
        bg-white dark:bg-gray-900
        border-gray-300 dark:border-gray-600
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-primary focus:border-primary
    `;

    const labelClasses = `
        block text-xs font-medium mb-1
        text-gray-600 dark:text-gray-400
    `;

    return (
        <div className="h-full overflow-y-auto">
            {/* Header */}
            <div className="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                        Propriétés
                    </h3>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        {fieldTypeLabels[selectedField.type] || selectedField.type}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => selectField(null)}
                    className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                >
                    <XMarkIcon className="h-5 w-5" />
                </button>
            </div>

            {/* Properties Form */}
            <div className="p-4 space-y-4">
                {/* Basic Properties */}
                <div className="space-y-3">
                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                        Général
                    </h4>

                    <div>
                        <label className={labelClasses}>Libellé</label>
                        <input
                            type="text"
                            value={selectedField.label || ''}
                            onChange={(e) => handleChange('label', e.target.value)}
                            className={inputClasses}
                        />
                    </div>

                    <div>
                        <label className={labelClasses}>Nom technique</label>
                        <input
                            type="text"
                            value={selectedField.name || ''}
                            onChange={(e) => handleChange('name', e.target.value)}
                            className={`${inputClasses} font-mono text-xs`}
                        />
                    </div>

                    <div>
                        <label className={labelClasses}>Placeholder</label>
                        <input
                            type="text"
                            value={selectedField.placeholder || ''}
                            onChange={(e) => handleChange('placeholder', e.target.value)}
                            className={inputClasses}
                        />
                    </div>

                    <div>
                        <label className={labelClasses}>Texte d'aide</label>
                        <textarea
                            value={selectedField.helper_text || ''}
                            onChange={(e) => handleChange('helper_text', e.target.value)}
                            rows={2}
                            className={inputClasses}
                        />
                    </div>

                    <div>
                        <label className={labelClasses}>Largeur</label>
                        <select
                            value={selectedField.width || 'full'}
                            onChange={(e) => handleChange('width', e.target.value)}
                            className={inputClasses}
                        >
                            <option value="full">Pleine largeur</option>
                            <option value="half">Demi largeur</option>
                            <option value="third">Tiers</option>
                            <option value="quarter">Quart</option>
                        </select>
                    </div>
                </div>

                {/* Options for Select/Radio/Checkbox fields */}
                {hasOptions(selectedField.type) && (
                    <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                Options
                            </h4>
                            <button
                                type="button"
                                onClick={handleAddOption}
                                className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-primary"
                            >
                                <PlusIcon className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-2">
                            {(selectedField.options || []).map((option, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    <input
                                        type="text"
                                        value={option.label}
                                        onChange={(e) =>
                                            handleUpdateOption(index, 'label', e.target.value)
                                        }
                                        placeholder="Libellé"
                                        className={`${inputClasses} flex-1`}
                                    />
                                    <input
                                        type="text"
                                        value={option.value}
                                        onChange={(e) =>
                                            handleUpdateOption(index, 'value', e.target.value)
                                        }
                                        placeholder="Valeur"
                                        className={`${inputClasses} w-24 font-mono text-xs`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => handleRemoveOption(index)}
                                        className="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500"
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Validation */}
                <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                        Validation
                    </h4>

                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={selectedField.is_required || false}
                            onChange={(e) => handleChange('is_required', e.target.checked)}
                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        />
                        <span className="text-sm text-gray-700 dark:text-gray-300">
                            Champ obligatoire
                        </span>
                    </label>

                    {['text', 'textarea', 'password'].includes(selectedField.type) && (
                        <>
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className={labelClasses}>Min. caractères</label>
                                    <input
                                        type="number"
                                        value={selectedField.validation?.min || ''}
                                        onChange={(e) =>
                                            handleValidationChange('min', e.target.value ? Number(e.target.value) : undefined)
                                        }
                                        className={inputClasses}
                                    />
                                </div>
                                <div>
                                    <label className={labelClasses}>Max. caractères</label>
                                    <input
                                        type="number"
                                        value={selectedField.validation?.max || ''}
                                        onChange={(e) =>
                                            handleValidationChange('max', e.target.value ? Number(e.target.value) : undefined)
                                        }
                                        className={inputClasses}
                                    />
                                </div>
                            </div>
                            <div>
                                <label className={labelClasses}>Pattern (regex)</label>
                                <input
                                    type="text"
                                    value={selectedField.validation?.pattern || ''}
                                    onChange={(e) => handleValidationChange('pattern', e.target.value)}
                                    placeholder="^[a-z]+$"
                                    className={`${inputClasses} font-mono text-xs`}
                                />
                            </div>
                        </>
                    )}

                    {selectedField.type === 'number' && (
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className={labelClasses}>Min.</label>
                                <input
                                    type="number"
                                    value={selectedField.validation?.min || ''}
                                    onChange={(e) =>
                                        handleValidationChange('min', e.target.value ? Number(e.target.value) : undefined)
                                    }
                                    className={inputClasses}
                                />
                            </div>
                            <div>
                                <label className={labelClasses}>Max.</label>
                                <input
                                    type="number"
                                    value={selectedField.validation?.max || ''}
                                    onChange={(e) =>
                                        handleValidationChange('max', e.target.value ? Number(e.target.value) : undefined)
                                    }
                                    className={inputClasses}
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Display Options */}
                <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                        Affichage
                    </h4>

                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={selectedField.is_readonly || false}
                            onChange={(e) => handleChange('is_readonly', e.target.checked)}
                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        />
                        <span className="text-sm text-gray-700 dark:text-gray-300">
                            Lecture seule
                        </span>
                    </label>

                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={selectedField.is_hidden || false}
                            onChange={(e) => handleChange('is_hidden', e.target.checked)}
                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        />
                        <span className="text-sm text-gray-700 dark:text-gray-300">
                            Masqué
                        </span>
                    </label>
                </div>

                {/* Default Value */}
                <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                        Valeur par défaut
                    </h4>

                    <div>
                        <label className={labelClasses}>Valeur</label>
                        <input
                            type="text"
                            value={selectedField.default_value !== undefined ? String(selectedField.default_value) : ''}
                            onChange={(e) => handleChange('default_value', e.target.value)}
                            className={inputClasses}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
