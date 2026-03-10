<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the total count of unread notifications (chat messages + system messages).
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Count unread chat messages from rooms the user is in
        $unreadChatMessages = ChatMessage::whereHas('room.participants', function ($query) use ($user): void {
            $query->where('users.id', $user->id);
        })
            ->where('sender_id', '!=', $user->id)
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->count();

        // Count unread system messages
        $unreadSystemMessages = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $totalUnread = $unreadChatMessages + $unreadSystemMessages;

        return response()->json([
            'count' => $totalUnread,
            'chat_messages' => $unreadChatMessages,
            'system_messages' => $unreadSystemMessages,
        ]);
    }
}
