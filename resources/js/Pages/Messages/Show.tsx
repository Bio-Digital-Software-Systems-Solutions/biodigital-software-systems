import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon, PencilIcon, TrashIcon, EnvelopeIcon, ClockIcon, UserIcon, ArrowUturnRightIcon } from '@heroicons/react/24/outline';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Message {
    id: number;
    subject?: string;
    content: string;
    type: 'direct' | 'broadcast' | 'system';
    read_at?: string;
    created_at: string;
    updated_at: string;
    sender: User;
    receiver: User;
    type_label: string;
}

interface Props extends PageProps {
    message: Message;
}

export default function Show({ message, auth }: Props) {
    const isCurrentUserSender = message.sender.id === auth.user?.id;
    const isCurrentUserReceiver = message.receiver.id === auth.user?.id;

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'direct': return 'text-primary bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20';
            case 'broadcast': return 'text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20';
            case 'system': return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
            default: return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this message?')) {
            window.location.href = route('messages.destroy', message.id);
        }
    };

    return (
        <DashboardLayout>
            <Head title={message.subject || 'Message'} />

            <div className="p-4">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('messages.index')}
                            className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Messages
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        {/* Message Header */}
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <EnvelopeIcon className="w-6 h-6 text-gray-400" />
                                    <div>
                                        <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                            {message.subject || 'No Subject'}
                                        </h1>
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getTypeColor(message.type)}`}>
                                            {message.type_label}
                                        </span>
                                    </div>
                                </div>
                                
                                {isCurrentUserSender && (
                                    <div className="flex space-x-2">
                                        <Link
                                            href={route('messages.edit', message.id)}
                                            className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                        >
                                            <PencilIcon className="w-4 h-4 mr-2" />
                                            Edit
                                        </Link>
                                        <button
                                            onClick={handleDelete}
                                            className="inline-flex items-center px-3 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                                        >
                                            <TrashIcon className="w-4 h-4 mr-2" />
                                            Delete
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Message Details */}
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="flex items-center space-x-3">
                                    <UserIcon className="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            From: {message.sender.first_name} {message.sender.last_name}
                                        </p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {message.sender.email}
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-center space-x-3">
                                    <UserIcon className="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            To: {message.receiver.first_name} {message.receiver.last_name}
                                        </p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {message.receiver.email}
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-center space-x-3">
                                    <ClockIcon className="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Sent
                                        </p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {new Date(message.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                                
                                {message.read_at && (
                                    <div className="flex items-center space-x-3">
                                        <ClockIcon className="w-5 h-5 text-green-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                Read
                                            </p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {new Date(message.read_at).toLocaleString()}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Message Content */}
                        <div className="px-6 py-6">
                            <div className="prose prose-sm dark:prose-invert max-w-none">
                                <div dangerouslySetInnerHTML={{ __html: message.content }} />
                            </div>
                        </div>

                        {/* Message Status */}
                        {isCurrentUserReceiver && !message.read_at && (
                            <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <EnvelopeIcon className="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-primary dark:text-blue-300">
                                            This message is now marked as read.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Reply & Forward Actions */}
                        {isCurrentUserReceiver && (
                            <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                                <Link
                                    href={route('messages.create', {
                                        reply_to: message.id,
                                        receiver_id: message.sender.id,
                                        subject: message.subject ? `Re: ${message.subject}` : 'Re: Your message'
                                    })}
                                    className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary"
                                >
                                    Reply
                                </Link>
                                <Link
                                    href={route('messages.create', {
                                        forward: message.id,
                                        subject: message.subject ? `Fwd: ${message.subject}` : 'Fwd: Message'
                                    })}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    <ArrowUturnRightIcon className="w-4 h-4 mr-2" />
                                    Forward
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}