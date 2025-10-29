import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PlusIcon, FunnelIcon, EyeIcon, PencilIcon, TrashIcon, EnvelopeIcon, EnvelopeOpenIcon, PaperAirplaneIcon, InboxIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { PageProps } from '@/Types';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Recipient {
    id: number;
    name: string;
    email: string;
}

interface Message {
    id: number;
    uuid: string;
    subject?: string;
    content: string;
    type: 'direct' | 'broadcast' | 'system';
    read_at?: string;
    created_at: string;
    sender: User;
    receiver: User;
    excerpt: string;
    type_label: string;
    is_sent?: boolean;
    all_recipients?: Recipient[];
    recipients_count?: number;
    cc_list?: number[];
    bcc_list?: number[];
}

interface Props extends PageProps {
    messages: {
        data: Message[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        [key: string]: string | undefined;
        type?: string;
        status?: string;
        search?: string;
        mailbox?: string;
    };
}

export default function Index({ messages, filters, auth }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [messageToDelete, setMessageToDelete] = useState<Message | null>(null);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value };
        if (!value) delete newFilters[key];
        
        router.get(route('messages.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (message: Message) => {
        setMessageToDelete(message);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (messageToDelete) {
            router.delete(route('messages.destroy', messageToDelete.uuid), {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setMessageToDelete(null);
                },
            });
        }
    };

    const handleMarkAsRead = (message: Message) => {
        if (!message.read_at) {
            router.patch(route('messages.mark-as-read', message.uuid));
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'direct': return 'text-primary bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20';
            case 'broadcast': return 'text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20';
            case 'system': return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
            default: return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
        }
    };

    const isCurrentUserSender = (message: Message) => {
        return message.sender.id === auth.user?.id;
    };

    const isCurrentUserReceiver = (message: Message) => {
        return message.receiver.id === auth.user?.id;
    };

    return (
        <DashboardLayout
            title="Messages"
            description="Gérez vos messages et communications"
            actions={
                <>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-200"
                    >
                        <FunnelIcon className="w-4 h-4 mr-2" />
                        Filtres
                    </button>
                    <Link
                        href={route('messages.create')}
                        className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-lg transition duration-200"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Nouveau message
                    </Link>
                </>
            }
        >
            <Head title="Messages" />

            {showFilters && (
                                <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Mailbox
                                            </label>
                                            <select
                                                value={filters.mailbox || 'inbox'}
                                                onChange={(e) => handleFilter('mailbox', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="inbox">Inbox</option>
                                                <option value="sent">Sent Messages</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Type
                                            </label>
                                            <select
                                                value={filters.type || ''}
                                                onChange={(e) => handleFilter('type', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Types</option>
                                                <option value="direct">Direct Message</option>
                                                <option value="broadcast">Broadcast</option>
                                                <option value="system">System Message</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Status
                                            </label>
                                            <select
                                                value={filters.status || ''}
                                                onChange={(e) => handleFilter('status', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Messages</option>
                                                <option value="unread">Unread</option>
                                                <option value="read">Read</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Search
                                            </label>
                                            <input
                                                type="text"
                                                value={filters.search || ''}
                                                onChange={(e) => handleFilter('search', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                placeholder="Search messages..."
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Mobile Card Layout */}
                            <div className="block lg:hidden space-y-4">
                                {messages.data.map((message) => (
                                    <div
                                        key={message.id}
                                        className={`bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3 ${
                                            message.is_sent
                                                ? 'border-l-4 border-green-500'
                                                : (!message.read_at ? 'border-l-4 border-primary' : '')
                                        }`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center space-x-2">
                                                    {message.is_sent ? (
                                                        <PaperAirplaneIcon className="w-4 h-4 text-green-600" />
                                                    ) : !message.read_at ? (
                                                        <EnvelopeIcon className="w-4 h-4 text-primary" />
                                                    ) : (
                                                        <InboxIcon className="w-4 h-4 text-gray-400" />
                                                    )}
                                                    <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                        {message.subject || 'No Subject'}
                                                    </h3>
                                                </div>
                                                {message.is_sent && message.all_recipients ? (
                                                    <div className="mt-1">
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                                            To: {message.recipients_count} {message.recipients_count === 1 ? 'recipient' : 'recipients'}
                                                        </p>
                                                        <div className="flex flex-wrap gap-1 mt-1">
                                                            {message.all_recipients.slice(0, 2).map((recipient, idx) => (
                                                                <span
                                                                    key={idx}
                                                                    className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200"
                                                                >
                                                                    {recipient.name}
                                                                </span>
                                                            ))}
                                                            {message.recipients_count && message.recipients_count > 2 && (
                                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                                                                    +{message.recipients_count - 2}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        From: {message.sender.first_name} {message.sender.last_name}
                                                    </p>
                                                )}
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                                    {message.excerpt}
                                                </p>
                                            </div>
                                            <div className="flex space-x-2 ml-4">
                                                <Link
                                                    href={route('messages.show', message.uuid)}
                                                    className="p-1 text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    onClick={() => handleMarkAsRead(message)}
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                                {isCurrentUserSender(message) && (
                                                    <>
                                                        <Link
                                                            href={route('messages.edit', message.uuid)}
                                                            className="p-1 text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(message)}
                                                            className="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        
                                        <div className="grid grid-cols-2 gap-3 text-xs">
                                            <div>
                                                <span className="text-gray-500 dark:text-gray-400">Type:</span>
                                                <span className={`inline-flex px-2 py-1 ml-1 text-xs font-semibold rounded-full ${getTypeColor(message.type)}`}>
                                                    {message.type_label}
                                                </span>
                                            </div>
                                            <div>
                                                <span className="text-gray-500 dark:text-gray-400">Date:</span>
                                                <span className="text-gray-900 dark:text-gray-100 ml-1">
                                                    {new Date(message.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Desktop Table Layout */}
                            <div className="hidden lg:block overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Message
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Recipients
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {messages.data.map((message) => (
                                            <tr
                                                key={message.id}
                                                className={
                                                    message.is_sent
                                                        ? 'bg-green-50 dark:bg-green-900/10'
                                                        : (!message.read_at ? 'bg-blue-50 dark:bg-blue-900/10' : '')
                                                }
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        {message.is_sent ? (
                                                            <PaperAirplaneIcon className="w-5 h-5 text-green-600 mr-3" />
                                                        ) : !message.read_at ? (
                                                            <EnvelopeIcon className="w-5 h-5 text-primary mr-3" />
                                                        ) : (
                                                            <InboxIcon className="w-5 h-5 text-gray-400 mr-3" />
                                                        )}
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {message.subject || 'No Subject'}
                                                            </div>
                                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                {message.excerpt}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getTypeColor(message.type)}`}>
                                                        {message.type_label}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                    {message.is_sent && message.all_recipients ? (
                                                        <div>
                                                            <div className="font-medium text-gray-500 dark:text-gray-400 text-xs mb-1">
                                                                To: {message.recipients_count} {message.recipients_count === 1 ? 'recipient' : 'recipients'}
                                                            </div>
                                                            <div className="flex flex-wrap gap-1">
                                                                {message.all_recipients.slice(0, 3).map((recipient, idx) => (
                                                                    <span
                                                                        key={idx}
                                                                        className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                                                        title={recipient.email}
                                                                    >
                                                                        {recipient.name}
                                                                    </span>
                                                                ))}
                                                                {message.recipients_count && message.recipients_count > 3 && (
                                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                                        +{message.recipients_count - 3} more
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <div>
                                                            <div className="font-medium">
                                                                {`${message.sender.first_name} ${message.sender.last_name}`}
                                                            </div>
                                                            <div className="text-gray-500 dark:text-gray-400 text-xs">
                                                                From
                                                            </div>
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {new Date(message.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex space-x-2">
                                                        <Link
                                                            href={route('messages.show', message.uuid)}
                                                            className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                            onClick={() => handleMarkAsRead(message)}
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>
                                                        {isCurrentUserSender(message) && (
                                                            <>
                                                                <Link
                                                                    href={route('messages.edit', message.uuid)}
                                                                    className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                >
                                                                    <PencilIcon className="w-4 h-4" />
                                                                </Link>
                                                                <button
                                                                    onClick={() => handleDelete(message)}
                                                                    className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                >
                                                                    <TrashIcon className="w-4 h-4" />
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {messages.data.length === 0 && (
                                <div className="text-center py-8">
                                    <EnvelopeIcon className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No messages</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Get started by sending a new message.
                                    </p>
                                    <div className="mt-6">
                                        <Link
                                            href={route('messages.create')}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            New Message
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {/* Pagination */}
                            {messages.last_page > 1 && (
                                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-6 space-y-4 sm:space-y-0">
                                    <div className="text-xs sm:text-sm text-gray-700 dark:text-gray-300 text-center sm:text-left">
                                        Showing {((messages.current_page - 1) * messages.per_page) + 1} to{' '}
                                        {Math.min(messages.current_page * messages.per_page, messages.total)} of{' '}
                                        {messages.total} results
                                    </div>
                                    <div className="flex justify-center sm:justify-end">
                                        <div className="flex space-x-1 sm:space-x-2">
                                            {messages.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-sm font-medium rounded-md ${
                                                        link.active
                                                            ? 'bg-primary text-white'
                                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer le message"
                description={`Êtes-vous sûr de vouloir supprimer le message "${messageToDelete?.subject || 'sans sujet'}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}