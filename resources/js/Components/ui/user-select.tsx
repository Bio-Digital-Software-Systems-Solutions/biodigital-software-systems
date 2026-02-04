import React, { useState, useEffect, useCallback } from 'react';
import { AsyncSearchableSelect, AsyncSelectOption } from '@/Components/ui/searchable-select';

interface User {
    id: number;
    uuid: string;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
}

interface UserSelectProps {
    onUserSelect: (user: User | null) => void;
    selectedUser?: User | null;
    placeholder?: string;
    className?: string;
    excludeUserIds?: number[];
}

export function UserSelect({
    onUserSelect,
    selectedUser,
    placeholder = 'Rechercher un utilisateur...',
    className,
    excludeUserIds = [],
}: UserSelectProps) {
    const [allUsers, setAllUsers] = useState<User[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    // Fetch all users on mount
    useEffect(() => {
        const fetchUsers = async () => {
            setIsLoading(true);
            try {
                const response = await fetch('/api/users', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    setAllUsers(data);
                } else {
                    console.error('Failed to fetch users');
                    setAllUsers([]);
                }
            } catch (error) {
                console.error('Error fetching users:', error);
                setAllUsers([]);
            } finally {
                setIsLoading(false);
            }
        };

        fetchUsers();
    }, []);

    // Convert selected user to AsyncSelectOption format
    const selectedOption: AsyncSelectOption | null = selectedUser
        ? {
              value: selectedUser.id,
              label: selectedUser.name,
              email: selectedUser.email,
          }
        : null;

    // Load options based on search input
    const loadOptions = useCallback(
        async (inputValue: string): Promise<AsyncSelectOption[]> => {
            const filteredUsers = allUsers.filter((user) => {
                // Exclude specified user IDs
                if (excludeUserIds.includes(user.id)) {
                    return false;
                }

                // Filter by search term
                if (inputValue) {
                    const searchLower = inputValue.toLowerCase();
                    return (
                        user.name.toLowerCase().includes(searchLower) ||
                        user.email.toLowerCase().includes(searchLower) ||
                        user.first_name.toLowerCase().includes(searchLower) ||
                        user.last_name.toLowerCase().includes(searchLower)
                    );
                }

                return true;
            });

            return filteredUsers.map((user) => ({
                value: user.id,
                label: user.name,
                email: user.email,
            }));
        },
        [allUsers, excludeUserIds]
    );

    // Handle selection change
    const handleChange = (option: AsyncSelectOption | null) => {
        if (option) {
            const user = allUsers.find((u) => u.id === option.value);
            onUserSelect(user || null);
        } else {
            onUserSelect(null);
        }
    };

    return (
        <div className={className}>
            <AsyncSearchableSelect
                value={selectedOption}
                onChange={handleChange}
                loadOptions={loadOptions}
                placeholder={placeholder}
                noOptionsMessage="Aucun utilisateur trouvé"
                loadingMessage="Recherche..."
                isClearable
                defaultOptions={!isLoading}
            />
        </div>
    );
}
