<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_belongs_to_leader(): void
    {
        $leader = User::factory()->create();
        $group = Group::factory()->create(['leader_id' => $leader->id]);

        $this->assertInstanceOf(User::class, $group->leader);
        $this->assertEquals($leader->id, $group->leader->id);
    }

    public function test_group_has_many_users(): void
    {
        $group = Group::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertCount(3, $group->users);
        $this->assertInstanceOf(User::class, $group->users->first());
    }

    public function test_members_count_attribute_returns_correct_count(): void
    {
        $group = Group::factory()->create();
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertEquals(5, $group->members_count);
    }

    public function test_is_at_capacity_returns_true_when_at_max_members(): void
    {
        $group = Group::factory()->create(['max_members' => 3]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertTrue($group->isAtCapacity());
    }

    public function test_is_at_capacity_returns_false_when_under_max_members(): void
    {
        $group = Group::factory()->create(['max_members' => 5]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertFalse($group->isAtCapacity());
    }

    public function test_is_at_capacity_returns_false_when_no_max_members_set(): void
    {
        $group = Group::factory()->create(['max_members' => null]);
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertFalse($group->isAtCapacity());
    }

    public function test_can_join_returns_true_when_active_and_has_space(): void
    {
        $group = Group::factory()->create([
            'is_active' => true,
            'max_members' => 5,
        ]);

        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertTrue($group->canJoin());
    }

    public function test_can_join_returns_false_when_inactive(): void
    {
        $group = Group::factory()->create([
            'is_active' => false,
            'max_members' => 5,
        ]);

        $this->assertFalse($group->canJoin());
    }

    public function test_can_join_returns_false_when_at_capacity(): void
    {
        $group = Group::factory()->create([
            'is_active' => true,
            'max_members' => 2,
        ]);

        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $group->users()->attach($user, ['joined_at' => now()]);
        }

        $this->assertFalse($group->canJoin());
    }

    public function test_is_member_returns_true_when_user_is_member(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $group->users()->attach($user, ['joined_at' => now()]);

        $this->assertTrue($group->isMember($user));
    }

    public function test_is_member_returns_false_when_user_is_not_member(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($group->isMember($user));
    }

    public function test_is_leader_returns_true_when_user_is_leader(): void
    {
        $leader = User::factory()->create();
        $group = Group::factory()->create(['leader_id' => $leader->id]);

        $this->assertTrue($group->isLeader($leader));
    }

    public function test_is_leader_returns_false_when_user_is_not_leader(): void
    {
        $leader = User::factory()->create();
        $user = User::factory()->create();
        $group = Group::factory()->create(['leader_id' => $leader->id]);

        $this->assertFalse($group->isLeader($user));
    }

    public function test_is_leader_returns_false_when_no_leader(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['leader_id' => null]);

        $this->assertFalse($group->isLeader($user));
    }

    public function test_active_scope_filters_active_groups(): void
    {
        $activeGroup = Group::factory()->create(['is_active' => true]);
        Group::factory()->create(['is_active' => false]);

        $activeGroups = Group::active()->get();

        $this->assertCount(1, $activeGroups);
        $this->assertEquals($activeGroup->id, $activeGroups->first()->id);
    }

    public function test_with_space_scope_filters_groups_with_available_spots(): void
    {
        $groupWithSpace = Group::factory()->create([
            'max_members' => 5,
        ]);

        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $groupWithSpace->users()->attach($user, ['joined_at' => now()]);
        }

        $fullGroup = Group::factory()->create([
            'max_members' => 2,
        ]);

        $moreUsers = User::factory()->count(2)->create();
        foreach ($moreUsers as $user) {
            $fullGroup->users()->attach($user, ['joined_at' => now()]);
        }

        $unlimitedGroup = Group::factory()->create([
            'max_members' => null,
        ]);

        $groupsWithSpace = Group::withSpace()->get();

        $this->assertCount(2, $groupsWithSpace); // groupWithSpace and unlimitedGroup
        $this->assertTrue($groupsWithSpace->contains($groupWithSpace));
        $this->assertTrue($groupsWithSpace->contains($unlimitedGroup));
        $this->assertFalse($groupsWithSpace->contains($fullGroup));
    }

    public function test_get_route_key_name_returns_code(): void
    {
        $group = new Group;

        $this->assertEquals('code', $group->getRouteKeyName());
    }

    public function test_group_can_be_found_by_code(): void
    {
        $group = Group::factory()->create(['code' => 'TEST-GROUP']);

        $foundGroup = Group::where($group->getRouteKeyName(), 'TEST-GROUP')->first();

        $this->assertNotNull($foundGroup);
        $this->assertEquals($group->id, $foundGroup->id);
    }

    public function test_users_relationship_includes_joined_at_timestamp(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $joinedAt = now()->subHour();

        $group->users()->attach($user, ['joined_at' => $joinedAt]);

        $groupUser = $group->users->first();

        $this->assertEquals($user->id, $groupUser->id);
        $this->assertEquals(
            $joinedAt->format('Y-m-d H:i:s'),
            $groupUser->pivot->joined_at->format('Y-m-d H:i:s')
        );
    }
}
