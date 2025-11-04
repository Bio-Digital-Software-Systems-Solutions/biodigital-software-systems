<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view events');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir les événements publics
        if ($event->is_public) {
            return $user->can('view events');
        }

        // Les événements privés sont visibles par le créateur et les participants
        return $user->id === $event->user_id
            || $event->participants->contains($user)
            || $user->can('manage event participants');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create events');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        // Check if the event can be modified by this user
        if (!$event->canBeModifiedBy($user)) {
            return false;
        }

        // Seul le créateur ou quelqu'un avec permission 'edit events' peut modifier
        return $user->id === $event->user_id || $user->can('edit events');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        // Check if the event can be modified by this user
        if (!$event->canBeModifiedBy($user)) {
            return false;
        }

        // Seul le créateur ou quelqu'un avec permission 'delete events' peut supprimer
        return $user->id === $event->user_id || $user->can('delete events');
    }

    /**
     * Determine whether the user can participate in the event.
     */
    public function participate(User $user, Event $event): bool
    {
        // Check if participation changes are allowed for this event
        if (!$event->canAcceptParticipationChanges($user)) {
            return false;
        }

        // Pour les événements publics, tout utilisateur authentifié peut participer
        if ($event->is_public) {
            return true;
        }

        // Pour les événements privés, vérifier les permissions
        if (! $user->can('attend events')) {
            return false;
        }

        // Vérifier si l'utilisateur est déjà participant ou peut gérer les participants
        return $event->participants->contains($user) || $user->can('manage event participants');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->can('delete events');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->can('delete events');
    }
}
