<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Development Team',
                'code' => 'DEV-001',
                'description' => 'Core development team responsible for building and maintaining applications.',
                'max_members' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'QA Testing Group',
                'code' => 'QA-001',
                'description' => 'Quality assurance team ensuring product reliability and performance.',
                'max_members' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Project Managers',
                'code' => 'PM-001',
                'description' => 'Project management team coordinating cross-functional initiatives.',
                'max_members' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Design Team',
                'code' => 'DES-001',
                'description' => 'Creative team handling UI/UX design and visual assets.',
                'max_members' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Data Analytics',
                'code' => 'DA-001',
                'description' => 'Data science team analyzing business metrics and user behavior.',
                'max_members' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'DevOps Team',
                'code' => 'DEVOPS-001',
                'description' => 'Infrastructure and deployment team managing CI/CD and cloud services.',
                'max_members' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Support Team',
                'code' => 'SUP-001',
                'description' => 'Customer support team handling user inquiries and technical issues.',
                'max_members' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($groups as $groupData) {
            Group::create($groupData);
        }

        // Assign group leaders from existing users
        $groups = Group::all();
        $users = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'project-manager', 'event-manager', 'super-admin']);
        })->get();

        if ($users->count() > 0) {
            foreach ($groups->take($users->count()) as $index => $group) {
                $group->update(['leader_id' => $users[$index]->id]);
            }
        }
    }
}
