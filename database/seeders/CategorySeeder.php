<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Worship',
                'description' => 'Articles related to worship, praise, and spiritual practices',
            ],
            [
                'name' => 'Course',
                'description' => 'Educational courses and learning materials',
            ],
            [
                'name' => 'Seminar',
                'description' => 'Seminar content and workshop materials',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
