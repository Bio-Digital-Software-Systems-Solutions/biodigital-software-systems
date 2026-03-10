<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
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

        Storage::fake('public');

        // Create teacher
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        // Create other teacher
        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole('teacher');

        // Create student
        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        // Create training
        $this->training = Training::factory()->create();

        // Create class for teacher
        $this->trainingClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
        ]);

        // Create class for other teacher
        $this->otherClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->otherTeacher->id,
        ]);

        // Enroll student in the class
        $this->training->students()->attach($this->student->id, [
            'training_class_id' => $this->trainingClass->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);
    }

    /** @test */
    public function teacher_can_list_their_class_materials(): void
    {
        // Create materials for this class
        TrainingClassMaterial::factory()->count(3)->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson(route('training-classes.materials.index', $this->trainingClass));

        $response->assertOk()
            ->assertJsonCount(3, 'materials')
            ->assertJsonStructure([
                'materials' => [
                    '*' => ['id', 'uuid', 'title', 'type', 'file_url', 'order'],
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

        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Guide de référence',
            'type' => 'pdf',
        ]);

        Storage::disk('public')->assertExists('class-materials/'.$this->trainingClass->id.'/'.$file->hashName());
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

        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
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

        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
            'title' => 'Cours en ligne',
            'url' => 'https://youtube.com/watch?v=example',
        ]);
    }

    /** @test */
    public function teacher_can_update_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($this->teacher)
            ->putJson(route('training-classes.materials.update', [$this->trainingClass, $material]), [
                'title' => 'New Title',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_class_materials', [
            'id' => $material->id,
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function teacher_can_delete_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->teacher)
            ->deleteJson(route('training-classes.materials.destroy', [$this->trainingClass, $material]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('training_class_materials', [
            'id' => $material->id,
        ]);
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
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->otherClass->id,
            'teacher_id' => $this->otherTeacher->id,
        ]);

        $response = $this->actingAs($this->teacher)
            ->putJson(route('training-classes.materials.update', [$this->otherClass, $material]), [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function student_can_view_their_class_materials(): void
    {
        TrainingClassMaterial::factory()->count(2)->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        // Create inactive material (should not be visible to student)
        TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson(route('student.training-classes.materials.index', $this->trainingClass));

        $response->assertOk()
            ->assertJsonCount(2, 'materials'); // Only active materials
    }

    /** @test */
    public function student_cannot_view_other_class_materials(): void
    {
        TrainingClassMaterial::factory()->count(2)->create([
            'training_class_id' => $this->otherClass->id,
            'teacher_id' => $this->otherTeacher->id,
        ]);

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
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->student)
            ->putJson(route('training-classes.materials.update', [$this->trainingClass, $material]), [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function student_cannot_delete_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->student)
            ->deleteJson(route('training-classes.materials.destroy', [$this->trainingClass, $material]));

        $response->assertForbidden();
    }

    /** @test */
    public function materials_are_ordered_correctly(): void
    {
        TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Third',
            'order' => 3,
        ]);

        TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'First',
            'order' => 1,
        ]);

        TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Second',
            'order' => 2,
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson(route('training-classes.materials.index', $this->trainingClass));

        $materials = $response->json('materials');

        $this->assertEquals('First', $materials[0]['title']);
        $this->assertEquals('Second', $materials[1]['title']);
        $this->assertEquals('Third', $materials[2]['title']);
    }

    /** @test */
    public function creating_material_requires_either_file_or_url(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Test Material',
                'type' => 'pdf',
                // No file or URL
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

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function teacher_can_download_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'url' => 'https://example.com/doc.pdf',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('training-class-materials.download', $material));

        $response->assertRedirect('https://example.com/doc.pdf');
    }

    /** @test */
    public function student_can_download_active_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'url' => 'https://example.com/doc.pdf',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('training-class-materials.download', $material));

        $response->assertRedirect('https://example.com/doc.pdf');
    }

    /** @test */
    public function student_cannot_download_inactive_material(): void
    {
        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'url' => 'https://example.com/doc.pdf',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('training-class-materials.download', $material));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_add_material_to_any_class(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($admin)
            ->post(route('training-classes.materials.store', $this->trainingClass), [
                'title' => 'Admin Added Material',
                'type' => 'pdf',
                'file' => $file,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
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
        $this->assertDatabaseHas('training_class_materials', [
            'training_class_id' => $this->trainingClass->id,
            'title' => 'SuperAdmin Added Material',
            'type' => 'video',
        ]);
    }

    /** @test */
    public function admin_can_update_any_material(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('training-classes.materials.update', [$this->trainingClass, $material]), [
                'title' => 'Admin Updated Title',
                'type' => $material->type,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_class_materials', [
            'id' => $material->id,
            'title' => 'Admin Updated Title',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_material(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $material = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('training-classes.materials.destroy', [$this->trainingClass, $material]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('training_class_materials', [
            'id' => $material->id,
        ]);
    }

    /** @test */
    public function admin_can_view_all_materials(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        TrainingClassMaterial::factory()->count(3)->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('training-classes.materials.index', $this->trainingClass));

        $response->assertOk();
        $response->assertJsonCount(3, 'materials');
    }

    /** @test */
    public function admin_can_view_inactive_materials(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $inactiveMaterial = TrainingClassMaterial::factory()->create([
            'training_class_id' => $this->trainingClass->id,
            'teacher_id' => $this->teacher->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('training-class-materials.download', $inactiveMaterial));

        // Admin can access inactive materials
        $response->assertSuccessful();
    }
}
