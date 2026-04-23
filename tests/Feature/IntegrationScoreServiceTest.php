<?php

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\IntegrationPathwayStep;
use App\Models\IntegrationPathwayTemplate;
use App\Models\IntegrationSuggestion;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use App\Models\VisitorIntegrationProgress;
use App\Models\VisitorVisit;
use App\Services\IntegrationScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(IntegrationScoreService::class);
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['leader_id' => $this->user->id]);
    $this->visitor = Visitor::factory()->create(['created_by' => $this->user->id]);
    $this->visit = VisitorVisit::factory()->forGroup($this->group)->create([
        'visitor_id' => $this->visitor->id,
    ]);
});

it('returns zero score when no template exists', function (): void {
    $score = $this->service->calculateScore($this->visit);

    expect($score)->toBe(0.0);
});

it('returns zero score when template has no steps', function (): void {
    IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    $score = $this->service->calculateScore($this->visit);

    expect($score)->toBe(0.0);
});

it('calculates attendance count step progress', function (): void {
    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    IntegrationPathwayStep::factory()->attendanceCount(4, 8)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 1,
    ]);

    // Create 2 out of 4 required attendances
    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->user->id]);
    VisitorAttendance::factory()->present()->count(2)->create([
        'visitor_id' => $this->visitor->id,
        'visitor_visit_id' => $this->visit->id,
        'attendable_type' => GroupActivity::class,
        'attendable_id' => $activity->id,
        'attended_at' => now()->subDays(5),
    ]);

    $score = $this->service->calculateScore($this->visit);

    expect($score)->toBe(50.0);
    expect($this->visit->fresh()->integration_status)->toBe('progressing');
});

it('calculates weighted average correctly', function (): void {
    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    // Step 1: attendance_count, weight 3, will score 100% (4/4)
    $step1 = IntegrationPathwayStep::factory()->attendanceCount(4, 8)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 3,
    ]);

    // Step 2: manual_approval, weight 1, will score 0%
    IntegrationPathwayStep::factory()->manualApproval()->create([
        'template_id' => $template->id,
        'order_index' => 1,
        'weight' => 1,
    ]);

    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->user->id]);
    VisitorAttendance::factory()->present()->count(4)->create([
        'visitor_id' => $this->visitor->id,
        'visitor_visit_id' => $this->visit->id,
        'attendable_type' => GroupActivity::class,
        'attendable_id' => $activity->id,
        'attended_at' => now()->subDays(3),
    ]);

    $score = $this->service->calculateScore($this->visit);

    // (100 * 3 + 0 * 1) / (3 + 1) = 75
    expect($score)->toBe(75.0);
});

it('creates suggestion when score reaches 80%', function (): void {
    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    IntegrationPathwayStep::factory()->attendanceCount(4, 12)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 1,
    ]);

    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->user->id]);
    VisitorAttendance::factory()->present()->count(4)->create([
        'visitor_id' => $this->visitor->id,
        'visitor_visit_id' => $this->visit->id,
        'attendable_type' => GroupActivity::class,
        'attendable_id' => $activity->id,
        'attended_at' => now()->subDays(2),
    ]);

    $this->service->calculateScore($this->visit);

    expect(IntegrationSuggestion::where('visitor_visit_id', $this->visit->id)->exists())->toBeTrue();
    expect($this->visit->fresh()->integration_status)->toBe('ready');
});

it('does not create duplicate suggestions', function (): void {
    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    IntegrationPathwayStep::factory()->attendanceCount(2, 12)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 1,
    ]);

    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->user->id]);
    VisitorAttendance::factory()->present()->count(4)->create([
        'visitor_id' => $this->visitor->id,
        'visitor_visit_id' => $this->visit->id,
        'attendable_type' => GroupActivity::class,
        'attendable_id' => $activity->id,
        'attended_at' => now()->subDays(2),
    ]);

    // Calculate twice
    $this->service->calculateScore($this->visit);
    $this->service->calculateScore($this->visit->fresh());

    expect(IntegrationSuggestion::where('visitor_visit_id', $this->visit->id)->count())->toBe(1);
});

it('updates step progress records', function (): void {
    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    $step = IntegrationPathwayStep::factory()->attendanceCount(4, 8)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 1,
    ]);

    $activity = GroupActivity::factory()->create(['group_id' => $this->group->id, 'created_by' => $this->user->id]);
    VisitorAttendance::factory()->present()->count(2)->create([
        'visitor_id' => $this->visitor->id,
        'visitor_visit_id' => $this->visit->id,
        'attendable_type' => GroupActivity::class,
        'attendable_id' => $activity->id,
        'attended_at' => now()->subDays(3),
    ]);

    $this->service->calculateScore($this->visit);

    $progress = VisitorIntegrationProgress::where('visitor_visit_id', $this->visit->id)
        ->where('step_id', $step->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect((float) $progress->progress_value)->toBe(50.0);
    expect($progress->status)->toBe('in_progress');
});

it('does not recalculate for integrated visitors', function (): void {
    $this->visit->update(['integration_status' => 'integrated', 'integration_score' => 95]);

    $template = IntegrationPathwayTemplate::factory()->forGroups()->default()->create([
        'created_by' => $this->user->id,
    ]);

    IntegrationPathwayStep::factory()->attendanceCount(1, 12)->create([
        'template_id' => $template->id,
        'order_index' => 0,
        'weight' => 1,
    ]);

    $score = $this->service->calculateScore($this->visit);

    // Status should stay 'integrated'
    expect($this->visit->fresh()->integration_status)->toBe('integrated');
});
