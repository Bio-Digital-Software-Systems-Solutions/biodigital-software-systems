import React, { useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PlusIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { useNeedStore, selectFilteredNeeds } from '@/stores/needStore';
import KanbanBoard from '@/Components/NeedManagement/KanbanBoard';
import NeedFilters from '@/Components/NeedManagement/NeedFilters';
import NeedDetailPanel from '@/Components/NeedManagement/NeedDetailPanel';
import NeedListView from '@/Components/NeedManagement/NeedListView';
import NeedTableView from '@/Components/NeedManagement/NeedTableView';
import NeedGridView from '@/Components/NeedManagement/NeedGridView';
import type { DepartmentNeed, NeedStatus } from '@/Types/need';
import type { PaginatedData } from '@/Types';

interface Props {
    needs: PaginatedData<DepartmentNeed>;
}

export default function NeedsIndex({ needs: paginatedNeeds }: Props) {
    const initialNeeds = paginatedNeeds?.data || [];
    const {
        needs,
        setNeeds,
        selectedNeedId,
        selectNeed,
        viewMode,
        filters,
        updateNeed,
        reset,
    } = useNeedStore();

    const [detailPanelOpen, setDetailPanelOpen] = React.useState(false);

    // Initialize store
    useEffect(() => {
        setNeeds(initialNeeds);
        return () => reset();
    }, [initialNeeds]);

    const selectedNeed = needs.find((n) => n.uuid === selectedNeedId);
    const filteredNeeds = selectFilteredNeeds(needs, filters);

    const handleNeedClick = (need: DepartmentNeed) => {
        selectNeed(need.uuid);
        setDetailPanelOpen(true);
    };

    const handleCloseDetail = () => {
        setDetailPanelOpen(false);
        selectNeed(null);
    };

    const handleStatusChange = (needId: string, newStatus: NeedStatus) => {
        router.patch(
            route('needs.update-status', needId),
            { status: newStatus },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Statut mis à jour');
                    updateNeed(needId, { status: newStatus });
                },
                onError: () => {
                    toast.error('Erreur lors de la mise à jour');
                },
            }
        );
    };

    const handleAddComment = (content: string) => {
        if (!selectedNeedId) return;

        router.post(
            route('needs.comments.add', selectedNeedId),
            { content },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Commentaire ajouté');
                },
                onError: () => {
                    toast.error('Erreur lors de l\'ajout du commentaire');
                },
            }
        );
    };

    return (
        <DashboardLayout>
            <Head title="Besoins" />

            <div className="h-[calc(100vh-64px)] flex flex-col">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div className="min-w-0">
                        <h1 className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                            Gestion des besoins
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {filteredNeeds.length} besoin{filteredNeeds.length > 1 ? 's' : ''}
                        </p>
                    </div>
                    <Link
                        href={route('needs.create')}
                        className="
                            inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-md
                            bg-primary text-white font-medium text-sm
                            hover:bg-primary/90 transition-colors
                        "
                    >
                        <PlusIcon className="h-4 w-4 sm:h-5 sm:w-5" />
                        <span className="hidden sm:inline">Nouveau besoin</span>
                    </Link>
                </div>

                {/* Filters */}
                <NeedFilters />

                {/* Main Content */}
                <div className="flex-1 flex overflow-hidden">
                    {/* Board/List/Table/Grid */}
                    <main className="flex-1 overflow-hidden">
                        {viewMode === 'kanban' && (
                            <KanbanBoard
                                onNeedClick={handleNeedClick}
                                onStatusChange={handleStatusChange}
                            />
                        )}
                        {viewMode === 'list' && (
                            <NeedListView
                                needs={filteredNeeds}
                                onNeedClick={handleNeedClick}
                            />
                        )}
                        {viewMode === 'table' && (
                            <NeedTableView
                                needs={filteredNeeds}
                                onNeedClick={handleNeedClick}
                            />
                        )}
                        {viewMode === 'grid' && (
                            <NeedGridView
                                needs={filteredNeeds}
                                onNeedClick={handleNeedClick}
                            />
                        )}
                    </main>

                    {/* Detail Panel */}
                    {detailPanelOpen && selectedNeed && (
                        <aside className="fixed inset-0 z-50 bg-white dark:bg-gray-800 sm:relative sm:inset-auto sm:z-auto sm:w-80 lg:w-96 border-l border-gray-200 dark:border-gray-700 overflow-hidden">
                            <NeedDetailPanel
                                need={selectedNeed}
                                onClose={handleCloseDetail}
                                onStatusChange={(status) => handleStatusChange(selectedNeed.uuid, status)}
                                onAddComment={handleAddComment}
                            />
                        </aside>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
