<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_can_be_updated_with_tus_avatar_string(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-01-01',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'birth_date' => '1995-05-15',
            'avatar' => 'test-image.png', // TUS uploaded filename
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $user->refresh();

        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertEquals('avatars/test-image.png', $user->avatar);
    }

    public function test_profile_can_be_updated_with_uploaded_file(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-01-01',
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'birth_date' => '1995-05-15',
            'avatar' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_profile_update_validates_avatar_extension(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-01-01',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'birth_date' => '1995-05-15',
            'avatar' => 'test-file.txt', // Invalid extension
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_profile_update_without_avatar(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-01-01',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'birth_date' => '1995-05-15',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');
    }
}
