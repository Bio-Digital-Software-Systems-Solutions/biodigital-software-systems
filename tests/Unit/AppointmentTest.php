<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_belongs_to_organizer(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($user)->create();

        $this->assertTrue($appointment->organizer->is($user));
        $this->assertEquals($user->id, $appointment->user_id);
    }

    public function test_appointment_can_have_participants(): void
    {
        $appointment = Appointment::factory()->create();
        $participants = User::factory()->count(3)->create();

        foreach ($participants as $participant) {
            $appointment->participants()->attach($participant->id, [
                'status' => 'pending',
                'invited_at' => now(),
            ]);
        }

        $this->assertCount(3, $appointment->participants);
        $this->assertTrue($appointment->participants->contains($participants->first()));
    }

    public function test_appointment_duration_minutes_is_calculated_correctly(): void
    {
        $startTime = Carbon::now();
        $endTime = $startTime->copy()->addMinutes(90);

        $appointment = Appointment::factory()->create([
            'start_datetime' => $startTime,
            'end_datetime' => $endTime,
        ]);

        $this->assertEquals(90, $appointment->duration_minutes);
    }

    public function test_appointment_can_determine_if_it_is_past(): void
    {
        $pastAppointment = Appointment::factory()->past()->create();
        $futureAppointment = Appointment::factory()->future()->create();

        $this->assertTrue($pastAppointment->is_past);
        $this->assertFalse($futureAppointment->is_past);
    }

    public function test_appointment_can_determine_if_it_is_future(): void
    {
        $pastAppointment = Appointment::factory()->past()->create();
        $futureAppointment = Appointment::factory()->future()->create();

        $this->assertFalse($pastAppointment->is_future);
        $this->assertTrue($futureAppointment->is_future);
    }

    public function test_appointment_can_determine_if_it_is_today(): void
    {
        $todayAppointment = Appointment::factory()->today()->create();
        $futureAppointment = Appointment::factory()->future()->create();

        $this->assertTrue($todayAppointment->is_today);
        $this->assertFalse($futureAppointment->is_today);
    }

    public function test_appointment_can_be_cancelled_when_future_and_not_completed(): void
    {
        $futureAppointment = Appointment::factory()->future()->confirmed()->create();
        $pastAppointment = Appointment::factory()->past()->completed()->create();
        $completedAppointment = Appointment::factory()->future()->completed()->create();

        $this->assertTrue($futureAppointment->can_be_cancelled);
        $this->assertFalse($pastAppointment->can_be_cancelled);
        $this->assertFalse($completedAppointment->can_be_cancelled);
    }

    public function test_appointment_can_be_modified_by_organizer(): void
    {
        Permission::findOrCreate('edit appointments', 'web');

        $organizer = User::factory()->create();
        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($organizer)->future()->create();

        $this->assertTrue($appointment->canBeModifiedBy($organizer));
        $this->assertFalse($appointment->canBeModifiedBy($otherUser));
    }

    public function test_appointment_can_be_viewed_by_organizer_and_participants(): void
    {
        Permission::findOrCreate('view appointments', 'web');

        $organizer = User::factory()->create();
        $participant = User::factory()->create();
        $otherUser = User::factory()->create();

        $appointment = Appointment::factory()->ownedBy($organizer)->create([
            'visibility' => 'private',
        ]);
        $appointment->participants()->attach($participant->id, ['status' => 'accepted']);

        $this->assertTrue($appointment->canBeViewedBy($organizer));
        $this->assertTrue($appointment->canBeViewedBy($participant));
        $this->assertFalse($appointment->canBeViewedBy($otherUser));
    }

    public function test_appointment_formatted_date_returns_correct_format(): void
    {
        $date = Carbon::create(2024, 12, 25, 14, 30, 0);
        $appointment = Appointment::factory()->create([
            'start_datetime' => $date,
        ]);

        $expectedFormat = $date->format('d/m/Y');
        $this->assertEquals($expectedFormat, $appointment->formatted_date);
    }

    public function test_appointment_formatted_time_range_returns_correct_format(): void
    {
        $startTime = Carbon::create(2024, 12, 25, 14, 30, 0);
        $endTime = $startTime->copy()->addHours(2);

        $appointment = Appointment::factory()->create([
            'start_datetime' => $startTime,
            'end_datetime' => $endTime,
        ]);

        $this->assertEquals('14:30 - 16:30', $appointment->formatted_time_range);
    }

    public function test_appointment_participants_count_is_calculated_correctly(): void
    {
        $appointment = Appointment::factory()->create();
        $participants = User::factory()->count(5)->create();

        foreach ($participants as $participant) {
            $appointment->participants()->attach($participant->id, ['status' => 'accepted']);
        }

        $this->assertEquals(5, $appointment->participants_count);
    }

    public function test_appointment_has_conflict_detects_overlapping_appointments(): void
    {
        $user = User::factory()->create();

        // Create existing appointment
        Appointment::factory()->create([
            'user_id' => $user->id,
            'start_datetime' => Carbon::now()->addDays(1)->setTime(10, 0),
            'end_datetime' => Carbon::now()->addDays(1)->setTime(12, 0),
            'status' => 'confirmed',
        ]);

        // Test overlapping appointment
        $overlappingStart = Carbon::now()->addDays(1)->setTime(11, 0);
        $overlappingEnd = Carbon::now()->addDays(1)->setTime(13, 0);

        $this->assertTrue(Appointment::hasConflict($overlappingStart, $overlappingEnd));

        // Test non-overlapping appointment
        $nonOverlappingStart = Carbon::now()->addDays(1)->setTime(13, 0);
        $nonOverlappingEnd = Carbon::now()->addDays(1)->setTime(14, 0);

        $this->assertFalse(Appointment::hasConflict($nonOverlappingStart, $nonOverlappingEnd));
    }

    public function test_appointment_has_conflict_ignores_same_appointment_when_updating(): void
    {
        $user = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $user->id,
            'start_datetime' => Carbon::now()->addDays(1)->setTime(10, 0),
            'end_datetime' => Carbon::now()->addDays(1)->setTime(12, 0),
            'status' => 'confirmed',
        ]);

        // Test updating the same appointment with same times - should not conflict
        $this->assertFalse(Appointment::hasConflict(
            $appointment->start_datetime,
            $appointment->end_datetime,
            $appointment->id
        ));
    }

    public function test_appointment_get_available_slots_returns_correct_slots(): void
    {
        $date = Carbon::now()->addDays(1)->toDateString();
        $duration = 60; // 1 hour

        // Create an existing appointment to create gaps
        Appointment::factory()->create([
            'start_datetime' => Carbon::parse($date)->setTime(10, 0),
            'end_datetime' => Carbon::parse($date)->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $slots = Appointment::getAvailableSlots($date, $duration);

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);

        // The 10:00 - 11:00 slot should not be available
        $takenSlot = collect($slots)->first(fn($slot): bool => $slot['formatted_time'] === '10:00 - 11:00' && $slot['available'] === true);

        $this->assertNull($takenSlot);
    }

    public function test_appointment_scope_upcoming_returns_future_appointments(): void
    {
        Appointment::factory()->past()->count(2)->create();
        Appointment::factory()->future()->count(3)->create();

        $upcomingAppointments = Appointment::upcoming()->get();

        $this->assertCount(3, $upcomingAppointments);
        foreach ($upcomingAppointments as $appointment) {
            $this->assertTrue($appointment->start_datetime > now());
        }
    }

    public function test_appointment_scope_today_returns_todays_appointments(): void
    {
        Appointment::factory()->past()->count(2)->create();
        Appointment::factory()->today()->count(2)->create();
        Appointment::factory()->future()->count(3)->create();

        $todayAppointments = Appointment::today()->get();

        $this->assertCount(2, $todayAppointments);
        foreach ($todayAppointments as $appointment) {
            $this->assertTrue($appointment->start_datetime->isToday());
        }
    }

    public function test_appointment_scope_by_status_filters_correctly(): void
    {
        Appointment::factory()->confirmed()->count(2)->create();
        Appointment::factory()->pending()->count(3)->create();
        Appointment::factory()->cancelled()->count(1)->create();

        $confirmedAppointments = Appointment::withStatus('confirmed')->get();
        $pendingAppointments = Appointment::withStatus('pending')->get();

        $this->assertCount(2, $confirmedAppointments);
        $this->assertCount(3, $pendingAppointments);

        foreach ($confirmedAppointments as $appointment) {
            $this->assertEquals('confirmed', $appointment->status);
        }
    }

    public function test_appointment_scope_by_type_filters_correctly(): void
    {
        Appointment::factory()->individual()->count(2)->create();
        Appointment::factory()->group()->count(3)->create();
        Appointment::factory()->meeting()->count(1)->create();

        $individualAppointments = Appointment::withType('individual')->get();
        $groupAppointments = Appointment::withType('group')->get();

        $this->assertCount(2, $individualAppointments);
        $this->assertCount(3, $groupAppointments);

        foreach ($individualAppointments as $appointment) {
            $this->assertEquals('individual', $appointment->type);
        }
    }

    public function test_appointment_scope_for_user_returns_user_appointments_and_participations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // User's own appointments
        Appointment::factory()->ownedBy($user)->count(2)->create();

        // Appointments where user is a participant
        $participantAppointment = Appointment::factory()->ownedBy($otherUser)->create();
        $participantAppointment->participants()->attach($user->id, ['status' => 'accepted']);

        // Other user's appointments where our user is not involved
        Appointment::factory()->ownedBy($otherUser)->count(3)->create();

        $userAppointments = Appointment::forUser($user)->get();

        $this->assertCount(3, $userAppointments); // 2 own + 1 participant
    }

    public function test_appointment_scope_search_finds_appointments_by_title_and_description(): void
    {
        Appointment::factory()->create(['title' => 'Team Meeting', 'description' => 'Weekly sync']);
        Appointment::factory()->create(['title' => 'Doctor Visit', 'description' => 'Medical checkup']);
        Appointment::factory()->create(['title' => 'Client Call', 'description' => 'Project discussion']);

        $searchResults = Appointment::search('Meeting')->get();
        $this->assertCount(1, $searchResults);

        $searchResults = Appointment::search('project')->get();
        $this->assertCount(1, $searchResults);

        $searchResults = Appointment::search('nonexistent')->get();
        $this->assertCount(0, $searchResults);
    }

    public function test_appointment_uuid_is_generated_automatically(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertNotNull($appointment->uuid);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($appointment->uuid));
    }

    public function test_appointment_activity_is_logged(): void
    {
        $appointment = Appointment::factory()->create();

        // Check that creation was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'created',
        ]);

        // Update the appointment
        $appointment->update(['title' => 'Updated Title']);

        // Check that update was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'updated',
        ]);
    }
}
