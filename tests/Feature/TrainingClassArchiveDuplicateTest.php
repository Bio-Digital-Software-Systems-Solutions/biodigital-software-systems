<?php

use App\Models\Quiz;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingClassSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $teacher = Teacher::create([
        'user_id' => User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe'])->id,
        'specialization' => 'Computer Science',
        'bio' => 'Experienced teacher',
        'is_active' => true,
    ]);

    $this->training = Training::factory()->create([
        'title' => 'Laravel Development',
        'teacher_id' => $teacher->id,
    ]);
});

// ─── Archive Tests ───────────────────────────────────────────────

it('can archive an active class', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'date' => now()->addDays(5),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.archive', $class->uuid));

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);

    $class->refresh();
    expect($class->status)->toBe('archived')
        ->and($class->archived_at)->not->toBeNull()
        ->and($class->archive_access_until)->not->toBeNull();
});

it('uses 6 months as default archive access duration', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('training-classes.archive', $class->uuid));

    $class->refresh();

    $expectedUntil = now()->addMonths(6);
    expect($class->archive_access_until->format('Y-m-d'))
        ->toBe($expectedUntil->format('Y-m-d'));
});

it('cannot archive an already archived class', function () {
    $class = TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.archive', $class->uuid));

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

it('can unarchive an archived class', function () {
    $class = TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.unarchive', $class->uuid));

    $response->assertSuccessful();

    $class->refresh();
    expect($class->status)->toBe('active')
        ->and($class->archived_at)->toBeNull()
        ->and($class->archive_access_until)->toBeNull();
});

it('cannot unarchive a non-archived class', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.unarchive', $class->uuid));

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

it('cannot update an archived class', function () {
    $class = TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->putJson(route('training-classes.update', $class->uuid), [
            'training_id' => $this->training->id,
            'name' => 'Updated name',
            'date' => now()->addDay()->toDateString(),
        ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => 'Impossible de modifier une classe archivée.']);
});

it('cannot delete an archived class', function () {
    $class = TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->deleteJson(route('training-classes.destroy', $class->uuid));

    $response->assertStatus(422);
    expect($class->fresh())->not->toBeNull();
});

it('excludes archived classes from index by default', function () {
    TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'date' => now()->addDay(),
    ]);

    TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
        'date' => now()->addDay(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('training-classes.index'));

    $response->assertSuccessful();

    $classes = $response->viewData('page')['props']['classes']['data'];
    expect($classes)->toHaveCount(1);
});

it('shows archived classes when filtered by status=archived', function () {
    TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'date' => now()->addDay(),
    ]);

    TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
        'date' => now()->addDay(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('training-classes.index', ['status' => 'archived']));

    $response->assertSuccessful();

    $classes = $response->viewData('page')['props']['classes']['data'];
    expect($classes)->toHaveCount(1);
    expect($classes[0]['status'])->toBe('Archivée');
});

it('validates access_duration_months must be between 1 and 24', function (int $months, bool $shouldPass) {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.archive', $class->uuid), [
            'access_duration_months' => $months,
        ]);

    if ($shouldPass) {
        $response->assertSuccessful();
    } else {
        $response->assertUnprocessable();
    }
})->with([
    'valid: 1 month' => [1, true],
    'valid: 12 months' => [12, true],
    'valid: 24 months' => [24, true],
    'invalid: 0 months' => [0, false],
    'invalid: 25 months' => [25, false],
]);

// ─── Duplicate Tests ─────────────────────────────────────────────

it('can duplicate a class', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'name' => 'Classe Originale',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid));

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);
    expect(TrainingClass::count())->toBe(2);
});

it('appends (Copie) to the duplicated class name', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'name' => 'Classe Originale',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid));

    $response->assertSuccessful();
    $data = $response->json('class');
    expect($data['name'])->toBe('Classe Originale (Copie)');
});

it('uses custom name when provided during duplication', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'name' => 'Classe Originale',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid), [
            'name' => 'Ma Nouvelle Classe',
        ]);

    $response->assertSuccessful();
    $data = $response->json('class');
    expect($data['name'])->toBe('Ma Nouvelle Classe');
});

it('duplicates schedules', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    TrainingClassSchedule::factory()->create([
        'training_class_id' => $class->id,
        'day_of_week' => 'Lundi',
    ]);
    TrainingClassSchedule::factory()->create([
        'training_class_id' => $class->id,
        'day_of_week' => 'Mercredi',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid));

    $response->assertSuccessful();

    $copy = TrainingClass::where('id', '!=', $class->id)->first();
    expect($copy->schedules)->toHaveCount(2);
});

it('duplicates materials', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    TrainingClassMaterial::factory()->count(3)->create([
        'training_class_id' => $class->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid));

    $response->assertSuccessful();

    $copy = TrainingClass::where('id', '!=', $class->id)->first();
    expect($copy->materials)->toHaveCount(3);
});

it('duplicates quiz associations', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $quiz = Quiz::factory()->create(['training_id' => $this->training->id]);
    $class->allQuizzes()->attach($quiz->id, [
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid));

    $response->assertSuccessful();

    $copy = TrainingClass::where('id', '!=', $class->id)->first();
    expect($copy->allQuizzes)->toHaveCount(1);
});

it('does not duplicate student enrollments', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    // Enroll students in the training
    $students = User::factory()->count(3)->create();
    foreach ($students as $student) {
        $this->training->students()->attach($student->id, [
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);
    }

    $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid))
        ->assertSuccessful();

    // No new enrollments should be created
    $enrollmentCount = $this->training->students()->count();
    expect($enrollmentCount)->toBe(3);
});

it('creates a copy with a different uuid', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid))
        ->assertSuccessful();

    $copy = TrainingClass::where('id', '!=', $class->id)->first();
    expect($copy->uuid)->not->toBe($class->uuid);
});

it('creates an active class when duplicating an archived class', function () {
    $class = TrainingClass::factory()->archived()->create([
        'training_id' => $this->training->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('training-classes.duplicate', $class->uuid))
        ->assertSuccessful();

    $copy = TrainingClass::where('id', '!=', $class->id)->first();
    expect($copy->status)->toBe('active')
        ->and($copy->archived_at)->toBeNull()
        ->and($copy->archive_access_until)->toBeNull();
});

it('requires authentication for archive, unarchive, and duplicate', function () {
    $class = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);

    $this->postJson(route('training-classes.archive', $class->uuid))
        ->assertUnauthorized();

    $this->postJson(route('training-classes.unarchive', $class->uuid))
        ->assertUnauthorized();

    $this->postJson(route('training-classes.duplicate', $class->uuid))
        ->assertUnauthorized();
});
