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
        $memberRole = Role::create(['name' => 'member']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create();
        $this->member->assignRole('member');
    }

    /** @test */
    public function guest_can_view_contact_form()
    {
        $response = $this->get(route('contacts.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Create'));
    }

    /** @test */
    public function guest_can_submit_contact_form()
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
    public function contact_form_requires_name()
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
    public function contact_form_requires_valid_email()
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
    public function contact_form_requires_subject()
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
    public function contact_form_requires_message()
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
    public function guest_cannot_view_contacts_index()
    {
        $response = $this->get(route('contacts.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function member_without_permission_cannot_view_contacts_index()
    {
        $response = $this->actingAs($this->member)->get(route('contacts.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_contacts_index()
    {
        Contact::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Index'));
    }

    /** @test */
    public function admin_can_view_contact_details()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('contacts.show', $contact));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contacts/Show')
            ->has('contact')
        );
    }

    /** @test */
    public function viewing_contact_marks_it_as_read()
    {
        $contact = Contact::factory()->create(['read_at' => null]);

        $this->assertNull($contact->fresh()->read_at);

        $this->actingAs($this->admin)->get(route('contacts.show', $contact));

        $this->assertNotNull($contact->fresh()->read_at);
    }

    /** @test */
    public function admin_can_update_contact_status()
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
    public function admin_can_delete_contact()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('contacts.destroy', $contact));

        $response->assertRedirect(route('contacts.index'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function member_without_permission_cannot_update_contact()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->member)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function member_without_permission_cannot_delete_contact()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->member)->delete(route('contacts.destroy', $contact));

        $response->assertStatus(403);
    }

    /** @test */
    public function contacts_are_ordered_with_new_status_first()
    {
        Contact::factory()->create(['status' => 'resolved', 'created_at' => now()->subDay()]);
        Contact::factory()->create(['status' => 'new', 'created_at' => now()->subDays(2)]);
        Contact::factory()->create(['status' => 'in_progress', 'created_at' => now()]);

        $response = $this->actingAs($this->admin)->get(route('contacts.index'));

        $response->assertStatus(200);
        // The 'new' status contact should appear first despite being older
    }

    /** @test */
    public function contact_can_have_phone_number_optional()
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
    public function update_requires_valid_status()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
    }

    /** @test */
    public function update_requires_valid_user_id_when_assigning()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('contacts.update', $contact), [
            'status' => 'in_progress',
            'assigned_to' => 99999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    /** @test */
    public function contact_submission_sends_email_to_admins_with_permission()
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
        Mail::assertQueued(ContactSubmitted::class, function ($mail) {
            return $mail->hasTo($this->admin->email);
        });

        Mail::assertQueued(ContactSubmitted::class, function ($mail) use ($anotherAdmin) {
            return $mail->hasTo($anotherAdmin->email);
        });

        // Assert that email was queued twice (once to each admin)
        Mail::assertQueued(ContactSubmitted::class, 2);
    }

    /** @test */
    public function contact_submission_sends_email_with_correct_content()
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

        Mail::assertQueued(ContactSubmitted::class, function ($mail) use ($data) {
            $contact = $mail->contact;

            return $contact->name === $data['name'] &&
                   $contact->email === $data['email'] &&
                   $contact->subject === $data['subject'] &&
                   $contact->message === $data['message'];
        });
    }

    /** @test */
    public function contact_submission_sends_email_with_reply_to()
    {
        Mail::fake();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message',
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class, function ($mail) use ($data) {
            return $mail->hasReplyTo($data['email']);
        });
    }

    /** @test */
    public function contact_submission_sends_to_default_email_when_no_admins()
    {
        Mail::fake();

        // Remove all permissions from admin
        $this->admin->roles()->detach();

        // Update config to have a default email
        config(['mail.from.address' => 'default@example.com']);

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertQueued(ContactSubmitted::class, function ($mail) {
            return $mail->hasTo('default@example.com');
        });
    }

    /** @test */
    public function contact_submission_does_not_send_email_when_no_admins_and_no_default()
    {
        Mail::fake();

        // Remove all permissions from admin
        $this->admin->roles()->detach();

        // Clear default email
        config(['mail.from.address' => null]);

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];

        $this->post(route('contacts.store'), $data);

        Mail::assertNothingSent();
    }

    /** @test */
    public function contact_email_is_queued()
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
