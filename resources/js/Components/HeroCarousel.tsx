import { useState, useEffect } from 'react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export interface HeroSlide {
    id: number;
    title: string;
    description: string;
    media_type: 'image' | 'video';
    media_url: string;
    cta_text?: string;
    cta_link?: string;
    overlay_opacity?: number;
}

interface HeroCarouselProps {
    slides: HeroSlide[];
    autoPlayInterval?: number;
}

export default function HeroCarousel({ slides, autoPlayInterval = 5000 }: HeroCarouselProps) {
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    const nextSlide = () => {
        setCurrentSlide((prev) => (prev + 1) % slides.length);
    };

    const prevSlide = () => {
        setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
    };

    const goToSlide = (index: number) => {
        setCurrentSlide(index);
    };

    useEffect(() => {
        if (!isAutoPlaying || slides.length <= 1) return;

        const interval = setInterval(() => {
            nextSlide();
        }, autoPlayInterval);

        return () => clearInterval(interval);
    }, [currentSlide, isAutoPlaying, autoPlayInterval, slides.length]);

    if (slides.length === 0) {
        return null;
    }

    const currentSlideData = slides[currentSlide];

    return (
        <div
            className="relative w-full h-screen overflow-hidden"
            onMouseEnter={() => setIsAutoPlaying(false)}
            onMouseLeave={() => setIsAutoPlaying(true)}
        >
            {/* Slides */}
            {slides.map((slide, index) => (
                <div
                    key={slide.id}
                    className={cn(
                        "absolute inset-0 transition-opacity duration-1000",
                        index === currentSlide ? "opacity-100 z-10" : "opacity-0 z-0"
                    )}
                >
                    {/* Media */}
                    <div className="absolute inset-0">
                        {slide.media_type === 'video' ? (
                            <video
                                src={slide.media_url}
                                className="w-full h-full object-cover"
                                autoPlay
                                loop
                                muted
                                playsInline
                            />
                        ) : (
                            <img
                                src={slide.media_url}
                                alt={slide.title}
                                className="w-full h-full object-cover"
                            />
                        )}
                    </div>

                    {/* Overlay */}
                    <div
                        className="absolute inset-0 bg-black/50"
                        style={{ opacity: slide.overlay_opacity || 0.5 }}
                    />

                    {/* Content */}
                    <div className="absolute inset-0 flex items-center justify-center z-20">
                        <div className="container mx-auto px-4 text-center text-white">
                            <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 animate-in fade-in slide-in-from-bottom-4 duration-1000">
                                {slide.title}
                            </h1>
                            <p className="text-lg md:text-xl lg:text-2xl mb-8 max-w-3xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-200">
                                {slide.description}
                            </p>
                            {/* {slide.cta_text && slide.cta_link && (
                                <div className="flex flex-wrap gap-4 justify-center animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-300">
                                    <Button
                                        size="lg"
                                        className="bg-primary hover:bg-primary/90 text-lg px-8 py-6"
                                        asChild
                                    >
                                        <a href={slide.cta_link}>{slide.cta_text}</a>
                                    </Button>
                                </div>
                            )} */}
                        </div>
                    </div>
                </div>
            ))}

            {/* Navigation Arrows */}
            {slides.length > 1 && (
                <>
                    <button
                        onClick={prevSlide}
                        className="absolute left-4 top-1/2 -translate-y-1/2 z-30 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 hover:scale-110"
                        aria-label="Previous slide"
                    >
                        <ChevronLeftIcon className="h-6 w-6" />
                    </button>
                    <button
                        onClick={nextSlide}
                        className="absolute right-4 top-1/2 -translate-y-1/2 z-30 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 hover:scale-110"
                        aria-label="Next slide"
                    >
                        <ChevronRightIcon className="h-6 w-6" />
                    </button>
                </>
            )}

            {/* Indicators */}
            {slides.length > 1 && (
                <div className="absolute bottom-8 left-0 right-0 z-30 flex justify-center gap-2">
                    {slides.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => goToSlide(index)}
                            className={cn(
                                "h-2 rounded-full transition-all duration-300",
                                index === currentSlide
                                    ? "w-12 bg-white"
                                    : "w-2 bg-white/50 hover:bg-white/75"
                            )}
                            aria-label={`Go to slide ${index + 1}`}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
