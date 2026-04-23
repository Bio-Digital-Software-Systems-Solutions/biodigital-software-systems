<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\CreatesPermissions;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    public $user;

    public $leader;

    use CreatesPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();

        $this->user = User::factory()->create();
        $this->leader = User::factory()->create();
    }

    public function test_index_displays_groups(): void
    {
        Group::factory()->count(3)->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Index')
            ->has('groups.data', 3)
        );
    }

    public function test_create_displays_form(): void
    {
        $this->user->givePermissionTo('create groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.create'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Create')
            ->has('users')
        );
    }

    public function test_store_creates_group(): void
    {
        $this->user->givePermissionTo('create groups');

        $groupData = [
            'name' => 'Test Group',
            'description' => 'Test group description',
            'code' => 'TEST-GRP',
            'max_members' => 10,
            'leader_id' => $this->leader->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('groups.store'), $groupData);

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'Group created successfully.');

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'code' => 'TEST-GRP',
            'max_members' => 10,
            'leader_id' => $this->leader->id,
            'is_active' => true,
        ]);

        $group = Group::where('code', 'TEST-GRP')->first();
        $this->assertTrue($group->users->contains($this->leader));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->user->givePermissionTo('create groups');

        $response = $this->actingAs($this->user)
            ->post(route('groups.store'), []);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_store_validates_unique_code(): void
    {
        Group::factory()->create(['code' => 'DUPLICATE']);

        $this->user->givePermissionTo('create groups');

        $response = $this->actingAs($this->user)
            ->post(route('groups.store'), [
                'name' => 'Test Group',
                'code' => 'DUPLICATE',
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * Extract Inertia page data from HTML response when viewData is unavailable.
     */
    protected function getInertiaPageData(TestResponse $response): array
    {
        $content = $response->content();
        preg_match('/data-page="([^"]*)"/', $content, $matches);
        $this->assertNotEmpty($matches, 'No Inertia data-page found in response');

        return json_decode(html_entity_decode($matches[1]), true);
    }

    public function test_show_displays_group(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $this->assertEquals('Groups/Show', $page['component']);
        $this->assertEquals($group->id, $page['props']['group']['id']);
        $this->assertEquals($group->name, $page['props']['group']['name']);
    }

    public function test_show_includes_tab_data(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $props = $page['props'];
        $this->assertArrayHasKey('meetings', $props);
        $this->assertArrayHasKey('appointments', $props);
        $this->assertArrayHasKey('activities', $props);
        $this->assertArrayHasKey('statistics', $props);
        $this->assertArrayHasKey('todos', $props['statistics']);
        $this->assertArrayHasKey('performance', $props['statistics']);
        $this->assertArrayHasKey('members', $props['statistics']);
    }

    public function test_show_includes_statistics_with_todos(): void
    {
        $group = Group::factory()->create();

        \App\Models\GroupTodo::factory()->count(5)->create([
            'group_id' => $group->id,
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $this->user->id,
        ]);
        \App\Models\GroupTodo::factory()->count(3)->create([
            'group_id' => $group->id,
            'status' => 'pending',
        ]);

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $stats = $page['props']['statistics'];
        $this->assertEquals(8, $stats['todos']['total']);
        $this->assertEquals(5, $stats['todos']['completed']);
        $this->assertEquals(3, $stats['todos']['pending']);
    }

    public function test_statistics_include_available_years(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $stats = $page['props']['statistics'];
        $this->assertArrayHasKey('available_years', $stats);
        $this->assertIsArray($stats['available_years']);
        $this->assertContains(now()->year, $stats['available_years']);
    }

    public function test_task_evolution_has_all_granularities(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $stats = $page['props']['statistics'];
        $currentYear = (string) now()->year;

        $this->assertArrayHasKey('task_evolution', $stats);
        $this->assertArrayHasKey($currentYear, $stats['task_evolution']);

        $yearData = $stats['task_evolution'][$currentYear];
        $this->assertArrayHasKey('weekly', $yearData);
        $this->assertArrayHasKey('monthly', $yearData);
        $this->assertArrayHasKey('quarterly', $yearData);
        $this->assertArrayHasKey('semester', $yearData);
        $this->assertArrayHasKey('yearly', $yearData);
    }

    public function test_member_growth_has_all_granularities(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $stats = $page['props']['statistics'];
        $currentYear = (string) now()->year;

        $this->assertArrayHasKey('member_growth', $stats);
        $this->assertArrayHasKey($currentYear, $stats['member_growth']);

        $yearData = $stats['member_growth'][$currentYear];
        $this->assertArrayHasKey('weekly', $yearData);
        $this->assertArrayHasKey('monthly', $yearData);
        $this->assertArrayHasKey('quarterly', $yearData);
        $this->assertArrayHasKey('semester', $yearData);
        $this->assertArrayHasKey('yearly', $yearData);
    }

    public function test_monthly_view_always_shows_12_months(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $currentYear = (string) now()->year;
        $monthly = $page['props']['statistics']['task_evolution'][$currentYear]['monthly'];

        $this->assertCount(12, $monthly);
        $this->assertArrayHasKey('label', $monthly[0]);
        $this->assertArrayHasKey('period', $monthly[0]);
        $this->assertArrayHasKey('created', $monthly[0]);
        $this->assertArrayHasKey('completed', $monthly[0]);
    }

    public function test_weekly_view_contains_weeks_with_7_days_each(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $currentYear = (string) now()->year;
        $weekly = $page['props']['statistics']['task_evolution'][$currentYear]['weekly'];

        $this->assertNotEmpty($weekly);

        $firstWeek = $weekly[0];
        $this->assertArrayHasKey('week_number', $firstWeek);
        $this->assertArrayHasKey('label', $firstWeek);
        $this->assertArrayHasKey('start_date', $firstWeek);
        $this->assertArrayHasKey('end_date', $firstWeek);
        $this->assertArrayHasKey('days', $firstWeek);
        $this->assertCount(7, $firstWeek['days']);

        $firstDay = $firstWeek['days'][0];
        $this->assertArrayHasKey('label', $firstDay);
        $this->assertArrayHasKey('created', $firstDay);
        $this->assertArrayHasKey('completed', $firstDay);
    }

    public function test_member_growth_monthly_always_shows_12_months(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create();
        $group->users()->attach($member, ['joined_at' => now()]);

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $currentYear = (string) now()->year;
        $monthly = $page['props']['statistics']['member_growth'][$currentYear]['monthly'];

        $this->assertCount(12, $monthly);
        $lastEntry = end($monthly);
        $this->assertArrayHasKey('new_members', $lastEntry);
        $this->assertArrayHasKey('total_members', $lastEntry);
    }

    public function test_member_growth_weekly_contains_7_days(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();

        $page = $this->getInertiaPageData($response);
        $currentYear = (string) now()->year;
        $weekly = $page['props']['statistics']['member_growth'][$currentYear]['weekly'];

        $this->assertNotEmpty($weekly);
        $firstWeek = $weekly[0];
        $this->assertCount(7, $firstWeek['days']);
    }

    public function test_edit_displays_form(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('edit groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Edit')
            ->where('group.id', $group->id)
            ->has('users')
        );
    }

    public function test_update_modifies_group(): void
    {
        $group = Group::factory()->create([
            'name' => 'Original Name',
            'code' => 'ORIG',
            'max_members' => 5,
        ]);

        $this->user->givePermissionTo('edit groups');

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'code' => 'UPD',
            'max_members' => 15,
            'leader_id' => $this->leader->id,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('groups.update', $group), $updateData);

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'Group updated successfully.');

        $group->refresh();
        $this->assertEquals('Updated Name', $group->name);
        $this->assertEquals('UPD', $group->code);
        $this->assertEquals(15, $group->max_members);
        $this->assertEquals($this->leader->id, $group->leader_id);
        $this->assertFalse($group->is_active);
    }

    public function test_destroy_deletes_group(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('delete groups');

        $response = $this->actingAs($this->user)
            ->delete(route('groups.destroy', $group));

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'Group deleted successfully.');

        $this->assertModelMissing($group);
    }

    public function test_user_can_join_group(): void
    {
        $group = Group::factory()->create([
            'is_active' => true,
            'max_members' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Successfully joined the group.');

        $group->refresh();
        $this->assertTrue($group->isMember($this->user));
    }

    public function test_user_cannot_join_inactive_group(): void
    {
        $group = Group::factory()->create([
            'is_active' => false,
            'max_members' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot join this group.');

        $group->refresh();
        $this->assertFalse($group->isMember($this->user));
    }

    public function test_user_cannot_join_full_group(): void
    {
        $group = Group::factory()->create([
            'is_active' => true,
            'max_members' => 1,
        ]);

        $otherUser = User::factory()->create();
        $group->users()->attach($otherUser, ['joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot join this group.');

        $group->refresh();
        $this->assertFalse($group->isMember($this->user));
    }

    public function test_user_cannot_join_same_group_twice(): void
    {
        $group = Group::factory()->create([
            'is_active' => true,
            'max_members' => 10,
        ]);

        $group->users()->attach($this->user, ['joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You are already a member of this group.');
    }

    public function test_user_can_leave_group(): void
    {
        $group = Group::factory()->create();
        $group->users()->attach($this->user, ['joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->delete(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Successfully left the group.');

        $group->refresh();
        $this->assertFalse($group->isMember($this->user));
    }

    public function test_leader_cannot_leave_group(): void
    {
        $group = Group::factory()->create(['leader_id' => $this->user->id]);
        $group->users()->attach($this->user, ['joined_at' => now()]);

        $response = $this->actingAs($this->user)
            ->delete(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Group leaders cannot leave their groups.');

        $group->refresh();
        $this->assertTrue($group->isMember($this->user));
    }

    public function test_non_member_cannot_leave_group(): void
    {
        $group = Group::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You are not a member of this group.');
    }

    public function test_can_filter_active_groups(): void
    {
        Group::factory()->count(2)->create(['is_active' => true]);
        Group::factory()->create(['is_active' => false]);

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Index')
            ->has('groups.data', 2)
        );
    }

    public function test_can_filter_groups_with_space(): void
    {
        Group::factory()->create([
            'is_active' => true,
            'max_members' => 5,
        ]);

        $fullGroup = Group::factory()->create([
            'is_active' => true,
            'max_members' => 1,
        ]);

        $user = User::factory()->create();
        $fullGroup->users()->attach($user, ['joined_at' => now()]);

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.index', ['status' => 'with_space']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Index')
            ->has('groups.data', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_groups(): void
    {
        Group::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('groups.index'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_max_members_validation(): void
    {
        $this->user->givePermissionTo('create groups');

        $response = $this->actingAs($this->user)
            ->post(route('groups.store'), [
                'name' => 'Test Group',
                'code' => 'TEST',
                'max_members' => 0,
            ]);

        $response->assertSessionHasErrors(['max_members']);
    }
}
