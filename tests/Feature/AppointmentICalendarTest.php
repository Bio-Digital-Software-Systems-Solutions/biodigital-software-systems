<?php

use App\Models\Appointment;
use App\Models\User;
use App\Services\ICalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\CreatesPermissions;

uses(RefreshDatabase::class);
uses(CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();
    Permission::firstOrCreate(['name' => 'view appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage appointment participants', 'guard_name' => 'web']);
});

// === Export Single ===

it('can export a single appointment as ics', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view appointments');

    $appointment = Appointment::factory()->confirmed()->create([
        'title' => 'Test Meeting',
        'description' => 'A test meeting description',
        'location' => 'Office 101',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('appointments.export-ics', $appointment->uuid));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/calendar; charset=utf-8');
    $content = $response->streamedContent();
    expect($content)->toContain('BEGIN:VCALENDAR');
    expect($content)->toContain('BEGIN:VEVENT');
    expect($content)->toContain('END:VCALENDAR');
});

it('export ics requires view permission', function (): void {
    $user = User::factory()->create();

    $appointment = Appointment::factory()->create();

    $response = $this->actingAs($user)->get(route('appointments.export-ics', $appointment->uuid));

    $response->assertStatus(302);
});

it('exported ics contains correct fields', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view appointments');

    $appointment = Appointment::factory()->confirmed()->create([
        'title' => 'Important Meeting',
        'description' => 'Discuss project plans',
        'location' => 'Conference Room B',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('appointments.export-ics', $appointment->uuid));

    $content = $response->streamedContent();
    expect($content)->toContain('SUMMARY:Important Meeting');
    expect($content)->toContain('DESCRIPTION:Discuss project plans');
    expect($content)->toContain('Conference Room B');
});

// === Export Bulk ===

it('can export bulk appointments as ics', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view appointments');

    Appointment::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('appointments.export-bulk-ics'));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/calendar; charset=utf-8');
    $content = $response->streamedContent();
    expect($content)->toContain('BEGIN:VCALENDAR');
});

it('bulk export respects status filter', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view appointments');

    Appointment::factory()->confirmed()->count(2)->create(['user_id' => $user->id]);
    Appointment::factory()->cancelled()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('appointments.export-bulk-ics', ['status' => 'confirmed']));

    $response->assertSuccessful();
    $content = $response->streamedContent();
    expect($content)->toContain('BEGIN:VCALENDAR');
    // Count VEVENT occurrences - should be 2 (confirmed only)
    expect(substr_count((string) $content, 'BEGIN:VEVENT'))->toBe(2);
});

it('bulk export requires permission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appointments.export-bulk-ics'));

    $response->assertStatus(302);
});

// === Import ===

it('can import an ics file', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create appointments');

    $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nSUMMARY:Imported Meeting\r\nDTSTART:20260601T100000Z\r\nDTEND:20260601T110000Z\r\nLOCATION:Room A\r\nDESCRIPTION:An imported event\r\nEND:VEVENT\r\nEND:VCALENDAR";

    $file = UploadedFile::fake()->createWithContent('calendar.ics', $icsContent);

    $response = $this->actingAs($user)->post(route('appointments.import-ics'), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    $this->assertDatabaseHas('appointments', [
        'title' => 'Imported Meeting',
        'location' => 'Room A',
        'user_id' => $user->id,
    ]);
});

it('import validates file type', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create appointments');

    $file = UploadedFile::fake()->createWithContent('document.pdf', 'not an ics file');

    $response = $this->actingAs($user)->post(route('appointments.import-ics'), [
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
});

it('import handles multiple events', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create appointments');

    $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n"
        ."BEGIN:VEVENT\r\nSUMMARY:Meeting One\r\nDTSTART:20260601T100000Z\r\nDTEND:20260601T110000Z\r\nEND:VEVENT\r\n"
        ."BEGIN:VEVENT\r\nSUMMARY:Meeting Two\r\nDTSTART:20260602T140000Z\r\nDTEND:20260602T150000Z\r\nEND:VEVENT\r\n"
        ."BEGIN:VEVENT\r\nSUMMARY:Meeting Three\r\nDTSTART:20260603T090000Z\r\nDTEND:20260603T100000Z\r\nEND:VEVENT\r\n"
        .'END:VCALENDAR';

    $file = UploadedFile::fake()->createWithContent('calendar.ics', $icsContent);

    $response = $this->actingAs($user)->post(route('appointments.import-ics'), [
        'file' => $file,
    ]);

    $response->assertRedirect();

    expect(Appointment::where('user_id', $user->id)->count())->toBe(3);
    $this->assertDatabaseHas('appointments', ['title' => 'Meeting One']);
    $this->assertDatabaseHas('appointments', ['title' => 'Meeting Two']);
    $this->assertDatabaseHas('appointments', ['title' => 'Meeting Three']);
});

it('import requires create permission', function (): void {
    $user = User::factory()->create();

    $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nSUMMARY:Test\r\nDTSTART:20260601T100000Z\r\nDTEND:20260601T110000Z\r\nEND:VEVENT\r\nEND:VCALENDAR";

    $file = UploadedFile::fake()->createWithContent('calendar.ics', $icsContent);

    $response = $this->actingAs($user)->post(route('appointments.import-ics'), [
        'file' => $file,
    ]);

    $response->assertStatus(302);
    expect(Appointment::count())->toBe(0);
});

it('import skips events without title or date', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create appointments');

    $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n"
        ."BEGIN:VEVENT\r\nSUMMARY:Valid Meeting\r\nDTSTART:20260601T100000Z\r\nDTEND:20260601T110000Z\r\nEND:VEVENT\r\n"
        ."BEGIN:VEVENT\r\nDESCRIPTION:No title event\r\nDTSTART:20260602T100000Z\r\nDTEND:20260602T110000Z\r\nEND:VEVENT\r\n"
        .'END:VCALENDAR';

    $file = UploadedFile::fake()->createWithContent('calendar.ics', $icsContent);

    $response = $this->actingAs($user)->post(route('appointments.import-ics'), [
        'file' => $file,
    ]);

    $response->assertRedirect();

    // Only the valid meeting should be imported
    expect(Appointment::where('user_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('appointments', ['title' => 'Valid Meeting']);
});

// === Calendar URLs ===

it('show page includes calendar urls', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view appointments');

    $appointment = Appointment::factory()->confirmed()->create([
        'title' => 'Calendar URL Test',
        'user_id' => $user->id,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($user)->get(route('appointments.show', $appointment->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Appointments/Show')
        ->has('calendarUrls.google')
        ->has('calendarUrls.outlook')
        ->has('calendarUrls.ics')
    );
});

// === ICalendarService Unit ===

it('service generates valid ics for single appointment', function (): void {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->confirmed()->create([
        'title' => 'Service Test',
        'description' => 'Service description',
        'location' => 'Room 42',
        'user_id' => $user->id,
    ]);

    $service = new ICalendarService;
    $ics = $service->exportAppointment($appointment);

    expect($ics)->toContain('BEGIN:VCALENDAR');
    expect($ics)->toContain('SUMMARY:Service Test');
    expect($ics)->toContain('END:VCALENDAR');
});

it('service generates google calendar url', function (): void {
    $appointment = Appointment::factory()->create([
        'title' => 'Google Test',
        'description' => 'Test description',
        'location' => 'Berlin',
    ]);

    $service = new ICalendarService;
    $url = $service->generateGoogleCalendarUrl($appointment);

    expect($url)->toStartWith('https://calendar.google.com/calendar/render?');
    expect($url)->toContain('Google+Test');
    expect($url)->toContain('Berlin');
});

it('service generates outlook web url', function (): void {
    $appointment = Appointment::factory()->create([
        'title' => 'Outlook Test',
        'location' => 'Munich',
    ]);

    $service = new ICalendarService;
    $url = $service->generateOutlookWebUrl($appointment);

    expect($url)->toStartWith('https://outlook.live.com/calendar/0/action/compose?');
    expect($url)->toContain('Outlook+Test');
    expect($url)->toContain('Munich');
});
