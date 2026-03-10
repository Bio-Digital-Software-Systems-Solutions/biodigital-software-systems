<?php

namespace App\Policies;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatRoomPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('use chat');
    }

    /**
     * Determine whether the user can view/access the model.
     * Uses caching and random delays to prevent timing attacks.
     */
    public function view(User $user, ChatRoom $chatRoom): bool
    {
        $cacheKey = "user:{$user->id}:room:{$chatRoom->id}:participant";

        // Use cache to prevent timing attacks and improve performance
        $isParticipant = Cache::remember($cacheKey, 3600, fn() => $chatRoom->participants()->where('user_id', $user->id)->exists());

        // Add random delay (1-5ms) to mask timing differences
        usleep(random_int(1000, 5000));

        return $isParticipant;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('use chat');
    }

    /**
     * Determine whether the user can update the model.
     * Only the creator of a group chat can update it.
     */
    public function update(User $user, ChatRoom $chatRoom): bool
    {
        // For group chats, only the creator can update
        if ($chatRoom->type === 'group') {
            return $chatRoom->created_by === $user->id;
        }

        // Direct messages cannot be updated
        return false;
    }

    /**
     * Determine whether the user can delete/leave the model.
     */
    public function delete(User $user, ChatRoom $chatRoom): bool
    {
        // Users can leave rooms they are participants in
        $cacheKey = "user:{$user->id}:room:{$chatRoom->id}:participant";

        $isParticipant = Cache::remember($cacheKey, 3600, fn() => $chatRoom->participants()->where('user_id', $user->id)->exists());

        usleep(random_int(1000, 5000));

        return $isParticipant;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChatRoom $chatRoom): bool
    {
        return $chatRoom->created_by === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChatRoom $chatRoom): bool
    {
        return $chatRoom->created_by === $user->id;
    }

    /**
     * Determine whether the user can send messages in the chat room.
     * Uses same caching strategy as view() to prevent timing attacks.
     */
    public function sendMessage(User $user, ChatRoom $chatRoom): bool
    {
        return $this->view($user, $chatRoom);
    }

    /**
     * Clear participant cache when needed (e.g., after adding/removing participants).
     */
    public static function clearParticipantCache(int $userId, int $roomId): void
    {
        $cacheKey = "user:{$userId}:room:{$roomId}:participant";
        Cache::forget($cacheKey);
    }
}
