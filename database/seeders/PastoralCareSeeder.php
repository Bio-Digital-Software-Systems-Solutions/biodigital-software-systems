<?php

namespace Database\Seeders;

use App\Models\PastoralCare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PastoralCareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get pastors
        $pastors = User::whereHas('roles', function ($query) {
            $query->where('name', 'pastor');
        })->get();

        if ($pastors->isEmpty()) {
            $this->command->warn('⚠️ No pastors found. Please run PastorRoleSeeder first.');

            return;
        }

        // Get some regular users for appointments
        $users = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'pastor');
        })->take(5)->get();

        $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
        $locationTypes = ['in_person', 'zoom', 'hybrid'];

        $clientNames = [
            'Marie Dupont',
            'Jean-Pierre Martin',
            'Sophie Bernard',
            'Michel Laurent',
            'Isabelle Moreau',
            'François Petit',
            'Catherine Dubois',
            'Thomas Leroy',
            'Anne-Marie Roux',
            'Philippe Garnier',
        ];

        $notes = [
            'Première consultation pour accompagnement spirituel.',
            'Suivi de la situation familiale discutée précédemment.',
            'Discussion sur le parcours de foi et questions existentielles.',
            'Préparation au mariage - première rencontre.',
            'Accompagnement suite à un deuil récent.',
            'Questions sur la prière et la vie spirituelle.',
            'Conseil pour une décision importante.',
            'Suivi pastoral régulier.',
        ];

        $pastorNotes = [
            'Client très réceptif, prévoir un suivi dans 2 semaines.',
            'Situation complexe, nécessite plusieurs rencontres.',
            'Progrès notable depuis la dernière rencontre.',
            'Recommandé de participer au groupe de prière.',
            'À recontacter pour suivi la semaine prochaine.',
        ];

        $cancellationReasons = [
            'Empêchement de dernière minute',
            'Maladie',
            'Problème de transport',
            'Rendez-vous reporté',
        ];

        $appointmentCount = 0;

        foreach ($pastors as $pastor) {
            // Create past completed appointments
            for ($i = 1; $i <= 5; $i++) {
                $date = Carbon::now()->subDays(rand(7, 60));
                $hour = rand(10, 16);

                PastoralCare::create([
                    'pastor_id' => $pastor->id,
                    'user_id' => $users->random()?->id,
                    'client_name' => $clientNames[array_rand($clientNames)],
                    'client_email' => 'client'.rand(1, 100).'@example.com',
                    'client_phone' => '+49 '.rand(100, 999).' '.rand(1000000, 9999999),
                    'appointment_date' => $date->toDateString(),
                    'appointment_time' => $date->setTime($hour, 0),
                    'duration_minutes' => [30, 60, 90][array_rand([30, 60, 90])],
                    'status' => 'completed',
                    'location_type' => $locationTypes[array_rand($locationTypes)],
                    'zoom_link' => rand(0, 1) ? 'https://zoom.us/j/'.rand(1000000000, 9999999999) : null,
                    'notes' => $notes[array_rand($notes)],
                    'pastor_notes' => $pastorNotes[array_rand($pastorNotes)],
                ]);
                $appointmentCount++;
            }

            // Create past cancelled appointments
            for ($i = 1; $i <= 2; $i++) {
                $date = Carbon::now()->subDays(rand(7, 30));
                $hour = rand(10, 16);

                PastoralCare::create([
                    'pastor_id' => $pastor->id,
                    'user_id' => $users->random()?->id,
                    'client_name' => $clientNames[array_rand($clientNames)],
                    'client_email' => 'client'.rand(1, 100).'@example.com',
                    'client_phone' => '+49 '.rand(100, 999).' '.rand(1000000, 9999999),
                    'appointment_date' => $date->toDateString(),
                    'appointment_time' => $date->setTime($hour, 0),
                    'duration_minutes' => 60,
                    'status' => 'cancelled',
                    'location_type' => $locationTypes[array_rand($locationTypes)],
                    'notes' => $notes[array_rand($notes)],
                    'cancellation_reason' => $cancellationReasons[array_rand($cancellationReasons)],
                    'cancelled_at' => $date->subDays(1),
                ]);
                $appointmentCount++;
            }

            // Create upcoming confirmed appointments
            for ($i = 1; $i <= 3; $i++) {
                $date = Carbon::now()->addDays(rand(3, 14));
                $hour = rand(10, 16);

                PastoralCare::create([
                    'pastor_id' => $pastor->id,
                    'user_id' => $users->random()?->id,
                    'client_name' => $clientNames[array_rand($clientNames)],
                    'client_email' => 'client'.rand(1, 100).'@example.com',
                    'client_phone' => '+49 '.rand(100, 999).' '.rand(1000000, 9999999),
                    'appointment_date' => $date->toDateString(),
                    'appointment_time' => $date->setTime($hour, 0),
                    'duration_minutes' => [30, 60, 90][array_rand([30, 60, 90])],
                    'status' => 'confirmed',
                    'location_type' => $locationTypes[array_rand($locationTypes)],
                    'zoom_link' => rand(0, 1) ? 'https://zoom.us/j/'.rand(1000000000, 9999999999) : null,
                    'notes' => $notes[array_rand($notes)],
                    'confirmation_sent_at' => now()->subDays(rand(1, 3)),
                ]);
                $appointmentCount++;
            }

            // Create upcoming pending appointments
            for ($i = 1; $i <= 2; $i++) {
                $date = Carbon::now()->addDays(rand(5, 21));
                $hour = rand(10, 16);

                PastoralCare::create([
                    'pastor_id' => $pastor->id,
                    'user_id' => $users->random()?->id,
                    'client_name' => $clientNames[array_rand($clientNames)],
                    'client_email' => 'client'.rand(1, 100).'@example.com',
                    'client_phone' => '+49 '.rand(100, 999).' '.rand(1000000, 9999999),
                    'appointment_date' => $date->toDateString(),
                    'appointment_time' => $date->setTime($hour, 0),
                    'duration_minutes' => 60,
                    'status' => 'pending',
                    'location_type' => $locationTypes[array_rand($locationTypes)],
                    'notes' => $notes[array_rand($notes)],
                ]);
                $appointmentCount++;
            }

            // Create a no-show appointment
            $date = Carbon::now()->subDays(rand(2, 10));
            $hour = rand(10, 16);

            PastoralCare::create([
                'pastor_id' => $pastor->id,
                'client_name' => $clientNames[array_rand($clientNames)],
                'client_email' => 'client'.rand(1, 100).'@example.com',
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $date->setTime($hour, 0),
                'duration_minutes' => 60,
                'status' => 'no_show',
                'location_type' => 'in_person',
                'notes' => 'Client ne s\'est pas présenté.',
            ]);
            $appointmentCount++;
        }

        // Create some follow-up appointments (linked to parent)
        $completedAppointments = PastoralCare::where('status', 'completed')
            ->whereNull('parent_id')
            ->take(3)
            ->get();

        foreach ($completedAppointments as $parent) {
            $date = Carbon::now()->addDays(rand(7, 14));
            $hour = rand(10, 16);

            PastoralCare::create([
                'pastor_id' => $parent->pastor_id,
                'parent_id' => $parent->id,
                'user_id' => $parent->user_id,
                'client_name' => $parent->client_name,
                'client_email' => $parent->client_email,
                'client_phone' => $parent->client_phone,
                'appointment_date' => $date->toDateString(),
                'appointment_time' => $date->setTime($hour, 0),
                'duration_minutes' => $parent->duration_minutes,
                'status' => 'pending',
                'location_type' => $parent->location_type,
                'notes' => 'Rendez-vous de suivi suite à la rencontre du '.$parent->appointment_date->format('d/m/Y'),
            ]);
            $appointmentCount++;
        }

        $this->command->info("✅ Created {$appointmentCount} pastoral care appointments for {$pastors->count()} pastors.");
    }
}
