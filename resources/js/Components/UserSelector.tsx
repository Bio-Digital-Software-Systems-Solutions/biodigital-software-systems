import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface User {
    id: number;
    name: string;
    first_name?: string;
    last_name?: string;
    email: string;
}

interface UserSelectorProps {
    selectedUsers: User[];
    onUsersChange: (users: User[]) => void;
    availableUsers?: User[];
}

export default function UserSelector({ selectedUsers, onUsersChange, availableUsers = [] }: UserSelectorProps) {
    const [users, setUsers] = useState<User[]>(availableUsers);
    const [searchTerm, setSearchTerm] = useState('');
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    // Fetch users if not provided
    useEffect(() => {
        if (availableUsers.length === 0) {
            setLoading(true);
            fetch('/api/users')
                .then(res => res.json())
                .then(data => {
                    setUsers(data);
                    setLoading(false);
                })
                .catch(() => setLoading(false));
        }
    }, []);

    const filteredUsers = users.filter(user => {
        const fullName = `${user.first_name || ''} ${user.last_name || user.name}`.toLowerCase();
        const email = user.email.toLowerCase();
        const search = searchTerm.toLowerCase();
        return fullName.includes(search) || email.includes(search);
    }).filter(user => !selectedUsers.find(u => u.id === user.id));

    const handleAddUser = (user: User) => {
        onUsersChange([...selectedUsers, user]);
        setSearchTerm('');
    };

    const handleRemoveUser = (userId: number) => {
        onUsersChange(selectedUsers.filter(u => u.id !== userId));
    };

    const getUserDisplayName = (user: User) => {
        if (user.first_name && user.last_name) {
            return `${user.first_name} ${user.last_name}`;
        }
        return user.name;
    };

    return (
        <div className="relative">
            <div className="flex items-start gap-2">
                <svg className="h-5 w-5 text-gray-400 mt-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <div className="flex-1">
                    {/* Selected Users */}
                    {selectedUsers.length > 0 && (
                        <div className="flex flex-wrap gap-2 mb-2">
                            {selectedUsers.map(user => (
                                <div
                                    key={user.id}
                                    className="inline-flex items-center gap-2 px-3 py-1 bg-icc-blue/10 dark:bg-icc-blue/20 text-icc-blue dark:text-icc-blue rounded-full text-sm"
                                >
                                    <span>{getUserDisplayName(user)}</span>
                                    <button
                                        type="button"
                                        onClick={() => handleRemoveUser(user.id)}
                                        className="hover:bg-icc-blue/20 dark:hover:bg-icc-blue/30 rounded-full p-0.5"
                                    >
                                        <XMarkIcon className="w-3 h-3" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Search Input */}
                    <div className="relative">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => {
                                    setSearchTerm(e.target.value);
                                    setIsOpen(true);
                                }}
                                onFocus={() => setIsOpen(true)}
                                className="w-full pl-10 pr-4 py-2 border-0 border-b border-gray-200 dark:border-gray-700 focus:ring-0 focus:border-icc-blue bg-transparent dark:text-white placeholder-gray-400 text-sm"
                                placeholder={selectedUsers.length > 0 ? "Ajouter d'autres participants..." : "Add guests"}
                            />
                        </div>

                        {/* Dropdown */}
                        {isOpen && searchTerm && (
                            <>
                                <div
                                    className="fixed inset-0 z-10"
                                    onClick={() => setIsOpen(false)}
                                />
                                <div className="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 max-h-60 overflow-auto z-20">
                                    {loading ? (
                                        <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            Chargement...
                                        </div>
                                    ) : filteredUsers.length > 0 ? (
                                        filteredUsers.map(user => (
                                            <button
                                                key={user.id}
                                                type="button"
                                                onClick={() => {
                                                    handleAddUser(user);
                                                    setIsOpen(false);
                                                }}
                                                className="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-3"
                                            >
                                                <div className="w-8 h-8 rounded-full bg-icc-blue text-white flex items-center justify-center text-sm font-medium">
                                                    {(user.first_name?.[0] || user.name[0]).toUpperCase()}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {getUserDisplayName(user)}
                                                    </div>
                                                    <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {user.email}
                                                    </div>
                                                </div>
                                            </button>
                                        ))
                                    ) : (
                                        <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            Aucun utilisateur trouvé
                                        </div>
                                    )}
                                </div>
                            </>
                        )}
                    </div>

                    {selectedUsers.length > 0 && (
                        <div className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {selectedUsers.length} participant{selectedUsers.length > 1 ? 's' : ''} invité{selectedUsers.length > 1 ? 's' : ''}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
