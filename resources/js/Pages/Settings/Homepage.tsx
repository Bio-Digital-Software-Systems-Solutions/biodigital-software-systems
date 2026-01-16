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
}

interface SortableSlideProps {
    slide: HeroSlide;
    onEdit: (slide: HeroSlide) => void;
    onDelete: (slide: HeroSlide) => void;
}

function SortableSlide({ slide, onEdit, onDelete }: SortableSlideProps) {
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

export default function Homepage({ auth, slides: initialSlides }: Props) {
    const [slides, setSlides] = useState<HeroSlide[]>(initialSlides);
    const [showAddModal, setShowAddModal] = useState(false);
    const [editingSlide, setEditingSlide] = useState<HeroSlide | null>(null);
    const [deletingSlide, setDeletingSlide] = useState<HeroSlide | null>(null);

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

        router.post(route('settings.homepage.slides.update', editingSlide.id), formData, {
            forceFormData: true,
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

        router.delete(route('settings.homepage.slides.destroy', deletingSlide.id), {
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
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>
                        )}
                    </CardContent>
                </Card>
            </div>

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
        </DashboardLayout>
    );
}
