<?php

namespace Database\Seeders;

use App\Models\ProfileSkill;
use Illuminate\Database\Seeder;

class ProfileSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            // Soft Skills
            ['name' => 'Communication', 'category' => 'soft'],
            ['name' => 'Leadership', 'category' => 'soft'],
            ['name' => 'Teamwork', 'category' => 'soft'],
            ['name' => 'Problem Solving', 'category' => 'soft'],
            ['name' => 'Critical Thinking', 'category' => 'soft'],
            ['name' => 'Time Management', 'category' => 'soft'],
            ['name' => 'Adaptability', 'category' => 'soft'],
            ['name' => 'Creativity', 'category' => 'soft'],
            ['name' => 'Emotional Intelligence', 'category' => 'soft'],
            ['name' => 'Conflict Resolution', 'category' => 'soft'],
            ['name' => 'Active Listening', 'category' => 'soft'],
            ['name' => 'Empathy', 'category' => 'soft'],
            ['name' => 'Patience', 'category' => 'soft'],
            ['name' => 'Negotiation', 'category' => 'soft'],
            ['name' => 'Public Speaking', 'category' => 'soft'],
            ['name' => 'Decision Making', 'category' => 'soft'],
            ['name' => 'Interpersonal Skills', 'category' => 'soft'],
            ['name' => 'Stress Management', 'category' => 'soft'],
            ['name' => 'Motivation', 'category' => 'soft'],
            ['name' => 'Flexibility', 'category' => 'soft'],
            ['name' => 'Work Ethic', 'category' => 'soft'],
            ['name' => 'Collaboration', 'category' => 'soft'],
            ['name' => 'Self-Awareness', 'category' => 'soft'],
            ['name' => 'Positive Attitude', 'category' => 'soft'],
            ['name' => 'Cultural Awareness', 'category' => 'soft'],

            // Hard Skills
            ['name' => 'Project Management', 'category' => 'hard'],
            ['name' => 'Data Analysis', 'category' => 'hard'],
            ['name' => 'Financial Analysis', 'category' => 'hard'],
            ['name' => 'Budgeting', 'category' => 'hard'],
            ['name' => 'Strategic Planning', 'category' => 'hard'],
            ['name' => 'Market Research', 'category' => 'hard'],
            ['name' => 'Business Development', 'category' => 'hard'],
            ['name' => 'Sales', 'category' => 'hard'],
            ['name' => 'Customer Service', 'category' => 'hard'],
            ['name' => 'Event Planning', 'category' => 'hard'],
            ['name' => 'Copywriting', 'category' => 'hard'],
            ['name' => 'Content Writing', 'category' => 'hard'],
            ['name' => 'Translation', 'category' => 'hard'],
            ['name' => 'Research', 'category' => 'hard'],
            ['name' => 'Teaching', 'category' => 'hard'],
            ['name' => 'Coaching', 'category' => 'hard'],
            ['name' => 'Accounting', 'category' => 'hard'],
            ['name' => 'Legal Knowledge', 'category' => 'hard'],
            ['name' => 'Human Resources', 'category' => 'hard'],
            ['name' => 'Supply Chain Management', 'category' => 'hard'],
            ['name' => 'Quality Assurance', 'category' => 'hard'],
            ['name' => 'Risk Management', 'category' => 'hard'],
            ['name' => 'Logistics', 'category' => 'hard'],
            ['name' => 'Procurement', 'category' => 'hard'],
            ['name' => 'Administrative Skills', 'category' => 'hard'],

            // Technical Skills
            ['name' => 'JavaScript', 'category' => 'technical'],
            ['name' => 'TypeScript', 'category' => 'technical'],
            ['name' => 'Python', 'category' => 'technical'],
            ['name' => 'PHP', 'category' => 'technical'],
            ['name' => 'Java', 'category' => 'technical'],
            ['name' => 'C#', 'category' => 'technical'],
            ['name' => 'C++', 'category' => 'technical'],
            ['name' => 'Ruby', 'category' => 'technical'],
            ['name' => 'Go', 'category' => 'technical'],
            ['name' => 'Rust', 'category' => 'technical'],
            ['name' => 'Swift', 'category' => 'technical'],
            ['name' => 'Kotlin', 'category' => 'technical'],
            ['name' => 'React', 'category' => 'technical'],
            ['name' => 'Vue.js', 'category' => 'technical'],
            ['name' => 'Angular', 'category' => 'technical'],
            ['name' => 'Node.js', 'category' => 'technical'],
            ['name' => 'Laravel', 'category' => 'technical'],
            ['name' => 'Django', 'category' => 'technical'],
            ['name' => 'Spring Boot', 'category' => 'technical'],
            ['name' => 'SQL', 'category' => 'technical'],
            ['name' => 'MongoDB', 'category' => 'technical'],
            ['name' => 'PostgreSQL', 'category' => 'technical'],
            ['name' => 'MySQL', 'category' => 'technical'],
            ['name' => 'Redis', 'category' => 'technical'],
            ['name' => 'Docker', 'category' => 'technical'],
            ['name' => 'Kubernetes', 'category' => 'technical'],
            ['name' => 'AWS', 'category' => 'technical'],
            ['name' => 'Azure', 'category' => 'technical'],
            ['name' => 'Google Cloud', 'category' => 'technical'],
            ['name' => 'Git', 'category' => 'technical'],
            ['name' => 'CI/CD', 'category' => 'technical'],
            ['name' => 'Linux Administration', 'category' => 'technical'],
            ['name' => 'Network Administration', 'category' => 'technical'],
            ['name' => 'Cybersecurity', 'category' => 'technical'],
            ['name' => 'Machine Learning', 'category' => 'technical'],
            ['name' => 'Deep Learning', 'category' => 'technical'],
            ['name' => 'Data Engineering', 'category' => 'technical'],
            ['name' => 'DevOps', 'category' => 'technical'],
            ['name' => 'REST APIs', 'category' => 'technical'],
            ['name' => 'GraphQL', 'category' => 'technical'],
            ['name' => 'UI/UX Design', 'category' => 'technical'],
            ['name' => 'Figma', 'category' => 'technical'],
            ['name' => 'Adobe Photoshop', 'category' => 'technical'],
            ['name' => 'Adobe Illustrator', 'category' => 'technical'],
            ['name' => 'Adobe Premiere', 'category' => 'technical'],
            ['name' => 'Microsoft Office', 'category' => 'technical'],
            ['name' => 'Microsoft Excel (Advanced)', 'category' => 'technical'],
            ['name' => 'Google Workspace', 'category' => 'technical'],
            ['name' => 'Tableau', 'category' => 'technical'],
            ['name' => 'Power BI', 'category' => 'technical'],
            ['name' => 'SEO', 'category' => 'technical'],
            ['name' => 'Google Analytics', 'category' => 'technical'],
            ['name' => 'Social Media Marketing', 'category' => 'technical'],
            ['name' => 'Email Marketing', 'category' => 'technical'],
            ['name' => 'WordPress', 'category' => 'technical'],
            ['name' => 'Shopify', 'category' => 'technical'],
            ['name' => 'Salesforce', 'category' => 'technical'],
            ['name' => 'SAP', 'category' => 'technical'],
            ['name' => 'JIRA', 'category' => 'technical'],
            ['name' => 'Agile/Scrum', 'category' => 'technical'],
        ];

        foreach ($skills as $skill) {
            ProfileSkill::firstOrCreate(
                ['name' => $skill['name'], 'category' => $skill['category']],
                $skill
            );
        }
    }
}
