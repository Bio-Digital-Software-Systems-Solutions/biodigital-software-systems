<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingTopic;
use Illuminate\Database\Seeder;

class TrainingTopicSeeder extends Seeder
{
    public function run(): void
    {
        $trainings = Training::all();

        if ($trainings->isEmpty()) {
            $this->command->warn('No trainings found. Please run TrainingSeeder first.');

            return;
        }

        // Topics mappés par catégorie de formation
        $topicsByCategory = [
            'Développement Web' => [
                ['name' => 'HTML & CSS', 'description' => 'Maîtriser les fondamentaux du web design'],
                ['name' => 'JavaScript ES6+', 'description' => 'Programmation moderne avec JavaScript'],
                ['name' => 'React.js', 'description' => 'Créer des applications web interactives'],
                ['name' => 'Node.js & Express', 'description' => 'Backend avec JavaScript'],
                ['name' => 'Bases de données', 'description' => 'SQL et NoSQL pour vos applications'],
            ],
            'Data Science' => [
                ['name' => 'Python pour la data', 'description' => 'Maîtrise de Python et ses librairies'],
                ['name' => 'Statistiques descriptives', 'description' => 'Analyse statistique des données'],
                ['name' => 'Machine Learning supervisé', 'description' => 'Algorithmes d\'apprentissage supervisé'],
                ['name' => 'Deep Learning', 'description' => 'Réseaux de neurones et TensorFlow'],
                ['name' => 'Data Visualization', 'description' => 'Visualiser vos données avec Matplotlib et Seaborn'],
            ],
            'Design UI/UX' => [
                ['name' => 'Principes du design', 'description' => 'Fondamentaux du design d\'interface'],
                ['name' => 'Recherche utilisateur', 'description' => 'Méthodologies UX research'],
                ['name' => 'Prototypage', 'description' => 'Outils de prototypage (Figma, Sketch)'],
                ['name' => 'Design System', 'description' => 'Créer et maintenir un design system'],
            ],
            'Marketing Digital' => [
                ['name' => 'SEO & SEA', 'description' => 'Référencement naturel et payant'],
                ['name' => 'Social Media Marketing', 'description' => 'Stratégies sur les réseaux sociaux'],
                ['name' => 'Content Marketing', 'description' => 'Créer du contenu qui convertit'],
                ['name' => 'Analytics & Tracking', 'description' => 'Mesurer la performance de vos campagnes'],
            ],
            'Gestion de Projet' => [
                ['name' => 'Méthodologies Agile', 'description' => 'Scrum, Kanban et autres méthodes agiles'],
                ['name' => 'Outils de gestion', 'description' => 'Jira, Trello, Asana'],
                ['name' => 'Leadership', 'description' => 'Diriger et motiver une équipe'],
                ['name' => 'Gestion des risques', 'description' => 'Identifier et mitiger les risques'],
            ],
        ];

        // Topics génériques pour les formations sans catégorie spécifique
        $genericTopics = [
            ['name' => 'Introduction', 'description' => 'Découverte des concepts fondamentaux'],
            ['name' => 'Pratique et exercices', 'description' => 'Mise en application des connaissances'],
            ['name' => 'Projet final', 'description' => 'Projet de fin de formation'],
        ];

        foreach ($trainings as $training) {
            $category = $training->category;

            // Sélectionner les topics appropriés
            $topics = $topicsByCategory[$category] ?? $genericTopics;

            // Limiter à 3-5 topics par formation
            $numberOfTopics = rand(3, min(5, count($topics)));
            $selectedTopics = array_slice($topics, 0, $numberOfTopics);

            foreach ($selectedTopics as $index => $topicData) {
                TrainingTopic::create([
                    'training_id' => $training->id,
                    'name' => $topicData['name'],
                    'description' => $topicData['description'],
                    'order' => $index + 1,
                ]);
            }
        }

        $this->command->info('Training topics seeded successfully!');
    }
}
