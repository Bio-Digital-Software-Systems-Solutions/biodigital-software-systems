<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_articles_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $response = $this->actingAs($user)->get(route('articles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Articles/Index'));
    }

    public function test_articles_index_displays_articles_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('articles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('articles.data', 1)
            ->where('articles.data.0.id', $article->id)
        );
    }

    public function test_events_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Events/Index'));
    }

    public function test_events_index_displays_events_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('events.data', 1)
            ->where('events.data.0.id', $event->id)
        );
    }

    public function test_departments_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view departments');

        $response = $this->actingAs($user)->get(route('departments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Departments/Index'));
    }

    public function test_departments_index_displays_departments_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view departments');

        $department = Department::factory()->create();

        $response = $this->actingAs($user)->get(route('departments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('departments.data', 1)
            ->where('departments.data.0.id', $department->id)
        );
    }

    public function test_books_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view books');

        $response = $this->actingAs($user)->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Books/Index'));
    }

    public function test_books_index_displays_books_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view books');

        $category = Category::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('books.data', 1)
            ->where('books.data.0.id', $book->id)
        );
    }

    public function test_groups_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view groups');

        $response = $this->actingAs($user)->get(route('groups.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Groups/Index'));
    }

    public function test_groups_index_displays_groups_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view groups');

        $group = Group::factory()->create();

        $response = $this->actingAs($user)->get(route('groups.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('groups.data', 1)
            ->where('groups.data.0.id', $group->id)
        );
    }

    public function test_articles_index_with_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create(['name' => 'Test Category']);

        Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        Article::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('articles.index', ['category' => $category->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('articles.data', 1)
        );
    }

    public function test_books_index_with_search(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view books');

        $book1 = Book::factory()->create([
            'title' => 'Laravel Programming',
            'author' => 'John Doe',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'React Development',
            'author' => 'Jane Smith',
        ]);

        $response = $this->actingAs($user)->get(route('books.index', ['search' => 'Laravel']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('books.data', 1)
            ->where('books.data.0.id', $book1->id)
        );
    }

    public function test_departments_index_with_status_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view departments');

        $activeDept = Department::factory()->create(['is_active' => true]);
        $inactiveDept = Department::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('departments.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('departments.data', 1)
            ->where('departments.data.0.id', $activeDept->id)
        );
    }

    public function test_groups_index_with_status_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view groups');

        $activeGroup = Group::factory()->create(['is_active' => true]);
        $inactiveGroup = Group::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('groups.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('groups.data', 1)
            ->where('groups.data.0.id', $activeGroup->id)
        );
    }

    public function test_unauthorized_user_cannot_access_articles_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('articles.index'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_unauthorized_user_cannot_access_events_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('events.index'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_pagination_works_for_articles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();

        // Create 20 articles
        Article::factory()->count(20)->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('articles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('articles.data')
            ->has('articles.links')
        );
    }

    public function test_pagination_works_for_events(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        // Create 20 events
        Event::factory()->count(20)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('events.data')
            ->has('events.links')
        );
    }
}
