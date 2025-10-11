import React from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import { CalendarIcon } from '@heroicons/react/24/outline';

interface DateTimePickerProps {
    selected: Date | null;
    onChange: (date: Date | null) => void;
    showTimeSelect?: boolean;
    timeIntervals?: number;
    dateFormat?: string;
    placeholderText?: string;
    minDate?: Date;
    maxDate?: Date;
    className?: string;
    disabled?: boolean;
    required?: boolean;
}

export default function DateTimePicker({
    selected,
    onChange,
    showTimeSelect = true,
    timeIntervals = 15,
    dateFormat = showTimeSelect ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
    placeholderText = 'Sélectionner une date',
    minDate,
    maxDate,
    className = '',
    disabled = false,
    required = false,
}: DateTimePickerProps) {
    return (
        <div className="relative">
            <DatePicker
                selected={selected}
                onChange={onChange}
                showTimeSelect={showTimeSelect}
                timeIntervals={timeIntervals}
                dateFormat={dateFormat}
                placeholderText={placeholderText}
                minDate={minDate}
                maxDate={maxDate}
                disabled={disabled}
                required={required}
                className={`block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-icc-blue focus:ring-icc-blue sm:text-sm ${className}`}
                calendarClassName="dark:bg-gray-800 dark:border-gray-700"
                wrapperClassName="w-full"
                timeCaption="Heure"
                locale="fr"
            />
            <CalendarIcon className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
        </div>
    );
}
