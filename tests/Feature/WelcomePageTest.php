<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\HomepageSubsection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function welcome_page_loads_with_no_sections_in_db_and_uses_fallback(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('hasConfiguredSections', false)
                ->has('sections', 0)
        );
    }

    /** @test */
    public function welcome_page_shows_only_active_sections(): void
    {
        $active1 = HomepageSection::factory()->ofType('about')->create([
            'is_active' => true,
            'order' => 1,
        ]);
        $active2 = HomepageSection::factory()->ofType('contact')->create([
            'is_active' => true,
            'order' => 2,
        ]);
        HomepageSection::factory()->ofType('activities')->create([
            'is_active' => false,
            'order' => 3,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('hasConfiguredSections', true)
                ->has('sections', 2)
                ->where('sections.0.id', $active1->id)
                ->where('sections.1.id', $active2->id)
        );
    }

    /** @test */
    public function welcome_page_hides_all_sections_when_all_are_inactive_and_does_not_fallback(): void
    {
        HomepageSection::factory()->ofType('about')->create(['is_active' => false, 'order' => 1]);
        HomepageSection::factory()->ofType('activities')->create(['is_active' => false, 'order' => 2]);
        HomepageSection::factory()->ofType('training')->create(['is_active' => false, 'order' => 3]);
        HomepageSection::factory()->ofType('contact')->create(['is_active' => false, 'order' => 4]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('hasConfiguredSections', true)
                ->has('sections', 0)
        );
    }

    /** @test */
    public function welcome_page_no_longer_shows_deleted_section(): void
    {
        $a = HomepageSection::factory()->ofType('about')->create(['is_active' => true, 'order' => 1]);
        $b = HomepageSection::factory()->ofType('contact')->create(['is_active' => true, 'order' => 2]);

        $b->delete();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('hasConfiguredSections', true)
                ->has('sections', 1)
                ->where('sections.0.id', $a->id)
        );
    }

    /** @test */
    public function welcome_page_returns_sections_in_order(): void
    {
        $third = HomepageSection::factory()->ofType('about')->create(['is_active' => true, 'order' => 30]);
        $first = HomepageSection::factory()->ofType('activities')->create(['is_active' => true, 'order' => 10]);
        $second = HomepageSection::factory()->ofType('contact')->create(['is_active' => true, 'order' => 20]);

        $response = $this->get('/');

        $response->assertInertia(
            fn ($page) => $page
                ->has('sections', 3)
                ->where('sections.0.id', $first->id)
                ->where('sections.1.id', $second->id)
                ->where('sections.2.id', $third->id)
        );
    }

    /** @test */
    public function welcome_page_loads_subsections_only_when_active(): void
    {
        $section = HomepageSection::factory()->ofType('custom')->create(['is_active' => true, 'order' => 1]);
        HomepageSubsection::factory()->ofBlockType('heading')->create([
            'homepage_section_id' => $section->id,
            'order' => 1,
            'is_active' => true,
        ]);
        HomepageSubsection::factory()->ofBlockType('paragraph')->create([
            'homepage_section_id' => $section->id,
            'order' => 2,
            'is_active' => false,
        ]);
        HomepageSubsection::factory()->ofBlockType('button')->create([
            'homepage_section_id' => $section->id,
            'order' => 3,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertInertia(
            fn ($page) => $page
                ->has('sections', 1)
                ->has('sections.0.subsections', 2)
                ->where('sections.0.subsections.0.block_type', 'heading')
                ->where('sections.0.subsections.1.block_type', 'button')
        );
    }

    /** @test */
    public function welcome_page_excludes_subsections_of_inactive_parent(): void
    {
        $inactiveSection = HomepageSection::factory()->ofType('custom')->create([
            'is_active' => false,
            'order' => 1,
        ]);
        HomepageSubsection::factory()->ofBlockType('heading')->create([
            'homepage_section_id' => $inactiveSection->id,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertInertia(
            fn ($page) => $page
                ->where('hasConfiguredSections', true)
                ->has('sections', 0)
        );
    }

    /** @test */
    public function welcome_page_deleting_section_cascades_to_subsections(): void
    {
        $section = HomepageSection::factory()->ofType('custom')->create(['is_active' => true]);
        HomepageSubsection::factory()->ofBlockType('heading')->create(['homepage_section_id' => $section->id]);
        HomepageSubsection::factory()->ofBlockType('paragraph')->create(['homepage_section_id' => $section->id]);

        $this->assertDatabaseCount('homepage_subsections', 2);

        $section->delete();

        $this->assertDatabaseCount('homepage_sections', 0);
        $this->assertDatabaseCount('homepage_subsections', 0);

        $response = $this->get('/');
        $response->assertInertia(
            fn ($page) => $page
                ->where('hasConfiguredSections', false)
                ->has('sections', 0)
        );
    }
}
