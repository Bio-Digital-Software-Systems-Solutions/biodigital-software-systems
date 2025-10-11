<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('authenticated user can access tus upload endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->options('/api/files');

    $response->assertStatus(204);
    $response->assertHeader('Tus-Resumable', '1.0.0');
});

test('unauthenticated user cannot access tus upload endpoint', function () {
    $response = $this->post('/api/files');

    $response->assertStatus(302); // Redirect to login
});

test('event can be created with images', function () {
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
            'images' => ['events/test-image-1.jpg', 'events/test-image-2.jpg'],
        ]);

    $response->assertRedirect(route('events.index'));

    $this->assertDatabaseHas('events', [
        'title' => 'Test Event with Images',
    ]);

    $event = \App\Models\Event::where('title', 'Test Event with Images')->first();
    expect($event->images)->toBeArray();
    expect($event->images)->toHaveCount(2);
});

test('article can be created with featured image and documents', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('create articles');

    $response = $this->actingAs($user)
        ->post(route('articles.store'), [
            'title' => 'Test Article with Media',
            'content' => 'Article with media uploads',
            'status' => 'published',
            'featured_image' => 'articles/images/featured.jpg',
            'images' => ['articles/images/gallery-1.jpg'],
            'documents' => ['articles/documents/document.pdf'],
        ]);

    $response->assertRedirect(route('articles.index'));

    $this->assertDatabaseHas('articles', [
        'title' => 'Test Article with Media',
    ]);
});
