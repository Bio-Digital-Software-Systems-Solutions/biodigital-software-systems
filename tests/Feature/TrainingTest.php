<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        // Create a test user
        $this->user = User::factory()->create();
    }

    public function test_can_list_trainings(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'category' => 'Test Category',
            'is_active' => true,
        ]);

        TrainingTopic::create([
            'training_id' => $training->id,
            'name' => 'Topic 1',
            'description' => 'Topic Description',
            'order' => 1,
        ]);

        $response = $this->get('/api/trainings');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Test Training']);
    }

    public function test_can_filter_trainings_by_level(): void
    {
        Training::create([
            'title' => 'Beginner Training',
            'description' => 'For beginners',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 50.00,
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Advanced Training',
            'description' => 'For advanced',
            'duration' => '4 months',
            'level' => 'advanced',
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->get('/api/trainings?level=beginner');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Beginner Training']);
    }

    public function test_can_search_trainings(): void
    {
        $training = Training::create([
            'title' => 'Web Development',
            'description' => 'Learn web development',
            'duration' => '6 months',
            'level' => 'intermediate',
            'price' => 200.00,
            'is_active' => true,
        ]);

        TrainingTopic::create([
            'training_id' => $training->id,
            'name' => 'React.js',
            'description' => 'Learn React',
            'order' => 1,
        ]);

        $response = $this->get('/api/trainings?search=React');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Web Development']);
    }

    public function test_authenticated_user_can_enroll_with_complete_data(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'room' => 'Salle 1',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'motivation' => str_repeat('Je suis très motivé pour suivre cette formation car elle correspond parfaitement à mes objectifs professionnels. ', 2),
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_enrollments', [
            'user_id' => $this->user->id,
            'training_id' => $training->id,
            'training_class_id' => $class->id,
            'status' => 'pending',
            'payment_method' => 'card',
        ]);

        $training->refresh();
        $this->assertEquals(1, $training->students_count);
    }

    public function test_enrollment_requires_class_selection(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $enrollmentData = [
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['selectedClassId']);
    }

    public function test_enrollment_requires_valid_class(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $enrollmentData = [
            'selectedClassId' => 99999, // Non-existent class
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['selectedClassId']);
    }

    public function test_enrollment_requires_motivation_minimum_length(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'motivation' => 'Too short', // Less than 50 characters
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['motivation']);
    }

    public function test_enrollment_requires_terms_acceptance(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'card',
            'hasReadTerms' => false, // Not accepted
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['hasReadTerms']);
    }

    public function test_enrollment_requires_privacy_policy_acceptance(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => false, // Not accepted
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['hasReadPrivacyPolicy']);
    }

    public function test_enrollment_requires_valid_payment_method(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'invalid-method',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertSessionHasErrors(['paymentMethod']);
    }

    public function test_cannot_enroll_twice(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $class = TrainingClass::create([
            'training_id' => $training->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $enrollmentData = [
            'selectedClassId' => $class->id,
            'motivation' => str_repeat('Je suis très motivé. ', 10),
            'paymentMethod' => 'card',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ];

        // First enrollment
        $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        // Second enrollment attempt
        $response = $this->actingAs($this->user)
            ->post("/trainings/{$training->id}/enroll", $enrollmentData);

        $response->assertRedirect();

        // Should still have only one enrollment
        $this->assertCount(1, $training->students);
    }

    public function test_student_dashboard_shows_enrolled_trainings(): void
    {
        $training = Training::create([
            'title' => 'Enrolled Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Enroll user with approved status
        $training->students()->attach($this->user->id, [
            'status' => 'approved',
            'progress' => 50,
            'grade' => 15,
            'attendance_rate' => 90,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/student/dashboard');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('StudentDashboard')
                ->has('trainings', 1)
            );
    }

    public function test_only_active_trainings_are_listed(): void
    {
        Training::create([
            'title' => 'Active Training',
            'description' => 'Active',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 50.00,
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Inactive Training',
            'description' => 'Inactive',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 50.00,
            'is_active' => false,
        ]);

        $response = $this->get('/api/trainings');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Active Training'])
            ->assertJsonMissing(['title' => 'Inactive Training']);
    }

    public function test_trainings_can_be_sorted(): void
    {
        Training::create([
            'title' => 'Z Training',
            'description' => 'Last',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 100.00,
            'rating' => 4.0,
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'A Training',
            'description' => 'First',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 50.00,
            'rating' => 5.0,
            'is_active' => true,
        ]);

        // Test sorting by title
        $response = $this->get('/api/trainings?sort=title');
        $data = $response->json();
        $this->assertEquals('A Training', $data[0]['title']);

        // Test sorting by price descending
        $response = $this->get('/api/trainings?sort=price-desc');
        $data = $response->json();
        $this->assertEquals('Z Training', $data[0]['title']);

        // Test sorting by rating
        $response = $this->get('/api/trainings?sort=rating');
        $data = $response->json();
        $this->assertEquals('A Training', $data[0]['title']);
    }

    public function test_unauthenticated_user_cannot_enroll(): void
    {
        $training = Training::create([
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->post("/trainings/{$training->id}/enroll");

        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_access_student_dashboard(): void
    {
        $response = $this->get('/student/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_search_trainings_by_title(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::create([
            'title' => 'Web Development Bootcamp',
            'description' => 'Learn web development',
            'duration' => '6 months',
            'level' => 'beginner',
            'price' => 200.00,
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Mobile App Development',
            'description' => 'Learn mobile apps',
            'duration' => '4 months',
            'level' => 'intermediate',
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/trainings?search=Web');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 1)
                ->where('trainings.data.0.title', 'Web Development Bootcamp')
            );
    }

    public function test_admin_can_search_trainings_by_description(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::create([
            'title' => 'Backend Development',
            'description' => 'Learn Laravel framework',
            'duration' => '5 months',
            'level' => 'intermediate',
            'price' => 180.00,
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Frontend Development',
            'description' => 'Learn React framework',
            'duration' => '4 months',
            'level' => 'beginner',
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/trainings?search=Laravel');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 1)
                ->where('trainings.data.0.title', 'Backend Development')
            );
    }

    public function test_admin_can_filter_by_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::create([
            'title' => 'Python Programming',
            'description' => 'Learn Python',
            'duration' => '3 months',
            'level' => 'beginner',
            'price' => 100.00,
            'category' => 'Programming',
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Graphic Design',
            'description' => 'Learn design',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 80.00,
            'category' => 'Design',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/trainings?category=Programming');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 1)
                ->where('trainings.data.0.category', 'Programming')
            );
    }

    public function test_admin_can_combine_search_and_filters(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::create([
            'title' => 'Advanced JavaScript',
            'description' => 'Master JavaScript',
            'duration' => '4 months',
            'level' => 'advanced',
            'price' => 200.00,
            'category' => 'Programming',
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'JavaScript for Beginners',
            'description' => 'Learn JavaScript basics',
            'duration' => '2 months',
            'level' => 'beginner',
            'price' => 80.00,
            'category' => 'Programming',
            'is_active' => true,
        ]);

        Training::create([
            'title' => 'Advanced Python',
            'description' => 'Master Python',
            'duration' => '4 months',
            'level' => 'advanced',
            'price' => 200.00,
            'category' => 'Programming',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/trainings?search=JavaScript&level=advanced');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 1)
                ->where('trainings.data.0.title', 'Advanced JavaScript')
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::create([
            'title' => 'React Development',
            'description' => 'Learn React',
            'duration' => '3 months',
            'level' => 'intermediate',
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/trainings?search=REACT');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 1)
            );
    }

    public function test_empty_search_returns_all_trainings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Training::factory()->count(5)->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get('/trainings?search=');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 5)
            );
    }

    public function test_filters_preserve_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create 20 trainings with different levels
        Training::factory()->count(10)->create(['level' => 'beginner', 'is_active' => true]);
        Training::factory()->count(10)->create(['level' => 'advanced', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/trainings?level=beginner&page=1');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Training/Index')
                ->has('trainings.data', 10)
                ->where('trainings.current_page', 1)
            );
    }
}
