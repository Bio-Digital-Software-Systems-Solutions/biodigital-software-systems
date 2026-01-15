<?php

namespace Tests\Unit\Models;

use App\Enums\Star\StarCategory;
use App\Enums\Star\StarStatus;
use App\Enums\Star\StarType;
use App\Models\Department;
use App\Models\Star;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarModelTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_star_can_be_created(): void
    {
        $user = User::factory()->create();
        $star = Star::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('stars', [
            'id' => $star->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_star_uses_uuid_for_route_key(): void
    {
        $star = Star::factory()->create();

        $this->assertEquals('uuid', $star->getRouteKeyName());
        $this->assertNotNull($star->uuid);
        $this->assertEquals(36, strlen($star->uuid));
    }

    public function test_star_generates_unique_star_number(): void
    {
        $star1 = Star::factory()->create();
        $star2 = Star::factory()->create();

        $this->assertNotNull($star1->star_number);
        $this->assertNotNull($star2->star_number);
        $this->assertNotEquals($star1->star_number, $star2->star_number);
        $this->assertStringStartsWith('STR', $star1->star_number);
    }

    public function test_star_number_contains_year(): void
    {
        $star = Star::factory()->create();
        $year = date('Y');

        $this->assertStringContainsString($year, $star->star_number);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_star_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $star = Star::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $star->user);
        $this->assertEquals($user->id, $star->user->id);
    }

    public function test_star_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $star = Star::factory()->inDepartment($department)->create();

        $this->assertInstanceOf(Department::class, $star->department);
        $this->assertEquals($department->id, $star->department->id);
    }

    public function test_star_can_have_nominator(): void
    {
        $nominator = User::factory()->create();
        $star = Star::factory()->nominatedBy($nominator)->create();

        $this->assertInstanceOf(User::class, $star->nominator);
        $this->assertEquals($nominator->id, $star->nominator->id);
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
        $star = Star::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->full_name, $star->full_name);
    }

    public function test_is_expired_accessor_returns_true_when_expired(): void
    {
        $star = Star::factory()->expired()->create();

        $this->assertTrue($star->is_expired);
    }

    public function test_is_expired_accessor_returns_false_when_not_expired(): void
    {
        $star = Star::factory()->create(['expiry_date' => now()->addDays(30)]);

        $this->assertFalse($star->is_expired);
    }

    public function test_is_expired_accessor_returns_false_when_no_expiry_date(): void
    {
        $star = Star::factory()->create(['expiry_date' => null]);

        $this->assertFalse($star->is_expired);
    }

    public function test_days_until_expiry_accessor(): void
    {
        $star = Star::factory()->create([
            'expiry_date' => now()->addDays(30),
        ]);

        $daysUntilExpiry = $star->days_until_expiry;
        $this->assertGreaterThanOrEqual(29, $daysUntilExpiry);
        $this->assertLessThanOrEqual(30, $daysUntilExpiry);
    }

    public function test_days_until_expiry_returns_null_when_expired(): void
    {
        $star = Star::factory()->expired()->create();

        $this->assertNull($star->days_until_expiry);
    }

    public function test_service_duration_accessor(): void
    {
        $star = Star::factory()->create([
            'recognition_date' => now()->subMonths(6),
        ]);

        $this->assertEqualsWithDelta(6.0, $star->service_duration, 0.5);
    }

    public function test_level_title_accessor(): void
    {
        $star1 = Star::factory()->create(['level' => 1]);
        $star2 = Star::factory()->create(['level' => 3]);
        $star3 = Star::factory()->create(['level' => 5]);

        $this->assertEquals('Bronze', $star1->level_title);
        $this->assertEquals('Or', $star2->level_title);
        $this->assertEquals('Diamant', $star3->level_title);
    }

    public function test_next_level_points_accessor(): void
    {
        $star1 = Star::factory()->create(['level' => 1]);
        $star2 = Star::factory()->create(['level' => 3]);

        $this->assertEquals(100, $star1->next_level_points);
        $this->assertEquals(500, $star2->next_level_points);
    }

    public function test_progress_to_next_level_accessor(): void
    {
        $star = Star::factory()->create(['level' => 1, 'points' => 50]);

        $this->assertEquals(50, $star->progress_to_next_level);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        Star::factory()->active()->count(3)->create();
        Star::factory()->inactive()->count(2)->create();

        $activeStars = Star::active()->get();

        $this->assertCount(3, $activeStars);
        $activeStars->each(fn($s) => $this->assertEquals(StarStatus::ACTIVE, $s->status));
    }

    public function test_scope_inactive(): void
    {
        Star::factory()->active()->count(2)->create();
        Star::factory()->inactive()->count(3)->create();

        $inactiveStars = Star::inactive()->get();

        $this->assertCount(3, $inactiveStars);
    }

    public function test_scope_on_break(): void
    {
        Star::factory()->active()->count(2)->create();
        Star::factory()->onBreak()->count(2)->create();

        $onBreakStars = Star::onBreak()->get();

        $this->assertCount(2, $onBreakStars);
    }

    public function test_scope_graduated(): void
    {
        Star::factory()->active()->count(2)->create();
        Star::factory()->graduated()->count(1)->create();

        $graduatedStars = Star::graduated()->get();

        $this->assertCount(1, $graduatedStars);
    }

    public function test_scope_suspended(): void
    {
        Star::factory()->active()->count(2)->create();
        Star::factory()->suspended()->count(1)->create();

        $suspendedStars = Star::suspended()->get();

        $this->assertCount(1, $suspendedStars);
    }

    public function test_scope_by_status(): void
    {
        Star::factory()->active()->count(3)->create();
        Star::factory()->onBreak()->count(2)->create();

        $activeStars = Star::byStatus(StarStatus::ACTIVE)->get();

        $this->assertCount(3, $activeStars);
    }

    public function test_scope_by_type(): void
    {
        Star::factory()->volunteer()->count(3)->create();
        Star::factory()->leader()->count(2)->create();

        $volunteerStars = Star::byType(StarType::VOLUNTEER)->get();

        $this->assertCount(3, $volunteerStars);
    }

    public function test_scope_by_category(): void
    {
        Star::factory()->inCategory(StarCategory::SERVICE)->count(3)->create();
        Star::factory()->inCategory(StarCategory::WORSHIP)->count(2)->create();

        $serviceStars = Star::byCategory(StarCategory::SERVICE)->get();

        $this->assertCount(3, $serviceStars);
    }

    public function test_scope_in_department(): void
    {
        $department = Department::factory()->create();
        Star::factory()->inDepartment($department)->count(3)->create();
        Star::factory()->count(2)->create();

        $deptStars = Star::inDepartment($department->id)->get();

        $this->assertCount(3, $deptStars);
    }

    public function test_scope_featured(): void
    {
        Star::factory()->featured()->count(2)->create();
        Star::factory()->count(3)->create();

        $featuredStars = Star::featured()->get();

        $this->assertCount(2, $featuredStars);
    }

    public function test_scope_public_profile(): void
    {
        Star::factory()->publicProfile()->count(2)->create();
        Star::factory()->create(['is_public_profile' => false]);

        $publicStars = Star::publicProfile()->get();

        $this->assertCount(2, $publicStars);
    }

    public function test_scope_min_level(): void
    {
        Star::factory()->create(['level' => 1]);
        Star::factory()->create(['level' => 2]);
        Star::factory()->create(['level' => 3]);
        Star::factory()->create(['level' => 4]);

        $highLevelStars = Star::minLevel(3)->get();

        $this->assertCount(2, $highLevelStars);
    }

    public function test_scope_expiring_soon(): void
    {
        Star::factory()->expiringSoon(15)->count(2)->create();
        Star::factory()->expiringSoon(45)->count(1)->create();
        Star::factory()->count(2)->create();

        $expiringSoon = Star::expiringSoon(30)->get();

        $this->assertCount(2, $expiringSoon);
    }

    public function test_scope_not_expired(): void
    {
        Star::factory()->create(['expiry_date' => null]);
        Star::factory()->create(['expiry_date' => now()->addDays(30)]);
        Star::factory()->expired()->create();

        $notExpired = Star::notExpired()->get();

        $this->assertCount(2, $notExpired);
    }

    public function test_scope_search(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Developer',
        ]);
        Star::factory()->create([
            'user_id' => $user->id,
            'title' => 'Star Developer',
        ]);
        Star::factory()->count(2)->create();

        $results = Star::search('Developer')->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_contactable(): void
    {
        Star::factory()->create(['is_contactable' => true]);
        Star::factory()->create(['is_contactable' => true]);
        Star::factory()->create(['is_contactable' => false]);

        $contactable = Star::contactable()->get();

        $this->assertCount(2, $contactable);
    }

    public function test_scope_available_on(): void
    {
        Star::factory()->create(['available_days' => ['monday', 'tuesday']]);
        Star::factory()->create(['available_days' => ['wednesday', 'thursday']]);
        Star::factory()->create(['available_days' => ['monday', 'friday']]);

        $availableMonday = Star::availableOn('monday')->get();

        $this->assertCount(2, $availableMonday);
    }

    // ==========================================
    // Method Tests
    // ==========================================

    public function test_can_serve_returns_true_for_active_star(): void
    {
        $star = Star::factory()->active()->create(['expiry_date' => null]);

        $this->assertTrue($star->canServe());
    }

    public function test_can_serve_returns_false_for_inactive_star(): void
    {
        $star = Star::factory()->inactive()->create();

        $this->assertFalse($star->canServe());
    }

    public function test_can_serve_returns_false_for_expired_star(): void
    {
        $star = Star::factory()->active()->expired()->create();

        $this->assertFalse($star->canServe());
    }

    public function test_is_available_on_checks_available_days(): void
    {
        $star = Star::factory()->create([
            'status' => StarStatus::ACTIVE,
            'available_days' => ['monday', 'tuesday', 'wednesday'],
            'expiry_date' => null,
        ]);

        // Find a Monday
        $monday = Carbon::now()->startOfWeek();
        // Find a Saturday
        $saturday = Carbon::now()->endOfWeek()->subDay();

        $this->assertTrue($star->isAvailableOn($monday));
        $this->assertFalse($star->isAvailableOn($saturday));
    }

    public function test_activate_updates_status(): void
    {
        $star = Star::factory()->inactive()->create();

        $star->activate();

        $this->assertEquals(StarStatus::ACTIVE, $star->status);
    }

    public function test_deactivate_updates_status(): void
    {
        $star = Star::factory()->active()->create();

        $star->deactivate();

        $this->assertEquals(StarStatus::INACTIVE, $star->status);
    }

    public function test_set_on_break_updates_status(): void
    {
        $star = Star::factory()->active()->create();

        $star->setOnBreak();

        $this->assertEquals(StarStatus::ON_BREAK, $star->status);
    }

    public function test_graduate_updates_status_and_expiry(): void
    {
        $star = Star::factory()->active()->create();

        $star->graduate();

        $this->assertEquals(StarStatus::GRADUATED, $star->status);
        $this->assertNotNull($star->expiry_date);
        $this->assertTrue($star->expiry_date->isToday());
    }

    public function test_suspend_updates_status(): void
    {
        $star = Star::factory()->active()->create();

        $star->suspend();

        $this->assertEquals(StarStatus::SUSPENDED, $star->status);
    }

    public function test_add_points_increments_and_checks_level(): void
    {
        $star = Star::factory()->create(['points' => 240, 'level' => 1]);

        $star->addPoints(20);

        $star->refresh();
        $this->assertEquals(260, $star->points);
        $this->assertEquals(2, $star->level); // Should level up at 250 points
    }

    public function test_remove_points_decrements(): void
    {
        $star = Star::factory()->create(['points' => 100]);

        $star->removePoints(30);

        $this->assertEquals(70, $star->fresh()->points);
    }

    public function test_remove_points_cannot_go_negative(): void
    {
        $star = Star::factory()->create(['points' => 20]);

        $star->removePoints(50);

        $this->assertEquals(0, $star->fresh()->points);
    }

    public function test_add_hours_served_increments(): void
    {
        $star = Star::factory()->create(['total_hours_served' => 100]);

        $star->addHoursServed(10);

        $this->assertEquals(110, $star->fresh()->total_hours_served);
    }

    public function test_add_achievement(): void
    {
        $star = Star::factory()->create(['achievements' => ['First Achievement']]);

        $star->addAchievement('Second Achievement');

        $this->assertContains('Second Achievement', $star->fresh()->achievements);
    }

    public function test_add_achievement_does_not_duplicate(): void
    {
        $star = Star::factory()->create(['achievements' => ['First Achievement']]);

        $star->addAchievement('First Achievement');

        $this->assertCount(1, $star->fresh()->achievements);
    }

    public function test_remove_achievement(): void
    {
        $star = Star::factory()->create(['achievements' => ['First', 'Second', 'Third']]);

        $star->removeAchievement('Second');

        $achievements = $star->fresh()->achievements;
        $this->assertNotContains('Second', $achievements);
        $this->assertCount(2, $achievements);
    }

    public function test_has_achievement(): void
    {
        $star = Star::factory()->create(['achievements' => ['First', 'Second']]);

        $this->assertTrue($star->hasAchievement('First'));
        $this->assertFalse($star->hasAchievement('Third'));
    }

    public function test_add_badge(): void
    {
        $star = Star::factory()->create(['badges' => ['Badge1']]);

        $star->addBadge('Badge2');

        $this->assertContains('Badge2', $star->fresh()->badges);
    }

    public function test_remove_badge(): void
    {
        $star = Star::factory()->create(['badges' => ['Badge1', 'Badge2']]);

        $star->removeBadge('Badge1');

        $badges = $star->fresh()->badges;
        $this->assertNotContains('Badge1', $badges);
        $this->assertCount(1, $badges);
    }

    public function test_has_badge(): void
    {
        $star = Star::factory()->create(['badges' => ['Badge1', 'Badge2']]);

        $this->assertTrue($star->hasBadge('Badge1'));
        $this->assertFalse($star->hasBadge('Badge3'));
    }

    public function test_has_skill(): void
    {
        $star = Star::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $this->assertTrue($star->hasSkill('PHP'));
        $this->assertTrue($star->hasSkill('php')); // Case insensitive
        $this->assertFalse($star->hasSkill('Python'));
    }

    public function test_add_skill(): void
    {
        $star = Star::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $star->addSkill('React');

        $this->assertContains('React', $star->fresh()->skills);
    }

    public function test_add_skill_does_not_duplicate(): void
    {
        $star = Star::factory()->create(['skills' => ['PHP', 'Laravel']]);

        $star->addSkill('PHP');

        $this->assertCount(2, $star->fresh()->skills);
    }

    public function test_remove_skill(): void
    {
        $star = Star::factory()->create(['skills' => ['PHP', 'Laravel', 'React']]);

        $star->removeSkill('Laravel');

        $skills = $star->fresh()->skills;
        $this->assertNotContains('Laravel', $skills);
        $this->assertCount(2, $skills);
    }

    public function test_set_featured(): void
    {
        $star = Star::factory()->create(['is_featured' => false]);

        $star->setFeatured(true);

        $this->assertTrue($star->fresh()->is_featured);
    }

    public function test_renew_for_months(): void
    {
        $star = Star::factory()->expired()->create();

        $star->renewForMonths(12);

        $star->refresh();
        $this->assertEquals(StarStatus::ACTIVE, $star->status);
        $this->assertTrue($star->expiry_date->isFuture());
    }

    // ==========================================
    // Cast Tests
    // ==========================================

    public function test_status_is_cast_to_enum(): void
    {
        $star = Star::factory()->active()->create();

        $this->assertInstanceOf(StarStatus::class, $star->status);
        $this->assertEquals(StarStatus::ACTIVE, $star->status);
    }

    public function test_type_is_cast_to_enum(): void
    {
        $star = Star::factory()->volunteer()->create();

        $this->assertInstanceOf(StarType::class, $star->type);
        $this->assertEquals(StarType::VOLUNTEER, $star->type);
    }

    public function test_category_is_cast_to_enum(): void
    {
        $star = Star::factory()->inCategory(StarCategory::SERVICE)->create();

        $this->assertInstanceOf(StarCategory::class, $star->category);
        $this->assertEquals(StarCategory::SERVICE, $star->category);
    }

    public function test_date_fields_are_cast_to_carbon(): void
    {
        $star = Star::factory()->create([
            'recognition_date' => '2024-01-15',
            'expiry_date' => '2025-01-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $star->recognition_date);
        $this->assertInstanceOf(Carbon::class, $star->expiry_date);
    }

    public function test_array_fields_are_cast_correctly(): void
    {
        $star = Star::factory()->create([
            'available_days' => ['monday', 'tuesday'],
            'skills' => ['PHP', 'Laravel'],
            'achievements' => ['First'],
            'badges' => ['Badge1'],
            'areas_of_service' => ['Accueil'],
        ]);

        $this->assertIsArray($star->available_days);
        $this->assertIsArray($star->skills);
        $this->assertIsArray($star->achievements);
        $this->assertIsArray($star->badges);
        $this->assertIsArray($star->areas_of_service);
    }

    public function test_boolean_fields_are_cast_correctly(): void
    {
        $star = Star::factory()->create([
            'is_contactable' => true,
            'receive_notifications' => false,
            'is_public_profile' => true,
            'is_featured' => false,
        ]);

        $this->assertIsBool($star->is_contactable);
        $this->assertIsBool($star->receive_notifications);
        $this->assertIsBool($star->is_public_profile);
        $this->assertIsBool($star->is_featured);
    }

    // ==========================================
    // Soft Delete Tests
    // ==========================================

    public function test_star_can_be_soft_deleted(): void
    {
        $star = Star::factory()->create();

        $star->delete();

        $this->assertSoftDeleted('stars', ['id' => $star->id]);
        $this->assertNull(Star::find($star->id));
        $this->assertNotNull(Star::withTrashed()->find($star->id));
    }

    public function test_soft_deleted_star_can_be_restored(): void
    {
        $star = Star::factory()->create();
        $star->delete();

        $star->restore();

        $this->assertDatabaseHas('stars', ['id' => $star->id, 'deleted_at' => null]);
    }
}
