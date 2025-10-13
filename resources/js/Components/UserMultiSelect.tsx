import React, { useState, useEffect, useRef } from 'react';
import { User } from '@/Types';
import { MagnifyingGlassIcon, XMarkIcon, UserCircleIcon, CheckIcon } from '@heroicons/react/24/outline';
import axios from 'axios';

interface UserMultiSelectProps {
    selectedUserIds: number[];
    onChange: (userIds: number[]) => void;
    error?: string;
    label?: string;
    placeholder?: string;
    maxHeight?: string;
}

interface SimpleUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    avatar?: string;
}

export default function UserMultiSelect({
    selectedUserIds,
    onChange,
    error,
    label = 'Participants',
    placeholder = 'Rechercher des participants...',
    maxHeight = 'max-h-60',
}: UserMultiSelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [users, setUsers] = useState<SimpleUser[]>([]);
    const [loading, setLoading] = useState(false);
    const [selectedUsers, setSelectedUsers] = useState<SimpleUser[]>([]);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);

    // Fetch users from API
    const fetchUsers = async (search: string = '') => {
        setLoading(true);
        try {
            const response = await axios.get('/api/users', {
                params: { search },
            });
            setUsers(response.data);
        } catch (error) {
            console.error('Error fetching users:', error);
        } finally {
            setLoading(false);
        }
    };

    // Load initial users and selected users data
    useEffect(() => {
        fetchUsers();
    }, []);

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            fetchUsers(searchTerm);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Update selected users when selectedUserIds changes
    useEffect(() => {
        const selected = users.filter(user => selectedUserIds.includes(user.id));
        setSelectedUsers(selected);
    }, [selectedUserIds, users]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Focus search input when dropdown opens
    useEffect(() => {
        if (isOpen && searchInputRef.current) {
            searchInputRef.current.focus();
        }
    }, [isOpen]);

    const toggleUser = (user: SimpleUser) => {
        const isSelected = selectedUserIds.includes(user.id);
        const newSelectedIds = isSelected
            ? selectedUserIds.filter(id => id !== user.id)
            : [...selectedUserIds, user.id];

        onChange(newSelectedIds);
    };

    const removeUser = (userId: number) => {
        onChange(selectedUserIds.filter(id => id !== userId));
    };

    const getUserDisplayName = (user: SimpleUser) => {
        return `${user.first_name} ${user.last_name}`.trim() || user.email;
    };

    const filteredUsers = users.filter(user => !selectedUserIds.includes(user.id));

    return (
        <div className="w-full">
            {label && (
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {label}
                </label>
            )}

            <div className="relative" ref={dropdownRef}>
                {/* Selected Users Display */}
                <div
                    onClick={() => setIsOpen(!isOpen)}
                    className={`
                        min-h-[42px] w-full rounded-md border
                        ${error ? 'border-red-300 dark:border-red-600' : 'border-gray-300 dark:border-gray-600'}
                        dark:bg-gray-700 bg-white
                        shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary
                        cursor-text px-3 py-2
                    `}
                >
                    <div className="flex flex-wrap gap-2">
                        {selectedUsers.length > 0 ? (
                            selectedUsers.map(user => (
                                <div
                                    key={user.id}
                                    className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded text-sm"
                                >
                                    {user.avatar ? (
                                        <img
                                            src={user.avatar}
                                            alt={getUserDisplayName(user)}
                                            className="w-4 h-4 rounded-full"
                                        />
                                    ) : (
                                        <UserCircleIcon className="w-4 h-4" />
                                    )}
                                    <span>{getUserDisplayName(user)}</span>
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            removeUser(user.id);
                                        }}
                                        className="hover:text-blue-600 dark:hover:text-blue-300"
                                    >
                                        <XMarkIcon className="w-4 h-4" />
                                    </button>
                                </div>
                            ))
                        ) : (
                            <span className="text-gray-400 dark:text-gray-500 text-sm">
                                Sélectionner des participants...
                            </span>
                        )}
                    </div>
                </div>

                {/* Dropdown */}
                {isOpen && (
                    <div className={`
                        absolute z-50 mt-1 w-full rounded-md
                        bg-white dark:bg-gray-800
                        shadow-lg border border-gray-200 dark:border-gray-700
                    `}>
                        {/* Search Input */}
                        <div className="p-2 border-b border-gray-200 dark:border-gray-700">
                            <div className="relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    ref={searchInputRef}
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder={placeholder}
                                    className="w-full pl-9 pr-3 py-2 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary focus:ring-primary"
                                />
                            </div>
                        </div>

                        {/* User List */}
                        <div className={`overflow-y-auto ${maxHeight}`}>
                            {loading ? (
                                <div className="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Chargement...
                                </div>
                            ) : filteredUsers.length === 0 ? (
                                <div className="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {searchTerm ? 'Aucun utilisateur trouvé' : 'Tous les utilisateurs ont été sélectionnés'}
                                </div>
                            ) : (
                                <div className="py-1">
                                    {filteredUsers.map(user => (
                                        <button
                                            key={user.id}
                                            type="button"
                                            onClick={() => toggleUser(user)}
                                            className="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors"
                                        >
                                            <div className="flex-shrink-0">
                                                {user.avatar ? (
                                                    <img
                                                        src={user.avatar}
                                                        alt={getUserDisplayName(user)}
                                                        className="w-8 h-8 rounded-full"
                                                    />
                                                ) : (
                                                    <UserCircleIcon className="w-8 h-8 text-gray-400" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {getUserDisplayName(user)}
                                                </div>
                                                <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {user.email}
                                                </div>
                                            </div>
                                            <div className="flex-shrink-0">
                                                {selectedUserIds.includes(user.id) && (
                                                    <CheckIcon className="w-5 h-5 text-primary" />
                                                )}
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Selected Count */}
                        {selectedUsers.length > 0 && (
                            <div className="p-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 text-center">
                                {selectedUsers.length} participant{selectedUsers.length > 1 ? 's' : ''} sélectionné{selectedUsers.length > 1 ? 's' : ''}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Error Message */}
            {error && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>
            )}
        </div>
    );
}
