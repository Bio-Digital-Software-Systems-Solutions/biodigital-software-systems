import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { MagnifyingGlassIcon, UserIcon, UserGroupIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { apiLogger } from '@/utils/logger';

interface Recipient {
    id: number;
    type: 'user' | 'department';
    name: string;
    label: string;
    email?: string;
    code?: string;
    users_count?: number;
}

interface RecipientSelectorProps {
    value: Recipient | null;
    onChange: (recipient: Recipient | null) => void;
    error?: string;
}

export default function RecipientSelector({ value, onChange, error }: RecipientSelectorProps) {
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
            setResults(response.data);
        } catch (error) {
            apiLogger.error('Error fetching recipients', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSelect = (recipient: Recipient) => {
        onChange(recipient);
        setIsOpen(false);
        setSearch('');
    };

    const handleClear = () => {
        onChange(null);
        setSearch('');
    };

    const handleInputFocus = () => {
        setIsOpen(true);
        if (results.length === 0) {
            fetchRecipients();
        }
    };

    return (
        <div ref={wrapperRef} className="relative">
            {/* Selected Recipient Display */}
            {value ? (
                <div className="flex items-center justify-between p-3 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700">
                    <div className="flex items-center space-x-3">
                        {value.type === 'user' ? (
                            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                <UserIcon className="w-5 h-5 text-primary dark:text-blue-300" />
                            </div>
                        ) : (
                            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900 flex items-center justify-center">
                                <UserGroupIcon className="w-5 h-5 text-violet-600 dark:text-violet-300" />
                            </div>
                        )}
                        <div>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {value.name}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {value.type === 'user' ? value.email : `${value.users_count} members`}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={handleClear}
                        className="ml-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <XMarkIcon className="w-5 h-5" />
                    </button>
                </div>
            ) : (
                /* Search Input */
                <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onFocus={handleInputFocus}
                        placeholder="Search users or departments..."
                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                    />
                </div>
            )}

            {/* Dropdown Results */}
            {isOpen && !value && (
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
