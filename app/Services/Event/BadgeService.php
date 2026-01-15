<?php

namespace App\Services\Event;

use App\Enums\Event\BadgeStatus;
use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventBadge;
use App\Models\Event\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BadgeService
{
    /**
     * Generate a badge for a registration.
     */
    public function generateBadge(
        EventRegistration $registration,
        array $options = []
    ): EventBadge {
        // Check if badge already exists
        $existingBadge = $registration->badge;
        if ($existingBadge && $existingBadge->status !== BadgeStatus::LOST) {
            return $existingBadge;
        }

        return DB::transaction(function () use ($registration, $options) {
            $event = $registration->event;

            $badge = EventBadge::create([
                'registration_id' => $registration->id,
                'badge_number' => $this->generateBadgeNumber($event),
                'template' => $options['template'] ?? 'default',
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'company' => $registration->company,
                'job_title' => $registration->job_title,
                'custom_fields' => $options['custom_fields'] ?? null,
                'qr_data' => $registration->qr_code,
                'status' => BadgeStatus::GENERATED,
                'generated_at' => now(),
            ]);

            return $badge;
        });
    }

    /**
     * Generate badges for all confirmed registrations.
     */
    public function generateBulkBadges(Event $event, array $options = []): array
    {
        $registrations = $event->registrations()
            ->whereIn('status', [
                RegistrationStatus::CONFIRMED,
                RegistrationStatus::CHECKED_IN,
            ])
            ->whereDoesntHave('badge', function ($q) {
                $q->whereNotIn('status', [BadgeStatus::LOST]);
            })
            ->get();

        $generated = [];
        $errors = [];

        foreach ($registrations as $registration) {
            try {
                $badge = $this->generateBadge($registration, $options);
                $generated[] = $badge;
            } catch (\Exception $e) {
                $errors[] = [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generated' => count($generated),
            'errors' => $errors,
            'badges' => $generated,
        ];
    }

    /**
     * Mark a badge as printed.
     */
    public function markAsPrinted(EventBadge $badge, ?User $printedBy = null): EventBadge
    {
        $badge->update([
            'status' => BadgeStatus::PRINTED,
            'printed_at' => now(),
            'printed_by' => $printedBy?->id,
        ]);

        return $badge->fresh();
    }

    /**
     * Mark badges as printed in bulk.
     */
    public function markBulkAsPrinted(array $badgeIds, ?User $printedBy = null): int
    {
        return EventBadge::whereIn('id', $badgeIds)
            ->where('status', BadgeStatus::GENERATED)
            ->update([
                'status' => BadgeStatus::PRINTED,
                'printed_at' => now(),
                'printed_by' => $printedBy?->id,
            ]);
    }

    /**
     * Mark a badge as collected.
     */
    public function markAsCollected(EventBadge $badge): EventBadge
    {
        $badge->update([
            'status' => BadgeStatus::COLLECTED,
            'collected_at' => now(),
        ]);

        return $badge->fresh();
    }

    /**
     * Report a badge as lost and generate replacement.
     */
    public function reportLostAndReplace(
        EventBadge $badge,
        ?User $replacedBy = null
    ): EventBadge {
        return DB::transaction(function () use ($badge, $replacedBy) {
            // Mark old badge as lost
            $badge->update([
                'status' => BadgeStatus::LOST,
            ]);

            // Generate replacement
            $event = $badge->registration->event;

            $newBadge = EventBadge::create([
                'registration_id' => $badge->registration_id,
                'badge_number' => $this->generateBadgeNumber($event) . '-R',
                'template' => $badge->template,
                'first_name' => $badge->first_name,
                'last_name' => $badge->last_name,
                'company' => $badge->company,
                'job_title' => $badge->job_title,
                'custom_fields' => $badge->custom_fields,
                'qr_data' => $badge->qr_data,
                'status' => BadgeStatus::REPLACED,
                'generated_at' => now(),
                'replaced_by' => $replacedBy?->id,
            ]);

            return $newBadge;
        });
    }

    /**
     * Get badge statistics for an event.
     */
    public function getStats(Event $event): array
    {
        $badges = EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })->get();

        $totalRegistrations = $event->registrations()
            ->whereIn('status', [
                RegistrationStatus::CONFIRMED,
                RegistrationStatus::CHECKED_IN,
            ])
            ->count();

        return [
            'total_expected' => $totalRegistrations,
            'generated' => $badges->whereIn('status', [
                BadgeStatus::GENERATED,
                BadgeStatus::PRINTED,
                BadgeStatus::COLLECTED,
            ])->count(),
            'printed' => $badges->whereIn('status', [
                BadgeStatus::PRINTED,
                BadgeStatus::COLLECTED,
            ])->count(),
            'collected' => $badges->where('status', BadgeStatus::COLLECTED)->count(),
            'pending_generation' => $totalRegistrations - $badges->whereNotIn('status', [BadgeStatus::LOST])->count(),
            'lost' => $badges->where('status', BadgeStatus::LOST)->count(),
            'replaced' => $badges->where('status', BadgeStatus::REPLACED)->count(),
        ];
    }

    /**
     * Get badges pending printing.
     */
    public function getPendingPrint(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })
            ->where('status', BadgeStatus::GENERATED)
            ->with('registration')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get badges pending collection.
     */
    public function getPendingCollection(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })
            ->where('status', BadgeStatus::PRINTED)
            ->with('registration')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Search badges by name or badge number.
     */
    public function search(Event $event, string $search): \Illuminate\Database\Eloquent\Collection
    {
        return EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('badge_number', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            })
            ->with('registration')
            ->orderBy('last_name')
            ->limit(20)
            ->get();
    }

    /**
     * Get badge templates available.
     */
    public function getTemplates(): array
    {
        return [
            'default' => [
                'name' => 'Standard',
                'size' => '86x54mm',
                'fields' => ['first_name', 'last_name', 'company', 'job_title', 'qr_code'],
            ],
            'vip' => [
                'name' => 'VIP',
                'size' => '86x54mm',
                'fields' => ['first_name', 'last_name', 'company', 'job_title', 'qr_code', 'vip_ribbon'],
                'color' => 'gold',
            ],
            'speaker' => [
                'name' => 'Intervenant',
                'size' => '86x54mm',
                'fields' => ['first_name', 'last_name', 'company', 'title', 'qr_code', 'speaker_ribbon'],
                'color' => 'blue',
            ],
            'staff' => [
                'name' => 'Staff',
                'size' => '86x54mm',
                'fields' => ['first_name', 'last_name', 'role', 'qr_code'],
                'color' => 'orange',
            ],
            'sponsor' => [
                'name' => 'Sponsor',
                'size' => '86x54mm',
                'fields' => ['first_name', 'last_name', 'company', 'sponsor_tier', 'qr_code'],
                'color' => 'purple',
            ],
        ];
    }

    /**
     * Generate badge PDF data (for printing).
     */
    public function getBadgePrintData(EventBadge $badge): array
    {
        $registration = $badge->registration;
        $event = $registration->event;
        $template = $this->getTemplates()[$badge->template] ?? $this->getTemplates()['default'];

        return [
            'badge' => [
                'number' => $badge->badge_number,
                'first_name' => $badge->first_name,
                'last_name' => $badge->last_name,
                'full_name' => $badge->full_name,
                'company' => $badge->company,
                'job_title' => $badge->job_title,
                'qr_code' => $badge->qr_data,
                'custom_fields' => $badge->custom_fields,
            ],
            'registration' => [
                'number' => $registration->registration_number,
                'role' => $registration->participant_role->label(),
                'ticket' => $registration->ticket?->name,
            ],
            'event' => [
                'name' => $event->title,
                'date' => $event->start_date->format('d/m/Y'),
                'location' => $event->location,
            ],
            'template' => $template,
        ];
    }

    /**
     * Get bulk print data for multiple badges.
     */
    public function getBulkPrintData(array $badgeIds): array
    {
        $badges = EventBadge::whereIn('id', $badgeIds)
            ->with('registration.event')
            ->get();

        return $badges->map(fn ($badge) => $this->getBadgePrintData($badge))->toArray();
    }

    /**
     * Update badge information.
     */
    public function updateBadge(EventBadge $badge, array $data): EventBadge
    {
        $badge->update([
            'first_name' => $data['first_name'] ?? $badge->first_name,
            'last_name' => $data['last_name'] ?? $badge->last_name,
            'company' => $data['company'] ?? $badge->company,
            'job_title' => $data['job_title'] ?? $badge->job_title,
            'custom_fields' => $data['custom_fields'] ?? $badge->custom_fields,
            'template' => $data['template'] ?? $badge->template,
        ]);

        // Reset to generated status if already printed
        if ($badge->status === BadgeStatus::PRINTED) {
            $badge->update([
                'status' => BadgeStatus::GENERATED,
                'printed_at' => null,
                'printed_by' => null,
            ]);
        }

        return $badge->fresh();
    }

    /**
     * Generate a unique badge number.
     */
    protected function generateBadgeNumber(Event $event): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $event->title), 0, 3));
        $count = EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    /**
     * Find badge by QR data.
     */
    public function findByQRData(string $qrData): ?EventBadge
    {
        return EventBadge::where('qr_data', $qrData)
            ->whereNotIn('status', [BadgeStatus::LOST])
            ->with('registration')
            ->first();
    }
}
