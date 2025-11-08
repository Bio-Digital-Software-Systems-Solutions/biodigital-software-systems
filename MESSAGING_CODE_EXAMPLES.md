# Code Examples - Messaging & Pastoral Care System

## 1. CHAT SYSTEM EXAMPLES

### Creating a Direct Message Room
```php
// From ChatController::createRoom()
$chatMessage = ChatRoom::create([
    'name' => "Direct message with {$firstUser->name}",
    'type' => 'direct',
    'created_by' => auth()->id(),
]);
$room->participants()->attach([user1_id, user2_id]);
```

### Sending a Chat Message
```php
// From ChatController::sendMessage()
$message = ChatMessage::create([
    'room_id' => $room->id,
    'sender_id' => auth()->id(),
    'content' => strip_tags($validated['content']), // XSS Protection
    'is_read' => false,
]);
```

### Marking Messages as Read
```php
// From ChatController::getMessages()
$room->messages()
    ->where('sender_id', '!=', auth()->id())
    ->where('is_read', false)
    ->update(['is_read' => true]);
```

### Getting Unread Message Count
```php
// From ChatController::getUnreadCount()
$unreadCount = ChatMessage::whereHas('room.participants', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})
    ->where('sender_id', '!=', $user->id)
    ->where('is_read', false)
    ->count();
```

---

## 2. PASTORAL CARE EXAMPLES

### Checking Time Slot Availability
```php
// Static method on PastoralCare model
$isAvailable = PastoralCare::isTimeSlotAvailable(
    $pastorId,
    $appointmentDateTime,
    $durationMinutes = 60,
    $excludeId = null
);
```

### Getting Available Time Slots
```php
// From PastoralCareController::getAvailableSlots()
$slots = PastoralCare::getAvailableTimeSlots(
    $request->user()->id,
    $validated['date'],
    $validated['duration'] ?? 60
);
// Returns array like: ['09:00', '09:30', '10:00', ...]
```

### Creating a Pastoral Care Appointment
```php
// From PastoralCareController::store()
$appointmentDateTime = Carbon::parse($validated['appointment_date'] . ' ' . $validated['appointment_time']);

$appointment = PastoralCare::create([
    'pastor_id' => $pastorId,
    'client_name' => $validated['client_name'],
    'client_email' => $validated['client_email'],
    'appointment_date' => $validated['appointment_date'],
    'appointment_time' => $appointmentDateTime,
    'duration_minutes' => $validated['duration_minutes'],
    'location_type' => $validated['location_type'],
    'zoom_link' => $validated['zoom_link'],
    'notes' => $validated['notes'],
    'status' => 'pending',
]);
```

### Confirming an Appointment
```php
// From PastoralCare model
public function confirm()
{
    if (!$this->can_be_confirmed) {
        throw new \Exception('This appointment cannot be confirmed.');
    }

    $this->update([
        'status' => 'confirmed',
        'confirmation_sent_at' => now(),
    ]);

    return $this;
}
```

### Cancelling an Appointment
```php
public function cancel($reason = null)
{
    if (!$this->can_be_cancelled) {
        throw new \Exception('This appointment cannot be cancelled.');
    }

    $this->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'cancellation_reason' => $reason,
    ]);

    return $this;
}
```

### Checking if Appointment is Upcoming
```php
// From PastoralCare model - Accessor
public function getIsUpcomingAttribute()
{
    return $this->appointment_date >= now()->toDateString() &&
           in_array($this->status, ['pending', 'confirmed']);
}

// Usage:
if ($appointment->is_upcoming) {
    // Show reminder UI
}
```

---

## 3. EMAIL NOTIFICATION EXAMPLES

### Dispatching a Pastoral Care Email
```php
// How to send email (not currently wired in controller)
use App\Mail\PastoralCareAppointmentConfirmation;
use Illuminate\Support\Facades\Mail;

Mail::to($appointment->client_email)
    ->send(new PastoralCareAppointmentConfirmation($appointment));
```

### Queued Email Sending
```php
// All Mail classes implement ShouldQueue for async processing
class PastoralCareAppointmentReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    // Will be automatically queued when dispatched
    public function __construct(public PastoralCare $appointment)
    {
        $this->appointment->load(['pastor']);
    }
}
```

### Email Context Setup
```php
public function content(): Content
{
    return new Content(
        markdown: 'emails.pastoral-care.appointment-confirmation',
        with: [
            'appointment' => $this->appointment,
            'pastor' => $this->appointment->pastor,
            'confirmUrl' => route('pastoral-care.public.confirm', 
                ['uuid' => $this->appointment->uuid]),
            'cancelUrl' => route('pastoral-care.public.cancel', 
                ['uuid' => $this->appointment->uuid]),
            'churchName' => 'ICC Munich',
            'churchEmail' => 'info@icc-munich.de',
            'churchPhone' => '+49 89 123456789',
        ],
    );
}
```

---

## 4. NOTIFICATION SYSTEM EXAMPLES

### Sending Database + Email Notification
```php
// From NewDirectMessageNotification
public function via(object $notifiable): array
{
    return ['mail', 'database']; // Send via both channels
}

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject("Message from {$this->sender->full_name}")
        ->action('Open Chat', route('chat.index'));
}

public function toDatabase(object $notifiable): array
{
    return [
        'type' => 'new_message',
        'title' => "New message from {$this->sender->full_name}",
        'sender_id' => $this->sender->id,
        'chat_message_id' => $this->chatMessage->id,
        'room_id' => $this->chatMessage->room_id,
        'action_url' => route('chat.index'),
    ];
}
```

---

## 5. APPOINTMENT NOTIFICATION SERVICE EXAMPLES

### Multi-Channel Invitation Notification
```php
// From AppointmentNotificationService
public function sendInvitationNotification(Appointment $appointment, User $participant, string $confirmationToken): void
{
    // 1. Standard notification
    $participant->notify(new AppointmentInvitation($appointment, $confirmationToken));

    // 2. System message in Messages inbox
    $this->createInvitationMessage($appointment, $participant, $confirmationToken);

    // 3. Chat room message
    $directRoom = $this->getOrCreateDirectRoom($appointment->organizer, $participant);
    $messageContent = $this->createInvitationMessageContent($appointment, $confirmationToken);
    $chatMessage = $this->sendDirectMessage($directRoom, $appointment->organizer, $messageContent);

    // 4. Email notification about the chat message
    $participant->notify(new NewDirectMessageNotification($chatMessage, $appointment->organizer));
}
```

### Get or Create Direct Room
```php
protected function getOrCreateDirectRoom(User $organizer, User $participant): ChatRoom
{
    $participantIds = [$organizer->id, $participant->id];
    sort($participantIds);

    // Check if direct room already exists
    $existingRoom = ChatRoom::where('type', 'direct')
        ->withCount('participants')
        ->having('participants_count', '=', 2)
        ->whereHas('participants', function ($query) use ($participantIds) {
            $query->where('user_id', $participantIds[0]);
        })
        ->whereHas('participants', function ($query) use ($participantIds) {
            $query->where('user_id', $participantIds[1]);
        })
        ->first();

    if ($existingRoom) {
        return $existingRoom;
    }

    // Create new direct room
    $room = ChatRoom::create([
        'name' => $this->generateDirectRoomName($organizer, $participant),
        'type' => 'direct',
        'created_by' => $organizer->id,
    ]);

    $room->participants()->sync($participantIds);

    return $room;
}
```

---

## 6. DATABASE QUERIES

### Get Pastor's Appointments with Scopes
```php
// Using scopes from PastoralCare model
$upcomingAppointments = PastoralCare::forPastor($pastorId)
    ->upcoming()
    ->orderBy('appointment_date')
    ->orderBy('appointment_time')
    ->get();

// Get confirmed appointments for next week
$weekAppointments = PastoralCare::forPastor($pastorId)
    ->betweenDates(now()->toDateString(), now()->addWeek()->toDateString())
    ->confirmed()
    ->get();
```

### Finding Unread Messages
```php
// From Message model scopes
$unreadMessages = Message::where('receiver_id', auth()->id())
    ->unread()
    ->orderBy('created_at', 'desc')
    ->get();
```

### Getting Chat Room Messages
```php
// From ChatController::getMessages()
$messages = $room->messages()
    ->with(['sender:id,first_name,last_name'])
    ->latest()
    ->take(50)
    ->get()
    ->reverse()
    ->values();
```

---

## 7. CONFIGURATION USAGE

### Accessing Pastoral Care Config
```php
// Get all settings
$config = config('pastoral_care');

// Get specific settings
$defaultDuration = config('pastoral_care.appointments.default_duration'); // 60
$businessHours = config('pastoral_care.business_hours.monday');
$reminderHours = config('pastoral_care.notifications.reminders.send_at_hours_before'); // 24
$maxApptsPerMonth = config('pastoral_care.appointments.max_appointments_per_user_per_month'); // 4
```

---

## 8. BLADE EMAIL TEMPLATE EXAMPLE

```blade
<x-mail::message>
# Confirmation de votre rendez-vous

Bonjour {{ $appointment->client_name }},

## Détails du rendez-vous

**Date :** {{ $appointment->appointment_date->format('d/m/Y') }}
**Heure :** {{ $appointment->appointment_time->format('H:i') }}
**Durée :** {{ $appointment->duration_minutes }} minutes
**Pasteur :** {{ $pastor->first_name }} {{ $pastor->last_name }}

@if($appointment->zoom_link)
**Lien Zoom :** [{{ $appointment->zoom_link }}]({{ $appointment->zoom_link }})
@endif

<x-mail::button :url="$confirmUrl" color="success">
Confirmer
</x-mail::button>

<x-mail::button :url="$cancelUrl" color="error">
Annuler
</x-mail::button>

{{ $churchName }}
</x-mail::message>
```

---

## 9. PERMISSION CHECKS

### In Controller
```php
// From PastoralCareController constructor
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('can:view pastoral care')->only(['index', 'show']);
    $this->middleware('can:create pastoral care')->only(['create', 'store']);
    $this->middleware('can:edit pastoral care')->only(['edit', 'update']);
    $this->middleware('can:delete pastoral care')->only(['destroy']);
}
```

### In Model
```php
// From PastoralCarePolicy
public function view(User $user, PastoralCare $pastoralCare): bool
{
    return $user->can('view pastoral care') ||
           $user->id === $pastoralCare->pastor_id ||
           $user->hasRole(['admin', 'SuperAdmin']);
}
```

### In Blade
```blade
@can('create pastoral care')
    <a href="{{ route('pastoral-care.create') }}">Create Appointment</a>
@endcan
```

---

## 10. ACTIVITY LOGGING

### Automatic Logging
```php
// All models with LogsActivity trait are tracked automatically
class ChatMessage extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()           // Log only fillable attributes
            ->logOnlyDirty()          // Log only changed attributes
            ->dontSubmitEmptyLogs();  // Don't log if nothing changed
    }
}

// Results in activity records with:
// - log_name: default
// - description: created, updated, deleted
// - subject_type: App\Models\ChatMessage
// - subject_id: message id
// - causer_type: App\Models\User
// - causer_id: user id
// - properties: old & new values
```

