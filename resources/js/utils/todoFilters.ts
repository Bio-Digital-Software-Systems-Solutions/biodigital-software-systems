import type { DepartmentTodo } from '@/Types/scheduling';

export interface TodoFilterOptions {
    showAll?: boolean;
    searchQuery?: string;
}

/**
 * Filters todos based on status and search query
 * @param todos - Array of todos to filter
 * @param options - Filter options including showAll flag and searchQuery
 * @returns Filtered array of todos
 */
export function filterTodos(
    todos: DepartmentTodo[],
    options: TodoFilterOptions = {}
): DepartmentTodo[] {
    const { showAll = false, searchQuery = '' } = options;
    let filtered = todos;

    // Filter by status - show only active todos unless showAll is true
    if (!showAll) {
        filtered = filtered.filter(
            todo => todo.status !== 'completed' && todo.status !== 'cancelled'
        );
    }

    // Filter by search query
    if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase().trim();
        filtered = filtered.filter(todo =>
            todo.title.toLowerCase().includes(query) ||
            (todo.description && todo.description.toLowerCase().includes(query)) ||
            (todo.assignee?.name && todo.assignee.name.toLowerCase().includes(query))
        );
    }

    return filtered;
}

/**
 * Checks if a todo matches a search query
 * @param todo - Todo to check
 * @param searchQuery - Search query string
 * @returns Boolean indicating if the todo matches the query
 */
export function todoMatchesSearch(
    todo: DepartmentTodo,
    searchQuery: string
): boolean {
    if (!searchQuery.trim()) return true;

    const query = searchQuery.toLowerCase().trim();
    return (
        todo.title.toLowerCase().includes(query) ||
        (todo.description ? todo.description.toLowerCase().includes(query) : false) ||
        (todo.assignee?.name ? todo.assignee.name.toLowerCase().includes(query) : false)
    );
}
