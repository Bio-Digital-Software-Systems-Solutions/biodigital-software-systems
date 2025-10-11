<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_validation_with_tus_string()
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
            'avatar' => 'test-image.png',
        ]);

        // Dump session errors if any
        if ($response->getSession()->has('errors')) {
            dump('Session has errors:');
            dump($response->getSession()->get('errors')->toArray());
        }

        // Dump validation errors
        $response->dumpSession();
        
        $response->assertSessionHasNoErrors();
    }
}
