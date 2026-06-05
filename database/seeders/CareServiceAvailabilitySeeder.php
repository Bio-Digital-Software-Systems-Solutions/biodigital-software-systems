<?php

namespace Database\Seeders;

use App\Models\CareServiceAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CareServiceAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with pastor role
        $pastors = User::whereHas('roles', function ($query): void {
            $query->where('name', 'pastor');
        })->get();

        if ($pastors->isEmpty()) {
            $this->command->warn('⚠️ No pastors found. Please run PastorRoleSeeder first.');

            return;
        }

        $consultationModes = ['in_person', 'online', 'hybrid'];
        $rooms = ['Bureau pastoral', 'Salle de réunion A', 'Salle de prière', 'Bureau 102'];

        $availabilityCount = 0;

        foreach ($pastors as $index => $pastor) {
            // Vary availability by pastor
            $startHour = $index % 2 === 0 ? 9 : 10;
            $endHour = $index % 2 === 0 ? 17 : 18;

            // Create weekly availability for weekdays
            $weekdayDays = $index % 2 === 0 ? [1, 2, 3, 4, 5] : [1, 3, 5]; // Mon-Fri or Mon/Wed/Fri

            foreach ($weekdayDays as $day) {
                CareServiceAvailability::create([
                    'pastor_id' => $pastor->id,
                    'type' => 'weekly',
                    'day_of_week' => $day,
                    'start_time' => sprintf('%02d:00', $startHour),
                    'end_time' => sprintf('%02d:00', $endHour),
                    'consultation_mode' => $consultationModes[array_rand($consultationModes)],
                    'room' => $rooms[array_rand($rooms)],
                    'is_active' => true,
                    'notes' => 'Créneaux de consultation standard',
                ]);
                $availabilityCount++;
            }

            // Create Saturday morning availability for some pastors
            if ($index % 2 === 0) {
                CareServiceAvailability::create([
                    'pastor_id' => $pastor->id,
                    'type' => 'weekly',
                    'day_of_week' => 6, // Saturday
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'consultation_mode' => 'in_person',
                    'room' => 'Bureau pastoral',
                    'is_active' => true,
                    'notes' => 'Créneaux du samedi matin',
                ]);
                $availabilityCount++;
            }

            // Create specific date availability for upcoming special events
            for ($i = 1; $i <= 3; $i++) {
                $specificDate = Carbon::now()->addWeeks($i)->next(Carbon::SUNDAY);

                CareServiceAvailability::create([
                    'pastor_id' => $pastor->id,
                    'type' => 'specific_date',
                    'specific_date' => $specificDate->toDateString(),
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                    'consultation_mode' => 'hybrid',
                    'room' => 'Salle de prière',
                    'is_active' => true,
                    'notes' => 'Disponibilité exceptionnelle après le culte',
                ]);
                $availabilityCount++;
            }
        }

        $this->command->info("✅ Created {$availabilityCount} availability entries for {$pastors->count()} pastors.");
    }
}
