import DOMPurify from 'isomorphic-dompurify';

/**
 * Sanitize HTML content to prevent XSS attacks
 *
 * @param html - The HTML string to sanitize
 * @param config - Optional DOMPurify configuration
 * @returns Sanitized HTML string safe for rendering
 */
export const sanitizeHtml = (html: string, config?: DOMPurify.Config): string => {
    // Default configuration - allows common formatting tags only
    const defaultConfig: DOMPurify.Config = {
        ALLOWED_TAGS: [
            'p', 'br', 'strong', 'em', 'u', 'strike', 'del', 's',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'blockquote', 'pre', 'code',
            'a', 'img',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'div', 'span'
        ],
        ALLOWED_ATTR: [
            'href', 'title', 'target', 'rel',
            'src', 'alt', 'width', 'height',
            'class', 'id'
        ],
        FORBID_TAGS: ['script', 'style'],
        FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover'],
        ALLOWED_URI_REGEXP: /^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
        ALLOW_DATA_ATTR: false,
        ALLOW_UNKNOWN_PROTOCOLS: false,
        SAFE_FOR_TEMPLATES: true,
        WHOLE_DOCUMENT: false,
        RETURN_DOM: false,
        RETURN_DOM_FRAGMENT: false,
        FORCE_BODY: false,
        SANITIZE_DOM: true,
        IN_PLACE: false
    };

    // Merge with custom config if provided
    const finalConfig = { ...defaultConfig, ...config } as DOMPurify.Config;

    // Add hooks to further sanitize
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        // Set all links to open in new tab and add noopener/noreferrer
        if (node.tagName === 'A') {
            node.setAttribute('target', '_blank');
            node.setAttribute('rel', 'noopener noreferrer');
        }

        // Remove all inline styles
        if (node.hasAttribute('style')) {
            node.removeAttribute('style');
        }
    });

    // Sanitize the HTML
    const sanitized = DOMPurify.sanitize(html, finalConfig as any);

    // Remove hooks after sanitization
    DOMPurify.removeAllHooks();

    return sanitized as unknown as string;
};

/**
 * Sanitize HTML for article content - more permissive
 */
export const sanitizeArticleContent = (html: string): string => {
    return sanitizeHtml(html, {
        ADD_TAGS: ['iframe', 'video', 'audio', 'source'],
        ADD_ATTR: ['frameborder', 'allowfullscreen', 'controls', 'autoplay', 'loop', 'muted'],
        ALLOWED_URI_REGEXP: /^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp|data):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
    });
};

/**
 * Sanitize plain text - strips all HTML
 */
export const sanitizePlainText = (text: string): string => {
    return DOMPurify.sanitize(text, {
        ALLOWED_TAGS: [],
        ALLOWED_ATTR: [],
        FORBID_TAGS: ['script', 'style']
    });
};

/**
 * Sanitize user input for display in limited contexts (comments, descriptions, etc.)
 */
export const sanitizeUserInput = (html: string): string => {
    return sanitizeHtml(html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'a'],
        ALLOWED_ATTR: ['href', 'title'],
    });
};
