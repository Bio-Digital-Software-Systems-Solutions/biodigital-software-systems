<?php

namespace Tests\Feature;

use App\Enums\Form\FormStatus;
use App\Enums\Form\SubmissionStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentFormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormSubmissionProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $regularUser;
    private User $adminUser;
    private User $userWithPermission;
    private User $projectManager;
    private Department $department;
    private DepartmentForm $form;
    private DepartmentFormSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => 'process form submissions', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage forms', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view forms', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'submit forms', 'guard_name' => 'web']);

        // Create roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $projectManagerRole = Role::firstOrCreate(['name' => 'project-manager', 'guard_name' => 'web']);
        $projectManagerRole->syncPermissions(['process form submissions', 'view forms', 'manage forms']);

        // Create users
        $this->regularUser = User::factory()->create();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->userWithPermission = User::factory()->create();
        $this->userWithPermission->givePermissionTo('process form submissions');

        $this->projectManager = User::factory()->create();
        $this->projectManager->assignRole('project-manager');

        // Create department and form
        $this->department = Department::factory()->create();

        $this->form = DepartmentForm::factory()->create([
            'department_id' => $this->department->id,
            'status' => FormStatus::PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        // Create a submission
        $this->submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->regularUser->id,
            'status' => SubmissionStatus::SUBMITTED,
            'data' => ['name' => 'Test User', 'email' => 'test@example.com'],
        ]);
    }

    /** @test */
    public function admin_can_process_submission(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => 'Traitement effectué par admin',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::COMPLETED, $this->submission->status);
        $this->assertEquals('Traitement effectué par admin', $this->submission->notes);
        $this->assertEquals($this->adminUser->id, $this->submission->processed_by);
        $this->assertNotNull($this->submission->processed_at);
    }

    /** @test */
    public function user_with_permission_can_process_submission(): void
    {
        $response = $this->actingAs($this->userWithPermission)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'processing',
                'notes' => 'En cours de traitement',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::PROCESSING, $this->submission->status);
        $this->assertEquals('En cours de traitement', $this->submission->notes);
    }

    /** @test */
    public function project_manager_can_process_submission(): void
    {
        $response = $this->actingAs($this->projectManager)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => 'Validé par le chef de projet',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::COMPLETED, $this->submission->status);
    }

    /** @test */
    public function regular_user_cannot_process_submission(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => 'Tentative non autorisée',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Vous n\'avez pas la permission de traiter cette soumission.');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::SUBMITTED, $this->submission->status);
        $this->assertNull($this->submission->notes);
    }

    /** @test */
    public function unauthenticated_user_cannot_process_submission(): void
    {
        $response = $this->post(route('form-submissions.update-status', $this->submission->uuid), [
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function can_reject_submission_with_notes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'rejected',
                'notes' => 'Informations incomplètes',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::REJECTED, $this->submission->status);
        $this->assertEquals('Informations incomplètes', $this->submission->notes);
        $this->assertNotNull($this->submission->processed_at);
    }

    /** @test */
    public function processing_submission_does_not_set_processed_at(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'processing',
            ]);

        $response->assertRedirect();

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::PROCESSING, $this->submission->status);
        $this->assertNull($this->submission->processed_at);
    }

    /** @test */
    public function completing_submission_sets_processed_at(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
            ]);

        $response->assertRedirect();

        $this->submission->refresh();
        $this->assertNotNull($this->submission->processed_at);
    }

    /** @test */
    public function status_validation_rejects_invalid_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors('status');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::SUBMITTED, $this->submission->status);
    }

    /** @test */
    public function notes_are_optional(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::COMPLETED, $this->submission->status);
    }

    /** @test */
    public function notes_have_max_length_validation(): void
    {
        $longNotes = str_repeat('a', 5001);

        $response = $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => $longNotes,
            ]);

        $response->assertSessionHasErrors('notes');
    }

    /** @test */
    public function show_page_displays_processing_section_for_authorized_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('form-submissions.show', $this->submission->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('FormSubmissions/Show')
            ->has('canProcess')
            ->where('canProcess', true)
            ->has('statuses')
        );
    }

    /** @test */
    public function show_page_hides_processing_section_for_regular_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('form-submissions.show', $this->submission->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('FormSubmissions/Show')
            ->where('canProcess', false)
        );
    }

    /** @test */
    public function processor_info_is_recorded_when_processing(): void
    {
        $this->actingAs($this->userWithPermission)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => 'Test notes',
            ]);

        $this->submission->refresh();
        $this->assertEquals($this->userWithPermission->id, $this->submission->processed_by);

        // Load processor relationship
        $this->submission->load('processor');
        $this->assertNotNull($this->submission->processor);
        $this->assertEquals($this->userWithPermission->id, $this->submission->processor->id);
    }

    /** @test */
    public function can_change_status_multiple_times(): void
    {
        // First change: to processing
        $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'processing',
                'notes' => 'En cours',
            ]);

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::PROCESSING, $this->submission->status);

        // Second change: to completed
        $this->actingAs($this->adminUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
                'notes' => 'Terminé',
            ]);

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::COMPLETED, $this->submission->status);
        $this->assertEquals('Terminé', $this->submission->notes);
    }

    /** @test */
    public function super_admin_can_process_submission(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::COMPLETED, $this->submission->status);
    }

    /** @test */
    public function submission_owner_without_permission_cannot_process_own_submission(): void
    {
        // The regular user created the submission but doesn't have process permission
        $response = $this->actingAs($this->regularUser)
            ->post(route('form-submissions.update-status', $this->submission->uuid), [
                'status' => 'completed',
            ]);

        $response->assertSessionHas('error');

        $this->submission->refresh();
        $this->assertEquals(SubmissionStatus::SUBMITTED, $this->submission->status);
    }
}
