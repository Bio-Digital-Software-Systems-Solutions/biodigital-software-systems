import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import RecipientSelector from '@/Components/RecipientSelector';
import MultiRecipientSelector, { Recipient } from '@/Components/MultiRecipientSelector';
import { LazyRichTextEditor, withLazyLoad } from '@/Components/LazyComponents';

// Use lazy-loaded RichTextEditor with HOC
const RichTextEditor = withLazyLoad(LazyRichTextEditor, 'Chargement de l\'éditeur...');

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface OriginalMessage {
    id: number;
    subject?: string;
    content: string;
    sender: User;
    receiver: User;
    created_at: string;
}

interface Props extends PageProps {
    users?: any[]; // Keep for backward compatibility but not used anymore
    reply_to?: number;
    receiver_id?: number;
    subject?: string;
    forward?: number;
    original_message?: OriginalMessage;
}

export default function Create({ reply_to, receiver_id, subject, forward, original_message }: Props) {
    const [selectedRecipient, setSelectedRecipient] = useState<Recipient | null>(null);
    const [ccRecipients, setCcRecipients] = useState<Recipient[]>([]);
    const [bccRecipients, setBccRecipients] = useState<Recipient[]>([]);
    const [showCc, setShowCc] = useState(false);
    const [showBcc, setShowBcc] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        subject: subject || '',
        content: '',
        recipient_type: '',
        recipient_id: '',
        cc_recipients: [] as number[],
        bcc_recipients: [] as number[],
        type: 'direct',
    });

    // Handle reply or forward when component mounts
    useEffect(() => {
        if (reply_to && original_message) {
            // Set recipient for reply
            if (receiver_id) {
                setData('recipient_type', 'user');
                setData('recipient_id', String(receiver_id));
            }

            // Prepend original message as quote
            const quotedContent = `
                <br><br>
                <hr>
                <p><strong>On ${new Date(original_message.created_at).toLocaleString()}, ${original_message.sender.first_name} ${original_message.sender.last_name} wrote:</strong></p>
                <blockquote>${original_message.content}</blockquote>
            `;
            setData('content', quotedContent);
        } else if (forward && original_message) {
            // Prepend original message for forwarding
            const forwardedContent = `
                <br><br>
                <hr>
                <p><strong>---------- Forwarded message ----------</strong></p>
                <p><strong>From:</strong> ${original_message.sender.first_name} ${original_message.sender.last_name} &lt;${original_message.sender.email}&gt;</p>
                <p><strong>Date:</strong> ${new Date(original_message.created_at).toLocaleString()}</p>
                <p><strong>Subject:</strong> ${original_message.subject || 'No Subject'}</p>
                <p><strong>To:</strong> ${original_message.receiver.first_name} ${original_message.receiver.last_name} &lt;${original_message.receiver.email}&gt;</p>
                <br>
                ${original_message.content}
            `;
            setData('content', forwardedContent);
        }
    }, [reply_to, forward, original_message, receiver_id]);

    const handleRecipientChange = (recipient: Recipient | null) => {
        setSelectedRecipient(recipient);
        if (recipient) {
            setData('recipient_type', recipient.type);
            setData('recipient_id', String(recipient.id));
            // Auto-set type based on recipient
            if (recipient.type === 'department') {
                setData('type', 'broadcast');
            } else {
                setData('type', 'direct');
            }
        } else {
            setData('recipient_type', '');
            setData('recipient_id', '');
        }
    };

    const handleCcChange = (recipients: Recipient[]) => {
        setCcRecipients(recipients);
        // Only include user IDs (not departments for CC)
        const ccIds = recipients.filter(r => r.type === 'user').map(r => r.id);
        setData('cc_recipients', ccIds);
    };

    const handleBccChange = (recipients: Recipient[]) => {
        setBccRecipients(recipients);
        // Only include user IDs (not departments for BCC)
        const bccIds = recipients.filter(r => r.type === 'user').map(r => r.id);
        setData('bcc_recipients', bccIds);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('messages.store'));
    };

    return (
        <DashboardLayout>
            <Head title="Compose Message" />

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
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                Compose New Message
                            </h1>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Recipient Selection */}
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        To *
                                    </label>
                                    <div className="flex gap-2">
                                        {!showCc && (
                                            <button
                                                type="button"
                                                onClick={() => setShowCc(true)}
                                                className="text-xs text-primary dark:text-blue-400 hover:underline"
                                            >
                                                + CC
                                            </button>
                                        )}
                                        {!showBcc && (
                                            <button
                                                type="button"
                                                onClick={() => setShowBcc(true)}
                                                className="text-xs text-primary dark:text-blue-400 hover:underline"
                                            >
                                                + BCC
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <RecipientSelector
                                    value={selectedRecipient}
                                    onChange={handleRecipientChange}
                                    error={errors.recipient_id}
                                />
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Search for individual users or select a department to send to all members
                                </p>
                            </div>

                            {/* CC Recipients */}
                            {showCc && (
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            CC
                                        </label>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setShowCc(false);
                                                setCcRecipients([]);
                                                setData('cc_recipients', []);
                                            }}
                                            className="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <MultiRecipientSelector
                                        value={ccRecipients}
                                        onChange={handleCcChange}
                                        error={errors.cc_recipients}
                                        placeholder="Search users for CC..."
                                        label=""
                                    />
                                    <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        CC recipients will receive a copy of the message
                                    </p>
                                </div>
                            )}

                            {/* BCC Recipients */}
                            {showBcc && (
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            BCC
                                        </label>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setShowBcc(false);
                                                setBccRecipients([]);
                                                setData('bcc_recipients', []);
                                            }}
                                            className="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <MultiRecipientSelector
                                        value={bccRecipients}
                                        onChange={handleBccChange}
                                        error={errors.bcc_recipients}
                                        placeholder="Search users for BCC..."
                                        label=""
                                    />
                                    <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        BCC recipients will receive a copy without others knowing
                                    </p>
                                </div>
                            )}

                            {/* Message Type - Auto-set based on recipient */}
                            {selectedRecipient && (
                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                                    <p className="text-sm text-primary dark:text-blue-300">
                                        <strong>Type:</strong> {data.type === 'direct' ? 'Direct Message' : 'Broadcast Message'}
                                        {selectedRecipient.type === 'department' && (
                                            <span className="ml-2">
                                                (Will be sent to {selectedRecipient.users_count} members)
                                            </span>
                                        )}
                                    </p>
                                </div>
                            )}

                            {/* Subject */}
                            <div>
                                <label htmlFor="subject" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Subject
                                </label>
                                <input
                                    type="text"
                                    id="subject"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Enter message subject (optional)"
                                />
                                {errors.subject && <p className="mt-1 text-sm text-red-600">{errors.subject}</p>}
                            </div>

                            {/* Message Content */}
                            <div>
                                <label htmlFor="content" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Message *
                                </label>
                                <RichTextEditor
                                    content={data.content}
                                    onChange={(html) => setData('content', html)}
                                    placeholder="Type your message..."
                                    error={errors.content}
                                />
                            </div>


                            {/* Actions */}
                            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('messages.index')}
                                    className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-primary hover:bg-primary disabled:bg-blue-300 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    {processing ? 'Sending...' : 'Send Message'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}