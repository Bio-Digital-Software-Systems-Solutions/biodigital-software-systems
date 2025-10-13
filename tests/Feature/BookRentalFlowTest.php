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

class BookRentalFlowTest extends TestCase
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

    public function test_complete_rental_flow()
    {
        // Create a user with member role
        $user = User::factory()->create();
        $user->assignRole('member');

        // Create a library
        $library = Library::factory()->create();

        // Create a category
        $category = Category::factory()->create(['type' => 'book']);

        // Create a book
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'rental_price' => 15.99,
            'max_rental_days' => 14,
            'stock_quantity' => 5,
        ]);

        // Associate book with library
        $book->libraries()->attach($library->id);

        // Step 1: User views book details
        $response = $this->actingAs($user)->get(route('books.show', $book->uuid));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Show')
            ->has('book')
            ->where('book.id', $book->id)
            ->where('canRent', true)
        );

        // Step 2: User rents the book
        $rentalData = [
            'library_id' => $library->id,
            'rental_days' => 7,
        ];

        $response = $this->actingAs($user)->post(route('books.rent', $book->uuid), $rentalData);
        $response->assertRedirect(route('books.show', $book->uuid));
        $response->assertSessionHas('success');

        // Verify rental was created
        $this->assertDatabaseHas('book_rentals', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);

        // Step 3: User views their rentals
        $response = $this->actingAs($user)->get(route('book-rentals.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('BookRentals/Index')
            ->has('rentals.data', 1)
            ->where('rentals.data.0.book.title', $book->title)
            ->where('rentals.data.0.status', 'active')
        );

        // Step 4: Verify data integrity
        $rental = BookRental::where('user_id', $user->id)->first();
        $this->assertNotNull($rental);
        $this->assertEquals($book->id, $rental->book_id);
        $this->assertEquals($library->id, $rental->library_id);
        $this->assertEquals('active', $rental->status);
        $this->assertEquals(15.99 * 7, $rental->rental_fee);
    }

    public function test_rental_appears_immediately_in_list()
    {
        // Create test user and data
        $user = User::factory()->create();
        $user->assignRole('member');

        $library = Library::factory()->create();
        $category = Category::factory()->create(['type' => 'book']);
        $book = Book::factory()->create(['category_id' => $category->id]);
        $book->libraries()->attach($library->id);

        // Create a rental directly
        $rental = BookRental::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'library_id' => $library->id,
            'status' => 'active',
        ]);

        // Verify it appears in the rentals list
        $response = $this->actingAs($user)->get(route('book-rentals.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('BookRentals/Index')
            ->has('rentals.data', 1)
            ->where('rentals.data.0.id', $rental->id)
        );
    }
}
