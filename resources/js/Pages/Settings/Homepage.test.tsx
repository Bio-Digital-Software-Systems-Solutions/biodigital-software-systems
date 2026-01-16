import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Homepage from './Homepage';

// Mock Inertia
const mockPost = vi.fn();
const mockDelete = vi.fn();
const mockSetData = vi.fn();
const mockReset = vi.fn();

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react');
    return {
        ...actual,
        router: {
            post: mockPost,
            delete: mockDelete,
        },
        useForm: vi.fn(() => ({
            data: {
                title: '',
                description: '',
                media_type: 'image',
                media_file: null,
                media_url: '',
                cta_text: '',
                cta_link: '',
                overlay_opacity: 0.5,
                is_active: true,
            },
            setData: mockSetData,
            post: vi.fn(),
            processing: false,
            errors: {},
            reset: mockReset,
        })),
        Head: ({ children }: any) => <>{children}</>,
        Link: ({ children, href, ...props }: any) => <a href={href} {...props}>{children}</a>,
    };
});

// Mock Laravel route helper
(global as any).route = vi.fn((name: string, params?: any) => {
    const routes: { [key: string]: string } = {
        'settings.index': '/settings',
        'settings.homepage': '/settings/homepage',
        'settings.homepage.slides.store': '/settings/homepage/slides',
        'settings.homepage.slides.update': `/settings/homepage/slides/${params || ''}`,
        'settings.homepage.slides.destroy': `/settings/homepage/slides/${params || ''}`,
        'settings.homepage.slides.reorder': '/settings/homepage/slides/reorder',
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
    Home: () => <div data-testid="home-icon">Home</div>,
    Plus: () => <div data-testid="plus-icon">Plus</div>,
    Pencil: () => <div data-testid="pencil-icon">Pencil</div>,
    Trash2: () => <div data-testid="trash-icon">Trash2</div>,
    GripVertical: () => <div data-testid="grip-icon">GripVertical</div>,
    Image: () => <div data-testid="image-icon">Image</div>,
    Video: () => <div data-testid="video-icon">Video</div>,
    Eye: () => <div data-testid="eye-icon">Eye</div>,
    EyeOff: () => <div data-testid="eye-off-icon">EyeOff</div>,
    ArrowLeft: () => <div data-testid="arrow-left-icon">ArrowLeft</div>,
}));

// Mock @dnd-kit
vi.mock('@dnd-kit/core', () => ({
    DndContext: ({ children }: any) => <div data-testid="dnd-context">{children}</div>,
    closestCenter: vi.fn(),
    KeyboardSensor: vi.fn(),
    PointerSensor: vi.fn(),
    useSensor: vi.fn(),
    useSensors: vi.fn(() => []),
}));

vi.mock('@dnd-kit/sortable', () => ({
    SortableContext: ({ children }: any) => <div data-testid="sortable-context">{children}</div>,
    sortableKeyboardCoordinates: vi.fn(),
    verticalListSortingStrategy: vi.fn(),
    useSortable: vi.fn(() => ({
        attributes: {},
        listeners: {},
        setNodeRef: vi.fn(),
        transform: null,
        transition: null,
        isDragging: false,
    })),
    arrayMove: vi.fn((arr, from, to) => {
        const result = [...arr];
        const [removed] = result.splice(from, 1);
        result.splice(to, 0, removed);
        return result;
    }),
}));

vi.mock('@dnd-kit/utilities', () => ({
    CSS: {
        Transform: {
            toString: vi.fn(() => ''),
        },
    },
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

vi.mock('@/Components/ui/input', () => ({
    Input: (props: any) => <input data-testid="input" {...props} />,
}));

vi.mock('@/Components/ui/textarea', () => ({
    Textarea: (props: any) => <textarea data-testid="textarea" {...props} />,
}));

vi.mock('@/Components/ui/label', () => ({
    Label: ({ children, ...props }: any) => <label {...props}>{children}</label>,
}));

vi.mock('@/Components/ui/switch', () => ({
    Switch: ({ checked, onCheckedChange, ...props }: any) => (
        <input
            type="checkbox"
            data-testid="switch"
            checked={checked}
            onChange={() => onCheckedChange?.(!checked)}
            {...props}
        />
    ),
}));

vi.mock('@/Components/ui/slider', () => ({
    Slider: ({ value, onValueChange, ...props }: any) => (
        <input
            type="range"
            data-testid="slider"
            value={value?.[0] || 0}
            onChange={(e) => onValueChange?.([Number(e.target.value)])}
            {...props}
        />
    ),
}));

vi.mock('@/Components/ui/dialog', () => ({
    Dialog: ({ children, open, ...props }: any) => open ? <div data-testid="dialog" {...props}>{children}</div> : null,
    DialogContent: ({ children, ...props }: any) => <div data-testid="dialog-content" {...props}>{children}</div>,
    DialogDescription: ({ children, ...props }: any) => <div data-testid="dialog-description" {...props}>{children}</div>,
    DialogFooter: ({ children, ...props }: any) => <div data-testid="dialog-footer" {...props}>{children}</div>,
    DialogHeader: ({ children, ...props }: any) => <div data-testid="dialog-header" {...props}>{children}</div>,
    DialogTitle: ({ children, ...props }: any) => <div data-testid="dialog-title" {...props}>{children}</div>,
}));

vi.mock('@/Components/ui/alert-dialog', () => ({
    AlertDialog: ({ children, open, ...props }: any) => open ? <div data-testid="alert-dialog" {...props}>{children}</div> : null,
    AlertDialogAction: ({ children, ...props }: any) => <button data-testid="alert-dialog-action" {...props}>{children}</button>,
    AlertDialogCancel: ({ children, ...props }: any) => <button data-testid="alert-dialog-cancel" {...props}>{children}</button>,
    AlertDialogContent: ({ children, ...props }: any) => <div data-testid="alert-dialog-content" {...props}>{children}</div>,
    AlertDialogDescription: ({ children, ...props }: any) => <div data-testid="alert-dialog-description" {...props}>{children}</div>,
    AlertDialogFooter: ({ children, ...props }: any) => <div data-testid="alert-dialog-footer" {...props}>{children}</div>,
    AlertDialogHeader: ({ children, ...props }: any) => <div data-testid="alert-dialog-header" {...props}>{children}</div>,
    AlertDialogTitle: ({ children, ...props }: any) => <div data-testid="alert-dialog-title" {...props}>{children}</div>,
}));

vi.mock('@/Components/ui/select', () => ({
    Select: ({ children, ...props }: any) => <div data-testid="select" {...props}>{children}</div>,
    SelectContent: ({ children, ...props }: any) => <div data-testid="select-content" {...props}>{children}</div>,
    SelectItem: ({ children, value, ...props }: any) => <option data-testid="select-item" value={value} {...props}>{children}</option>,
    SelectTrigger: ({ children, ...props }: any) => <div data-testid="select-trigger" {...props}>{children}</div>,
    SelectValue: (props: any) => <span data-testid="select-value" {...props} />,
}));

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

describe('Homepage Settings Page', () => {
    const defaultProps = {
        auth: {
            user: {
                id: 1,
                name: 'Admin User',
                email: 'admin@example.com',
            },
        },
        slides: [
            {
                id: 1,
                uuid: 'slide-1-uuid',
                title: 'First Slide',
                description: 'Description for first slide',
                media_type: 'image' as const,
                media_url: '/storage/hero-slides/slide1.jpg',
                cta_text: 'Learn More',
                cta_link: '/about',
                overlay_opacity: 0.5,
                order: 1,
                is_active: true,
            },
            {
                id: 2,
                uuid: 'slide-2-uuid',
                title: 'Second Slide',
                description: 'Description for second slide',
                media_type: 'video' as const,
                media_url: '/storage/hero-slides/slide2.mp4',
                cta_text: null,
                cta_link: null,
                overlay_opacity: 0.3,
                order: 2,
                is_active: false,
            },
        ],
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the homepage settings page correctly', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText("Page d'accueil")).toBeInTheDocument();
        expect(screen.getByText("Gérez les images et vidéos du carrousel de la page d'accueil")).toBeInTheDocument();
    });

    it('displays back link to settings', () => {
        render(<Homepage {...defaultProps} />);

        const backLink = screen.getByText('Retour aux paramètres');
        expect(backLink).toBeInTheDocument();
        expect(backLink.closest('a')).toHaveAttribute('href', '/settings');
    });

    it('displays add slide button', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText('Ajouter un slide')).toBeInTheDocument();
    });

    it('renders all slides in the list', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText('First Slide')).toBeInTheDocument();
        expect(screen.getByText('Second Slide')).toBeInTheDocument();
    });

    it('shows correct status badges for active/inactive slides', () => {
        render(<Homepage {...defaultProps} />);

        // Active badge should exist
        expect(screen.getByText('Actif')).toBeInTheDocument();
        // Inactive badge should exist
        expect(screen.getByText('Inactif')).toBeInTheDocument();
    });

    it('shows media type badges', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText('Image')).toBeInTheDocument();
        expect(screen.getByText('Vidéo')).toBeInTheDocument();
    });

    it('displays empty state when no slides', () => {
        const propsWithNoSlides = {
            ...defaultProps,
            slides: [],
        };

        render(<Homepage {...propsWithNoSlides} />);

        expect(screen.getByText('Aucun slide')).toBeInTheDocument();
        expect(screen.getByText('Commencez par ajouter des images ou vidéos au carrousel.')).toBeInTheDocument();
    });

    it('opens add modal when add button is clicked', () => {
        render(<Homepage {...defaultProps} />);

        const addButton = screen.getByText('Ajouter un slide');
        fireEvent.click(addButton);

        // The dialog should now be open
        expect(screen.getByTestId('dialog')).toBeInTheDocument();
        expect(screen.getByTestId('dialog-title')).toBeInTheDocument();
    });

    it('shows slide order numbers', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText('#1')).toBeInTheDocument();
        expect(screen.getByText('#2')).toBeInTheDocument();
    });

    it('renders action buttons for each slide', () => {
        render(<Homepage {...defaultProps} />);

        // Should have 2 edit buttons (one per slide)
        const editIcons = screen.getAllByTestId('pencil-icon');
        expect(editIcons).toHaveLength(2);

        // Should have 2 delete buttons (one per slide)
        const deleteIcons = screen.getAllByTestId('trash-icon');
        expect(deleteIcons).toHaveLength(2);
    });

    it('renders drag handles for reordering', () => {
        render(<Homepage {...defaultProps} />);

        const gripIcons = screen.getAllByTestId('grip-icon');
        expect(gripIcons).toHaveLength(2);
    });

    it('displays card header correctly', () => {
        render(<Homepage {...defaultProps} />);

        expect(screen.getByText('Slides du carrousel')).toBeInTheDocument();
        expect(screen.getByText("Glissez-déposez pour réorganiser les slides. Les slides actifs apparaissent sur la page d'accueil.")).toBeInTheDocument();
    });
});
