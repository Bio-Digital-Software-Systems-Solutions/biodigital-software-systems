<?php

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use App\Models\VisitorVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->group = Group::factory()->create(['leader_id' => $this->admin->id]);
});

it('lists visitors for a group', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor->id]);

    $response = $this->actingAs($this->admin)->getJson("/groups/{$this->group->uuid}/visitors");

    $response->assertOk();
    $response->assertJsonCount(1, 'visitors');
    $response->assertJsonPath('visitors.0.visitor.first_name', $visitor->first_name);
});

it('creates a new visitor and attaches to group', function (): void {
    $response = $this->actingAs($this->admin)->postJson("/groups/{$this->group->uuid}/visitors", [
        'first_name' => 'Paul',
        'last_name' => 'Martin',
        'email' => 'paul@example.com',
        'first_visited_at' => '2026-03-15',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('visitors', ['first_name' => 'Paul', 'last_name' => 'Martin']);
    $this->assertDatabaseHas('visitor_visits', [
        'visitable_type' => Group::class,
        'visitable_id' => $this->group->id,
    ]);
});

it('attaches an existing visitor to group', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->postJson("/groups/{$this->group->uuid}/visitors", [
        'visitor_id' => $visitor->id,
        'first_visited_at' => '2026-03-15',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('visitor_visits', [
        'visitor_id' => $visitor->id,
        'visitable_type' => Group::class,
        'visitable_id' => $this->group->id,
    ]);
});

it('prevents duplicate visitor attachment', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor->id]);

    $response = $this->actingAs($this->admin)->postJson("/groups/{$this->group->uuid}/visitors", [
        'visitor_id' => $visitor->id,
        'first_visited_at' => '2026-03-15',
    ]);

    $response->assertStatus(422);
});

it('records attendance for a visitor', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor->id]);
    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->postJson(
        "/groups/{$this->group->uuid}/visitors/{$visitor->uuid}/attendance",
        [
            'attendable_type' => GroupActivity::class,
            'attendable_id' => $activity->id,
            'attended_at' => '2026-03-20',
            'status' => 'present',
        ]
    );

    $response->assertOk();
    $this->assertDatabaseHas('visitor_attendances', [
        'visitor_id' => $visitor->id,
        'status' => 'present',
    ]);
});

it('records bulk attendance for multiple visitors', function (): void {
    $visitor1 = Visitor::factory()->create(['created_by' => $this->admin->id]);
    $visitor2 = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor1->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor2->id]);
    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->postJson(
        "/groups/{$this->group->uuid}/visitors/attendance",
        [
            'attendable_type' => GroupActivity::class,
            'attendable_id' => $activity->id,
            'attended_at' => '2026-03-20',
            'attendances' => [
                ['visitor_id' => $visitor1->id, 'status' => 'present'],
                ['visitor_id' => $visitor2->id, 'status' => 'absent'],
            ],
        ]
    );

    $response->assertOk();
    expect(VisitorAttendance::count())->toBe(2);
    $this->assertDatabaseHas('visitor_attendances', ['visitor_id' => $visitor1->id, 'status' => 'present']);
    $this->assertDatabaseHas('visitor_attendances', ['visitor_id' => $visitor2->id, 'status' => 'absent']);
});

it('removes a visitor from a group', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor->id]);

    $response = $this->actingAs($this->admin)->deleteJson("/groups/{$this->group->uuid}/visitors/{$visitor->uuid}");

    $response->assertOk();
    $this->assertDatabaseMissing('visitor_visits', ['id' => $visit->id]);
});

it('returns integration dashboard stats', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create([
        'visitor_id' => $visitor->id,
        'integration_score' => 45.5,
        'integration_status' => 'progressing',
    ]);

    $response = $this->actingAs($this->admin)->getJson("/groups/{$this->group->uuid}/visitors/dashboard");

    $response->assertOk();
    $response->assertJsonPath('stats.total_visitors', 1);
    $response->assertJsonPath('stats.progressing', 1);
});

it('counts visitors correctly for a group', function (): void {
    $visitor1 = Visitor::factory()->create(['created_by' => $this->admin->id]);
    $visitor2 = Visitor::factory()->create(['created_by' => $this->admin->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor1->id]);
    VisitorVisit::factory()->forGroup($this->group)->create(['visitor_id' => $visitor2->id]);

    expect($this->group->visitorVisits()->count())->toBe(2);
});
