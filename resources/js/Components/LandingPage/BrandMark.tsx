interface BrandMarkProps {
    /** Extra Tailwind classes for the wrapping element. */
    className?: string;
    /** Render in white for placement on a dark or coloured (brand) background. */
    inverted?: boolean;
}

/**
 * Bio-Digital brand wordmark: "Bio-" with the cardinal-red "Digital" accent
 * and a small uppercase descriptor. Pass `inverted` to render it in white on a
 * dark or brand-coloured surface (e.g. the dashboard sidebar header).
 */
export default function BrandMark({ className = '', inverted = false }: BrandMarkProps) {
    return (
        <span className={`inline-flex flex-col leading-none ${className}`}>
            <span
                className={`font-display text-[1.4rem] font-bold tracking-tight ${
                    inverted ? 'text-white' : 'text-bd-ink'
                }`}
            >
                Bio-<span className={inverted ? 'text-white' : 'text-bd-brand'}>Digital</span>
            </span>
            <span
                className={`mt-1 text-[9px] font-medium uppercase tracking-[0.18em] ${
                    inverted ? 'text-white/70' : 'text-bd-ink-3'
                }`}
            >
                Software Systems Solutions
            </span>
        </span>
    );
}
