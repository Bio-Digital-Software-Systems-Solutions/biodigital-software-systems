<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingMaterial;
use Illuminate\Database\Seeder;

class TrainingMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $trainings = Training::all();

        if ($trainings->isEmpty()) {
            $this->command->warn('No trainings found. Please run TrainingSeeder first.');

            return;
        }

        $materialTemplates = [
            [
                'title' => 'Introduction à la formation',
                'type' => 'video',
                'duration' => '15 min',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            [
                'title' => 'Support de cours - Partie 1',
                'type' => 'pdf',
                'duration' => null,
                'url' => 'https://example.com/support-cours-1.pdf',
            ],
            [
                'title' => 'Présentation PowerPoint',
                'type' => 'powerpoint',
                'duration' => null,
                'url' => 'https://example.com/presentation.pptx',
            ],
            [
                'title' => 'Tutoriel pratique',
                'type' => 'video',
                'duration' => '45 min',
                'url' => 'https://www.youtube.com/watch?v=example',
            ],
            [
                'title' => 'Guide de référence',
                'type' => 'pdf',
                'duration' => null,
                'url' => 'https://example.com/guide-reference.pdf',
            ],
            [
                'title' => 'Exercices pratiques',
                'type' => 'pdf',
                'duration' => null,
                'url' => 'https://example.com/exercices.pdf',
            ],
            [
                'title' => 'Webinaire enregistré',
                'type' => 'video',
                'duration' => '1h 30min',
                'url' => 'https://www.youtube.com/watch?v=webinar',
            ],
            [
                'title' => 'Podcast - Interview d\'expert',
                'type' => 'audio',
                'duration' => '35 min',
                'url' => 'https://example.com/podcast-expert.mp3',
            ],
            [
                'title' => 'Étude de cas',
                'type' => 'pdf',
                'duration' => null,
                'url' => 'https://example.com/etude-cas.pdf',
            ],
            [
                'title' => 'Démonstration technique',
                'type' => 'video',
                'duration' => '25 min',
                'url' => 'https://www.youtube.com/watch?v=demo',
            ],
        ];

        foreach ($trainings as $training) {
            // Choisir 3-6 matériaux aléatoires pour chaque formation
            $numberOfMaterials = random_int(3, 6);
            $selectedMaterials = array_slice($materialTemplates, 0, $numberOfMaterials);

            // Mélanger pour plus de variété
            shuffle($selectedMaterials);

            foreach ($selectedMaterials as $index => $materialData) {
                TrainingMaterial::create([
                    'training_id' => $training->id,
                    'title' => $materialData['title'],
                    'type' => $materialData['type'],
                    'duration' => $materialData['duration'],
                    'url' => $materialData['url'],
                    'order' => $index + 1,
                ]);
            }
        }

        $this->command->info('Training materials seeded successfully!');
    }
}
