<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = Program::all();
        if ($programs->isEmpty()) {
            $this->call(ProgramSeeder::class);
            $programs = Program::all();
        }

        $statuses = Status::all();
        if ($statuses->isEmpty()) {
            $this->call(StatusSeeder::class);
            $statuses = Status::all();
        }

        $users = User::all();
        if ($users->isEmpty()) {
            throw new \Exception('Users must be seeded before tasks');
        }

        $digitalTransformation = $programs->where('name', 'Digital Transformation Initiative')->first();
        $customerExperience = $programs->where('name', 'Customer Experience Enhancement')->first();
        $dataAnalytics = $programs->where('name', 'Data Analytics Platform')->first();
        $security = $programs->where('name', 'Security Infrastructure Upgrade')->first();

        $pendingStatus = $statuses->where('name', 'pending')->first();
        $inProgressStatus = $statuses->where('name', 'in_progress')->first();
        $completedStatus = $statuses->where('name', 'completed')->first();
        $underReviewStatus = $statuses->where('name', 'under_review')->first();

        $tasks = [
            // Digital Transformation Initiative Tasks
            [
                'title' => 'Infrastructure Assessment',
                'description' => 'Conduct comprehensive assessment of current IT infrastructure to identify modernization opportunities and potential risks.',
                'due_date' => Carbon::now()->addDays(10),
                'priority' => 'high',
                'estimated_hours' => 80.0,
                'actual_hours' => 75.0,
                'notes' => 'Assessment completed on schedule. Identified 15 critical systems for immediate upgrade.',
                'status_id' => $completedStatus->id,
                'program_id' => $digitalTransformation->id,
            ],
            [
                'title' => 'Cloud Migration Planning',
                'description' => 'Develop detailed migration plan for moving core business applications to cloud infrastructure.',
                'due_date' => Carbon::now()->addDays(25),
                'priority' => 'high',
                'estimated_hours' => 120.0,
                'actual_hours' => 95.0,
                'notes' => 'Migration plan drafted, currently under stakeholder review.',
                'status_id' => $inProgressStatus->id,
                'program_id' => $digitalTransformation->id,
            ],
            [
                'title' => 'API Gateway Implementation',
                'description' => 'Set up enterprise API gateway for secure and efficient service communication.',
                'due_date' => Carbon::now()->addDays(45),
                'priority' => 'medium',
                'estimated_hours' => 160.0,
                'actual_hours' => null,
                'notes' => 'Waiting for infrastructure team availability.',
                'status_id' => $pendingStatus->id,
                'program_id' => $digitalTransformation->id,
            ],

            // Customer Experience Enhancement Tasks
            [
                'title' => 'User Journey Mapping',
                'description' => 'Map current customer journeys across all touchpoints to identify improvement opportunities.',
                'due_date' => Carbon::now()->addDays(15),
                'priority' => 'high',
                'estimated_hours' => 60.0,
                'actual_hours' => 58.0,
                'notes' => 'Journey maps completed for all major customer segments.',
                'status_id' => $completedStatus->id,
                'program_id' => $customerExperience->id,
            ],
            [
                'title' => 'Mobile App Redesign',
                'description' => 'Redesign mobile application interface based on user feedback and modern UX principles.',
                'due_date' => Carbon::now()->addDays(30),
                'priority' => 'high',
                'estimated_hours' => 200.0,
                'actual_hours' => 150.0,
                'notes' => 'Design phase completed, development in progress.',
                'status_id' => $inProgressStatus->id,
                'program_id' => $customerExperience->id,
            ],
            [
                'title' => 'Chatbot Integration',
                'description' => 'Integrate AI-powered chatbot for customer service automation and 24/7 support.',
                'due_date' => Carbon::now()->addDays(20),
                'priority' => 'medium',
                'estimated_hours' => 100.0,
                'actual_hours' => 95.0,
                'notes' => 'Chatbot tested and ready for deployment, awaiting final approval.',
                'status_id' => $underReviewStatus->id,
                'program_id' => $customerExperience->id,
            ],

            // Data Analytics Platform Tasks
            [
                'title' => 'Data Warehouse Design',
                'description' => 'Design and architect enterprise data warehouse for centralized data storage and analytics.',
                'due_date' => Carbon::now()->addDays(40),
                'priority' => 'high',
                'estimated_hours' => 180.0,
                'actual_hours' => null,
                'notes' => 'Initial requirements gathering completed.',
                'status_id' => $pendingStatus->id,
                'program_id' => $dataAnalytics->id,
            ],
            [
                'title' => 'ETL Pipeline Development',
                'description' => 'Develop Extract, Transform, Load pipelines for data integration from various sources.',
                'due_date' => Carbon::now()->addDays(60),
                'priority' => 'high',
                'estimated_hours' => 240.0,
                'actual_hours' => null,
                'notes' => 'Dependent on data warehouse completion.',
                'status_id' => $pendingStatus->id,
                'program_id' => $dataAnalytics->id,
            ],

            // Security Infrastructure Upgrade Tasks
            [
                'title' => 'Security Audit',
                'description' => 'Comprehensive security audit of current systems and identification of vulnerabilities.',
                'due_date' => Carbon::now()->addDays(5),
                'priority' => 'high',
                'estimated_hours' => 120.0,
                'actual_hours' => 115.0,
                'notes' => 'Audit completed, 23 critical vulnerabilities identified.',
                'status_id' => $completedStatus->id,
                'program_id' => $security->id,
            ],
            [
                'title' => 'Zero Trust Implementation',
                'description' => 'Implement zero trust security architecture across all network segments.',
                'due_date' => Carbon::now()->addDays(35),
                'priority' => 'high',
                'estimated_hours' => 300.0,
                'actual_hours' => 250.0,
                'notes' => 'Phase 1 implementation 80% complete.',
                'status_id' => $inProgressStatus->id,
                'program_id' => $security->id,
            ],
            [
                'title' => 'Employee Security Training',
                'description' => 'Develop and deliver comprehensive security awareness training for all employees.',
                'due_date' => Carbon::now()->addDays(20),
                'priority' => 'medium',
                'estimated_hours' => 80.0,
                'actual_hours' => 75.0,
                'notes' => 'Training materials completed, scheduling sessions.',
                'status_id' => $inProgressStatus->id,
                'program_id' => $security->id,
            ],

            // Additional cross-program tasks
            [
                'title' => 'Stakeholder Communication Plan',
                'description' => 'Develop communication strategy for keeping stakeholders informed of program progress.',
                'due_date' => Carbon::now()->addDays(7),
                'priority' => 'low',
                'estimated_hours' => 20.0,
                'actual_hours' => 18.0,
                'notes' => 'Communication plan approved by leadership team.',
                'status_id' => $completedStatus->id,
                'program_id' => $digitalTransformation->id,
            ],
            [
                'title' => 'Performance Metrics Dashboard',
                'description' => 'Create dashboard for tracking key performance indicators across all programs.',
                'due_date' => Carbon::now()->addDays(50),
                'priority' => 'medium',
                'estimated_hours' => 60.0,
                'actual_hours' => null,
                'notes' => 'Requirements gathering in progress.',
                'status_id' => $pendingStatus->id,
                'program_id' => $dataAnalytics->id,
            ],
            [
                'title' => 'Vendor Evaluation',
                'description' => 'Evaluate and select third-party vendors for specialized technology services.',
                'due_date' => Carbon::now()->addDays(30),
                'priority' => 'medium',
                'estimated_hours' => 40.0,
                'actual_hours' => 35.0,
                'notes' => 'Shortlisted 3 vendors, final presentations scheduled.',
                'status_id' => $inProgressStatus->id,
                'program_id' => $digitalTransformation->id,
            ],
        ];

        foreach ($tasks as $taskData) {
            $taskData['assigned_to'] = $users->random()->id;
            Task::create($taskData);
        }
    }
}
