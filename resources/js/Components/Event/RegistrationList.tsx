import React, { useState, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    UserGroupIcon,
    ArrowPathIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { useEventRegistrations } from '@/Hooks/useEventRegistrations';
import { EventRegistration, RegistrationStatus, EventTicket } from '@/Types/event';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface RegistrationListProps {
    eventId: number | string;
    tickets?: EventTicket[];
}

const statusConfig: Record<RegistrationStatus, { label: string; color: string; icon: typeof CheckCircleIcon }> = {
    pending: { label: 'En attente', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', icon: ClockIcon },
    confirmed: { label: 'Confirmé', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', icon: CheckCircleIcon },
    waitlisted: { label: 'Liste d\'attente', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', icon: UserGroupIcon },
    cancelled: { label: 'Annulé', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', icon: XCircleIcon },
    checked_in: { label: 'Présent', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300', icon: CheckCircleIcon },
    no_show: { label: 'Absent', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', icon: XCircleIcon },
};

export const RegistrationList: React.FC<RegistrationListProps> = ({ eventId, tickets = [] }) => {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<RegistrationStatus | ''>('');
    const [ticketFilter, setTicketFilter] = useState<number | ''>('');
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [cancelDialog, setCancelDialog] = useState<EventRegistration | null>(null);
    const [showFilters, setShowFilters] = useState(false);

    const {
        registrations,
        stats,
        loading,
        pagination,
        fetchRegistrations,
        confirmRegistration,
        cancelRegistration,
        promoteFromWaitlist,
        bulkConfirm,
        bulkCancel,
        exportRegistrations,
    } = useEventRegistrations({
        eventId,
        filters: {
            status: statusFilter || undefined,
            ticket_id: ticketFilter || undefined,
            search: search || undefined,
        },
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        fetchRegistrations(1);
    };

    const handleConfirm = async (registration: EventRegistration) => {
        try {
            await confirmRegistration(registration.id);
            toast.success('Inscription confirmée');
        } catch (error) {
            toast.error('Erreur lors de la confirmation');
        }
    };

    const handleCancel = async () => {
        if (!cancelDialog) return;
        try {
            await cancelRegistration(cancelDialog.id);
            toast.success('Inscription annulée');
            setCancelDialog(null);
        } catch (error) {
            toast.error('Erreur lors de l\'annulation');
        }
    };

    const handlePromote = async (registration: EventRegistration) => {
        try {
            await promoteFromWaitlist(registration.id);
            toast.success('Inscription promue de la liste d\'attente');
        } catch (error: any) {
            toast.error(error.message || 'Erreur lors de la promotion');
        }
    };

    const handleBulkConfirm = async () => {
        try {
            const result = await bulkConfirm(selectedIds);
            toast.success(result.message);
            setSelectedIds([]);
        } catch (error) {
            toast.error('Erreur lors de la confirmation groupée');
        }
    };

    const handleBulkCancel = async () => {
        try {
            const result = await bulkCancel(selectedIds);
            toast.success(result.message);
            setSelectedIds([]);
        } catch (error) {
            toast.error('Erreur lors de l\'annulation groupée');
        }
    };

    const handleExport = async () => {
        try {
            const data = await exportRegistrations(statusFilter || undefined);
            // Download as JSON (could be CSV)
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `registrations-${eventId}-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
            toast.success('Export téléchargé');
        } catch (error) {
            toast.error('Erreur lors de l\'export');
        }
    };

    const toggleSelection = (id: number) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === registrations.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(registrations.map(r => r.id));
        }
    };

    const formatPrice = (price: number, currency = 'EUR') => {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(price);
    };

    return (
        <div className="space-y-6">
            {/* Stats Summary */}
            {stats && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <p className="text-sm text-gray-500 dark:text-gray-400">Total</p>
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.total}</p>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <p className="text-sm text-gray-500 dark:text-gray-400">Confirmés</p>
                        <p className="text-2xl font-bold text-green-600">{stats.by_status.confirmed + stats.by_status.checked_in}</p>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <p className="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                        <p className="text-2xl font-bold text-yellow-600">{stats.by_status.pending}</p>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <p className="text-sm text-gray-500 dark:text-gray-400">Liste d'attente</p>
                        <p className="text-2xl font-bold text-orange-600">{stats.by_status.waitlisted || 0}</p>
                    </div>
                </div>
            )}

            {/* Search & Filters */}
            <div className="flex flex-col md:flex-row gap-4">
                <form onSubmit={handleSearch} className="flex-1">
                    <div className="relative">
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher par nom, email, numéro..."
                            className="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </div>
                </form>
                <div className="flex gap-2">
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className={`inline-flex items-center px-4 py-2 rounded-lg border transition-colors ${
                            showFilters || statusFilter || ticketFilter
                                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-400'
                                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                        }`}
                    >
                        <FunnelIcon className="w-5 h-5 mr-2" />
                        Filtres
                    </button>
                    <button
                        onClick={handleExport}
                        className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <ArrowDownTrayIcon className="w-5 h-5 mr-2" />
                        Exporter
                    </button>
                    <button
                        onClick={() => fetchRegistrations()}
                        className="p-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        title="Rafraîchir"
                    >
                        <ArrowPathIcon className="w-5 h-5" />
                    </button>
                </div>
            </div>

            {/* Filter Panel */}
            {showFilters && (
                <div className="flex flex-wrap gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <select
                        value={statusFilter}
                        onChange={(e) => {
                            setStatusFilter(e.target.value as RegistrationStatus | '');
                            fetchRegistrations(1);
                        }}
                        className="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                    >
                        <option value="">Tous les statuts</option>
                        {Object.entries(statusConfig).map(([value, { label }]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                    {tickets.length > 0 && (
                        <select
                            value={ticketFilter}
                            onChange={(e) => {
                                setTicketFilter(e.target.value ? parseInt(e.target.value) : '');
                                fetchRegistrations(1);
                            }}
                            className="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">Tous les billets</option>
                            {tickets.map((ticket) => (
                                <option key={ticket.id} value={ticket.id}>{ticket.name}</option>
                            ))}
                        </select>
                    )}
                </div>
            )}

            {/* Bulk Actions */}
            {selectedIds.length > 0 && (
                <div className="flex items-center gap-4 p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                    <span className="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                        {selectedIds.length} sélectionné(s)
                    </span>
                    <button
                        onClick={handleBulkConfirm}
                        className="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Confirmer
                    </button>
                    <button
                        onClick={handleBulkCancel}
                        className="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        onClick={() => setSelectedIds([])}
                        className="px-3 py-1 text-indigo-700 dark:text-indigo-300 text-sm hover:underline"
                    >
                        Désélectionner
                    </button>
                </div>
            )}

            {/* Table */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                {loading ? (
                    <div className="p-8 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto" />
                    </div>
                ) : registrations.length === 0 ? (
                    <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                        Aucune inscription trouvée
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th className="w-12 px-4 py-3">
                                        <input
                                            type="checkbox"
                                            checked={selectedIds.length === registrations.length && registrations.length > 0}
                                            onChange={toggleSelectAll}
                                            className="rounded border-gray-300 dark:border-gray-600"
                                        />
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Participant
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Billet
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Statut
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Montant
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Date
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {registrations.map((registration) => {
                                    const StatusIcon = statusConfig[registration.status]?.icon || ClockIcon;
                                    return (
                                        <tr key={registration.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(registration.id)}
                                                    onChange={() => toggleSelection(registration.id)}
                                                    className="rounded border-gray-300 dark:border-gray-600"
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="font-medium text-gray-900 dark:text-white">
                                                        {registration.first_name} {registration.last_name}
                                                    </p>
                                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                                        {registration.email}
                                                    </p>
                                                    {registration.company && (
                                                        <p className="text-xs text-gray-400">
                                                            {registration.company}
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="text-sm text-gray-900 dark:text-white">
                                                    {registration.ticket?.name || '-'}
                                                </span>
                                                {registration.quantity > 1 && (
                                                    <span className="ml-1 text-gray-500">×{registration.quantity}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${statusConfig[registration.status]?.color}`}>
                                                    <StatusIcon className="w-3 h-3 mr-1" />
                                                    {statusConfig[registration.status]?.label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {registration.total_amount > 0
                                                    ? formatPrice(registration.total_amount, registration.currency)
                                                    : 'Gratuit'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                {registration.registered_at
                                                    ? new Date(registration.registered_at).toLocaleDateString('fr-FR')
                                                    : '-'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    {registration.status === 'pending' && (
                                                        <button
                                                            onClick={() => handleConfirm(registration)}
                                                            className="text-green-600 hover:text-green-700 text-sm"
                                                        >
                                                            Confirmer
                                                        </button>
                                                    )}
                                                    {registration.status === 'waitlisted' && (
                                                        <button
                                                            onClick={() => handlePromote(registration)}
                                                            className="text-blue-600 hover:text-blue-700 text-sm"
                                                        >
                                                            Promouvoir
                                                        </button>
                                                    )}
                                                    {!['cancelled', 'no_show'].includes(registration.status) && (
                                                        <button
                                                            onClick={() => setCancelDialog(registration)}
                                                            className="text-red-600 hover:text-red-700 text-sm"
                                                        >
                                                            Annuler
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                {pagination.lastPage > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t dark:border-gray-700">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Page {pagination.currentPage} sur {pagination.lastPage} ({pagination.total} résultats)
                        </p>
                        <div className="flex gap-2">
                            <button
                                onClick={() => fetchRegistrations(pagination.currentPage - 1)}
                                disabled={pagination.currentPage === 1}
                                className="p-2 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <ChevronLeftIcon className="w-5 h-5" />
                            </button>
                            <button
                                onClick={() => fetchRegistrations(pagination.currentPage + 1)}
                                disabled={pagination.currentPage === pagination.lastPage}
                                className="p-2 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <ChevronRightIcon className="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {/* Cancel Dialog */}
            <DeleteConfirmationDialog
                open={!!cancelDialog}
                onOpenChange={(open) => !open && setCancelDialog(null)}
                onConfirm={handleCancel}
                title="Annuler cette inscription"
                description={`Êtes-vous sûr de vouloir annuler l'inscription de ${cancelDialog?.first_name} ${cancelDialog?.last_name} ?`}
                confirmText="Annuler l'inscription"
            />
        </div>
    );
};

export default RegistrationList;
