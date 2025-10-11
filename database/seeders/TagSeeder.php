<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'Programming',
                'description' => 'Articles related to programming and software development',
                'color' => 'blue',
            ],
            [
                'name' => 'JavaScript',
                'description' => 'JavaScript programming language and frameworks',
                'color' => 'yellow',
            ],
            [
                'name' => 'PHP',
                'description' => 'PHP programming language and frameworks',
                'color' => 'purple',
            ],
            [
                'name' => 'Laravel',
                'description' => 'Laravel PHP framework',
                'color' => 'red',
            ],
            [
                'name' => 'React',
                'description' => 'React JavaScript library',
                'color' => 'blue',
            ],
            [
                'name' => 'Tutorial',
                'description' => 'Step-by-step learning guides',
                'color' => 'green',
            ],
            [
                'name' => 'Best Practices',
                'description' => 'Industry standards and recommended practices',
                'color' => 'orange',
            ],
            [
                'name' => 'DevOps',
                'description' => 'Development operations and deployment',
                'color' => 'gray',
            ],
            [
                'name' => 'Database',
                'description' => 'Database design and management',
                'color' => 'indigo',
            ],
            [
                'name' => 'Testing',
                'description' => 'Software testing and quality assurance',
                'color' => 'pink',
            ],
            [
                'name' => 'Security',
                'description' => 'Application security and best practices',
                'color' => 'red',
            ],
            [
                'name' => 'Performance',
                'description' => 'Application performance optimization',
                'color' => 'green',
            ],
            [
                'name' => 'UI/UX',
                'description' => 'User interface and user experience design',
                'color' => 'purple',
            ],
            [
                'name' => 'API',
                'description' => 'Application programming interfaces',
                'color' => 'blue',
            ],
            [
                'name' => 'Mobile',
                'description' => 'Mobile application development',
                'color' => 'cyan',
            ],
        ];

        foreach ($tags as $tagData) {
            Tag::firstOrCreate(
                ['name' => $tagData['name']],
                $tagData
            );
        }
    }
}
