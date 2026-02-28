<?php

use App\Models\Event;
use App\Models\Event\EventProgramme;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Permission::firstOrCreate(['name' => 'view events']);
    Permission::firstOrCreate(['name' => 'create events']);
    Permission::firstOrCreate(['name' => 'edit events']);
    Permission::firstOrCreate(['name' => 'delete events']);

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->syncPermissions(['view events', 'create events', 'edit events', 'delete events']);

    $memberRole = Role::firstOrCreate(['name' => 'member']);
    $memberRole->syncPermissions(['view events']);
});

// ===== Model Tests =====

describe('EventProgramme Model', function () {
    it('can be created with required fields', function () {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $programme = EventProgramme::create([
            'event_id' => $event->id,
            'uploaded_by' => $user->id,
            'file_path' => 'events/programmes/test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        expect($programme)->toBeInstanceOf(EventProgramme::class);
        expect($programme->file_name)->toBe('test.pdf');
        expect($programme->is_active)->toBeTrue();
    });

    it('belongs to an event', function () {
        $event = Event::factory()->create();
        $programme = EventProgramme::factory()->create(['event_id' => $event->id]);

        expect($programme->event)->toBeInstanceOf(Event::class);
        expect($programme->event->id)->toBe($event->id);
    });

    it('belongs to an uploader', function () {
        $user = User::factory()->create();
        $programme = EventProgramme::factory()->create(['uploaded_by' => $user->id]);

        expect($programme->uploader)->toBeInstanceOf(User::class);
        expect($programme->uploader->id)->toBe($user->id);
    });

    it('can generate a share token', function () {
        $programme = EventProgramme::factory()->create();

        $programme->generateShareToken(24);

        expect($programme->share_token)->not->toBeNull();
        expect(strlen($programme->share_token))->toBe(64);
        expect($programme->share_token_expires_at)->not->toBeNull();
        expect($programme->share_token_expires_at->isFuture())->toBeTrue();
    });

    it('can renew a share token', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();
        $originalToken = $programme->share_token;

        $programme->renewShareToken(24);

        expect($programme->share_token)->toBe($originalToken);
        expect($programme->share_token_expires_at->isFuture())->toBeTrue();
    });

    it('generates a new token when renewing without existing token', function () {
        $programme = EventProgramme::factory()->create();

        $programme->renewShareToken(24);

        expect($programme->share_token)->not->toBeNull();
        expect(strlen($programme->share_token))->toBe(64);
    });

    it('can revoke a share token', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();

        $programme->revokeShareToken();

        expect($programme->share_token)->toBeNull();
        expect($programme->share_token_expires_at)->toBeNull();
    });

    it('validates share token correctly', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();
        expect($programme->isShareTokenValid())->toBeTrue();

        $expired = EventProgramme::factory()->withExpiredToken()->create();
        expect($expired->isShareTokenValid())->toBeFalse();

        $noToken = EventProgramme::factory()->create();
        expect($noToken->isShareTokenValid())->toBeFalse();

        $inactive = EventProgramme::factory()->withShareToken()->create(['is_active' => false]);
        expect($inactive->isShareTokenValid())->toBeFalse();
    });

    it('finds programme by valid token', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();

        $found = EventProgramme::findByValidToken($programme->share_token);

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($programme->id);
    });

    it('does not find programme with expired token', function () {
        $programme = EventProgramme::factory()->withExpiredToken()->create();

        $found = EventProgramme::findByValidToken($programme->share_token);

        expect($found)->toBeNull();
    });

    it('does not find programme with inactive status', function () {
        $programme = EventProgramme::factory()->withShareToken()->create(['is_active' => false]);

        $found = EventProgramme::findByValidToken($programme->share_token);

        expect($found)->toBeNull();
    });

    it('detects PDF files correctly', function () {
        $pdf = EventProgramme::factory()->create(['file_type' => 'application/pdf']);
        expect($pdf->is_pdf)->toBeTrue();
        expect($pdf->is_image)->toBeFalse();

        $jpg = EventProgramme::factory()->image()->create();
        expect($jpg->is_pdf)->toBeFalse();
        expect($jpg->is_image)->toBeTrue();
    });

    it('computes file size for humans', function () {
        $small = EventProgramme::factory()->create(['file_size' => 512]);
        expect($small->file_size_for_humans)->toBe('512 B');

        $kb = EventProgramme::factory()->create(['file_size' => 2048]);
        expect($kb->file_size_for_humans)->toBe('2 KB');

        $mb = EventProgramme::factory()->create(['file_size' => 5242880]);
        expect($mb->file_size_for_humans)->toBe('5 MB');
    });
});

// ===== Upload Tests =====

describe('Programme Upload', function () {
    it('requires authentication to upload', function () {
        $event = Event::factory()->create();

        $this->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.pdf', 1024, 'application/pdf'),
        ])->assertUnauthorized();
    });

    it('requires edit events permission', function () {
        $user = User::factory()->create();
        $user->assignRole('member');
        $event = Event::factory()->create();

        $this->actingAs($user)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.pdf', 1024, 'application/pdf'),
        ])->assertForbidden();
    });

    it('allows admin to upload a PDF programme', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.pdf', 1024, 'application/pdf'),
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'programme']);

        $this->assertDatabaseHas('event_programmes', [
            'event_id' => $event->id,
            'uploaded_by' => $admin->id,
            'file_type' => 'application/pdf',
        ]);
    });

    it('allows admin to upload an image programme', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->image('programme.jpg', 800, 600),
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('event_programmes', [
            'event_id' => $event->id,
            'file_type' => 'image/jpeg',
        ]);
    });

    it('rejects unsupported file types', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.zip', 1024, 'application/zip'),
        ])->assertUnprocessable();
    });

    it('rejects files exceeding 50MB', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.pdf', 52000, 'application/pdf'),
        ])->assertUnprocessable();
    });

    it('replaces previous programme when uploading a new one', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        // Upload first programme
        Storage::disk('public')->put('events/programmes/'.$event->id.'/old.pdf', 'content');
        EventProgramme::factory()->create([
            'event_id' => $event->id,
            'file_path' => 'events/programmes/'.$event->id.'/old.pdf',
        ]);

        // Upload replacement
        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('new-programme.pdf', 1024, 'application/pdf'),
        ])->assertCreated();

        // Only one programme should remain
        expect(EventProgramme::where('event_id', $event->id)->count())->toBe(1);
        Storage::disk('public')->assertMissing('events/programmes/'.$event->id.'/old.pdf');
    });

    it('allows event creator to upload programme', function () {
        $creator = User::factory()->create();
        $creator->assignRole('admin');
        $event = Event::factory()->create(['user_id' => $creator->id]);

        $this->actingAs($creator)->postJson("/events/{$event->uuid}/programme", [
            'file' => UploadedFile::fake()->create('programme.pdf', 1024, 'application/pdf'),
        ])->assertCreated();
    });
});

// ===== Delete Tests =====

describe('Programme Delete', function () {
    it('allows admin to delete a programme', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        Storage::disk('public')->put('events/programmes/test.pdf', 'content');
        EventProgramme::factory()->create([
            'event_id' => $event->id,
            'file_path' => 'events/programmes/test.pdf',
        ]);

        $this->actingAs($admin)->deleteJson("/events/{$event->uuid}/programme")
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('events/programmes/test.pdf');
    });

    it('returns 404 when no programme exists', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->deleteJson("/events/{$event->uuid}/programme")
            ->assertNotFound();
    });

    it('soft deletes the programme record', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $programme = EventProgramme::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->deleteJson("/events/{$event->uuid}/programme")
            ->assertSuccessful();

        expect(EventProgramme::withTrashed()->find($programme->id))->not->toBeNull();
        expect(EventProgramme::find($programme->id))->toBeNull();
    });
});

// ===== Share Link Tests =====

describe('Share Link', function () {
    it('generates a share link with token and QR code', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        EventProgramme::factory()->create(['event_id' => $event->id]);

        $this->mock(QrCodeService::class, function ($mock) {
            $mock->shouldReceive('generateBase64')
                ->once()
                ->andReturn('data:image/svg+xml;base64,FAKE_QR');
        });

        $response = $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme/share-link");

        $response->assertSuccessful();
        $response->assertJsonStructure(['url', 'token', 'expires_at', 'qr_code']);
        expect($response->json('qr_code'))->toBe('data:image/svg+xml;base64,FAKE_QR');
    });

    it('returns 422 when no programme exists', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme/share-link")
            ->assertUnprocessable();
    });

    it('renews existing share link for 24 hours', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        $programme = EventProgramme::factory()->withShareToken()->create([
            'event_id' => $event->id,
        ]);
        $originalToken = $programme->share_token;

        $response = $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme/renew-link");

        $response->assertSuccessful();
        $response->assertJsonStructure(['url', 'token', 'expires_at']);

        // Token should stay the same, expiration should be extended
        expect($response->json('token'))->toBe($originalToken);
    });

    it('returns 422 when renewing without share link', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        EventProgramme::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme/renew-link")
            ->assertUnprocessable();
    });

    it('revokes share link', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        EventProgramme::factory()->withShareToken()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->postJson("/events/{$event->uuid}/programme/revoke-link")
            ->assertSuccessful();

        $programme = EventProgramme::where('event_id', $event->id)->first();
        expect($programme->share_token)->toBeNull();
        expect($programme->share_token_expires_at)->toBeNull();
    });
});

// ===== Public Access Tests =====

describe('Public Programme Access', function () {
    it('renders shared programme page for valid token', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();

        $this->get("/p/{$programme->share_token}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Programme/SharedView')
                ->has('programme')
                ->has('eventTitle')
                ->has('downloadUrl')
            );
    });

    it('renders expired page for invalid token', function () {
        $this->get('/p/invalid_token_that_does_not_exist')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Programme/SharedExpired')
                ->has('message')
            );
    });

    it('renders expired page for expired token', function () {
        $programme = EventProgramme::factory()->withExpiredToken()->create();

        $this->get("/p/{$programme->share_token}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Programme/SharedExpired')
                ->has('message')
            );
    });

    it('allows download with valid token', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();
        Storage::disk('public')->put($programme->file_path, 'fake content');

        $this->get("/p/{$programme->share_token}/download")
            ->assertSuccessful();
    });

    it('rejects download with expired token', function () {
        $programme = EventProgramme::factory()->withExpiredToken()->create();

        $this->get("/p/{$programme->share_token}/download")
            ->assertForbidden();
    });

    it('does not require authentication for public access', function () {
        $programme = EventProgramme::factory()->withShareToken()->create();

        // No actingAs — unauthenticated request
        $this->get("/p/{$programme->share_token}")
            ->assertSuccessful();
    });
});

// ===== Tab Permission Tests =====

describe('Programme Tab Permissions', function () {
    it('shows canViewProgramme as true when programme exists', function () {
        Permission::firstOrCreate(['name' => 'view event gallery']);
        Permission::firstOrCreate(['name' => 'manage tickets']);
        Permission::firstOrCreate(['name' => 'view registrations']);
        Permission::firstOrCreate(['name' => 'manage registrations']);
        Permission::firstOrCreate(['name' => 'checkin events']);
        Permission::firstOrCreate(['name' => 'view event analytics']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        EventProgramme::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->get("/events/{$event->uuid}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->where('tabPermissions.canViewProgramme', true)
            );
    });

    it('shows canViewProgramme as false when no programme', function () {
        Permission::firstOrCreate(['name' => 'view event gallery']);
        Permission::firstOrCreate(['name' => 'manage tickets']);
        Permission::firstOrCreate(['name' => 'view registrations']);
        Permission::firstOrCreate(['name' => 'manage registrations']);
        Permission::firstOrCreate(['name' => 'checkin events']);
        Permission::firstOrCreate(['name' => 'view event analytics']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->get("/events/{$event->uuid}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->where('tabPermissions.canViewProgramme', false)
            );
    });

    it('passes programme data to show page', function () {
        Permission::firstOrCreate(['name' => 'view event gallery']);
        Permission::firstOrCreate(['name' => 'manage tickets']);
        Permission::firstOrCreate(['name' => 'view registrations']);
        Permission::firstOrCreate(['name' => 'manage registrations']);
        Permission::firstOrCreate(['name' => 'checkin events']);
        Permission::firstOrCreate(['name' => 'view event analytics']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();
        EventProgramme::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->get("/events/{$event->uuid}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->has('programme')
                ->where('programme.event_id', $event->id)
            );
    });
});
