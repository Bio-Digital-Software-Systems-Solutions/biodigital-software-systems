<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\HomepageSubsection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomepageSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'manage hero slides']);
        Permission::create(['name' => 'manage homepage sections']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['manage hero slides', 'manage homepage sections']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
    }

    /** @test */
    public function admin_can_list_homepage_sections(): void
    {
        HomepageSection::factory()->ofType('about')->create(['order' => 1]);
        HomepageSection::factory()->ofType('contact')->create(['order' => 2]);

        $response = $this->actingAs($this->admin)->get(route('settings.homepage'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Settings/Homepage')
                ->has('sections', 2)
        );
    }

    /** @test */
    public function admin_can_create_section(): void
    {
        $data = [
            'type' => 'about',
            'title' => 'À propos',
            'content' => [
                'badge' => 'À propos',
                'heading' => 'Bienvenue',
            ],
            'design_settings' => [
                'font_family' => 'poppins',
                'heading_size' => 'xl',
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('settings.homepage.sections.store'), $data);

        $response->assertRedirect(route('settings.homepage'));
        $this->assertDatabaseCount('homepage_sections', 1);
        $section = HomepageSection::first();
        $this->assertSame('about', $section->type);
        $this->assertSame('Bienvenue', $section->content['heading']);
        $this->assertSame('poppins', $section->design_settings['font_family']);
        $this->assertTrue($section->is_active);
    }

    /** @test */
    public function admin_can_update_section_content_and_design(): void
    {
        $section = HomepageSection::factory()->ofType('contact')->create([
            'content' => ['heading' => 'Old'],
            'design_settings' => ['font_family' => 'inter'],
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('settings.homepage.sections.update', $section),
            [
                'type' => 'contact',
                'content' => ['heading' => 'New Heading', 'email' => 'hi@example.com'],
                'design_settings' => ['font_family' => 'playfair', 'overlay_opacity' => 0.4],
            ]
        );

        $response->assertRedirect(route('settings.homepage'));
        $section->refresh();
        $this->assertSame('New Heading', $section->content['heading']);
        $this->assertSame('hi@example.com', $section->content['email']);
        $this->assertSame('playfair', $section->design_settings['font_family']);
        $this->assertEquals(0.4, $section->design_settings['overlay_opacity']);
    }

    /** @test */
    public function admin_can_delete_section_and_subsections_cascade(): void
    {
        $section = HomepageSection::factory()->ofType('custom')->create();
        HomepageSubsection::factory()->ofBlockType('heading')->create(['homepage_section_id' => $section->id]);
        HomepageSubsection::factory()->ofBlockType('paragraph')->create(['homepage_section_id' => $section->id]);

        $this->assertDatabaseCount('homepage_subsections', 2);

        $this->actingAs($this->admin)
            ->delete(route('settings.homepage.sections.destroy', $section))
            ->assertRedirect(route('settings.homepage'));

        $this->assertDatabaseCount('homepage_sections', 0);
        $this->assertDatabaseCount('homepage_subsections', 0);
    }

    /** @test */
    public function admin_can_reorder_sections(): void
    {
        $a = HomepageSection::factory()->ofType('about')->create(['order' => 1]);
        $b = HomepageSection::factory()->ofType('activities')->create(['order' => 2]);
        $c = HomepageSection::factory()->ofType('contact')->create(['order' => 3]);

        $this->actingAs($this->admin)->post(route('settings.homepage.sections.reorder'), [
            'sections' => [
                ['id' => $a->id, 'order' => 3],
                ['id' => $b->id, 'order' => 1],
                ['id' => $c->id, 'order' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(3, $a->fresh()->order);
        $this->assertSame(1, $b->fresh()->order);
        $this->assertSame(2, $c->fresh()->order);
    }

    /** @test */
    public function regular_user_cannot_manage_sections(): void
    {
        $section = HomepageSection::factory()->ofType('about')->create();

        $store = $this->actingAs($this->user)->post(
            route('settings.homepage.sections.store'),
            ['type' => 'about']
        );
        $update = $this->actingAs($this->user)->put(
            route('settings.homepage.sections.update', $section),
            ['type' => 'about']
        );
        $destroy = $this->actingAs($this->user)->delete(
            route('settings.homepage.sections.destroy', $section)
        );
        $reorder = $this->actingAs($this->user)->post(
            route('settings.homepage.sections.reorder'),
            ['sections' => [['id' => $section->id, 'order' => 0]]]
        );

        foreach ([$store, $update, $destroy, $reorder] as $response) {
            $this->assertTrue(
                $response->isForbidden() || $response->isRedirect(),
                'Expected forbidden or redirect for unprivileged user'
            );
        }
        $this->assertDatabaseCount('homepage_sections', 1);
    }

    /** @test */
    public function section_validation_rejects_invalid_type_and_design(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.homepage.sections.store'), [
                'type' => 'invalid-type',
                'design_settings' => [
                    'font_family' => 'comic-sans',
                    'overlay_opacity' => 1.5,
                ],
            ]);

        $response->assertSessionHasErrors([
            'type',
            'design_settings.font_family',
            'design_settings.overlay_opacity',
        ]);
        $this->assertDatabaseCount('homepage_sections', 0);
    }

    /** @test */
    public function admin_can_crud_subsections_and_reorder(): void
    {
        $section = HomepageSection::factory()->ofType('custom')->create();

        $this->actingAs($this->admin)->post(
            route('settings.homepage.sections.subsections.store', $section),
            [
                'block_type' => 'heading',
                'content' => ['text' => 'Hello', 'level' => 2],
            ]
        )->assertRedirect();

        $this->actingAs($this->admin)->post(
            route('settings.homepage.sections.subsections.store', $section),
            [
                'block_type' => 'paragraph',
                'content' => ['text' => 'A paragraph.'],
            ]
        )->assertRedirect();

        $this->assertDatabaseCount('homepage_subsections', 2);

        $heading = HomepageSubsection::where('block_type', 'heading')->firstOrFail();
        $paragraph = HomepageSubsection::where('block_type', 'paragraph')->firstOrFail();

        $this->actingAs($this->admin)->put(
            route('settings.homepage.sections.subsections.update', [$section, $heading]),
            [
                'block_type' => 'heading',
                'content' => ['text' => 'Updated', 'level' => 1],
            ]
        )->assertRedirect();
        $this->assertSame('Updated', $heading->fresh()->content['text']);

        $this->actingAs($this->admin)->post(
            route('settings.homepage.sections.subsections.reorder', $section),
            [
                'subsections' => [
                    ['id' => $heading->id, 'order' => 5],
                    ['id' => $paragraph->id, 'order' => 1],
                ],
            ]
        )->assertRedirect();
        $this->assertSame(5, $heading->fresh()->order);
        $this->assertSame(1, $paragraph->fresh()->order);

        $this->actingAs($this->admin)->delete(
            route('settings.homepage.sections.subsections.destroy', [$section, $paragraph])
        )->assertRedirect();
        $this->assertDatabaseCount('homepage_subsections', 1);
    }
}
