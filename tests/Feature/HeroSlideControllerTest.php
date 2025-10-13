<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HeroSlideControllerTest extends TestCase
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

    /** @test */
    public function guests_can_view_hero_slides_index()
    {
        $slides = HeroSlide::factory()->count(3)->create();

        $response = $this->get(route('hero-slides.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_hero_slides_index()
    {
        $slides = HeroSlide::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('hero-slides.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_create_hero_slide_form()
    {
        $response = $this->actingAs($this->admin)->get(route('hero-slides.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_view_create_hero_slide_form()
    {
        $response = $this->actingAs($this->user)->get(route('hero-slides.create'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function admin_can_create_hero_slide()
    {
        $data = [
            'title' => 'Test Slide',
            'description' => 'Test Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
            'cta_text' => 'Learn More',
            'cta_link' => '/about',
            'overlay_opacity' => 0.5,
            'order' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('hero-slides.store'), $data);

        $response->assertRedirect(route('hero-slides.index'));
        $this->assertDatabaseHas('hero_slides', [
            'title' => 'Test Slide',
            'description' => 'Test Description',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_hero_slide()
    {
        $data = [
            'title' => 'Test Slide',
            'description' => 'Test Description',
            'media_type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
        ];

        $response = $this->actingAs($this->user)->post(route('hero-slides.store'), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function validation_fails_when_required_fields_are_missing()
    {
        $response = $this->actingAs($this->admin)->post(route('hero-slides.store'), []);

        $response->assertSessionHasErrors(['title', 'description', 'media_type', 'media_url']);
    }

    /** @test */
    public function admin_can_view_hero_slide()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('hero-slides.show', $slide));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_edit_hero_slide_form()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('hero-slides.edit', $slide));

        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_view_edit_hero_slide_form()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->user)->get(route('hero-slides.edit', $slide));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function admin_can_update_hero_slide()
    {
        $slide = HeroSlide::factory()->create();

        $data = [
            'title' => 'Updated Slide',
            'description' => 'Updated Description',
            'media_type' => 'video',
            'media_url' => 'https://example.com/video.mp4',
            'cta_text' => 'Watch Now',
            'cta_link' => '/videos',
            'overlay_opacity' => 0.7,
            'order' => 2,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)->put(route('hero-slides.update', $slide), $data);

        $response->assertRedirect(route('hero-slides.index'));
        $this->assertDatabaseHas('hero_slides', [
            'id' => $slide->id,
            'title' => 'Updated Slide',
            'description' => 'Updated Description',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_hero_slide()
    {
        $slide = HeroSlide::factory()->create();

        $data = [
            'title' => 'Updated Slide',
            'description' => 'Updated Description',
            'media_type' => 'video',
            'media_url' => 'https://example.com/video.mp4',
        ];

        $response = $this->actingAs($this->user)->put(route('hero-slides.update', $slide), $data);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function admin_can_delete_hero_slide()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('hero-slides.destroy', $slide));

        $response->assertRedirect(route('hero-slides.index'));
        $this->assertDatabaseMissing('hero_slides', ['id' => $slide->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_hero_slide()
    {
        $slide = HeroSlide::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('hero-slides.destroy', $slide));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseHas('hero_slides', ['id' => $slide->id]);
    }

    /** @test */
    public function active_slides_only_returns_active_slides()
    {
        $activeSlide = HeroSlide::factory()->create(['is_active' => true, 'order' => 1]);
        $inactiveSlide = HeroSlide::factory()->create(['is_active' => false, 'order' => 2]);

        $activeSlides = HeroSlide::active()->get();

        $this->assertCount(1, $activeSlides);
        $this->assertEquals($activeSlide->id, $activeSlides->first()->id);
    }

    /** @test */
    public function active_slides_are_ordered_correctly()
    {
        $slide1 = HeroSlide::factory()->create(['is_active' => true, 'order' => 3]);
        $slide2 = HeroSlide::factory()->create(['is_active' => true, 'order' => 1]);
        $slide3 = HeroSlide::factory()->create(['is_active' => true, 'order' => 2]);

        $activeSlides = HeroSlide::active()->get();

        $this->assertEquals($slide2->id, $activeSlides[0]->id);
        $this->assertEquals($slide3->id, $activeSlides[1]->id);
        $this->assertEquals($slide1->id, $activeSlides[2]->id);
    }
}
