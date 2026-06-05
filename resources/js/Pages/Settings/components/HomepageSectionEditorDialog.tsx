import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Slider } from '@/Components/ui/slider';
import { Switch } from '@/Components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Plus, Trash2 } from 'lucide-react';
import {
    type DesignSettings,
    FONT_FAMILY_OPTIONS,
    HEADING_SIZE_OPTIONS,
    PARAGRAPH_SIZE_OPTIONS,
    ALIGNMENT_OPTIONS,
    LAYOUT_OPTIONS,
    SPACING_OPTIONS,
    ANIMATION_OPTIONS,
    DARK_MODE_OPTIONS,
} from '@/lib/sectionDesign';
import HomepageSubsectionsEditor, { type HomepageSubsection } from './HomepageSubsectionsEditor';

export interface HomepageSection {
    id: number;
    uuid: string;
    key: string;
    type: 'about' | 'activities' | 'training' | 'contact' | 'custom';
    title: string | null;
    content: Record<string, any> | null;
    design_settings: DesignSettings | null;
    order: number;
    is_active: boolean;
    subsections?: HomepageSubsection[];
}

interface Props {
    section: HomepageSection;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSaved: (section: HomepageSection) => void;
}

export default function HomepageSectionEditorDialog({ section, open, onOpenChange, onSaved }: Props) {
    const [title, setTitle] = useState(section.title ?? '');
    const [isActive, setIsActive] = useState(section.is_active);
    const [content, setContent] = useState<Record<string, any>>(section.content ?? {});
    const [design, setDesign] = useState<DesignSettings>(section.design_settings ?? {});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setTitle(section.title ?? '');
        setIsActive(section.is_active);
        setContent(section.content ?? {});
        setDesign(section.design_settings ?? {});
    }, [section]);

    const updateContent = (key: string, value: unknown) => {
        setContent((prev) => ({ ...prev, [key]: value }));
    };

    const updateDesign = <K extends keyof DesignSettings>(key: K, value: DesignSettings[K] | undefined) => {
        setDesign((prev) => {
            const next = { ...prev };
            if (value === undefined || value === '') {
                delete next[key];
            } else {
                next[key] = value;
            }
            return next;
        });
    };

    const handleSave = () => {
        setSaving(true);
        router.put(
            route('settings.homepage.sections.update', section.uuid),
            {
                type: section.type,
                title,
                is_active: isActive,
                content,
                design_settings: design,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Section mise à jour.');
                    onSaved({ ...section, title, is_active: isActive, content, design_settings: design });
                },
                onError: () => toast.error('Échec de la sauvegarde.'),
                onFinish: () => setSaving(false),
            }
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto px-6 py-5">
                <DialogHeader>
                    <DialogTitle>Éditer la section</DialogTitle>
                    <DialogDescription>
                        Modifiez le contenu et le design de cette section. Les changements s'appliquent immédiatement après sauvegarde.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Titre admin</Label>
                            <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Titre interne" />
                        </div>
                        <div className="flex items-center gap-3 pt-6">
                            <Switch checked={isActive} onCheckedChange={setIsActive} id="is-active" />
                            <Label htmlFor="is-active">Section active</Label>
                        </div>
                    </div>

                    <Tabs defaultValue="content">
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="content">Contenu</TabsTrigger>
                            <TabsTrigger value="design">Design</TabsTrigger>
                        </TabsList>

                        <TabsContent value="content" className="space-y-4 mt-4">
                            <ContentEditor section={section} content={content} updateContent={updateContent} setContent={setContent} />
                        </TabsContent>

                        <TabsContent value="design" className="space-y-4 mt-4">
                            <DesignEditor design={design} updateDesign={updateDesign} />
                        </TabsContent>
                    </Tabs>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Annuler
                    </Button>
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? 'Sauvegarde…' : 'Sauvegarder'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface ContentEditorProps {
    section: HomepageSection;
    content: Record<string, any>;
    updateContent: (key: string, value: unknown) => void;
    setContent: (c: Record<string, any>) => void;
}

function ContentEditor({ section, content, updateContent, setContent }: ContentEditorProps) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                    <Label>Badge</Label>
                    <Input value={content.badge ?? ''} onChange={(e) => updateContent('badge', e.target.value)} />
                </div>
                <div className="space-y-2">
                    <Label>Titre principal</Label>
                    <Input value={content.heading ?? ''} onChange={(e) => updateContent('heading', e.target.value)} />
                </div>
            </div>

            <div className="space-y-2">
                <Label>Sous-titre / description</Label>
                <Textarea value={content.subtitle ?? ''} onChange={(e) => updateContent('subtitle', e.target.value)} />
            </div>

            {section.type === 'about' && (
                <AboutContentEditor content={content} updateContent={updateContent} setContent={setContent} />
            )}

            {section.type === 'activities' && (
                <ActivitiesContentEditor content={content} setContent={setContent} />
            )}

            {section.type === 'contact' && (
                <div className="grid grid-cols-1 gap-3">
                    <div className="space-y-2">
                        <Label>Email</Label>
                        <Input
                            type="email"
                            value={content.email ?? ''}
                            onChange={(e) => updateContent('email', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Téléphone</Label>
                        <Input value={content.phone ?? ''} onChange={(e) => updateContent('phone', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Adresse</Label>
                        <Textarea
                            value={content.address ?? ''}
                            onChange={(e) => updateContent('address', e.target.value)}
                        />
                    </div>
                </div>
            )}

            {section.type === 'custom' && <HomepageSubsectionsEditor section={section} />}
        </div>
    );
}

function AboutContentEditor({ content, updateContent, setContent }: {
    content: Record<string, any>;
    updateContent: (k: string, v: unknown) => void;
    setContent: (c: Record<string, any>) => void;
}) {
    const paragraphs: string[] = content.paragraphs ?? [];

    const updateParagraph = (index: number, value: string) => {
        const next = [...paragraphs];
        next[index] = value;
        updateContent('paragraphs', next);
    };

    const addParagraph = () => {
        updateContent('paragraphs', [...paragraphs, '']);
    };

    const removeParagraph = (index: number) => {
        updateContent('paragraphs', paragraphs.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-4 border-t pt-4">
            <div className="space-y-2">
                <Label>URL de l'image principale</Label>
                <Input value={content.image_url ?? ''} onChange={(e) => updateContent('image_url', e.target.value)} />
            </div>
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label>Paragraphes</Label>
                    <Button type="button" size="sm" variant="outline" onClick={addParagraph}>
                        <Plus className="h-3 w-3 mr-1" />
                        Ajouter
                    </Button>
                </div>
                {paragraphs.map((p, i) => (
                    <div key={i} className="flex gap-2">
                        <Textarea value={p} onChange={(e) => updateParagraph(i, e.target.value)} className="flex-1" />
                        <Button type="button" size="icon" variant="ghost" onClick={() => removeParagraph(i)}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                ))}
            </div>
        </div>
    );
}

interface ActivityItem {
    icon: string;
    iconColor?: string;
    title: string;
    description: string;
}

function ActivitiesContentEditor({ content, setContent }: {
    content: Record<string, any>;
    setContent: (c: Record<string, any>) => void;
}) {
    const items: ActivityItem[] = content.items ?? [];

    const update = (next: ActivityItem[]) => {
        setContent({ ...content, items: next });
    };

    const addItem = () => {
        update([...items, { icon: 'CalendarDaysIcon', iconColor: 'bg-icc-blue/10 text-icc-blue', title: '', description: '' }]);
    };

    const updateItem = (index: number, key: keyof ActivityItem, value: string) => {
        const next = [...items];
        next[index] = { ...next[index], [key]: value };
        update(next);
    };

    const removeItem = (index: number) => update(items.filter((_, i) => i !== index));

    return (
        <div className="space-y-3 border-t pt-4">
            <div className="flex items-center justify-between">
                <Label>Activités</Label>
                <Button type="button" size="sm" variant="outline" onClick={addItem}>
                    <Plus className="h-3 w-3 mr-1" />
                    Ajouter
                </Button>
            </div>
            {items.map((item, i) => (
                <div key={i} className="border rounded p-3 space-y-2">
                    <div className="grid grid-cols-2 gap-2">
                        <Input
                            placeholder="Nom de l'icône (ex: CalendarDaysIcon)"
                            value={item.icon}
                            onChange={(e) => updateItem(i, 'icon', e.target.value)}
                        />
                        <Input
                            placeholder="Classes Tailwind (couleur icône)"
                            value={item.iconColor ?? ''}
                            onChange={(e) => updateItem(i, 'iconColor', e.target.value)}
                        />
                    </div>
                    <Input placeholder="Titre" value={item.title} onChange={(e) => updateItem(i, 'title', e.target.value)} />
                    <Textarea
                        placeholder="Description"
                        value={item.description}
                        onChange={(e) => updateItem(i, 'description', e.target.value)}
                    />
                    <Button type="button" size="sm" variant="ghost" onClick={() => removeItem(i)}>
                        <Trash2 className="h-4 w-4 text-destructive mr-1" />
                        Supprimer
                    </Button>
                </div>
            ))}
        </div>
    );
}

interface DesignEditorProps {
    design: DesignSettings;
    updateDesign: <K extends keyof DesignSettings>(key: K, value: DesignSettings[K] | undefined) => void;
}

const SIDE_LABELS: Record<string, string> = {
    padding_top: 'Haut',
    padding_right: 'Droite',
    padding_bottom: 'Bas',
    padding_left: 'Gauche',
    margin_top: 'Haut',
    margin_right: 'Droite',
    margin_bottom: 'Bas',
    margin_left: 'Gauche',
};

function sideLabel(field: string): string {
    return SIDE_LABELS[field] ?? field;
}

function DesignEditor({ design, updateDesign }: DesignEditorProps) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
                <Label>Police</Label>
                <Select
                    value={design.font_family ?? ''}
                    onValueChange={(v) => updateDesign('font_family', v as DesignSettings['font_family'])}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Par défaut" />
                    </SelectTrigger>
                    <SelectContent>
                        {FONT_FAMILY_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Taille du titre</Label>
                <Select
                    value={design.heading_size ?? ''}
                    onValueChange={(v) => updateDesign('heading_size', v as DesignSettings['heading_size'])}
                >
                    <SelectTrigger><SelectValue placeholder="Par défaut" /></SelectTrigger>
                    <SelectContent>
                        {HEADING_SIZE_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Taille du paragraphe</Label>
                <Select
                    value={design.paragraph_size ?? ''}
                    onValueChange={(v) => updateDesign('paragraph_size', v as DesignSettings['paragraph_size'])}
                >
                    <SelectTrigger><SelectValue placeholder="Par défaut" /></SelectTrigger>
                    <SelectContent>
                        {PARAGRAPH_SIZE_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Alignement</Label>
                <Select
                    value={design.alignment ?? ''}
                    onValueChange={(v) => updateDesign('alignment', v as DesignSettings['alignment'])}
                >
                    <SelectTrigger><SelectValue placeholder="Par défaut" /></SelectTrigger>
                    <SelectContent>
                        {ALIGNMENT_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Disposition</Label>
                <Select
                    value={design.layout ?? ''}
                    onValueChange={(v) => updateDesign('layout', v as DesignSettings['layout'])}
                >
                    <SelectTrigger><SelectValue placeholder="Par défaut" /></SelectTrigger>
                    <SelectContent>
                        {LAYOUT_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="md:col-span-2 border rounded-lg p-3 space-y-3">
                <Label className="text-base font-semibold">Padding (espacement intérieur)</Label>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {(['padding_top', 'padding_right', 'padding_bottom', 'padding_left'] as const).map((field) => (
                        <div key={field} className="space-y-1">
                            <Label className="text-xs text-muted-foreground capitalize">{sideLabel(field)}</Label>
                            <Select
                                value={design[field] ?? ''}
                                onValueChange={(v) => updateDesign(field, v as DesignSettings[typeof field])}
                            >
                                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    {SPACING_OPTIONS.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    ))}
                </div>
            </div>

            <div className="md:col-span-2 border rounded-lg p-3 space-y-3">
                <Label className="text-base font-semibold">Margin (espacement extérieur)</Label>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {(['margin_top', 'margin_right', 'margin_bottom', 'margin_left'] as const).map((field) => (
                        <div key={field} className="space-y-1">
                            <Label className="text-xs text-muted-foreground capitalize">{sideLabel(field)}</Label>
                            <Select
                                value={design[field] ?? ''}
                                onValueChange={(v) => updateDesign(field, v as DesignSettings[typeof field])}
                            >
                                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    {SPACING_OPTIONS.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    ))}
                </div>
            </div>

            <div className="space-y-2">
                <Label>Animation</Label>
                <Select
                    value={design.animation ?? ''}
                    onValueChange={(v) => updateDesign('animation', v as DesignSettings['animation'])}
                >
                    <SelectTrigger><SelectValue placeholder="Par défaut" /></SelectTrigger>
                    <SelectContent>
                        {ANIMATION_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Mode sombre</Label>
                <Select
                    value={design.dark_mode ?? ''}
                    onValueChange={(v) => updateDesign('dark_mode', v as DesignSettings['dark_mode'])}
                >
                    <SelectTrigger><SelectValue placeholder="Hérité" /></SelectTrigger>
                    <SelectContent>
                        {DARK_MODE_OPTIONS.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Couleur de texte (hex)</Label>
                <Input
                    type="color"
                    value={design.text_color ?? '#000000'}
                    onChange={(e) => updateDesign('text_color', e.target.value)}
                />
            </div>

            <div className="space-y-2">
                <Label>Couleur de fond (hex)</Label>
                <Input
                    type="color"
                    value={design.background_color ?? '#ffffff'}
                    onChange={(e) => updateDesign('background_color', e.target.value)}
                />
            </div>

            <div className="space-y-2 md:col-span-2">
                <Label>URL de l'image de fond</Label>
                <Input
                    value={design.background_image_url ?? ''}
                    onChange={(e) => updateDesign('background_image_url', e.target.value)}
                    placeholder="https://… ou /path/to/image.png"
                />
            </div>

            <div className="space-y-2 md:col-span-2">
                <Label>Opacité de l'overlay : {(design.overlay_opacity ?? 0.5).toFixed(2)}</Label>
                <Slider
                    min={0}
                    max={1}
                    step={0.05}
                    value={[design.overlay_opacity ?? 0.5]}
                    onValueChange={(v) => updateDesign('overlay_opacity', v[0])}
                />
            </div>
        </div>
    );
}
