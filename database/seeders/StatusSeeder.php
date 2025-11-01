04<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'pending',
                'description' => 'Task is waiting to be started',
                'color' => 'gray',
                'is_active' => true,
            ],
            [
                'name' => 'in_progress',
                'description' => 'Task is currently being worked on',
                'color' => 'blue',
                'is_active' => true,
            ],
            [
                'name' => 'under_review',
                'description' => 'Task is being reviewed before completion',
                'color' => 'yellow',
                'is_active' => true,
            ],
            [
                'name' => 'completed',
                'description' => 'Task has been successfully completed',
                'color' => 'green',
                'is_active' => true,
            ],
            [
                'name' => 'cancelled',
                'description' => 'Task has been cancelled and will not be completed',
                'color' => 'red',
                'is_active' => true,
            ],
            [
                'name' => 'on_hold',
                'description' => 'Task is temporarily paused',
                'color' => 'orange',
                'is_active' => true,
            ],
            [
                'name' => 'blocked',
                'description' => 'Task cannot proceed due to dependencies or issues',
                'color' => 'purple',
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $statusData) {
            Status::firstOrCreate(
                ['name' => $statusData['name']],
                $statusData
            );
        }
    }
}
