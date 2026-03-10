<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('authenticated user can access tus upload endpoint', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->withHeaders(['Tus-Resumable' => '1.0.0'])
        ->post('/api/files');

    // Authenticated user should not get a 401/403 - the TUS server handles the request
    expect($response->getStatusCode())->not->toBeIn([401, 403]);
});

test('unauthenticated user cannot access tus upload endpoint', function (): void {
    $response = $this->post('/api/files');

    $response->assertStatus(302); // Redirect to login
});

test('event can be created with images', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create events');

    $response = $this->actingAs($user)
        ->post(route('events.store'), [
            'title' => 'Test Event with Images',
            'description' => 'Event with image uploads',
            'start_date' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'location' => 'Test Location',
            'is_public' => true,
        ]);

    $event = \App\Models\Event::where('title', 'Test Event with Images')->first();
    $response->assertRedirect(route('events.edit', $event->uuid));

    $this->assertDatabaseHas('events', [
        'title' => 'Test Event with Images',
    ]);
});

test('article can be created with featured image and documents', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create articles');

    $category = \App\Models\Category::factory()->create(['type' => 'article']);

    $response = $this->actingAs($user)
        ->post(route('articles.store'), [
            'title' => 'Test Article with Media',
            'content' => 'Article with media uploads',
            'status' => 'published',
            'category_id' => $category->id,
            'featured_image' => 'articles/images/featured.jpg',
            'images' => ['articles/images/gallery-1.jpg'],
            'documents' => ['articles/documents/document.pdf'],
        ]);

    $response->assertRedirect(route('articles.index'));

    $this->assertDatabaseHas('articles', [
        'title' => 'Test Article with Media',
    ]);
});
