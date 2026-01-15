<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Database\Seeder;

class DepartmentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define positions for each department type
        $positionsByDepartment = [
            'IT' => [
                ['name' => 'Développeur Senior', 'code' => 'DEV-SR', 'color' => '#3B82F6', 'hourly_rate' => 45.00],
                ['name' => 'Développeur Junior', 'code' => 'DEV-JR', 'color' => '#60A5FA', 'hourly_rate' => 25.00],
                ['name' => 'Chef de Projet', 'code' => 'PM', 'color' => '#8B5CF6', 'hourly_rate' => 50.00],
                ['name' => 'Administrateur Système', 'code' => 'SYS-ADMIN', 'color' => '#10B981', 'hourly_rate' => 40.00],
                ['name' => 'Support Technique', 'code' => 'SUPPORT', 'color' => '#F59E0B', 'hourly_rate' => 22.00],
            ],
            'HR' => [
                ['name' => 'Responsable RH', 'code' => 'HR-MGR', 'color' => '#EC4899', 'hourly_rate' => 45.00],
                ['name' => 'Chargé de Recrutement', 'code' => 'RECRUITER', 'color' => '#F472B6', 'hourly_rate' => 30.00],
                ['name' => 'Assistant RH', 'code' => 'HR-ASST', 'color' => '#FB7185', 'hourly_rate' => 22.00],
                ['name' => 'Formateur', 'code' => 'TRAINER', 'color' => '#A855F7', 'hourly_rate' => 35.00],
            ],
            'FIN' => [
                ['name' => 'Directeur Financier', 'code' => 'CFO', 'color' => '#059669', 'hourly_rate' => 60.00],
                ['name' => 'Comptable', 'code' => 'ACCOUNTANT', 'color' => '#10B981', 'hourly_rate' => 35.00],
                ['name' => 'Contrôleur de Gestion', 'code' => 'CONTROLLER', 'color' => '#34D399', 'hourly_rate' => 40.00],
                ['name' => 'Assistant Comptable', 'code' => 'ACCT-ASST', 'color' => '#6EE7B7', 'hourly_rate' => 22.00],
            ],
            'MKT' => [
                ['name' => 'Directeur Marketing', 'code' => 'MKT-DIR', 'color' => '#DC2626', 'hourly_rate' => 55.00],
                ['name' => 'Chef de Produit', 'code' => 'PRODUCT-MGR', 'color' => '#EF4444', 'hourly_rate' => 40.00],
                ['name' => 'Community Manager', 'code' => 'CM', 'color' => '#F87171', 'hourly_rate' => 28.00],
                ['name' => 'Graphiste', 'code' => 'DESIGNER', 'color' => '#FCA5A5', 'hourly_rate' => 30.00],
                ['name' => 'Chargé de Communication', 'code' => 'COMM', 'color' => '#FECACA', 'hourly_rate' => 28.00],
            ],
            'OPS' => [
                ['name' => 'Responsable Opérations', 'code' => 'OPS-MGR', 'color' => '#7C3AED', 'hourly_rate' => 45.00],
                ['name' => 'Coordinateur', 'code' => 'COORD', 'color' => '#8B5CF6', 'hourly_rate' => 30.00],
                ['name' => 'Agent Logistique', 'code' => 'LOGISTICS', 'color' => '#A78BFA', 'hourly_rate' => 22.00],
                ['name' => 'Superviseur', 'code' => 'SUPERVISOR', 'color' => '#C4B5FD', 'hourly_rate' => 32.00],
            ],
            'RND' => [
                ['name' => 'Directeur R&D', 'code' => 'RND-DIR', 'color' => '#0891B2', 'hourly_rate' => 60.00],
                ['name' => 'Chercheur', 'code' => 'RESEARCHER', 'color' => '#06B6D4', 'hourly_rate' => 45.00],
                ['name' => 'Ingénieur', 'code' => 'ENGINEER', 'color' => '#22D3EE', 'hourly_rate' => 40.00],
                ['name' => 'Technicien de Laboratoire', 'code' => 'LAB-TECH', 'color' => '#67E8F9', 'hourly_rate' => 28.00],
            ],
            'CS' => [
                ['name' => 'Responsable Service Client', 'code' => 'CS-MGR', 'color' => '#EA580C', 'hourly_rate' => 40.00],
                ['name' => 'Agent Service Client', 'code' => 'CS-AGENT', 'color' => '#F97316', 'hourly_rate' => 20.00],
                ['name' => 'Téléconseiller', 'code' => 'ADVISOR', 'color' => '#FB923C', 'hourly_rate' => 18.00],
                ['name' => 'Superviseur Support', 'code' => 'SUPPORT-SUP', 'color' => '#FDBA74', 'hourly_rate' => 28.00],
            ],
        ];

        // Generic positions that can be added to any department
        $genericPositions = [
            ['name' => 'Stagiaire', 'code' => 'INTERN', 'color' => '#9CA3AF', 'hourly_rate' => 12.00],
            ['name' => 'Consultant', 'code' => 'CONSULTANT', 'color' => '#6B7280', 'hourly_rate' => 50.00],
            ['name' => 'Bénévole', 'code' => 'VOLUNTEER', 'color' => '#FFD700', 'hourly_rate' => null],
        ];

        $departments = Department::all();

        foreach ($departments as $department) {
            $sortOrder = 0;

            // Add department-specific positions
            if (isset($positionsByDepartment[$department->code])) {
                foreach ($positionsByDepartment[$department->code] as $positionData) {
                    DepartmentPosition::create([
                        'department_id' => $department->id,
                        'name' => $positionData['name'],
                        'code' => $positionData['code'],
                        'color' => $positionData['color'],
                        'hourly_rate' => $positionData['hourly_rate'],
                        'min_staff' => 1,
                        'is_active' => true,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            // Add generic positions to all departments
            foreach ($genericPositions as $positionData) {
                DepartmentPosition::create([
                    'department_id' => $department->id,
                    'name' => $positionData['name'],
                    'code' => $department->code . '-' . $positionData['code'],
                    'color' => $positionData['color'],
                    'hourly_rate' => $positionData['hourly_rate'],
                    'min_staff' => 1,
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}
