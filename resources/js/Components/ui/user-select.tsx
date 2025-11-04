import React, { useState, useEffect } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Search, User, X } from 'lucide-react';

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
}

export function UserSelect({ onUserSelect, selectedUser, placeholder = "Sélectionner un utilisateur...", className }: UserSelectProps) {
    const [users, setUsers] = useState<User[]>([]);
    const [allUsers, setAllUsers] = useState<User[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [showSearch, setShowSearch] = useState(false);

    const fetchUsers = async () => {
        setLoading(true);
        try {
            const response = await fetch('/api/users', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setAllUsers(data);
                setUsers(data);
            } else {
                console.error('Failed to fetch users');
                setUsers([]);
                setAllUsers([]);
            }
        } catch (error) {
            console.error('Error fetching users:', error);
            setUsers([]);
            setAllUsers([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUsers();
    }, []);

    useEffect(() => {
        if (search) {
            const filtered = allUsers.filter(user =>
                user.name.toLowerCase().includes(search.toLowerCase()) ||
                user.email.toLowerCase().includes(search.toLowerCase())
            );
            setUsers(filtered);
        } else {
            setUsers(allUsers);
        }
    }, [search, allUsers]);

    const handleSelect = (userId: string) => {
        if (userId === 'clear') {
            onUserSelect(null);
            setShowSearch(false);
            setSearch('');
            return;
        }

        const user = users.find(u => u.id.toString() === userId);
        if (user) {
            onUserSelect(user);
            setShowSearch(false);
            setSearch('');
        }
    };

    return (
        <div className={`space-y-2 ${className}`}>
            <div className="flex items-center space-x-2">
                <div className="flex-1">
                    <Select
                        value={selectedUser ? selectedUser.id.toString() : ''}
                        onValueChange={handleSelect}
                    >
                        <SelectTrigger className="w-full">
                            <div className="flex items-center space-x-2">
                                <User className="h-4 w-4" />
                                <SelectValue placeholder={placeholder} />
                            </div>
                        </SelectTrigger>
                        <SelectContent>
                            {selectedUser && (
                                <SelectItem value="clear">
                                    <div className="flex items-center space-x-2 text-red-600">
                                        <X className="h-4 w-4" />
                                        <span>Effacer la sélection</span>
                                    </div>
                                </SelectItem>
                            )}
                            {loading ? (
                                <SelectItem value="loading">
                                    Chargement...
                                </SelectItem>
                            ) : users.length === 0 ? (
                                <SelectItem value="empty">
                                    Aucun utilisateur trouvé
                                </SelectItem>
                            ) : (
                                users.map((user) => (
                                    <SelectItem key={user.id} value={user.id.toString()}>
                                        <div className="flex items-center space-x-2">
                                            <User className="h-4 w-4" />
                                            <div>
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-sm text-gray-500">{user.email}</div>
                                            </div>
                                        </div>
                                    </SelectItem>
                                ))
                            )}
                        </SelectContent>
                    </Select>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setShowSearch(!showSearch)}
                    className="flex-shrink-0"
                >
                    <Search className="h-4 w-4" />
                </Button>
            </div>
            {showSearch && (
                <Input
                    type="text"
                    placeholder="Rechercher un utilisateur..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full"
                />
            )}
        </div>
    );
}