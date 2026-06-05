import { useState } from 'react';
import { router } from '@inertiajs/react';
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
import { GripVertical, Pencil, Trash2, Plus } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import type { DesignSettings } from '@/lib/sectionDesign';

export interface HomepageSubsection {
    id: number;
    uuid: string;
    homepage_section_id: number;
    block_type: 'heading' | 'paragraph' | 'image' | 'button' | 'card';
    content: Record<string, any>;
    design_settings: DesignSettings | null;
    order: number;
    is_active: boolean;
}

const BLOCK_LABELS: Record<HomepageSubsection['block_type'], string> = {
    heading: 'Titre',
    paragraph: 'Paragraphe',
    image: 'Image',
    button: 'Bouton',
    card: 'Carte',
};

interface SortableBlockProps {
    block: HomepageSubsection;
    onEdit: (b: HomepageSubsection) => void;
    onDelete: (b: HomepageSubsection) => void;
}

function SortableBlock({ block, onEdit, onDelete }: SortableBlockProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: block.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const preview =
        block.block_type === 'heading' || block.block_type === 'paragraph'
            ? block.content.text
            : block.block_type === 'image'
                ? block.content.url
                : block.block_type === 'button'
                    ? block.content.label
                    : block.content.title;

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded border"
        >
            <button {...attributes} {...listeners} className="cursor-grab active:cursor-grabbing p-1 text-gray-400">
                <GripVertical className="h-4 w-4" />
            </button>
            <Badge variant="outline">{BLOCK_LABELS[block.block_type]}</Badge>
            <span className="flex-1 min-w-0 text-sm truncate text-muted-foreground">{preview || '—'}</span>
            <Button variant="ghost" size="icon" onClick={() => onEdit(block)} aria-label="Éditer">
                <Pencil className="h-3.5 w-3.5" />
            </Button>
            <Button variant="ghost" size="icon" onClick={() => onDelete(block)} aria-label="Supprimer">
                <Trash2 className="h-3.5 w-3.5 text-destructive" />
            </Button>
        </div>
    );
}

interface Props {
    section: { id: number; uuid: string; subsections?: HomepageSubsection[] };
}

export default function HomepageSubsectionsEditor({ section }: Props) {
    const [blocks, setBlocks] = useState<HomepageSubsection[]>(section.subsections ?? []);
    const [editing, setEditing] = useState<HomepageSubsection | null>(null);
    const [creatingType, setCreatingType] = useState<HomepageSubsection['block_type'] | null>(null);
    const [addMenuOpen, setAddMenuOpen] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) {
            return;
        }
        const oldIndex = blocks.findIndex((b) => b.id === active.id);
        const newIndex = blocks.findIndex((b) => b.id === over.id);
        const next = arrayMove(blocks, oldIndex, newIndex);
        setBlocks(next);
        router.post(
            route('settings.homepage.sections.subsections.reorder', section.uuid),
            { subsections: next.map((b, i) => ({ id: b.id, order: i + 1 })) },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Ordre des blocs mis à jour.'),
                onError: () => toast.error('Échec de la réorganisation.'),
            }
        );
    };

    const handleDelete = (block: HomepageSubsection) => {
        router.delete(route('settings.homepage.sections.subsections.destroy', [section.uuid, block.uuid]), {
            preserveScroll: true,
            onSuccess: () => {
                setBlocks((prev) => prev.filter((b) => b.id !== block.id));
                toast.success('Bloc supprimé.');
            },
            onError: () => toast.error('Échec de la suppression.'),
        });
    };

    const handleCreate = (type: HomepageSubsection['block_type']) => {
        setCreatingType(type);
        setAddMenuOpen(false);
        setEditing({
            id: 0,
            uuid: '',
            homepage_section_id: section.id,
            block_type: type,
            content: defaultContent(type),
            design_settings: {},
            order: blocks.length + 1,
            is_active: true,
        });
    };

    const handleSave = (updated: HomepageSubsection) => {
        if (updated.id === 0) {
            router.post(
                route('settings.homepage.sections.subsections.store', section.uuid),
                {
                    block_type: updated.block_type,
                    content: updated.content,
                    design_settings: updated.design_settings,
                    is_active: updated.is_active,
                },
                {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        const sections = (page.props as any).sections as Array<{ id: number; subsections?: HomepageSubsection[] }>;
                        const refreshed = sections?.find((s) => s.id === section.id)?.subsections ?? [];
                        setBlocks(refreshed);
                        toast.success('Bloc ajouté.');
                        setEditing(null);
                        setCreatingType(null);
                    },
                    onError: () => toast.error('Échec de la création.'),
                }
            );
        } else {
            router.put(
                route('settings.homepage.sections.subsections.update', [section.uuid, updated.uuid]),
                {
                    block_type: updated.block_type,
                    content: updated.content,
                    design_settings: updated.design_settings,
                    is_active: updated.is_active,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setBlocks((prev) => prev.map((b) => (b.id === updated.id ? updated : b)));
                        toast.success('Bloc mis à jour.');
                        setEditing(null);
                    },
                    onError: () => toast.error('Échec de la sauvegarde.'),
                }
            );
        }
    };

    return (
        <div className="border-t pt-4 space-y-3">
            <div className="flex items-center justify-between">
                <Label>Blocs de la section</Label>
                <Button type="button" size="sm" variant="outline" onClick={() => setAddMenuOpen(true)}>
                    <Plus className="h-3 w-3 mr-1" />
                    Ajouter un bloc
                </Button>
            </div>

            {blocks.length === 0 ? (
                <p className="text-sm text-muted-foreground py-4 text-center border rounded">
                    Aucun bloc. Cliquez sur "Ajouter un bloc" pour commencer.
                </p>
            ) : (
                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                    <SortableContext items={blocks.map((b) => b.id)} strategy={verticalListSortingStrategy}>
                        <div className="space-y-2">
                            {blocks.map((b) => (
                                <SortableBlock key={b.id} block={b} onEdit={setEditing} onDelete={handleDelete} />
                            ))}
                        </div>
                    </SortableContext>
                </DndContext>
            )}

            <Dialog open={addMenuOpen} onOpenChange={setAddMenuOpen}>
                <DialogContent className="px-6 py-5">
                    <DialogHeader>
                        <DialogTitle>Ajouter un bloc</DialogTitle>
                        <DialogDescription>Choisissez le type de bloc à ajouter.</DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-2">
                        {(Object.keys(BLOCK_LABELS) as HomepageSubsection['block_type'][]).map((type) => (
                            <Button
                                key={type}
                                variant="outline"
                                onClick={() => handleCreate(type)}
                            >
                                {BLOCK_LABELS[type]}
                            </Button>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>

            {editing && (
                <SubsectionEditorDialog
                    block={editing}
                    onOpenChange={(o) => {
                        if (!o) {
                            setEditing(null);
                            setCreatingType(null);
                        }
                    }}
                    onSave={handleSave}
                />
            )}
        </div>
    );
}

function defaultContent(type: HomepageSubsection['block_type']): Record<string, any> {
    switch (type) {
        case 'heading':
            return { text: '', level: 2 };
        case 'paragraph':
            return { text: '' };
        case 'image':
            return { url: '', alt: '' };
        case 'button':
            return { label: '', href: '', variant: 'default' };
        case 'card':
            return { title: '', body: '' };
    }
}

interface SubsectionEditorDialogProps {
    block: HomepageSubsection;
    onOpenChange: (open: boolean) => void;
    onSave: (b: HomepageSubsection) => void;
}

function SubsectionEditorDialog({ block, onOpenChange, onSave }: SubsectionEditorDialogProps) {
    const [content, setContent] = useState<Record<string, any>>(block.content);

    const update = (key: string, value: unknown) => setContent((p) => ({ ...p, [key]: value }));

    return (
        <Dialog open onOpenChange={onOpenChange}>
            <DialogContent className="max-w-xl px-6 py-5">
                <DialogHeader>
                    <DialogTitle>{block.id === 0 ? 'Nouveau bloc' : 'Éditer le bloc'} — {BLOCK_LABELS[block.block_type]}</DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    {(block.block_type === 'heading' || block.block_type === 'paragraph') && (
                        <div className="space-y-2">
                            <Label>Texte</Label>
                            <Textarea value={content.text ?? ''} onChange={(e) => update('text', e.target.value)} />
                            {block.block_type === 'heading' && (
                                <>
                                    <Label>Niveau</Label>
                                    <Select
                                        value={String(content.level ?? 2)}
                                        onValueChange={(v) => update('level', Number(v))}
                                    >
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">H1</SelectItem>
                                            <SelectItem value="2">H2</SelectItem>
                                            <SelectItem value="3">H3</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </>
                            )}
                        </div>
                    )}

                    {block.block_type === 'image' && (
                        <>
                            <div className="space-y-2">
                                <Label>URL de l'image</Label>
                                <Input value={content.url ?? ''} onChange={(e) => update('url', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Texte alternatif</Label>
                                <Input value={content.alt ?? ''} onChange={(e) => update('alt', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Légende</Label>
                                <Input value={content.caption ?? ''} onChange={(e) => update('caption', e.target.value)} />
                            </div>
                        </>
                    )}

                    {block.block_type === 'button' && (
                        <>
                            <div className="space-y-2">
                                <Label>Libellé</Label>
                                <Input value={content.label ?? ''} onChange={(e) => update('label', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Lien (href)</Label>
                                <Input value={content.href ?? ''} onChange={(e) => update('href', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Variante</Label>
                                <Select value={content.variant ?? 'default'} onValueChange={(v) => update('variant', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="default">Default</SelectItem>
                                        <SelectItem value="outline">Outline</SelectItem>
                                        <SelectItem value="ghost">Ghost</SelectItem>
                                        <SelectItem value="secondary">Secondary</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
                    )}

                    {block.block_type === 'card' && (
                        <>
                            <div className="space-y-2">
                                <Label>Titre</Label>
                                <Input value={content.title ?? ''} onChange={(e) => update('title', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Contenu</Label>
                                <Textarea value={content.body ?? ''} onChange={(e) => update('body', e.target.value)} />
                            </div>
                        </>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Annuler
                    </Button>
                    <Button onClick={() => onSave({ ...block, content })}>
                        Sauvegarder
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
