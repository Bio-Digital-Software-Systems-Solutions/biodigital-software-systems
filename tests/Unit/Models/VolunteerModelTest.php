<?php

namespace Tests\Unit\Models;

use App\Enums\Volunteer\VolunteerCategory;
use App\Enums\Volunteer\VolunteerStatus;
use App\Enums\Volunteer\VolunteerType;
use App\Models\Department;
use App\Models\Volunteer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerModelTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_volunteer_can_be_created(): void
    {
        $user = User::factory()->create();
        $volunteer = Volunteer::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('volunteers', [
            'id' => $volunteer->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_volunteer_uses_uuid_for_route_key(): void
    {
        $volunteer = Volunteer::factory()->create();

        $this->assertEquals('uuid', $volunteer->getRouteKeyName());
        $this->assertNotNull($volunteer->uuid);
        $this->assertEquals(36, strlen($volunteer->uuid));
    }

    public function test_volunteer_generates_unique_volunteer_number(): void
    {
        $volunteer1 = Volunteer::factory()->create();
        $volunteer2 = Volunteer::factory()->create();

        $this->assertNotNull($volunteer1->volunteer_number);
        $this->assertNotNull($volunteer2->volunteer_number);
        $this->assertNotEquals($volunteer1->volunteer_number, $volunteer2->volunteer_number);
        $this->assertStringStartsWith('VOL', $volunteer1->volunteer_number);
    }

    public function test_volunteer_number_contains_year(): void
    {
        $volunteer = Volunteer::factory()->create();
        $year = date('Y');

        $this->assertStringContainsString($year, $volunteer->volunteer_number);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_volunteer_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $volunteer = Volunteer::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $volunteer->user);
        $this->assertEquals($user->id, $volunteer->user->id);
    }

    public function test_volunteer_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $volunteer = Volunteer::factory()->inDepartment($department)->create();

        $this->assertInstanceOf(Department::class, $volunteer->department);
        $this->assertEquals($department->id, $volunteer->department->id);
    }

    public function test_volunteer_can_have_nominator(): void
    {
        $nominator = User::factory()->create();
        $volunteer = Volunteer::factory()->nominatedBy($nominator)->create();

        $this->assertInstanceOf(User::class, $volunteer->nominator);
        $this->assertEquals($nominator->id, $volunteer->nominator->id);
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    public function test_full_name_accessor(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $volunteer = Volunteer::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->full_name, $volunteer->full_name);
    }

    public function test_is_expired_accessor_returns_true_when_expired(): void
    {
        $volunteer = Volunteer::factory()->expired()->create();

        $this->assertTrue($volunteer->is_expired);
    }

    public function test_is_expired_accessor_returns_false_when_not_expired(): void
    {
        $volunteer = Volunteer::factory()->create(['expiry_date' => now()->addDays(30)]);

        $this->assertFalse($volunteer->is_expired);
    }

    public function test_is_expired_accessor_returns_false_when_no_expiry_date(): void
    {
        $volunteer = Volunteer::factory()->create(['expiry_date' => null]);

        $this->assertFalse($volunteer->is_expired);
    }

    public function test_days_until_expiry_accessor(): void
    {
        $volunteer = Volunteer::factory()->create([
            'expiry_date' => now()->addDays(30),
        ]);

        $daysUntilExpiry = $volunteer->days_until_expiry;
        $this->assertGreaterThanOrEqual(29, $daysUntilExpiry);
        $this->assertLessThanOrEqual(30, $daysUntilExpiry);
    }

    public function test_days_until_expiry_returns_null_when_expired(): void
    {
        $volunteer = Volunteer::factory()->expired()->create();

        $this->assertNull($volunteer->days_until_expiry);
    }

    public function test_service_duration_accessor(): void
    {
        $volunteer = Volunteer::factory()->create([
            'recognition_date' => now()->subMonths(6),
        ]);

        $this->assertEqualsWithDelta(6.0, $volunteer->service_duration, 0.5);
    }

    public function test_level_title_accessor(): void
    {
        $volunteer1 = Volunteer::factory()->create(['level' => 1]);
        $volunteer2 = Volunteer::factory()->create(['level' => 3]);
        $volunteer3 = Volunteer::factory()->create(['level' => 5]);

        $this->assertEquals('Bronze', $volunteer1->level_title);
        $this->assertEquals('Or', $volunteer2->level_title);
        $this->assertEquals('Diamant', $volunteer3->level_title);
    }

    public function test_next_level_points_accessor(): void
    {
        $volunteer1 = Volunteer::factory()->create(['level' => 1]);
        $volunteer2 = Volunteer::factory()->create(['level' => 3]);

        $this->assertEquals(100, $volunteer1->next_level_points);
        $this->assertEquals(500, $volunteer2->next_level_points);
    }

    public function test_progress_to_next_level_accessor(): void
    {
        $volunteer = Volunteer::factory()->create(['level' => 1, 'points' => 50]);

        $this->assertEquals(50, $volunteer->progress_to_next_level);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        Volunteer::factory()->active()->count(3)->create();
        Volunteer::factory()->inactive()->count(2)->create();

        $activeVolunteers = Volunteer::active()->get();

        $this->assertCount(3, $activeVolunteers);
        $activeVolunteers->each(fn($s) => $this->assertEquals(VolunteerStatus::ACTIVE, $s->status));
    }

    public function test_scope_inactive(): void
    {
        Volunteer::factory()->active()->count(2)->create();
        Volunteer::factory()->inactive()->count(3)->create();

        $inactiveVolunteers = Volunteer::inactive()->get();

        $this->assertCount(3, $inactiveVolunteers);
    }

    public function test_scope_on_break(): void
    {
        Volunteer::factory()->active()->count(2)->create();
        Volunteer::factory()->onBreak()->count(2)->create();

        $onBreakVolunteers = Volunteer::onBreak()->get();

        $this->assertCount(2, $onBreakVolunteers);
    }

    public function test_scope_graduated(): void
    {
        Volunteer::factory()->active()->count(2)->create();
        Volunteer::factory()->graduated()->count(1)->create();

        $graduatedVolunteers = Volunteer::graduated()->get();

        $this->assertCount(1, $graduatedVolunteers);
    }

    public function test_scope_suspended(): void
    {
        Volunteer::factory()->active()->count(2)->create();
        Volunteer::factory()->suspended()->count(1)->create();

        $suspendedVolunteers = Volunteer::suspended()->get();

        $this->assertCount(1, $suspendedVolunteers);
    }

    public function test_scope_by_status(): void
    {
        Volunteer::factory()->active()->count(3)->create();
        Volunteer::factory()->onBreak()->count(2)->create();

        $activeVolunteers = Volunteer::byStatus(VolunteerStatus::ACTIVE)->get();

        $this->assertCount(3, $activeVolunteers);
    }

    public function test_scope_by_type(): void
    {
        Volunteer::factory()->volunteer()->count(3)->create();
        Volunteer::factory()->leader()->count(2)->create();

        $volunteerVolunteers = Volunteer::byType(VolunteerType::VOLUNTEER)->get();

        $this->assertCount(3, $volunteerVolunteers);
    }

    public function test_scope_by_category(): void
    {
        Volunteer::factory()->inCategory(VolunteerCategory::SERVICE)->count(3)->create();
        Volunteer::factory()->inCategory(VolunteerCategory::WORSHIP)->count(2)->create();

        $serviceVolunteers = Volunteer::byCategory(VolunteerCategory::SERVICE)->get();

        $this->assertCount(3, $serviceVolunteers);
    }

    public function test_scope_in_department(): void
    {
        $department = Department::factory()->create();
        Volunteer::factory()->inDepartment($department)->count(3)->create();
        Volunteer::factory()->count(2)->create();

        $deptVolunteers = Volunteer::inDepartment($department->id)->get();

        $this->assertCount(3, $deptVolunteers);
    }

    public function test_scope_featured(): void
    {
        Volunteer::factory()->featured()->count(2)->create();
        Volunteer::factory()->count(3)->create();

        $featuredVolunteers = Volunteer::featured()->get();

        $this->assertCount(2, $featuredVolunteers);
    }

    public function test_scope_public_profile(): void
    {
        Volunteer::factory()->publicProfile()->count(2)->create();
        Volunteer::factory()->create(['is_public_profile' => false]);

        $publicVolunteers = Volunteer::publicProfile()->get();

        $this->assertCount(2, $publicVolunteers);
    }

    public function test_scope_min_level(): void
    {
        Volunteer::factory()->create(['level' => 1]);
        Volunteer::factory()->create(['level' => 2]);
        Volunteer::factory()->create(['level' => 3]);
        Volunteer::factory()->create(['level' => 4]);

        $highLevelVolunteers = Volunteer::minLevel(3)->get();

        $this->assertCount(2, $highLevelVolunteers);
    }

    public function test_scope_expiring_soon(): void
    {
        Volunteer::factory()->expiringSoon(15)->count(2)->create();
        Volunteer::factory()->expiringSoon(45)->count(1)->create();
        Volunteer::factory()->count(2)->create();

        $expiringSoon = Volunteer::expiringSoon(30)->get();

        $this->assertCount(2, $expiringSoon);
    }

    public function test_scope_not_expired(): void
    {
        Volunteer::factory()->create(['expiry_date' => null]);
        Volunteer::factory()->create(['expiry_date' => now()->addDays(30)]);
        Volunteer::factory()->expired()->create();

        $notExpired = Volunteer::notExpired()->get();

        $this->assertCount(2, $notExpired);
    }

    public function test_scope_search(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Developer',
        ]);
        Volunteer::factory()->create([
            'user_id' => $user->id,
            'title' => 'Volunteer Developer',
        ]);
        Volunteer::factory()->count(2)->create();

        $results = Volunteer::search('Developer')->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_contactable(): void
    {
        Volunteer::factory()->create(['is_contactable' => true]);
        Volunteer::factory()->create(['is_contactable' => true]);
        Volunteer::factory()->create(['is_contactable' => false]);

        $contactable = Volunteer::contactable()->get();

        $this->assertCount(2, $contactable);
    }

    public function test_scope_available_on(): void
    {
        Volunteer::factory()->create(['available_days' => ['monday', 'tuesday']]);
        Volunteer::factory()->create(['available_days' => ['wednesday', 'thursday']]);
        Volunteer::factory()->create(['available_days' => ['monday', 'friday']]);

        $availableMonday = Volunteer::availableOn('monday')->get();

        $this->assertCount(2, $availableMonday);
    }

    // ==========================================
    // Method Tests
    // ==========================================

    public function test_can_serve_returns_true_for_active_volunteer(): void
    {
        $volunteer = Volunteer::factory()->active()->create(['expiry_date' => null]);

        $this->assertTrue($volunteer->canServe());
    }

    public function test_can_serve_returns_false_for_inactive_volunteer(): void
    {
        $volunteer = Volunteer::factory()->inactive()->create();

        $this->assertFalse($volunteer->canServe());
    }

    public function test_can_serve_returns_false_for_expired_volunteer(): void
    {
        $volunteer = Volunteer::factory()->active()->expired()->create();

        $this->assertFalse($volunteer->canServe());
    }

    public function test_is_available_on_checks_available_days(): void
    {
        $volunteer = Volunteer::factory()->create([
            'status' => VolunteerStatus::ACTIVE,
            'available_days' => ['monday', 'tuesday', 'wednesday'],
            'expiry_date' => null,
        ]);

        // Find a Monday
        $monday = Carbon::now()->startOfWeek();
        // Find a Saturday
        $saturday = Carbon::now()->endOfWeek()->subDay();

        $this->assertTrue($volunteer->isAvailableOn($monday));
        $this->assertFalse($volunteer->isAvailableOn($saturday));
    }

    public function test_activate_updates_status(): void
    {
        $volunteer = Volunteer::factory()->inactive()->create();

        $volunteer->activate();

        $this->assertEquals(VolunteerStatus::ACTIVE, $volunteer->status);
    }

    public function test_deactivate_updates_status(): void
    {
        $volunteer = Volunteer::factory()->active()->create();

        $volunteer->deactivate();

        $this->assertEquals(VolunteerStatus::INACTIVE, $volunteer->status);
    }

    public function test_set_on_break_updates_status(): void
    {
        $volunteer = Volunteer::factory()->active()->create();

        $volunteer->setOnBreak();

        $this->assertEquals(VolunteerStatus::ON_BREAK, $volunteer->status);
    }

    public function test_graduate_updates_status_and_expiry(): void
    {
        $volunteer = Volunteer::factory()->active()->create();

        $volunteer->graduate();

        $this->assertEquals(VolunteerStatus::GRADUATED, $volunteer->status);
        $this->assertNotNull($volunteer->expiry_date);
        $this->assertTrue($volunteer->expiry_date->isToday());
    }

    public function test_suspend_updates_status(): void
    {
        $volunteer = Volunteer::factory()->active()->create();

        $volunteer->suspend();

        $this->assertEquals(VolunteerStatus::SUSPENDED, $volunteer->status);
    }

    public function test_add_points_increments_and_checks_level(): void
    {
        $volunteer = Volunteer::factory()->create(['points' => 240, 'level' => 1]);

        $volunteer->addPoints(20);

        $volunteer->refresh();
        $this->assertEquals(260, $volunteer->points);
        $this->assertEquals(2, $volunteer->level); // Should level up at 250 points
    }

    public function test_remove_points_decrements(): void
    {
        $volunteer = Volunteer::factory()->create(['points' => 100]);

        $volunteer->removePoints(30);

        $this->assertEquals(70, $volunteer->fresh()->points);
    }

    public function test_remove_points_cannot_go_negative(): void
    {
        $volunteer = Volunteer::factory()->create(['points' => 20]);

        $volunteer->removePoints(50);

        $this->assertEquals(0, $volunteer->fresh()->points);
    }

    public function test_add_hours_served_increments(): void
    {
        $volunteer = Volunteer::factory()->create(['total_hours_served' => 100]);

        $volunteer->addHoursServed(10);

        $this->assertEquals(110, $volunteer->fresh()->total_hours_served);
    }

    public function test_add_achievement(): void
    {
        $volunteer = Volunteer::factory()->create(['achievements' => ['First Achievement']]);

        $volunteer->addAchievement('Second Achievement');

        $this->assertContains('Second Achievement', $volunteer->fresh()->achievements);
    }

    public function test_add_achievement_does_not_duplicate(): void
    {
        $volunteer = Volunteer::factory()->create(['achievements' => ['First Achievement']]);

        $volunteer->addAchievement('First Achievement');

        $this->assertCount(1, $volunteer->fresh()->achievements);
    }

    public function test_remove_achievement(): void
    {
        $volunteer = Volunteer::factory()->create(['achievements' => ['First', 'Second', 'Third']]);

        $volunteer->removeAchievement('Second');

        $achievements = $volunteer->fresh()->achievements;
        $this->assertNotContains('Second', $achievements);
        $this->assertCount(2, $achievements);
    }

    public function test_has_achievement(): void
    {
        $volunteer = Volunteer::factory()->create(['achievements' => ['First', 'Second']]);

        $this->assertTrue($volunteer->hasAchievement('First'));
        $this->assertFalse($volunteer->hasAchievement('Third'));
    }

    public function test_add_badge(): void
    {
        $volunteer = Volunteer::factory()->create(['badges' => ['Badge1']]);

        $volunteer->addBadge('Badge2');

        $this->assertContains('Badge2', $volunteer->fresh()->badges);
    }

    public function test_remove_badge(): void
    {
        $volunteer = Volunteer::factory()->create(['badges' => ['Badge1', 'Badge2']]);

        $volunteer->removeBadge('Badge1');

        $badges = $volunteer->fresh()->badges;
        $this->assertNotContains('Badge1', $badges);
        $this->assertCount(1, $badges);
    }

    public function test_has_badge(): void
    {
        $volunteer = Volunteer::factory()->create(['badges' => ['Badge1', 'Badge2']]);

        $this->assertTrue($volunteer->hasBadge('Badge1'));
        $this->assertFalse($volunteer->hasBadge('Badge3'));
    }

    public function test_has_skill(): void
    {
        $volunteer = Volunteer::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $this->assertTrue($volunteer->hasSkill('PHP'));
        $this->assertTrue($volunteer->hasSkill('php')); // Case insensitive
        $this->assertFalse($volunteer->hasSkill('Python'));
    }

    public function test_add_skill(): void
    {
        $volunteer = Volunteer::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $volunteer->addSkill('React');

        $this->assertContains('React', $volunteer->fresh()->skills);
    }

    public function test_add_skill_does_not_duplicate(): void
    {
        $volunteer = Volunteer::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $volunteer->addSkill('PHP');

        $this->assertCount(2, $volunteer->fresh()->skills);
    }

    public function test_remove_skill(): void
    {
        $volunteer = Volunteer::factory()->create(['skills' => ['PHP', 'Laravel', 'React']]);

        $volunteer->removeSkill('Laravel');

        $skills = $volunteer->fresh()->skills;
        $this->assertNotContains('Laravel', $skills);
        $this->assertCount(2, $skills);
    }

    public function test_set_featured(): void
    {
        $volunteer = Volunteer::factory()->create(['is_featured' => false]);

        $volunteer->setFeatured(true);

        $this->assertTrue($volunteer->fresh()->is_featured);
    }

    public function test_renew_for_months(): void
    {
        $volunteer = Volunteer::factory()->expired()->create();

        $volunteer->renewForMonths(12);

        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::ACTIVE, $volunteer->status);
        $this->assertTrue($volunteer->expiry_date->isFuture());
    }

    // ==========================================
    // Cast Tests
    // ==========================================

    public function test_status_is_cast_to_enum(): void
    {
        $volunteer = Volunteer::factory()->active()->create();

        $this->assertInstanceOf(VolunteerStatus::class, $volunteer->status);
        $this->assertEquals(VolunteerStatus::ACTIVE, $volunteer->status);
    }

    public function test_type_is_cast_to_enum(): void
    {
        $volunteer = Volunteer::factory()->volunteer()->create();

        $this->assertInstanceOf(VolunteerType::class, $volunteer->type);
        $this->assertEquals(VolunteerType::VOLUNTEER, $volunteer->type);
    }

    public function test_category_is_cast_to_enum(): void
    {
        $volunteer = Volunteer::factory()->inCategory(VolunteerCategory::SERVICE)->create();

        $this->assertInstanceOf(VolunteerCategory::class, $volunteer->category);
        $this->assertEquals(VolunteerCategory::SERVICE, $volunteer->category);
    }

    public function test_date_fields_are_cast_to_carbon(): void
    {
        $volunteer = Volunteer::factory()->create([
            'recognition_date' => '2024-01-15',
            'expiry_date' => '2025-01-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $volunteer->recognition_date);
        $this->assertInstanceOf(Carbon::class, $volunteer->expiry_date);
    }

    public function test_array_fields_are_cast_correctly(): void
    {
        $volunteer = Volunteer::factory()->create([
            'available_days' => ['monday', 'tuesday'],
            'skills' => ['PHP', 'Laravel'],
            'achievements' => ['First'],
            'badges' => ['Badge1'],
            'areas_of_service' => ['Accueil'],
        ]);

        $this->assertIsArray($volunteer->available_days);
        $this->assertIsArray($volunteer->skills);
        $this->assertIsArray($volunteer->achievements);
        $this->assertIsArray($volunteer->badges);
        $this->assertIsArray($volunteer->areas_of_service);
    }

    public function test_boolean_fields_are_cast_correctly(): void
    {
        $volunteer = Volunteer::factory()->create([
            'is_contactable' => true,
            'receive_notifications' => false,
            'is_public_profile' => true,
            'is_featured' => false,
        ]);

        $this->assertIsBool($volunteer->is_contactable);
        $this->assertIsBool($volunteer->receive_notifications);
        $this->assertIsBool($volunteer->is_public_profile);
        $this->assertIsBool($volunteer->is_featured);
    }

    // ==========================================
    // Soft Delete Tests
    // ==========================================

    public function test_volunteer_can_be_soft_deleted(): void
    {
        $volunteer = Volunteer::factory()->create();

        $volunteer->delete();

        $this->assertSoftDeleted('volunteers', ['id' => $volunteer->id]);
        $this->assertNull(Volunteer::find($volunteer->id));
        $this->assertNotNull(Volunteer::withTrashed()->find($volunteer->id));
    }

    public function test_soft_deleted_volunteer_can_be_restored(): void
    {
        $volunteer = Volunteer::factory()->create();
        $volunteer->delete();

        $volunteer->restore();

        $this->assertDatabaseHas('volunteers', ['id' => $volunteer->id, 'deleted_at' => null]);
    }
}
