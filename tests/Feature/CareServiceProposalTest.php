<?php

use App\Models\CareService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create necessary roles
    Role::firstOrCreate(['name' => 'pastor']);
    Role::firstOrCreate(['name' => 'member']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'super-admin']);
    Role::firstOrCreate(['name' => 'care-service-agent']);

    // Create users
    $this->pastor = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Pastor',
        'email' => 'pastor@example.com',
    ]);
    $this->pastor->assignRole('pastor');

    $this->careServiceAgent = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Agent',
        'email' => 'care-service-agent@example.com',
    ]);
    $this->careServiceAgent->assignRole('care-service-agent');

    $this->admin = User::factory()->create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin@example.com',
    ]);
    $this->admin->assignRole('admin');

    $this->regularUser = User::factory()->create([
        'first_name' => 'Regular',
        'last_name' => 'User',
        'email' => 'user@example.com',
    ]);
    $this->regularUser->assignRole('member');
});

// ==========================================
// PROPOSAL SUBMISSION TESTS
// ==========================================

it('can submit a proposal for a custom appointment date', function (): void {
    $proposalData = [
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'client_phone' => '+4917612345678',
        'appointment_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d'),
        'appointment_time' => '14:00',
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'notes' => 'Je souhaite discuter de questions spirituelles',
        'proposal_reason' => 'Les créneaux proposés ne correspondent pas à mon emploi du temps',
        'theme' => 'spiritual_guidance',
    ];

    $response = $this->postJson('/api/care-service/proposals', $proposalData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'uuid',
                'proposal_token',
                'appointment' => [
                    'client_name',
                    'client_email',
                    'appointment_date',
                    'appointment_time',
                    'duration_minutes',
                    'location_type',
                    'status',
                    'proposal_response_status',
                ],
            ],
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('data.appointment.status'))->toBe('proposed');
    expect($response->json('data.appointment.proposal_response_status'))->toBe('pending');

    $this->assertDatabaseHas('care_services', [
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
    ]);
});

it('validates required fields when submitting a proposal', function (): void {
    $response = $this->postJson('/api/care-service/proposals', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'client_name',
            'client_email',
            'appointment_date',
            'appointment_time',
            'duration_minutes',
            'location_type',
            'proposal_reason',
        ]);
});

it('validates appointment time is within reasonable hours (8:00-20:00)', function (): void {
    $proposalData = [
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
        'appointment_time' => '06:00', // Too early
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'proposal_reason' => 'Test reason',
    ];

    $response = $this->postJson('/api/care-service/proposals', $proposalData);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'L\'heure proposée doit être entre 08:00 et 20:00.',
        ]);
});

it('validates appointment date is not too far in the future (max 3 months)', function (): void {
    // Use a date well beyond 3 months to ensure it fails
    $proposalData = [
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::now()->addMonths(3)->addDays(2)->format('Y-m-d'),
        'appointment_time' => '14:00',
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'proposal_reason' => 'Test reason',
    ];

    $response = $this->postJson('/api/care-service/proposals', $proposalData);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'La date proposée ne peut pas être à plus de 3 mois.',
        ]);
});

// ==========================================
// PROPOSAL VIEW TESTS
// ==========================================

it('can view a proposal by token', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->getJson('/api/care-service/proposals/show?token='.$proposal->proposal_token);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'uuid',
                'client_name',
                'client_email',
                'appointment_date',
                'appointment_time',
                'status',
                'proposal_reason',
                'proposal_response_status',
                'has_counter_proposal',
            ],
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('data.client_name'))->toBe('Jean Dupont');
    expect($response->json('data.has_counter_proposal'))->toBeFalse();
});

it('returns 404 for invalid proposal token', function (): void {
    $response = $this->getJson('/api/care-service/proposals/show?token=invalid_token_12345678901234567890123456789012345678901234567890');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Proposition introuvable ou token invalide',
        ]);
});

// ==========================================
// CARE SERVICE PROPOSAL MANAGEMENT TESTS
// ==========================================

it('allows care service agent to view pending proposals', function (): void {
    // Create some proposals
    CareService::factory()->count(3)->create([
        'pastor_id' => $this->pastor->id,
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
        'proposal_token' => fn () => Str::random(64),
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->careServiceAgent)
        ->getJson('/api/care-service/proposals');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data',
            'stats' => [
                'pending',
                'counter_proposed',
                'accepted',
                'rejected',
            ],
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('stats.pending'))->toBe(3);
});

it('denies proposal access to regular users', function (): void {
    $response = $this->actingAs($this->regularUser)
        ->getJson('/api/care-service/proposals');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized - Care Service access required',
        ]);
});

it('allows care service agent to accept a proposal and assign a pastor', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/accept", [
            'pastor_id' => $this->pastor->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('pending');
    expect($proposal->proposal_response_status)->toBe('accepted');
    expect($proposal->care_service_agent_id)->toBe($this->careServiceAgent->id);
    expect($proposal->pastor_id)->toBe($this->pastor->id);
    expect($proposal->proposal_reviewed_at)->not->toBeNull();
});

it('validates that assigned pastor is actually a pastor', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/accept", [
            'pastor_id' => $this->regularUser->id, // Not a pastor
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'L\'utilisateur sélectionné n\'est pas un pasteur.',
        ]);
});

it('allows care service agent to reject a proposal without counter-proposal', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/reject", [
            'rejection_reason' => 'Aucun pasteur disponible à cette date',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Proposition refusée. Le client a été notifié.',
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('cancelled');
    expect($proposal->proposal_response_status)->toBe('rejected');
    expect($proposal->proposal_rejection_reason)->toBe('Aucun pasteur disponible à cette date');
});

it('allows care service agent to reject a proposal with counter-proposal', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $counterDate = Carbon::tomorrow()->addDays(7)->format('Y-m-d');

    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/reject", [
            'rejection_reason' => 'Le créneau demandé n\'est pas disponible',
            'counter_proposed_date' => $counterDate,
            'counter_proposed_time' => '10:00',
            'counter_proposal_message' => 'Nous vous proposons ce créneau alternatif.',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Contre-proposition envoyée. Le client a été notifié.',
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('proposed'); // Still proposed, waiting for client response
    expect($proposal->proposal_response_status)->toBe('counter_proposed');
    expect($proposal->counter_proposed_date->format('Y-m-d'))->toBe($counterDate);
    expect(substr((string) $proposal->counter_proposed_time, 0, 5))->toBe('10:00'); // MySQL stores as 10:00:00
    expect($proposal->counter_proposal_message)->toBe('Nous vous proposons ce créneau alternatif.');
    expect($proposal->counter_proposal_sent_at)->not->toBeNull();
});

// ==========================================
// CLIENT COUNTER-PROPOSAL RESPONSE TESTS
// ==========================================

it('allows client to accept a counter-proposal', function (): void {
    $counterDate = Carbon::tomorrow()->addDays(7);

    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'counter_proposed',
        'proposal_submitted_at' => now(),
        'counter_proposed_date' => $counterDate,
        'counter_proposed_time' => '10:00',
        'counter_proposal_message' => 'Alternative slot',
        'counter_proposal_sent_at' => now(),
    ]);

    $response = $this->postJson('/api/care-service/proposals/accept-counter', [
        'token' => $proposal->proposal_token,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('pending');
    expect($proposal->proposal_response_status)->toBe('accepted');
    expect($proposal->appointment_date->format('Y-m-d'))->toBe($counterDate->format('Y-m-d'));
    expect($proposal->appointment_time->format('H:i'))->toBe('10:00');
    expect($proposal->client_responded_at)->not->toBeNull();
    // Counter-proposal fields should be cleared
    expect($proposal->counter_proposed_date)->toBeNull();
    expect($proposal->counter_proposed_time)->toBeNull();
});

it('allows client to reject a counter-proposal', function (): void {
    $counterDate = Carbon::tomorrow()->addDays(7);

    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'counter_proposed',
        'proposal_submitted_at' => now(),
        'counter_proposed_date' => $counterDate,
        'counter_proposed_time' => '10:00',
        'counter_proposal_message' => 'Alternative slot',
        'counter_proposal_sent_at' => now(),
    ]);

    $response = $this->postJson('/api/care-service/proposals/reject-counter', [
        'token' => $proposal->proposal_token,
        'reason' => 'Ce créneau ne me convient pas non plus.',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('cancelled');
    expect($proposal->proposal_response_status)->toBe('rejected');
    expect($proposal->client_responded_at)->not->toBeNull();
});

it('returns error when trying to accept counter-proposal without one', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending', // No counter-proposal
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->postJson('/api/care-service/proposals/accept-counter', [
        'token' => $proposal->proposal_token,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Aucune contre-proposition à accepter.',
        ]);
});

// ==========================================
// MODEL METHOD TESTS
// ==========================================

it('correctly identifies proposals with counter-proposals', function (): void {
    $proposalWithoutCounter = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Client 1',
        'client_email' => 'client1@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
    ]);

    // First create without counter, then update to add counter-proposal properly
    $proposalWithCounter = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Client 2',
        'client_email' => 'client2@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(15),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
    ]);

    // Simulate care service agent sending counter-proposal
    $proposalWithCounter->update([
        'proposal_response_status' => 'counter_proposed',
        'counter_proposed_date' => Carbon::tomorrow()->addDays(2),
        'counter_proposed_time' => '10:00',
    ]);

    $proposalWithCounter->refresh();

    expect($proposalWithoutCounter->hasCounterProposal())->toBeFalse();
    expect($proposalWithCounter->hasCounterProposal())->toBeTrue();
});

it('generates proposal token automatically when is_proposal is true', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
    ]);

    expect($proposal->proposal_token)->not->toBeNull();
    expect(strlen((string) $proposal->proposal_token))->toBe(64);
    expect($proposal->proposal_submitted_at)->not->toBeNull();
    expect($proposal->proposal_response_status)->toBe('pending');
});

it('returns correct proposal status label', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
    ]);

    expect($proposal->proposal_status_label)->toBe('En attente de réponse');

    $proposal->update(['proposal_response_status' => 'accepted']);
    $proposal->refresh();
    expect($proposal->proposal_status_label)->toBe('Proposition acceptée');

    $proposal->update(['proposal_response_status' => 'rejected']);
    $proposal->refresh();
    expect($proposal->proposal_status_label)->toBe('Proposition refusée');

    $proposal->update(['proposal_response_status' => 'counter_proposed']);
    $proposal->refresh();
    expect($proposal->proposal_status_label)->toBe('Contre-proposition envoyée');
});

// ==========================================
// SCOPE TESTS
// ==========================================

it('filters proposals using scopes', function (): void {
    // Create regular appointments
    CareService::factory()->count(2)->create([
        'pastor_id' => $this->pastor->id,
        'status' => 'pending',
        'is_proposal' => false,
    ]);

    // Create proposals
    CareService::factory()->count(3)->create([
        'pastor_id' => $this->pastor->id,
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'pending',
        'proposal_token' => fn () => Str::random(64),
    ]);

    CareService::factory()->count(2)->create([
        'pastor_id' => $this->pastor->id,
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_response_status' => 'counter_proposed',
        'proposal_token' => fn () => Str::random(64),
        'counter_proposed_date' => Carbon::tomorrow(),
        'counter_proposed_time' => '10:00',
    ]);

    expect(CareService::proposed()->count())->toBe(5);
    expect(CareService::isProposal()->count())->toBe(5);
    expect(CareService::pendingProposals()->count())->toBe(3);
    expect(CareService::counterProposed()->count())->toBe(2);
});

// ==========================================
// EDGE CASES AND ERROR HANDLING
// ==========================================

it('prevents accepting already processed proposal', function (): void {
    // Create proposal and then accept it to mark it as processed
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    // First accept the proposal
    $proposal->acceptProposal($this->pastor->id, $this->careServiceAgent->id);

    // Now try to accept it again - this should fail since it's already processed
    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/accept", [
            'pastor_id' => $this->pastor->id,
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Cette proposition a déjà été traitée.',
        ]);
});

it('prevents rejecting already processed proposal', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'cancelled',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'rejected',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->careServiceAgent)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/reject", [
            'rejection_reason' => 'Already rejected',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Cette proposition a déjà été traitée.',
        ]);
});

it('returns 404 for non-existent proposal', function (): void {
    $response = $this->actingAs($this->careServiceAgent)
        ->postJson('/api/care-service/proposals/non-existent-uuid/accept', [
            'pastor_id' => $this->pastor->id,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Proposition introuvable',
        ]);
});

it('admin can also manage proposals', function (): void {
    $proposal = CareService::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Jean Dupont',
        'client_email' => 'jean.dupont@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(5),
        'appointment_time' => Carbon::tomorrow()->addDays(5)->setHour(14),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'proposed',
        'is_proposal' => true,
        'proposal_reason' => 'Test reason',
        'proposal_token' => Str::random(64),
        'proposal_response_status' => 'pending',
        'proposal_submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson("/api/care-service/proposals/{$proposal->uuid}/accept", [
            'pastor_id' => $this->pastor->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});
