<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            throw new \Exception('Users must be seeded before events');
        }

        $events = [
            [
                'title' => 'Annual Tech Conference 2025',
                'description' => 'Join us for our annual technology conference featuring keynote speakers, workshops, and networking opportunities. Topics include AI, cloud computing, and emerging technologies.',
                'start_date' => Carbon::now()->addDays(30)->setTime(9, 0),
                'end_date' => Carbon::now()->addDays(32)->setTime(17, 0),
                'location' => 'Grand Convention Center',
                'max_participants' => 500,
                'is_public' => true,
                'status' => 'planned',
                'address' => [
                    'street' => '100 Convention Boulevard',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'France',
                ],
            ],
            [
                'title' => 'Monthly Team Building Workshop',
                'description' => 'Interactive team building activities to strengthen collaboration and communication among team members.',
                'start_date' => Carbon::now()->addDays(15)->setTime(14, 0),
                'end_date' => Carbon::now()->addDays(15)->setTime(18, 0),
                'location' => 'Company Training Room',
                'max_participants' => 50,
                'is_public' => false,
                'status' => 'planned',
                'address' => [
                    'street' => '25 Business Park Avenue',
                    'city' => 'Lyon',
                    'postal_code' => '69000',
                    'country' => 'France',
                ],
            ],
            [
                'title' => 'JavaScript Fundamentals Training',
                'description' => 'Comprehensive training session covering JavaScript basics, ES6+ features, and modern development practices.',
                'start_date' => Carbon::now()->addDays(7)->setTime(10, 0),
                'end_date' => Carbon::now()->addDays(7)->setTime(16, 0),
                'location' => 'Learning Center Room A',
                'max_participants' => 25,
                'is_public' => true,
                'status' => 'planned',
                'address' => null,
            ],
            [
                'title' => 'Quarterly All-Hands Meeting',
                'description' => 'Company-wide meeting to discuss quarterly results, upcoming projects, and strategic initiatives.',
                'start_date' => Carbon::now()->addDays(45)->setTime(13, 0),
                'end_date' => Carbon::now()->addDays(45)->setTime(15, 0),
                'location' => 'Main Auditorium',
                'max_participants' => 200,
                'is_public' => false,
                'status' => 'planned',
                'address' => null,
            ],
            [
                'title' => 'React & TypeScript Workshop',
                'description' => 'Hands-on workshop for building modern web applications with React and TypeScript.',
                'start_date' => Carbon::now()->addDays(21)->setTime(9, 0),
                'end_date' => Carbon::now()->addDays(22)->setTime(17, 0),
                'location' => 'Tech Hub',
                'max_participants' => 30,
                'is_public' => true,
                'status' => 'planned',
                'address' => [
                    'street' => '50 Innovation Street',
                    'city' => 'Toulouse',
                    'postal_code' => '31000',
                    'country' => 'France',
                ],
            ],
            [
                'title' => 'Product Launch Event',
                'description' => 'Official launch event for our new product line with demonstrations and customer presentations.',
                'start_date' => Carbon::now()->addDays(60)->setTime(18, 0),
                'end_date' => Carbon::now()->addDays(60)->setTime(21, 0),
                'location' => 'Marketing Event Space',
                'max_participants' => 150,
                'is_public' => true,
                'status' => 'planned',
                'address' => [
                    'street' => '200 Marketing Plaza',
                    'city' => 'Nice',
                    'postal_code' => '06000',
                    'country' => 'France',
                ],
            ],
            [
                'title' => 'Code Review Best Practices',
                'description' => 'Learn effective code review techniques to improve code quality and team collaboration.',
                'start_date' => Carbon::now()->subDays(7)->setTime(14, 0),
                'end_date' => Carbon::now()->subDays(7)->setTime(16, 0),
                'location' => 'Development Floor',
                'max_participants' => 20,
                'is_public' => false,
                'status' => 'completed',
                'address' => null,
            ],
            [
                'title' => 'DevOps Pipeline Workshop',
                'description' => 'Workshop on setting up CI/CD pipelines, containerization, and deployment strategies.',
                'start_date' => Carbon::now()->addDays(14)->setTime(9, 0),
                'end_date' => Carbon::now()->addDays(14)->setTime(17, 0),
                'location' => 'Infrastructure Lab',
                'max_participants' => 15,
                'is_public' => true,
                'status' => 'planned',
                'address' => null,
            ],
        ];

        foreach ($events as $eventData) {
            $addressId = null;

            if ($eventData['address']) {
                $address = Address::create($eventData['address']);
                $addressId = $address->id;
            }

            unset($eventData['address']);
            $eventData['address_id'] = $addressId;
            $eventData['user_id'] = $users->random()->id;

            Event::create($eventData);
        }
    }
}
