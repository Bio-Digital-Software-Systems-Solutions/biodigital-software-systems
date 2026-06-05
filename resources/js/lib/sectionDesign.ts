import type { CSSProperties } from 'react';

export type SpacingSize = 'none' | 'sm' | 'md' | 'lg' | 'xl';

export type DesignSettings = Partial<{
    font_family: 'inter' | 'poppins' | 'playfair' | 'roboto';
    heading_size: 'sm' | 'md' | 'lg' | 'xl';
    paragraph_size: 'sm' | 'md' | 'lg';
    text_color: string;
    background_color: string;
    alignment: 'left' | 'center' | 'right';
    layout: 'single' | 'two-col' | 'three-col' | 'grid' | 'carousel';
    padding: SpacingSize;
    margin: SpacingSize;
    padding_top: SpacingSize;
    padding_right: SpacingSize;
    padding_bottom: SpacingSize;
    padding_left: SpacingSize;
    margin_top: SpacingSize;
    margin_right: SpacingSize;
    margin_bottom: SpacingSize;
    margin_left: SpacingSize;
    background_image_url: string | null;
    overlay_opacity: number;
    animation: 'none' | 'fade' | 'slide' | 'zoom';
    dark_mode: 'inherit' | 'force-light' | 'force-dark';
}>;

const FONT_FAMILY: Record<NonNullable<DesignSettings['font_family']>, string> = {
    inter: '"Inter", system-ui, sans-serif',
    poppins: '"Poppins", system-ui, sans-serif',
    playfair: '"Playfair Display", Georgia, serif',
    roboto: '"Roboto", system-ui, sans-serif',
};

const HEADING_SIZE: Record<NonNullable<DesignSettings['heading_size']>, string> = {
    sm: 'text-2xl md:text-3xl',
    md: 'text-3xl md:text-4xl',
    lg: 'text-4xl md:text-5xl',
    xl: 'text-5xl md:text-6xl',
};

const PARAGRAPH_SIZE: Record<NonNullable<DesignSettings['paragraph_size']>, string> = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
};

const ALIGNMENT: Record<NonNullable<DesignSettings['alignment']>, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};

const PADDING: Record<SpacingSize, string> = {
    none: 'py-0',
    sm: 'py-6',
    md: 'py-12',
    lg: 'py-16',
    xl: 'py-24',
};

const MARGIN: Record<SpacingSize, string> = {
    none: 'my-0',
    sm: 'my-4',
    md: 'my-8',
    lg: 'my-12',
    xl: 'my-16',
};

const PADDING_TOP: Record<SpacingSize, string> = {
    none: 'pt-0', sm: 'pt-6', md: 'pt-12', lg: 'pt-16', xl: 'pt-24',
};
const PADDING_RIGHT: Record<SpacingSize, string> = {
    none: 'pr-0', sm: 'pr-6', md: 'pr-12', lg: 'pr-16', xl: 'pr-24',
};
const PADDING_BOTTOM: Record<SpacingSize, string> = {
    none: 'pb-0', sm: 'pb-6', md: 'pb-12', lg: 'pb-16', xl: 'pb-24',
};
const PADDING_LEFT: Record<SpacingSize, string> = {
    none: 'pl-0', sm: 'pl-6', md: 'pl-12', lg: 'pl-16', xl: 'pl-24',
};
const MARGIN_TOP: Record<SpacingSize, string> = {
    none: 'mt-0', sm: 'mt-4', md: 'mt-8', lg: 'mt-12', xl: 'mt-16',
};
const MARGIN_RIGHT: Record<SpacingSize, string> = {
    none: 'mr-0', sm: 'mr-4', md: 'mr-8', lg: 'mr-12', xl: 'mr-16',
};
const MARGIN_BOTTOM: Record<SpacingSize, string> = {
    none: 'mb-0', sm: 'mb-4', md: 'mb-8', lg: 'mb-12', xl: 'mb-16',
};
const MARGIN_LEFT: Record<SpacingSize, string> = {
    none: 'ml-0', sm: 'ml-4', md: 'ml-8', lg: 'ml-12', xl: 'ml-16',
};

const LAYOUT: Record<NonNullable<DesignSettings['layout']>, string> = {
    single: 'grid grid-cols-1 gap-6',
    'two-col': 'grid grid-cols-1 md:grid-cols-2 gap-6',
    'three-col': 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
    grid: 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6',
    carousel: 'flex gap-6 overflow-x-auto snap-x snap-mandatory',
};

const ANIMATION: Record<NonNullable<DesignSettings['animation']>, string> = {
    none: '',
    fade: 'motion-safe:animate-in motion-safe:fade-in motion-safe:duration-700',
    slide: 'motion-safe:animate-in motion-safe:slide-in-from-bottom-8 motion-safe:duration-700',
    zoom: 'motion-safe:animate-in motion-safe:zoom-in-95 motion-safe:duration-700',
};

const DARK_MODE: Record<NonNullable<DesignSettings['dark_mode']>, string> = {
    inherit: '',
    'force-light': 'light',
    'force-dark': 'dark',
};

export interface ResolvedDesign {
    sectionClass: string;
    headingClass: string;
    paragraphClass: string;
    alignmentClass: string;
    layoutClass: string;
    sectionStyle: CSSProperties;
    hasPadding: boolean;
    hasMargin: boolean;
}

const isHex = (value: string): boolean => /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value);

export function resolveDesign(design?: DesignSettings | null): ResolvedDesign {
    const d = design ?? {};

    const parts: string[] = [];
    const hasSidePadding =
        d.padding_top || d.padding_right || d.padding_bottom || d.padding_left;
    const hasSideMargin =
        d.margin_top || d.margin_right || d.margin_bottom || d.margin_left;

    if (hasSidePadding) {
        if (d.padding_top) parts.push(PADDING_TOP[d.padding_top]);
        if (d.padding_right) parts.push(PADDING_RIGHT[d.padding_right]);
        if (d.padding_bottom) parts.push(PADDING_BOTTOM[d.padding_bottom]);
        if (d.padding_left) parts.push(PADDING_LEFT[d.padding_left]);
    } else if (d.padding) {
        parts.push(PADDING[d.padding]);
    }

    if (hasSideMargin) {
        if (d.margin_top) parts.push(MARGIN_TOP[d.margin_top]);
        if (d.margin_right) parts.push(MARGIN_RIGHT[d.margin_right]);
        if (d.margin_bottom) parts.push(MARGIN_BOTTOM[d.margin_bottom]);
        if (d.margin_left) parts.push(MARGIN_LEFT[d.margin_left]);
    } else if (d.margin) {
        parts.push(MARGIN[d.margin]);
    }

    if (d.animation) {
        parts.push(ANIMATION[d.animation]);
    }
    if (d.dark_mode) {
        parts.push(DARK_MODE[d.dark_mode]);
    }

    const style: CSSProperties = {};
    if (d.font_family) {
        style.fontFamily = FONT_FAMILY[d.font_family];
    }
    if (d.background_color && isHex(d.background_color)) {
        style.backgroundColor = d.background_color;
    }
    if (d.text_color && isHex(d.text_color)) {
        style.color = d.text_color;
    }
    if (d.background_image_url) {
        style.backgroundImage = `url(${d.background_image_url})`;
        style.backgroundSize = 'cover';
        style.backgroundPosition = 'center';
    }
    if (typeof d.overlay_opacity === 'number' && d.background_image_url) {
        style.position = 'relative';
    }

    return {
        sectionClass: parts.filter(Boolean).join(' '),
        headingClass: d.heading_size ? HEADING_SIZE[d.heading_size] : '',
        paragraphClass: d.paragraph_size ? PARAGRAPH_SIZE[d.paragraph_size] : '',
        alignmentClass: d.alignment ? ALIGNMENT[d.alignment] : '',
        layoutClass: d.layout ? LAYOUT[d.layout] : '',
        sectionStyle: style,
        hasPadding: Boolean(d.padding || hasSidePadding),
        hasMargin: Boolean(d.margin || hasSideMargin),
    };
}

export const FONT_FAMILY_OPTIONS: Array<{ value: NonNullable<DesignSettings['font_family']>; label: string }> = [
    { value: 'inter', label: 'Inter' },
    { value: 'poppins', label: 'Poppins' },
    { value: 'playfair', label: 'Playfair Display' },
    { value: 'roboto', label: 'Roboto' },
];

export const HEADING_SIZE_OPTIONS: Array<{ value: NonNullable<DesignSettings['heading_size']>; label: string }> = [
    { value: 'sm', label: 'Petit' },
    { value: 'md', label: 'Moyen' },
    { value: 'lg', label: 'Grand' },
    { value: 'xl', label: 'Très grand' },
];

export const PARAGRAPH_SIZE_OPTIONS: Array<{ value: NonNullable<DesignSettings['paragraph_size']>; label: string }> = [
    { value: 'sm', label: 'Petit' },
    { value: 'md', label: 'Moyen' },
    { value: 'lg', label: 'Grand' },
];

export const ALIGNMENT_OPTIONS: Array<{ value: NonNullable<DesignSettings['alignment']>; label: string }> = [
    { value: 'left', label: 'Gauche' },
    { value: 'center', label: 'Centré' },
    { value: 'right', label: 'Droite' },
];

export const LAYOUT_OPTIONS: Array<{ value: NonNullable<DesignSettings['layout']>; label: string }> = [
    { value: 'single', label: '1 colonne' },
    { value: 'two-col', label: '2 colonnes' },
    { value: 'three-col', label: '3 colonnes' },
    { value: 'grid', label: 'Grille' },
    { value: 'carousel', label: 'Carrousel' },
];

export const SPACING_OPTIONS: Array<{ value: NonNullable<DesignSettings['padding']>; label: string }> = [
    { value: 'none', label: 'Aucun' },
    { value: 'sm', label: 'Petit' },
    { value: 'md', label: 'Moyen' },
    { value: 'lg', label: 'Grand' },
    { value: 'xl', label: 'Très grand' },
];

export const ANIMATION_OPTIONS: Array<{ value: NonNullable<DesignSettings['animation']>; label: string }> = [
    { value: 'none', label: 'Aucune' },
    { value: 'fade', label: 'Fondu' },
    { value: 'slide', label: 'Glissement' },
    { value: 'zoom', label: 'Zoom' },
];

export const DARK_MODE_OPTIONS: Array<{ value: NonNullable<DesignSettings['dark_mode']>; label: string }> = [
    { value: 'inherit', label: 'Hérité du thème' },
    { value: 'force-light', label: 'Toujours clair' },
    { value: 'force-dark', label: 'Toujours sombre' },
];
