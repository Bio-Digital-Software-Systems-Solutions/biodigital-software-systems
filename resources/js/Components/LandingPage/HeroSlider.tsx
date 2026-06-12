import { useCallback, useEffect, useRef, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { ChevronLeft, ChevronRight } from 'lucide-react';

type SlideVisual = 'build' | 'train' | 'bio';

interface SlideDef {
    id: number;
    eyebrow: string;
    title: string;
    desc: string;
    cta1: { key: string; href: string; variant: 'primary' };
    cta2: { key: string; href: string; variant: 'ghost' };
    visual: SlideVisual;
}

const SLIDES: SlideDef[] = [
    {
        id: 1,
        eyebrow: 'home.hero.s1.eyebrow',
        title: 'home.hero.s1.title',
        desc: 'home.hero.s1.desc',
        cta1: { key: 'home.hero.s1.cta1', href: '#contact', variant: 'primary' },
        cta2: { key: 'home.hero.s1.cta2', href: '#services', variant: 'ghost' },
        visual: 'build',
    },
    {
        id: 2,
        eyebrow: 'home.hero.s2.eyebrow',
        title: 'home.hero.s2.title',
        desc: 'home.hero.s2.desc',
        cta1: { key: 'home.hero.s2.cta1', href: '#trainings', variant: 'primary' },
        cta2: { key: 'home.hero.s2.cta2', href: '#contact', variant: 'ghost' },
        visual: 'train',
    },
    {
        id: 3,
        eyebrow: 'home.hero.s3.eyebrow',
        title: 'home.hero.s3.title',
        desc: 'home.hero.s3.desc',
        cta1: { key: 'home.hero.s3.cta1', href: '#services', variant: 'primary' },
        cta2: { key: 'home.hero.s3.cta2', href: '#contact', variant: 'ghost' },
        visual: 'bio',
    },
];

const TRUST = [
    { value: '8+', key: 'home.hero.trust.years' },
    { value: 'M.Sc.', key: 'home.hero.trust.bioinfo' },
    { value: 'ISTQB', key: 'home.hero.trust.trainer' },
    { value: 'München', key: 'home.hero.trust.location' },
];

const AUTOPLAY_MS = 6000;

function SlideVisualPanel({ visual }: { visual: SlideVisual }) {
    const gradients: Record<SlideVisual, string> = {
        build: 'linear-gradient(135deg, oklch(0.95 0.03 200), oklch(0.97 0.02 215))',
        train: 'linear-gradient(135deg, oklch(0.96 0.035 155), oklch(0.97 0.02 185))',
        bio: 'linear-gradient(135deg, oklch(0.96 0.025 250), oklch(0.97 0.02 210))',
    };

    return (
        <div
            className="relative hidden items-center justify-center overflow-hidden md:flex"
            style={{ background: gradients[visual] }}
        >
            {visual === 'build' && (
                <svg className="w-[min(74%,320px)] opacity-90" viewBox="0 0 320 260" fill="none" aria-hidden="true">
                    <rect x="40" y="44" width="240" height="172" rx="14" fill="#fff" stroke="oklch(0.62 0.12 200)" strokeWidth="2" />
                    <rect x="40" y="44" width="240" height="34" rx="14" fill="oklch(0.95 0.03 200)" />
                    <circle cx="60" cy="61" r="4" fill="oklch(0.7 0.13 155)" />
                    <circle cx="76" cy="61" r="4" fill="oklch(0.62 0.12 200)" />
                    <circle cx="92" cy="61" r="4" fill="oklch(0.8 0.1 80)" />
                    <rect x="60" y="98" width="120" height="9" rx="4.5" fill="oklch(0.62 0.12 200)" />
                    <rect x="60" y="120" width="180" height="8" rx="4" fill="oklch(0.9 0.01 200)" />
                    <rect x="60" y="138" width="150" height="8" rx="4" fill="oklch(0.9 0.01 200)" />
                    <rect x="60" y="166" width="70" height="24" rx="8" fill="oklch(0.7 0.13 155)" />
                </svg>
            )}
            {visual === 'train' && (
                <svg className="w-[min(74%,320px)] opacity-90" viewBox="0 0 320 260" fill="none" aria-hidden="true">
                    <rect x="46" y="56" width="228" height="120" rx="12" fill="#fff" stroke="oklch(0.7 0.13 155)" strokeWidth="2" />
                    <path d="M46 96h228" stroke="oklch(0.92 0.01 180)" strokeWidth="2" />
                    <rect x="66" y="118" width="90" height="10" rx="5" fill="oklch(0.7 0.13 155)" />
                    <rect x="66" y="140" width="150" height="8" rx="4" fill="oklch(0.9 0.01 180)" />
                    <circle cx="160" cy="210" r="26" fill="#fff" stroke="oklch(0.7 0.13 155)" strokeWidth="2" />
                    <path d="M150 210l7 7 14-15" stroke="oklch(0.7 0.13 155)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    <rect x="70" y="70" width="40" height="14" rx="7" fill="oklch(0.96 0.035 155)" />
                </svg>
            )}
            {visual === 'bio' && (
                <svg className="w-[min(74%,320px)] opacity-90" viewBox="0 0 320 260" fill="none" aria-hidden="true">
                    <path d="M120 40c0 50 80 50 80 100s-80 50-80 100" stroke="oklch(0.62 0.12 200)" strokeWidth="3" strokeLinecap="round" />
                    <path d="M200 40c0 50-80 50-80 100s80 50 80 100" stroke="oklch(0.7 0.13 155)" strokeWidth="3" strokeLinecap="round" />
                    <path d="M128 70h64M122 110h76M122 150h76M128 190h64" stroke="oklch(0.78 0.04 205)" strokeWidth="2.5" strokeLinecap="round" />
                </svg>
            )}
        </div>
    );
}

/**
 * Hero carousel for the Bio-Digital landing page: three auto-advancing slides
 * with eyebrow, emphasised heading, copy, dual CTAs and a brand visual, plus a
 * trust strip. Autoplay pauses on hover and respects reduced-motion.
 */
export default function HeroSlider() {
    const { t } = useTranslation();
    const [current, setCurrent] = useState(0);
    const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const goTo = useCallback((index: number) => {
        setCurrent((index + SLIDES.length) % SLIDES.length);
    }, []);
    const next = useCallback(() => goTo(current + 1), [current, goTo]);
    const prev = useCallback(() => goTo(current - 1), [current, goTo]);

    const stop = useCallback(() => {
        if (timerRef.current) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const start = useCallback(() => {
        const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        if (reduce || SLIDES.length <= 1) {
            return;
        }
        stop();
        timerRef.current = setInterval(() => {
            setCurrent((c) => (c + 1) % SLIDES.length);
        }, AUTOPLAY_MS);
    }, [stop]);

    useEffect(() => {
        start();
        return stop;
    }, [start, stop]);

    return (
        <section className="mx-auto max-w-[1140px] px-5 pb-12 pt-8 sm:px-8 lg:px-10 lg:pb-16">
            <div
                className="relative min-h-[420px] overflow-hidden rounded-2xl border border-bd-line bg-bd-surface sm:min-h-[460px] lg:min-h-[520px]"
                aria-roledescription="carousel"
                onMouseEnter={stop}
                onMouseLeave={start}
            >
                {SLIDES.map((slide, index) => (
                    <article
                        key={slide.id}
                        aria-label={`${index + 1} / ${SLIDES.length}`}
                        aria-hidden={index !== current}
                        className={`absolute inset-0 grid grid-cols-1 transition-opacity duration-700 md:grid-cols-[1.05fr_0.95fr] ${
                            index === current ? 'visible opacity-100' : 'invisible opacity-0'
                        }`}
                    >
                        <div className="flex flex-col justify-center p-8 sm:p-12 lg:p-16">
                            <span className="mb-4 flex items-center gap-2.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
                                <span className="h-0.5 w-6 rounded bg-bd-brand" aria-hidden="true" />
                                {t(slide.eyebrow)}
                            </span>
                            <h2 className="font-display text-[clamp(1.9rem,3.6vw,2.9rem)] font-semibold leading-tight tracking-tight text-bd-ink">
                                <Trans
                                    i18nKey={slide.title}
                                    components={{ 1: <em className="not-italic text-bd-brand-deep" /> }}
                                />
                            </h2>
                            <p className="mt-4 max-w-[42ch] text-[1.06rem] text-bd-ink-2">{t(slide.desc)}</p>
                            <div className="mt-7 flex flex-wrap gap-3">
                                <a
                                    href={slide.cta1.href}
                                    className="inline-flex items-center rounded-[10px] bg-bd-brand px-5 py-2.5 text-[14.5px] font-semibold text-white transition-all hover:-translate-y-px hover:bg-bd-brand-deep"
                                >
                                    {t(slide.cta1.key)}
                                </a>
                                <a
                                    href={slide.cta2.href}
                                    className="inline-flex items-center rounded-[10px] border border-bd-line bg-bd-surface px-5 py-2.5 text-[14.5px] font-semibold text-bd-ink transition-colors hover:border-bd-brand hover:text-bd-brand-deep"
                                >
                                    {t(slide.cta2.key)}
                                </a>
                            </div>
                        </div>
                        <SlideVisualPanel visual={slide.visual} />
                    </article>
                ))}

                <div className="absolute bottom-7 left-8 z-[5] flex gap-2.5 sm:left-12 lg:left-16" role="tablist" aria-label="Slides">
                    {SLIDES.map((slide, index) => (
                        <button
                            key={slide.id}
                            type="button"
                            role="tab"
                            aria-current={index === current}
                            aria-label={`${index + 1} / ${SLIDES.length}`}
                            onClick={() => {
                                goTo(index);
                                start();
                            }}
                            className={`h-1 rounded transition-all ${
                                index === current ? 'w-11 bg-bd-brand' : 'w-[30px] bg-bd-line'
                            }`}
                        />
                    ))}
                </div>

                <div className="absolute bottom-4 right-4 z-[5] flex gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            prev();
                            start();
                        }}
                        aria-label="Previous slide"
                        className="grid h-[38px] w-[38px] place-items-center rounded-[10px] border border-bd-line bg-bd-surface/80 text-bd-ink-2 transition-colors hover:border-bd-brand hover:text-bd-brand-deep"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            next();
                            start();
                        }}
                        aria-label="Next slide"
                        className="grid h-[38px] w-[38px] place-items-center rounded-[10px] border border-bd-line bg-bd-surface/80 text-bd-ink-2 transition-colors hover:border-bd-brand hover:text-bd-brand-deep"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div className="mt-8 flex flex-wrap items-center gap-x-9 gap-y-3.5 text-[13.5px] text-bd-ink-3">
                {TRUST.map((item) => (
                    <span key={item.key}>
                        <b className="font-display font-semibold text-bd-ink">{item.value}</b> {t(item.key)}
                    </span>
                ))}
            </div>
        </section>
    );
}
