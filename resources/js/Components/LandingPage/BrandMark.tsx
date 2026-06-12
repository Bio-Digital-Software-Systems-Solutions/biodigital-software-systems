interface BrandMarkProps {
    /** Tailwind size classes for the wrapping link/element, e.g. "text-[17px]". */
    className?: string;
    /** Size of the glyph in pixels. */
    size?: number;
}

/**
 * Bio-Digital wordmark: the intertwined helix glyph plus the two-line name.
 * Mirrors the brand lockup from the approved landing page proposal.
 */
export default function BrandMark({ className = '', size = 30 }: BrandMarkProps) {
    return (
        <span className={`flex items-center gap-2.5 font-display font-semibold text-[17px] text-bd-ink ${className}`}>
            <svg
                viewBox="0 0 40 40"
                fill="none"
                aria-hidden="true"
                width={size}
                height={size}
                className="flex-none"
            >
                <path
                    d="M12 5c9 4 7 9 8 15s-1 11 8 15"
                    className="stroke-bd-brand"
                    strokeWidth={3}
                    strokeLinecap="round"
                />
                <path
                    d="M28 5c-9 4-7 9-8 15s1 11-8 15"
                    className="stroke-bd-accent"
                    strokeWidth={3}
                    strokeLinecap="round"
                />
            </svg>
            <span className="leading-none">
                Bio-Digital
                <small className="block font-body font-medium text-[10px] tracking-[0.12em] uppercase text-bd-ink-3 mt-0.5">
                    Software Systems
                </small>
            </span>
        </span>
    );
}
