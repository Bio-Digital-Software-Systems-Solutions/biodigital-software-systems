import React from 'react';
import type { FormField } from '@/Types/form';

interface FieldRendererProps {
    field: FormField;
    value?: any;
    onChange?: (value: any) => void;
    preview?: boolean;
    error?: string;
}

export default function FieldRenderer({
    field,
    value,
    onChange,
    preview = false,
    error,
}: FieldRendererProps) {
    const baseInputClasses = `
        w-full px-3 py-2 rounded-md border
        bg-white dark:bg-gray-900
        border-gray-300 dark:border-gray-600
        text-gray-900 dark:text-white
        placeholder-gray-400 dark:placeholder-gray-500
        focus:ring-2 focus:ring-primary focus:border-primary
        disabled:bg-gray-100 dark:disabled:bg-gray-800
        disabled:cursor-not-allowed
        ${error ? 'border-red-500 focus:ring-red-500' : ''}
    `;

    const labelClasses = `
        block text-sm font-medium mb-1
        text-gray-700 dark:text-gray-300
    `;

    const renderLabel = () => {
        if (!field.label) return null;
        return (
            <label className={labelClasses}>
                {field.label}
                {field.is_required && <span className="text-red-500 ml-1">*</span>}
            </label>
        );
    };

    const renderHelperText = () => {
        if (!field.helper_text) return null;
        return (
            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {field.helper_text}
            </p>
        );
    };

    const renderError = () => {
        if (!error) return null;
        return (
            <p className="mt-1 text-xs text-red-500">{error}</p>
        );
    };

    switch (field.type) {
        case 'text':
        case 'email':
        case 'phone':
        case 'url':
        case 'password':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type={field.type === 'phone' ? 'tel' : field.type}
                        placeholder={field.placeholder}
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'textarea':
            return (
                <div>
                    {renderLabel()}
                    <textarea
                        placeholder={field.placeholder}
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        rows={4}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'number':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type="number"
                        placeholder={field.placeholder}
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        min={field.validation?.min}
                        max={field.validation?.max}
                        step={field.validation?.step}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'select':
            return (
                <div>
                    {renderLabel()}
                    <select
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        className={baseInputClasses}
                    >
                        <option value="">{field.placeholder || 'Sélectionner...'}</option>
                        {field.options?.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'multi_select':
            return (
                <div>
                    {renderLabel()}
                    <select
                        multiple
                        value={value || []}
                        onChange={(e) => {
                            const selected = Array.from(e.target.selectedOptions).map(
                                (opt) => opt.value
                            );
                            onChange?.(selected);
                        }}
                        disabled={!preview && !onChange}
                        className={`${baseInputClasses} min-h-[100px]`}
                    >
                        {field.options?.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'radio':
            return (
                <div>
                    {renderLabel()}
                    <div className="space-y-2 mt-2">
                        {field.options?.map((option) => (
                            <label
                                key={option.value}
                                className="flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="radio"
                                    name={field.name}
                                    value={option.value}
                                    checked={value === option.value}
                                    onChange={(e) => onChange?.(e.target.value)}
                                    disabled={!preview && !onChange}
                                    className="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                    {option.label}
                                </span>
                            </label>
                        ))}
                    </div>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'checkbox':
            return (
                <div>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={value || false}
                            onChange={(e) => onChange?.(e.target.checked)}
                            disabled={!preview && !onChange}
                            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        />
                        <span className="text-sm text-gray-700 dark:text-gray-300">
                            {field.label}
                            {field.is_required && <span className="text-red-500 ml-1">*</span>}
                        </span>
                    </label>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'checkbox_group':
            return (
                <div>
                    {renderLabel()}
                    <div className="space-y-2 mt-2">
                        {field.options?.map((option) => (
                            <label
                                key={option.value}
                                className="flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    value={option.value}
                                    checked={(value || []).includes(option.value)}
                                    onChange={(e) => {
                                        const current = value || [];
                                        const updated = e.target.checked
                                            ? [...current, option.value]
                                            : current.filter((v: string) => v !== option.value);
                                        onChange?.(updated);
                                    }}
                                    disabled={!preview && !onChange}
                                    className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                    {option.label}
                                </span>
                            </label>
                        ))}
                    </div>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'toggle':
            return (
                <div>
                    <label className="flex items-center gap-3 cursor-pointer">
                        <button
                            type="button"
                            role="switch"
                            aria-checked={value || false}
                            onClick={() => onChange?.(!value)}
                            disabled={!preview && !onChange}
                            className={`
                                relative inline-flex h-6 w-11 flex-shrink-0 rounded-full
                                border-2 border-transparent transition-colors duration-200
                                focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2
                                ${value ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-700'}
                            `}
                        >
                            <span
                                className={`
                                    pointer-events-none inline-block h-5 w-5 rounded-full
                                    bg-white shadow ring-0 transition duration-200
                                    ${value ? 'translate-x-5' : 'translate-x-0'}
                                `}
                            />
                        </button>
                        <span className="text-sm text-gray-700 dark:text-gray-300">
                            {field.label}
                        </span>
                    </label>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'date':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type="date"
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'time':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type="time"
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'datetime':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type="datetime-local"
                        value={value || ''}
                        onChange={(e) => onChange?.(e.target.value)}
                        disabled={!preview && !onChange}
                        className={baseInputClasses}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'file':
        case 'image':
            return (
                <div>
                    {renderLabel()}
                    <input
                        type="file"
                        accept={field.type === 'image' ? 'image/*' : undefined}
                        onChange={(e) => onChange?.(e.target.files?.[0])}
                        disabled={!preview && !onChange}
                        className={`
                            ${baseInputClasses}
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-medium
                            file:bg-primary file:text-white
                            hover:file:bg-primary/90
                        `}
                    />
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'section':
            return (
                <div className="border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {field.label}
                    </h3>
                    {field.description && (
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {field.description}
                        </p>
                    )}
                </div>
            );

        case 'group':
            return (
                <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    {field.label && (
                        <h4 className="text-md font-medium text-gray-900 dark:text-white mb-4">
                            {field.label}
                        </h4>
                    )}
                    {field.children?.map((child) => (
                        <div key={child.uuid} className="mb-4 last:mb-0">
                            <FieldRenderer
                                field={child}
                                preview={preview}
                            />
                        </div>
                    ))}
                </div>
            );

        case 'rating':
            return (
                <div>
                    {renderLabel()}
                    <div className="flex gap-1 mt-2">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <button
                                key={star}
                                type="button"
                                onClick={() => onChange?.(star)}
                                disabled={!preview && !onChange}
                                className={`
                                    text-2xl transition-colors
                                    ${(value || 0) >= star
                                        ? 'text-yellow-400'
                                        : 'text-gray-300 dark:text-gray-600'
                                    }
                                    hover:text-yellow-400
                                `}
                            >
                                ★
                            </button>
                        ))}
                    </div>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'slider':
            return (
                <div>
                    {renderLabel()}
                    <div className="flex items-center gap-4 mt-2">
                        <input
                            type="range"
                            value={value || field.validation?.min || 0}
                            onChange={(e) => onChange?.(Number(e.target.value))}
                            disabled={!preview && !onChange}
                            min={field.validation?.min || 0}
                            max={field.validation?.max || 100}
                            step={field.validation?.step || 1}
                            className="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                        />
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[3ch]">
                            {value || field.validation?.min || 0}
                        </span>
                    </div>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'color':
            return (
                <div>
                    {renderLabel()}
                    <div className="flex items-center gap-3 mt-2">
                        <input
                            type="color"
                            value={value || '#000000'}
                            onChange={(e) => onChange?.(e.target.value)}
                            disabled={!preview && !onChange}
                            className="h-10 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                        />
                        <input
                            type="text"
                            value={value || '#000000'}
                            onChange={(e) => onChange?.(e.target.value)}
                            disabled={!preview && !onChange}
                            className={`${baseInputClasses} flex-1`}
                            pattern="^#[0-9A-Fa-f]{6}$"
                        />
                    </div>
                    {renderHelperText()}
                    {renderError()}
                </div>
            );

        case 'hidden':
            return preview ? null : (
                <div className="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 border border-dashed border-gray-300 dark:border-gray-600">
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        Champ caché: <span className="font-mono">{field.name}</span>
                    </p>
                    {field.default_value && (
                        <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Valeur: {String(field.default_value)}
                        </p>
                    )}
                </div>
            );

        case 'computed':
            return preview ? (
                <div>
                    {renderLabel()}
                    <div className="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
                        <span className="text-gray-600 dark:text-gray-400">
                            {value || 'Valeur calculée'}
                        </span>
                    </div>
                </div>
            ) : (
                <div className="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 border border-dashed border-gray-300 dark:border-gray-600">
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        Champ calculé: <span className="font-mono">{field.label}</span>
                    </p>
                </div>
            );

        default:
            return (
                <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                    <p className="text-sm text-yellow-700 dark:text-yellow-400">
                        Type de champ non supporté: {field.type}
                    </p>
                </div>
            );
    }
}
