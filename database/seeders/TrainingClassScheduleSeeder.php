<?php

namespace Database\Seeders;

use App\Models\TrainingClass;
use App\Models\TrainingClassSchedule;
use Illuminate\Database\Seeder;

class TrainingClassScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = TrainingClass::all();

        foreach ($classes as $class) {
            // Create 1-3 schedules per training class
            $schedulesCount = rand(1, 3);
            $usedDays = [];

            for ($i = 0; $i < $schedulesCount; $i++) {
                $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                $availableDays = array_diff($days, $usedDays);

                if (empty($availableDays)) {
                    break;
                }

                $day = $availableDays[array_rand($availableDays)];
                $usedDays[] = $day;

                TrainingClassSchedule::create([
                    'training_class_id' => $class->id,
                    'day_of_week' => $day,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'room' => $class->room,
                    'is_active' => true,
                ]);
            }
        }
    }
}
