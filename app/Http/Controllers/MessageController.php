<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view messages')->only(['index', 'show']);
        $this->middleware('can:create messages')->only(['create', 'store']);
        $this->middleware('can:edit messages')->only(['edit', 'update']);
        $this->middleware('can:delete messages')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $mailbox = $request->get('mailbox', 'inbox');

        // Get all messages for the user based on mailbox
        $query = Message::with(['sender', 'receiver']);

        if ($mailbox === 'sent') {
            // Show only sent messages
            $query->where('sender_id', $userId);
        } else {
            // Show only received messages (inbox)
            $query->where('receiver_id', $userId);
        }

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread()->where('receiver_id', $userId);
            } elseif ($request->status === 'read') {
                $query->read()->where('receiver_id', $userId);
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%'.$request->search.'%')
                    ->orWhere('content', 'like', '%'.$request->search.'%');
            });
        }

        $allMessages = $query->latest()->get();

        // Group sent messages by subject, content, and created_at to consolidate multiple recipients
        $groupedMessages = collect();
        $processedSentIds = [];

        if ($mailbox === 'sent') {
            // For sent messages, group by subject/content/timestamp
            foreach ($allMessages as $message) {
                if (!in_array($message->id, $processedSentIds)) {
                    $message->is_sent = true;

                    // Find all messages with same subject, content, and timestamp (sent in the same batch)
                    $relatedMessages = $allMessages->filter(function ($m) use ($message) {
                        return $m->subject === $message->subject
                            && $m->content === $message->content
                            && abs(strtotime($m->created_at) - strtotime($message->created_at)) < 5; // Within 5 seconds
                    });

                    // Collect all recipients
                    $recipients = $relatedMessages->map(function ($m) {
                        return [
                            'id' => $m->receiver->id,
                            'name' => $m->receiver->first_name . ' ' . $m->receiver->last_name,
                            'email' => $m->receiver->email,
                        ];
                    })->unique('id')->values();

                    $message->all_recipients = $recipients;
                    $message->recipients_count = $recipients->count();

                    // Get CC and BCC from the first message (they should be the same for all in the batch)
                    $message->cc_list = $message->cc_recipients ?? [];
                    $message->bcc_list = $message->bcc_recipients ?? [];

                    // Mark these message IDs as processed
                    foreach ($relatedMessages as $rm) {
                        $processedSentIds[] = $rm->id;
                    }

                    $groupedMessages->push($message);
                }
            }
        } else {
            // For inbox, just show received messages
            foreach ($allMessages as $message) {
                $message->is_sent = false;
                $message->all_recipients = null;
                $groupedMessages->push($message);
            }
        }

        // Paginate the grouped messages
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $total = $groupedMessages->count();
        $messages = $groupedMessages->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedMessages = new \Illuminate\Pagination\LengthAwarePaginator(
            $messages,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Messages/Index', [
            'messages' => $paginatedMessages,
            'filters' => $request->only(['type', 'status', 'search', 'mailbox']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $users = User::where('id', '!=', Auth::id())
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        $data = [
            'users' => $users,
        ];

        // Handle reply
        if ($request->has('reply_to')) {
            $originalMessage = Message::with(['sender', 'receiver'])->findOrFail($request->reply_to);

            // Check if user is authorized to reply
            if ($originalMessage->receiver_id !== Auth::id()) {
                abort(403, 'Unauthorized to reply to this message.');
            }

            $data['reply_to'] = $request->reply_to;
            $data['receiver_id'] = $request->receiver_id;
            $data['subject'] = $request->subject;
            $data['original_message'] = $originalMessage;
        }

        // Handle forward
        if ($request->has('forward')) {
            $originalMessage = Message::with(['sender', 'receiver'])->findOrFail($request->forward);

            // Check if user is authorized to forward
            if ($originalMessage->receiver_id !== Auth::id() && $originalMessage->sender_id !== Auth::id()) {
                abort(403, 'Unauthorized to forward this message.');
            }

            $data['forward'] = $request->forward;
            $data['subject'] = $request->subject;
            $data['original_message'] = $originalMessage;
        }

        return Inertia::render('Messages/Create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'recipient_type' => 'required|in:user,department',
            'recipient_id' => 'required|integer',
            'cc_recipients' => 'nullable|array',
            'cc_recipients.*' => 'integer|exists:users,id',
            'bcc_recipients' => 'nullable|array',
            'bcc_recipients.*' => 'integer|exists:users,id',
            'type' => 'required|in:direct,broadcast,system',
        ]);

        $ccRecipients = $validated['cc_recipients'] ?? [];
        $bccRecipients = $validated['bcc_recipients'] ?? [];

        // Verify recipient exists
        if ($validated['recipient_type'] === 'user') {
            $user = User::findOrFail($validated['recipient_id']);

            // Create main message
            $message = Message::create([
                'subject' => $validated['subject'] ?? null,
                'content' => $validated['content'],
                'sender_id' => Auth::id(),
                'receiver_id' => $validated['recipient_id'],
                'recipient_type' => 'user',
                'department_id' => null,
                'cc_recipients' => $ccRecipients,
                'bcc_recipients' => $bccRecipients,
                'type' => $validated['type'],
            ]);

            // Send copies to CC recipients
            foreach ($ccRecipients as $ccUserId) {
                if ($ccUserId === Auth::id() || $ccUserId === $validated['recipient_id']) {
                    continue; // Skip sender and main recipient
                }

                Message::create([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                    'sender_id' => Auth::id(),
                    'receiver_id' => $ccUserId,
                    'recipient_type' => 'user',
                    'department_id' => null,
                    'cc_recipients' => $ccRecipients,
                    'bcc_recipients' => [], // Don't expose BCC to CC recipients
                    'type' => 'direct',
                ]);
            }

            // Send copies to BCC recipients
            foreach ($bccRecipients as $bccUserId) {
                if ($bccUserId === Auth::id() || $bccUserId === $validated['recipient_id'] || in_array($bccUserId, $ccRecipients)) {
                    continue; // Skip sender, main recipient, and CC recipients
                }

                Message::create([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                    'sender_id' => Auth::id(),
                    'receiver_id' => $bccUserId,
                    'recipient_type' => 'user',
                    'department_id' => null,
                    'cc_recipients' => [], // Don't expose CC to BCC recipients
                    'bcc_recipients' => [], // Don't expose BCC to other BCC recipients
                    'type' => 'direct',
                ]);
            }
        } else {
            // Department message
            $department = Department::findOrFail($validated['recipient_id']);

            // Create a broadcast message for each member of the department
            $departmentUsers = $department->users;

            foreach ($departmentUsers as $user) {
                // Don't send to self
                if ($user->id === Auth::id()) {
                    continue;
                }

                Message::create([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                    'sender_id' => Auth::id(),
                    'receiver_id' => $user->id,
                    'recipient_type' => 'department',
                    'department_id' => $department->id,
                    'cc_recipients' => $ccRecipients,
                    'bcc_recipients' => $bccRecipients,
                    'type' => 'broadcast',
                ]);
            }

            // Also send to CC recipients (if not already in department)
            foreach ($ccRecipients as $ccUserId) {
                if ($ccUserId === Auth::id() || $departmentUsers->contains('id', $ccUserId)) {
                    continue;
                }

                Message::create([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                    'sender_id' => Auth::id(),
                    'receiver_id' => $ccUserId,
                    'recipient_type' => 'user',
                    'department_id' => null,
                    'cc_recipients' => $ccRecipients,
                    'bcc_recipients' => [],
                    'type' => 'direct',
                ]);
            }

            // Send to BCC recipients (if not already in department or CC)
            foreach ($bccRecipients as $bccUserId) {
                if ($bccUserId === Auth::id() || $departmentUsers->contains('id', $bccUserId) || in_array($bccUserId, $ccRecipients)) {
                    continue;
                }

                Message::create([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                    'sender_id' => Auth::id(),
                    'receiver_id' => $bccUserId,
                    'recipient_type' => 'user',
                    'department_id' => null,
                    'cc_recipients' => [],
                    'bcc_recipients' => [],
                    'type' => 'direct',
                ]);
            }
        }

        return redirect()->route('messages.index')
            ->with('message', 'Message sent successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message): Response
    {
        // Check if user can view this message
        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            abort(403, 'Unauthorized to view this message.');
        }

        $message->load(['sender', 'receiver']);

        // Mark as read if user is the receiver
        if ($message->receiver_id === Auth::id() && ! $message->isRead()) {
            $message->markAsRead();
        }

        return Inertia::render('Messages/Show', [
            'message' => $message,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Message $message): Response
    {
        // Only sender can edit their own messages
        if ($message->sender_id !== Auth::id()) {
            abort(403, 'Unauthorized to edit this message.');
        }

        $users = User::where('id', '!=', Auth::id())
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        return Inertia::render('Messages/Edit', [
            'message' => $message,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Message $message): RedirectResponse
    {
        // Only sender can update their own messages
        if ($message->sender_id !== Auth::id()) {
            abort(403, 'Unauthorized to update this message.');
        }

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|in:direct,broadcast,system',
        ]);

        $message->update($validated);

        return redirect()->route('messages.index')
            ->with('message', 'Message updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message): RedirectResponse
    {
        // Only sender can delete their own messages
        if ($message->sender_id !== Auth::id()) {
            abort(403, 'Unauthorized to delete this message.');
        }

        $message->delete();

        return redirect()->route('messages.index')
            ->with('message', 'Message deleted successfully.');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Message $message): RedirectResponse
    {
        // Only receiver can mark message as read
        if ($message->receiver_id !== Auth::id()) {
            abort(403, 'Unauthorized to mark this message as read.');
        }

        $message->markAsRead();

        return back()->with('message', 'Message marked as read.');
    }

    /**
     * Get unread message count for current user
     */
    public function unreadCount(): array
    {
        $count = Message::where('receiver_id', Auth::id())
            ->unread()
            ->count();

        return ['count' => $count];
    }

    /**
     * Search for recipients (users and departments)
     */
    public function searchRecipients(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $results = [];

        // Search users (excluding current user)
        if ($search) {
            $users = User::where('id', '!=', Auth::id())
                ->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user',
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'label' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')',
                    ];
                });

            $results = array_merge($results, $users->toArray());
        } else {
            // If no search, show recent users
            $users = User::where('id', '!=', Auth::id())
                ->select('id', 'first_name', 'last_name', 'email')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user',
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'label' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')',
                    ];
                });

            $results = array_merge($results, $users->toArray());
        }

        // Search departments
        if ($search) {
            $departments = Department::where('is_active', true)
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->withCount('users')
                ->limit(10)
                ->get()
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'type' => 'department',
                        'name' => $dept->name,
                        'code' => $dept->code,
                        'users_count' => $dept->users_count,
                        'label' => $dept->name . ' (' . $dept->users_count . ' members)',
                    ];
                });

            $results = array_merge($results, $departments->toArray());
        } else {
            // If no search, show all departments
            $departments = Department::where('is_active', true)
                ->withCount('users')
                ->limit(10)
                ->get()
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'type' => 'department',
                        'name' => $dept->name,
                        'code' => $dept->code,
                        'users_count' => $dept->users_count,
                        'label' => $dept->name . ' (' . $dept->users_count . ' members)',
                    ];
                });

            $results = array_merge($results, $departments->toArray());
        }

        return response()->json($results);
    }
}
