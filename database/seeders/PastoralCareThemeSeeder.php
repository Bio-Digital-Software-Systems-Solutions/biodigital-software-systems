<?php

namespace Database\Seeders;

use App\Models\PastoralCareTheme;
use Illuminate\Database\Seeder;

class PastoralCareThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Accompagnement spirituel',
                'slug' => 'spiritual-guidance',
                'description' => 'Soutien et accompagnement dans votre cheminement spirituel et votre relation avec Dieu.',
                'color' => '#6366f1',
                'icon' => 'heart',
                'sort_order' => 1,
            ],
            [
                'name' => 'Accompagnement de deuil',
                'slug' => 'grief-counseling',
                'description' => 'Soutien pour traverser les moments difficiles liés à la perte d\'un être cher.',
                'color' => '#8b5cf6',
                'icon' => 'cloud',
                'sort_order' => 2,
            ],
            [
                'name' => 'Conseil conjugal',
                'slug' => 'marriage-counseling',
                'description' => 'Accompagnement pour les couples dans leur relation et leur vie de couple.',
                'color' => '#ec4899',
                'icon' => 'users',
                'sort_order' => 3,
            ],
            [
                'name' => 'Questions familiales',
                'slug' => 'family-issues',
                'description' => 'Soutien pour les défis familiaux, relations parents-enfants et dynamiques familiales.',
                'color' => '#14b8a6',
                'icon' => 'home',
                'sort_order' => 4,
            ],
            [
                'name' => 'Questions de foi',
                'slug' => 'faith-questions',
                'description' => 'Exploration des questions sur la foi, les doutes et l\'approfondissement de la connaissance biblique.',
                'color' => '#f59e0b',
                'icon' => 'book-open',
                'sort_order' => 5,
            ],
            [
                'name' => 'Soutien en situation de crise',
                'slug' => 'crisis-support',
                'description' => 'Aide urgente pour faire face à des situations de crise personnelle ou professionnelle.',
                'color' => '#ef4444',
                'icon' => 'alert-triangle',
                'sort_order' => 6,
            ],
            [
                'name' => 'Demande de prière',
                'slug' => 'prayer-request',
                'description' => 'Moment de prière et d\'intercession pour vos besoins spécifiques.',
                'color' => '#3b82f6',
                'icon' => 'sparkles',
                'sort_order' => 7,
            ],
            [
                'name' => 'Préparation au baptême',
                'slug' => 'baptism-preparation',
                'description' => 'Accompagnement pour préparer votre baptême ou celui d\'un proche.',
                'color' => '#06b6d4',
                'icon' => 'droplets',
                'sort_order' => 8,
            ],
            [
                'name' => 'Préparation au mariage',
                'slug' => 'marriage-preparation',
                'description' => 'Sessions de préparation au mariage chrétien.',
                'color' => '#d946ef',
                'icon' => 'heart-handshake',
                'sort_order' => 9,
            ],
            [
                'name' => 'Croissance spirituelle',
                'slug' => 'spiritual-growth',
                'description' => 'Mentorat et accompagnement pour approfondir votre vie spirituelle.',
                'color' => '#10b981',
                'icon' => 'trending-up',
                'sort_order' => 10,
            ],
            [
                'name' => 'Conseils pour les jeunes',
                'slug' => 'youth-counseling',
                'description' => 'Soutien et conseils adaptés aux défis spécifiques des jeunes.',
                'color' => '#84cc16',
                'icon' => 'graduation-cap',
                'sort_order' => 11,
            ],
            [
                'name' => 'Problèmes professionnels',
                'slug' => 'professional-issues',
                'description' => 'Conseils et prière pour les défis liés au travail et à la carrière.',
                'color' => '#64748b',
                'icon' => 'briefcase',
                'sort_order' => 12,
            ],
            [
                'name' => 'Autre',
                'slug' => 'other',
                'description' => 'Tout autre sujet non listé ci-dessus.',
                'color' => '#78716c',
                'icon' => 'message-circle',
                'sort_order' => 99,
            ],
        ];

        foreach ($themes as $themeData) {
            PastoralCareTheme::updateOrCreate(
                ['slug' => $themeData['slug']],
                $themeData
            );
        }
    }
}
