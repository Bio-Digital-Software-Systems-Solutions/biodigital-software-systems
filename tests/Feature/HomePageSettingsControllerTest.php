<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomePageSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'manage hero slides']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo('manage hero slides');

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
    }

    // ==========================================
    // Homepage Settings Page Access Tests
    // ==========================================

    /** @test */
    public function admin_can_access_homepage_settings()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Settings/Homepage')
                ->has('slides')
        );
    }

    /** @test */
    public function regular_user_cannot_access_homepage_settings()
    {
        $response = $this->actingAs($this->user)->get(route('settings.homepage'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function guest_cannot_access_homepage_settings()
    {
        $response = $this->get(route('settings.homepage'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function homepage_settings_returns_slides_ordered()
    {
        $slide1 = HeroSlide::factory()->create(['order' => 3]);
        $slide2 = HeroSlide::factory()->create(['order' => 1]);
        $slide3 = HeroSlide::factory()->create(['order' => 2]);

        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Settings/Homepage')
                ->has('slides', 3)
                ->where('slides.0.id', $slide2->id)
                ->where('slides.1.id', $slide3->id)
                ->where('slides.2.id', $slide1->id)
        );
    }

    // ==========================================
    // Create Slide Tests
    // ==========================================

    /** @test */
    public function admin_can_create_slide_with_url()
    {
        $data = [
            'title' => 'New Slide',
            'description' => 'Slide Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
            'cta_text' => 'Learn More',
            'cta_link' => '/about',
            'overlay_opacity' => 0.5,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.store'), $data);

        $response->assertRedirect(route('settings.homepage'));
        $this->assertDatabaseHas('hero_slides', [
            'title' => 'New Slide',
            'description' => 'Slide Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
        ]);
    }

    /** @test */
    public function admin_can_create_slide_with_file_upload()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('hero-slide.jpg', 1920, 1080);

        $data = [
            'title' => 'Upload Slide',
            'description' => 'Description',
            'media_type' => 'image',
            'media_file' => $file,
            'overlay_opacity' => 0.6,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.store'), $data);

        $response->assertRedirect(route('settings.homepage'));

        $slide = HeroSlide::where('title', 'Upload Slide')->first();
        $this->assertNotNull($slide);
        $this->assertStringContainsString('hero-slides/', $slide->media_url);
    }

    /** @test */
    public function admin_can_create_video_slide()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('hero-video.mp4', 5000, 'video/mp4');

        $data = [
            'title' => 'Video Slide',
            'description' => 'A video slide',
            'media_type' => 'video',
            'media_file' => $file,
            'overlay_opacity' => 0.4,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.store'), $data);

        $response->assertRedirect(route('settings.homepage'));
        $this->assertDatabaseHas('hero_slides', [
            'title' => 'Video Slide',
            'media_type' => 'video',
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_slide()
    {
        $data = [
            'title' => 'Test Slide',
            'description' => 'Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
        ];

        $response = $this->actingAs($this->user)->post(route('settings.homepage.slides.store'), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseMissing('hero_slides', ['title' => 'Test Slide']);
    }

    /** @test */
    public function new_slide_gets_auto_incremented_order()
    {
        HeroSlide::factory()->create(['order' => 1]);
        HeroSlide::factory()->create(['order' => 2]);

        $data = [
            'title' => 'Third Slide',
            'description' => 'Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
        ];

        $this->actingAs($this->admin)->post(route('settings.homepage.slides.store'), $data);

        $slide = HeroSlide::where('title', 'Third Slide')->first();
        $this->assertEquals(3, $slide->order);
    }

    /** @test */
    public function validation_fails_without_media()
    {
        $data = [
            'title' => 'No Media Slide',
            'description' => 'Description',
            'media_type' => 'image',
            // No media_url or media_file
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.store'), $data);

        $response->assertSessionHasErrors(['media_url', 'media_file']);
    }

    // ==========================================
    // Update Slide Tests
    // ==========================================

    /** @test */
    public function admin_can_update_slide()
    {
        $slide = HeroSlide::factory()->create([
            'title' => 'Original Title',
            'media_type' => 'image',
        ]);

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/new-image.jpg',
            'overlay_opacity' => 0.7,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.update', $slide), $data);

        $response->assertRedirect(route('settings.homepage'));
        $this->assertDatabaseHas('hero_slides', [
            'id' => $slide->id,
            'title' => 'Updated Title',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function admin_can_update_slide_with_new_file()
    {
        Storage::fake('public');

        $slide = HeroSlide::factory()->create([
            'media_url' => '/storage/hero-slides/old-image.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new-hero.jpg', 1920, 1080);

        $data = [
            'title' => 'Updated Slide',
            'description' => 'Description',
            'media_type' => 'image',
            'media_file' => $newFile,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.update', $slide), $data);

        $response->assertRedirect(route('settings.homepage'));
        $slide->refresh();
        $this->assertStringContainsString('hero-slides/', $slide->media_url);
    }

    /** @test */
    public function regular_user_cannot_update_slide()
    {
        $slide = HeroSlide::factory()->create();

        $data = [
            'title' => 'Hacked Title',
            'description' => 'Hacked',
            'media_type' => 'image',
            'media_url' => 'https://example.com/hacked.jpg',
        ];

        $response = $this->actingAs($this->user)->post(route('settings.homepage.slides.update', $slide), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $slide->refresh();
        $this->assertNotEquals('Hacked Title', $slide->title);
    }

    // ==========================================
    // Delete Slide Tests
    // ==========================================

    /** @test */
    public function admin_can_delete_slide()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('settings.homepage.slides.destroy', $slide));

        $response->assertRedirect(route('settings.homepage'));
        $this->assertDatabaseMissing('hero_slides', ['id' => $slide->id]);
    }

    /** @test */
    public function regular_user_cannot_delete_slide()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('settings.homepage.slides.destroy', $slide));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseHas('hero_slides', ['id' => $slide->id]);
    }

    // ==========================================
    // Reorder Slides Tests
    // ==========================================

    /** @test */
    public function admin_can_reorder_slides()
    {
        $slide1 = HeroSlide::factory()->create(['order' => 1]);
        $slide2 = HeroSlide::factory()->create(['order' => 2]);
        $slide3 = HeroSlide::factory()->create(['order' => 3]);

        $data = [
            'slides' => [
                ['id' => $slide3->id, 'order' => 1],
                ['id' => $slide1->id, 'order' => 2],
                ['id' => $slide2->id, 'order' => 3],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.reorder'), $data);

        $response->assertRedirect(route('settings.homepage'));

        $this->assertEquals(1, $slide3->fresh()->order);
        $this->assertEquals(2, $slide1->fresh()->order);
        $this->assertEquals(3, $slide2->fresh()->order);
    }

    /** @test */
    public function regular_user_cannot_reorder_slides()
    {
        $slide1 = HeroSlide::factory()->create(['order' => 1]);
        $slide2 = HeroSlide::factory()->create(['order' => 2]);

        $data = [
            'slides' => [
                ['id' => $slide2->id, 'order' => 1],
                ['id' => $slide1->id, 'order' => 2],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('settings.homepage.slides.reorder'), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );

        // Order should be unchanged
        $this->assertEquals(1, $slide1->fresh()->order);
        $this->assertEquals(2, $slide2->fresh()->order);
    }

    /** @test */
    public function reorder_validates_slide_ids()
    {
        $slide = HeroSlide::factory()->create();

        $data = [
            'slides' => [
                ['id' => 99999, 'order' => 1], // Non-existent slide
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.slides.reorder'), $data);

        $response->assertSessionHasErrors(['slides.0.id']);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    /** @test */
    public function welcome_page_shows_active_slides()
    {
        $activeSlide = HeroSlide::factory()->create(['is_active' => true, 'order' => 1]);
        $inactiveSlide = HeroSlide::factory()->create(['is_active' => false, 'order' => 2]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Welcome')
                ->has('heroSlides', 1)
                ->where('heroSlides.0.id', $activeSlide->id)
        );
    }

    // ==========================================
    // Global Stats Tests
    // ==========================================

    /** @test */
    public function homepage_settings_returns_global_stats()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Settings/Homepage')
                ->has('globalStats')
                ->has('globalStats.total_churches')
                ->has('globalStats.total_countries')
                ->has('globalStats.total_members')
                ->has('globalStats.europe')
                ->has('globalStats.africa')
                ->has('globalStats.americas')
                ->has('globalStats.asia')
                ->has('globalStats.oceania')
        );
    }

    /** @test */
    public function admin_can_update_global_stats()
    {
        $data = [
            'total_churches' => 150,
            'total_countries' => 30,
            'total_members' => 60000,
            'europe' => 70,
            'africa' => 50,
            'americas' => 20,
            'asia' => 8,
            'oceania' => 2,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.global-stats.update'), $data);

        $response->assertRedirect();

        // Verify the stats were saved
        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));
        $response->assertInertia(
            fn($page) => $page
                ->where('globalStats.total_churches', 150)
                ->where('globalStats.total_countries', 30)
                ->where('globalStats.total_members', 60000)
                ->where('globalStats.europe', 70)
                ->where('globalStats.africa', 50)
                ->where('globalStats.americas', 20)
                ->where('globalStats.asia', 8)
                ->where('globalStats.oceania', 2)
        );
    }

    /** @test */
    public function regular_user_cannot_update_global_stats()
    {
        $data = [
            'total_churches' => 999,
            'total_countries' => 999,
            'total_members' => 999999,
            'europe' => 999,
            'africa' => 999,
            'americas' => 999,
            'asia' => 999,
            'oceania' => 999,
        ];

        $response = $this->actingAs($this->user)->post(route('settings.homepage.global-stats.update'), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function global_stats_validation_rejects_negative_numbers()
    {
        $data = [
            'total_churches' => -5,
            'total_countries' => 30,
            'total_members' => 60000,
            'europe' => 70,
            'africa' => 50,
            'americas' => 20,
            'asia' => 5,
            'oceania' => 2,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.global-stats.update'), $data);

        $response->assertSessionHasErrors(['total_churches']);
    }

    /** @test */
    public function global_stats_validation_requires_all_fields()
    {
        $data = [
            'total_churches' => 100,
            // Missing other fields
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.global-stats.update'), $data);

        $response->assertSessionHasErrors(['total_countries', 'total_members', 'europe', 'africa', 'americas', 'asia', 'oceania']);
    }

    // ==========================================
    // Church Management Tests
    // ==========================================

    /** @test */
    public function homepage_settings_returns_churches()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Settings/Homepage')
                ->has('churches')
        );
    }

    /** @test */
    public function admin_can_create_church()
    {
        $data = [
            'name' => 'ICC Test',
            'city' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'members' => 500,
            'address' => '123 Test Street',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('churches', [
            'name' => 'ICC Test',
            'city' => 'Test City',
            'country' => 'Test Country',
        ]);
    }

    /** @test */
    public function admin_can_update_church()
    {
        $church = \App\Models\Church::create([
            'name' => 'Original Church',
            'city' => 'Original City',
            'country' => 'Original Country',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $data = [
            'name' => 'Updated Church',
            'city' => 'Updated City',
            'country' => 'Updated Country',
            'latitude' => 45.0,
            'longitude' => 5.0,
            'members' => 1000,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.update', $church), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('churches', [
            'id' => $church->id,
            'name' => 'Updated Church',
            'city' => 'Updated City',
        ]);
    }

    /** @test */
    public function admin_can_delete_church()
    {
        $church = \App\Models\Church::create([
            'name' => 'Church to Delete',
            'city' => 'City',
            'country' => 'Country',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('settings.homepage.churches.destroy', $church));

        $response->assertRedirect();
        $this->assertDatabaseMissing('churches', ['id' => $church->id]);
    }

    /** @test */
    public function regular_user_cannot_create_church()
    {
        $data = [
            'name' => 'Unauthorized Church',
            'city' => 'City',
            'country' => 'Country',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ];

        $response = $this->actingAs($this->user)->post(route('settings.homepage.churches.store'), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseMissing('churches', ['name' => 'Unauthorized Church']);
    }

    /** @test */
    public function church_validation_requires_name_city_country_coordinates()
    {
        $data = [
            // Missing all required fields
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $response->assertSessionHasErrors(['name', 'city', 'country']);
    }

    /** @test */
    public function church_auto_detects_continent()
    {
        $data = [
            'name' => 'ICC Paris',
            'city' => 'Paris',
            'country' => 'France',
            'latitude' => 48.8566,  // Europe
            'longitude' => 2.3522,
            'is_active' => true,
        ];

        $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $church = \App\Models\Church::where('name', 'ICC Paris')->first();
        $this->assertEquals('europe', $church->continent);
    }

    /** @test */
    public function admin_can_create_church_with_leader_and_category()
    {
        $data = [
            'name' => 'ICC Lyon',
            'city' => 'Lyon',
            'country' => 'France',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'members' => 300,
            'leader_name' => 'Pasteur Jean Martin',
            'category' => 'campus_connecte',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('churches', [
            'name' => 'ICC Lyon',
            'leader_name' => 'Pasteur Jean Martin',
            'category' => 'campus_connecte',
        ]);
    }

    /** @test */
    public function admin_can_update_church_leader_and_category()
    {
        $church = \App\Models\Church::create([
            'name' => 'ICC Marseille',
            'city' => 'Marseille',
            'country' => 'France',
            'latitude' => 43.2965,
            'longitude' => 5.3698,
            'leader_name' => 'Old Leader',
            'category' => 'eglise',
        ]);

        $data = [
            'name' => 'ICC Marseille',
            'city' => 'Marseille',
            'country' => 'France',
            'latitude' => 43.2965,
            'longitude' => 5.3698,
            'leader_name' => 'New Leader',
            'category' => 'famille_impact',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.update', $church), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('churches', [
            'id' => $church->id,
            'leader_name' => 'New Leader',
            'category' => 'famille_impact',
        ]);
    }

    /** @test */
    public function category_validation_rejects_invalid_values()
    {
        $data = [
            'name' => 'ICC Test',
            'city' => 'Test City',
            'country' => 'Test Country',
            'category' => 'invalid_category',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $response->assertSessionHasErrors(['category']);
    }

    /** @test */
    public function leader_name_and_category_are_optional()
    {
        $data = [
            'name' => 'ICC Minimal',
            'city' => 'Minimal City',
            'country' => 'Minimal Country',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('churches', [
            'name' => 'ICC Minimal',
            'category' => 'eglise', // default value
        ]);
    }

    /** @test */
    public function global_stats_update_when_church_created()
    {
        // Clear any existing churches
        \App\Models\Church::query()->delete();

        $data = [
            'name' => 'First Church',
            'city' => 'Paris',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'members' => 500,
            'is_active' => true,
        ];

        $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), $data);

        $stats = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(1, $stats['total_churches']);
        $this->assertEquals(1, $stats['total_countries']);
        $this->assertEquals(500, $stats['total_members']);
        $this->assertEquals(1, $stats['europe']);
    }

    /** @test */
    public function global_stats_update_when_church_deleted()
    {
        // Clear any existing churches
        \App\Models\Church::query()->delete();

        // Create a church first
        $church = \App\Models\Church::create([
            'name' => 'Church to Delete',
            'city' => 'Berlin',
            'country' => 'Germany',
            'latitude' => 52.52,
            'longitude' => 13.405,
            'members' => 300,
            'continent' => 'europe',
            'is_active' => true,
        ]);

        // Recalculate to set initial stats
        \App\Models\SiteSetting::recalculateGlobalStats();
        $statsBefore = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(1, $statsBefore['total_churches']);

        // Delete the church
        $this->actingAs($this->admin)->delete(route('settings.homepage.churches.destroy', $church));

        $statsAfter = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(0, $statsAfter['total_churches']);
        $this->assertEquals(0, $statsAfter['total_countries']);
        $this->assertEquals(0, $statsAfter['total_members']);
    }

    /** @test */
    public function total_countries_increases_for_new_country()
    {
        // Clear any existing churches
        \App\Models\Church::query()->delete();

        // Create first church in France
        $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), [
            'name' => 'ICC France',
            'city' => 'Paris',
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'is_active' => true,
        ]);

        $stats1 = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(1, $stats1['total_countries']);

        // Create second church in Germany (new country)
        $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), [
            'name' => 'ICC Germany',
            'city' => 'Berlin',
            'country' => 'Germany',
            'latitude' => 52.52,
            'longitude' => 13.405,
            'is_active' => true,
        ]);

        $stats2 = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(2, $stats2['total_countries']);

        // Create third church in France (same country)
        $this->actingAs($this->admin)->post(route('settings.homepage.churches.store'), [
            'name' => 'ICC Lyon',
            'city' => 'Lyon',
            'country' => 'France',
            'latitude' => 45.764,
            'longitude' => 4.8357,
            'is_active' => true,
        ]);

        $stats3 = \App\Models\SiteSetting::getGlobalStats();
        $this->assertEquals(2, $stats3['total_countries']); // Still 2 countries
        $this->assertEquals(3, $stats3['total_churches']);
    }

    /** @test */
    public function total_members_updates_with_church_changes()
    {
        // Clear any existing churches
        \App\Models\Church::query()->delete();

        // Create church with 100 members
        $church = \App\Models\Church::create([
            'name' => 'Test Church',
            'city' => 'Munich',
            'country' => 'Germany',
            'latitude' => 48.1351,
            'longitude' => 11.5820,
            'members' => 100,
            'continent' => 'europe',
            'is_active' => true,
        ]);

        \App\Models\SiteSetting::recalculateGlobalStats();
        $this->assertEquals(100, \App\Models\SiteSetting::getGlobalStats()['total_members']);

        // Update church to 250 members
        $this->actingAs($this->admin)->post(route('settings.homepage.churches.update', $church), [
            'name' => 'Test Church',
            'city' => 'Munich',
            'country' => 'Germany',
            'latitude' => 48.1351,
            'longitude' => 11.5820,
            'members' => 250,
            'is_active' => true,
        ]);

        $this->assertEquals(250, \App\Models\SiteSetting::getGlobalStats()['total_members']);
    }
}
