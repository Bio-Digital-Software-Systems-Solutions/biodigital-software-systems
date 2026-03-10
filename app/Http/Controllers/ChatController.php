<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:use chat');
    }

    /**
     * Display chat interface
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Get user's chat rooms
        $chatRooms = $user->chatRooms()
            ->with(['participants', 'lastMessage.sender'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get all users for creating new chats (exclude current user)
        $users = User::where('id', '!=', $user->id)
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                $user->full_name = $user->first_name.' '.$user->last_name;

                return $user;
            });

        return Inertia::render('Chat/Index', [
            'chatRooms' => $chatRooms,
            'users' => $users,
        ]);
    }

    /**
     * Create a new chat room
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required_if:type,group|nullable|string|max:255',
            'type' => 'required|in:direct,group',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        // Add current user to participants
        $participantIds = array_merge($validated['participant_ids'], [Auth::id()]);
        $participantIds = array_unique($participantIds);

        // For direct messages, check if room already exists
        if ($validated['type'] === 'direct' && count($participantIds) === 2) {
            $existingRoom = ChatRoom::where('type', 'direct')
                ->whereHas('participants', function ($query) use ($participantIds): void {
                    $query->whereIn('user_id', $participantIds);
                }, '=', 2)
                ->first();

            if ($existingRoom) {
                return response()->json(['room' => $existingRoom]);
            }
        }

        $room = ChatRoom::create([
            'name' => $validated['name'] ?? $this->generateRoomName($participantIds),
            'type' => $validated['type'],
            'created_by' => Auth::id(),
        ]);

        $room->participants()->attach($participantIds);
        $room->load(['participants', 'lastMessage.sender']);

        // Clear cache for all participants
        foreach ($participantIds as $participantId) {
            \App\Policies\ChatRoomPolicy::clearParticipantCache($participantId, $room->id);
        }

        return response()->json(['room' => $room]);
    }

    /**
     * Get messages for a chat room
     */
    public function getMessages(ChatRoom $room, Request $request): JsonResponse
    {
        // Check if user is participant using Policy
        $this->authorize('view', $room);

        $messages = $room->messages()
            ->with(['sender:id,first_name,last_name'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($message): \stdClass {
                $message->sender->full_name = $message->sender->first_name.' '.$message->sender->last_name;

                return $message;
            });

        // Mark messages as read
        $room->messages()
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, ChatRoom $room): JsonResponse
    {
        // Check if user is participant using Policy
        $this->authorize('sendMessage', $room);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        // Sanitize content to prevent XSS
        $sanitizedContent = strip_tags((string) $validated['content']);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => Auth::id(),
            'content' => $sanitizedContent,
            'is_read' => false,
        ]);

        // Update room's updated_at timestamp
        $room->touch();

        $message->load(['sender:id,first_name,last_name']);
        $message->sender->full_name = $message->sender->first_name.' '.$message->sender->last_name;

        return response()->json(['message' => $message]);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();

        $unreadCount = ChatMessage::whereHas('room.participants', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }

    /**
     * Leave a chat room
     */
    public function leaveRoom(ChatRoom $room): JsonResponse
    {
        $user = Auth::user();

        // Check if user is participant using Policy
        $this->authorize('delete', $room);

        $room->participants()->detach($user->id);

        // Clear cache after removing participant
        \App\Policies\ChatRoomPolicy::clearParticipantCache($user->id, $room->id);

        // If no participants left, delete the room
        if ($room->participants()->count() === 0) {
            $room->messages()->delete();
            $room->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Generate room name for direct messages
     */
    private function generateRoomName(array $participantIds): string
    {
        $participants = User::whereIn('id', $participantIds)
            ->where('id', '!=', Auth::id())
            ->get(['first_name', 'last_name']);

        if ($participants->count() === 1) {
            $participant = $participants->first();

            return $participant->first_name.' '.$participant->last_name;
        }

        return 'Groupe de discussion';
    }
}
