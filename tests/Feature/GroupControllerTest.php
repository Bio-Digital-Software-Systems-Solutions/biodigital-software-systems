<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->leader = User::factory()->create();
    }

    public function test_index_displays_groups(): void
    {
        $groups = Group::factory()->count(3)->create();

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

    public function test_show_displays_group(): void
    {
        $group = Group::factory()->create();

        $this->user->givePermissionTo('view groups');

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $group));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Groups/Show')
            ->where('group.id', $group->id)
            ->where('group.name', $group->name)
        );
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
        $group = Group::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('groups.index'));

        $response->assertForbidden();
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
