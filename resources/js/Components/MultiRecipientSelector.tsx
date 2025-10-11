import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { MagnifyingGlassIcon, UserIcon, UserGroupIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { apiLogger } from '@/utils/logger';

export interface Recipient {
    id: number;
    type: 'user' | 'department';
    name: string;
    label: string;
    email?: string;
    code?: string;
    users_count?: number;
}

interface MultiRecipientSelectorProps {
    value: Recipient[];
    onChange: (recipients: Recipient[]) => void;
    error?: string;
    placeholder?: string;
    label?: string;
}

export default function MultiRecipientSelector({
    value,
    onChange,
    error,
    placeholder = "Search users or departments...",
    label = "Recipients"
}: MultiRecipientSelectorProps) {
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<Recipient[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);

    // Fetch recipients on mount and when search changes
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            fetchRecipients();
        }, 300);

        return () => clearTimeout(delayDebounceFn);
    }, [search]);

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [wrapperRef]);

    const fetchRecipients = async () => {
        setIsLoading(true);
        try {
            const response = await axios.get(route('messages.search-recipients'), {
                params: { search },
            });
            // Filter out already selected recipients
            const filtered = response.data.filter((r: Recipient) =>
                !value.some(v => v.id === r.id && v.type === r.type)
            );
            setResults(filtered);
        } catch (error) {
            apiLogger.error('Error fetching recipients', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSelect = (recipient: Recipient) => {
        onChange([...value, recipient]);
        setSearch('');
        setIsOpen(false);
    };

    const handleRemove = (recipient: Recipient) => {
        onChange(value.filter(r => !(r.id === recipient.id && r.type === recipient.type)));
    };

    return (
        <div ref={wrapperRef} className="relative">
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {label}
            </label>

            {/* Selected Recipients */}
            {value.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-2">
                    {value.map((recipient, index) => (
                        <div
                            key={`${recipient.type}-${recipient.id}-${index}`}
                            className="flex items-center space-x-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md px-3 py-1.5"
                        >
                            {recipient.type === 'user' ? (
                                <UserIcon className="w-4 h-4 text-primary dark:text-blue-300" />
                            ) : (
                                <UserGroupIcon className="w-4 h-4 text-violet-600 dark:text-violet-300" />
                            )}
                            <span className="text-sm text-gray-900 dark:text-white">{recipient.name}</span>
                            <button
                                type="button"
                                onClick={() => handleRemove(recipient)}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <XMarkIcon className="w-4 h-4" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {/* Search Input */}
            <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                </div>
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onFocus={() => setIsOpen(true)}
                    placeholder={placeholder}
                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                />
            </div>

            {/* Dropdown Results */}
            {isOpen && (
                <div className="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                    {isLoading ? (
                        <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                            Loading...
                        </div>
                    ) : results.length > 0 ? (
                        <>
                            {/* Users Section */}
                            {results.filter(r => r.type === 'user').length > 0 && (
                                <>
                                    <div className="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Users
                                    </div>
                                    {results
                                        .filter(r => r.type === 'user')
                                        .map((recipient) => (
                                            <button
                                                key={`user-${recipient.id}`}
                                                type="button"
                                                onClick={() => handleSelect(recipient)}
                                                className="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
                                            >
                                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                    <UserIcon className="w-4 h-4 text-primary dark:text-blue-300" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {recipient.name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {recipient.email}
                                                    </p>
                                                </div>
                                            </button>
                                        ))}
                                </>
                            )}

                            {/* Departments Section */}
                            {results.filter(r => r.type === 'department').length > 0 && (
                                <>
                                    <div className="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-t dark:border-gray-700 mt-1">
                                        Departments
                                    </div>
                                    {results
                                        .filter(r => r.type === 'department')
                                        .map((recipient) => (
                                            <button
                                                key={`dept-${recipient.id}`}
                                                type="button"
                                                onClick={() => handleSelect(recipient)}
                                                className="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
                                            >
                                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900 flex items-center justify-center">
                                                    <UserGroupIcon className="w-4 h-4 text-violet-600 dark:text-violet-300" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {recipient.name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {recipient.users_count} members
                                                    </p>
                                                </div>
                                            </button>
                                        ))}
                                </>
                            )}
                        </>
                    ) : (
                        <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                            {search ? 'No results found' : 'Start typing to search...'}
                        </div>
                    )}
                </div>
            )}

            {/* Error Message */}
            {error && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
