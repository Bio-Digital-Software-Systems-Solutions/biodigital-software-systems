<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Library;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Student;
use App\Models\Tag;
use App\Models\Teacher;
use App\Models\TrainingClassSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalUuidRouteModelBindingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that models using UUID for routing have proper UUID setup
     */
    public function test_models_with_uuid_routing(): void
    {
        $models = [
            ['class' => Message::class, 'factory_data' => function (): array {
                $sender = User::factory()->create();
                $receiver = User::factory()->create();

                return ['sender_id' => $sender->id, 'receiver_id' => $receiver->id];
            }],
            ['class' => Sprint::class, 'factory_data' => []],
            ['class' => Teacher::class, 'factory_data' => []],
            ['class' => Student::class, 'factory_data' => []],
            ['class' => Quiz::class, 'factory_data' => []],
            ['class' => QuizAttempt::class, 'factory_data' => []],
        ];

        foreach ($models as $modelData) {
            $class = $modelData['class'];
            $factoryData = is_callable($modelData['factory_data'])
                ? $modelData['factory_data']()
                : $modelData['factory_data'];

            $model = $class::factory()->create($factoryData);

            $this->assertNotNull($model->uuid, "{$class} should have a UUID");
            $this->assertEquals('uuid', $model->getRouteKeyName(), "{$class} should use UUID for routing");
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $model->uuid,
                "{$class} UUID should be valid UUID v4 format"
            );

            $json = $model->toArray();
            $this->assertArrayHasKey('uuid', $json, "{$class} UUID should be included in JSON serialization");
        }
    }

    /**
     * Test that models with UUID but using other routing keys still have UUID field
     */
    public function test_models_with_uuid_but_custom_routing(): void
    {
        $models = [
            ['class' => Library::class, 'route_key' => 'code', 'factory_data' => []],
            ['class' => Category::class, 'route_key' => 'slug', 'factory_data' => []],
            ['class' => Status::class, 'route_key' => 'name', 'factory_data' => []],
            ['class' => Article::class, 'route_key' => 'slug', 'factory_data' => function (): array {
                $user = User::factory()->create();
                $category = Category::factory()->create();

                return ['user_id' => $user->id, 'category_id' => $category->id];
            }],
        ];

        foreach ($models as $modelData) {
            $class = $modelData['class'];
            $factoryData = is_callable($modelData['factory_data'])
                ? $modelData['factory_data']()
                : $modelData['factory_data'];
            $model = $class::factory()->create($factoryData);

            $this->assertNotNull($model->uuid, "{$class} should have a UUID field");
            $this->assertEquals($modelData['route_key'], $model->getRouteKeyName(),
                "{$class} should use {$modelData['route_key']} for routing, not UUID");
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $model->uuid,
                "{$class} UUID should be valid UUID v4 format"
            );
        }
    }

    /**
     * Test UUID uniqueness across multiple records
     */
    public function test_uuid_uniqueness(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $message1 = Message::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);
        $message2 = Message::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

        $this->assertNotEquals($message1->uuid, $message2->uuid, 'UUIDs should be unique');

        $sprint1 = Sprint::factory()->create();
        $sprint2 = Sprint::factory()->create();

        $this->assertNotEquals($sprint1->uuid, $sprint2->uuid, 'UUIDs should be unique');
    }
}
