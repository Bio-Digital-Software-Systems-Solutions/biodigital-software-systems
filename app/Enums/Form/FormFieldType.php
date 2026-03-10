<?php

namespace App\Enums\Form;

enum FormFieldType: string
{
    // Text inputs
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case RICH_TEXT = 'rich_text';
    case NUMBER = 'number';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case URL = 'url';
    case PASSWORD = 'password';

    // Selection inputs
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';
    case RADIO = 'radio';
    case CHECKBOX = 'checkbox';
    case CHECKBOX_GROUP = 'checkbox_group';
    case TOGGLE = 'toggle';

    // Date/Time inputs
    case DATE = 'date';
    case TIME = 'time';
    case DATETIME = 'datetime';
    case DATE_RANGE = 'date_range';

    // File inputs
    case FILE = 'file';
    case IMAGE = 'image';
    case SIGNATURE = 'signature';

    // Layout elements
    case SECTION = 'section';
    case GROUP = 'group';
    case REPEATER = 'repeater';
    case COLUMNS = 'columns';
    case TABS = 'tabs';
    case ACCORDION = 'accordion';

    // Special inputs
    case HIDDEN = 'hidden';
    case COMPUTED = 'computed';
    case LOOKUP = 'lookup';
    case RATING = 'rating';
    case SLIDER = 'slider';
    case COLOR = 'color';
    case TAGS = 'tags';
    case LOCATION = 'location';
    case USER_SELECT = 'user_select';
    case DEPARTMENT_SELECT = 'department_select';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texte court',
            self::TEXTAREA => 'Texte long',
            self::RICH_TEXT => 'Texte riche',
            self::NUMBER => 'Nombre',
            self::EMAIL => 'Email',
            self::PHONE => 'Téléphone',
            self::URL => 'URL',
            self::PASSWORD => 'Mot de passe',
            self::SELECT => 'Liste déroulante',
            self::MULTI_SELECT => 'Sélection multiple',
            self::RADIO => 'Boutons radio',
            self::CHECKBOX => 'Case à cocher',
            self::CHECKBOX_GROUP => 'Groupe de cases',
            self::TOGGLE => 'Interrupteur',
            self::DATE => 'Date',
            self::TIME => 'Heure',
            self::DATETIME => 'Date et heure',
            self::DATE_RANGE => 'Plage de dates',
            self::FILE => 'Fichier',
            self::IMAGE => 'Image',
            self::SIGNATURE => 'Signature',
            self::SECTION => 'Section',
            self::GROUP => 'Groupe',
            self::REPEATER => 'Répéteur',
            self::COLUMNS => 'Colonnes',
            self::TABS => 'Onglets',
            self::ACCORDION => 'Accordéon',
            self::HIDDEN => 'Champ caché',
            self::COMPUTED => 'Champ calculé',
            self::LOOKUP => 'Recherche',
            self::RATING => 'Évaluation',
            self::SLIDER => 'Curseur',
            self::COLOR => 'Couleur',
            self::TAGS => 'Tags',
            self::LOCATION => 'Localisation',
            self::USER_SELECT => 'Sélection utilisateur',
            self::DEPARTMENT_SELECT => 'Sélection département',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEXT => 'type',
            self::TEXTAREA => 'align-left',
            self::RICH_TEXT => 'file-text',
            self::NUMBER => 'hash',
            self::EMAIL => 'mail',
            self::PHONE => 'phone',
            self::URL => 'link',
            self::PASSWORD => 'lock',
            self::SELECT => 'chevron-down',
            self::MULTI_SELECT => 'list-checks',
            self::RADIO => 'circle-dot',
            self::CHECKBOX => 'check-square',
            self::CHECKBOX_GROUP => 'check-square',
            self::TOGGLE => 'toggle-left',
            self::DATE => 'calendar',
            self::TIME => 'clock',
            self::DATETIME => 'calendar-clock',
            self::DATE_RANGE => 'calendar-range',
            self::FILE => 'paperclip',
            self::IMAGE => 'image',
            self::SIGNATURE => 'pen-tool',
            self::SECTION => 'layout',
            self::GROUP => 'folder',
            self::REPEATER => 'repeat',
            self::COLUMNS => 'columns',
            self::TABS => 'folder-open',
            self::ACCORDION => 'chevrons-down',
            self::HIDDEN => 'eye-off',
            self::COMPUTED => 'calculator',
            self::LOOKUP => 'search',
            self::RATING => 'star',
            self::SLIDER => 'sliders',
            self::COLOR => 'palette',
            self::TAGS => 'tag',
            self::LOCATION => 'map-pin',
            self::USER_SELECT => 'user',
            self::DEPARTMENT_SELECT => 'building',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::TEXT, self::TEXTAREA, self::RICH_TEXT, self::NUMBER, self::EMAIL, self::PHONE, self::URL, self::PASSWORD => 'text',
            self::SELECT, self::MULTI_SELECT, self::RADIO, self::CHECKBOX, self::CHECKBOX_GROUP, self::TOGGLE => 'selection',
            self::DATE, self::TIME, self::DATETIME, self::DATE_RANGE => 'datetime',
            self::FILE, self::IMAGE, self::SIGNATURE => 'file',
            self::SECTION, self::GROUP, self::REPEATER, self::COLUMNS, self::TABS, self::ACCORDION => 'layout',
            self::HIDDEN, self::COMPUTED, self::LOOKUP, self::RATING, self::SLIDER, self::COLOR, self::TAGS, self::LOCATION, self::USER_SELECT, self::DEPARTMENT_SELECT => 'special',
        };
    }

    public function isInput(): bool
    {
        return !in_array($this, [self::SECTION, self::COLUMNS, self::TABS, self::ACCORDION]);
    }

    public function isLayout(): bool
    {
        return in_array($this, [self::SECTION, self::GROUP, self::REPEATER, self::COLUMNS, self::TABS, self::ACCORDION]);
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::SELECT, self::MULTI_SELECT, self::RADIO, self::CHECKBOX_GROUP]);
    }

    public function defaultValidation(): array
    {
        return match ($this) {
            self::EMAIL => ['email'],
            self::URL => ['url'],
            self::NUMBER => ['numeric'],
            self::PHONE => ['regex:/^[\d\s\+\-\(\)]+$/'],
            self::DATE, self::DATETIME => ['date'],
            self::FILE, self::IMAGE => ['file'],
            default => [],
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Form\FormFieldType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
            'category' => $case->category(),
            'is_input' => $case->isInput(),
        ], self::cases());
    }

    public static function groupedOptions(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $category = $case->category();
            if (!isset($groups[$category])) {
                $groups[$category] = [
                    'label' => self::categoryLabel($category),
                    'options' => [],
                ];
            }
            $groups[$category]['options'][] = [
                'value' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
            ];
        }
        return array_values($groups);
    }

    private static function categoryLabel(string $category): string
    {
        return match ($category) {
            'text' => 'Texte',
            'selection' => 'Sélection',
            'datetime' => 'Date et heure',
            'file' => 'Fichiers',
            'layout' => 'Mise en page',
            'special' => 'Spécial',
            default => $category,
        };
    }
}
