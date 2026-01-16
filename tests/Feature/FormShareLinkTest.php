<?php

namespace Tests\Feature;

use App\Enums\Form\FormStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormShareLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $adminUser;
    private Department $department;
    private DepartmentForm $publishedForm;
    private DepartmentForm $draftForm;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Create users
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Create department
        $this->department = Department::factory()->create();

        // Create forms
        $this->publishedForm = DepartmentForm::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => FormStatus::PUBLISHED,
        ]);

        $this->draftForm = DepartmentForm::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => FormStatus::DRAFT,
        ]);
    }

    /** @test */
    public function it_generates_secure_token_with_correct_length(): void
    {
        $token = FormShareLink::generateToken();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    /** @test */
    public function it_generates_unique_tokens(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = FormShareLink::generateToken();
        }

        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);
    }

    /** @test */
    public function it_creates_share_link_for_form(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            24,
            100
        );

        $this->assertDatabaseHas('form_share_links', [
            'id' => $shareLink->id,
            'form_id' => $this->publishedForm->id,
            'created_by' => $this->user->id,
            'max_uses' => 100,
            'use_count' => 0,
            'is_active' => true,
        ]);

        $this->assertEquals(64, strlen($shareLink->token));
        $this->assertTrue($shareLink->expires_at->isFuture());
    }

    /** @test */
    public function it_creates_share_link_with_default_expiration(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        // Default is 24 hours
        $expectedExpiry = now()->addHours(24);
        $this->assertTrue(
            $shareLink->expires_at->diffInMinutes($expectedExpiry) < 1
        );
    }

    /** @test */
    public function it_finds_valid_share_link_by_token(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $found = FormShareLink::findValidByToken($shareLink->token);

        $this->assertNotNull($found);
        $this->assertEquals($shareLink->id, $found->id);
    }

    /** @test */
    public function it_returns_null_for_invalid_token(): void
    {
        $found = FormShareLink::findValidByToken('invalid_token_that_does_not_exist');

        $this->assertNull($found);
    }

    /** @test */
    public function it_returns_null_for_expired_token(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            1 // 1 hour
        );

        // Manually expire the link
        $shareLink->update(['expires_at' => now()->subHour()]);

        $found = FormShareLink::findValidByToken($shareLink->token);

        $this->assertNull($found);
    }

    /** @test */
    public function it_returns_null_for_deactivated_link(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $shareLink->deactivate();

        $found = FormShareLink::findValidByToken($shareLink->token);

        $this->assertNull($found);
    }

    /** @test */
    public function it_returns_null_when_max_uses_exceeded(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            24,
            5 // Max 5 uses
        );

        // Simulate 5 uses
        $shareLink->update(['use_count' => 5]);

        $found = FormShareLink::findValidByToken($shareLink->token);

        $this->assertNull($found);
    }

    /** @test */
    public function it_allows_unlimited_uses_when_max_uses_is_null(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            24,
            null // Unlimited
        );

        // Simulate many uses
        $shareLink->update(['use_count' => 10000]);

        $found = FormShareLink::findValidByToken($shareLink->token);

        $this->assertNotNull($found);
    }

    /** @test */
    public function is_valid_returns_true_for_valid_link(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertTrue($shareLink->isValid());
    }

    /** @test */
    public function is_valid_returns_false_for_expired_link(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $shareLink->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($shareLink->isValid());
    }

    /** @test */
    public function is_valid_returns_false_for_deactivated_link(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $shareLink->update(['is_active' => false]);

        $this->assertFalse($shareLink->isValid());
    }

    /** @test */
    public function is_valid_returns_false_when_max_uses_reached(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            24,
            3
        );

        $shareLink->update(['use_count' => 3]);

        $this->assertFalse($shareLink->isValid());
    }

    /** @test */
    public function it_increments_use_count(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertEquals(0, $shareLink->use_count);

        $shareLink->incrementUseCount();
        $shareLink->refresh();

        $this->assertEquals(1, $shareLink->use_count);

        $shareLink->incrementUseCount();
        $shareLink->incrementUseCount();
        $shareLink->refresh();

        $this->assertEquals(3, $shareLink->use_count);
    }

    /** @test */
    public function it_deactivates_link(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertTrue($shareLink->is_active);

        $shareLink->deactivate();
        $shareLink->refresh();

        $this->assertFalse($shareLink->is_active);
    }

    /** @test */
    public function it_generates_correct_url(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $url = $shareLink->getUrl();

        $this->assertStringContainsString('/f/', $url);
        $this->assertStringContainsString($shareLink->token, $url);
    }

    /** @test */
    public function it_has_form_relationship(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertInstanceOf(DepartmentForm::class, $shareLink->form);
        $this->assertEquals($this->publishedForm->id, $shareLink->form->id);
    }

    /** @test */
    public function it_has_creator_relationship(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertInstanceOf(User::class, $shareLink->creator);
        $this->assertEquals($this->user->id, $shareLink->creator->id);
    }

    /** @test */
    public function authenticated_user_can_generate_share_link_for_published_form(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid), [
                'expires_in_hours' => 48,
                'max_uses' => 50,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'url',
            'token',
            'expires_at',
            'max_uses',
        ]);

        $this->assertDatabaseHas('form_share_links', [
            'form_id' => $this->publishedForm->id,
            'created_by' => $this->user->id,
            'max_uses' => 50,
        ]);
    }

    /** @test */
    public function user_cannot_generate_share_link_for_draft_form(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->draftForm->uuid));

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'error' => 'Seuls les formulaires publiés peuvent être partagés.',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_generate_share_link(): void
    {
        $response = $this->postJson(route('forms.generate-share-link', $this->publishedForm->uuid));

        $response->assertUnauthorized();
    }

    /** @test */
    public function share_link_uses_default_expiration_when_not_specified(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid));

        $response->assertOk();

        $shareLink = FormShareLink::where('form_id', $this->publishedForm->id)->first();
        $expectedExpiry = now()->addHours(24);

        $this->assertTrue(
            $shareLink->expires_at->diffInMinutes($expectedExpiry) < 1
        );
    }

    /** @test */
    public function valid_share_link_renders_form(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Render')
            ->has('form')
            ->has('fields')
            ->where('sharedToken', $shareLink->token)
        );
    }

    /** @test */
    public function accessing_shared_form_increments_use_count(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertEquals(0, $shareLink->use_count);

        $this->get(route('forms.shared', $shareLink->token));

        $shareLink->refresh();
        $this->assertEquals(1, $shareLink->use_count);
    }

    /** @test */
    public function expired_share_link_shows_expired_page(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $shareLink->update(['expires_at' => now()->subMinute()]);

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/SharedExpired')
            ->has('message')
        );
    }

    /** @test */
    public function invalid_token_shows_expired_page(): void
    {
        $response = $this->get(route('forms.shared', 'invalid_token_12345'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/SharedExpired')
        );
    }

    /** @test */
    public function deactivated_share_link_shows_expired_page(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $shareLink->deactivate();

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/SharedExpired')
        );
    }

    /** @test */
    public function max_uses_exceeded_shows_expired_page(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id,
            24,
            1
        );

        $shareLink->update(['use_count' => 1]);

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/SharedExpired')
        );
    }

    /** @test */
    public function unpublished_form_via_valid_link_shows_unavailable(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        // Unpublish the form after creating share link
        $this->publishedForm->update(['status' => FormStatus::DRAFT]);

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/SharedExpired')
            ->where('message', 'Ce formulaire n\'est plus disponible.')
        );
    }

    /** @test */
    public function share_link_token_does_not_expose_form_uuid(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        // Token should not contain the UUID (which is a longer, distinct string)
        $this->assertStringNotContainsString(
            $this->publishedForm->uuid,
            $shareLink->token
        );

        // Token should be cryptographically random (hex string of 64 chars)
        // It should not be derived from or based on the form ID
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $shareLink->token);

        // Creating another share link should produce a different token
        $anotherShareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );
        $this->assertNotEquals($shareLink->token, $anotherShareLink->token);
    }

    /** @test */
    public function multiple_share_links_can_exist_for_same_form(): void
    {
        $link1 = FormShareLink::createForForm($this->publishedForm, $this->user->id);
        $link2 = FormShareLink::createForForm($this->publishedForm, $this->user->id);
        $link3 = FormShareLink::createForForm($this->publishedForm, $this->adminUser->id);

        $this->assertNotEquals($link1->token, $link2->token);
        $this->assertNotEquals($link2->token, $link3->token);

        $this->assertDatabaseCount('form_share_links', 3);
    }

    /** @test */
    public function share_link_validates_expiration_hours_range(): void
    {
        // Test minimum
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid), [
                'expires_in_hours' => 0,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expires_in_hours']);

        // Test maximum (over 1 year)
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid), [
                'expires_in_hours' => 9000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expires_in_hours']);
    }

    /** @test */
    public function share_link_validates_max_uses_range(): void
    {
        // Test minimum
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid), [
                'max_uses' => 0,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['max_uses']);

        // Test maximum
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.generate-share-link', $this->publishedForm->uuid), [
                'max_uses' => 20000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['max_uses']);
    }

    /** @test */
    public function shared_form_can_be_accessed_without_authentication(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        // Ensure we're not authenticated
        $this->assertGuest();

        $response = $this->get(route('forms.shared', $shareLink->token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Forms/Render'));
    }

    /** @test */
    public function deleting_form_cascades_to_share_links(): void
    {
        $shareLink = FormShareLink::createForForm(
            $this->publishedForm,
            $this->user->id
        );

        $this->assertDatabaseHas('form_share_links', ['id' => $shareLink->id]);

        $this->publishedForm->delete();

        $this->assertDatabaseMissing('form_share_links', ['id' => $shareLink->id]);
    }
}
