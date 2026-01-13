import React from 'react';
import { XMarkIcon, TrashIcon, PlusIcon } from '@heroicons/react/24/outline';
import { useWorkflowStore } from '@/stores/workflowStore';
import type { WorkflowStep, StepType, ApprovalType } from '@/Types/workflow';

const stepTypeLabels: Record<StepType, string> = {
    start: 'Début',
    end: 'Fin',
    task: 'Tâche',
    approval: 'Approbation',
    form: 'Formulaire',
    condition: 'Condition',
    parallel: 'Parallèle',
    notification: 'Notification',
    delay: 'Délai',
    script: 'Script',
    sub_workflow: 'Sous-workflow',
};

const approvalTypes: { value: ApprovalType; label: string }[] = [
    { value: 'any', label: 'Un seul approbateur' },
    { value: 'all', label: 'Tous les approbateurs' },
    { value: 'majority', label: 'Majorité' },
    { value: 'sequential', label: 'Séquentiel' },
];

export default function StepPropertiesPanel() {
    const {
        steps,
        selectedStepId,
        updateStep,
        removeStep,
        selectStep,
    } = useWorkflowStore();

    const selectedStep = steps.find((s) => s.uuid === selectedStepId);

    if (!selectedStep) {
        return null;
    }

    const handleChange = (key: keyof WorkflowStep, value: any) => {
        updateStep(selectedStep.uuid, { [key]: value });
    };

    const handleConfigChange = (key: string, value: any) => {
        updateStep(selectedStep.uuid, {
            config: {
                ...selectedStep.config,
                [key]: value,
            },
        });
    };

    const handleDelete = () => {
        removeStep(selectedStep.uuid);
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

    const renderTypeSpecificConfig = () => {
        switch (selectedStep.type) {
            case 'task':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Instructions</label>
                            <textarea
                                value={selectedStep.config?.instructions || ''}
                                onChange={(e) => handleConfigChange('instructions', e.target.value)}
                                rows={3}
                                className={inputClasses}
                                placeholder="Instructions pour l'exécutant..."
                            />
                        </div>
                        <div>
                            <label className={labelClasses}>Délai (heures)</label>
                            <input
                                type="number"
                                value={selectedStep.config?.timeout_hours || ''}
                                onChange={(e) => handleConfigChange('timeout_hours', e.target.value ? Number(e.target.value) : undefined)}
                                className={inputClasses}
                                min="0"
                            />
                        </div>
                    </div>
                );

            case 'approval':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Type d'approbation</label>
                            <select
                                value={selectedStep.config?.approval_type || 'any'}
                                onChange={(e) => handleConfigChange('approval_type', e.target.value)}
                                className={inputClasses}
                            >
                                {approvalTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={labelClasses}>Niveaux d'approbation</label>
                            <input
                                type="number"
                                value={selectedStep.config?.approval_levels || 1}
                                onChange={(e) => handleConfigChange('approval_levels', Number(e.target.value))}
                                className={inputClasses}
                                min="1"
                                max="10"
                            />
                        </div>
                        <div>
                            <label className={labelClasses}>Délai (heures)</label>
                            <input
                                type="number"
                                value={selectedStep.config?.timeout_hours || ''}
                                onChange={(e) => handleConfigChange('timeout_hours', e.target.value ? Number(e.target.value) : undefined)}
                                className={inputClasses}
                                min="0"
                            />
                        </div>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={selectedStep.config?.allow_delegation || false}
                                onChange={(e) => handleConfigChange('allow_delegation', e.target.checked)}
                                className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                Autoriser la délégation
                            </span>
                        </label>
                    </div>
                );

            case 'notification':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Canaux de notification</label>
                            <div className="space-y-2">
                                {['email', 'database', 'sms'].map((channel) => (
                                    <label key={channel} className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={(selectedStep.config?.channels || []).includes(channel)}
                                            onChange={(e) => {
                                                const channels = selectedStep.config?.channels || [];
                                                const updated = e.target.checked
                                                    ? [...channels, channel]
                                                    : channels.filter((c: string) => c !== channel);
                                                handleConfigChange('channels', updated);
                                            }}
                                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300 capitalize">
                                            {channel}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div>
                            <label className={labelClasses}>Sujet</label>
                            <input
                                type="text"
                                value={selectedStep.config?.subject || ''}
                                onChange={(e) => handleConfigChange('subject', e.target.value)}
                                className={inputClasses}
                            />
                        </div>
                        <div>
                            <label className={labelClasses}>Message</label>
                            <textarea
                                value={selectedStep.config?.message || ''}
                                onChange={(e) => handleConfigChange('message', e.target.value)}
                                rows={4}
                                className={inputClasses}
                            />
                        </div>
                    </div>
                );

            case 'delay':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Type de délai</label>
                            <select
                                value={selectedStep.config?.delay_type || 'duration'}
                                onChange={(e) => handleConfigChange('delay_type', e.target.value)}
                                className={inputClasses}
                            >
                                <option value="duration">Durée fixe</option>
                                <option value="until_date">Jusqu'à une date</option>
                                <option value="until_time">Jusqu'à une heure</option>
                            </select>
                        </div>
                        {selectedStep.config?.delay_type === 'duration' && (
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className={labelClasses}>Valeur</label>
                                    <input
                                        type="number"
                                        value={selectedStep.config?.delay_value || ''}
                                        onChange={(e) => handleConfigChange('delay_value', Number(e.target.value))}
                                        className={inputClasses}
                                        min="0"
                                    />
                                </div>
                                <div>
                                    <label className={labelClasses}>Unité</label>
                                    <select
                                        value={selectedStep.config?.delay_unit || 'hours'}
                                        onChange={(e) => handleConfigChange('delay_unit', e.target.value)}
                                        className={inputClasses}
                                    >
                                        <option value="minutes">Minutes</option>
                                        <option value="hours">Heures</option>
                                        <option value="days">Jours</option>
                                        <option value="weeks">Semaines</option>
                                    </select>
                                </div>
                            </div>
                        )}
                    </div>
                );

            case 'condition':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Expression de condition</label>
                            <textarea
                                value={selectedStep.config?.expression || ''}
                                onChange={(e) => handleConfigChange('expression', e.target.value)}
                                rows={3}
                                className={`${inputClasses} font-mono text-xs`}
                                placeholder="data.amount > 1000"
                            />
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Utilisez JavaScript pour définir la condition. Les données du workflow sont accessibles via <code className="bg-gray-100 dark:bg-gray-800 px-1 rounded">data</code>.
                        </p>
                    </div>
                );

            case 'script':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Script PHP</label>
                            <textarea
                                value={selectedStep.config?.script || ''}
                                onChange={(e) => handleConfigChange('script', e.target.value)}
                                rows={8}
                                className={`${inputClasses} font-mono text-xs`}
                                placeholder="// $data contient les données du workflow\nreturn ['result' => true];"
                            />
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Le script est exécuté dans un environnement sandboxé avec accès aux données du workflow via <code className="bg-gray-100 dark:bg-gray-800 px-1 rounded">$data</code>.
                        </p>
                    </div>
                );

            case 'form':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Formulaire</label>
                            <select
                                value={selectedStep.config?.form_id || ''}
                                onChange={(e) => handleConfigChange('form_id', e.target.value)}
                                className={inputClasses}
                            >
                                <option value="">Sélectionner un formulaire</option>
                                {/* TODO: Load forms dynamically */}
                            </select>
                        </div>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={selectedStep.config?.required || false}
                                onChange={(e) => handleConfigChange('required', e.target.checked)}
                                className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                Formulaire obligatoire
                            </span>
                        </label>
                    </div>
                );

            case 'sub_workflow':
                return (
                    <div className="space-y-3">
                        <div>
                            <label className={labelClasses}>Sous-workflow</label>
                            <select
                                value={selectedStep.config?.workflow_id || ''}
                                onChange={(e) => handleConfigChange('workflow_id', e.target.value)}
                                className={inputClasses}
                            >
                                <option value="">Sélectionner un workflow</option>
                                {/* TODO: Load workflows dynamically */}
                            </select>
                        </div>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={selectedStep.config?.wait_completion || true}
                                onChange={(e) => handleConfigChange('wait_completion', e.target.checked)}
                                className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                Attendre la fin du sous-workflow
                            </span>
                        </label>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <div className="h-full flex flex-col">
            {/* Header */}
            <div className="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                        Propriétés de l'étape
                    </h3>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        {stepTypeLabels[selectedStep.type]}
                    </p>
                </div>
                <div className="flex items-center gap-1">
                    {selectedStep.type !== 'start' && selectedStep.type !== 'end' && (
                        <button
                            type="button"
                            onClick={handleDelete}
                            className="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500"
                            title="Supprimer"
                        >
                            <TrashIcon className="h-5 w-5" />
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => selectStep(null)}
                        className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto p-4 space-y-6">
                {/* Basic Properties */}
                <div className="space-y-3">
                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                        Général
                    </h4>

                    <div>
                        <label className={labelClasses}>Nom</label>
                        <input
                            type="text"
                            value={selectedStep.name}
                            onChange={(e) => handleChange('name', e.target.value)}
                            className={inputClasses}
                        />
                    </div>

                    <div>
                        <label className={labelClasses}>Description</label>
                        <textarea
                            value={selectedStep.description || ''}
                            onChange={(e) => handleChange('description', e.target.value)}
                            rows={2}
                            className={inputClasses}
                        />
                    </div>
                </div>

                {/* Type-specific config */}
                {selectedStep.type !== 'start' && selectedStep.type !== 'end' && (
                    <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                            Configuration
                        </h4>
                        {renderTypeSpecificConfig()}
                    </div>
                )}

                {/* Assignment */}
                {['task', 'approval', 'form'].includes(selectedStep.type) && (
                    <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 className="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                            Assignation
                        </h4>

                        <div>
                            <label className={labelClasses}>Type d'assignation</label>
                            <select
                                value={selectedStep.config?.assignment_type || 'user'}
                                onChange={(e) => handleConfigChange('assignment_type', e.target.value)}
                                className={inputClasses}
                            >
                                <option value="user">Utilisateur spécifique</option>
                                <option value="role">Par rôle</option>
                                <option value="department">Par département</option>
                                <option value="initiator">Initiateur du workflow</option>
                                <option value="previous">Exécutant précédent</option>
                            </select>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
