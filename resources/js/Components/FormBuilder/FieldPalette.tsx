import React from 'react';
import {
    Bars3BottomLeftIcon,
    DocumentTextIcon,
    HashtagIcon,
    EnvelopeIcon,
    PhoneIcon,
    LinkIcon,
    LockClosedIcon,
    ChevronDownIcon,
    ListBulletIcon,
    CheckCircleIcon,
    CheckIcon,
    Squares2X2Icon,
    CalendarIcon,
    ClockIcon,
    CalendarDaysIcon,
    PaperClipIcon,
    PhotoIcon,
    PencilSquareIcon,
    RectangleGroupIcon,
    ArrowPathIcon,
    ViewColumnsIcon,
    Square3Stack3DIcon,
    ChevronUpDownIcon,
    EyeSlashIcon,
    CalculatorIcon,
    MagnifyingGlassIcon,
    StarIcon,
    AdjustmentsHorizontalIcon,
    SwatchIcon,
    TagIcon,
    MapPinIcon,
    UserIcon,
    BuildingOfficeIcon,
} from '@heroicons/react/24/outline';
import type { FormFieldType } from '@/Types/form';

interface FieldTypeItem {
    type: FormFieldType;
    label: string;
    icon: React.ReactNode;
    category: 'text' | 'selection' | 'date' | 'file' | 'layout' | 'advanced';
}

const fieldTypes: FieldTypeItem[] = [
    // Text Fields
    { type: 'text', label: 'Texte', icon: <Bars3BottomLeftIcon className="h-4 w-4" />, category: 'text' },
    { type: 'textarea', label: 'Zone de texte', icon: <DocumentTextIcon className="h-4 w-4" />, category: 'text' },
    { type: 'rich_text', label: 'Texte enrichi', icon: <DocumentTextIcon className="h-4 w-4" />, category: 'text' },
    { type: 'number', label: 'Nombre', icon: <HashtagIcon className="h-4 w-4" />, category: 'text' },
    { type: 'email', label: 'Email', icon: <EnvelopeIcon className="h-4 w-4" />, category: 'text' },
    { type: 'phone', label: 'Téléphone', icon: <PhoneIcon className="h-4 w-4" />, category: 'text' },
    { type: 'url', label: 'URL', icon: <LinkIcon className="h-4 w-4" />, category: 'text' },
    { type: 'password', label: 'Mot de passe', icon: <LockClosedIcon className="h-4 w-4" />, category: 'text' },

    // Selection Fields
    { type: 'select', label: 'Liste déroulante', icon: <ChevronDownIcon className="h-4 w-4" />, category: 'selection' },
    { type: 'multi_select', label: 'Sélection multiple', icon: <ListBulletIcon className="h-4 w-4" />, category: 'selection' },
    { type: 'radio', label: 'Boutons radio', icon: <CheckCircleIcon className="h-4 w-4" />, category: 'selection' },
    { type: 'checkbox', label: 'Case à cocher', icon: <CheckIcon className="h-4 w-4" />, category: 'selection' },
    { type: 'checkbox_group', label: 'Groupe de cases', icon: <Squares2X2Icon className="h-4 w-4" />, category: 'selection' },
    { type: 'toggle', label: 'Interrupteur', icon: <AdjustmentsHorizontalIcon className="h-4 w-4" />, category: 'selection' },

    // Date/Time Fields
    { type: 'date', label: 'Date', icon: <CalendarIcon className="h-4 w-4" />, category: 'date' },
    { type: 'time', label: 'Heure', icon: <ClockIcon className="h-4 w-4" />, category: 'date' },
    { type: 'datetime', label: 'Date et heure', icon: <CalendarDaysIcon className="h-4 w-4" />, category: 'date' },
    { type: 'date_range', label: 'Plage de dates', icon: <CalendarDaysIcon className="h-4 w-4" />, category: 'date' },

    // File Fields
    { type: 'file', label: 'Fichier', icon: <PaperClipIcon className="h-4 w-4" />, category: 'file' },
    { type: 'image', label: 'Image', icon: <PhotoIcon className="h-4 w-4" />, category: 'file' },
    { type: 'signature', label: 'Signature', icon: <PencilSquareIcon className="h-4 w-4" />, category: 'file' },

    // Layout Fields
    { type: 'section', label: 'Section', icon: <RectangleGroupIcon className="h-4 w-4" />, category: 'layout' },
    { type: 'group', label: 'Groupe', icon: <Squares2X2Icon className="h-4 w-4" />, category: 'layout' },
    { type: 'repeater', label: 'Répéteur', icon: <ArrowPathIcon className="h-4 w-4" />, category: 'layout' },
    { type: 'columns', label: 'Colonnes', icon: <ViewColumnsIcon className="h-4 w-4" />, category: 'layout' },
    { type: 'tabs', label: 'Onglets', icon: <Square3Stack3DIcon className="h-4 w-4" />, category: 'layout' },
    { type: 'accordion', label: 'Accordéon', icon: <ChevronUpDownIcon className="h-4 w-4" />, category: 'layout' },

    // Advanced Fields
    { type: 'hidden', label: 'Champ caché', icon: <EyeSlashIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'computed', label: 'Champ calculé', icon: <CalculatorIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'lookup', label: 'Recherche', icon: <MagnifyingGlassIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'rating', label: 'Évaluation', icon: <StarIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'slider', label: 'Curseur', icon: <AdjustmentsHorizontalIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'color', label: 'Couleur', icon: <SwatchIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'tags', label: 'Tags', icon: <TagIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'location', label: 'Localisation', icon: <MapPinIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'user_select', label: 'Sélection utilisateur', icon: <UserIcon className="h-4 w-4" />, category: 'advanced' },
    { type: 'department_select', label: 'Sélection département', icon: <BuildingOfficeIcon className="h-4 w-4" />, category: 'advanced' },
];

const categories = [
    { id: 'text', label: 'Texte' },
    { id: 'selection', label: 'Sélection' },
    { id: 'date', label: 'Date / Heure' },
    { id: 'file', label: 'Fichiers' },
    { id: 'layout', label: 'Mise en page' },
    { id: 'advanced', label: 'Avancé' },
];

interface FieldPaletteProps {
    onAddField: (type: FormFieldType) => void;
}

export default function FieldPalette({ onAddField }: FieldPaletteProps) {
    const onDragStart = (event: React.DragEvent, type: FormFieldType) => {
        event.dataTransfer.setData('application/form-field-type', type);
        event.dataTransfer.effectAllowed = 'copy';
    };

    return (
        <div className="h-full overflow-y-auto p-4 space-y-4">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                Champs du formulaire
            </h3>
            <p className="text-xs text-gray-500 dark:text-gray-400">
                Glissez-déposez ou cliquez pour ajouter
            </p>

            {categories.map((category) => (
                <div key={category.id} className="space-y-2">
                    <h4 className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {category.label}
                    </h4>
                    <div className="grid grid-cols-2 gap-1">
                        {fieldTypes
                            .filter((field) => field.category === category.id)
                            .map((field) => (
                                <div
                                    key={field.type}
                                    draggable
                                    onDragStart={(e) => onDragStart(e, field.type)}
                                    onClick={() => onAddField(field.type)}
                                    className="
                                        flex items-center gap-2 p-2 rounded-md cursor-grab
                                        bg-white dark:bg-gray-800
                                        border border-gray-200 dark:border-gray-700
                                        hover:border-primary hover:shadow-sm
                                        transition-all duration-150
                                        text-gray-700 dark:text-gray-300
                                    "
                                >
                                    <span className="text-gray-500 dark:text-gray-400">
                                        {field.icon}
                                    </span>
                                    <span className="text-xs font-medium truncate">
                                        {field.label}
                                    </span>
                                </div>
                            ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
