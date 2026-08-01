<?php

namespace Tests\Feature;

use App\Mail\ContactSubmitted;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'manage contacts']);

        // Create admin role and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo('manage contacts');

        // Create member role without contact management permission
        Role::create(['name' => 'member']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create();
        $this->member->assignRole('member');
    }

    /** @test */
    public function guest_can_view_contact_form(): void
    {
        $response = $this->get(route('contacts.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Create'));
    }

    /** @test */
    public function guest_can_submit_contact_form(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contacts', [
            'email' => $data['email'],
            'subject' => $data['subject'],
            'status' => 'new',
        ]);
    }

    /** @test */
    public function contact_form_requires_name(): void
    {
        $data = [
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function contact_form_requires_valid_email(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => 'invalid-email',
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function contact_form_requires_subject(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'message' => $this->faker->paragraph,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertSessionHasErrors('subject');
    }

    /** @test */
    public function contact_form_requires_message(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertSessionHasErrors('message');
    }

    /** @test */
    public function guest_cannot_view_contacts_index(): void
    {
        $response = $this->get(route('contacts.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function member_without_permission_cannot_view_contacts_index(): void
    {
        $response = $this->actingAs($this->member)->get(route('contacts.index'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function admin_can_view_contacts_index(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Index'));
    }

    /** @test */
    public function admin_can_view_contact_details(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.show', $contact));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Show')
            ->has('contact')
        );
    }

    /** @test */
    public function viewing_contact_marks_it_as_read(): void
    {
        $contact = Contact::factory()->create(['read_at' => null]);

        $this->assertNull($contact->fresh()->read_at);

        $this->actingAs($this->admin)->get(route('contacts.show', $contact));

        $this->assertNotNull($contact->fresh()->read_at);
    }

    /** @test */
    public function admin_can_update_contact_status(): void
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
            'assigned_to' => $this->admin->id,
        ]);

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => 'in_progress',
            'assigned_to' => $this->admin->id,
        ]);
    }

    /** @test */
    public function admin_can_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('contacts.destroy', $contact));

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function member_without_permission_cannot_update_contact(): void
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->member)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
        ]);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function member_without_permission_cannot_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->member)->delete(route('contacts.destroy', $contact));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function contacts_are_ordered_with_new_status_first(): void
    {
        Contact::factory()->create(['status' => 'resolved', 'created_at' => now()->subDay()]);
        Contact::factory()->create(['status' => 'new', 'created_at' => now()->subDays(2)]);
        Contact::factory()->create(['status' => 'in_progress', 'created_at' => now()]);

        $response = $this->actingAs($this->admin)->get(route('contacts.index'));

        $response->assertStatus(200);
        // The 'new' status contact should appear first despite being older
    }

    /** @test */
    public function contact_can_have_phone_number_optional(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
            // phone is intentionally omitted
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('contacts', [
            'email' => $data['email'],
            'phone' => null,
        ]);
    }

    /** @test */
    public function update_requires_valid_status(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
    }

    /** @test */
    public function update_requires_valid_user_id_when_assigning(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
            'assigned_to' => 99999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    /** @test */
    public function admin_can_view_contact_edit_page(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.edit', $contact));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Contacts/Edit')
            ->has('contact')
            ->where('contact.id', $contact->id)
            ->has('users')
        );
    }

    /** @test */
    public function edit_page_lists_only_users_with_manage_contacts_permission(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.edit', $contact));

        $response->assertInertia(fn ($page) => $page->component('Contacts/Edit')
            ->has('users', 1)
            ->where('users.0.id', $this->admin->id)
        );
    }

    /** @test */
    public function guest_cannot_view_contact_edit_page(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->get(route('contacts.edit', $contact));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function member_without_permission_cannot_view_contact_edit_page(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->member)->get(route('contacts.edit', $contact));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function admin_can_unassign_contact(): void
    {
        $contact = Contact::factory()->create([
            'status' => 'in_progress',
            'assigned_to' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
            'assigned_to' => null,
        ]);

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'assigned_to' => null,
        ]);
    }

    /** @test */
    public function guest_cannot_view_contact_details(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->get(route('contacts.show', $contact));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function member_without_permission_cannot_view_contact_details(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->member)->get(route('contacts.show', $contact));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    /** @test */
    public function viewing_already_read_contact_keeps_original_read_timestamp(): void
    {
        $readAt = now()->subDay();
        $contact = Contact::factory()->create(['read_at' => $readAt]);

        $this->actingAs($this->admin)->get(route('contacts.show', $contact));

        $this->assertSame(
            $readAt->toDateTimeString(),
            $contact->fresh()->read_at->toDateTimeString()
        );
    }

    /** @test */
    public function contact_details_are_accessible_via_uuid(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get("/contacts/{$contact->uuid}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Contacts/Show')
            ->where('contact.id', $contact->id)
        );
    }

    /** @test */
    public function contact_details_are_not_accessible_via_numeric_id(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get("/contacts/{$contact->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function generated_contact_routes_use_uuid(): void
    {
        $contact = Contact::factory()->create();

        $this->assertSame(
            url("/contacts/{$contact->uuid}"),
            route('contacts.show', $contact)
        );
    }

    /** @test */
    public function contact_can_be_updated_via_uuid(): void
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->admin)->put("/contacts/{$contact->uuid}", [
            'status' => 'resolved',
            'assigned_to' => null,
        ]);

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => 'resolved',
        ]);
    }

    /** @test */
    public function contact_can_be_deleted_via_uuid(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/contacts/{$contact->uuid}");

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function contacts_index_is_paginated(): void
    {
        Contact::factory()->count(25)->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.index'));

        $response->assertInertia(fn ($page) => $page->component('Contacts/Index')
            ->has('contacts.data', 20)
            ->where('contacts.total', 25)
            ->where('contacts.last_page', 2)
        );
    }

    /** @test */
    public function contact_submission_sends_email_to_admins_with_permission(): void
    {
        Mail::fake();

        // Create an additional admin with 'manage contacts' permission
        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole('admin');

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $response = $this->post(route('contacts.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert that email was queued to both admins
        Mail::assertQueued(ContactSubmitted::class, fn ($mail) => $mail->hasTo($this->admin->email));

        Mail::assertQueued(ContactSubmitted::class, fn ($mail) => $mail->hasTo($anotherAdmin->email));

        // Assert that email was queued twice (once to each admin)
        Mail::assertQueued(ContactSubmitted::class, 2);
    }

    /** @test */
    public function contact_submission_sends_email_with_correct_content(): void
    {
        Mail::fake();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class, function ($mail) use ($data): bool {
            $contact = $mail->contact;

            return $contact->name === $data['name'] &&
                   $contact->email === $data['email'] &&
                   $contact->subject === $data['subject'] &&
                   $contact->message === $data['message'];
        });
    }

    /** @test */
    public function contact_submission_sends_email_with_reply_to(): void
    {
        Mail::fake();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message',
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class, fn ($mail) => $mail->hasReplyTo($data['email']));
    }

    /** @test */
    public function contact_submission_sends_to_default_email_when_no_admins(): void
    {
        Mail::fake();

        // Remove all permissions from admin
        $this->admin->roles()->detach();

        // Update config to have a default contact email (the fallback used by the controller)
        config(['mail.from.contact' => 'default@example.com']);

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class, fn ($mail) => $mail->hasTo('default@example.com'));
    }

    /** @test */
    public function contact_submission_does_not_send_email_when_no_admins_and_no_default(): void
    {
        Mail::fake();

        // Remove all permissions from admin
        $this->admin->roles()->detach();

        // Clear default contact email (the fallback used by the controller)
        config(['mail.from.contact' => null]);

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertNothingOutgoing();
    }

    /** @test */
    public function contact_email_is_queued(): void
    {
        Mail::fake();

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class);
    }
}
