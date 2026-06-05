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
                'title' => 'À propos',
                'content' => [
                    'badge' => 'À propos',
                    'heading' => "Une famille d'églises qui transforme des vies",
                    'paragraphs' => [
                        "L'Impact Centre Chrétien (ICC) est une famille d'églises charismatiques fondées en France en 2002 par les pasteurs Yves et Yvan Castanou, qui vise à former des disciples pour qu'ils exercent une influence positive dans la société.",
                        "L'ICC diffuse son message par les médias et les nouvelles technologies, propose des formations pour le développement spirituel et met en œuvre des actions humanitaires via sa branche Impact Sans Frontières (ISF).",
                    ],
                    'image_url' => '/vision_missions_icc.png',
                    'mission_blocks' => [
                        [
                            'title' => 'Former des disciples',
                            'body' => "L'objectif principal est de former des chrétiens qui influencent leur environnement.",
                            'color' => 'primary',
                        ],
                        [
                            'title' => "L'impact sur le monde",
                            'body' => "L'ICC veut avoir un impact positif sur la société, en accord avec les plans de Dieu, en créant de bons résultats.",
                            'color' => 'green',
                        ],
                        [
                            'title' => "L'église sans barrière",
                            'body' => "La diffusion du message de l'église par les médias et les nouvelles technologies vise à toucher un large public, sans frontières.",
                            'color' => 'purple',
                        ],
                    ],
                    'stats' => [
                        ['value' => '2002', 'label' => 'Année de fondation', 'color' => 'primary'],
                        ['value' => 'Global', 'label' => 'Présence mondiale', 'color' => 'green'],
                        ['value' => 'FPF', 'label' => 'Membre officiel', 'color' => 'purple'],
                    ],
                    'affiliations' => [
                        ['label' => 'Membre de la Fédération protestante de France (FPF)', 'color' => 'primary'],
                        ['label' => "Affilié à la Communauté des Églises d'expressions africaines en France (CEAF)", 'color' => 'green'],
                    ],
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
