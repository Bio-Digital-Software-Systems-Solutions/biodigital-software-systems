<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Sabre\VObject\Reader;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

class ICalendarService
{
    /**
     * Export a single appointment as an iCalendar string.
     */
    public function exportAppointment(Appointment $appointment): string
    {
        $appointment->loadMissing('organizer');

        return Calendar::create('AIG-App Rendez-vous')
            ->event($this->buildEvent($appointment))
            ->get();
    }

    /**
     * Export multiple appointments as a single iCalendar string.
     *
     * @param  Collection<int, Appointment>  $appointments
     */
    public function exportAppointments(Collection $appointments): string
    {
        $calendar = Calendar::create('AIG-App Rendez-vous');

        foreach ($appointments as $appointment) {
            $appointment->loadMissing('organizer');
            $calendar->event($this->buildEvent($appointment));
        }

        return $calendar->get();
    }

    /**
     * Parse an uploaded .ics file and return an array of appointment data.
     *
     * @return array<int, array{title: string, description: string|null, start_datetime: string, end_datetime: string, location: string|null}>
     */
    public function parseIcsFile(UploadedFile $file): array
    {
        $content = $file->getContent();
        $vcalendar = Reader::read($content);

        $events = [];

        if (! isset($vcalendar->VEVENT)) {
            return $events;
        }

        foreach ($vcalendar->VEVENT as $vevent) {
            $summary = (string) ($vevent->SUMMARY ?? '');
            $dtstart = $vevent->DTSTART ?? null;
            $dtend = $vevent->DTEND ?? null;
            // Skip events without required fields
            if ($summary === '') {
                continue;
            }
            if ($dtstart === null) {
                continue;
            }

            $startDateTime = Carbon::parse($dtstart->getDateTime());

            // If no DTEND, default to 1 hour after start
            $endDateTime = $dtend
                ? Carbon::parse($dtend->getDateTime())
                : $startDateTime->copy()->addHour();

            $events[] = [
                'title' => $summary,
                'description' => isset($vevent->DESCRIPTION) ? (string) $vevent->DESCRIPTION : null,
                'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
                'end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
                'location' => isset($vevent->LOCATION) ? (string) $vevent->LOCATION : null,
            ];
        }

        return $events;
    }

    /**
     * Generate a Google Calendar URL for a given appointment.
     */
    public function generateGoogleCalendarUrl(Appointment $appointment): string
    {
        $params = [
            'action' => 'TEMPLATE',
            'text' => $appointment->title,
            'dates' => $this->formatDateForGoogle($appointment->start_datetime).'/'.$this->formatDateForGoogle($appointment->end_datetime),
        ];

        if ($appointment->description) {
            $params['details'] = $appointment->description;
        }

        if ($appointment->location) {
            $params['location'] = $appointment->location;
        }

        return 'https://calendar.google.com/calendar/render?'.http_build_query($params);
    }

    /**
     * Generate an Outlook Web URL for a given appointment.
     */
    public function generateOutlookWebUrl(Appointment $appointment): string
    {
        $params = [
            'rru' => 'addevent',
            'subject' => $appointment->title,
            'startdt' => $appointment->start_datetime->toIso8601String(),
            'enddt' => $appointment->end_datetime->toIso8601String(),
            'allday' => 'false',
            'path' => '/calendar/action/compose',
        ];

        if ($appointment->description) {
            $params['body'] = $appointment->description;
        }

        if ($appointment->location) {
            $params['location'] = $appointment->location;
        }

        return 'https://outlook.live.com/calendar/0/action/compose?'.http_build_query($params);
    }

    /**
     * Build a VEVENT from an Appointment model.
     */
    private function buildEvent(Appointment $appointment): Event
    {
        $event = Event::create($appointment->title)
            ->uniqueIdentifier($appointment->uuid)
            ->startsAt($appointment->start_datetime)
            ->endsAt($appointment->end_datetime);

        if ($appointment->description) {
            $event->description($appointment->description);
        }

        if ($appointment->location) {
            $event->address($appointment->location);
        }

        if ($appointment->meeting_link) {
            $event->url($appointment->meeting_link);
        }

        if ($appointment->organizer && $appointment->organizer->email) {
            $event->organizer(
                $appointment->organizer->email,
                $appointment->organizer->first_name.' '.$appointment->organizer->last_name
            );
        }

        $event->status($this->mapStatus($appointment->status));

        return $event;
    }

    /**
     * Map appointment status to iCalendar event status.
     */
    private function mapStatus(string $status): EventStatus
    {
        return match ($status) {
            'confirmed', 'completed' => EventStatus::Confirmed,
            'cancelled' => EventStatus::Cancelled,
            default => EventStatus::Tentative,
        };
    }

    /**
     * Format a datetime for Google Calendar URL (YYYYMMDDTHHmmssZ).
     */
    private function formatDateForGoogle(Carbon $date): string
    {
        return $date->utc()->format('Ymd\THis\Z');
    }
}
