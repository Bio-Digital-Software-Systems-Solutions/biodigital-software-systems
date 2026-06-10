import { Badge } from '@/Components/ui/badge';
import {
    ChatBubbleLeftRightIcon,
    ShieldCheckIcon,
    CurrencyEuroIcon,
    BoltIcon,
    GlobeEuropeAfricaIcon,
    HandRaisedIcon,
    CheckBadgeIcon,
    MapPinIcon,
} from '@heroicons/react/24/outline';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { PageProps } from '@/Types';
import FeatureSectionItem from './FeatureSectionItem';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';
import type { ComponentType, SVGProps } from 'react';

const ICON_MAP: Record<string, ComponentType<SVGProps<SVGSVGElement>>> = {
    ChatBubbleLeftRightIcon,
    ShieldCheckIcon,
    CurrencyEuroIcon,
    BoltIcon,
    GlobeEuropeAfricaIcon,
    HandRaisedIcon,
    CheckBadgeIcon,
    MapPinIcon,
};

export interface FeaturesContent {
    badge?: string;
    heading?: string;
    subtitle?: string;
    items?: Array<{
        icon: string;
        iconColor?: string;
        title: string;
        description: string;
    }>;
}

interface FeaturesSectionProps {
    content?: FeaturesContent;
    design?: DesignSettings | null;
}

const defaultFeatureDefinitions = [
    { icon: ChatBubbleLeftRightIcon, iconColor: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400', key: 'communication' },
    { icon: ShieldCheckIcon, iconColor: 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400', key: 'gdpr' },
    { icon: CurrencyEuroIcon, iconColor: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400', key: 'pricing' },
    { icon: BoltIcon, iconColor: 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400', key: 'flexibility' },
    { icon: GlobeEuropeAfricaIcon, iconColor: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400', key: 'hosting' },
    { icon: HandRaisedIcon, iconColor: 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400', key: 'partnership' },
    { icon: CheckBadgeIcon, iconColor: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400', key: 'quality' },
    { icon: MapPinIcon, iconColor: 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400', key: 'location' },
];

export default function FeaturesSection({ content, design }: FeaturesSectionProps = {}) {
    const appName = usePage<PageProps>().props.app.name;
    const { t } = useTranslation();
    const resolved = resolveDesign(design);

    const badge = content?.badge ?? t('home.features.badge');
    const heading = content?.heading ?? t('home.features.heading', { appName });
    const subtitle = content?.subtitle ?? t('home.features.subtitle');

    const defaultFeatures = defaultFeatureDefinitions.map((feature) => ({
        icon: feature.icon,
        iconColor: feature.iconColor,
        title: t(`home.features.items.${feature.key}.title`),
        description: t(`home.features.items.${feature.key}.desc`),
    }));

    const items = content?.items
        ? content.items.map((item) => ({
              icon: ICON_MAP[item.icon] ?? ChatBubbleLeftRightIcon,
              iconColor: item.iconColor ?? 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
              title: item.title,
              description: item.description,
          }))
        : defaultFeatures;

    return (
        <section
            id="features"
            className={`bg-muted/50 ${resolved.sectionClass} ${resolved.hasPadding ? '' : 'py-16'}`}
            style={resolved.sectionStyle}
        >
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                <div className={`space-y-4 ${resolved.alignmentClass || 'text-center'}`}>
                    <Badge variant="secondary" className="mb-2">
                        {badge}
                    </Badge>
                    <h2
                        className={`font-bold tracking-tight ${
                            resolved.headingClass || 'text-3xl sm:text-4xl md:text-5xl'
                        }`}
                    >
                        {heading}
                    </h2>
                    <p
                        className={`mx-auto max-w-2xl text-muted-foreground ${
                            resolved.paragraphClass || 'text-lg'
                        }`}
                    >
                        {subtitle}
                    </p>
                </div>

                <div className="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {items.map((feature, index) => (
                        <FeatureSectionItem
                            key={index}
                            icon={feature.icon}
                            iconColor={feature.iconColor}
                            title={feature.title}
                            description={feature.description}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
