import React from 'react';
import DOMPurify from 'isomorphic-dompurify';

/**
 * SafeHTML Component
 *
 * A secure wrapper for rendering HTML content that automatically sanitizes
 * input to prevent XSS attacks. This component should be used instead of
 * dangerouslySetInnerHTML throughout the application.
 *
 * @example
 * ```tsx
 * // Basic usage
 * <SafeHTML html={userContent} />
 *
 * // With custom tag
 * <SafeHTML html={description} tag="p" />
 *
 * // With className
 * <SafeHTML html={content} className="prose dark:prose-invert" />
 *
 * // With custom configuration
 * <SafeHTML
 *   html={richText}
 *   config={{ ALLOWED_TAGS: ['b', 'i', 'em', 'strong'] }}
 * />
 * ```
 */

interface SafeHTMLProps {
    /**
     * The HTML string to sanitize and render
     */
    html: string;

    /**
     * The HTML tag to use as the container element
     * @default 'div'
     */
    tag?: keyof JSX.IntrinsicElements;

    /**
     * CSS className to apply to the container
     */
    className?: string;

    /**
     * Custom DOMPurify configuration
     * @see https://github.com/cure53/DOMPurify#can-i-configure-dompurify
     */
    config?: DOMPurify.Config;

    /**
     * Additional props to pass to the container element
     */
    [key: string]: unknown;
}

/**
 * Default DOMPurify configuration
 *
 * Allows most common HTML elements and attributes while removing
 * potentially dangerous elements like scripts, iframes, and event handlers.
 */
const DEFAULT_CONFIG: DOMPurify.Config = {
    ALLOWED_TAGS: [
        'a', 'abbr', 'address', 'article', 'aside', 'b', 'blockquote', 'br',
        'caption', 'code', 'col', 'colgroup', 'dd', 'del', 'details', 'div',
        'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'header', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'main',
        'mark', 'nav', 'ol', 'p', 'pre', 'q', 'section', 'small', 'span', 'strong',
        'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead',
        'time', 'tr', 'u', 'ul', 'var',
    ],
    ALLOWED_ATTR: [
        'href', 'src', 'alt', 'title', 'class', 'id', 'style', 'target', 'rel',
        'width', 'height', 'align', 'border', 'colspan', 'rowspan', 'datetime',
        'cite', 'data-*', 'aria-*', 'role',
    ],
    FORBID_TAGS: ['script', 'style'],
    FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur'],
    ALLOW_DATA_ATTR: true,
    ALLOW_ARIA_ATTR: true,
    // Force all links to open in new tab and add security attributes
    ADD_ATTR: ['target', 'rel'],
    SAFE_FOR_TEMPLATES: true,
};

/**
 * Sanitize HTML string using DOMPurify
 *
 * @param html - The HTML string to sanitize
 * @param config - Optional DOMPurify configuration
 * @returns Sanitized HTML string
 */
export const sanitizeHTML = (
    html: string,
    config?: DOMPurify.Config
): string => {
    if (!html || typeof html !== 'string') {
        return '';
    }

    const mergedConfig = {
        ...DEFAULT_CONFIG,
        ...config,
    } as DOMPurify.Config;

    return DOMPurify.sanitize(html, mergedConfig as any) as unknown as string;
};

/**
 * SafeHTML Component
 *
 * Renders sanitized HTML content in a specified container element.
 * Automatically prevents XSS attacks by removing dangerous content.
 */
const SafeHTML: React.FC<SafeHTMLProps> = ({
    html,
    tag: Tag = 'div',
    className = '',
    config,
    ...props
}) => {
    // Sanitize the HTML
    const sanitizedHTML = sanitizeHTML(html, config);

    // Additional security: Add rel="noopener noreferrer" to all links
    const secureHTML = sanitizedHTML.replace(
        /<a /g,
        '<a target="_blank" rel="noopener noreferrer" '
    );

    return React.createElement(Tag, {
        className,
        dangerouslySetInnerHTML: { __html: secureHTML },
        ...props,
    });
};

export default SafeHTML;

/**
 * Helper hook for sanitizing HTML in components
 *
 * @example
 * ```tsx
 * const MyComponent = ({ userContent }) => {
 *   const safeContent = useSanitizedHTML(userContent);
 *   return <div dangerouslySetInnerHTML={{ __html: safeContent }} />;
 * };
 * ```
 */
export const useSanitizedHTML = (
    html: string,
    config?: DOMPurify.Config
): string => {
    return React.useMemo(() => sanitizeHTML(html, config), [html, config]);
};

/**
 * Preset configurations for common use cases
 */
export const DOMPurifyPresets = {
    /**
     * Strict configuration - Only allows basic text formatting
     */
    STRICT: {
        ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'p', 'br'],
        ALLOWED_ATTR: [],
        FORBID_TAGS: ['script', 'style'],
    } as DOMPurify.Config,

    /**
     * Basic configuration - Allows common formatting and links
     */
    BASIC: {
        ALLOWED_TAGS: ['a', 'b', 'i', 'em', 'strong', 'p', 'br', 'ul', 'ol', 'li'],
        ALLOWED_ATTR: ['href', 'target', 'rel'],
        FORBID_TAGS: ['script', 'style'],
    } as DOMPurify.Config,

    /**
     * Rich text configuration - Allows rich text editor content
     */
    RICH_TEXT: {
        ALLOWED_TAGS: [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'a', 'b', 'i', 'em',
            'strong', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'img',
        ],
        ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'target', 'rel'],
        FORBID_TAGS: ['script', 'style'],
    } as DOMPurify.Config,

    /**
     * Full configuration - Allows most HTML (use with caution)
     */
    FULL: DEFAULT_CONFIG,
};
