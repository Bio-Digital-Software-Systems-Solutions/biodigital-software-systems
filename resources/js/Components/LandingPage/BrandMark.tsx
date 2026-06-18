interface BrandMarkProps {
    /** Extra Tailwind classes for the wrapping element. */
    className?: string;
    /** Height of the logo in pixels. */
    size?: number;
}

/**
 * Bio-Digital brand lockup: the full logo image (glyph + wordmark).
 */
export default function BrandMark({ className = '', size = 54 }: BrandMarkProps) {
    return (
        <span className={`flex items-center ${className}`}>
            <img
                src="/Logo.png"
                alt="Bio-Digital Software Systems Solutions"
                style={{ height: size }}
                className="block w-auto max-w-none object-contain"
            />
        </span>
    );
}
