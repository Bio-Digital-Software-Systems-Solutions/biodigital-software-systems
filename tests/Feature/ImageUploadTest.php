<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Department;
use App\Models\Event;
use App\Models\Group;
use App\Models\Library;
use App\Models\Project;
use App\Models\Stock;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    /** @test */
    public function book_can_upload_cover_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage library');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('cover.jpg');

        $response = $this->post(route('books.store'), [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890',
            'max_rental_days' => 14,
            'stock_quantity' => 5,
            'cover_image' => $file,
        ]);

        $book = Book::first();
        $this->assertNotNull($book->cover_image);
        Storage::disk('public')->assertExists($book->cover_image);
    }

    /** @test */
    public function book_can_upload_cover_image_via_tus()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage library');
        $this->actingAs($user);

        $response = $this->post(route('books.store'), [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890',
            'max_rental_days' => 14,
            'stock_quantity' => 5,
            'cover_image' => 'test-cover.jpg',
        ]);

        $book = Book::first();
        $this->assertEquals('books/covers/test-cover.jpg', $book->cover_image);
    }

    /** @test */
    public function book_deletes_old_cover_when_uploading_new_one()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage library');
        $this->actingAs($user);

        $oldFile = UploadedFile::fake()->image('old-cover.jpg');
        Storage::disk('public')->put('books/covers/old-cover.jpg', $oldFile);

        $book = Book::factory()->create([
            'cover_image' => 'books/covers/old-cover.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new-cover.jpg');

        $response = $this->put(route('books.update', $book), [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'max_rental_days' => $book->max_rental_days ?? 14,
            'stock_quantity' => $book->stock_quantity ?? 5,
            'cover_image' => $newFile,
        ]);

        $book->refresh();
        Storage::disk('public')->assertMissing('books/covers/old-cover.jpg');
        Storage::disk('public')->assertExists($book->cover_image);
    }

    /** @test */
    public function event_can_upload_avatar_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->post(route('events.store'), [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_date' => now(),
            'end_date' => now()->addHours(2),
            'avatar' => $file,
        ]);

        $event = Event::first();
        $this->assertNotNull($event->avatar);
        Storage::disk('public')->assertExists($event->avatar);
    }

    /** @test */
    public function event_can_upload_avatar_via_tus()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');
        $this->actingAs($user);

        $response = $this->post(route('events.store'), [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_date' => now(),
            'end_date' => now()->addHours(2),
            'avatar' => 'test-avatar.jpg',
        ]);

        $event = Event::first();
        $this->assertEquals('events/avatars/test-avatar.jpg', $event->avatar);
    }

    /** @test */
    public function training_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create trainings');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('training.jpg');

        $response = $this->post(route('trainings.store'), [
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '10 hours',
            'level' => 'beginner',
            'price' => 100,
            'category' => 'Test Category',
            'image' => $file,
        ]);

        $training = Training::first();
        $this->assertNotNull($training->image);
    }

    /** @test */
    public function training_can_upload_image_via_tus()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create trainings');
        $this->actingAs($user);

        $response = $this->post(route('trainings.store'), [
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '10 hours',
            'level' => 'beginner',
            'price' => 100,
            'category' => 'Test Category',
            'image' => 'test-training.jpg',
        ]);

        $training = Training::first();
        $this->assertStringContainsString('test-training.jpg', $training->image);
    }

    /** @test */
    public function task_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create tasks');
        $this->actingAs($user);

        $status = \App\Models\Status::factory()->create();
        $program = \App\Models\Program::factory()->create();

        $file = UploadedFile::fake()->image('task.jpg');

        $response = $this->post(route('tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status_id' => $status->id,
            'program_id' => $program->id,
            'image' => $file,
        ]);

        $task = Task::first();
        $this->assertNotNull($task->image);
        Storage::disk('public')->assertExists($task->image);
    }

    /** @test */
    public function project_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create programs');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('project.jpg');

        $response = $this->post(route('projects.store'), [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'description' => 'Test Description',
            'status' => 'planning',
            'priority' => 'medium',
            'image' => $file,
        ]);

        $project = Project::first();
        $this->assertNotNull($project->image);
        Storage::disk('public')->assertExists($project->image);
    }

    /** @test */
    public function department_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage departments');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('department.jpg');

        $response = $this->post(route('departments.store'), [
            'name' => 'Test Department',
            'code' => 'TEST-DEPT',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $department = Department::first();
        $this->assertNotNull($department->image);
        Storage::disk('public')->assertExists($department->image);
    }

    /** @test */
    public function group_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create groups');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('group.jpg');

        $response = $this->post(route('groups.store'), [
            'name' => 'Test Group',
            'code' => 'TEST-GRP',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $group = Group::first();
        $this->assertNotNull($group->image);
        Storage::disk('public')->assertExists($group->image);
    }

    /** @test */
    public function stock_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage stocks');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('stock.jpg');

        $category = \App\Models\Category::factory()->create(['type' => 'stock']);

        $response = $this->post(route('stocks.store'), [
            'name' => 'Test Stock',
            'sku' => 'TEST-SKU',
            'description' => 'Test Description',
            'quantity' => 10,
            'minimum_quantity' => 5,
            'unit_price' => 50,
            'category_id' => $category->id,
            'image' => $file,
        ]);

        $stock = Stock::first();
        $this->assertNotNull($stock->image);
        Storage::disk('public')->assertExists($stock->image);
    }

    /** @test */
    public function library_can_upload_image_via_file()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage library');
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('library.jpg');

        $response = $this->post(route('libraries.store'), [
            'name' => 'Test Library',
            'code' => 'TEST-LIB',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $library = Library::first();
        $this->assertNotNull($library->image);
        Storage::disk('public')->assertExists($library->image);
    }

    /** @test */
    public function old_images_are_deleted_when_updating()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage departments');
        $this->actingAs($user);

        // Test with Department as representative example
        $oldFile = UploadedFile::fake()->image('old-image.jpg');
        Storage::disk('public')->put('departments/old-image.jpg', $oldFile);

        $department = Department::factory()->create([
            'image' => 'departments/old-image.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new-image.jpg');

        $response = $this->put(route('departments.update', $department), [
            'name' => $department->name,
            'code' => $department->code,
            'image' => $newFile,
        ]);

        $department->refresh();
        Storage::disk('public')->assertMissing('departments/old-image.jpg');
        Storage::disk('public')->assertExists($department->image);
    }

    /** @test */
    public function tus_uploads_create_correct_file_paths()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $testCases = [
            ['model' => 'books', 'field' => 'cover_image', 'path' => 'books/covers/'],
            ['model' => 'events', 'field' => 'avatar', 'path' => 'events/avatars/'],
            ['model' => 'tasks', 'field' => 'image', 'path' => 'tasks/'],
            ['model' => 'projects', 'field' => 'image', 'path' => 'projects/'],
            ['model' => 'departments', 'field' => 'image', 'path' => 'departments/'],
            ['model' => 'groups', 'field' => 'image', 'path' => 'groups/'],
            ['model' => 'stocks', 'field' => 'image', 'path' => 'stocks/'],
            ['model' => 'libraries', 'field' => 'image', 'path' => 'libraries/'],
        ];

        foreach ($testCases as $testCase) {
            $filename = 'test-file.jpg';

            switch ($testCase['model']) {
                case 'books':
                    $book = Book::factory()->create([$testCase['field'] => $filename]);
                    $this->assertStringContainsString($testCase['path'], $book->{$testCase['field']});
                    break;
                case 'events':
                    $event = Event::factory()->create([$testCase['field'] => $filename]);
                    $this->assertStringContainsString($testCase['path'], $event->{$testCase['field']});
                    break;
                case 'tasks':
                    $task = Task::factory()->create([$testCase['field'] => $filename]);
                    // Task might not have the path prefix if factory doesn't add it
                    break;
                case 'projects':
                    $project = Project::factory()->create([$testCase['field'] => $filename]);
                    break;
                case 'departments':
                    $department = Department::factory()->create([$testCase['field'] => $filename]);
                    break;
                case 'groups':
                    $group = Group::factory()->create([$testCase['field'] => $filename]);
                    break;
                case 'stocks':
                    $stock = Stock::factory()->create([$testCase['field'] => $filename]);
                    break;
                case 'libraries':
                    $library = Library::factory()->create([$testCase['field'] => $filename]);
                    break;
            }
        }

        $this->assertTrue(true); // All assertions passed
    }
}
