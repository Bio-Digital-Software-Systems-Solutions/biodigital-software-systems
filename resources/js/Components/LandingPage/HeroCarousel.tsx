import type { CSSProperties } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade, Navigation, Pagination } from 'swiper/modules';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

interface HeroSlideDef {
    id: number;
    image: string;
    eyebrow: string;
    /** Title key; the emphasised span is wrapped in <1></1> for <Trans>. */
    title: string;
    desc: string;
    cta1: { key: string; href: string };
    cta2: { key: string; href: string };
}

const SLIDES: HeroSlideDef[] = [
    {
        id: 1,
        image: '/1.png',
        eyebrow: 'home.hero.s1.eyebrow',
        title: 'home.hero.s1.title',
        desc: 'home.hero.s1.desc',
        cta1: { key: 'home.hero.s1.cta1', href: '#contact' },
        cta2: { key: 'home.hero.s1.cta2', href: '#services' },
    },
    {
        id: 2,
        image: '/2.png',
        eyebrow: 'home.hero.s2.eyebrow',
        title: 'home.hero.s2.title',
        desc: 'home.hero.s2.desc',
        cta1: { key: 'home.hero.s2.cta1', href: '#trainings' },
        cta2: { key: 'home.hero.s2.cta2', href: '#contact' },
    },
    {
        id: 3,
        image: '/3.png',
        eyebrow: 'home.hero.s3.eyebrow',
        title: 'home.hero.s3.title',
        desc: 'home.hero.s3.desc',
        cta1: { key: 'home.hero.s3.cta1', href: '#services' },
        cta2: { key: 'home.hero.s3.cta2', href: '#contact' },
    },
];

const TRUST = [
    { value: '8+', key: 'home.hero.trust.years' },
    { value: 'M.Sc.', key: 'home.hero.trust.bioinfo' },
    { value: 'ISTQB', key: 'home.hero.trust.trainer' },
    { value: 'München', key: 'home.hero.trust.location' },
];

const AUTOPLAY_MS = 6000;

/** Swiper reads these custom properties to theme its navigation & pagination. */
const swiperTheme = {
    '--swiper-navigation-color': '#ffffff',
    '--swiper-pagination-color': '#ffffff',
    '--swiper-pagination-bullet-inactive-color': '#ffffff',
    '--swiper-pagination-bullet-inactive-opacity': '0.45',
} as CSSProperties;

/**
 * Full-bleed hero carousel for the Bio-Digital landing page.
 *
 * Mirrors the photographic, full-viewport Swiper style of the reference
 * homepage: cross-fading slides with a dark overlay, centred headline, dual
 * CTAs, arrow navigation, pagination bullets and a scroll cue — themed with the
 * Bio-Digital brand tokens. Autoplay pauses on hover.
 */
export default function HeroCarousel() {
    const { t } = useTranslation();

    return (
        <section
            className="relative h-[78vh] min-h-[540px] w-full overflow-hidden bg-bd-deep lg:h-[calc(100vh-66px)]"
            style={swiperTheme}
            aria-roledescription="carousel"
        >
            <Swiper
                modules={[Navigation, Pagination, Autoplay, EffectFade]}
                effect="fade"
                fadeEffect={{ crossFade: true }}
                slidesPerView={1}
                loop={SLIDES.length > 1}
                navigation={{ prevEl: '.hero-prev', nextEl: '.hero-next' }}
                pagination={{ el: '.hero-pagination', clickable: true }}
                autoplay={{
                    delay: AUTOPLAY_MS,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                }}
                className="h-full w-full"
            >
                {SLIDES.map((slide, index) => (
                    <SwiperSlide key={slide.id}>
                        <div className="relative h-full w-full">
                            <img
                                src={slide.image}
                                alt=""
                                aria-hidden="true"
                                className="absolute inset-0 h-full w-full object-cover"
                                loading={index === 0 ? 'eager' : 'lazy'}
                            />
                            <div className="absolute inset-0 bg-gradient-to-b from-bd-deep/80 via-bd-deep/60 to-bd-deep/85" />

                            <div className="relative mx-auto flex h-full max-w-4xl flex-col items-center justify-center px-5 text-center text-white sm:px-8">
                                <span className="mb-5 inline-flex items-center gap-2.5 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] backdrop-blur-sm">
                                    <span className="h-0.5 w-5 rounded bg-bd-accent" aria-hidden="true" />
                                    {t(slide.eyebrow)}
                                </span>
                                <h1 className="font-display text-[clamp(2rem,5vw,3.6rem)] font-semibold leading-tight tracking-tight">
                                    <Trans
                                        i18nKey={slide.title}
                                        components={{ 1: <em className="not-italic text-bd-accent" /> }}
                                    />
                                </h1>
                                <p className="mt-5 max-w-2xl text-[1.05rem] leading-relaxed text-white/85 sm:text-[1.15rem]">
                                    {t(slide.desc)}
                                </p>
                                <div className="mt-8 flex flex-col items-center gap-3 sm:flex-row">
                                    <a
                                        href={slide.cta1.href}
                                        className="inline-flex w-full items-center justify-center rounded-[10px] bg-bd-brand px-6 py-3 text-[15px] font-semibold text-white shadow-lg transition-all hover:-translate-y-px hover:bg-bd-brand-deep sm:w-auto"
                                    >
                                        {t(slide.cta1.key)}
                                    </a>
                                    <a
                                        href={slide.cta2.href}
                                        className="inline-flex w-full items-center justify-center rounded-[10px] border-2 border-white/70 px-6 py-3 text-[15px] font-semibold text-white transition-colors hover:bg-white hover:text-bd-deep sm:w-auto"
                                    >
                                        {t(slide.cta2.key)}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </SwiperSlide>
                ))}
            </Swiper>

            <button
                type="button"
                aria-label="Previous slide"
                className="hero-prev absolute left-4 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20 sm:left-6"
            >
                <ChevronLeft className="h-5 w-5" />
            </button>
            <button
                type="button"
                aria-label="Next slide"
                className="hero-next absolute right-4 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20 sm:right-6"
            >
                <ChevronRight className="h-5 w-5" />
            </button>

            <div className="hero-pagination absolute bottom-8 left-1/2 z-10 flex -translate-x-1/2 justify-center gap-2" />

            <div className="absolute inset-x-0 bottom-0 z-[5] flex flex-wrap items-center justify-center gap-x-8 gap-y-2 border-t border-white/10 bg-bd-deep/40 px-5 py-3.5 text-[13px] text-white/80 backdrop-blur-sm">
                {TRUST.map((item) => (
                    <span key={item.key}>
                        <b className="font-display font-semibold text-white">{item.value}</b> {t(item.key)}
                    </span>
                ))}
            </div>
        </section>
    );
}
