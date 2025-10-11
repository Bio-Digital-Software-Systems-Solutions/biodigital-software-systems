<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_image_url_accessor_returns_correct_url_for_local_file(): void
    {
        $training = Training::factory()->create([
            'image' => 'training-1.jpg',
        ]);

        $this->assertNotNull($training->image_url);
        $this->assertStringContainsString('storage/trainings/training-1.jpg', $training->image_url);
        $this->assertStringContainsString('http', $training->image_url);
    }

    public function test_training_image_url_accessor_returns_external_url_unchanged(): void
    {
        $externalUrl = 'https://example.com/images/training.jpg';
        $training = Training::factory()->create([
            'image' => $externalUrl,
        ]);

        $this->assertNotNull($training->image_url);
        $this->assertEquals($externalUrl, $training->image_url);
    }

    public function test_training_image_url_accessor_returns_null_when_no_image(): void
    {
        $training = Training::factory()->create([
            'image' => null,
        ]);

        $this->assertNull($training->image_url);
    }

    public function test_training_api_includes_image_url(): void
    {
        Training::factory()->create([
            'image' => 'training-test.jpg',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/trainings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'image',
                'image_url',
            ],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('image_url', $data[0]);
        $this->assertStringContainsString('storage/trainings/', $data[0]['image_url']);
    }
}
