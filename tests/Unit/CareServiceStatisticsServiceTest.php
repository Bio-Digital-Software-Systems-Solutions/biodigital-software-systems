<?php

use App\Models\CareService;
use App\Models\CareServiceAvailability;
use App\Models\User;
use App\Services\CareServiceStatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'pastor']);
    Role::create(['name' => 'care-service-agent']);
    $this->service = new CareServiceStatisticsService;
});

// Helper to create a pastor
function createPastor(): User
{
    $user = User::factory()->create();
    $user->assignRole('pastor');

    return $user;
}

// Helper to create an appointment
function createAppointment(array $attributes = []): CareService
{
    $defaults = [
        'pastor_id' => createPastor()->id,
        'appointment_date' => now()->toDateString(),
        'appointment_time' => now()->setHour(10)->setMinute(0),
        'status' => 'pending',
    ];

    return CareService::factory()->create(array_merge($defaults, $attributes));
}

describe('getAppointmentsByPastor', function (): void {
    test('returns empty collection when no appointments exist', function (): void {
        $result = $this->service->getAppointmentsByPastor('month');

        expect($result)->toBeEmpty();
    });

    test('returns appointments distribution by pastor for current month', function (): void {
        $pastor1 = createPastor();
        $pastor2 = createPastor();

        // Create 3 appointments for pastor1
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
        ]);

        // Create 2 appointments for pastor2
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
        ]);

        $result = $this->service->getAppointmentsByPastor('month');

        expect($result)->toHaveCount(2);
        expect($result->first()['count'])->toBe(3);
        expect($result->first()['percentage'])->toBe(60.0);
        expect($result->last()['count'])->toBe(2);
        expect($result->last()['percentage'])->toBe(40.0);
    });

    test('includes pastor name in result', function (): void {
        $pastor = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        $pastor->assignRole('pastor');

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
        ]);

        $result = $this->service->getAppointmentsByPastor('month');

        expect($result->first()['pastor_name'])->toBe('Jean Dupont');
    });

    test('filters by week period correctly', function (): void {
        $pastor = createPastor();

        // This week
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfWeek()->addDays(2),
        ]);

        // Last week
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->subWeek()->startOfWeek()->addDays(2),
        ]);

        $result = $this->service->getAppointmentsByPastor('week');

        expect($result->sum('count'))->toBe(2);
    });
});

describe('getAverageDuration', function (): void {
    test('returns zero values when no appointments exist', function (): void {
        $result = $this->service->getAverageDuration('month');

        expect($result['average'])->toBe(0.0);
        expect($result['min'])->toBe(0); // MySQL returns 0 for MIN on empty set
        expect($result['max'])->toBe(0); // MySQL returns 0 for MAX on empty set
        expect($result['count'])->toBe(0);
    });

    test('calculates correct average duration for completed appointments', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 30,
            'status' => 'completed',
        ]);

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(6),
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(7),
            'duration_minutes' => 90,
            'status' => 'confirmed',
        ]);

        $result = $this->service->getAverageDuration('month');

        expect($result['average'])->toBe(60.0);
        expect($result['min'])->toBe(30);
        expect($result['max'])->toBe(90);
        expect($result['count'])->toBe(3);
    });

    test('excludes pending and cancelled appointments from average', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(6),
            'duration_minutes' => 120,
            'status' => 'pending',
        ]);

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(7),
            'duration_minutes' => 90,
            'status' => 'cancelled',
        ]);

        $result = $this->service->getAverageDuration('month');

        expect($result['count'])->toBe(1);
        expect($result['average'])->toBe(60.0);
    });

    test('formats duration correctly', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 45,
            'status' => 'completed',
        ]);

        $result = $this->service->getAverageDuration('month');

        expect($result['formatted'])->toBe('45 min');
    });
});

describe('getDistributionByTheme', function (): void {
    test('returns empty collection when no themed appointments exist', function (): void {
        $result = $this->service->getDistributionByTheme('month');

        expect($result)->toBeEmpty();
    });

    test('groups appointments by theme correctly', function (): void {
        $pastor = createPastor();

        // Create 3 spiritual guidance appointments
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'theme' => 'spiritual_guidance',
        ]);

        // Create 2 grief counseling appointments
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(6),
            'theme' => 'grief_counseling',
        ]);

        $result = $this->service->getDistributionByTheme('month');

        expect($result)->toHaveCount(2);
        expect($result->first()['count'])->toBe(3);
        expect($result->first()['theme'])->toBe('spiritual_guidance');
        expect($result->first()['theme_label'])->toBe('Accompagnement spirituel');
        expect($result->first()['percentage'])->toBe(60.0);
    });

    test('excludes appointments without theme', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'theme' => 'spiritual_guidance',
        ]);

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(6),
            'theme' => null,
        ]);

        $result = $this->service->getDistributionByTheme('month');

        expect($result)->toHaveCount(1);
    });
});

describe('getFollowUpFrequency', function (): void {
    test('returns zero values when no appointments exist', function (): void {
        $result = $this->service->getFollowUpFrequency('month');

        expect($result['total'])->toBe(0);
        expect($result['follow_ups'])->toBe(0);
        expect($result['initial'])->toBe(0);
        expect($result['follow_up_rate'])->toBe(0);
    });

    test('calculates follow-up statistics correctly', function (): void {
        $pastor = createPastor();

        // Create 3 initial appointments
        $initialAppointments = CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'parent_id' => null,
        ]);

        // Create 2 follow-up appointments
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'parent_id' => $initialAppointments->first()->id,
        ]);

        $result = $this->service->getFollowUpFrequency('month');

        expect($result['total'])->toBe(5);
        expect($result['follow_ups'])->toBe(2);
        expect($result['initial'])->toBe(3);
        expect($result['follow_up_rate'])->toBe(40.0);
        expect($result['average_follow_ups_per_initial'])->toBe(0.67);
    });
});

describe('getTransferStatistics', function (): void {
    test('returns zero values when no appointments exist', function (): void {
        $result = $this->service->getTransferStatistics('month');

        expect($result['total'])->toBe(0);
        expect($result['transferred'])->toBe(0);
        expect($result['transfer_rate'])->toBe(0);
        expect($result['by_destination'])->toBeEmpty();
    });

    test('calculates transfer statistics correctly', function (): void {
        $pastor1 = createPastor();
        $pastor2 = createPastor();

        // Create 5 normal appointments
        CareService::factory()->count(5)->create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'transferred_at' => null,
        ]);

        // Create 2 transferred appointments
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'transferred_from_id' => $pastor1->id,
            'transferred_to_id' => $pastor2->id,
            'transferred_at' => now()->startOfMonth()->addDays(8),
        ]);

        $result = $this->service->getTransferStatistics('month');

        expect($result['total'])->toBe(7);
        expect($result['transferred'])->toBe(2);
        expect($result['transfer_rate'])->toBeGreaterThan(0);
        expect($result['by_destination'])->toHaveCount(1);
    });

    test('groups transfers by destination user', function (): void {
        $pastor1 = createPastor();
        $pastor2 = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
        ]);
        $pastor2->assignRole('pastor');

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'transferred_from_id' => $pastor1->id,
            'transferred_to_id' => $pastor2->id,
            'transferred_at' => now()->startOfMonth()->addDays(8),
        ]);

        $result = $this->service->getTransferStatistics('month');

        expect($result['by_destination']->first()['user_name'])->toBe('Marie Martin');
        expect($result['by_destination']->first()['count'])->toBe(3);
    });
});

describe('getIncomingAppointments', function (): void {
    test('returns empty collection when no pending appointments', function (): void {
        $result = $this->service->getIncomingAppointments();

        expect($result)->toBeEmpty();
    });

    test('returns only pending future appointments', function (): void {
        $pastor = createPastor();

        // Create 2 pending future appointments
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        // Create confirmed appointment (should be excluded)
        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->addDays(3),
            'status' => 'confirmed',
        ]);

        // Create past pending appointment (should be excluded)
        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->subDays(2),
            'status' => 'pending',
        ]);

        $result = $this->service->getIncomingAppointments();

        expect($result)->toHaveCount(2);
    });

    test('respects limit parameter', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(10)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $result = $this->service->getIncomingAppointments(5);

        expect($result)->toHaveCount(5);
    });

    test('orders by appointment date and time', function (): void {
        $pastor = createPastor();

        $later = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->addDays(10),
            'appointment_time' => now()->addDays(10)->setHour(10),
            'status' => 'pending',
        ]);

        $earlier = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(9),
            'status' => 'pending',
        ]);

        $result = $this->service->getIncomingAppointments();

        expect($result->first()->id)->toBe($earlier->id);
    });
});

describe('getAllAvailabilities', function (): void {
    test('returns empty collection when no availabilities exist', function (): void {
        $result = $this->service->getAllAvailabilities();

        expect($result)->toBeEmpty();
    });

    test('returns active availabilities grouped by pastor', function (): void {
        $pastor1 = createPastor();
        $pastor2 = createPastor();

        // Create 2 availabilities for pastor1
        CareServiceAvailability::factory()->count(2)->create([
            'pastor_id' => $pastor1->id,
            'is_active' => true,
        ]);

        // Create 1 availability for pastor2
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor2->id,
            'is_active' => true,
        ]);

        // Create inactive availability (should be excluded)
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor1->id,
            'is_active' => false,
        ]);

        $result = $this->service->getAllAvailabilities();

        expect($result)->toHaveCount(2);
        expect($result->first()['availabilities'])->toHaveCount(2);
    });

    test('calculates weekly slots correctly', function (): void {
        $pastor = createPastor();

        // Create weekly availability: 9:00-12:00, 60 min slots = 3 slots
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $result = $this->service->getAllAvailabilities();

        // 3 hours with 60 min slots = 3 slots
        expect($result->first()['total_slots_per_week'])->toBe(3);
    });
});

describe('getStatusDistribution', function (): void {
    test('returns all statuses with zero counts when no appointments', function (): void {
        $result = $this->service->getStatusDistribution('month');

        expect($result['total'])->toBe(0);
        expect($result['distribution'])->toHaveCount(5); // 5 status types
    });

    test('calculates status distribution correctly', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(6),
            'status' => 'confirmed',
        ]);

        CareService::factory()->count(5)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(7),
            'status' => 'completed',
        ]);

        $result = $this->service->getStatusDistribution('month');

        expect($result['total'])->toBe(10);

        $pendingDist = collect($result['distribution'])->firstWhere('status', 'pending');
        expect($pendingDist['count'])->toBe(3);
        expect($pendingDist['percentage'])->toBe(30.0);
        expect($pendingDist['label'])->toBe('En attente');

        $completedDist = collect($result['distribution'])->firstWhere('status', 'completed');
        expect($completedDist['count'])->toBe(5);
        expect($completedDist['percentage'])->toBe(50.0);
    });
});

describe('getTrendData', function (): void {
    test('returns empty collection when no appointments', function (): void {
        $result = $this->service->getTrendData('month', 'day');

        expect($result)->toBeEmpty();
    });

    test('groups appointments by day correctly', function (): void {
        $pastor = createPastor();

        // Create appointments on 2 different days
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
        ]);

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'pending',
        ]);

        $result = $this->service->getTrendData('month', 'day');

        expect($result)->toHaveCount(2);
        expect((int) $result->first()->total)->toBe(2);
        expect((int) $result->first()->completed)->toBe(2);
    });

    test('groups by week when specified', function (): void {
        $pastor = createPastor();

        // Create appointments in same week
        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(1),
        ]);

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(2),
        ]);

        $result = $this->service->getTrendData('month', 'week');

        // Should be grouped into weeks
        expect($result->sum('total'))->toBe(5);
    });
});

describe('getCareServiceDashboardStats', function (): void {
    test('returns comprehensive dashboard statistics', function (): void {
        $pastor = createPastor();

        // Create various appointments
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
            'duration_minutes' => 60,
            'theme' => 'spiritual_guidance',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'completed',
            'duration_minutes' => 45,
            'theme' => 'grief_counseling',
        ]);

        // Create availability
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'is_active' => true,
        ]);

        $result = $this->service->getCareServiceDashboardStats('month');

        // Check structure
        expect($result)->toHaveKeys([
            'period',
            'overview',
            'average_duration',
            'by_pastor',
            'by_theme',
            'by_status',
            'follow_ups',
            'transfers',
            'trend',
            'incoming',
            'availabilities',
        ]);

        // Check overview
        expect($result['overview']['total'])->toBe(5);
        expect($result['overview']['pending'])->toBe(3);
        expect($result['overview']['completed'])->toBe(2);

        // Check period
        expect($result['period']['label'])->toBe('Ce mois');
    });

    test('handles different periods correctly', function (): void {
        $result = $this->service->getCareServiceDashboardStats('week');
        expect($result['period']['label'])->toBe('Cette semaine');

        $result = $this->service->getCareServiceDashboardStats('quarter');
        expect($result['period']['label'])->toBe('Ce trimestre');

        $result = $this->service->getCareServiceDashboardStats('year');
        expect($result['period']['label'])->toBe('Cette année');
    });
});

describe('getPeriodDates helper', function (): void {
    test('returns correct dates for week period', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 2, 4)); // A Wednesday

        $result = $this->service->getCareServiceDashboardStats('week');

        expect($result['period']['start'])->toBe('2026-02-02'); // Monday
        expect($result['period']['end'])->toBe('2026-02-08'); // Sunday

        Carbon::setTestNow();
    });

    test('returns correct dates for month period', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 2, 15));

        $result = $this->service->getCareServiceDashboardStats('month');

        expect($result['period']['start'])->toBe('2026-02-01');
        expect($result['period']['end'])->toBe('2026-02-28');

        Carbon::setTestNow();
    });

    test('returns correct dates for quarter period', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 2, 15));

        $result = $this->service->getCareServiceDashboardStats('quarter');

        expect($result['period']['start'])->toBe('2026-01-01');
        expect($result['period']['end'])->toBe('2026-03-31');

        Carbon::setTestNow();
    });

    test('returns correct dates for year period', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 6, 15));

        $result = $this->service->getCareServiceDashboardStats('year');

        expect($result['period']['start'])->toBe('2026-01-01');
        expect($result['period']['end'])->toBe('2026-12-31');

        Carbon::setTestNow();
    });
});

describe('formatDuration helper', function (): void {
    test('formats minutes correctly', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 30,
            'status' => 'completed',
        ]);

        $result = $this->service->getAverageDuration('month');
        expect($result['formatted'])->toBe('30 min');
    });

    test('formats hours correctly', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $result = $this->service->getAverageDuration('month');
        expect($result['formatted'])->toBe('1h');
    });

    test('formats hours and minutes correctly', function (): void {
        $pastor = createPastor();

        CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);

        $result = $this->service->getAverageDuration('month');
        expect($result['formatted'])->toBe('1h 30min');
    });
});

describe('getAnalyticsData', function (): void {
    test('returns analytics data structure', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
            'theme' => 'spiritual_guidance',
            'location_type' => 'in_person',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'completed',
            'theme' => 'grief_counseling',
            'location_type' => 'zoom',
        ]);

        $result = $this->service->getAnalyticsData('month');

        // Check structure
        expect($result)->toHaveKeys([
            'appointments_by_status',
            'appointments_by_theme',
            'appointments_by_pastor',
            'appointments_by_mode',
            'global_progress',
            'velocity',
            'appointment_evolution',
            'completion_by_pastor',
        ]);
    });

    test('returns correct appointments by status', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'completed',
        ]);

        $result = $this->service->getAnalyticsData('month');

        $statusData = collect($result['appointments_by_status']);
        $pendingItem = $statusData->firstWhere('label', 'En attente');
        $completedItem = $statusData->firstWhere('label', 'Terminé');

        expect($pendingItem['value'])->toBe(3);
        expect($completedItem['value'])->toBe(2);
    });

    test('returns correct global progress', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(4)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
        ]);

        CareService::factory()->count(1)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'pending',
        ]);

        $result = $this->service->getAnalyticsData('month');

        expect($result['global_progress']['total'])->toBe(5);
        expect($result['global_progress']['completed'])->toBe(4);
        expect($result['global_progress']['percentage'])->toBe(80.0);
    });

    test('returns velocity data structure', function (): void {
        $result = $this->service->getAnalyticsData('month');

        expect($result['velocity'])->toHaveKeys(['daily', 'weekly', 'monthly']);
        expect($result['velocity']['daily'])->toHaveKeys(['value', 'total', 'period_count', 'max', 'label']);
        expect($result['velocity']['weekly'])->toHaveKeys(['value', 'total', 'period_count', 'max', 'label']);
        expect($result['velocity']['monthly'])->toHaveKeys(['value', 'total', 'period_count', 'max', 'label']);
    });

    test('returns appointment evolution data', function (): void {
        $result = $this->service->getAnalyticsData('month');

        expect($result['appointment_evolution'])->toHaveKeys(['weekly', 'monthly', 'quarterly']);
        expect($result['appointment_evolution']['weekly'])->toBeArray();
        expect($result['appointment_evolution']['monthly'])->toBeArray();
        expect($result['appointment_evolution']['quarterly'])->toBeArray();
    });

    test('returns completion by pastor', function (): void {
        $pastor1 = createPastor();
        $pastor2 = createPastor();

        CareService::factory()->count(4)->create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
        ]);

        CareService::factory()->count(1)->create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'pending',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
        ]);

        $result = $this->service->getAnalyticsData('month');

        expect($result['completion_by_pastor'])->toBeArray();
        expect(count($result['completion_by_pastor']))->toBe(2);

        // First pastor should have 80% completion rate (4/5)
        // Second pastor should have 100% completion rate (2/2)
        $pastor2Data = collect($result['completion_by_pastor'])->first();
        expect($pastor2Data['value'])->toBe(100.0);
    });

    test('includes analytics data in dashboard stats', function (): void {
        $pastor = createPastor();

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        $result = $this->service->getCareServiceDashboardStats('month');

        expect($result)->toHaveKey('analytics');
        expect($result['analytics'])->toHaveKeys([
            'appointments_by_status',
            'appointments_by_theme',
            'appointments_by_pastor',
            'appointments_by_mode',
            'global_progress',
            'velocity',
            'appointment_evolution',
            'completion_by_pastor',
        ]);
    });
});
