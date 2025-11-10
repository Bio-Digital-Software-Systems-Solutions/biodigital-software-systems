<?php

namespace Database\Seeders;

use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PastorAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with pastor role
        $pastors = User::whereHas('roles', function ($query) {
            $query->where('name', 'pastor');
        })->get();

        foreach ($pastors as $pastor) {
            // Create weekly availability for the pastor
            // Monday to Friday, 11:00-17:00 with 60-minute slots
            for ($day = 1; $day <= 5; $day++) { // 1=Monday, 5=Friday
                PastorAvailability::create([
                    'pastor_id' => $pastor->id,
                    'type' => 'weekly',
                    'day_of_week' => $day,
                    'start_time' => '11:00',
                    'end_time' => '17:00',
                    'slot_duration' => 60,
                    'is_active' => true,
                    'notes' => 'Créneaux de consultation standard'
                ]);
            }

            // Create specific Saturday morning availability
            PastorAvailability::create([
                'pastor_id' => $pastor->id,
                'type' => 'weekly',
                'day_of_week' => 6, // Saturday
                'start_time' => '09:00',
                'end_time' => '12:00',
                'slot_duration' => 30,
                'is_active' => true,
                'notes' => 'Créneaux courts le samedi matin'
            ]);
        }

        $this->command->info('Pastor availability created for ' . $pastors->count() . ' pastors');
    }
}