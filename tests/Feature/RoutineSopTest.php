<?php

use App\Models\Department;
use App\Models\Routine;
use App\Models\RoutineSop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Permission::create(['name' => 'manage departments']);
    Permission::create(['name' => 'view departments']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(['manage departments', 'view departments']);

    $this->department = Department::factory()->create([
        'head_of_department' => $this->admin->id,
    ]);
    $this->department->users()->attach($this->admin);

    $this->routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);
});

it('uploads a SOP file', function () {
    $file = UploadedFile::fake()->create('procedure.pdf', 1024, 'application/pdf');

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/sops", [
            'title' => 'Procédure standard',
            'description' => 'Guide de procédure',
            'file' => $file,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_sops', [
        'routine_id' => $this->routine->id,
        'title' => 'Procédure standard',
        'extension' => 'pdf',
        'uploaded_by' => $this->admin->id,
    ]);
});

it('rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('script.sh', 100, 'text/x-shellscript');

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/sops", [
            'title' => 'Script',
            'file' => $file,
        ]);

    $response->assertSessionHasErrors('file');
});

it('deletes a SOP', function () {
    $sop = RoutineSop::create([
        'routine_id' => $this->routine->id,
        'title' => 'Test SOP',
        'original_name' => 'test.pdf',
        'file_name' => 'test.pdf',
        'file_path' => 'routines/sops/test.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'extension' => 'pdf',
        'uploaded_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/sops/{$sop->uuid}");

    $response->assertRedirect();
    $this->assertSoftDeleted('routine_sops', ['id' => $sop->id]);
});

it('uploads SOP for a specific step', function () {
    $step = \App\Models\RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
    ]);

    $file = UploadedFile::fake()->create('video-guide.mp4', 5120, 'video/mp4');

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/sops", [
            'title' => 'Guide vidéo',
            'file' => $file,
            'routine_step_id' => $step->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_sops', [
        'routine_id' => $this->routine->id,
        'routine_step_id' => $step->id,
        'title' => 'Guide vidéo',
        'extension' => 'mp4',
    ]);
});
