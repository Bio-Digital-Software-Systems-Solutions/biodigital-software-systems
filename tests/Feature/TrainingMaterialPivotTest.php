<?php

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');

    $this->training = Training::factory()->create(['teacher_id' => $this->teacher->id]);

    $this->classA = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'teacher_id' => $this->teacher->id,
        'name' => 'Class A',
    ]);

    $this->classB = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'teacher_id' => $this->teacher->id,
        'name' => 'Class B',
    ]);

    $this->material = TrainingMaterial::factory()->create([
        'training_id' => $this->training->id,
        'teacher_id' => $this->teacher->id,
        'title' => 'Shared Slides',
        'type' => 'pdf',
    ]);
});

it('lets the same TrainingMaterial be active in one class and inactive in another', function (): void {
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
        'is_active' => true,
    ]);
    TrainingClassMaterial::create([
        'training_class_id' => $this->classB->id,
        'training_material_id' => $this->material->id,
        'is_active' => false,
    ]);

    expect($this->classA->fresh()->activeMaterials()->pluck('training_materials.id')->all())
        ->toContain($this->material->id);
    expect($this->classB->fresh()->activeMaterials()->pluck('training_materials.id')->all())
        ->not->toContain($this->material->id);
    expect($this->classB->fresh()->materials()->pluck('training_materials.id')->all())
        ->toContain($this->material->id);
});

it('exposes the same TrainingMaterial to multiple classes via classes() relation', function (): void {
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
    ]);
    TrainingClassMaterial::create([
        'training_class_id' => $this->classB->id,
        'training_material_id' => $this->material->id,
    ]);

    expect($this->material->classes()->pluck('training_classes.id')->all())
        ->toEqualCanonicalizing([$this->classA->id, $this->classB->id]);
});

it('prevents attaching the same material to a class twice', function (): void {
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
    ]);

    expect(fn () => TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('orders the materials of a class by pivot order', function (): void {
    $second = TrainingMaterial::factory()->create([
        'training_id' => $this->training->id,
        'title' => 'Second',
    ]);
    $third = TrainingMaterial::factory()->create([
        'training_id' => $this->training->id,
        'title' => 'Third',
    ]);

    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $third->id,
        'order' => 3,
    ]);
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
        'order' => 1,
    ]);
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $second->id,
        'order' => 2,
    ]);

    $titles = $this->classA->fresh()->materials()->get()->pluck('title')->all();

    expect($titles)->toBe(['Shared Slides', 'Second', 'Third']);
});

it('toggleActive endpoint flips only the targeted class pivot', function (): void {
    $pivotA = TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
        'teacher_id' => $this->teacher->id,
        'is_active' => true,
    ]);
    $pivotB = TrainingClassMaterial::create([
        'training_class_id' => $this->classB->id,
        'training_material_id' => $this->material->id,
        'teacher_id' => $this->teacher->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->teacher)
        ->patch(route('training-classes.materials.toggle-active', [$this->classA, $pivotA]))
        ->assertRedirect();

    expect($pivotA->fresh()->is_active)->toBeFalse();
    expect($pivotB->fresh()->is_active)->toBeTrue();
});

it('attach endpoint links an existing material to a class without duplicating content', function (): void {
    $countBefore = TrainingMaterial::count();

    $this->actingAs($this->teacher)
        ->post(route('training-classes.materials.attach', $this->classA), [
            'training_material_id' => $this->material->id,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(TrainingMaterial::count())->toBe($countBefore);
    $this->assertDatabaseHas('training_class_materials', [
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
        'is_active' => 1,
    ]);
});

it('attach endpoint is idempotent', function (): void {
    $this->actingAs($this->teacher)
        ->post(route('training-classes.materials.attach', $this->classA), [
            'training_material_id' => $this->material->id,
        ])->assertRedirect();

    $this->actingAs($this->teacher)
        ->post(route('training-classes.materials.attach', $this->classA), [
            'training_material_id' => $this->material->id,
        ])->assertRedirect();

    expect(TrainingClassMaterial::where('training_class_id', $this->classA->id)
        ->where('training_material_id', $this->material->id)
        ->count())->toBe(1);
});

it('attach rejects a material that belongs to another training', function (): void {
    $otherTraining = Training::factory()->create();
    $foreignMaterial = TrainingMaterial::factory()->create([
        'training_id' => $otherTraining->id,
    ]);

    $this->actingAs($this->teacher)
        ->post(route('training-classes.materials.attach', $this->classA), [
            'training_material_id' => $foreignMaterial->id,
        ])
        ->assertStatus(422);

    $this->assertDatabaseMissing('training_class_materials', [
        'training_class_id' => $this->classA->id,
        'training_material_id' => $foreignMaterial->id,
    ]);
});

it('deleting a class detaches its pivot rows but keeps the materials', function (): void {
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
    ]);

    $this->classA->delete();

    $this->assertDatabaseMissing('training_class_materials', [
        'training_class_id' => $this->classA->id,
    ]);
    $this->assertDatabaseHas('training_materials', [
        'id' => $this->material->id,
    ]);
});

it('deleting a TrainingMaterial cascades to its pivot rows', function (): void {
    TrainingClassMaterial::create([
        'training_class_id' => $this->classA->id,
        'training_material_id' => $this->material->id,
    ]);
    TrainingClassMaterial::create([
        'training_class_id' => $this->classB->id,
        'training_material_id' => $this->material->id,
    ]);

    $this->material->delete();

    $this->assertDatabaseMissing('training_class_materials', [
        'training_material_id' => $this->material->id,
    ]);
});
