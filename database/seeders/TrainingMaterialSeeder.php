<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingMaterial;
use Illuminate\Database\Seeder;

class TrainingMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $trainings = Training::with('classes')->get();

        if ($trainings->isEmpty()) {
            $this->command->warn('No trainings found. Please run TrainingSeeder first.');

            return;
        }

        $materialTemplates = [
            ['title' => 'Introduction à la formation', 'type' => 'video', 'duration' => '15 min', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['title' => 'Support de cours - Partie 1', 'type' => 'pdf', 'duration' => null, 'url' => 'https://example.com/support-cours-1.pdf'],
            ['title' => 'Présentation PowerPoint', 'type' => 'powerpoint', 'duration' => null, 'url' => 'https://example.com/presentation.pptx'],
            ['title' => 'Tutoriel pratique', 'type' => 'video', 'duration' => '45 min', 'url' => 'https://www.youtube.com/watch?v=example'],
            ['title' => 'Guide de référence', 'type' => 'pdf', 'duration' => null, 'url' => 'https://example.com/guide-reference.pdf'],
            ['title' => 'Exercices pratiques', 'type' => 'pdf', 'duration' => null, 'url' => 'https://example.com/exercices.pdf'],
            ['title' => 'Webinaire enregistré', 'type' => 'video', 'duration' => '1h 30min', 'url' => 'https://www.youtube.com/watch?v=webinar'],
            ['title' => 'Podcast - Interview d\'expert', 'type' => 'audio', 'duration' => '35 min', 'url' => 'https://example.com/podcast-expert.mp3'],
            ['title' => 'Étude de cas', 'type' => 'pdf', 'duration' => null, 'url' => 'https://example.com/etude-cas.pdf'],
            ['title' => 'Démonstration technique', 'type' => 'video', 'duration' => '25 min', 'url' => 'https://www.youtube.com/watch?v=demo'],
        ];

        foreach ($trainings as $training) {
            $numberOfMaterials = random_int(3, 6);
            $selectedMaterials = array_slice($materialTemplates, 0, $numberOfMaterials);
            shuffle($selectedMaterials);

            $createdMaterials = [];
            foreach ($selectedMaterials as $index => $data) {
                $createdMaterials[] = TrainingMaterial::create([
                    'training_id' => $training->id,
                    'teacher_id' => $training->teacher_id,
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'duration' => $data['duration'],
                    'url' => $data['url'],
                    'order' => $index + 1,
                    'is_active' => true,
                ]);
            }

            // Attach each material to every class of the training. By default
            // each pivot is active; the seeder leaves room for tests/demos to
            // toggle is_active per class without rebuilding the catalogue.
            foreach ($training->classes as $class) {
                foreach ($createdMaterials as $index => $material) {
                    TrainingClassMaterial::create([
                        'training_class_id' => $class->id,
                        'training_material_id' => $material->id,
                        'teacher_id' => $class->teacher_id,
                        'is_active' => true,
                        'order' => $index + 1,
                    ]);
                }
            }
        }

        $this->command->info('Training materials seeded successfully!');
    }
}
