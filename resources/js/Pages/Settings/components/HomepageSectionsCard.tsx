import { useState } from 'react';
import { router } from '@inertiajs/react';
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
import { GripVertical, Pencil, Trash2, Plus, Layout, Eye, EyeOff } from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
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
import HomepageSectionEditorDialog, {
    type HomepageSection,
} from './HomepageSectionEditorDialog';

const TYPE_LABELS: Record<HomepageSection['type'], string> = {
    about: 'À propos',
    activities: 'Activités',
    training: 'Formations',
    contact: 'Contact',
    custom: 'Personnalisée',
};

const TYPE_VARIANTS: Record<HomepageSection['type'], 'default' | 'secondary' | 'outline'> = {
    about: 'default',
    activities: 'secondary',
    training: 'secondary',
    contact: 'outline',
    custom: 'outline',
};

interface SortableSectionRowProps {
    section: HomepageSection;
    onEdit: (s: HomepageSection) => void;
    onDelete: (s: HomepageSection) => void;
    onToggle: (s: HomepageSection) => void;
}

function SortableSectionRow({ section, onEdit, onDelete, onToggle }: SortableSectionRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: section.id,
    });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };
    const subCount = section.subsections?.length ?? 0;

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border ${
                isDragging ? 'border-primary shadow-lg' : 'border-gray-200 dark:border-gray-700'
            }`}
        >
            <button
                {...attributes}
                {...listeners}
                className="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                aria-label="Réorganiser"
            >
                <GripVertical className="h-5 w-5" />
            </button>

            <div className="flex-1 min-w-0 flex items-center gap-3">
                <Badge variant={TYPE_VARIANTS[section.type]}>{TYPE_LABELS[section.type]}</Badge>
                <div className="min-w-0">
                    <div className="font-medium truncate">
                        {section.title || section.content?.heading || section.key}
                    </div>
                    {section.type === 'custom' && (
                        <div className="text-xs text-muted-foreground">
                            {subCount} bloc{subCount > 1 ? 's' : ''}
                        </div>
                    )}
                </div>
            </div>

            <Button
                variant="ghost"
                size="icon"
                onClick={() => onToggle(section)}
                aria-label={section.is_active ? 'Désactiver' : 'Activer'}
                title={section.is_active ? 'Désactiver' : 'Activer'}
            >
                {section.is_active ? (
                    <Eye className="h-4 w-4 text-green-600" />
                ) : (
                    <EyeOff className="h-4 w-4 text-gray-400" />
                )}
            </Button>
            <Button variant="ghost" size="icon" onClick={() => onEdit(section)} aria-label="Éditer">
                <Pencil className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon" onClick={() => onDelete(section)} aria-label="Supprimer">
                <Trash2 className="h-4 w-4 text-destructive" />
            </Button>
        </div>
    );
}

interface HomepageSectionsCardProps {
    sections: HomepageSection[];
}

const TYPE_OPTIONS: HomepageSection['type'][] = ['about', 'activities', 'training', 'contact', 'custom'];

export default function HomepageSectionsCard({ sections: initialSections }: HomepageSectionsCardProps) {
    const [sections, setSections] = useState<HomepageSection[]>(initialSections);
    const [editing, setEditing] = useState<HomepageSection | null>(null);
    const [deleting, setDeleting] = useState<HomepageSection | null>(null);
    const [addOpen, setAddOpen] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) {
            return;
        }
        const oldIndex = sections.findIndex((s) => s.id === active.id);
        const newIndex = sections.findIndex((s) => s.id === over.id);
        const next = arrayMove(sections, oldIndex, newIndex);
        setSections(next);
        router.post(
            route('settings.homepage.sections.reorder'),
            {
                sections: next.map((s, i) => ({ id: s.id, order: i + 1 })),
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Ordre mis à jour.'),
                onError: () => toast.error('Échec de la mise à jour.'),
            }
        );
    };

    const handleToggle = (section: HomepageSection) => {
        router.put(
            route('settings.homepage.sections.update', section.uuid),
            {
                type: section.type,
                is_active: !section.is_active,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSections((prev) =>
                        prev.map((s) => (s.id === section.id ? { ...s, is_active: !s.is_active } : s))
                    );
                    toast.success(section.is_active ? 'Section désactivée.' : 'Section activée.');
                },
                onError: () => toast.error('Échec de la mise à jour.'),
            }
        );
    };

    const handleDelete = () => {
        if (!deleting) {
            return;
        }
        router.delete(route('settings.homepage.sections.destroy', deleting.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setSections((prev) => prev.filter((s) => s.id !== deleting.id));
                toast.success('Section supprimée.');
                setDeleting(null);
            },
            onError: () => {
                toast.error('Échec de la suppression.');
                setDeleting(null);
            },
        });
    };

    const handleAdd = (type: HomepageSection['type']) => {
        router.post(
            route('settings.homepage.sections.store'),
            {
                type,
                title: TYPE_LABELS[type],
                content: { badge: TYPE_LABELS[type], heading: TYPE_LABELS[type] },
                design_settings: {},
                is_active: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Section ajoutée.');
                    setAddOpen(false);
                },
                onError: () => toast.error('Échec de la création.'),
            }
        );
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <Layout className="h-6 w-6 text-primary" />
                            <div>
                                <CardTitle>Sections de la page d'accueil</CardTitle>
                                <CardDescription>
                                    Activez, réorganisez, ajoutez ou supprimez les sections du corps de la page d'accueil.
                                </CardDescription>
                            </div>
                        </div>
                        <Button onClick={() => setAddOpen(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Ajouter
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    {sections.length === 0 ? (
                        <p className="text-sm text-muted-foreground text-center py-8">
                            Aucune section configurée. La page d'accueil affiche les sections par défaut.
                        </p>
                    ) : (
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={sections.map((s) => s.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-3">
                                    {sections.map((s) => (
                                        <SortableSectionRow
                                            key={s.id}
                                            section={s}
                                            onEdit={setEditing}
                                            onDelete={setDeleting}
                                            onToggle={handleToggle}
                                        />
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>
                    )}
                </CardContent>
            </Card>

            {editing && (
                <HomepageSectionEditorDialog
                    section={editing}
                    open={!!editing}
                    onOpenChange={(open) => !open && setEditing(null)}
                    onSaved={(updated) => {
                        setSections((prev) => prev.map((s) => (s.id === updated.id ? updated : s)));
                        setEditing(null);
                    }}
                />
            )}

            <Dialog open={addOpen} onOpenChange={setAddOpen}>
                <DialogContent className="px-6 py-5">
                    <DialogHeader>
                        <DialogTitle>Ajouter une section</DialogTitle>
                        <DialogDescription>Choisissez un type de section à ajouter à la page d'accueil.</DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-3 my-4">
                        {TYPE_OPTIONS.map((type) => (
                            <Button
                                key={type}
                                variant="outline"
                                className="h-auto py-4 flex flex-col items-start text-left"
                                onClick={() => handleAdd(type)}
                            >
                                <span className="font-semibold">{TYPE_LABELS[type]}</span>
                                <span className="text-xs text-muted-foreground mt-1">
                                    {type === 'custom' ? 'Section personnalisée avec blocs' : 'Template pré-défini'}
                                </span>
                            </Button>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>

            <AlertDialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer cette section ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            {deleting?.type === 'custom' && (deleting?.subsections?.length ?? 0) > 0
                                ? `Cette action supprimera également ${deleting?.subsections?.length} bloc(s) associé(s). Cette action est irréversible.`
                                : 'Cette action est irréversible.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Supprimer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
