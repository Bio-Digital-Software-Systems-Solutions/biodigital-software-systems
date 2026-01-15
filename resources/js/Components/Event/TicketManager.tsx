import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    TicketIcon,
    CurrencyEuroIcon,
    ClockIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/react/24/outline';
import { useEventTickets } from '@/Hooks/useEventTickets';
import { EventTicket, TicketType, TicketFormData } from '@/Types/event';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface TicketManagerProps {
    eventId: number | string;
    readonly?: boolean;
}

const ticketTypeLabels: Record<TicketType, string> = {
    free: 'Gratuit',
    paid: 'Payant',
    donation: 'Donation',
    early_bird: 'Early Bird',
    vip: 'VIP',
    group: 'Groupe',
    student: 'Étudiant',
    member: 'Membre',
};

const ticketTypeColors: Record<TicketType, string> = {
    free: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    paid: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    donation: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    early_bird: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    vip: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    group: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
    student: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
    member: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
};

export const TicketManager: React.FC<TicketManagerProps> = ({ eventId, readonly = false }) => {
    const { t } = useTranslation();
    const { tickets, stats, loading, createTicket, updateTicket, deleteTicket, refetch } = useEventTickets({ eventId });

    const [showForm, setShowForm] = useState(false);
    const [editingTicket, setEditingTicket] = useState<EventTicket | null>(null);
    const [deleteConfirm, setDeleteConfirm] = useState<EventTicket | null>(null);
    const [formData, setFormData] = useState<TicketFormData>({
        name: '',
        description: '',
        type: 'paid',
        price: 0,
        quantity_total: undefined,
        min_per_order: 1,
        max_per_order: undefined,
        is_visible: true,
        requires_approval: false,
    });

    const resetForm = () => {
        setFormData({
            name: '',
            description: '',
            type: 'paid',
            price: 0,
            quantity_total: undefined,
            min_per_order: 1,
            max_per_order: undefined,
            is_visible: true,
            requires_approval: false,
        });
        setEditingTicket(null);
        setShowForm(false);
    };

    const openEditForm = (ticket: EventTicket) => {
        setEditingTicket(ticket);
        setFormData({
            name: ticket.name,
            description: ticket.description || '',
            type: ticket.type,
            price: ticket.price,
            original_price: ticket.original_price || undefined,
            quantity_total: ticket.quantity_total || undefined,
            min_per_order: ticket.min_per_order,
            max_per_order: ticket.max_per_order || undefined,
            sales_start: ticket.sales_start,
            sales_end: ticket.sales_end,
            is_visible: ticket.is_visible,
            requires_approval: ticket.requires_approval,
        });
        setShowForm(true);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editingTicket) {
                await updateTicket(editingTicket.id, formData);
                toast.success('Billet mis à jour avec succès');
            } else {
                await createTicket(formData);
                toast.success('Billet créé avec succès');
            }
            resetForm();
        } catch (error) {
            toast.error('Une erreur est survenue');
        }
    };

    const handleDelete = async () => {
        if (!deleteConfirm) return;
        try {
            await deleteTicket(deleteConfirm.id);
            toast.success('Billet supprimé avec succès');
            setDeleteConfirm(null);
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Une erreur est survenue');
        }
    };

    const formatPrice = (price: number, currency = 'EUR') => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency,
        }).format(price);
    };

    if (loading) {
        return (
            <div className="animate-pulse space-y-4">
                {[...Array(3)].map((_, i) => (
                    <div key={i} className="h-24 bg-gray-200 dark:bg-gray-700 rounded-lg" />
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Gestion des billets
                    </h3>
                    {stats && (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {stats.total_sold} vendus sur {stats.total_capacity || '∞'} •
                            Revenus: {formatPrice(stats.revenue)}
                        </p>
                    )}
                </div>
                {!readonly && (
                    <button
                        onClick={() => setShowForm(true)}
                        className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <PlusIcon className="w-5 h-5 mr-2" />
                        Ajouter un billet
                    </button>
                )}
            </div>

            {/* Ticket List */}
            <div className="space-y-4">
                {tickets.length === 0 ? (
                    <div className="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <TicketIcon className="w-12 h-12 mx-auto text-gray-400" />
                        <p className="mt-4 text-gray-500 dark:text-gray-400">
                            Aucun billet créé pour cet événement
                        </p>
                        {!readonly && (
                            <button
                                onClick={() => setShowForm(true)}
                                className="mt-4 text-indigo-600 hover:text-indigo-500"
                            >
                                Créer le premier billet
                            </button>
                        )}
                    </div>
                ) : (
                    tickets.map((ticket) => (
                        <div
                            key={ticket.id}
                            className={`bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 ${
                                !ticket.is_visible ? 'opacity-60' : ''
                            }`}
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-3">
                                        <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                                            {ticket.name}
                                        </h4>
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${ticketTypeColors[ticket.type]}`}>
                                            {ticketTypeLabels[ticket.type]}
                                        </span>
                                        {!ticket.is_visible && (
                                            <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <EyeSlashIcon className="w-3 h-3 inline mr-1" />
                                                Masqué
                                            </span>
                                        )}
                                    </div>
                                    {ticket.description && (
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {ticket.description}
                                        </p>
                                    )}
                                    <div className="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span className="flex items-center">
                                            <CurrencyEuroIcon className="w-4 h-4 mr-1" />
                                            {ticket.type === 'free' ? 'Gratuit' : formatPrice(ticket.price)}
                                            {ticket.original_price && ticket.original_price > ticket.price && (
                                                <span className="ml-2 line-through text-gray-400">
                                                    {formatPrice(ticket.original_price)}
                                                </span>
                                            )}
                                        </span>
                                        <span className="flex items-center">
                                            <TicketIcon className="w-4 h-4 mr-1" />
                                            {ticket.quantity_sold} / {ticket.quantity_total || '∞'}
                                        </span>
                                        {ticket.sales_end && (
                                            <span className="flex items-center">
                                                <ClockIcon className="w-4 h-4 mr-1" />
                                                Jusqu'au {new Date(ticket.sales_end).toLocaleDateString('fr-FR')}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {!readonly && (
                                    <div className="flex items-center gap-2 ml-4">
                                        <button
                                            onClick={() => openEditForm(ticket)}
                                            className="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                                            title="Modifier"
                                        >
                                            <PencilIcon className="w-5 h-5" />
                                        </button>
                                        <button
                                            onClick={() => setDeleteConfirm(ticket)}
                                            className="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                            title="Supprimer"
                                        >
                                            <TrashIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                )}
                            </div>
                            {/* Progress bar for availability */}
                            {ticket.quantity_total && (
                                <div className="mt-4">
                                    <div className="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Disponibilité</span>
                                        <span>{Math.round(((ticket.quantity_total - ticket.quantity_sold) / ticket.quantity_total) * 100)}%</span>
                                    </div>
                                    <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-indigo-600 transition-all"
                                            style={{
                                                width: `${Math.min(100, (ticket.quantity_sold / ticket.quantity_total) * 100)}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>

            {/* Form Modal */}
            {showForm && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4">
                        <div className="fixed inset-0 bg-black/50" onClick={resetForm} />
                        <div className="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {editingTicket ? 'Modifier le billet' : 'Nouveau billet'}
                            </h3>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nom du billet *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        rows={2}
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Type *
                                        </label>
                                        <select
                                            required
                                            value={formData.type}
                                            onChange={(e) => setFormData({ ...formData, type: e.target.value as TicketType })}
                                            className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        >
                                            {Object.entries(ticketTypeLabels).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Prix (€) *
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            value={formData.price}
                                            onChange={(e) => setFormData({ ...formData, price: parseFloat(e.target.value) || 0 })}
                                            className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Quantité totale
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            value={formData.quantity_total || ''}
                                            onChange={(e) => setFormData({ ...formData, quantity_total: e.target.value ? parseInt(e.target.value) : undefined })}
                                            placeholder="Illimité"
                                            className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Max par commande
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            value={formData.max_per_order || ''}
                                            onChange={(e) => setFormData({ ...formData, max_per_order: e.target.value ? parseInt(e.target.value) : undefined })}
                                            placeholder="Illimité"
                                            className="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center gap-6">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_visible}
                                            onChange={(e) => setFormData({ ...formData, is_visible: e.target.checked })}
                                            className="rounded border-gray-300 dark:border-gray-600"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">Visible</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.requires_approval}
                                            onChange={(e) => setFormData({ ...formData, requires_approval: e.target.checked })}
                                            className="rounded border-gray-300 dark:border-gray-600"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">Approbation requise</span>
                                    </label>
                                </div>
                                <div className="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
                                    <button
                                        type="button"
                                        onClick={resetForm}
                                        className="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                                    >
                                        {editingTicket ? 'Mettre à jour' : 'Créer'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteConfirm}
                onOpenChange={(open) => !open && setDeleteConfirm(null)}
                onConfirm={handleDelete}
                title="Supprimer ce billet"
                description={`Êtes-vous sûr de vouloir supprimer le billet "${deleteConfirm?.name}" ? Cette action est irréversible.`}
            />
        </div>
    );
};

export default TicketManager;
