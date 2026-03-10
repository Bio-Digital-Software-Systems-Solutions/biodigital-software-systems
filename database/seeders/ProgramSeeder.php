<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'project-manager', 'super-admin']);
        })->get();

        if ($users->isEmpty()) {
            throw new \Exception('Users with admin or project-manager roles must be seeded before programs');
        }

        $programs = [
            [
                'name' => 'Digital Transformation Initiative',
                'description' => 'Comprehensive program to modernize our technology infrastructure and migrate legacy systems to cloud-based solutions. This includes updating our core business applications, implementing new APIs, and training staff on modern tools.',
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->addDays(150),
                'budget' => 2500000.00,
                'status' => 'active',
                'priority' => 'high',
                'progress_percentage' => 35,
            ],
            [
                'name' => 'Customer Experience Enhancement',
                'description' => 'Multi-phase program focused on improving customer touchpoints across all channels. Includes website redesign, mobile app development, customer service portal improvements, and integration of AI-powered chatbots.',
                'start_date' => Carbon::now()->subDays(60),
                'end_date' => Carbon::now()->addDays(90),
                'budget' => 1800000.00,
                'status' => 'active',
                'priority' => 'high',
                'progress_percentage' => 60,
            ],
            [
                'name' => 'Data Analytics Platform',
                'description' => 'Implementation of enterprise-wide data analytics platform to provide real-time insights and improve decision-making capabilities. Includes data warehousing, business intelligence tools, and predictive analytics.',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(180),
                'budget' => 3200000.00,
                'status' => 'draft',
                'priority' => 'medium',
                'progress_percentage' => 5,
            ],
            [
                'name' => 'Security Infrastructure Upgrade',
                'description' => 'Comprehensive security overhaul including implementation of zero-trust architecture, endpoint protection, security awareness training, and compliance with industry standards.',
                'start_date' => Carbon::now()->subDays(90),
                'end_date' => Carbon::now()->addDays(60),
                'budget' => 1500000.00,
                'status' => 'active',
                'priority' => 'high',
                'progress_percentage' => 75,
            ],
            [
                'name' => 'Employee Training and Development',
                'description' => 'Organization-wide learning and development program focused on digital skills, leadership development, and technical certifications. Includes online learning platform and mentorship programs.',
                'start_date' => Carbon::now()->subDays(45),
                'end_date' => Carbon::now()->addDays(120),
                'budget' => 950000.00,
                'status' => 'active',
                'priority' => 'medium',
                'progress_percentage' => 40,
            ],
            [
                'name' => 'Supply Chain Optimization',
                'description' => 'Program to streamline supply chain operations through automation, vendor management improvements, and implementation of advanced tracking systems.',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(200),
                'budget' => 2100000.00,
                'status' => 'draft',
                'priority' => 'medium',
                'progress_percentage' => 0,
            ],
            [
                'name' => 'Green Energy Initiative',
                'description' => 'Sustainability program focused on reducing carbon footprint through renewable energy adoption, energy-efficient technologies, and sustainable business practices.',
                'start_date' => Carbon::now()->subDays(120),
                'end_date' => Carbon::now()->subDays(30),
                'budget' => 1200000.00,
                'status' => 'completed',
                'priority' => 'low',
                'progress_percentage' => 100,
            ],
            [
                'name' => 'Agile Transformation',
                'description' => 'Organizational transformation to adopt agile methodologies across all departments. Includes training, process redesign, and implementation of agile tools and practices.',
                'start_date' => Carbon::now()->subDays(75),
                'end_date' => Carbon::now()->addDays(45),
                'budget' => 800000.00,
                'status' => 'active',
                'priority' => 'medium',
                'progress_percentage' => 65,
            ],
            [
                'name' => 'Quality Management System',
                'description' => 'Implementation of ISO 9001 quality management system to improve process efficiency, customer satisfaction, and regulatory compliance.',
                'start_date' => Carbon::now()->addDays(60),
                'end_date' => Carbon::now()->addDays(240),
                'budget' => 650000.00,
                'status' => 'draft',
                'priority' => 'low',
                'progress_percentage' => 0,
            ],
            [
                'name' => 'Remote Work Infrastructure',
                'description' => 'Program to enhance remote work capabilities including VPN improvements, collaboration tools deployment, home office equipment provision, and security policies for remote workers.',
                'start_date' => Carbon::now()->subDays(180),
                'end_date' => Carbon::now()->subDays(60),
                'budget' => 750000.00,
                'status' => 'completed',
                'priority' => 'high',
                'progress_percentage' => 100,
            ],
        ];

        foreach ($programs as $programData) {
            $programData['user_id'] = $users->random()->id;
            Program::create($programData);
        }
    }
}
