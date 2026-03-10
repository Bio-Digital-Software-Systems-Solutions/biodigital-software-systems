<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function available_slots_datetime_format_does_not_include_timezone_offset(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('api.appointments.available-slots', [
                'date' => $tomorrow,
                'duration' => 60,
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'date',
                'duration_minutes',
                'available_slots' => [
                    '*' => [
                        'start_datetime',
                        'end_datetime',
                        'formatted_time',
                        'available',
                    ]
                ],
                'total_slots',
            ]
        ]);

        // Verify datetime format is without timezone (Y-m-d\TH:i:s)
        $slots = $response->json('data.available_slots');
        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            // Should NOT contain 'Z' (UTC marker) or timezone offset like +00:00
            $this->assertStringNotContainsString('Z', $slot['start_datetime']);
            $this->assertStringNotContainsString('+', $slot['start_datetime']);
            $this->assertStringNotContainsString('Z', $slot['end_datetime']);
            $this->assertStringNotContainsString('+', $slot['end_datetime']);

            // Should match format Y-m-d\TH:i:s (e.g., 2026-01-12T11:00:00)
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $slot['start_datetime'],
                "start_datetime should be in Y-m-d\TH:i:s format without timezone"
            );
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $slot['end_datetime'],
                "end_datetime should be in Y-m-d\TH:i:s format without timezone"
            );
        }
    }

    /** @test */
    public function available_slots_date_matches_requested_date(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('api.appointments.available-slots', [
                'date' => $tomorrow,
                'duration' => 60,
            ]));

        $response->assertOk();

        $slots = $response->json('data.available_slots');
        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            // Extract date from start_datetime
            $slotDate = substr((string) $slot['start_datetime'], 0, 10);
            $this->assertEquals(
                $tomorrow,
                $slotDate,
                "Slot date should match the requested date without timezone shift"
            );
        }
    }

    /** @test */
    public function available_slots_time_is_consistent_with_formatted_time(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('api.appointments.available-slots', [
                'date' => $tomorrow,
                'duration' => 60,
            ]));

        $response->assertOk();

        $slots = $response->json('data.available_slots');
        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            // Extract time from start_datetime (e.g., "2026-01-12T11:00:00" -> "11:00")
            $startTime = substr((string) $slot['start_datetime'], 11, 5);
            $endTime = substr((string) $slot['end_datetime'], 11, 5);
            $expectedFormattedTime = $startTime . ' - ' . $endTime;

            $this->assertEquals(
                $expectedFormattedTime,
                $slot['formatted_time'],
                "formatted_time should match the time extracted from start_datetime and end_datetime"
            );
        }
    }

    /** @test */
    public function get_available_slots_method_returns_correct_format(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $slots = Appointment::getAvailableSlots($tomorrow, 60, '09:00', '12:00');

        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            // Verify format without timezone
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $slot['start_datetime']
            );
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $slot['end_datetime']
            );

            // Verify date is correct
            $this->assertStringStartsWith($tomorrow, $slot['start_datetime']);
            $this->assertStringStartsWith($tomorrow, $slot['end_datetime']);
        }
    }

    /** @test */
    public function slot_time_difference_matches_duration(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $duration = 60; // 60 minutes

        $slots = Appointment::getAvailableSlots($tomorrow, $duration, '09:00', '12:00');

        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['start_datetime']);
            $end = Carbon::parse($slot['end_datetime']);

            $diffInMinutes = $start->diffInMinutes($end);

            $this->assertEquals(
                $duration,
                $diffInMinutes,
                "Slot duration should be {$duration} minutes"
            );
        }
    }

    /** @test */
    public function slots_for_different_durations_have_correct_time_range(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $testDurations = [30, 45, 60, 90, 120];

        foreach ($testDurations as $duration) {
            $slots = Appointment::getAvailableSlots($tomorrow, $duration, '10:00', '12:00');

            if ($slots === []) {
                continue;
            }

            foreach ($slots as $slot) {
                $start = Carbon::parse($slot['start_datetime']);
                $end = Carbon::parse($slot['end_datetime']);

                $this->assertEquals(
                    $duration,
                    $start->diffInMinutes($end),
                    "Slot duration should be {$duration} minutes for duration={$duration}"
                );
            }
        }
    }

    /** @test */
    public function available_slots_respects_start_and_end_hours(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $slots = Appointment::getAvailableSlots($tomorrow, 60, '09:00', '11:00');

        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            $startTime = substr((string) $slot['start_datetime'], 11, 5);
            $endTime = substr((string) $slot['end_datetime'], 11, 5);

            // Start time should be >= 09:00
            $this->assertGreaterThanOrEqual('09:00', $startTime);

            // End time should be <= 11:00
            $this->assertLessThanOrEqual('11:00', $endTime);
        }
    }

    /** @test */
    public function user_appointments_for_date_returns_correct_format(): void
    {
        $tomorrow = Carbon::tomorrow();

        // Create an appointment
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'start_datetime' => $tomorrow->copy()->setTime(10, 0),
            'end_datetime' => $tomorrow->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);
        $appointment->participants()->attach($this->user->id, ['status' => 'accepted']);

        $appointments = Appointment::getUserSchedule($this->user, $tomorrow->format('Y-m-d'));

        $this->assertNotEmpty($appointments);

        foreach ($appointments as $apt) {
            // Verify format without timezone
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $apt['start_datetime'],
                "start_datetime should be in Y-m-d\TH:i:s format"
            );
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                $apt['end_datetime'],
                "end_datetime should be in Y-m-d\TH:i:s format"
            );
        }
    }

    /** @test */
    public function appointment_datetime_preserves_exact_time_when_created(): void
    {
        $specificDate = '2026-01-27';
        $specificStartTime = '10:00';
        $specificEndTime = '11:00';

        // Create appointment with specific date and time
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'start_datetime' => "{$specificDate} {$specificStartTime}:00",
            'end_datetime' => "{$specificDate} {$specificEndTime}:00",
            'status' => 'confirmed',
        ]);

        // Verify the stored datetime is correct
        $this->assertEquals($specificDate, $appointment->start_datetime->format('Y-m-d'));
        $this->assertEquals($specificStartTime, $appointment->start_datetime->format('H:i'));
        $this->assertEquals($specificDate, $appointment->end_datetime->format('Y-m-d'));
        $this->assertEquals($specificEndTime, $appointment->end_datetime->format('H:i'));
    }

    /** @test */
    public function task_appointment_api_returns_datetime_without_timezone_shift(): void
    {
        // Create a task
        $project = \App\Models\Project::factory()->create();
        $status = \App\Models\Status::where('name', 'pending')->first();
        $task = \App\Models\Task::factory()->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $status->id,
        ]);

        $specificDate = Carbon::tomorrow()->format('Y-m-d');
        $specificStartTime = '10:00';
        $specificEndTime = '11:00';

        // Create appointment linked to task
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'appointmentable_type' => \App\Models\Task::class,
            'appointmentable_id' => $task->id,
            'start_datetime' => "{$specificDate} {$specificStartTime}:00",
            'end_datetime' => "{$specificDate} {$specificEndTime}:00",
            'status' => 'pending',
        ]);

        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/{$task->uuid}/appointments");

        $response->assertOk();

        $appointments = $response->json('data');
        $this->assertNotEmpty($appointments);

        // Find our appointment
        $foundAppointment = collect($appointments)->firstWhere('uuid', $appointment->uuid);
        $this->assertNotNull($foundAppointment);

        // Verify datetime format and values
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
            $foundAppointment['start_datetime']
        );

        // Verify the exact date is preserved (no day shift)
        $this->assertStringStartsWith($specificDate, $foundAppointment['start_datetime']);

        // Verify the exact time is preserved (no hour shift)
        $this->assertStringContainsString("T{$specificStartTime}", $foundAppointment['start_datetime']);
    }

    /** @test */
    public function appointment_at_midnight_boundary_preserves_correct_date(): void
    {
        $specificDate = '2026-01-27';

        // Test appointment at 23:00-00:00 (crossing midnight)
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'start_datetime' => "{$specificDate} 23:00:00",
            'end_datetime' => "2026-01-28 00:00:00",
            'status' => 'confirmed',
        ]);

        // Verify dates are preserved correctly
        $this->assertEquals($specificDate, $appointment->start_datetime->format('Y-m-d'));
        $this->assertEquals('23:00', $appointment->start_datetime->format('H:i'));
        $this->assertEquals('2026-01-28', $appointment->end_datetime->format('Y-m-d'));
        $this->assertEquals('00:00', $appointment->end_datetime->format('H:i'));
    }

    /** @test */
    public function appointment_early_morning_preserves_correct_date(): void
    {
        $specificDate = '2026-01-27';

        // Test appointment at 01:00 (early morning - prone to timezone issues)
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'start_datetime' => "{$specificDate} 01:00:00",
            'end_datetime' => "{$specificDate} 02:00:00",
            'status' => 'confirmed',
        ]);

        // Verify the date is still correct (not shifted to previous day)
        $this->assertEquals($specificDate, $appointment->start_datetime->format('Y-m-d'));
        $this->assertEquals('01:00', $appointment->start_datetime->format('H:i'));
    }
}
