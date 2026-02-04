<?php

namespace Database\Seeders;

use App\Models\Interest;
use Illuminate\Database\Seeder;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interests = [
            // Sports & Fitness
            ['name' => 'Football', 'icon' => '⚽'],
            ['name' => 'Basketball', 'icon' => '🏀'],
            ['name' => 'Tennis', 'icon' => '🎾'],
            ['name' => 'Running', 'icon' => '🏃'],
            ['name' => 'Swimming', 'icon' => '🏊'],
            ['name' => 'Cycling', 'icon' => '🚴'],
            ['name' => 'Yoga', 'icon' => '🧘'],
            ['name' => 'Fitness', 'icon' => '💪'],
            ['name' => 'Hiking', 'icon' => '🥾'],
            ['name' => 'Volleyball', 'icon' => '🏐'],

            // Arts & Culture
            ['name' => 'Music', 'icon' => '🎵'],
            ['name' => 'Singing', 'icon' => '🎤'],
            ['name' => 'Piano', 'icon' => '🎹'],
            ['name' => 'Guitar', 'icon' => '🎸'],
            ['name' => 'Drums', 'icon' => '🥁'],
            ['name' => 'Dancing', 'icon' => '💃'],
            ['name' => 'Painting', 'icon' => '🎨'],
            ['name' => 'Drawing', 'icon' => '✏️'],
            ['name' => 'Photography', 'icon' => '📷'],
            ['name' => 'Film & Cinema', 'icon' => '🎬'],
            ['name' => 'Theater', 'icon' => '🎭'],
            ['name' => 'Literature', 'icon' => '📚'],
            ['name' => 'Poetry', 'icon' => '📝'],
            ['name' => 'Sculpture', 'icon' => '🗿'],

            // Technology & Science
            ['name' => 'Programming', 'icon' => '💻'],
            ['name' => 'Web Development', 'icon' => '🌐'],
            ['name' => 'Artificial Intelligence', 'icon' => '🤖'],
            ['name' => 'Data Science', 'icon' => '📊'],
            ['name' => 'Robotics', 'icon' => '🦾'],
            ['name' => 'Video Games', 'icon' => '🎮'],
            ['name' => 'Mobile Apps', 'icon' => '📱'],
            ['name' => 'Astronomy', 'icon' => '🔭'],
            ['name' => 'Physics', 'icon' => '⚛️'],
            ['name' => 'Biology', 'icon' => '🧬'],
            ['name' => 'Chemistry', 'icon' => '🧪'],
            ['name' => 'Mathematics', 'icon' => '🔢'],

            // Faith & Spirituality
            ['name' => 'Bible Study', 'icon' => '📖'],
            ['name' => 'Prayer', 'icon' => '🙏'],
            ['name' => 'Worship', 'icon' => '✝️'],
            ['name' => 'Evangelism', 'icon' => '🕊️'],
            ['name' => 'Missions', 'icon' => '🌍'],
            ['name' => 'Youth Ministry', 'icon' => '👥'],
            ['name' => 'Children Ministry', 'icon' => '👶'],
            ['name' => 'Counseling', 'icon' => '💬'],
            ['name' => 'Theology', 'icon' => '📜'],

            // Hobbies & Lifestyle
            ['name' => 'Cooking', 'icon' => '👨‍🍳'],
            ['name' => 'Baking', 'icon' => '🍰'],
            ['name' => 'Gardening', 'icon' => '🌱'],
            ['name' => 'Travel', 'icon' => '✈️'],
            ['name' => 'Reading', 'icon' => '📖'],
            ['name' => 'Writing', 'icon' => '✍️'],
            ['name' => 'Board Games', 'icon' => '🎲'],
            ['name' => 'Chess', 'icon' => '♟️'],
            ['name' => 'Puzzles', 'icon' => '🧩'],
            ['name' => 'Crafts', 'icon' => '🧶'],
            ['name' => 'Sewing', 'icon' => '🧵'],
            ['name' => 'DIY Projects', 'icon' => '🔧'],
            ['name' => 'Fashion', 'icon' => '👗'],
            ['name' => 'Interior Design', 'icon' => '🏠'],
            ['name' => 'Pets', 'icon' => '🐾'],

            // Social & Community
            ['name' => 'Volunteering', 'icon' => '🤝'],
            ['name' => 'Community Service', 'icon' => '🏘️'],
            ['name' => 'Mentoring', 'icon' => '👨‍🏫'],
            ['name' => 'Public Speaking', 'icon' => '🎤'],
            ['name' => 'Networking', 'icon' => '🔗'],
            ['name' => 'Event Planning', 'icon' => '📅'],
            ['name' => 'Teaching', 'icon' => '👩‍🏫'],

            // Business & Finance
            ['name' => 'Entrepreneurship', 'icon' => '🚀'],
            ['name' => 'Investing', 'icon' => '📈'],
            ['name' => 'Marketing', 'icon' => '📣'],
            ['name' => 'Leadership', 'icon' => '👔'],
            ['name' => 'Project Management', 'icon' => '📋'],

            // Health & Wellness
            ['name' => 'Meditation', 'icon' => '🧘‍♂️'],
            ['name' => 'Nutrition', 'icon' => '🥗'],
            ['name' => 'Mental Health', 'icon' => '🧠'],
            ['name' => 'Wellness', 'icon' => '🌿'],

            // Languages & Culture
            ['name' => 'Language Learning', 'icon' => '🗣️'],
            ['name' => 'Cultural Exchange', 'icon' => '🌏'],
            ['name' => 'History', 'icon' => '🏛️'],
            ['name' => 'Geography', 'icon' => '🗺️'],

            // Nature & Environment
            ['name' => 'Environment', 'icon' => '🌳'],
            ['name' => 'Wildlife', 'icon' => '🦁'],
            ['name' => 'Sustainability', 'icon' => '♻️'],
            ['name' => 'Camping', 'icon' => '⛺'],
            ['name' => 'Fishing', 'icon' => '🎣'],
        ];

        foreach ($interests as $interest) {
            Interest::firstOrCreate(
                ['name' => $interest['name']],
                $interest
            );
        }
    }
}
