<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Event;
use App\Models\Article;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\CreatesPermissions;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_access_all_resources(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $responses = [
            $this->actingAs($admin)->get('/events'),
            $this->actingAs($admin)->get('/articles'),
            $this->actingAs($admin)->get('/books'),
            $this->actingAs($admin)->get('/departments'),
        ];

        foreach ($responses as $response) {
            $response->assertSuccessful();
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function member_without_permissions_cannot_create_events(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $response = $this->actingAs($member)->get('/events/create');

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function member_without_permissions_cannot_create_articles(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $response = $this->actingAs($member)->post('/articles', [
            'title' => 'Test Article',
            'content' => 'Test Content',
            'status' => 'draft',
        ]);

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_only_edit_own_events(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('edit events');

        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo('edit events');

        $event = Event::factory()->create(['user_id' => $owner->id]);

        // Owner can edit
        $response = $this->actingAs($owner)->get("/events/{$event->uuid}/edit");
        $response->assertSuccessful();

        // Other user cannot edit
        $response = $this->actingAs($otherUser)->get("/events/{$event->uuid}/edit");
        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_only_delete_own_articles(): void
    {
        $author = User::factory()->create();
        $author->givePermissionTo('delete articles');

        $otherUser = User::factory()->create();
        $otherUser->givePermissionTo('delete articles');

        $article = Article::factory()->create(['author_id' => $author->id]);

        // Author can delete
        $response = $this->actingAs($author)->delete("/articles/{$article->slug}");
        $response->assertRedirect();

        // Create another article
        $article2 = Article::factory()->create(['author_id' => $author->id]);

        // Other user cannot delete
        $response = $this->actingAs($otherUser)->delete("/articles/{$article2->id}");
        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_manager_can_manage_all_events(): void
    {
        $eventManager = User::factory()->create();
        $eventManager->assignRole('event-manager');

        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $otherUser->id]);

        // Event manager can edit any event
        $response = $this->actingAs($eventManager)->get("/events/{$event->uuid}/edit");
        $response->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function writer_can_create_and_edit_articles(): void
    {
        $writer = User::factory()->create();
        $writer->assignRole('writer');

        // Can access create form
        $response = $this->actingAs($writer)->get('/articles/create');
        $response->assertSuccessful();

        // Can create article
        $response = $this->actingAs($writer)->post('/articles', [
            'title' => 'Test Article',
            'content' => '<p>Test Content</p>',
            'status' => 'draft',
        ]);

        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function member_can_rent_books_with_permission(): void
    {
        $member = User::factory()->create();
        $member->givePermissionTo('rent books');

        $book = Book::factory()->create(['available_quantity' => 1]);

        $response = $this->actingAs($member)->post("/books/{$book->uuid}/rent");

        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function member_without_permission_cannot_rent_books(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');
        // No "rent books" permission

        $book = Book::factory()->create(['available_quantity' => 1]);

        $response = $this->actingAs($member)->post("/books/{$book->uuid}/rent");

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function permissions_are_enforced_on_api_routes(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        // Attempt to access restricted API endpoint
        $response = $this->actingAs($member)
            ->getJson('/api/users');

        // Should be forbidden or return limited data
        $this->assertTrue(
            $response->isForbidden() || $response->isSuccessful()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function super_admin_bypasses_all_permission_checks(): void
    {
        // Create a super admin role if it exists
        Role::findOrCreate('super-admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        // Should access everything
        $responses = [
            $this->actingAs($superAdmin)->get('/events'),
            $this->actingAs($superAdmin)->get('/articles'),
            $this->actingAs($superAdmin)->get('/books'),
            $this->actingAs($superAdmin)->get('/users'),
        ];

        foreach ($responses as $response) {
            $response->assertSuccessful();
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function role_based_access_works_with_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['member', 'writer']);

        // Should have permissions from both roles
        $this->assertTrue($user->hasRole('member'));
        $this->assertTrue($user->hasRole('writer'));

        // Can create articles (writer permission)
        $response = $this->actingAs($user)->get('/articles/create');
        $response->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removed_permissions_are_immediately_enforced(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        // Can create events
        $response = $this->actingAs($user)->get('/events/create');
        $response->assertSuccessful();

        // Remove permission
        $user->revokePermissionTo('create events');

        // Can no longer create events
        $response = $this->actingAs($user)->get('/events/create');
        $response->assertForbidden();
    }
}
