import React, { useState, useEffect, useRef } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { apiLogger } from '@/utils/logger';
import { 
    ChatBubbleLeftRightIcon,
    PlusIcon,
    PaperAirplaneIcon,
    UsersIcon,
    MagnifyingGlassIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
}

interface ChatMessage {
    id: number;
    content: string;
    created_at: string;
    sender: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
    };
    is_read: boolean;
}

interface ChatRoom {
    id: number;
    name: string;
    type: 'direct' | 'group';
    created_by: number;
    participants?: User[];
    lastMessage?: {
        id: number;
        content: string;
        created_at: string;
        sender: {
            id: number;
            first_name: string;
            last_name: string;
            full_name: string;
        };
    };
    updated_at: string;
}

interface ChatPageProps extends PageProps {
    chatRooms: ChatRoom[];
    users: User[];
}

export default function Index() {
    const { chatRooms: initialChatRooms, users, auth } = usePage<ChatPageProps>().props;
    
    // Ensure all chat rooms have participants array initialized
    const sanitizedChatRooms = initialChatRooms.map(room => ({
        ...room,
        participants: room.participants || []
    }));
    
    const [chatRooms, setChatRooms] = useState<ChatRoom[]>(sanitizedChatRooms);
    const [selectedRoom, setSelectedRoom] = useState<ChatRoom | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [showNewChatModal, setShowNewChatModal] = useState(false);
    const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
    const [searchUsers, setSearchUsers] = useState('');
    const [loading, setLoading] = useState(false);

    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const loadMessages = async (room: ChatRoom) => {
        try {
            // Ensure room has participants before setting as selected
            const roomWithParticipants = {
                ...room,
                participants: room.participants || []
            };
            
            const response = await fetch(`/chat/rooms/${room.id}/messages`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                credentials: 'same-origin',
            });
            
            if (response.ok) {
                const data = await response.json();
                setMessages(data.messages || []);
                setSelectedRoom(roomWithParticipants);
            } else {
                apiLogger.error('Failed to load messages', { status: response.statusText });
            }
        } catch (error) {
            apiLogger.error('Failed to load messages', error);
        }
    };

    const sendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRoom || !newMessage.trim()) return;

        setLoading(true);
        try {
            const response = await fetch(`/chat/rooms/${selectedRoom.id}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    content: newMessage.trim(),
                }),
            });

            if (response.ok) {
                const data = await response.json();
                setMessages(prev => [...prev, data.message]);
                setNewMessage('');
                
                // Update room in list
                setChatRooms(prev => prev.map(room => 
                    room.id === selectedRoom.id 
                        ? { ...room, lastMessage: data.message, updated_at: new Date().toISOString() }
                        : room
                ).sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()));
            } else {
                apiLogger.error('Failed to send message', { status: response.status, statusText: response.statusText });
            }
        } catch (error) {
            apiLogger.error('Failed to send message', error);
        } finally {
            setLoading(false);
        }
    };

    const createChatRoom = async () => {
        if (selectedUsers.length === 0) return;

        setLoading(true);
        try {
            const response = await fetch('/chat/rooms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    type: selectedUsers.length === 1 ? 'direct' : 'group',
                    participant_ids: selectedUsers,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                const newRoom = {
                    ...data.room,
                    participants: data.room.participants || []
                };
                
                // Check if room already exists in list
                const existingRoom = chatRooms.find(room => room.id === newRoom.id);
                if (!existingRoom) {
                    setChatRooms(prev => [newRoom, ...prev]);
                }
                
                setSelectedRoom(newRoom);
                setMessages([]);
                setShowNewChatModal(false);
                setSelectedUsers([]);
                setSearchUsers('');
            } else {
                apiLogger.error('Failed to create chat room', { status: response.status, statusText: response.statusText });
            }
        } catch (error) {
            apiLogger.error('Failed to create chat room', error);
        } finally {
            setLoading(false);
        }
    };

    const formatTime = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInHours = Math.abs(now.getTime() - date.getTime()) / (1000 * 60 * 60);

        if (diffInHours < 24) {
            return date.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } else if (diffInHours < 24 * 7) {
            return date.toLocaleDateString('fr-FR', {
                weekday: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            return date.toLocaleDateString('fr-FR', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };

    const filteredUsers = users.filter(user =>
        user.full_name.toLowerCase().includes(searchUsers.toLowerCase()) ||
        user.email.toLowerCase().includes(searchUsers.toLowerCase())
    );

    return (
        <DashboardLayout>
            <Head title="Chat - AIG-App" />

            <div className="h-[calc(100vh-4rem)] flex">
                {/* Chat Rooms Sidebar */}
                <div className="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
                    {/* Header */}
                    <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Messages
                            </h2>
                            <button
                                onClick={() => setShowNewChatModal(true)}
                                className="p-2 bg-primary hover:bg-primary text-white rounded-lg transition duration-200"
                            >
                                <PlusIcon className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {/* Chat Rooms List */}
                    <div className="flex-1 overflow-y-auto">
                        {chatRooms.length === 0 ? (
                            <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                <ChatBubbleLeftRightIcon className="mx-auto h-12 w-12 mb-2" />
                                <p className="text-sm">Aucune conversation</p>
                                <p className="text-xs mt-1">Créez votre première conversation</p>
                            </div>
                        ) : (
                            <div className="space-y-1 p-2">
                                {chatRooms.map((room) => (
                                    <button
                                        key={room.id}
                                        onClick={() => loadMessages(room)}
                                        className={`w-full p-3 text-left rounded-lg transition duration-200 ${
                                            selectedRoom?.id === room.id
                                                ? 'bg-blue-100 dark:bg-blue-900'
                                                : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <div className="flex items-start space-x-3">
                                            <div className="flex-shrink-0">
                                                <div className="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                                    {room.type === 'direct' ? (
                                                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {room.participants?.find(p => p.id !== auth.user?.id)?.first_name?.charAt(0) || 'U'}
                                                        </span>
                                                    ) : (
                                                        <UsersIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {room.name}
                                                </p>
                                                {room.lastMessage && (
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {room.lastMessage.sender.id === auth.user?.id ? 'Vous: ' : ''}
                                                        {room.lastMessage.content}
                                                    </p>
                                                )}
                                                <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    {formatTime(room.updated_at)}
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Messages Area */}
                <div className="flex-1 flex flex-col">
                    {selectedRoom ? (
                        <>
                            {/* Chat Header */}
                            <div className="p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <div className="flex items-center space-x-3">
                                    <div className="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        {selectedRoom.type === 'direct' ? (
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {selectedRoom.participants?.find(p => p.id !== auth.user?.id)?.first_name?.charAt(0) || 'U'}
                                            </span>
                                        ) : (
                                            <UsersIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                        )}
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                            {selectedRoom.name}
                                        </h3>
                                        {selectedRoom.type === 'group' && (
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {selectedRoom.participants?.length || 0} participants
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Messages */}
                            <div className="flex-1 overflow-y-auto p-4 space-y-4">
                                {messages.map((message) => (
                                    <div
                                        key={message.id}
                                        className={`flex ${
                                            message.sender.id === auth.user?.id ? 'justify-end' : 'justify-start'
                                        }`}
                                    >
                                        <div
                                            className={`lg:px-4 py-2 rounded-lg ${
                                                message.sender.id === auth.user?.id
                                                    ? 'bg-primary text-white'
                                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
                                            }`}
                                        >
                                            {message.sender.id !== auth.user?.id && selectedRoom.type === 'group' && (
                                                <p className="text-xs font-medium mb-1 opacity-70">
                                                    {message.sender.full_name}
                                                </p>
                                            )}
                                            <p className="text-sm">{message.content}</p>
                                            <p className="text-xs opacity-70 mt-1">
                                                {formatTime(message.created_at)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                <div ref={messagesEndRef} />
                            </div>

                            {/* Message Input */}
                            <div className="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                <form onSubmit={sendMessage} className="flex space-x-2">
                                    <input
                                        type="text"
                                        value={newMessage}
                                        onChange={(e) => setNewMessage(e.target.value)}
                                        placeholder="Tapez votre message..."
                                        className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        disabled={loading}
                                    />
                                    <button
                                        type="submit"
                                        disabled={loading || !newMessage.trim()}
                                        className="px-4 py-2 bg-primary hover:bg-primary disabled:bg-blue-400 text-white rounded-lg transition duration-200"
                                    >
                                        <PaperAirplaneIcon className="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </>
                    ) : (
                        <div className="flex-1 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <div className="text-center">
                                <ChatBubbleLeftRightIcon className="mx-auto h-16 w-16 mb-4" />
                                <p className="text-lg font-medium">Sélectionnez une conversation</p>
                                <p className="text-sm mt-1">Choisissez une conversation existante ou créez-en une nouvelle</p>
                            </div>
                        </div>
                    )}
                </div>

                {/* New Chat Modal */}
                {showNewChatModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full mx-4">
                            <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Nouvelle conversation
                                </h3>
                                <button
                                    onClick={() => {
                                        setShowNewChatModal(false);
                                        setSelectedUsers([]);
                                        setSearchUsers('');
                                    }}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <XMarkIcon className="h-5 w-5" />
                                </button>
                            </div>

                            <div className="p-4">
                                {/* Search Users */}
                                <div className="mb-4">
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={searchUsers}
                                            onChange={(e) => setSearchUsers(e.target.value)}
                                            placeholder="Rechercher des utilisateurs..."
                                            className="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        />
                                        <MagnifyingGlassIcon className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                    </div>
                                </div>

                                {/* Users List */}
                                <div className="max-h-60 overflow-y-auto space-y-2 mb-4">
                                    {filteredUsers.map((user) => (
                                        <label
                                            key={user.id}
                                            className="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedUsers.includes(user.id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setSelectedUsers([...selectedUsers, user.id]);
                                                    } else {
                                                        setSelectedUsers(selectedUsers.filter(id => id !== user.id));
                                                    }
                                                }}
                                                className="mr-3"
                                            />
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {user.full_name}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {user.email}
                                                </p>
                                            </div>
                                        </label>
                                    ))}
                                </div>

                                {/* Actions */}
                                <div className="flex justify-end space-x-2">
                                    <button
                                        onClick={() => {
                                            setShowNewChatModal(false);
                                            setSelectedUsers([]);
                                            setSearchUsers('');
                                        }}
                                        className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        onClick={createChatRoom}
                                        disabled={selectedUsers.length === 0 || loading}
                                        className="px-4 py-2 bg-primary hover:bg-primary disabled:bg-blue-400 text-white rounded-lg transition duration-200"
                                    >
                                        {loading ? 'Création...' : 'Créer'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}