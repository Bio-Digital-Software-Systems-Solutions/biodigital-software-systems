import AboutSection, { type AboutContent } from './AboutSection';
import FeaturesSection, { type FeaturesContent } from './FeaturesSection';
import TrainingBrowseSection, { type TrainingContent } from './TrainingBrowseSection';
import ContactSection, { type ContactContent } from './ContactSection';
import CustomSection, { type CustomSubsection } from './CustomSection';
import type { DesignSettings } from '@/lib/sectionDesign';

interface GlobalStats {
    total_churches: number;
    total_countries: number;
    total_members: number;
    europe: number;
    africa: number;
    americas: number;
    asia: number;
    oceania: number;
}

export interface HomepageSectionData {
    id: number;
    uuid: string;
    key: string;
    type: 'about' | 'activities' | 'training' | 'contact' | 'custom';
    title: string | null;
    content: Record<string, unknown> | null;
    design_settings: DesignSettings | null;
    order: number;
    is_active: boolean;
    subsections?: CustomSubsection[];
}

interface SectionRendererProps {
    section: HomepageSectionData;
    globalStats: GlobalStats;
    isAuthenticated: boolean;
}

export default function SectionRenderer({ section, globalStats, isAuthenticated }: SectionRendererProps) {
    if (!section.is_active) {
        return null;
    }

    switch (section.type) {
        case 'about':
            return (
                <AboutSection
                    globalStats={globalStats}
                    content={(section.content ?? undefined) as AboutContent | undefined}
                    design={section.design_settings}
                />
            );
        case 'activities':
            return (
                <FeaturesSection
                    content={(section.content ?? undefined) as FeaturesContent | undefined}
                    design={section.design_settings}
                />
            );
        case 'training':
            return (
                <TrainingBrowseSection
                    content={(section.content ?? undefined) as TrainingContent | undefined}
                    design={section.design_settings}
                />
            );
        case 'contact':
            return (
                <ContactSection
                    isAuthenticated={isAuthenticated}
                    content={(section.content ?? undefined) as ContactContent | undefined}
                    design={section.design_settings}
                />
            );
        case 'custom':
            return (
                <CustomSection
                    section={{
                        ...section,
                        type: 'custom',
                        content: (section.content ?? null) as {
                            badge?: string;
                            heading?: string;
                            subtitle?: string;
                        } | null,
                    }}
                />
            );
        default:
            return null;
    }
}
