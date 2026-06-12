<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

class HomepageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'about',
                'type' => 'about',
                'order' => 1,
                'title' => 'Geschäftsidee',
                'content' => [
                    'badge' => 'À propos',
                    'heading' => "Bio-Digital Software Systems Solutions",
                    'paragraphs' => [
                        'Bio-Digital Software Systems Solutions steht für beschleunigte, KI-gestützte Softwareentwicklung, spezialisiert auf BioTech-Daten, Bioinformatik und Laborprozesse.',
                        'Wir schließen die Lücke zwischen biologischer Forschung und marktfähiger Software durch die einzigartige Kombination aus tiefem bioinformatischem Fachwissen und modernster Fullstack-Softwareentwicklung.',
                    ],
                    'image_url' => '/pc.png',
                ],
            ],
            [
                'key' => 'activities',
                'type' => 'activities',
                'order' => 2,
                'title' => 'Activités',
                'content' => [
                    'badge' => 'Activités',
                    'heading' => 'Nos Activités',
                    'subtitle' => 'Découvrez les diverses activités et ministères qui font de notre communauté un lieu vivant et engagé.',
                    'items' => [
                        ['icon' => 'CalendarDaysIcon', 'iconColor' => 'bg-icc-blue/10 text-icc-blue', 'title' => 'Cultes dominicaux', 'description' => "Rejoignez-nous chaque dimanche à 10h pour un temps de louange, d'adoration et d'enseignement biblique inspirant."],
                        ['icon' => 'SunIcon', 'iconColor' => 'bg-icc-purple/10 text-icc-purple', 'title' => 'Matinales de prière', 'description' => 'Commencez votre journée avec Dieu par des prières matinales du lundi au vendredi de 05:00 à 06:00.'],
                        ['icon' => 'HomeModernIcon', 'iconColor' => 'bg-icc-red/10 text-icc-red', 'title' => "Famille d'Impact (FI)", 'description' => "Participez aux réunions de prière et d'étude biblique en petits groupes chaque semaine dans nos différentes FI."],
                        ['icon' => 'SparklesIcon', 'iconColor' => 'bg-icc-yellow/10 text-icc-yellow', 'title' => 'Atmosphère de Gloire (ADG)', 'description' => "Vivez une expérience de louange et d'adoration intense chaque vendredi de 19h00 à 21h00 dans une ADG."],
                        ['icon' => 'MegaphoneIcon', 'iconColor' => 'bg-icc-lime/10 text-icc-lime', 'title' => "Sortie d'évangélisation", 'description' => "Participez à nos sorties d'évangélisation hebdomadaires seul ou en groupe pour partager l'évangile."],
                        ['icon' => 'AcademicCapIcon', 'iconColor' => 'bg-icc-blue/10 text-icc-blue', 'title' => 'Formations Bibliques', 'description' => 'Inscrivez-vous à nos parcours de croissance de la nouvelle création (PCNC) en ligne ou en présentiel.'],
                        ['icon' => 'HeartIcon', 'iconColor' => 'bg-icc-purple/10 text-icc-purple', 'title' => 'Care Services', 'description' => "Bénéficiez de conseils et d'accompagnement spirituel personnalisé par notre équipe care service."],
                        ['icon' => 'UserGroupIcon', 'iconColor' => 'bg-icc-red/10 text-icc-red', 'title' => "Groupe d'Impact (GI)", 'description' => "Des cadres d'échange et de partage pour hommes, femmes et jeunes adultes."],
                    ],
                ],
            ],
            [
                'key' => 'training',
                'type' => 'training',
                'order' => 3,
                'title' => 'Formations',
                'content' => [
                    'badge' => 'Formations',
                    'heading' => 'Nos Formations',
                    'subtitle' => 'Se construire par la parole de Dieu et des formations.',
                ],
            ],
            [
                'key' => 'contact',
                'type' => 'contact',
                'order' => 4,
                'title' => 'Contact',
                'content' => [
                    'badge' => 'Contact',
                    'heading' => 'Contactez-nous',
                    'subtitle' => "Une question, une suggestion ou besoin d'aide ? N'hésitez pas à nous contacter.",
                ],
            ],
        ];

        foreach ($defaults as $row) {
            HomepageSection::updateOrCreate(
                ['key' => $row['key']],
                array_merge($row, [
                    'design_settings' => [],
                    'is_active' => true,
                ]),
            );
        }
    }
}
