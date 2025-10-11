<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
    }

    public function test_index_displays_programs(): void
    {
        $programs = Program::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->user->givePermissionTo('view programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Index')
            ->has('programs.data', 3)
        );
    }

    public function test_index_can_filter_by_status(): void
    {
        Program::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        Program::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $this->user->givePermissionTo('view programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Index')
            ->has('programs.data', 2)
        );
    }

    public function test_create_displays_form(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.create'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Create')
            ->has('users')
        );
    }

    public function test_store_creates_program(): void
    {
        $this->user->givePermissionTo('create programs');

        $programData = [
            'name' => 'Test Program',
            'description' => 'Program description',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'budget' => '50000.00',
            'status' => 'planning',
            'priority' => 'high',
            'progress_percentage' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), $programData);

        $response->assertRedirect(route('programs.index'));
        $response->assertSessionHas('success', 'Program created successfully.');

        $this->assertDatabaseHas('programs', [
            'name' => 'Test Program',
            'description' => 'Program description',
            'status' => 'planning',
            'priority' => 'high',
            'budget' => 50000.00,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), []);

        $response->assertSessionHasErrors(['name', 'start_date', 'end_date', 'status', 'priority']);
    }

    public function test_store_validates_end_date_after_start_date(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), [
                'name' => 'Test Program',
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'status' => 'planning',
                'priority' => 'medium',
            ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_store_validates_status_values(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), [
                'name' => 'Test Program',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addWeek()->format('Y-m-d'),
                'status' => 'invalid_status',
                'priority' => 'medium',
            ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validates_priority_values(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), [
                'name' => 'Test Program',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addWeek()->format('Y-m-d'),
                'status' => 'planning',
                'priority' => 'invalid_priority',
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    public function test_store_validates_progress_percentage(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), [
                'name' => 'Test Program',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addWeek()->format('Y-m-d'),
                'status' => 'planning',
                'priority' => 'medium',
                'progress_percentage' => 150,
            ]);

        $response->assertSessionHasErrors(['progress_percentage']);
    }

    public function test_show_displays_program(): void
    {
        $program = Program::factory()->create(['user_id' => $this->user->id]);

        $this->user->givePermissionTo('view programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Show')
            ->where('program.id', $program->id)
            ->where('program.name', $program->name)
        );
    }

    public function test_edit_displays_form(): void
    {
        $program = Program::factory()->create(['user_id' => $this->user->id]);

        $this->user->givePermissionTo('edit programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.edit', $program));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Edit')
            ->where('program.id', $program->id)
            ->has('users')
        );
    }

    public function test_update_modifies_program(): void
    {
        $program = Program::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
            'status' => 'planning',
        ]);

        $this->user->givePermissionTo('edit programs');

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'budget' => '75000.00',
            'status' => 'active',
            'priority' => 'high',
            'progress_percentage' => 25,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('programs.update', $program), $updateData);

        $response->assertRedirect(route('programs.index'));
        $response->assertSessionHas('success', 'Program updated successfully.');

        $program->refresh();
        $this->assertEquals('Updated Name', $program->name);
        $this->assertEquals('active', $program->status);
        $this->assertEquals('high', $program->priority);
        $this->assertEquals(25, $program->progress_percentage);
        $this->assertEquals(75000.00, (float) $program->budget);
    }

    public function test_destroy_deletes_program(): void
    {
        $program = Program::factory()->create(['user_id' => $this->user->id]);

        $this->user->givePermissionTo('delete programs');

        $response = $this->actingAs($this->user)
            ->delete(route('programs.destroy', $program));

        $response->assertRedirect(route('programs.index'));
        $response->assertSessionHas('success', 'Program deleted successfully.');

        $this->assertSoftDeleted($program);
    }

    public function test_unauthorized_user_cannot_access_programs(): void
    {
        $program = Program::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('programs.index'));

        $response->assertForbidden();
    }

    public function test_user_can_filter_by_priority(): void
    {
        Program::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
        ]);

        Program::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
        ]);

        $this->user->givePermissionTo('view programs');

        $response = $this->actingAs($this->user)
            ->get(route('programs.index', ['priority' => 'high']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Programs/Index')
            ->has('programs.data', 2)
        );
    }

    public function test_budget_validation(): void
    {
        $this->user->givePermissionTo('create programs');

        $response = $this->actingAs($this->user)
            ->post(route('programs.store'), [
                'name' => 'Test Program',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addWeek()->format('Y-m-d'),
                'status' => 'planning',
                'priority' => 'medium',
                'budget' => -1000,
            ]);

        $response->assertSessionHasErrors(['budget']);
    }
}
