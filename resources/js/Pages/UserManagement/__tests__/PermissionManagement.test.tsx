import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { toast } from 'sonner';
import UserManagementIndex from '../Index';
import { Permission, Role, User } from '@/Types';

// Mock dependencies
vi.mock('axios');
vi.mock('sonner');
vi.mock('@inertiajs/react', () => ({
    router: {
        reload: vi.fn(),
    },
    Head: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

// Mock global route helper
(global as any).route = vi.fn((name: string, params?: any) => {
    if (name === 'user-management.create-permission') {
        return '/user-management/permissions';
    }
    if (name === 'user-management.delete-permission') {
        return `/user-management/permissions/${params}`;
    }
    if (name === 'user-management.assign-roles') {
        return `/user-management/users/${params}/roles`;
    }
    if (name === 'user-management.assign-permissions') {
        return `/user-management/users/${params}/permissions`;
    }
    if (name === 'user-management.show') {
        return `/user-management/users/${params}`;
    }
    return `/${name.replace(/\./g, '/')}`;
});

const mockedAxios = vi.mocked(axios);
const mockedToast = vi.mocked(toast);

describe('Permission Management', () => {
    const mockUser: User = {
        id: 1,
        uuid: 'user-uuid',
        first_name: 'John',
        last_name: 'Doe',
        email: 'john@example.com',
        email_verified_at: null,
        is_active: true,
        is_blocked: false,
        last_login_at: null,
        last_login_ip: null,
        last_login_user_agent: null,
        created_at: '2023-01-01T00:00:00.000000Z',
        updated_at: '2023-01-01T00:00:00.000000Z',
        roles: [],
        permissions: [],
    };

    const mockPermissions: Permission[] = [
        { id: 1, name: 'view articles', guard_name: 'web', created_at: '2023-01-01', updated_at: '2023-01-01' },
        { id: 2, name: 'edit articles', guard_name: 'web', created_at: '2023-01-01', updated_at: '2023-01-01' },
        { id: 3, name: 'delete articles', guard_name: 'web', created_at: '2023-01-01', updated_at: '2023-01-01' },
    ];

    const mockRoles: Role[] = [
        { id: 1, name: 'admin', guard_name: 'web', created_at: '2023-01-01', updated_at: '2023-01-01' },
        { id: 2, name: 'member', guard_name: 'web', created_at: '2023-01-01', updated_at: '2023-01-01' },
    ];

    const mockProps = {
        users: {
            data: [{ ...mockUser, roles: [], permissions: [] }],
            current_page: 1,
            last_page: 1,
        },
        roles: mockRoles,
        permissions: mockPermissions,
        teachers: [],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.post.mockResolvedValue({ data: { success: true } });
        mockedAxios.delete.mockResolvedValue({ data: { success: true } });
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    describe('Component Rendering', () => {
        it('renders UserManagement component without errors', () => {
            const { container } = render(<UserManagementIndex {...mockProps} />);
            expect(container).toBeInTheDocument();
        });

        it('receives permissions data as props', () => {
            render(<UserManagementIndex {...mockProps} />);
            // Component should render with permissions data passed as props
            expect(mockProps.permissions).toHaveLength(3);
            expect(mockProps.permissions[0].name).toBe('view articles');
        });
    });

    describe('Create Permission Modal', () => {
        it('opens create permission modal when button is clicked', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Navigate to permissions tab
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);

            // Click create permission button
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Check if modal is open
            expect(screen.getByText('Créer une nouvelle permission')).toBeInTheDocument();
            expect(screen.getByPlaceholderText('Nom de la permission (ex: edit articles)')).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /créer/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /annuler/i })).toBeInTheDocument();
        });

        it('closes modal when cancel button is clicked', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Click cancel
            const cancelButton = screen.getByRole('button', { name: /annuler/i });
            await user.click(cancelButton);

            // Modal should be closed
            expect(screen.queryByText('Créer une nouvelle permission')).not.toBeInTheDocument();
        });

        it('allows typing in permission name input', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Type in input
            const input = screen.getByPlaceholderText('Nom de la permission (ex: edit articles)');
            await user.type(input, 'test permission');

            expect(input).toHaveValue('test permission');
        });
    });

    describe('Permission Creation', () => {
        it('successfully creates a permission when form is submitted', async () => {
            const user = userEvent.setup();
            mockedAxios.post.mockResolvedValue({
                data: {
                    message: 'Permission created successfully',
                    permission: { id: 4, name: 'test permission' },
                },
            });

            render(<UserManagementIndex {...mockProps} />);

            // Open modal and fill form
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            const input = screen.getByPlaceholderText('Nom de la permission (ex: edit articles)');
            await user.type(input, 'test permission');

            // Submit form
            const submitButton = screen.getByRole('button', { name: /créer/i });
            await user.click(submitButton);

            // Check API call
            await waitFor(() => {
                expect(mockedAxios.post).toHaveBeenCalledWith('/user-management/permissions', {
                    name: 'test permission',
                });
            });

            // Check success toast
            expect(mockedToast.success).toHaveBeenCalledWith('Permission créée avec succès');
        });

        it('handles API errors gracefully', async () => {
            const user = userEvent.setup();
            mockedAxios.post.mockRejectedValue(new Error('Network error'));

            render(<UserManagementIndex {...mockProps} />);

            // Open modal and fill form
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            const input = screen.getByPlaceholderText('Nom de la permission (ex: edit articles)');
            await user.type(input, 'test permission');

            // Submit form
            const submitButton = screen.getByRole('button', { name: /créer/i });
            await user.click(submitButton);

            // Check error toast
            await waitFor(() => {
                expect(mockedToast.error).toHaveBeenCalledWith('Erreur lors de la création de la permission');
            });
        });

        it('clears form and closes modal after successful creation', async () => {
            const user = userEvent.setup();
            mockedAxios.post.mockResolvedValue({
                data: {
                    message: 'Permission created successfully',
                    permission: { id: 4, name: 'test permission' },
                },
            });

            render(<UserManagementIndex {...mockProps} />);

            // Open modal and fill form
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            const input = screen.getByPlaceholderText('Nom de la permission (ex: edit articles)');
            await user.type(input, 'test permission');

            // Submit form
            const submitButton = screen.getByRole('button', { name: /créer/i });
            await user.click(submitButton);

            // Wait for completion and check modal is closed
            await waitFor(() => {
                expect(screen.queryByText('Créer une nouvelle permission')).not.toBeInTheDocument();
            });
        });

        it('does not submit empty permission name', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Submit without entering name
            const submitButton = screen.getByRole('button', { name: /créer/i });
            await user.click(submitButton);

            // Should not make API call with empty name
            expect(mockedAxios.post).not.toHaveBeenCalled();
        });
    });

    describe('Permission Search and Filter', () => {
        it('filters permissions based on search input', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Navigate to permissions tab
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);

            // Find search input (assuming there's a search field for permissions)
            const searchInput = screen.getByPlaceholderText(/rechercher.*permission/i);
            await user.type(searchInput, 'edit');

            // Should show only permissions containing 'edit'
            expect(screen.getByText('edit articles')).toBeInTheDocument();
            expect(screen.queryByText('view articles')).not.toBeInTheDocument();
        });
    });

    describe('Permission Deletion', () => {
        it('shows delete confirmation dialog when delete button is clicked', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Navigate to permissions tab
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);

            // Find and click delete button for first permission
            const deleteButtons = screen.getAllByTestId('delete-permission');
            await user.click(deleteButtons[0]);

            // Should show confirmation dialog
            expect(screen.getByText(/supprimer.*permission/i)).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /supprimer/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /annuler/i })).toBeInTheDocument();
        });

        it('deletes permission when confirmed', async () => {
            const user = userEvent.setup();
            mockedAxios.delete.mockResolvedValue({
                data: { message: 'Permission deleted successfully' },
            });

            render(<UserManagementIndex {...mockProps} />);

            // Navigate to permissions tab
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);

            // Click delete and confirm
            const deleteButtons = screen.getAllByTestId('delete-permission');
            await user.click(deleteButtons[0]);

            const confirmButton = screen.getByRole('button', { name: /supprimer/i });
            await user.click(confirmButton);

            // Check API call
            await waitFor(() => {
                expect(mockedAxios.delete).toHaveBeenCalledWith('/user-management/permissions/1');
            });

            // Check success toast
            expect(mockedToast.success).toHaveBeenCalledWith('Permission supprimée avec succès');
        });
    });

    describe('Accessibility', () => {
        it('has proper ARIA labels for modal', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Check modal accessibility
            const modal = screen.getByRole('dialog');
            expect(modal).toBeInTheDocument();

            const modalHeading = within(modal).getByRole('heading', { name: /créer une nouvelle permission/i });
            expect(modalHeading).toBeInTheDocument();
        });

        it('supports keyboard navigation', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Navigate to permissions tab with keyboard
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            permissionsTab.focus();
            await user.keyboard('{Enter}');

            // Open modal with keyboard
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            createButton.focus();
            await user.keyboard('{Enter}');

            // Check if modal opened
            expect(screen.getByText('Créer une nouvelle permission')).toBeInTheDocument();

            // Tab through modal elements
            await user.keyboard('{Tab}');
            expect(screen.getByPlaceholderText('Nom de la permission (ex: edit articles)')).toHaveFocus();

            await user.keyboard('{Tab}');
            expect(screen.getByRole('button', { name: /annuler/i })).toHaveFocus();

            await user.keyboard('{Tab}');
            expect(screen.getByRole('button', { name: /créer/i })).toHaveFocus();
        });

        it('traps focus within modal', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Focus should be trapped within modal
            const submitButton = screen.getByRole('button', { name: /créer/i });
            submitButton.focus();

            // Tab from last element should return to first
            await user.keyboard('{Tab}');
            expect(screen.getByPlaceholderText('Nom de la permission (ex: edit articles)')).toHaveFocus();
        });

        it('closes modal with Escape key', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Open modal
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Press Escape
            await user.keyboard('{Escape}');

            // Modal should be closed
            expect(screen.queryByText('Créer une nouvelle permission')).not.toBeInTheDocument();
        });
    });

    describe('Integration', () => {
        it('reloads page data after successful permission creation', async () => {
            const user = userEvent.setup();
            const { router } = await import('@inertiajs/react');

            mockedAxios.post.mockResolvedValue({
                data: {
                    message: 'Permission created successfully',
                    permission: { id: 4, name: 'test permission' },
                },
            });

            render(<UserManagementIndex {...mockProps} />);

            // Create permission
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            const input = screen.getByPlaceholderText('Nom de la permission (ex: edit articles)');
            await user.type(input, 'test permission');

            const submitButton = screen.getByRole('button', { name: /créer/i });
            await user.click(submitButton);

            // Check page reload
            await waitFor(() => {
                expect(router.reload).toHaveBeenCalledWith({ only: ['permissions'] });
            });
        });

        it('maintains tab state after permission operations', async () => {
            const user = userEvent.setup();
            render(<UserManagementIndex {...mockProps} />);

            // Switch to permissions tab
            const permissionsTab = screen.getByRole('button', { name: /permissions/i });
            await user.click(permissionsTab);

            // Create a permission
            const createButton = screen.getByRole('button', { name: /créer une permission/i });
            await user.click(createButton);

            // Cancel modal
            const cancelButton = screen.getByRole('button', { name: /annuler/i });
            await user.click(cancelButton);

            // Should still be on permissions tab
            expect(permissionsTab).toHaveAttribute('aria-selected', 'true');
        });
    });
});