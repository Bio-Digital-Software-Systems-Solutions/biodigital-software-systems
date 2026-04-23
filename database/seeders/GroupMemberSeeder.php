<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GroupMemberSeeder extends Seeder
{
    /**
     * Seed group members with realistic join dates spread over 12 months
     * to show member growth progression.
     */
    public function run(): void
    {
        $groups = Group::with('users')->get();

        if ($groups->isEmpty()) {
            $this->command->warn('No groups found. Please run GroupSeeder first.');

            return;
        }

        $allUsers = User::all();
        if ($allUsers->count() < 5) {
            $this->command->warn('Not enough users. Please run UserSeeder first.');

            return;
        }

        $now = Carbon::now();
        $totalAdded = 0;

        foreach ($groups as $group) {
            $existingMemberIds = $group->users->pluck('id')->toArray();
            $availableUsers = $allUsers->whereNotIn('id', $existingMemberIds)->values();

            if ($availableUsers->isEmpty()) {
                continue;
            }

            // Determine how many members to add (fill up to max_members or a reasonable number)
            $maxToAdd = $group->max_members
                ? max(0, $group->max_members - count($existingMemberIds))
                : min(12, $availableUsers->count());

            $usersToAdd = $availableUsers->random(min($maxToAdd, $availableUsers->count()));

            foreach ($usersToAdd as $index => $user) {
                // Spread join dates across the last 12 months with a realistic growth curve
                // More recent months have more joins (simulating organic growth)
                $monthsAgo = $this->weightedMonthsAgo();

                $joinDate = $now->copy()
                    ->subMonths($monthsAgo)
                    ->addDays(random_int(0, 27))
                    ->setTime(random_int(8, 18), random_int(0, 59), 0);

                if ($joinDate->isFuture()) {
                    $joinDate = $now->copy()->subDays(random_int(1, 7));
                }

                $group->users()->attach($user->id, [
                    'joined_at' => $joinDate,
                    'created_at' => $joinDate,
                    'updated_at' => $joinDate,
                ]);

                $totalAdded++;
            }
        }

        $this->command->info("GroupMemberSeeder completed. Added {$totalAdded} members across {$groups->count()} groups.");
    }

    /**
     * Generate a weighted months-ago value.
     * Recent months are more likely (simulating growth acceleration).
     */
    private function weightedMonthsAgo(): int
    {
        $weights = [
            0 => 20,  // Current month: 20%
            1 => 18,  // 1 month ago: 18%
            2 => 15,  // 2 months ago: 15%
            3 => 12,  // 3 months ago: 12%
            4 => 9,   // 4 months ago: 9%
            5 => 7,   // 5 months ago: 7%
            6 => 5,   // 6 months ago: 5%
            7 => 4,   // 7 months ago: 4%
            8 => 3,   // 8 months ago: 3%
            9 => 3,   // 9 months ago: 3%
            10 => 2,  // 10 months ago: 2%
            11 => 2,  // 11 months ago: 2%
        ];

        $rand = random_int(1, 100);
        $cumulative = 0;
        foreach ($weights as $month => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $month;
            }
        }

        return 0;
    }
}
