<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingTopicsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::create(['name' => 'manage trainings']);
        Permission::create(['name' => 'view trainings']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['manage trainings', 'view trainings']);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    public function test_can_create_training_with_topics(): void
    {
        $trainingData = [
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 mois',
            'level' => 'beginner',
            'price' => 299.99,
            'category' => 'Web Development',
            'is_active' => true,
            'topics' => [
                [
                    'name' => 'Introduction to HTML',
                    'description' => 'Learn the basics of HTML',
                ],
                [
                    'name' => 'CSS Fundamentals',
                    'description' => 'Master CSS styling',
                ],
                [
                    'name' => 'JavaScript Basics',
                    'description' => 'Introduction to JavaScript',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('trainings.store'), $trainingData);

        $response->assertRedirect(route('trainings.index'));

        $this->assertDatabaseHas('trainings', [
            'title' => 'Test Training',
        ]);

        $training = Training::where('title', 'Test Training')->first();
        $this->assertCount(3, $training->topics);

        // Check topics are created with correct order
        $topics = $training->topics()->orderBy('order')->get();
        $this->assertEquals('Introduction to HTML', $topics[0]->name);
        $this->assertEquals('Learn the basics of HTML', $topics[0]->description);
        $this->assertEquals(1, $topics[0]->order);

        $this->assertEquals('CSS Fundamentals', $topics[1]->name);
        $this->assertEquals(2, $topics[1]->order);

        $this->assertEquals('JavaScript Basics', $topics[2]->name);
        $this->assertEquals(3, $topics[2]->order);
    }

    public function test_can_create_training_without_topics(): void
    {
        $trainingData = [
            'title' => 'Training Without Topics',
            'description' => 'Test Description',
            'duration' => '2 mois',
            'level' => 'intermediate',
            'price' => 199.99,
            'category' => 'Design',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('trainings.store'), $trainingData);

        $response->assertRedirect(route('trainings.index'));

        $training = Training::where('title', 'Training Without Topics')->first();
        $this->assertCount(0, $training->topics);
    }

    public function test_can_update_training_and_add_new_topics(): void
    {
        $training = Training::factory()->create();

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [
                [
                    'name' => 'New Topic 1',
                    'description' => 'Description 1',
                    'order' => 0,
                ],
                [
                    'name' => 'New Topic 2',
                    'description' => 'Description 2',
                    'order' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $this->assertCount(2, $training->topics);
        $this->assertEquals('New Topic 1', $training->topics->first()->name);
    }

    public function test_can_update_existing_topics(): void
    {
        $training = Training::factory()->create();
        $topic1 = $training->topics()->create([
            'name' => 'Original Topic 1',
            'description' => 'Original Description 1',
            'order' => 0,
        ]);
        $topic2 = $training->topics()->create([
            'name' => 'Original Topic 2',
            'description' => 'Original Description 2',
            'order' => 1,
        ]);

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [
                [
                    'id' => $topic1->id,
                    'name' => 'Updated Topic 1',
                    'description' => 'Updated Description 1',
                    'order' => 0,
                ],
                [
                    'id' => $topic2->id,
                    'name' => 'Updated Topic 2',
                    'description' => 'Updated Description 2',
                    'order' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $this->assertCount(2, $training->topics);

        $topics = $training->topics()->orderBy('order')->get();
        $this->assertEquals('Updated Topic 1', $topics[0]->name);
        $this->assertEquals('Updated Description 1', $topics[0]->description);
        $this->assertEquals('Updated Topic 2', $topics[1]->name);
        $this->assertEquals('Updated Description 2', $topics[1]->description);
    }

    public function test_can_delete_topics_using_destroy_flag(): void
    {
        $training = Training::factory()->create();
        $topic1 = $training->topics()->create([
            'name' => 'Topic to Keep',
            'description' => 'This should stay',
            'order' => 0,
        ]);
        $topic2 = $training->topics()->create([
            'name' => 'Topic to Delete',
            'description' => 'This should be deleted',
            'order' => 1,
        ]);

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [
                [
                    'id' => $topic1->id,
                    'name' => 'Topic to Keep',
                    'description' => 'This should stay',
                    'order' => 0,
                ],
                [
                    'id' => $topic2->id,
                    'name' => 'Topic to Delete',
                    'description' => 'This should be deleted',
                    'order' => 1,
                    '_destroy' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $this->assertCount(1, $training->topics);
        $this->assertEquals('Topic to Keep', $training->topics->first()->name);

        $this->assertDatabaseMissing('training_topics', [
            'id' => $topic2->id,
        ]);
    }

    public function test_can_reorder_topics(): void
    {
        $training = Training::factory()->create();
        $topic1 = $training->topics()->create([
            'name' => 'First Topic',
            'description' => 'Should be second',
            'order' => 0,
        ]);
        $topic2 = $training->topics()->create([
            'name' => 'Second Topic',
            'description' => 'Should be first',
            'order' => 1,
        ]);

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [
                [
                    'id' => $topic2->id,
                    'name' => 'Second Topic',
                    'description' => 'Should be first',
                    'order' => 0,
                ],
                [
                    'id' => $topic1->id,
                    'name' => 'First Topic',
                    'description' => 'Should be second',
                    'order' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $topics = $training->topics()->orderBy('order')->get();
        $this->assertEquals('Second Topic', $topics[0]->name);
        $this->assertEquals(0, $topics[0]->order);
        $this->assertEquals('First Topic', $topics[1]->name);
        $this->assertEquals(1, $topics[1]->order);
    }

    public function test_can_mix_add_update_and_delete_topics(): void
    {
        $training = Training::factory()->create();
        $existingTopic = $training->topics()->create([
            'name' => 'Existing Topic',
            'description' => 'Will be updated',
            'order' => 0,
        ]);
        $topicToDelete = $training->topics()->create([
            'name' => 'Topic to Delete',
            'description' => 'Will be removed',
            'order' => 1,
        ]);

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [
                [
                    'id' => $existingTopic->id,
                    'name' => 'Updated Existing Topic',
                    'description' => 'Has been updated',
                    'order' => 0,
                ],
                [
                    'name' => 'Brand New Topic',
                    'description' => 'Just added',
                    'order' => 1,
                ],
                [
                    'id' => $topicToDelete->id,
                    'name' => 'Topic to Delete',
                    'description' => 'Will be removed',
                    'order' => 2,
                    '_destroy' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $this->assertCount(2, $training->topics);

        $topics = $training->topics()->orderBy('order')->get();
        $this->assertEquals('Updated Existing Topic', $topics[0]->name);
        $this->assertEquals('Brand New Topic', $topics[1]->name);

        $this->assertDatabaseMissing('training_topics', [
            'id' => $topicToDelete->id,
        ]);
    }

    public function test_topic_name_is_required(): void
    {
        $trainingData = [
            'title' => 'Test Training',
            'description' => 'Test Description',
            'duration' => '3 mois',
            'level' => 'beginner',
            'price' => 299.99,
            'category' => 'Web Development',
            'is_active' => true,
            'topics' => [
                [
                    'name' => '', // Empty name should fail
                    'description' => 'Description without name',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('trainings.store'), $trainingData);

        $response->assertSessionHasErrors('topics.0.name');
    }

    public function test_can_delete_all_topics_from_training(): void
    {
        $training = Training::factory()->create();
        $training->topics()->create([
            'name' => 'Topic 1',
            'description' => 'Description 1',
            'order' => 0,
        ]);
        $training->topics()->create([
            'name' => 'Topic 2',
            'description' => 'Description 2',
            'order' => 1,
        ]);

        $this->assertCount(2, $training->topics);

        $updateData = [
            'title' => $training->title,
            'description' => $training->description,
            'duration' => $training->duration,
            'level' => $training->level,
            'price' => $training->price,
            'category' => $training->category,
            'image' => $training->image,
            'is_active' => $training->is_active,
            'teacher_id' => $training->teacher_id,
            'topics' => [], // Empty topics array
        ];

        $response = $this->actingAs($this->user)
            ->put(route('trainings.update', $training->uuid), $updateData);

        $response->assertRedirect(route('trainings.index'));

        $training->refresh();
        $this->assertCount(0, $training->topics);
    }
}
