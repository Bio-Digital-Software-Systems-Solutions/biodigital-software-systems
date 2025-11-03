import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TopicsManager, { Topic } from '../TopicsManager';

describe('TopicsManager Component', () => {
    const mockOnChange = vi.fn();

    const mockTopics: Topic[] = [
        {
            id: 1,
            name: 'Introduction to HTML',
            description: 'Learn the basics of HTML',
            order: 0,
        },
        {
            id: 2,
            name: 'CSS Fundamentals',
            description: 'Master CSS styling',
            order: 1,
        },
    ];

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('renders with label and description', () => {
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            expect(screen.getByText('Thèmes abordés *')).toBeInTheDocument();
            expect(
                screen.getByText('Ajoutez les thèmes principaux qui seront abordés dans cette formation')
            ).toBeInTheDocument();
        });

        it('renders input field and add button', () => {
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            expect(screen.getByPlaceholderText('Ex: Principes du design')).toBeInTheDocument();
            expect(screen.getByText('Ajouter')).toBeInTheDocument();
        });

        it('shows empty state when no topics', () => {
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            expect(
                screen.getByText('Aucun thème ajouté. Ajoutez au moins un thème pour cette formation.')
            ).toBeInTheDocument();
        });

        it('displays error message when error prop is provided', () => {
            render(<TopicsManager topics={[]} onChange={mockOnChange} error="Ce champ est requis" />);

            expect(screen.getByText('Ce champ est requis')).toBeInTheDocument();
        });

        it('displays topics count', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            expect(screen.getByText('2 thèmes ajoutés')).toBeInTheDocument();
        });

        it('uses singular form when one topic', () => {
            render(<TopicsManager topics={[mockTopics[0]]} onChange={mockOnChange} />);

            expect(screen.getByText('1 thème ajouté')).toBeInTheDocument();
        });
    });

    describe('Displaying Topics', () => {
        it('renders list of topics with names', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            expect(screen.getByDisplayValue('Introduction to HTML')).toBeInTheDocument();
            expect(screen.getByDisplayValue('CSS Fundamentals')).toBeInTheDocument();
        });

        it('renders topics with descriptions', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            expect(screen.getByDisplayValue('Learn the basics of HTML')).toBeInTheDocument();
            expect(screen.getByDisplayValue('Master CSS styling')).toBeInTheDocument();
        });

        it('does not show topics marked for deletion', () => {
            const topicsWithDeleted: Topic[] = [
                ...mockTopics,
                {
                    id: 3,
                    name: 'Deleted Topic',
                    description: 'Should not be visible',
                    order: 2,
                    _destroy: true,
                },
            ];

            render(<TopicsManager topics={topicsWithDeleted} onChange={mockOnChange} />);

            expect(screen.queryByDisplayValue('Deleted Topic')).not.toBeInTheDocument();
            expect(screen.getByText('2 thèmes ajoutés')).toBeInTheDocument(); // Only counts visible topics
        });
    });

    describe('Adding Topics', () => {
        it('disables add button when input is empty', () => {
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const addButton = screen.getByText('Ajouter');
            expect(addButton).toBeDisabled();
        });

        it('enables add button when input has text', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');
            await user.type(input, 'New Topic');

            const addButton = screen.getByText('Ajouter');
            expect(addButton).not.toBeDisabled();
        });

        it('calls onChange with new topic when add button is clicked', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');
            await user.type(input, 'New Topic');

            const addButton = screen.getByText('Ajouter');
            await user.click(addButton);

            expect(mockOnChange).toHaveBeenCalledWith([
                {
                    name: 'New Topic',
                    description: '',
                    order: 0,
                },
            ]);
        });

        it('clears input after adding topic', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');
            await user.type(input, 'New Topic');

            const addButton = screen.getByText('Ajouter');
            await user.click(addButton);

            expect(input).toHaveValue('');
        });

        it('adds topic when pressing Enter in input field', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');
            await user.type(input, 'New Topic{Enter}');

            expect(mockOnChange).toHaveBeenCalledWith([
                {
                    name: 'New Topic',
                    description: '',
                    order: 0,
                },
            ]);
        });

        it('does not add topic with only whitespace', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');
            await user.type(input, '   {Enter}');

            expect(mockOnChange).not.toHaveBeenCalled();
        });

        it('assigns correct order when adding multiple topics', async () => {
            const user = userEvent.setup();
            const { rerender } = render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            const input = screen.getByPlaceholderText('Ex: Principes du design');

            // Add first topic
            await user.type(input, 'First Topic');
            await user.click(screen.getByText('Ajouter'));

            expect(mockOnChange).toHaveBeenLastCalledWith([
                {
                    name: 'First Topic',
                    description: '',
                    order: 0,
                },
            ]);

            // Simulate props update
            const newTopics: Topic[] = [{ name: 'First Topic', description: '', order: 0 }];
            rerender(<TopicsManager topics={newTopics} onChange={mockOnChange} />);

            // Add second topic
            await user.type(screen.getByPlaceholderText('Ex: Principes du design'), 'Second Topic');
            await user.click(screen.getByText('Ajouter'));

            expect(mockOnChange).toHaveBeenLastCalledWith([
                { name: 'First Topic', description: '', order: 0 },
                { name: 'Second Topic', description: '', order: 1 },
            ]);
        });
    });

    describe('Editing Topics', () => {
        it('allows editing topic name', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const nameInput = screen.getByDisplayValue('Introduction to HTML');
            await user.clear(nameInput);
            await user.type(nameInput, 'Updated HTML');

            expect(mockOnChange).toHaveBeenCalled();
            const lastCall = mockOnChange.mock.calls[mockOnChange.mock.calls.length - 1][0];
            expect(lastCall[0].name).toBe('Updated HTML');
        });

        it('allows editing topic description', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const descInput = screen.getByDisplayValue('Learn the basics of HTML');
            await user.clear(descInput);
            await user.type(descInput, 'Updated description');

            expect(mockOnChange).toHaveBeenCalled();
            const lastCall = mockOnChange.mock.calls[mockOnChange.mock.calls.length - 1][0];
            expect(lastCall[0].description).toBe('Updated description');
        });
    });

    describe('Removing Topics', () => {
        it('marks existing topic for deletion when remove button is clicked', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const deleteButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg') && btn.className.includes('text-red-600')
            );

            await user.click(deleteButtons[0]);

            expect(mockOnChange).toHaveBeenCalled();
            const updatedTopics = mockOnChange.mock.calls[0][0];

            // Should mark first topic for deletion
            expect(updatedTopics.find((t: Topic) => t.id === 1)?._destroy).toBe(true);
            // Should reorder remaining visible topics
            expect(updatedTopics.filter((t: Topic) => !t._destroy)).toHaveLength(1);
        });

        it('removes new topic immediately when remove button is clicked', async () => {
            const user = userEvent.setup();
            const topicsWithNew: Topic[] = [
                ...mockTopics,
                {
                    name: 'New Topic',
                    description: 'No ID',
                    order: 2,
                },
            ];

            render(<TopicsManager topics={topicsWithNew} onChange={mockOnChange} />);

            const deleteButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg') && btn.className.includes('text-red-600')
            );

            // Click delete on the last topic (new one without ID)
            await user.click(deleteButtons[2]);

            expect(mockOnChange).toHaveBeenCalled();
            const updatedTopics = mockOnChange.mock.calls[0][0];

            // New topic should be completely removed
            expect(updatedTopics.filter((t: Topic) => !t._destroy)).toHaveLength(2);
            expect(updatedTopics.find((t: Topic) => t.name === 'New Topic')).toBeUndefined();
        });
    });

    describe('Reordering Topics', () => {
        it('disables up button for first topic', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const upButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg[class*="ChevronUp"]')
            );

            expect(upButtons[0]).toBeDisabled();
        });

        it('disables down button for last topic', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const downButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg[class*="ChevronDown"]')
            );

            expect(downButtons[downButtons.length - 1]).toBeDisabled();
        });

        it('moves topic up when up button is clicked', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const upButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg[class*="ChevronUp"]') && !btn.disabled
            );

            // Click up on second topic
            await user.click(upButtons[0]);

            expect(mockOnChange).toHaveBeenCalled();
            const updatedTopics = mockOnChange.mock.calls[0][0];

            // Order should be swapped
            expect(updatedTopics[0].name).toBe('CSS Fundamentals');
            expect(updatedTopics[0].order).toBe(0);
            expect(updatedTopics[1].name).toBe('Introduction to HTML');
            expect(updatedTopics[1].order).toBe(1);
        });

        it('moves topic down when down button is clicked', async () => {
            const user = userEvent.setup();
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const downButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg[class*="ChevronDown"]') && !btn.disabled
            );

            // Click down on first topic
            await user.click(downButtons[0]);

            expect(mockOnChange).toHaveBeenCalled();
            const updatedTopics = mockOnChange.mock.calls[0][0];

            // Order should be swapped
            expect(updatedTopics[0].name).toBe('CSS Fundamentals');
            expect(updatedTopics[0].order).toBe(0);
            expect(updatedTopics[1].name).toBe('Introduction to HTML');
            expect(updatedTopics[1].order).toBe(1);
        });

        it('updates order property when reordering', async () => {
            const user = userEvent.setup();
            const threeTopics: Topic[] = [
                { id: 1, name: 'Topic 1', description: '', order: 0 },
                { id: 2, name: 'Topic 2', description: '', order: 1 },
                { id: 3, name: 'Topic 3', description: '', order: 2 },
            ];

            render(<TopicsManager topics={threeTopics} onChange={mockOnChange} />);

            const downButtons = screen.getAllByRole('button', { name: '' }).filter(
                (btn) => btn.querySelector('svg[class*="ChevronDown"]') && !btn.disabled
            );

            // Move first topic down
            await user.click(downButtons[0]);

            const updatedTopics = mockOnChange.mock.calls[0][0];
            // Check all orders are sequential
            expect(updatedTopics[0].order).toBe(0);
            expect(updatedTopics[1].order).toBe(1);
            expect(updatedTopics[2].order).toBe(2);
        });
    });

    describe('Visual Elements', () => {
        it('renders grip icon for each topic', () => {
            const { container } = render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const gripIcons = container.querySelectorAll('svg[class*="lucide-grip-vertical"]');
            expect(gripIcons).toHaveLength(2);
        });

        it('renders colored border on topic cards', () => {
            const { container } = render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const cards = container.querySelectorAll('.border-l-4.border-l-primary');
            expect(cards).toHaveLength(2);
        });

        it('applies proper styling to action buttons', () => {
            const { container } = render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            // Delete buttons should have red styling
            const deleteButtons = container.querySelectorAll('button.text-red-600');
            expect(deleteButtons).toHaveLength(2);
        });
    });

    describe('Accessibility', () => {
        it('has proper labels for topic inputs', () => {
            render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            expect(screen.getAllByText('Nom du thème *')).toHaveLength(2);
            expect(screen.getAllByText('Description (optionnelle)')).toHaveLength(2);
        });

        it('has unique IDs for topic inputs', () => {
            const { container } = render(<TopicsManager topics={mockTopics} onChange={mockOnChange} />);

            const nameInputs = container.querySelectorAll('input[id^="topic-name-"]');
            const descInputs = container.querySelectorAll('textarea[id^="topic-description-"]');

            expect(nameInputs).toHaveLength(2);
            expect(descInputs).toHaveLength(2);

            // Check IDs are unique
            const nameIds = Array.from(nameInputs).map(input => input.id);
            const descIds = Array.from(descInputs).map(input => input.id);

            expect(new Set(nameIds).size).toBe(2);
            expect(new Set(descIds).size).toBe(2);
        });
    });

    describe('Dark Mode Support', () => {
        it('renders with dark mode classes', () => {
            const { container } = render(<TopicsManager topics={[]} onChange={mockOnChange} />);

            // Check for dark mode classes
            const darkModeElements = container.querySelectorAll(
                '.dark\\:text-gray-400, .dark\\:bg-gray-700'
            );
            expect(darkModeElements.length).toBeGreaterThan(0);
        });
    });
});
