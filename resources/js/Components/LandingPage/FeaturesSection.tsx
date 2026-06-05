import { Badge } from '@/Components/ui/badge';
import {
    CalendarDaysIcon,
    SunIcon,
    HomeModernIcon,
    SparklesIcon,
    MegaphoneIcon,
    AcademicCapIcon,
    HeartIcon,
    UserGroupIcon,
} from '@heroicons/react/24/outline';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types';
import FeatureSectionItem from './FeatureSectionItem';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';
import type { ComponentType, SVGProps } from 'react';

const ICON_MAP: Record<string, ComponentType<SVGProps<SVGSVGElement>>> = {
    CalendarDaysIcon,
    SunIcon,
    HomeModernIcon,
    SparklesIcon,
    MegaphoneIcon,
    AcademicCapIcon,
    HeartIcon,
    UserGroupIcon,
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

const features = [
    {
        icon: CalendarDaysIcon,
        iconColor: 'bg-icc-blue/10 text-icc-blue',
        title: "Cultes dominicaux",
        description: "Rejoignez-nous chaque dimanche à 10h pour un temps de louange, d'adoration et d'enseignement biblique inspirant."
    },
    {
        icon: SunIcon,
        iconColor: 'bg-icc-purple/10 text-icc-purple',
        title: 'Matinales de prière',
        description: 'Commencez votre journée avec  Dieu par des prieres matinales du lundi au vendredi de 05:00 á 06:00'
    },
    {
        icon: HomeModernIcon,
        iconColor: 'bg-icc-red/10 text-icc-red',
        title: "Famille d'Impact (FI)",
        description: 'Participez aux réunions de prière et d\'étude biblique en petits groupes chaque semaine dans nos différentes FI.'
    },
    {
        icon: SparklesIcon,
        iconColor: 'bg-icc-yellow/10 text-icc-yellow',
        title: 'Atmosphère de Gloire (ADG)',
        description: 'Vivez une expérience de louange et d\'adoration intense chaque vendredi de 19h00 á 21h00 dans une ADG.'
    },
    {
        icon: MegaphoneIcon,
        iconColor: 'bg-icc-lime/10 text-icc-lime',
        title: "Sortie d'evangélisation",
        description: 'Participez à nos sorties d\'évangélisation hebdomadaire seul ou en groupe pour partager l\'évangile et gagner des âmes á Christ.'
    },
    {
        icon: AcademicCapIcon,
        iconColor: 'bg-icc-blue/10 text-icc-blue',
        title: 'Formations Bibliques',
        description: 'Inscrivez-vous à nos parcours de croissance de la nouvelle creation (PCNC) en ligne ou en présentiel pour approfondir votre connaissance de la Bible et grandir dans votre foi.'
    },
    {
        icon: HeartIcon,
        iconColor: 'bg-icc-purple/10 text-icc-purple',
        title: 'Care Services',
        description: 'Bénéficiez de conseils et d\'accompagnement spirituel personnalisé par notre équipe care service pour vous aider dans votre marche avec Dieu.'
    },
    {
        icon: UserGroupIcon,
        iconColor: 'bg-icc-red/10 text-icc-red',
        title: "Groupe d'Impact (GI)",
        description: "des cadres d'echange et de partage pour des  hommes, des femmes et des jeunes adultes visant à nous encourager et nous équiper mutuellement dans notre vie chrétienne."
    }
];

export default function FeaturesSection({ content, design }: FeaturesSectionProps = {}) {
    const appName = usePage<PageProps>().props.app.name;
    const resolved = resolveDesign(design);

    const badge = content?.badge ?? 'Activités';
    const heading = content?.heading ?? `Nos Activitités á ${appName}`;
    const subtitle =
        content?.subtitle ??
        `Découvrez les diverses activités et ministères qui font de ${appName} une communauté vivante et engagée.`;

    const items = content?.items
        ? content.items.map((item) => ({
              icon: ICON_MAP[item.icon] ?? CalendarDaysIcon,
              iconColor: item.iconColor ?? 'bg-icc-blue/10 text-icc-blue',
              title: item.title,
              description: item.description,
          }))
        : features;

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
