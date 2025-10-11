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
import FeatureSectionItem from './FeatureSectionItem';

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
        title: 'Soins pastoraux',
        description: 'Bénéficiez de conseils et d\'accompagnement spirituel personnalisé par notre équipe pastorale pour vous aider dans votre marche avec Dieu.'
    },
    {
        icon: UserGroupIcon,
        iconColor: 'bg-icc-red/10 text-icc-red',
        title: "Groupe d'Impact (GI)",
        description: "des cadres d'echange et de partage pour des  hommes, des femmes et des jeunes adultes visant à nous encourager et nous équiper mutuellement dans notre vie chrétienne."
    }
];

export default function FeaturesSection() {
    return (
        <section id="features" className="py-16 bg-muted/50">
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center space-y-4">
                    <Badge variant="secondary" className="mb-2">
                        Activités
                    </Badge>
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
                        Nos Activitités á ICC München
                    </h2>
                    <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                        Découvrez les diverses activités et ministères qui font d'ICC München une communauté vivante et engagée.
                    </p>
                </div>

                <div className="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {features.map((feature, index) => (
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
