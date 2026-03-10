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

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::create(['name' => 'view books']);
        Permission::create(['name' => 'manage library']);
        Permission::create(['name' => 'rent books']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view books', 'manage library', 'rent books']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view books', 'rent books']);
    }

    public function test_authenticated_user_can_view_books_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/books');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Index'));
    }

    public function test_user_with_permission_can_create_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Category::factory()->create(['type' => 'book']);

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Create'));
    }

    public function test_user_without_permission_cannot_create_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/books/create');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_user_can_store_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = Category::factory()->create(['type' => 'book']);

        $bookData = [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9781234567890',
            'description' => 'Test Description',
            'rental_price' => 2.50,
            'max_rental_days' => 14,
            'stock_quantity' => 5,
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($user)->post('/books', $bookData);

        $response->assertRedirect('/books');
        $this->assertDatabaseHas('books', [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_user_can_view_single_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get("/books/{$book->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Show')
            ->has('book.title')
        );
    }

    public function test_user_can_rent_available_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $book = Book::factory()->create([
            'stock_quantity' => 5,
            'rental_price' => 2.50,
            'max_rental_days' => 14,
        ]);

        $book->libraries()->attach($library->id);

        $rentalData = [
            'library_id' => $library->id,
            'rental_days' => 7,
        ];

        $response = $this->actingAs($user)->post("/books/{$book->uuid}/rent", $rentalData);

        $response->assertRedirect();
        $this->assertDatabaseHas('book_rentals', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_rent_unavailable_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $book = Book::factory()->create([
            'stock_quantity' => 1,
            'rental_price' => 2.50,
            'max_rental_days' => 14,
        ]);

        // Create an active rental to make book unavailable
        BookRental::factory()->create([
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        // Manually decrement available_copies to simulate the rental
        $book->update(['available_copies' => 0]);

        $book->libraries()->attach($library->id);

        $rentalData = [
            'library_id' => $library->id,
            'rental_days' => 7,
        ];

        $response = $this->actingAs($user)->post("/books/{$book->uuid}/rent", $rentalData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_cannot_rent_same_book_twice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $book = Book::factory()->create([
            'stock_quantity' => 5,
            'rental_price' => 2.50,
            'max_rental_days' => 14,
        ]);

        // Create existing active rental
        BookRental::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $book->libraries()->attach($library->id);

        $rentalData = [
            'library_id' => $library->id,
            'rental_days' => 7,
        ];

        $response = $this->actingAs($user)->post("/books/{$book->uuid}/rent", $rentalData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_update_own_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $book = Book::factory()->create([
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'description' => $book->description,
            'rental_price' => $book->rental_price,
            'max_rental_days' => $book->max_rental_days,
            'stock_quantity' => $book->stock_quantity,
            'category_id' => $book->category_id,
        ];

        $response = $this->actingAs($user)->put("/books/{$book->uuid}", $updateData);

        $response->assertRedirect('/books');
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_delete_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $book = Book::factory()->create();

        $response = $this->actingAs($user)->delete("/books/{$book->uuid}");

        $response->assertRedirect('/books');
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_guest_cannot_access_books(): void
    {
        $response = $this->get('/books');
        $response->assertRedirect('/login');
    }

    public function test_books_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Book::factory()->create(['title' => 'Laravel Book']);
        Book::factory()->create(['title' => 'PHP Book']);
        Book::factory()->create(['title' => 'JavaScript Book']);

        $response = $this->actingAs($user)->get('/books?search=Laravel');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Index')
            ->where('filters.search', 'Laravel')
        );
    }

    public function test_books_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $category1 = Category::factory()->create(['type' => 'book', 'name' => 'Programming']);
        $category2 = Category::factory()->create(['type' => 'book', 'name' => 'Fiction']);

        Book::factory()->create(['category_id' => $category1->id]);
        Book::factory()->create(['category_id' => $category2->id]);

        $response = $this->actingAs($user)->get("/books?category={$category1->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Index')
            ->where('filters.category', (string) $category1->id)
        );
    }
}
