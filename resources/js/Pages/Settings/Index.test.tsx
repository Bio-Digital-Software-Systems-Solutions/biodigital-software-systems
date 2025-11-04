import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { router } from '@inertiajs/react';
import Index from './Index';

// Mock useForm hook
const mockPost = vi.fn();
const mockSetData = vi.fn();

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react');
    return {
        ...actual,
        router: {
            post: vi.fn(),
        },
        useForm: vi.fn(() => ({
            data: {
                email_notifications: true,
                sms_notifications: false,
                push_notifications: true,
                newsletter: false,
                event_reminders: true,
                training_updates: true,
                message_notifications: true,
            },
            setData: mockSetData,
            post: mockPost,
            processing: false,
        })),
        Head: ({ children }: any) => <>{children}</>,
        Link: ({ children, href, ...props }: any) => <a href={href} {...props}>{children}</a>,
    };
});

// Mock Laravel route helper
(global as any).route = vi.fn((name: string, params?: any) => {
    const routes: { [key: string]: string } = {
        'settings.update': '/settings',
        'profile.edit': '/profile/edit',
        'login': '/login',
    };
    return routes[name] || `/${name}`;
});

// Mock sonner toast
vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

// Mock Lucide React icons
vi.mock('lucide-react', () => ({
    Bell: () => <div data-testid="bell-icon">Bell</div>,
    Shield: () => <div data-testid="shield-icon">Shield</div>,
    User: () => <div data-testid="user-icon">User</div>,
    Palette: () => <div data-testid="palette-icon">Palette</div>,
    CheckCircle2: () => <div data-testid="check-circle-icon">CheckCircle2</div>,
    Lock: () => <div data-testid="lock-icon">Lock</div>,
    Globe: () => <div data-testid="globe-icon">Globe</div>,
}));

// Mock UI components
vi.mock('@/Components/ui/card', () => ({
    Card: ({ children, ...props }: any) => <div data-testid="card" {...props}>{children}</div>,
    CardContent: ({ children, ...props }: any) => <div data-testid="card-content" {...props}>{children}</div>,
    CardDescription: ({ children, ...props }: any) => <div data-testid="card-description" {...props}>{children}</div>,
    CardHeader: ({ children, ...props }: any) => <div data-testid="card-header" {...props}>{children}</div>,
    CardTitle: ({ children, ...props }: any) => <div data-testid="card-title" {...props}>{children}</div>,
}));

vi.mock('@/Components/ui/button', () => ({
    Button: ({ children, ...props }: any) => <button {...props}>{children}</button>,
}));

vi.mock('@/Components/ui/badge', () => ({
    Badge: ({ children, ...props }: any) => <span data-testid="badge" {...props}>{children}</span>,
}));

vi.mock('@/Components/ui/tabs', () => ({
    Tabs: ({ children, ...props }: any) => <div data-testid="tabs" {...props}>{children}</div>,
    TabsContent: ({ children, ...props }: any) => <div data-testid="tabs-content" {...props}>{children}</div>,
    TabsList: ({ children, ...props }: any) => <div data-testid="tabs-list" {...props}>{children}</div>,
    TabsTrigger: ({ children, ...props }: any) => <button data-testid="tabs-trigger" {...props}>{children}</button>,
}));

vi.mock('@/Components/ui/switch', () => ({
    Switch: ({ checked, onCheckedChange, disabled, ...props }: any) => (
        <input
            type="checkbox"
            data-testid="switch"
            checked={checked}
            disabled={disabled}
            onChange={() => onCheckedChange?.()} // Call without parameters like the real component
            {...props}
        />
    ),
}));

vi.mock('@/Components/ui/label', () => ({
    Label: ({ children, ...props }: any) => <label {...props}>{children}</label>,
}));

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

describe('Settings Index Page', () => {
    const defaultProps = {
        auth: {
            user: {
                id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                first_name: 'John',
                last_name: 'Doe',
                phone: '+1234567890',
            },
        },
        settings: {
            email_notifications: true,
            sms_notifications: false,
            push_notifications: true,
            newsletter: false,
            event_reminders: true,
            training_updates: true,
            message_notifications: true,
        },
    };

    beforeEach(() => {
        vi.clearAllMocks();
        mockPost.mockClear();
        mockSetData.mockClear();
    });

    it('renders the settings page correctly', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('Paramètres')).toBeInTheDocument();
        expect(screen.getByText('Gérez vos préférences et paramètres de compte')).toBeInTheDocument();
    });

    it('displays all notification tabs', () => {
        render(<Index {...defaultProps} />);

        const tabButtons = screen.getAllByTestId('tabs-trigger');
        expect(tabButtons).toHaveLength(4);

        expect(screen.getByText('Compte')).toBeInTheDocument();
        expect(screen.getByText('Confidentialité')).toBeInTheDocument();
        expect(screen.getByText('Préférences')).toBeInTheDocument();
    });

    it('displays all notification settings switches', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('Notifications par email')).toBeInTheDocument();
        expect(screen.getByText('Notifications SMS')).toBeInTheDocument();
        expect(screen.getByText('Notifications push')).toBeInTheDocument();
        expect(screen.getByText('Notifications de messages')).toBeInTheDocument();
        expect(screen.getByText('Mises à jour de formations')).toBeInTheDocument();
        expect(screen.getByText('Rappels d\'événements')).toBeInTheDocument();
        expect(screen.getByText('Newsletter')).toBeInTheDocument();
    });


    it('renders all notification switches with correct labels', () => {
        render(<Index {...defaultProps} />);

        // Verify all notification switch labels are present
        expect(screen.getByText('Notifications par email')).toBeInTheDocument();
        expect(screen.getByText('Notifications SMS')).toBeInTheDocument();
        expect(screen.getByText('Notifications push')).toBeInTheDocument();
        expect(screen.getByText('Notifications de messages')).toBeInTheDocument();
        expect(screen.getByText('Mises à jour de formations')).toBeInTheDocument();
        expect(screen.getByText('Rappels d\'événements')).toBeInTheDocument();
        expect(screen.getByText('Newsletter')).toBeInTheDocument();

        // Verify all switches are rendered
        const switches = screen.getAllByTestId('switch');
        expect(switches).toHaveLength(7);
    });

    it('switches reflect current settings values', () => {
        render(<Index {...defaultProps} />);

        const switches = screen.getAllByTestId('switch');

        // Email notifications should be checked (true in defaultProps)
        expect(switches[0]).toBeChecked();
        // SMS notifications should not be checked (false in defaultProps)
        expect(switches[1]).not.toBeChecked();
        // Push notifications should be checked (true in defaultProps)
        expect(switches[2]).toBeChecked();
        // Message notifications should be checked (true in defaultProps)
        expect(switches[3]).toBeChecked();
        // Training updates should be checked (true in defaultProps)
        expect(switches[4]).toBeChecked();
        // Event reminders should be checked (true in defaultProps)
        expect(switches[5]).toBeChecked();
        // Newsletter should not be checked (false in defaultProps)
        expect(switches[6]).not.toBeChecked();
    });

    it('displays user information in account tab', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();
        expect(screen.getByText('Vérifié')).toBeInTheDocument();
    });

    it('displays account management buttons', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('Modifier le profil')).toBeInTheDocument();
        expect(screen.getByText('Changer le mot de passe')).toBeInTheDocument();
    });

    it('displays privacy information', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('Vos données sont protégées')).toBeInTheDocument();
        expect(screen.getByText('Politique de confidentialité')).toBeInTheDocument();
        expect(screen.getByText('Conditions d\'utilisation')).toBeInTheDocument();
    });

    it('displays preferences information', () => {
        render(<Index {...defaultProps} />);

        expect(screen.getByText('Thème')).toBeInTheDocument();
        expect(screen.getByText('Langue')).toBeInTheDocument();
        expect(screen.getByText('Automatique')).toBeInTheDocument();
        expect(screen.getByText('Français')).toBeInTheDocument();
    });

    it('disables switches while processing', async () => {
        // Mock useForm to return processing: true
        const mockUseForm = vi.mocked(await import('@inertiajs/react')).useForm;
        mockUseForm.mockReturnValue({
            data: {
                email_notifications: true,
                sms_notifications: false,
                push_notifications: true,
                newsletter: false,
                event_reminders: true,
                training_updates: true,
                message_notifications: true,
            },
            setData: mockSetData,
            post: mockPost,
            processing: true, // This simulates the processing state
        });

        render(<Index {...defaultProps} />);

        const switches = screen.getAllByTestId('switch');
        // All switches should be disabled while processing
        switches.forEach(switchElement => {
            expect(switchElement).toBeDisabled();
        });
    });

    it('handles settings with undefined values gracefully', () => {
        const propsWithUndefinedSettings = {
            ...defaultProps,
            settings: undefined,
        };

        expect(() => render(<Index {...propsWithUndefinedSettings} />)).not.toThrow();
    });

    it('renders without user auth data', () => {
        const propsWithoutAuth = {
            ...defaultProps,
            auth: {
                user: {
                    id: 1,
                    name: '',
                    email: 'test@example.com',
                    first_name: '',
                    last_name: '',
                },
            },
        };

        expect(() => render(<Index {...propsWithoutAuth} />)).not.toThrow();
    });
});