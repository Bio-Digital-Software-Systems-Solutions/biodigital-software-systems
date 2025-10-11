<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRental;
use App\Models\Category;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookRentalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view books']);
        Permission::create(['name' => 'rent books']);
        Permission::create(['name' => 'manage library']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view books', 'rent books']);
    }

    public function test_user_can_view_their_book_rentals()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $category = Category::factory()->create(['type' => 'book']);
        $book = Book::factory()->create(['category_id' => $category->id]);

        // Create a rental for this user
        $rental = BookRental::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/my-rentals');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('BookRentals/Index')
            ->has('rentals.data', 1)
            ->where('rentals.data.0.id', $rental->id)
            ->where('rentals.data.0.book.title', $book->title)
            ->where('rentals.data.0.book.author', $book->author)
            ->where('rentals.data.0.library.name', $library->name)
        );
    }

    public function test_user_cannot_see_other_users_rentals()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');
        $user2->assignRole('member');

        $library = Library::factory()->create();
        $book = Book::factory()->create();

        // Create rental for user2
        BookRental::factory()->create([
            'user_id' => $user2->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user1)->get('/my-rentals');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('BookRentals/Index')
            ->has('rentals.data', 0) // User1 should see no rentals
        );
    }

    public function test_user_can_filter_rentals_by_status()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $book = Book::factory()->create();

        // Create active and returned rentals
        BookRental::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);

        BookRental::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'returned',
            'return_date' => now()->subDays(5),
        ]);

        // Test filter for active rentals
        $response = $this->actingAs($user)->get('/my-rentals?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('BookRentals/Index')
            ->has('rentals.data', 1)
            ->where('rentals.data.0.status', 'active')
        );
    }

    public function test_guest_cannot_access_rentals()
    {
        $response = $this->get('/my-rentals');

        $response->assertRedirect('/login');
    }

    public function test_user_without_view_books_permission_cannot_access_rentals()
    {
        $user = User::factory()->create();
        // Don't give any permissions

        $response = $this->actingAs($user)->get('/my-rentals');

        $response->assertStatus(403);
    }
}
