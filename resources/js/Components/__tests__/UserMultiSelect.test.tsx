import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import UserMultiSelect from '../UserMultiSelect';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as any;

const mockUsers = [
    {
        id: 1,
        first_name: 'John',
        last_name: 'Doe',
        email: 'john.doe@example.com',
        avatar: null,
    },
    {
        id: 2,
        first_name: 'Jane',
        last_name: 'Smith',
        email: 'jane.smith@example.com',
        avatar: 'https://example.com/avatar.jpg',
    },
    {
        id: 3,
        first_name: 'Bob',
        last_name: 'Johnson',
        email: 'bob.johnson@example.com',
        avatar: null,
    },
];

describe('UserMultiSelect Component', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.get.mockResolvedValue({ data: mockUsers });
    });

    describe('Rendering', () => {
        it('renders with default label', async () => {
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            expect(screen.getByText('Participants')).toBeInTheDocument();
        });

        it('renders with custom label', async () => {
            render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={vi.fn()}
                    label="Invités"
                />
            );

            expect(screen.getByText('Invités')).toBeInTheDocument();
        });

        it('renders with placeholder when no users selected', async () => {
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            expect(screen.getByText('Sélectionner des participants...')).toBeInTheDocument();
        });

        it('displays error message when error prop is provided', () => {
            render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={vi.fn()}
                    error="Ce champ est requis"
                />
            );

            expect(screen.getByText('Ce champ est requis')).toBeInTheDocument();
        });

        it('applies error styles when error is present', () => {
            const { container } = render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={vi.fn()}
                    error="Error message"
                />
            );

            const inputContainer = container.querySelector('.border-red-300');
            expect(inputContainer).toBeInTheDocument();
        });
    });

    describe('User Selection Display', () => {
        it('displays selected users as tags', async () => {
            render(
                <UserMultiSelect
                    selectedUserIds={[1, 2]}
                    onChange={vi.fn()}
                />
            );

            // Wait for users to load
            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
                expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            });
        });

        it('shows user avatar when available', async () => {
            render(
                <UserMultiSelect
                    selectedUserIds={[2]}
                    onChange={vi.fn()}
                />
            );

            await waitFor(() => {
                const avatar = screen.getByAltText('Jane Smith');
                expect(avatar).toBeInTheDocument();
                expect(avatar).toHaveAttribute('src', 'https://example.com/avatar.jpg');
            });
        });

        it('shows user icon when avatar is not available', async () => {
            render(
                <UserMultiSelect
                    selectedUserIds={[1]}
                    onChange={vi.fn()}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });
        });
    });

    describe('Dropdown Interaction', () => {
        it('opens dropdown when clicking on input container', async () => {
            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByPlaceholderText('Rechercher des participants...')).toBeInTheDocument();
            });
        });

        it('closes dropdown when clicking outside', async () => {
            const user = userEvent.setup();
            render(
                <div>
                    <UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />
                    <button>Outside</button>
                </div>
            );

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByPlaceholderText('Rechercher des participants...')).toBeInTheDocument();
            });

            await user.click(screen.getByText('Outside'));

            await waitFor(() => {
                expect(screen.queryByPlaceholderText('Rechercher des participants...')).not.toBeInTheDocument();
            });
        });

        it('displays loading state while fetching users', async () => {
            mockedAxios.get.mockImplementation(
                () => new Promise((resolve) => setTimeout(() => resolve({ data: mockUsers }), 100))
            );

            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            expect(screen.getByText('Chargement...')).toBeInTheDocument();

            await waitFor(() => {
                expect(screen.queryByText('Chargement...')).not.toBeInTheDocument();
            });
        });
    });

    describe('Search Functionality', () => {
        it('calls API with search parameter when typing', async () => {
            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            const searchInput = await screen.findByPlaceholderText('Rechercher des participants...');
            await user.type(searchInput, 'John');

            // Wait for debounced search (300ms)
            await waitFor(
                () => {
                    expect(mockedAxios.get).toHaveBeenCalledWith('/api/users', {
                        params: { search: 'John' },
                    });
                },
                { timeout: 500 }
            );
        });

        it('debounces search requests', async () => {
            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            const searchInput = await screen.findByPlaceholderText('Rechercher des participants...');

            // Type multiple characters quickly
            await user.type(searchInput, 'Jo');

            // Should not call API immediately for each character
            expect(mockedAxios.get).toHaveBeenCalledTimes(1); // Initial load only

            // Wait for debounce
            await waitFor(
                () => {
                    expect(mockedAxios.get).toHaveBeenCalledWith('/api/users', {
                        params: { search: 'Jo' },
                    });
                },
                { timeout: 500 }
            );
        });

        it('shows "no users found" message when search returns empty', async () => {
            const user = userEvent.setup();
            mockedAxios.get.mockResolvedValueOnce({ data: mockUsers });

            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            mockedAxios.get.mockResolvedValueOnce({ data: [] });

            const searchInput = await screen.findByPlaceholderText('Rechercher des participants...');
            await user.clear(searchInput);
            await user.type(searchInput, 'NonExistent');

            await waitFor(
                () => {
                    expect(screen.getByText('Aucun utilisateur trouvé')).toBeInTheDocument();
                },
                { timeout: 500 }
            );
        });
    });

    describe('User Selection/Deselection', () => {
        it('calls onChange with new user id when selecting a user', async () => {
            const handleChange = vi.fn();
            const user = userEvent.setup();

            render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={handleChange}
                />
            );

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            const johnOption = screen.getByText('John Doe').closest('button')!;
            await user.click(johnOption);

            expect(handleChange).toHaveBeenCalledWith([1]);
        });

        it('calls onChange without user id when deselecting a user', async () => {
            const handleChange = vi.fn();
            const user = userEvent.setup();

            render(
                <UserMultiSelect
                    selectedUserIds={[1, 2]}
                    onChange={handleChange}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            // Find remove button for John Doe
            const johnTag = screen.getByText('John Doe').parentElement!;
            const removeButton = johnTag.querySelector('button')!;

            await user.click(removeButton);

            expect(handleChange).toHaveBeenCalledWith([2]);
        });

        it('supports selecting multiple users', async () => {
            const handleChange = vi.fn();
            const user = userEvent.setup();

            const { container } = render(
                <UserMultiSelect
                    selectedUserIds={[1]}
                    onChange={handleChange}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            // Click on the input container to open dropdown
            const inputContainer = container.querySelector('.min-h-\\[42px\\]')!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByPlaceholderText('Rechercher des participants...')).toBeInTheDocument();
            });

            // Wait for users to load in dropdown
            await waitFor(() => {
                const janeInDropdown = Array.from(container.querySelectorAll('button')).find(
                    btn => btn.textContent?.includes('Jane Smith') && btn.textContent?.includes('jane.smith@example.com')
                );
                expect(janeInDropdown).toBeDefined();
            });

            // Click on Jane Smith option
            const janeOption = Array.from(container.querySelectorAll('button')).find(
                btn => btn.textContent?.includes('Jane Smith') && btn.textContent?.includes('jane.smith@example.com')
            )!;
            await user.click(janeOption);

            expect(handleChange).toHaveBeenCalledWith([1, 2]);
        });

        it('filters out selected users from dropdown list', async () => {
            const user = userEvent.setup();

            render(
                <UserMultiSelect
                    selectedUserIds={[1]}
                    onChange={vi.fn()}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            const inputContainer = screen.getByText('John Doe').parentElement!.parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                // Search input should be present
                expect(screen.getByPlaceholderText('Rechercher des participants...')).toBeInTheDocument();
            });

            // John Doe should not appear in the dropdown list (already selected)
            const dropdownJohn = screen.queryAllByText('John Doe').find(
                (el) => el.closest('button') && !el.closest('.bg-blue-100')
            );
            expect(dropdownJohn).toBeUndefined();

            // But Jane Smith should appear
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        });
    });

    describe('Selected Count Display', () => {
        it('shows count of selected participants', async () => {
            const user = userEvent.setup();

            render(
                <UserMultiSelect
                    selectedUserIds={[1, 2]}
                    onChange={vi.fn()}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            const inputContainer = screen.getByText('John Doe').parentElement!.parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByText('2 participants sélectionnés')).toBeInTheDocument();
            });
        });

        it('uses singular form when one participant is selected', async () => {
            const user = userEvent.setup();

            render(
                <UserMultiSelect
                    selectedUserIds={[1]}
                    onChange={vi.fn()}
                />
            );

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            const inputContainer = screen.getByText('John Doe').parentElement!.parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                expect(screen.getByText('1 participant sélectionné')).toBeInTheDocument();
            });
        });
    });

    describe('Accessibility', () => {
        it('focuses search input when dropdown opens', async () => {
            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                const searchInput = screen.getByPlaceholderText('Rechercher des participants...');
                expect(searchInput).toHaveFocus();
            });
        });

        it('supports keyboard navigation', async () => {
            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            const searchInput = await screen.findByPlaceholderText('Rechercher des participants...');

            // Tab to next focusable element
            await user.tab();

            // Search input should lose focus
            expect(searchInput).not.toHaveFocus();
        });
    });

    describe('Error Handling', () => {
        it('handles API errors gracefully', async () => {
            const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockRejectedValueOnce(new Error('Network error'));

            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            // Component should still render without crashing
            await waitFor(() => {
                expect(screen.getByPlaceholderText('Rechercher des participants...')).toBeInTheDocument();
            });

            consoleErrorSpy.mockRestore();
        });

        it('continues to work after API error', async () => {
            const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockRejectedValueOnce(new Error('Network error'));

            const user = userEvent.setup();
            render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            // After error, retry with successful response
            mockedAxios.get.mockResolvedValueOnce({ data: mockUsers });

            const searchInput = await screen.findByPlaceholderText('Rechercher des participants...');
            await user.type(searchInput, 'John');

            await waitFor(
                () => {
                    expect(screen.getByText('John Doe')).toBeInTheDocument();
                },
                { timeout: 500 }
            );

            consoleErrorSpy.mockRestore();
        });
    });

    describe('Dark Mode Support', () => {
        it('renders with dark mode classes', () => {
            const { container } = render(<UserMultiSelect selectedUserIds={[]} onChange={vi.fn()} />);

            // Check for dark mode classes
            const darkModeElements = container.querySelectorAll('.dark\\:bg-gray-700, .dark\\:text-gray-300, .dark\\:border-gray-600');
            expect(darkModeElements.length).toBeGreaterThan(0);
        });
    });

    describe('Custom Props', () => {
        it('accepts custom placeholder', async () => {
            const user = userEvent.setup();
            render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={vi.fn()}
                    placeholder="Rechercher des utilisateurs..."
                />
            );

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            expect(screen.getByPlaceholderText('Rechercher des utilisateurs...')).toBeInTheDocument();
        });

        it('accepts custom maxHeight', async () => {
            const user = userEvent.setup();
            const { container } = render(
                <UserMultiSelect
                    selectedUserIds={[]}
                    onChange={vi.fn()}
                    maxHeight="max-h-96"
                />
            );

            const inputContainer = screen.getByText('Sélectionner des participants...').parentElement!;
            await user.click(inputContainer);

            await waitFor(() => {
                const dropdown = container.querySelector('.max-h-96');
                expect(dropdown).toBeInTheDocument();
            });
        });
    });
});
