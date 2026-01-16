import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Slider } from '@/Components/ui/slider';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { toast } from 'sonner';
import {
    DndContext,
    DragEndEvent,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
    useSortable,
    arrayMove,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    Home,
    Plus,
    Pencil,
    Trash2,
    GripVertical,
    Image as ImageIcon,
    Video,
    Eye,
    EyeOff,
    ArrowLeft,
    Globe,
    Save,
    MapPin,
} from 'lucide-react';
import { Link } from '@inertiajs/react';

interface HeroSlide {
    id: number;
    uuid: string;
    title: string;
    description: string;
    media_type: 'image' | 'video';
    media_url: string;
    cta_text: string | null;
    cta_link: string | null;
    overlay_opacity: number;
    order: number;
    is_active: boolean;
}

interface Props {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    slides: HeroSlide[];
    globalStats: GlobalStats;
    churches: Church[];
}

interface Church {
    id: number;
    name: string;
    city: string;
    country: string;
    latitude: number;
    longitude: number;
    members: number;
    address: string | null;
    website: string | null;
    email: string | null;
    phone: string | null;
    leader_name: string | null;
    category: string | null;
    continent: string | null;
    is_active: boolean;
}

interface GlobalStats {
    total_churches: number;
    total_countries: number;
    total_members: number;
    europe: number;
    africa: number;
    americas: number;
    asia: number;
    oceania: number;
}

interface SortableSlideProps {
    slide: HeroSlide;
    onEdit: (slide: HeroSlide) => void;
    onDelete: (slide: HeroSlide) => void;
    onView: (slide: HeroSlide) => void;
}

function SortableSlide({ slide, onEdit, onDelete, onView }: SortableSlideProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: slide.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`
                flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border
                ${isDragging ? 'border-primary shadow-lg' : 'border-gray-200 dark:border-gray-700'}
            `}
        >
            {/* Drag Handle */}
            <button
                {...attributes}
                {...listeners}
                className="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                aria-label="Drag to reorder"
            >
                <GripVertical className="h-5 w-5" />
            </button>

            {/* Clickable area - Media Preview + Slide Info */}
            <div
                className="flex items-center gap-4 flex-1 min-w-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg p-2 -m-2 transition-colors"
                onClick={() => onView(slide)}
            >
                {/* Media Preview */}
                <div className="w-24 h-16 rounded overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                    {slide.media_type === 'video' ? (
                        <video
                            src={slide.media_url}
                            className="w-full h-full object-cover"
                            muted
                        />
                    ) : (
                        <img
                            src={slide.media_url}
                            alt={slide.title || 'Slide'}
                            className="w-full h-full object-cover"
                        />
                    )}
                </div>

                {/* Slide Info */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="font-medium truncate">
                            {slide.title || 'Sans titre'}
                        </span>
                        <Badge variant={slide.media_type === 'video' ? 'secondary' : 'outline'} className="text-xs">
                            {slide.media_type === 'video' ? (
                                <><Video className="h-3 w-3 mr-1" /> Vidéo</>
                            ) : (
                                <><ImageIcon className="h-3 w-3 mr-1" /> Image</>
                            )}
                        </Badge>
                        {slide.is_active ? (
                            <Badge variant="default" className="text-xs bg-green-500">
                                <Eye className="h-3 w-3 mr-1" /> Actif
                            </Badge>
                        ) : (
                            <Badge variant="secondary" className="text-xs">
                                <EyeOff className="h-3 w-3 mr-1" /> Inactif
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {slide.description || 'Pas de description'}
                    </p>
                </div>
            </div>

            {/* Order Number */}
            <div className="text-sm text-gray-400 font-mono">
                #{slide.order}
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onEdit(slide)}
                    aria-label="Modifier"
                >
                    <Pencil className="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onDelete(slide)}
                    className="text-destructive hover:text-destructive"
                    aria-label="Supprimer"
                >
                    <Trash2 className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

export default function Homepage({ auth, slides: initialSlides, globalStats: initialGlobalStats, churches: initialChurches }: Props) {
    const [slides, setSlides] = useState<HeroSlide[]>(initialSlides);
    const [showAddModal, setShowAddModal] = useState(false);
    const [editingSlide, setEditingSlide] = useState<HeroSlide | null>(null);
    const [deletingSlide, setDeletingSlide] = useState<HeroSlide | null>(null);
    const [viewingSlide, setViewingSlide] = useState<HeroSlide | null>(null);

    // Church management state
    const [churches, setChurches] = useState<Church[]>(initialChurches ?? []);
    const [showChurchModal, setShowChurchModal] = useState(false);
    const [editingChurch, setEditingChurch] = useState<Church | null>(null);
    const [deletingChurch, setDeletingChurch] = useState<Church | null>(null);
    const [viewingChurch, setViewingChurch] = useState<Church | null>(null);
    const [churchProcessing, setChurchProcessing] = useState(false);
    const [churchForm, setChurchForm] = useState({
        name: '',
        city: '',
        country: '',
        latitude: '',
        longitude: '',
        members: '',
        address: '',
        website: '',
        email: '',
        phone: '',
        leader_name: '',
        category: 'eglise',
        is_active: true,
    });

    const resetChurchForm = () => {
        setChurchForm({
            name: '',
            city: '',
            country: '',
            latitude: '',
            longitude: '',
            members: '',
            address: '',
            website: '',
            email: '',
            phone: '',
            leader_name: '',
            category: 'eglise',
            is_active: true,
        });
    };

    const openChurchModal = (church?: Church) => {
        if (church) {
            setEditingChurch(church);
            setChurchForm({
                name: church.name,
                city: church.city,
                country: church.country,
                latitude: String(church.latitude ?? ''),
                longitude: String(church.longitude ?? ''),
                members: String(church.members || ''),
                address: church.address || '',
                website: church.website || '',
                email: church.email || '',
                phone: church.phone || '',
                leader_name: church.leader_name || '',
                category: church.category || 'eglise',
                is_active: church.is_active,
            });
        } else {
            setEditingChurch(null);
            resetChurchForm();
        }
        setShowChurchModal(true);
    };

    const closeChurchModal = () => {
        setShowChurchModal(false);
        setEditingChurch(null);
        resetChurchForm();
    };

    const handleChurchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setChurchProcessing(true);

        const formData = {
            name: churchForm.name,
            city: churchForm.city,
            country: churchForm.country,
            latitude: churchForm.latitude ? parseFloat(churchForm.latitude) : null,
            longitude: churchForm.longitude ? parseFloat(churchForm.longitude) : null,
            members: churchForm.members ? parseInt(churchForm.members, 10) : 0,
            address: churchForm.address || null,
            website: churchForm.website || null,
            email: churchForm.email || null,
            phone: churchForm.phone || null,
            leader_name: churchForm.leader_name || null,
            category: churchForm.category || 'eglise',
            is_active: churchForm.is_active,
        };

        const routeName = editingChurch
            ? route('settings.homepage.churches.update', editingChurch.id)
            : route('settings.homepage.churches.store');

        router.post(routeName, formData as unknown as Record<string, string>, {
            preserveState: false,
            onSuccess: () => {
                toast.success(editingChurch ? 'Église mise à jour' : 'Église ajoutée');
                closeChurchModal();
                setChurchProcessing(false);
            },
            onError: (errors) => {
                toast.error('Erreur lors de la sauvegarde');
                console.error(errors);
                setChurchProcessing(false);
            },
        });
    };

    const handleChurchDelete = () => {
        if (!deletingChurch) return;
        router.delete(route('settings.homepage.churches.destroy', deletingChurch.id), {
            preserveState: false,
            onSuccess: () => {
                toast.success('Église supprimée');
                setDeletingChurch(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            },
        });
    };

    // Global Stats form - ensure all fields have defaults for backward compatibility
    const [globalStats, setGlobalStats] = useState<GlobalStats>({
        total_churches: initialGlobalStats.total_churches ?? 0,
        total_countries: initialGlobalStats.total_countries ?? 0,
        total_members: initialGlobalStats.total_members ?? 0,
        europe: initialGlobalStats.europe ?? 0,
        africa: initialGlobalStats.africa ?? 0,
        americas: initialGlobalStats.americas ?? 0,
        asia: initialGlobalStats.asia ?? 0,
        oceania: initialGlobalStats.oceania ?? 0,
    });
    const [statsProcessing, setStatsProcessing] = useState(false);

    const handleStatsChange = (field: keyof GlobalStats, value: string) => {
        const numValue = parseInt(value, 10) || 0;
        setGlobalStats(prev => {
            const updated = { ...prev, [field]: numValue };
            // Auto-calculate total churches from regions
            const regionFields: (keyof GlobalStats)[] = ['europe', 'africa', 'americas', 'asia', 'oceania'];
            if (regionFields.includes(field)) {
                updated.total_churches = updated.europe + updated.africa + updated.americas + updated.asia + updated.oceania;
            }
            return updated;
        });
    };

    const handleStatsSave = () => {
        setStatsProcessing(true);
        router.post(route('settings.homepage.global-stats.update'), globalStats as unknown as Record<string, string>, {
            preserveState: false,
            onSuccess: () => {
                toast.success('Statistiques mises à jour avec succès');
                setStatsProcessing(false);
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour des statistiques');
                setStatsProcessing(false);
            },
        });
    };

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        media_type: 'image' as 'image' | 'video',
        media_file: null as File | null,
        media_url: '',
        cta_text: '',
        cta_link: '',
        overlay_opacity: 0.5,
        is_active: true,
    });

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = slides.findIndex((s) => s.id === active.id);
            const newIndex = slides.findIndex((s) => s.id === over.id);

            const newSlides = arrayMove(slides, oldIndex, newIndex);
            setSlides(newSlides);

            // Send reorder request
            const reorderData = newSlides.map((slide, index) => ({
                id: slide.id,
                order: index + 1,
            }));

            router.post(route('settings.homepage.slides.reorder'), { slides: reorderData }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Ordre mis à jour');
                },
                onError: () => {
                    toast.error('Erreur lors de la mise à jour de l\'ordre');
                    setSlides(initialSlides);
                },
            });
        }
    };

    const handleAddSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('title', data.title);
        formData.append('description', data.description);
        formData.append('media_type', data.media_type);
        if (data.media_file) {
            formData.append('media_file', data.media_file);
        } else {
            formData.append('media_url', data.media_url);
        }
        formData.append('cta_text', data.cta_text);
        formData.append('cta_link', data.cta_link);
        formData.append('overlay_opacity', String(data.overlay_opacity));
        formData.append('is_active', data.is_active ? '1' : '0');

        router.post(route('settings.homepage.slides.store'), formData, {
            forceFormData: true,
            preserveState: false,
            onSuccess: () => {
                toast.success('Slide ajouté avec succès');
                setShowAddModal(false);
                reset();
            },
            onError: () => {
                toast.error('Erreur lors de l\'ajout du slide');
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingSlide) return;

        const formData = new FormData();
        formData.append('title', data.title);
        formData.append('description', data.description);
        formData.append('media_type', data.media_type);
        if (data.media_file) {
            formData.append('media_file', data.media_file);
        } else {
            formData.append('media_url', data.media_url);
        }
        formData.append('cta_text', data.cta_text);
        formData.append('cta_link', data.cta_link);
        formData.append('overlay_opacity', String(data.overlay_opacity));
        formData.append('is_active', data.is_active ? '1' : '0');

        router.post(route('settings.homepage.slides.update', editingSlide.uuid), formData, {
            forceFormData: true,
            preserveState: false,
            onSuccess: () => {
                toast.success('Slide mis à jour avec succès');
                setEditingSlide(null);
                reset();
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour du slide');
            },
        });
    };

    const handleDelete = () => {
        if (!deletingSlide) return;

        router.delete(route('settings.homepage.slides.destroy', deletingSlide.uuid), {
            preserveState: false,
            onSuccess: () => {
                toast.success('Slide supprimé avec succès');
                setDeletingSlide(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression du slide');
            },
        });
    };

    const openEditModal = (slide: HeroSlide) => {
        setData({
            title: slide.title || '',
            description: slide.description || '',
            media_type: slide.media_type,
            media_file: null,
            media_url: slide.media_url,
            cta_text: slide.cta_text || '',
            cta_link: slide.cta_link || '',
            overlay_opacity: slide.overlay_opacity,
            is_active: slide.is_active,
        });
        setEditingSlide(slide);
    };

    const closeModal = () => {
        setShowAddModal(false);
        setEditingSlide(null);
        reset();
    };

    return (
        <DashboardLayout>
            <Head title="Paramètres de la page d'accueil" />

            <div className="py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link
                        href={route('settings.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Retour aux paramètres
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <Home className="h-8 w-8" />
                                Page d'accueil
                            </h1>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">
                                Gérez les images et vidéos du carrousel de la page d'accueil
                            </p>
                        </div>
                        <Button onClick={() => setShowAddModal(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Ajouter un slide
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Slides du carrousel</CardTitle>
                        <CardDescription>
                            Glissez-déposez pour réorganiser les slides. Les slides actifs apparaissent sur la page d'accueil.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {slides.length === 0 ? (
                            <div className="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                <ImageIcon className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Aucun slide
                                </h3>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    Commencez par ajouter des images ou vidéos au carrousel.
                                </p>
                                <Button onClick={() => setShowAddModal(true)}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Ajouter un slide
                                </Button>
                            </div>
                        ) : (
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragEnd={handleDragEnd}
                            >
                                <SortableContext
                                    items={slides.map((s) => s.id)}
                                    strategy={verticalListSortingStrategy}
                                >
                                    <div className="space-y-3">
                                        {slides.map((slide) => (
                                            <SortableSlide
                                                key={slide.id}
                                                slide={slide}
                                                onEdit={openEditModal}
                                                onDelete={setDeletingSlide}
                                                onView={setViewingSlide}
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Global Stats Section */}
            <div className="mt-8">
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <Globe className="h-6 w-6 text-purple-600" />
                                <div>
                                    <CardTitle>Statistiques Mondiales</CardTitle>
                                    <CardDescription>
                                        Modifiez les statistiques affichées dans "Notre Présence Mondiale"
                                    </CardDescription>
                                </div>
                            </div>
                            <Button onClick={handleStatsSave} disabled={statsProcessing}>
                                <Save className="h-4 w-4 mr-2" />
                                {statsProcessing ? 'Enregistrement...' : 'Enregistrer'}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Main Stats */}
                            <div className="space-y-4">
                                <h4 className="font-medium text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    Statistiques principales
                                </h4>
                                <div className="space-y-3">
                                    <div>
                                        <Label htmlFor="total_churches">Nombre d'Églises</Label>
                                        <Input
                                            id="total_churches"
                                            type="number"
                                            min="0"
                                            value={globalStats.total_churches}
                                            onChange={(e) => handleStatsChange('total_churches', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="total_countries">Nombre de Pays</Label>
                                        <Input
                                            id="total_countries"
                                            type="number"
                                            min="0"
                                            value={globalStats.total_countries}
                                            onChange={(e) => handleStatsChange('total_countries', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="total_members">Nombre de Membres</Label>
                                        <Input
                                            id="total_members"
                                            type="number"
                                            min="0"
                                            value={globalStats.total_members}
                                            onChange={(e) => handleStatsChange('total_members', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Regional Stats */}
                            <div className="space-y-4 md:col-span-2">
                                <h4 className="font-medium text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    Par région
                                </h4>
                                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <div>
                                        <Label htmlFor="europe">Europe</Label>
                                        <Input
                                            id="europe"
                                            type="number"
                                            min="0"
                                            value={globalStats.europe}
                                            onChange={(e) => handleStatsChange('europe', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="africa">Afrique</Label>
                                        <Input
                                            id="africa"
                                            type="number"
                                            min="0"
                                            value={globalStats.africa}
                                            onChange={(e) => handleStatsChange('africa', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="americas">Amériques</Label>
                                        <Input
                                            id="americas"
                                            type="number"
                                            min="0"
                                            value={globalStats.americas}
                                            onChange={(e) => handleStatsChange('americas', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="asia">Asie</Label>
                                        <Input
                                            id="asia"
                                            type="number"
                                            min="0"
                                            value={globalStats.asia}
                                            onChange={(e) => handleStatsChange('asia', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="oceania">Océanie</Label>
                                        <Input
                                            id="oceania"
                                            type="number"
                                            min="0"
                                            value={globalStats.oceania}
                                            onChange={(e) => handleStatsChange('oceania', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Church Management Section */}
            <div className="mt-8">
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <MapPin className="h-6 w-6 text-green-600" />
                                <div>
                                    <CardTitle>Gestion des Églises</CardTitle>
                                    <CardDescription>
                                        Gérez les églises affichées sur la carte mondiale
                                    </CardDescription>
                                </div>
                            </div>
                            <Button onClick={() => openChurchModal()}>
                                <Plus className="h-4 w-4 mr-2" />
                                Ajouter une église
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {churches.length === 0 ? (
                            <div className="text-center py-8 text-gray-500">
                                Aucune église enregistrée. Cliquez sur "Ajouter une église" pour commencer.
                            </div>
                        ) : (
                            <div className="space-y-2 max-h-96 overflow-y-auto">
                                {churches.map((church) => (
                                    <div
                                        key={church.id}
                                        className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        onClick={() => setViewingChurch(church)}
                                    >
                                        <div className="flex items-center gap-3">
                                            <MapPin className={`h-4 w-4 ${church.is_active ? 'text-green-600' : 'text-gray-400'}`} />
                                            <div>
                                                <div className="font-medium">{church.name}</div>
                                                <div className="text-sm text-gray-500">
                                                    {church.city}, {church.country} • {church.members} membres
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    openChurchModal(church);
                                                }}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setDeletingChurch(church);
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Church Add/Edit Modal */}
            <Dialog open={showChurchModal} onOpenChange={closeChurchModal}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto px-3 py-3">
                    <DialogHeader>
                        <DialogTitle>
                            {editingChurch ? 'Modifier l\'église' : 'Ajouter une église'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingChurch
                                ? 'Modifiez les informations de l\'église.'
                                : 'Ajoutez une nouvelle église à la carte mondiale.'
                            }
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleChurchSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="church_name">Nom *</Label>
                                <Input
                                    id="church_name"
                                    value={churchForm.name}
                                    onChange={(e) => setChurchForm({ ...churchForm, name: e.target.value })}
                                    placeholder="ICC Paris"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_city">Ville *</Label>
                                <Input
                                    id="church_city"
                                    value={churchForm.city}
                                    onChange={(e) => setChurchForm({ ...churchForm, city: e.target.value })}
                                    placeholder="Paris"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_country">Pays *</Label>
                                <Input
                                    id="church_country"
                                    value={churchForm.country}
                                    onChange={(e) => setChurchForm({ ...churchForm, country: e.target.value })}
                                    placeholder="France"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_members">Nombre de membres</Label>
                                <Input
                                    id="church_members"
                                    type="number"
                                    min="0"
                                    value={churchForm.members}
                                    onChange={(e) => setChurchForm({ ...churchForm, members: e.target.value })}
                                    placeholder="500"
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_latitude">Latitude </Label>
                                <Input
                                    id="church_latitude"
                                    type="number"
                                    step="any"
                                    value={churchForm.latitude}
                                    onChange={(e) => setChurchForm({ ...churchForm, latitude: e.target.value })}
                                    placeholder="48.8566"
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_longitude">Longitude </Label>
                                <Input
                                    id="church_longitude"
                                    type="number"
                                    step="any"
                                    value={churchForm.longitude}
                                    onChange={(e) => setChurchForm({ ...churchForm, longitude: e.target.value })}
                                    placeholder="2.3522"
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="church_address">Adresse</Label>
                            <Input
                                id="church_address"
                                value={churchForm.address}
                                onChange={(e) => setChurchForm({ ...churchForm, address: e.target.value })}
                                placeholder="123 Rue de l'Église, 75001 Paris"
                            />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="church_email">Email</Label>
                                <Input
                                    id="church_email"
                                    type="email"
                                    value={churchForm.email}
                                    onChange={(e) => setChurchForm({ ...churchForm, email: e.target.value })}
                                    placeholder="contact@icc-paris.fr"
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_phone">Téléphone</Label>
                                <Input
                                    id="church_phone"
                                    value={churchForm.phone}
                                    onChange={(e) => setChurchForm({ ...churchForm, phone: e.target.value })}
                                    placeholder="+33 1 23 45 67 89"
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="church_website">Site web</Label>
                            <Input
                                id="church_website"
                                type="url"
                                value={churchForm.website}
                                onChange={(e) => setChurchForm({ ...churchForm, website: e.target.value })}
                                placeholder="https://icc-paris.fr"
                            />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="church_leader_name">Nom du leader</Label>
                                <Input
                                    id="church_leader_name"
                                    value={churchForm.leader_name}
                                    onChange={(e) => setChurchForm({ ...churchForm, leader_name: e.target.value })}
                                    placeholder="Pasteur Jean Dupont"
                                />
                            </div>
                            <div>
                                <Label htmlFor="church_category">Catégorie</Label>
                                <Select
                                    value={churchForm.category}
                                    onValueChange={(value) => setChurchForm({ ...churchForm, category: value })}
                                >
                                    <SelectTrigger id="church_category">
                                        <SelectValue placeholder="Sélectionner une catégorie" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="eglise">Église</SelectItem>
                                        <SelectItem value="campus_connecte">Campus connecté</SelectItem>
                                        <SelectItem value="famille_connecte">Famille connectée</SelectItem>
                                        <SelectItem value="famille_impact">Famille d'impact</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="flex items-center justify-between">
                            <Label htmlFor="church_is_active">Afficher sur la carte</Label>
                            <Switch
                                id="church_is_active"
                                checked={churchForm.is_active}
                                onCheckedChange={(checked) => setChurchForm({ ...churchForm, is_active: checked })}
                            />
                        </div>
                        <div className="flex justify-end gap-2 pt-4">
                            <Button type="button" variant="outline" onClick={closeChurchModal}>
                                Annuler
                            </Button>
                            <Button type="submit" disabled={churchProcessing}>
                                {churchProcessing ? 'Enregistrement...' : (editingChurch ? 'Mettre à jour' : 'Ajouter')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Church Delete Confirmation */}
            <AlertDialog open={!!deletingChurch} onOpenChange={() => setDeletingChurch(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer cette église ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Voulez-vous vraiment supprimer "{deletingChurch?.name}" ? Cette action est irréversible.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction onClick={handleChurchDelete} className="bg-red-600 hover:bg-red-700">
                            Supprimer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Church View Details Modal */}
            <Dialog open={!!viewingChurch} onOpenChange={() => setViewingChurch(null)}>
                <DialogContent className="max-w-lg px-3 py-2">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <MapPin className={`h-5 w-5 ${viewingChurch?.is_active ? 'text-green-600' : 'text-gray-400'}`} />
                            {viewingChurch?.name}
                        </DialogTitle>
                        <DialogDescription>
                            Détails de l'église
                        </DialogDescription>
                    </DialogHeader>
                    {viewingChurch && (
                        <div className="space-y-4">
                            {/* Location */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Ville</Label>
                                    <p className="font-medium">{viewingChurch.city}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Pays</Label>
                                    <p className="font-medium">{viewingChurch.country}</p>
                                </div>
                            </div>

                            {/* Members & Status */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Membres</Label>
                                    <p className="font-medium">{viewingChurch.members || 0}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Statut</Label>
                                    <Badge variant={viewingChurch.is_active ? 'default' : 'secondary'} className={viewingChurch.is_active ? 'bg-green-500' : ''}>
                                        {viewingChurch.is_active ? 'Actif' : 'Inactif'}
                                    </Badge>
                                </div>
                            </div>

                            {/* Leader & Category */}
                            <div className="grid grid-cols-2 gap-4">
                                {viewingChurch.leader_name && (
                                    <div>
                                        <Label className="text-xs text-gray-500 uppercase tracking-wide">Leader</Label>
                                        <p className="font-medium">{viewingChurch.leader_name}</p>
                                    </div>
                                )}
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Catégorie</Label>
                                    <Badge variant="outline">
                                        {{
                                            'eglise': 'Église',
                                            'campus_connecte': 'Campus connecté',
                                            'famille_connecte': 'Famille connectée',
                                            'famille_impact': 'Famille d\'impact',
                                        }[viewingChurch.category || 'eglise'] || 'Église'}
                                    </Badge>
                                </div>
                            </div>

                            {/* Address */}
                            {viewingChurch.address && (
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Adresse</Label>
                                    <p className="font-medium">{viewingChurch.address}</p>
                                </div>
                            )}

                            {/* Coordinates */}
                            {(viewingChurch.latitude || viewingChurch.longitude) && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-xs text-gray-500 uppercase tracking-wide">Latitude</Label>
                                        <p className="font-medium font-mono text-sm">{viewingChurch.latitude || '-'}</p>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-gray-500 uppercase tracking-wide">Longitude</Label>
                                        <p className="font-medium font-mono text-sm">{viewingChurch.longitude || '-'}</p>
                                    </div>
                                </div>
                            )}

                            {/* Continent */}
                            {viewingChurch.continent && (
                                <div>
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide">Continent</Label>
                                    <p className="font-medium capitalize">{viewingChurch.continent}</p>
                                </div>
                            )}

                            {/* Contact Information */}
                            {(viewingChurch.email || viewingChurch.phone || viewingChurch.website) && (
                                <div className="border-t pt-4">
                                    <Label className="text-xs text-gray-500 uppercase tracking-wide mb-2 block">Contact</Label>
                                    <div className="space-y-2">
                                        {viewingChurch.email && (
                                            <p className="text-sm">
                                                <span className="text-gray-500">Email:</span>{' '}
                                                <a href={`mailto:${viewingChurch.email}`} className="text-blue-600 hover:underline">
                                                    {viewingChurch.email}
                                                </a>
                                            </p>
                                        )}
                                        {viewingChurch.phone && (
                                            <p className="text-sm">
                                                <span className="text-gray-500">Téléphone:</span>{' '}
                                                <a href={`tel:${viewingChurch.phone}`} className="text-blue-600 hover:underline">
                                                    {viewingChurch.phone}
                                                </a>
                                            </p>
                                        )}
                                        {viewingChurch.website && (
                                            <p className="text-sm">
                                                <span className="text-gray-500">Site web:</span>{' '}
                                                <a href={viewingChurch.website} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                                    {viewingChurch.website}
                                                </a>
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter className="flex gap-2">
                        <Button variant="outline" onClick={() => setViewingChurch(null)}>
                            Fermer
                        </Button>
                        <Button onClick={() => {
                            if (viewingChurch) {
                                openChurchModal(viewingChurch);
                                setViewingChurch(null);
                            }
                        }}>
                            <Pencil className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Add/Edit Modal */}
            <Dialog open={showAddModal || !!editingSlide} onOpenChange={closeModal}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingSlide ? 'Modifier le slide' : 'Ajouter un slide'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingSlide
                                ? 'Modifiez les informations du slide.'
                                : 'Ajoutez une nouvelle image ou vidéo au carrousel.'
                            }
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={editingSlide ? handleEditSubmit : handleAddSubmit}>
                        <div className="space-y-4 py-4 px-3">
                            {/* Media Type */}
                            <div className="space-y-2">
                                <Label>Type de média</Label>
                                <Select
                                    value={data.media_type}
                                    onValueChange={(value: string) => setData('media_type', value as 'image' | 'video')}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="image">
                                            <div className="flex items-center">
                                                <ImageIcon className="h-4 w-4 mr-2" />
                                                Image
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="video">
                                            <div className="flex items-center">
                                                <Video className="h-4 w-4 mr-2" />
                                                Vidéo
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Media File Upload */}
                            <div className="space-y-2">
                                <Label htmlFor="media_file">Fichier média</Label>
                                <Input
                                    id="media_file"
                                    type="file"
                                    accept={data.media_type === 'video' ? 'video/*' : 'image/*'}
                                    onChange={(e) => {
                                        const file = e.target.files?.[0] || null;
                                        setData('media_file', file);
                                    }}
                                />
                                {errors.media_file && (
                                    <p className="text-sm text-destructive">{errors.media_file}</p>
                                )}
                                {editingSlide && !data.media_file && (
                                    <p className="text-sm text-gray-500">
                                        Média actuel: {editingSlide.media_url.split('/').pop()}
                                    </p>
                                )}
                            </div>

                            {/* Or URL */}
                            <div className="space-y-2">
                                <Label htmlFor="media_url">Ou URL du média</Label>
                                <Input
                                    id="media_url"
                                    type="text"
                                    placeholder="https://..."
                                    value={data.media_url}
                                    onChange={(e) => setData('media_url', e.target.value)}
                                    disabled={!!data.media_file}
                                />
                                {errors.media_url && (
                                    <p className="text-sm text-destructive">{errors.media_url}</p>
                                )}
                            </div>

                            {/* Title */}
                            <div className="space-y-2">
                                <Label htmlFor="title">Titre (optionnel)</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Titre du slide"
                                />
                                {errors.title && (
                                    <p className="text-sm text-destructive">{errors.title}</p>
                                )}
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Description (optionnel)</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Description du slide"
                                    rows={3}
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">{errors.description}</p>
                                )}
                            </div>

                            {/* CTA */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="cta_text">Texte du bouton</Label>
                                    <Input
                                        id="cta_text"
                                        type="text"
                                        value={data.cta_text}
                                        onChange={(e) => setData('cta_text', e.target.value)}
                                        placeholder="Ex: En savoir plus"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="cta_link">Lien du bouton</Label>
                                    <Input
                                        id="cta_link"
                                        type="text"
                                        value={data.cta_link}
                                        onChange={(e) => setData('cta_link', e.target.value)}
                                        placeholder="Ex: /contact"
                                    />
                                </div>
                            </div>

                            {/* Overlay Opacity */}
                            <div className="space-y-2">
                                <Label>Opacité de l'overlay: {Math.round(data.overlay_opacity * 100)}%</Label>
                                <Slider
                                    value={[data.overlay_opacity * 100]}
                                    onValueChange={(values: number[]) => setData('overlay_opacity', values[0] / 100)}
                                    min={0}
                                    max={100}
                                    step={5}
                                />
                            </div>

                            {/* Active Toggle */}
                            <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                                <div>
                                    <Label className="text-base font-medium">Actif</Label>
                                    <p className="text-sm text-gray-500">
                                        Le slide apparaît sur la page d'accueil
                                    </p>
                                </div>
                                <Switch
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked)}
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeModal}>
                                Annuler
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Enregistrement...' : editingSlide ? 'Mettre à jour' : 'Ajouter'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={!!deletingSlide} onOpenChange={() => setDeletingSlide(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer le slide ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Cette action est irréversible. Le slide sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Supprimer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Details View Dialog */}
            <Dialog open={!!viewingSlide} onOpenChange={() => setViewingSlide(null)}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Détails du slide</DialogTitle>
                        <DialogDescription>
                            Aperçu du slide et de ses informations.
                        </DialogDescription>
                    </DialogHeader>

                    {viewingSlide && (
                        <div className="space-y-6 py-4 px-3">
                            {/* Media Preview */}
                            <div className="aspect-video rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                {viewingSlide.media_type === 'video' ? (
                                    <video
                                        src={viewingSlide.media_url}
                                        className="w-full h-full object-cover"
                                        controls
                                    />
                                ) : (
                                    <img
                                        src={viewingSlide.media_url}
                                        alt={viewingSlide.title || 'Slide'}
                                        className="w-full h-full object-cover"
                                    />
                                )}
                            </div>

                            {/* Slide Info */}
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Badge variant={viewingSlide.media_type === 'video' ? 'secondary' : 'outline'}>
                                        {viewingSlide.media_type === 'video' ? (
                                            <><Video className="h-3 w-3 mr-1" /> Vidéo</>
                                        ) : (
                                            <><ImageIcon className="h-3 w-3 mr-1" /> Image</>
                                        )}
                                    </Badge>
                                    {viewingSlide.is_active ? (
                                        <Badge variant="default" className="bg-green-500">
                                            <Eye className="h-3 w-3 mr-1" /> Actif
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary">
                                            <EyeOff className="h-3 w-3 mr-1" /> Inactif
                                        </Badge>
                                    )}
                                    <Badge variant="outline" className="ml-auto">
                                        Position #{viewingSlide.order}
                                    </Badge>
                                </div>

                                <div>
                                    <h3 className="text-lg font-semibold">
                                        {viewingSlide.title || 'Sans titre'}
                                    </h3>
                                    <p className="text-gray-500 dark:text-gray-400 mt-1">
                                        {viewingSlide.description || 'Pas de description'}
                                    </p>
                                </div>

                                {(viewingSlide.cta_text || viewingSlide.cta_link) && (
                                    <div className="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Bouton d'action</p>
                                        <p className="font-medium">{viewingSlide.cta_text || '-'}</p>
                                        {viewingSlide.cta_link && (
                                            <p className="text-sm text-primary">{viewingSlide.cta_link}</p>
                                        )}
                                    </div>
                                )}

                                <div className="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Opacité de l'overlay: {Math.round((viewingSlide.overlay_opacity || 0.5) * 100)}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setViewingSlide(null)}>
                            Fermer
                        </Button>
                        <Button onClick={() => {
                            if (viewingSlide) {
                                openEditModal(viewingSlide);
                                setViewingSlide(null);
                            }
                        }}>
                            <Pencil className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}
