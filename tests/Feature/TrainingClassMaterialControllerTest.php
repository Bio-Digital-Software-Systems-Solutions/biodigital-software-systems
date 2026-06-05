<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingClassMaterialControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $otherTeacher;

    protected User $student;

    protected Training $training;

    protected TrainingClass $trainingClass;

    protected TrainingClass $otherClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        Storage::fake('public');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->training = Training::factory()->create();

        $this->trainingClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->otherClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->otherTeacher->id,
        ]);

        $this->training->students()->attach($this->student->id, [
            'training_class_id' => $this->trainingClass->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);
    }

    protected function attachMaterial(
        TrainingClass $class,
        ?User $teacher = null,
        array $materialAttributes = [],
        array $pivotAttributes = []
    ): TrainingClassMaterial {
        $material = TrainingMaterial::factory()->create(array_merge([
            'training_id' => $class->training_id,
        ], $materialAttributes));

        return TrainingClassMaterial::create(array_merge([
            'training_class_id' => $class->id,
            'training_material_id' => $material->id,
            'teacher_id' => $teacher?->id ?? $class->teacher_id,
            'is_active' => true,
            'order' => 0,
        ], $pivotAttributes));
    }

    /** @test */
    public function teacher_can_list_their_class_materials(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->attachMaterial($this->trainingClass, $this->teacher);
        }

        $response = $this->actingAs($this->teacher)
            ->getJson(route('training-classes.materials.index', $this->trainingClass));

        $response->assertOk()
            ->assertJsonCount(3, 'materials')
            ->assertJsonStructure([
                'materials' => [
                    '*' => ['id', 'uuid', 'title', 'type', 'file_url', 'order', 'is_active'],
                ],
                'class' => ['id', 'name'],
            ]);
    }

    /** @test */
    public function teacher_can_upload_pdf_material(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Guide de référence',
                'type' => 'pdf',
                'file' => $file,
                'description' => 'Documentation complète',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('training_materials', [
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Guide de référence',
            'type' => 'pdf',
        ]);

        $material = TrainingMaterial::where('title', 'Guide de référence')->firstOrFail();
        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
            'training_material_id' => $material->id,
            'teacher_id' => $this->teacher->id,
        ]);

        Storage::disk('public')->assertExists($material->file_path);
    }

    /** @test */
    public function teacher_can_upload_video_material(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Introduction vidéo',
                'type' => 'video',
                'file' => $file,
                'duration' => '15 min',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_materials', [
            'training_id' => $this->training->id,
            'title' => 'Introduction vidéo',
            'type' => 'video',
            'duration' => '15 min',
        ]);
    }

    /** @test */
    public function teacher_can_create_material_with_external_url(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Cours en ligne',
                'type' => 'video',
                'url' => 'https://youtube.com/watch?v=example',
                'duration' => '30 min',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_materials', [
            'training_id' => $this->training->id,
            'title' => 'Cours en ligne',
            'url' => 'https://youtube.com/watch?v=example',
        ]);
    }

    /** @test */
    public function teacher_can_update_material_title_which_propagates_to_the_content(): void
    {
        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['title' => 'Old Title']
        );

        $response = $this->actingAs($this->teacher)
            ->putJson(route('training-classes.materials.update', [$this->trainingClass, $pivot]), [
                'title' => 'New Title',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_materials', [
            'id' => $pivot->training_material_id,
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function teacher_can_toggle_pivot_is_active_without_touching_content(): void
    {
        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['title' => 'Stable'],
            ['is_active' => true]
        );

        $this->actingAs($this->teacher)
            ->putJson(route('training-classes.materials.update', [$this->trainingClass, $pivot]), [
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('training_class_materials', [
            'id' => $pivot->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('training_materials', [
            'id' => $pivot->training_material_id,
            'title' => 'Stable',
        ]);
    }

    /** @test */
    public function teacher_can_delete_material_assignment_keeping_the_content(): void
    {
        $pivot = $this->attachMaterial($this->trainingClass, $this->teacher);
        $materialId = $pivot->training_material_id;

        $response = $this->actingAs($this->teacher)
            ->deleteJson(route('training-classes.materials.destroy', [$this->trainingClass, $pivot]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('training_class_materials', ['id' => $pivot->id]);
        $this->assertDatabaseHas('training_materials', ['id' => $materialId]);
    }

    /** @test */
    public function teacher_cannot_add_material_to_other_teacher_class(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->otherClass), [
                'title' => 'Test Material',
                'type' => 'pdf',
                'url' => 'https://example.com/doc.pdf',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function teacher_cannot_update_other_teacher_material(): void
    {
        $pivot = $this->attachMaterial($this->otherClass, $this->otherTeacher);

        $response = $this->actingAs($this->teacher)
            ->putJson(route('training-classes.materials.update', [$this->otherClass, $pivot]), [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function student_can_view_only_active_materials_of_their_class(): void
    {
        $this->attachMaterial($this->trainingClass, $this->teacher, [], ['is_active' => true]);
        $this->attachMaterial($this->trainingClass, $this->teacher, [], ['is_active' => true]);
        $this->attachMaterial($this->trainingClass, $this->teacher, [], ['is_active' => false]);

        $response = $this->actingAs($this->student)
            ->getJson(route('student.training-classes.materials.index', $this->trainingClass));

        $response->assertOk()->assertJsonCount(2, 'materials');
    }

    /** @test */
    public function student_cannot_view_other_class_materials(): void
    {
        $this->attachMaterial($this->otherClass, $this->otherTeacher);
        $this->attachMaterial($this->otherClass, $this->otherTeacher);

        $response = $this->actingAs($this->student)
            ->getJson(route('student.training-classes.materials.index', $this->otherClass));

        $response->assertForbidden();
    }

    /** @test */
    public function student_cannot_create_material(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Student Material',
                'type' => 'pdf',
                'url' => 'https://example.com/doc.pdf',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function student_cannot_update_material(): void
    {
        $pivot = $this->attachMaterial($this->trainingClass, $this->teacher);

        $response = $this->actingAs($this->student)
            ->putJson(route('training-classes.materials.update', [$this->trainingClass, $pivot]), [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function student_cannot_delete_material(): void
    {
        $pivot = $this->attachMaterial($this->trainingClass, $this->teacher);

        $response = $this->actingAs($this->student)
            ->deleteJson(route('training-classes.materials.destroy', [$this->trainingClass, $pivot]));

        $response->assertForbidden();
    }

    /** @test */
    public function materials_are_ordered_by_pivot_order(): void
    {
        $this->attachMaterial($this->trainingClass, $this->teacher, ['title' => 'Third'], ['order' => 3]);
        $this->attachMaterial($this->trainingClass, $this->teacher, ['title' => 'First'], ['order' => 1]);
        $this->attachMaterial($this->trainingClass, $this->teacher, ['title' => 'Second'], ['order' => 2]);

        $response = $this->actingAs($this->teacher)
            ->getJson(route('training-classes.materials.index', $this->trainingClass));

        $titles = array_column($response->json('materials'), 'title');
        $this->assertSame(['First', 'Second', 'Third'], $titles);
    }

    /** @test */
    public function creating_material_requires_either_file_or_url(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Test Material',
                'type' => 'pdf',
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function file_upload_validates_file_types(): void
    {
        $invalidFile = UploadedFile::fake()->create('malware.exe', 1000);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Test Material',
                'type' => 'pdf',
                'file' => $invalidFile,
            ]);

        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function teacher_can_download_material(): void
    {
        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['url' => 'https://example.com/doc.pdf']
        );

        $response = $this->actingAs($this->teacher)
            ->get(route('training-class-materials.download', $pivot));

        $response->assertRedirect('https://example.com/doc.pdf');
    }

    /** @test */
    public function student_can_download_active_material(): void
    {
        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['url' => 'https://example.com/doc.pdf'],
            ['is_active' => true]
        );

        $response = $this->actingAs($this->student)
            ->get(route('training-class-materials.download', $pivot));

        $response->assertRedirect('https://example.com/doc.pdf');
    }

    /** @test */
    public function student_cannot_download_inactive_material(): void
    {
        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['url' => 'https://example.com/doc.pdf'],
            ['is_active' => false]
        );

        // The app's global exception handler converts 403 to a back()-redirect
        // for non-JSON requests, so we have to ask for JSON to surface the
        // real 403 here.
        $response = $this->actingAs($this->student)
            ->getJson(route('training-class-materials.download', $pivot));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_add_material_to_any_class(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($admin)
            ->post(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Admin Added Material',
                'type' => 'pdf',
                'file' => $file,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_materials', [
            'training_id' => $this->training->id,
            'title' => 'Admin Added Material',
            'type' => 'pdf',
        ]);
    }

    /** @test */
    public function super_admin_can_add_material_to_any_class(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)
            ->post(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'SuperAdmin Added Material',
                'type' => 'video',
                'url' => 'https://example.com/video.mp4',
                'duration' => '15 min',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_materials', [
            'training_id' => $this->training->id,
            'title' => 'SuperAdmin Added Material',
            'type' => 'video',
        ]);
    }

    /** @test */
    public function admin_can_update_any_material(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['title' => 'Original Title']
        );

        $response = $this->actingAs($admin)
            ->put(route('training-classes.materials.update', [$this->trainingClass, $pivot]), [
                'title' => 'Admin Updated Title',
                'type' => $pivot->material->type,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_materials', [
            'id' => $pivot->training_material_id,
            'title' => 'Admin Updated Title',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_material(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pivot = $this->attachMaterial($this->trainingClass, $this->teacher);

        $response = $this->actingAs($admin)
            ->delete(route('training-classes.materials.destroy', [$this->trainingClass, $pivot]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('training_class_materials', ['id' => $pivot->id]);
    }

    /** @test */
    public function admin_can_view_all_materials(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        for ($i = 0; $i < 3; $i++) {
            $this->attachMaterial($this->trainingClass, $this->teacher);
        }

        $response = $this->actingAs($admin)
            ->getJson(route('training-classes.materials.index', $this->trainingClass));

        $response->assertOk()->assertJsonCount(3, 'materials');
    }

    /** @test */
    public function admin_can_download_inactive_material(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pivot = $this->attachMaterial(
            $this->trainingClass,
            $this->teacher,
            ['url' => 'https://example.com/doc.pdf'],
            ['is_active' => false]
        );

        $response = $this->actingAs($admin)
            ->get(route('training-class-materials.download', $pivot));

        // External URL → 302 redirect, gated by `view` policy (admin OK).
        $response->assertRedirect('https://example.com/doc.pdf');
    }
}
