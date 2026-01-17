<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use App\Notifications\AppointmentInvitation;
use App\Notifications\NewDirectMessageNotification;

class AppointmentNotificationService
{
    /**
     * Send complete invitation notification to a participant.
     * This includes:
     * 1. Standard notification/email via AppointmentInvitation
     * 2. System message in the Messages inbox
     * 3. Email notification about the new message
     */
    public function sendInvitationNotification(Appointment $appointment, User $participant, string $confirmationToken): void
    {
        // 1. Send the standard appointment invitation (email + database notification)
        $participant->notify(new AppointmentInvitation($appointment, $confirmationToken));

        // 2. Create system message in Messages inbox
        $this->createInvitationMessage($appointment, $participant, $confirmationToken);

        // 3. Create backup in chat system (for real-time notifications)
        $directRoom = $this->getOrCreateDirectRoom($appointment->organizer, $participant);
        $messageContent = $this->createInvitationMessageContent($appointment, $confirmationToken);
        $chatMessage = $this->sendDirectMessage($directRoom, $appointment->organizer, $messageContent);

        // 4. Send email notification about the new message
        $participant->notify(new NewDirectMessageNotification($chatMessage, $appointment->organizer));
    }

    /**
     * Get or create a direct chat room between organizer and participant.
     */
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

    /**
     * Generate a name for the direct room.
     */
    protected function generateDirectRoomName(User $organizer, User $participant): string
    {
        return "Message direct avec {$organizer->first_name} {$organizer->last_name}";
    }

    /**
     * Create invitation message in the Messages system.
     */
    protected function createInvitationMessage(Appointment $appointment, User $participant, string $confirmationToken): void
    {
        $subject = "🗓️ Invitation au rendez-vous : {$appointment->title}";
        $content = $this->createInvitationMessageContent($appointment, $confirmationToken);

        Message::create([
            'subject' => $subject,
            'content' => $content,
            'sender_id' => $appointment->organizer->id,
            'receiver_id' => $participant->id,
            'type' => 'system',
            'read_at' => null,
        ]);
    }

    /**
     * Create the invitation message content.
     */
    protected function createInvitationMessageContent(Appointment $appointment, string $confirmationToken): string
    {
        $startDate = \Carbon\Carbon::parse($appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($appointment->end_datetime);

        $confirmUrl = url("/appointments/{$appointment->uuid}/confirm/{$confirmationToken}");
        $declineUrl = url("/appointments/{$appointment->uuid}/decline/{$confirmationToken}");

        $message = '<p>🗓️ <strong>Invitation au rendez-vous</strong></p>';
        $message .= "<p><strong>{$appointment->title}</strong></p>";

        if ($appointment->description) {
            $message .= "<p>📝 <strong>Description :</strong> {$appointment->description}</p>";
        }

        $message .= "<p>📅 <strong>Date :</strong> {$startDate->format('d/m/Y')}<br>";
        $message .= "🕐 <strong>Heure :</strong> {$startDate->format('H:i')} - {$endDate->format('H:i')}</p>";

        if ($appointment->location) {
            $message .= "<p>📍 <strong>Lieu :</strong> {$appointment->location}</p>";
        }

        $message .= '<p>🏷️ <strong>Type :</strong> '.ucfirst($appointment->type).'</p>';

        // Add meeting link if present (online or hybrid meeting)
        if ($appointment->meeting_link && in_array($appointment->meeting_mode, ['online', 'hybrid'])) {
            $platformLabel = $this->getMeetingPlatformLabel($appointment->meeting_platform);
            $message .= "<p>🎥 <strong>Réunion en ligne ({$platformLabel}) :</strong><br>";
            $message .= "<a href=\"{$appointment->meeting_link}\">{$appointment->meeting_link}</a></p>";
        }

        $message .= '<p>⚡ <strong>Actions rapides :</strong><br>';
        $message .= "✅ <a href=\"{$confirmUrl}\">Confirmer</a><br>";
        $message .= "❌ <a href=\"{$declineUrl}\">Décliner</a></p>";
        $message .= '<p>Merci de répondre à cette invitation !</p>';

        return $message;
    }

    /**
     * Send a message in the direct chat room.
     */
    protected function sendDirectMessage(ChatRoom $room, User $sender, string $content): ChatMessage
    {
        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $sender->id,
            'content' => $content,
            'is_read' => false,
        ]);

        // Update room's updated_at timestamp
        $room->touch();

        return $message;
    }

    /**
     * Send notification for appointment updates.
     */
    public function sendUpdateNotification(Appointment $appointment, User $participant, string $action = 'updated'): void
    {
        // Create system message in Messages inbox
        $this->createUpdateMessage($appointment, $participant, $action);

        // Create backup in chat system
        $directRoom = $this->getOrCreateDirectRoom($appointment->organizer, $participant);
        $messageContent = $this->createUpdateMessageContent($appointment, $action);
        $chatMessage = $this->sendDirectMessage($directRoom, $appointment->organizer, $messageContent);

        // Send email notification about the message
        $participant->notify(new NewDirectMessageNotification($chatMessage, $appointment->organizer));
    }

    /**
     * Send confirmation message to organizer when appointment is created.
     */
    public function sendOrganizerConfirmation(Appointment $appointment): void
    {
        // Create system message in Messages inbox
        $this->createOrganizerConfirmationMessage($appointment);

        // Create backup in chat system
        $organizerRoom = $this->getOrCreateOrganizerRoom($appointment->organizer);
        $messageContent = $this->createOrganizerConfirmationMessageContent($appointment);
        $this->sendDirectMessage($organizerRoom, $appointment->organizer, $messageContent);
    }

    /**
     * Create update message in Messages system.
     */
    protected function createUpdateMessage(Appointment $appointment, User $participant, string $action): void
    {
        $actionLabels = [
            'updated' => '📝 Rendez-vous modifié',
            'confirmed' => '✅ Rendez-vous confirmé',
            'cancelled' => '❌ Rendez-vous annulé',
            'completed' => '🎉 Rendez-vous terminé',
        ];

        $subject = ($actionLabels[$action] ?? '📢 Mise à jour du rendez-vous')." : {$appointment->title}";
        $content = $this->createUpdateMessageContent($appointment, $action);

        Message::create([
            'subject' => $subject,
            'content' => $content,
            'sender_id' => $appointment->organizer->id,
            'receiver_id' => $participant->id,
            'type' => 'system',
            'read_at' => null,
        ]);
    }

    /**
     * Create update message content.
     */
    protected function createUpdateMessageContent(Appointment $appointment, string $action): string
    {
        $startDate = \Carbon\Carbon::parse($appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($appointment->end_datetime);

        $actionLabels = [
            'updated' => '📝 <strong>Rendez-vous modifié</strong>',
            'confirmed' => '✅ <strong>Rendez-vous confirmé</strong>',
            'cancelled' => '❌ <strong>Rendez-vous annulé</strong>',
            'completed' => '🎉 <strong>Rendez-vous terminé</strong>',
        ];

        $message = '<p>'.($actionLabels[$action] ?? '📢 <strong>Mise à jour du rendez-vous</strong>').'</p>';
        $message .= "<p><strong>{$appointment->title}</strong></p>";

        if ($action !== 'cancelled') {
            $message .= "<p>📅 <strong>Date :</strong> {$startDate->format('d/m/Y')}<br>";
            $message .= "🕐 <strong>Heure :</strong> {$startDate->format('H:i')} - {$endDate->format('H:i')}</p>";

            if ($appointment->location) {
                $message .= "<p>📍 <strong>Lieu :</strong> {$appointment->location}</p>";
            }

            // Add meeting link if present
            if ($appointment->meeting_link && in_array($appointment->meeting_mode, ['online', 'hybrid'])) {
                $platformLabel = $this->getMeetingPlatformLabel($appointment->meeting_platform);
                $message .= "<p>🎥 <strong>Réunion en ligne ({$platformLabel}) :</strong><br>";
                $message .= "<a href=\"{$appointment->meeting_link}\">{$appointment->meeting_link}</a></p>";
            }
        }

        $message .= '<p>📄 <a href="'.route('appointments.show', $appointment->uuid).'">Consultez le détail</a></p>';

        return $message;
    }

    /**
     * Get or create organizer's personal notification room.
     */
    protected function getOrCreateOrganizerRoom(User $organizer): ChatRoom
    {
        // Look for existing personal notification room
        $existingRoom = ChatRoom::where('type', 'group')
            ->where('name', 'Mes rendez-vous')
            ->whereHas('participants', function ($query) use ($organizer) {
                $query->where('user_id', $organizer->id);
            }, '=', 1)
            ->first();

        if ($existingRoom) {
            return $existingRoom;
        }

        // Create personal notification room
        $room = ChatRoom::create([
            'name' => 'Mes rendez-vous',
            'type' => 'group',
            'created_by' => $organizer->id,
        ]);

        $room->participants()->sync([$organizer->id]);

        return $room;
    }

    /**
     * Create organizer confirmation message in Messages system.
     */
    protected function createOrganizerConfirmationMessage(Appointment $appointment): void
    {
        $subject = "✅ Confirmation de création : {$appointment->title}";
        $content = $this->createOrganizerConfirmationMessageContent($appointment);

        Message::create([
            'subject' => $subject,
            'content' => $content,
            'sender_id' => $appointment->organizer->id,
            'receiver_id' => $appointment->organizer->id,
            'type' => 'system',
            'read_at' => null,
        ]);
    }

    /**
     * Create organizer confirmation message content.
     */
    protected function createOrganizerConfirmationMessageContent(Appointment $appointment): string
    {
        $startDate = \Carbon\Carbon::parse($appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($appointment->end_datetime);

        $message = '<p>✅ <strong>Rendez-vous créé avec succès</strong></p>';
        $message .= "<p>📝 <strong>{$appointment->title}</strong></p>";

        if ($appointment->description) {
            $message .= "<p>📋 <strong>Description :</strong> {$appointment->description}</p>";
        }

        $message .= "<p>📅 <strong>Date :</strong> {$startDate->format('d/m/Y')}<br>";
        $message .= "🕐 <strong>Heure :</strong> {$startDate->format('H:i')} - {$endDate->format('H:i')}<br>";
        $message .= '⏱️ <strong>Durée :</strong> '.$startDate->diffInMinutes($endDate).' minutes</p>';

        if ($appointment->location) {
            $message .= "<p>📍 <strong>Lieu :</strong> {$appointment->location}</p>";
        }

        $message .= '<p>🏷️ <strong>Type :</strong> '.ucfirst($appointment->type).'<br>';
        $message .= '👁️ <strong>Visibilité :</strong> '.ucfirst($appointment->visibility).'</p>';

        // Add participants info
        $participantsCount = $appointment->participants()->count();
        if ($participantsCount > 0) {
            $message .= "<p>👥 <strong>Participants :</strong> {$participantsCount} invité(s)</p>";
        }

        $message .= '<p>📄 <strong>Détails :</strong> <a href="'.route('appointments.show', $appointment->uuid).'">Voir le rendez-vous</a><br>';
        $message .= '✏️ <strong>Modifier :</strong> <a href="'.route('appointments.edit', $appointment->uuid).'">Modifier le rendez-vous</a></p>';

        $message .= '<p>Votre rendez-vous a été créé et les invitations ont été envoyées aux participants.</p>';

        return $message;
    }

    /**
     * Get human-readable meeting platform label.
     */
    protected function getMeetingPlatformLabel(?string $platform): string
    {
        return match ($platform) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            'ms_teams' => 'Microsoft Teams',
            'other' => 'Autre',
            default => 'Visioconférence',
        };
    }
}
