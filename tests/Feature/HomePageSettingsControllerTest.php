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
}
