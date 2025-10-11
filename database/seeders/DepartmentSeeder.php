<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Responsible for managing technology infrastructure, software development, and digital solutions.',
                'budget' => 500000,
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Manages employee relations, recruitment, training, and organizational development.',
                'budget' => 250000,
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Handles financial planning, budgeting, accounting, and financial reporting.',
                'budget' => 300000,
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Develops marketing strategies, brand management, and customer engagement initiatives.',
                'budget' => 400000,
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Oversees daily operations, process improvement, and operational efficiency.',
                'budget' => 350000,
                'is_active' => true,
            ],
            [
                'name' => 'Research & Development',
                'code' => 'RND',
                'description' => 'Focuses on innovation, product development, and research initiatives.',
                'budget' => 600000,
                'is_active' => true,
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CS',
                'description' => 'Provides customer support, handles inquiries, and maintains customer satisfaction.',
                'budget' => 200000,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::create($departmentData);
        }

        // Assign department heads from existing users
        $departments = Department::all();
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Admin', 'ProjectManager', 'SuperAdmin']);
        })->get();

        if ($users->count() > 0) {
            foreach ($departments->take($users->count()) as $index => $department) {
                $department->update(['head_of_department' => $users[$index]->id]);
            }
        }
    }
}
