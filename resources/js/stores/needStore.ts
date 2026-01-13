import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import type { DepartmentNeed, NeedStatus, NeedCategory, NeedPriority } from '@/Types/need';

interface NeedFilters {
    status?: NeedStatus[];
    category?: NeedCategory;
    priority?: NeedPriority;
    search?: string;
    assignedTo?: number;
    createdBy?: number;
    dateFrom?: string;
    dateTo?: string;
}

export type ViewMode = 'kanban' | 'list' | 'table' | 'grid';

interface NeedState {
    needs: DepartmentNeed[];
    selectedNeedId: string | null;
    filters: NeedFilters;
    viewMode: ViewMode;
    isLoading: boolean;
    error: string | null;
}

interface NeedActions {
    setNeeds: (needs: DepartmentNeed[]) => void;
    addNeed: (need: DepartmentNeed) => void;
    updateNeed: (needId: string, data: Partial<DepartmentNeed>) => void;
    removeNeed: (needId: string) => void;
    moveNeed: (needId: string, newStatus: NeedStatus) => void;
    selectNeed: (needId: string | null) => void;
    setFilters: (filters: Partial<NeedFilters>) => void;
    clearFilters: () => void;
    setViewMode: (mode: ViewMode) => void;
    setIsLoading: (isLoading: boolean) => void;
    setError: (error: string | null) => void;
    reset: () => void;
}

const initialState: NeedState = {
    needs: [],
    selectedNeedId: null,
    filters: {},
    viewMode: 'kanban',
    isLoading: false,
    error: null,
};

const kanbanColumns: Record<string, NeedStatus[]> = {
    pending: ['draft', 'submitted'],
    review: ['under_review'],
    approved: ['approved', 'in_progress', 'ordered', 'delivered'],
    rejected: ['rejected'],
    completed: ['completed', 'cancelled'],
};

export const useNeedStore = create<NeedState & NeedActions>()(
    immer((set, get) => ({
        ...initialState,

        setNeeds: (needs) =>
            set((state) => {
                state.needs = needs;
            }),

        addNeed: (need) =>
            set((state) => {
                state.needs.push(need);
            }),

        updateNeed: (needId, data) =>
            set((state) => {
                const index = state.needs.findIndex((n) => n.uuid === needId);
                if (index !== -1) {
                    state.needs[index] = { ...state.needs[index], ...data };
                }
            }),

        removeNeed: (needId) =>
            set((state) => {
                state.needs = state.needs.filter((n) => n.uuid !== needId);
                if (state.selectedNeedId === needId) {
                    state.selectedNeedId = null;
                }
            }),

        moveNeed: (needId, newStatus) =>
            set((state) => {
                const index = state.needs.findIndex((n) => n.uuid === needId);
                if (index !== -1) {
                    state.needs[index].status = newStatus;
                }
            }),

        selectNeed: (needId) =>
            set((state) => {
                state.selectedNeedId = needId;
            }),

        setFilters: (filters) =>
            set((state) => {
                state.filters = { ...state.filters, ...filters };
            }),

        clearFilters: () =>
            set((state) => {
                state.filters = {};
            }),

        setViewMode: (mode) =>
            set((state) => {
                state.viewMode = mode;
            }),

        setIsLoading: (isLoading) =>
            set((state) => {
                state.isLoading = isLoading;
            }),

        setError: (error) =>
            set((state) => {
                state.error = error;
            }),

        reset: () => set(initialState),
    }))
);

// Selectors
export const selectNeedsByStatus = (needs: DepartmentNeed[], status: NeedStatus[]) =>
    needs.filter((need) => status.includes(need.status));

export const selectNeedsByKanbanColumn = (needs: DepartmentNeed[], column: keyof typeof kanbanColumns) =>
    selectNeedsByStatus(needs, kanbanColumns[column]);

export const selectFilteredNeeds = (needs: DepartmentNeed[], filters: NeedFilters) => {
    return needs.filter((need) => {
        if (filters.status?.length && !filters.status.includes(need.status)) {
            return false;
        }
        if (filters.category && need.category !== filters.category) {
            return false;
        }
        if (filters.priority && need.priority !== filters.priority) {
            return false;
        }
        if (filters.search) {
            const search = filters.search.toLowerCase();
            if (
                !need.title.toLowerCase().includes(search) &&
                !need.description?.toLowerCase().includes(search) &&
                !need.reference?.toLowerCase().includes(search)
            ) {
                return false;
            }
        }
        if (filters.assignedTo && need.assigned_to_id !== filters.assignedTo) {
            return false;
        }
        if (filters.createdBy && need.created_by_id !== filters.createdBy) {
            return false;
        }
        return true;
    });
};

export { kanbanColumns };
