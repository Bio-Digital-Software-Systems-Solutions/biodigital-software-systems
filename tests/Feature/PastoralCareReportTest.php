<?php

use App\Models\PastoralCare;
use App\Models\User;
use App\Services\PastoralCareReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create necessary roles
    Role::firstOrCreate(['name' => 'pastor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

    // Create a pastor user
    $this->pastor = User::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'pastor@example.com',
    ]);
    $this->pastor->assignRole('pastor');

    // Create a member user
    $this->member = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'email' => 'member@example.com',
    ]);
    $this->member->assignRole('member');

    // Create a test appointment
    $this->appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean-Pierre Martin',
        'client_email' => 'client@example.com',
        'client_phone' => '+49 123 456789',
        'appointment_date' => Carbon::now()->subDays(7),
        'appointment_time' => Carbon::now()->subDays(7)->setHour(14)->setMinute(0),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'notes' => 'Notes du client pour le premier rendez-vous',
        'pastor_notes' => [
            ['note' => 'Première note du pasteur', 'created_at' => now()->subDays(6)->toIso8601String()],
            ['note' => 'Deuxième note du pasteur', 'created_at' => now()->subDays(5)->toIso8601String()],
        ],
        'status' => 'completed',
    ]);

    // Create a follow-up appointment
    $this->followUp = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'parent_id' => $this->appointment->id,
        'client_name' => 'Jean-Pierre Martin',
        'client_email' => 'client@example.com',
        'client_phone' => '+49 123 456789',
        'appointment_date' => Carbon::now()->subDays(3),
        'appointment_time' => Carbon::now()->subDays(3)->setHour(10)->setMinute(0),
        'duration_minutes' => 45,
        'location_type' => 'zoom',
        'notes' => 'Notes du client pour le suivi',
        'pastor_notes' => [
            ['note' => 'Note de suivi', 'created_at' => now()->subDays(2)->toIso8601String()],
        ],
        'status' => 'completed',
    ]);
});

describe('PastoralCareReportService', function (): void {
    it('collects all related appointments including parent and follow-ups', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['appointments'])->toHaveCount(2);
        expect($data['summary']['total_appointments'])->toBe(2);
    });

    it('calculates total duration correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        // 60 min (first appointment) + 45 min (follow-up) = 105 min
        expect($data['summary']['total_duration_minutes'])->toBe(105);
        expect($data['summary']['total_duration_formatted'])->toBe('1h 45min');
    });

    it('includes pastor information', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['pastor']['name'])->toBe('Jean Dupont');
        expect($data['pastor']['email'])->toBe('pastor@example.com');
    });

    it('includes client information', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['client']['name'])->toBe('Jean-Pierre Martin');
        expect($data['client']['email'])->toBe('client@example.com');
        expect($data['client']['phone'])->toBe('+49 123 456789');
    });

    it('formats pastor notes correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $firstAppointment = collect($data['appointments'])->first();
        expect($firstAppointment['pastor_notes'])->toHaveCount(2);
        expect($firstAppointment['pastor_notes'][0]['content'])->toBe('Première note du pasteur');
    });

    it('translates status correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $firstAppointment = collect($data['appointments'])->first();
        expect($firstAppointment['status'])->toBe('Terminé');
        expect($firstAppointment['status_raw'])->toBe('completed');
    });

    it('translates location type correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $appointments = $data['appointments'];
        $firstAppointment = collect($appointments)->first();
        $secondAppointment = collect($appointments)->last();

        expect($firstAppointment['location_type'])->toBe('En présentiel');
        expect($secondAppointment['location_type'])->toBe('Visioconférence');
    });

    it('marks current appointment correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $appointments = $data['appointments'];
        $currentAppointment = collect($appointments)->firstWhere('is_current', true);

        expect($currentAppointment)->not->toBeNull();
        expect($currentAppointment['id'])->toBe($this->appointment->id);
    });

    it('marks follow-up appointments correctly', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $followUpAppointment = collect($data['appointments'])->firstWhere('is_follow_up', true);

        expect($followUpAppointment)->not->toBeNull();
        expect($followUpAppointment['id'])->toBe($this->followUp->id);
    });
});

describe('Report Generation API', function (): void {
    it('requires authentication', function (): void {
        $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/report")
            ->assertUnauthorized();
    });

    it('requires pastor role', function (): void {
        actingAs($this->member)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report")
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ]);
    });

    it('returns 404 for non-existent appointment', function (): void {
        actingAs($this->pastor)
            ->get('/api/pastoral-care/appointments/non-existent-uuid/report')
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ]);
    });

    it('returns 400 for unsupported format', function (): void {
        actingAs($this->pastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report?format=invalid")
            ->assertBadRequest()
            ->assertJson([
                'success' => false,
                'message' => 'Format non supporté. Formats disponibles: pdf, excel, word',
            ]);
    });

    it('generates PDF report successfully', function (): void {
        $response = actingAs($this->pastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report?format=pdf");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    });

    it('generates Excel report successfully', function (): void {
        $response = actingAs($this->pastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report?format=excel");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
    });

    it('generates Word report successfully', function (): void {
        $response = actingAs($this->pastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report?format=word");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('wordprocessingml');
    });

    it('defaults to PDF format when no format specified', function (): void {
        $response = actingAs($this->pastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    });

    it('only allows pastor to access their own appointments', function (): void {
        // Create another pastor
        $otherPastor = User::factory()->create();
        $otherPastor->assignRole('pastor');

        actingAs($otherPastor)
            ->get("/api/pastoral-care/appointments/{$this->appointment->uuid}/report")
            ->assertNotFound();
    });
});

describe('Report Content Validation', function (): void {
    it('includes church information in report data', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['church'])->toHaveKeys(['name', 'email', 'phone', 'website']);
    });

    it('includes generation timestamp', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['generated_at'])->not->toBeNull();
        expect($data['generated_at'])->toMatch('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/');
    });

    it('includes summary statistics', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        expect($data['summary'])->toHaveKeys([
            'total_appointments',
            'completed_appointments',
            'total_duration_minutes',
            'total_duration_formatted',
            'first_appointment_date',
            'last_appointment_date',
        ]);
    });

    it('sorts appointments by date', function (): void {
        $service = new PastoralCareReportService($this->appointment);
        $data = $service->getReportData();

        $dates = collect($data['appointments'])->pluck('date')->toArray();

        // Convert dates to comparable format
        $parsedDates = array_map(fn ($d): ?\Carbon\Carbon => Carbon::createFromFormat('d/m/Y', $d), $dates);

        for ($i = 0; $i < count($parsedDates) - 1; $i++) {
            expect($parsedDates[$i]->lte($parsedDates[$i + 1]))->toBeTrue();
        }
    });
});

describe('Edge Cases', function (): void {
    it('handles appointment without parent', function (): void {
        $standaloneAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Standalone Client',
            'client_email' => 'standalone@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(10)->setMinute(0),
            'duration_minutes' => 30,
            'location_type' => 'in_person',
            'status' => 'pending',
        ]);

        $service = new PastoralCareReportService($standaloneAppointment);
        $data = $service->getReportData();

        expect($data['appointments'])->toHaveCount(1);
    });

    it('handles appointment without follow-ups', function (): void {
        $noFollowUpsAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'No Follow-ups Client',
            'client_email' => 'nofollowups@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(11)->setMinute(0),
            'duration_minutes' => 45,
            'location_type' => 'zoom',
            'status' => 'confirmed',
        ]);

        $service = new PastoralCareReportService($noFollowUpsAppointment);
        $data = $service->getReportData();

        expect($data['appointments'])->toHaveCount(1);
    });

    it('handles appointment without pastor notes', function (): void {
        $noNotesAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'No Notes Client',
            'client_email' => 'nonotes@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(12)->setMinute(0),
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'status' => 'completed',
            'pastor_notes' => null,
        ]);

        $service = new PastoralCareReportService($noNotesAppointment);
        $data = $service->getReportData();

        $appointment = collect($data['appointments'])->first();
        expect($appointment['pastor_notes'])->toBe([]);
    });

    it('handles appointment without client notes', function (): void {
        $noClientNotesAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'No Client Notes',
            'client_email' => 'noclientnotes@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(13)->setMinute(0),
            'duration_minutes' => 60,
            'location_type' => 'hybrid',
            'status' => 'completed',
            'notes' => null,
        ]);

        $service = new PastoralCareReportService($noClientNotesAppointment);
        $data = $service->getReportData();

        $appointment = collect($data['appointments'])->first();
        expect($appointment['client_notes'])->toBeNull();
    });

    it('handles duration formatting for hours only', function (): void {
        $exactHourAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Exact Hour Client',
            'client_email' => 'exacthour@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(14)->setMinute(0),
            'duration_minutes' => 120,
            'location_type' => 'in_person',
            'status' => 'completed',
        ]);

        $service = new PastoralCareReportService($exactHourAppointment);
        $data = $service->getReportData();

        expect($data['summary']['total_duration_formatted'])->toBe('2h');
    });

    it('handles duration formatting for minutes only', function (): void {
        $minutesOnlyAppointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Minutes Only Client',
            'client_email' => 'minutesonly@example.com',
            'appointment_date' => Carbon::now(),
            'appointment_time' => Carbon::now()->setHour(15)->setMinute(0),
            'duration_minutes' => 45,
            'location_type' => 'in_person',
            'status' => 'completed',
        ]);

        $service = new PastoralCareReportService($minutesOnlyAppointment);
        $data = $service->getReportData();

        expect($data['summary']['total_duration_formatted'])->toBe('45 min');
    });
});
