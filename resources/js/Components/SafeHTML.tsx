import DOMPurify, { Config } from 'dompurify';
import { useMemo } from 'react';

/**
 * Predefined DOMPurify configuration presets for different use cases
 */
export const DOMPurifyPresets = {
    /** Strict: Only basic text formatting, no links or images */
    STRICT: {
        ALLOWED_TAGS: ['p', 'br', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins'],
        ALLOWED_ATTR: ['class'],
        ALLOW_DATA_ATTR: false,
    },
    /** Basic: Text formatting with links, no images */
    BASIC: {
        ALLOWED_TAGS: [
            'p', 'br', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins',
            'a', 'ul', 'ol', 'li', 'blockquote',
        ],
        ALLOWED_ATTR: ['class', 'href', 'target', 'rel', 'title'],
        ALLOW_DATA_ATTR: false,
    },
    /** Rich Text: Full rich text support including images and tables */
    RICH_TEXT: {
        ALLOWED_TAGS: [
            // Text formatting
            'p', 'br', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins',
            // Headings
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            // Lists
            'ul', 'ol', 'li',
            // Block elements
            'div', 'blockquote', 'pre', 'code',
            // Links
            'a',
            // Images
            'img',
            // Tables
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
            // Other
            'hr', 'sub', 'sup', 'mark',
        ],
        ALLOWED_ATTR: [
            'class', 'id', 'style',
            'href', 'target', 'rel', 'title',
            'src', 'alt', 'width', 'height',
            'colspan', 'rowspan',
            'dir',
        ],
        ALLOW_DATA_ATTR: false,
    },
} as const;

// Default configuration (RICH_TEXT preset)
const DEFAULT_CONFIG: Config = {
    ...DOMPurifyPresets.RICH_TEXT,
    ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel):|[^a-z]|[a-z+.-]+(?:[^a-z+.\-:]|$))/i,
    ALLOW_UNKNOWN_PROTOCOLS: false,
    RETURN_DOM: false,
    RETURN_DOM_FRAGMENT: false,
    RETURN_TRUSTED_TYPE: false,
};

/**
 * Sanitize HTML string to prevent XSS attacks
 * @param html - The HTML string to sanitize
 * @param config - Optional DOMPurify configuration (defaults to RICH_TEXT preset)
 * @returns Sanitized HTML string
 */
export function sanitizeHTML(html: string, config?: Config): string {
    if (!html || typeof html !== 'string') {
        return '';
    }

    const finalConfig = config ? { ...DEFAULT_CONFIG, ...config } : DEFAULT_CONFIG;

    // Add hooks to modify behavior during sanitization
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        // Force all links to open in new tab with secure attributes
        if (node.tagName === 'A') {
            node.setAttribute('target', '_blank');
            node.setAttribute('rel', 'noopener noreferrer');
        }

        // Remove any javascript: or data: URLs that might have slipped through
        if (node.hasAttribute('href')) {
            const href = node.getAttribute('href') || '';
            if (href.toLowerCase().startsWith('javascript:') ||
                href.toLowerCase().startsWith('data:') ||
                href.toLowerCase().startsWith('vbscript:')) {
                node.removeAttribute('href');
            }
        }

        // Sanitize image sources
        if (node.hasAttribute('src')) {
            const src = node.getAttribute('src') || '';
            if (src.toLowerCase().startsWith('javascript:') ||
                src.toLowerCase().startsWith('data:text') ||
                src.toLowerCase().startsWith('vbscript:')) {
                node.removeAttribute('src');
            }
        }

        // Remove any event handlers that might have been added via style
        if (node.hasAttribute('style')) {
            const style = node.getAttribute('style') || '';
            // Remove any expression(), url(), or behavior patterns
            const sanitizedStyle = style
                .replace(/expression\s*\([^)]*\)/gi, '')
                .replace(/url\s*\(\s*["']?\s*javascript:[^)]*\)/gi, '')
                .replace(/behavior\s*:/gi, '')
                .replace(/-moz-binding/gi, '');
            node.setAttribute('style', sanitizedStyle);
        }
    });

    const sanitized = DOMPurify.sanitize(html, finalConfig);

    // Remove hooks after use to prevent memory leaks
    DOMPurify.removeHook('afterSanitizeAttributes');

    return sanitized;
}

// Alias for backward compatibility
export const sanitizeHtml = sanitizeHTML;

interface SafeHTMLProps {
    /** The HTML content to render safely */
    html: string;
    /** Additional CSS classes to apply to the container */
    className?: string;
    /** The HTML tag to use for the container (alias for 'as') */
    tag?: keyof JSX.IntrinsicElements;
    /** The HTML tag to use for the container */
    as?: keyof JSX.IntrinsicElements;
    /** Test ID for testing purposes */
    'data-testid'?: string;
}

/**
 * SafeHTML Component
 *
 * Renders HTML content safely by sanitizing it to prevent XSS attacks.
 * Uses DOMPurify to remove potentially malicious content while preserving
 * safe formatting tags.
 *
 * @example
 * ```tsx
 * <SafeHTML
 *   html="<p>Hello <strong>World</strong></p>"
 *   className="prose"
 * />
 * ```
 */
export default function SafeHTML({
    html,
    className = '',
    tag,
    as,
    'data-testid': testId,
}: SafeHTMLProps) {
    // Support both 'tag' and 'as' props, with 'tag' taking precedence for backward compatibility
    const Component = tag || as || 'div';
    const sanitizedHtml = useMemo(() => sanitizeHTML(html), [html]);

    // Return empty div instead of null to match test expectations
    return (
        <Component
            className={className ? `safe-html-content ${className}`.trim() : 'safe-html-content'}
            dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
            data-testid={testId}
        />
    );
}

// Export types for testing
export type { SafeHTMLProps };
// Alias for backward compatibility
export type SafeHtmlProps = SafeHTMLProps;
