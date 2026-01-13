import React from 'react';
import Select, { StylesConfig, SingleValue, GroupBase } from 'react-select';

export interface SelectOption {
    value: string | number;
    label: string;
}

interface SearchableSelectProps {
    options: SelectOption[];
    value: string | number | null;
    onChange: (value: string | number | null) => void;
    placeholder?: string;
    isDisabled?: boolean;
    isClearable?: boolean;
    isLoading?: boolean;
    noOptionsMessage?: string;
    className?: string;
    id?: string;
}

export function SearchableSelect({
    options,
    value,
    onChange,
    placeholder = 'Sélectionner...',
    isDisabled = false,
    isClearable = true,
    isLoading = false,
    noOptionsMessage = 'Aucune option',
    className = '',
    id,
}: SearchableSelectProps) {
    const selectedOption = options.find((opt) => opt.value === value) || null;

    const handleChange = (newValue: SingleValue<SelectOption>) => {
        onChange(newValue ? newValue.value : null);
    };

    // Custom styles for dark mode support
    const customStyles: StylesConfig<SelectOption, false, GroupBase<SelectOption>> = {
        control: (base, state) => ({
            ...base,
            minHeight: '38px',
            backgroundColor: 'var(--select-bg)',
            borderColor: state.isFocused ? 'var(--select-border-focus)' : 'var(--select-border)',
            borderRadius: '0.375rem',
            boxShadow: state.isFocused ? '0 0 0 2px var(--select-ring)' : 'none',
            '&:hover': {
                borderColor: 'var(--select-border-hover)',
            },
        }),
        menu: (base) => ({
            ...base,
            backgroundColor: 'var(--select-bg)',
            borderColor: 'var(--select-border)',
            borderRadius: '0.375rem',
            boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            zIndex: 50,
        }),
        menuList: (base) => ({
            ...base,
            padding: '4px',
        }),
        option: (base, state) => ({
            ...base,
            backgroundColor: state.isSelected
                ? 'var(--select-option-selected-bg)'
                : state.isFocused
                ? 'var(--select-option-hover-bg)'
                : 'transparent',
            color: state.isSelected
                ? 'var(--select-option-selected-text)'
                : 'var(--select-text)',
            borderRadius: '0.25rem',
            cursor: 'pointer',
            '&:active': {
                backgroundColor: 'var(--select-option-active-bg)',
            },
        }),
        singleValue: (base) => ({
            ...base,
            color: 'var(--select-text)',
        }),
        input: (base) => ({
            ...base,
            color: 'var(--select-text)',
        }),
        placeholder: (base) => ({
            ...base,
            color: 'var(--select-placeholder)',
        }),
        indicatorSeparator: (base) => ({
            ...base,
            backgroundColor: 'var(--select-border)',
        }),
        dropdownIndicator: (base) => ({
            ...base,
            color: 'var(--select-indicator)',
            '&:hover': {
                color: 'var(--select-indicator-hover)',
            },
        }),
        clearIndicator: (base) => ({
            ...base,
            color: 'var(--select-indicator)',
            '&:hover': {
                color: 'var(--select-indicator-hover)',
            },
        }),
        noOptionsMessage: (base) => ({
            ...base,
            color: 'var(--select-placeholder)',
        }),
        loadingMessage: (base) => ({
            ...base,
            color: 'var(--select-placeholder)',
        }),
    };

    return (
        <div className={`searchable-select ${className}`}>
            <Select<SelectOption, false>
                inputId={id}
                options={options}
                value={selectedOption}
                onChange={handleChange}
                placeholder={placeholder}
                isDisabled={isDisabled}
                isClearable={isClearable}
                isLoading={isLoading}
                isSearchable
                noOptionsMessage={() => noOptionsMessage}
                loadingMessage={() => 'Chargement...'}
                styles={customStyles}
                classNamePrefix="react-select"
            />
        </div>
    );
}

export default SearchableSelect;
