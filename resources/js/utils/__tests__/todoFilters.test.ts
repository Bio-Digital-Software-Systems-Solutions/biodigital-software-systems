import { describe, it, expect } from 'vitest';
import { filterTodos, todoMatchesSearch } from '../todoFilters';
import type { DepartmentTodo } from '@/Types/scheduling';

// Helper to create mock todos
const createMockTodo = (overrides: Partial<DepartmentTodo> = {}): DepartmentTodo => ({
    uuid: 'todo-1',
    title: 'Test Todo',
    description: null,
    status: 'todo',
    status_label: 'A faire',
    status_color: 'gray',
    priority: 'medium',
    priority_label: 'Moyenne',
    priority_color: 'blue',
    due_date: null,
    estimated_minutes: null,
    is_overdue: false,
    is_due_today: false,
    assignee: null,
    creator: null,
    shift: null,
    completed_at: null,
    completed_by: null,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ...overrides,
});

describe('todoFilters', () => {
    describe('filterTodos', () => {
        describe('Status filtering', () => {
            it('filters out completed todos by default', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Active todo', status: 'todo' }),
                    createMockTodo({ uuid: '2', title: 'Completed todo', status: 'completed' }),
                    createMockTodo({ uuid: '3', title: 'In progress', status: 'in_progress' }),
                ];

                const result = filterTodos(todos);

                expect(result).toHaveLength(2);
                expect(result.map(t => t.title)).toEqual(['Active todo', 'In progress']);
            });

            it('filters out cancelled todos by default', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Active todo', status: 'todo' }),
                    createMockTodo({ uuid: '2', title: 'Cancelled todo', status: 'cancelled' }),
                ];

                const result = filterTodos(todos);

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Active todo');
            });

            it('includes all todos when showAll is true', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Active todo', status: 'todo' }),
                    createMockTodo({ uuid: '2', title: 'Completed todo', status: 'completed' }),
                    createMockTodo({ uuid: '3', title: 'Cancelled todo', status: 'cancelled' }),
                ];

                const result = filterTodos(todos, { showAll: true });

                expect(result).toHaveLength(3);
            });

            it('keeps blocked and in_progress todos by default', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', status: 'blocked' }),
                    createMockTodo({ uuid: '2', status: 'in_progress' }),
                    createMockTodo({ uuid: '3', status: 'todo' }),
                ];

                const result = filterTodos(todos);

                expect(result).toHaveLength(3);
            });
        });

        describe('Search filtering', () => {
            it('filters by title (case insensitive)', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Prepare meeting' }),
                    createMockTodo({ uuid: '2', title: 'Review documents' }),
                    createMockTodo({ uuid: '3', title: 'Meeting notes' }),
                ];

                const result = filterTodos(todos, { searchQuery: 'meeting' });

                expect(result).toHaveLength(2);
                expect(result.map(t => t.title)).toEqual(['Prepare meeting', 'Meeting notes']);
            });

            it('filters by description', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Task 1', description: 'Important project deliverable' }),
                    createMockTodo({ uuid: '2', title: 'Task 2', description: 'Regular task' }),
                    createMockTodo({ uuid: '3', title: 'Task 3', description: null }),
                ];

                const result = filterTodos(todos, { searchQuery: 'project' });

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Task 1');
            });

            it('filters by assignee name', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({
                        uuid: '1',
                        title: 'Task 1',
                        assignee: { uuid: 'u1', name: 'John Doe', avatar_url: null },
                    }),
                    createMockTodo({
                        uuid: '2',
                        title: 'Task 2',
                        assignee: { uuid: 'u2', name: 'Jane Smith', avatar_url: null },
                    }),
                    createMockTodo({ uuid: '3', title: 'Task 3', assignee: null }),
                ];

                const result = filterTodos(todos, { searchQuery: 'john' });

                expect(result).toHaveLength(1);
                expect(result[0].assignee?.name).toBe('John Doe');
            });

            it('handles empty search query', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Task 1' }),
                    createMockTodo({ uuid: '2', title: 'Task 2' }),
                ];

                const result = filterTodos(todos, { searchQuery: '' });

                expect(result).toHaveLength(2);
            });

            it('handles whitespace-only search query', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Task 1' }),
                    createMockTodo({ uuid: '2', title: 'Task 2' }),
                ];

                const result = filterTodos(todos, { searchQuery: '   ' });

                expect(result).toHaveLength(2);
            });

            it('trims search query before matching', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Meeting' }),
                    createMockTodo({ uuid: '2', title: 'Other task' }),
                ];

                const result = filterTodos(todos, { searchQuery: '  meeting  ' });

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Meeting');
            });

            it('searches across multiple fields', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Budget review', description: 'Q1 financials' }),
                    createMockTodo({
                        uuid: '2',
                        title: 'Hire developer',
                        assignee: { uuid: 'u1', name: 'Budget Team', avatar_url: null },
                    }),
                    createMockTodo({ uuid: '3', title: 'Regular task', description: 'Budget planning' }),
                ];

                const result = filterTodos(todos, { searchQuery: 'budget' });

                expect(result).toHaveLength(3);
            });
        });

        describe('Combined filtering', () => {
            it('applies both status and search filters', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Active meeting', status: 'todo' }),
                    createMockTodo({ uuid: '2', title: 'Completed meeting', status: 'completed' }),
                    createMockTodo({ uuid: '3', title: 'Active task', status: 'todo' }),
                ];

                const result = filterTodos(todos, { showAll: false, searchQuery: 'meeting' });

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Active meeting');
            });

            it('shows completed todos when showAll is true with search', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Active meeting', status: 'todo' }),
                    createMockTodo({ uuid: '2', title: 'Completed meeting', status: 'completed' }),
                ];

                const result = filterTodos(todos, { showAll: true, searchQuery: 'meeting' });

                expect(result).toHaveLength(2);
            });
        });

        describe('Edge cases', () => {
            it('handles empty todos array', () => {
                const result = filterTodos([]);

                expect(result).toEqual([]);
            });

            it('handles undefined options', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', status: 'todo' }),
                ];

                const result = filterTodos(todos);

                expect(result).toHaveLength(1);
            });

            it('handles special characters in search query', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Task (urgent)' }),
                    createMockTodo({ uuid: '2', title: 'Regular task' }),
                ];

                const result = filterTodos(todos, { searchQuery: '(urgent)' });

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Task (urgent)');
            });

            it('handles accented characters', () => {
                const todos: DepartmentTodo[] = [
                    createMockTodo({ uuid: '1', title: 'Reunion' }),
                    createMockTodo({ uuid: '2', title: 'Réunion' }),
                ];

                // Note: This test shows current behavior - accents are not normalized
                const result = filterTodos(todos, { searchQuery: 'reunion' });

                expect(result).toHaveLength(1);
                expect(result[0].title).toBe('Reunion');
            });
        });
    });

    describe('todoMatchesSearch', () => {
        it('returns true for empty search query', () => {
            const todo = createMockTodo({ title: 'Any task' });

            expect(todoMatchesSearch(todo, '')).toBe(true);
            expect(todoMatchesSearch(todo, '   ')).toBe(true);
        });

        it('matches title', () => {
            const todo = createMockTodo({ title: 'Important meeting' });

            expect(todoMatchesSearch(todo, 'important')).toBe(true);
            expect(todoMatchesSearch(todo, 'meeting')).toBe(true);
            expect(todoMatchesSearch(todo, 'IMPORTANT')).toBe(true);
            expect(todoMatchesSearch(todo, 'unrelated')).toBe(false);
        });

        it('matches description', () => {
            const todo = createMockTodo({
                title: 'Task',
                description: 'Review quarterly budget',
            });

            expect(todoMatchesSearch(todo, 'budget')).toBe(true);
            expect(todoMatchesSearch(todo, 'quarterly')).toBe(true);
        });

        it('matches assignee name', () => {
            const todo = createMockTodo({
                title: 'Task',
                assignee: { uuid: 'u1', name: 'Marie Dupont', avatar_url: null },
            });

            expect(todoMatchesSearch(todo, 'marie')).toBe(true);
            expect(todoMatchesSearch(todo, 'dupont')).toBe(true);
        });

        it('handles null description gracefully', () => {
            const todo = createMockTodo({ title: 'Task', description: null });

            expect(todoMatchesSearch(todo, 'task')).toBe(true);
            expect(todoMatchesSearch(todo, 'description')).toBe(false);
        });

        it('handles null assignee gracefully', () => {
            const todo = createMockTodo({ title: 'Unassigned task', assignee: null });

            expect(todoMatchesSearch(todo, 'unassigned')).toBe(true);
            expect(todoMatchesSearch(todo, 'john')).toBe(false);
        });
    });
});
