<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view groups')->only(['index', 'show']);
        $this->middleware('can:create groups')->only(['create', 'store']);
        $this->middleware('can:edit groups')->only(['edit', 'update', 'addMember', 'removeMember']);
        $this->middleware('can:delete groups')->only(['destroy']);
    }

    public function index()
    {
        $groups = Group::with(['leader'])
            ->withCount('users')
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->active();
                } elseif ($status === 'with_space') {
                    $query->withSpace();
                }
            })
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'filters' => request()->only(['status']),
        ]);
    }

    public function create()
    {
        $users = User::all();

        return Inertia::render('Groups/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:255|unique:groups',
            'max_members' => 'nullable|integer|min:1',
            'leader_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('groups', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to groups directory
            $validated['image'] = 'groups/' . $request->image;
        }

        $group = Group::create($validated);

        if ($validated['leader_id']) {
            $group->users()->attach($validated['leader_id'], [
                'joined_at' => now(),
            ]);
        }

        return redirect()->route('groups.index')
            ->with('success', 'Group created successfully.');
    }

    public function show(Group $group)
    {
        $group->load(['leader', 'users']);

        // Get all users not in the group
        $availableUsers = User::select('id', 'uuid', 'first_name', 'last_name', 'email')
            ->whereNotIn('id', $group->users->pluck('id'))
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name, // Uses the accessor
                    'email' => $user->email,
                ];
            });

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'uuid' => $group->uuid,
                'name' => $group->name,
                'code' => $group->code,
                'description' => $group->description,
                'max_members' => $group->max_members,
                'is_active' => $group->is_active,
                'leader' => $group->leader ? [
                    'id' => $group->leader->id,
                    'uuid' => $group->leader->uuid,
                    'name' => $group->leader->name,
                    'email' => $group->leader->email,
                ] : null,
                'users' => $group->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'joined_at' => $user->pivot->joined_at,
                    ];
                }),
                'members_count' => $group->users->count(),
                'can_join' => $group->canJoin(),
                'is_at_capacity' => $group->isAtCapacity(),
            ],
            'availableUsers' => $availableUsers,
            'canManage' => Auth::user()->can('edit groups'),
        ]);
    }

    public function edit(Group $group)
    {
        $group->load(['leader', 'users']);
        $users = User::all();

        return Inertia::render('Groups/Edit', [
            'group' => $group,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:255|unique:groups,code,'.$group->id,
            'max_members' => 'nullable|integer|min:1',
            'leader_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($group->image) {
                \Storage::disk('public')->delete($group->image);
            }
            $validated['image'] = $request->file('image')->store('groups', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            if ($group->image) {
                \Storage::disk('public')->delete($group->image);
            }
            // Image has already been uploaded via TUS to groups directory
            $validated['image'] = 'groups/' . $request->image;
        }

        $group->update($validated);

        return redirect()->route('groups.index')
            ->with('success', 'Group updated successfully.');
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return redirect()->route('groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    public function join(Group $group)
    {
        $user = Auth::user();

        if (! $group->canJoin()) {
            return back()->with('error', 'Cannot join this group.');
        }

        if ($group->isMember($user)) {
            return back()->with('error', 'You are already a member of this group.');
        }

        $group->users()->attach($user->id, [
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Successfully joined the group.');
    }

    public function leave(Group $group)
    {
        $user = Auth::user();

        if (! $group->isMember($user)) {
            return back()->with('error', 'You are not a member of this group.');
        }

        if ($group->isLeader($user)) {
            return back()->with('error', 'Group leaders cannot leave their groups.');
        }

        $group->users()->detach($user->id);

        return back()->with('success', 'Successfully left the group.');
    }

    public function addMember(Request $request, Group $group)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($group->isMember(User::find($validated['user_id']))) {
            return back()->with('error', 'User is already a member of this group.');
        }

        if ($group->isAtCapacity()) {
            return back()->with('error', 'Group is at capacity.');
        }

        $group->users()->attach($validated['user_id'], [
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Member added successfully.');
    }

    public function removeMember(Request $request, Group $group, User $user)
    {
        if (! $group->isMember($user)) {
            return back()->with('error', 'User is not a member of this group.');
        }

        if ($group->isLeader($user)) {
            return back()->with('error', 'Cannot remove the group leader.');
        }

        $group->users()->detach($user->id);

        return back()->with('success', 'Member removed successfully.');
    }
}
