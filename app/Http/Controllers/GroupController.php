<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Group;
use App\Models\GroupTodo;
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
            ->when(request('status'), function ($query, $status): void {
                if ($status === 'active') {
                    $query->active();
                } elseif ($status === 'with_space') {
                    $query->withSpace();
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->appends(request()->query());

        return Inertia::render('Groups/Index', [
            'groups' => [
                'data' => $groups->items(),
                'links' => $groups->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $groups->currentPage(),
                    'last_page' => $groups->lastPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                    'from' => $groups->firstItem(),
                    'to' => $groups->lastItem(),
                ],
            ],
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
            $validated['image'] = 'groups/'.$request->image;
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

        $user = Auth::user();
        $canManage = $user->can('edit groups');

        // Get all users not in the group
        $availableUsers = User::select('id', 'uuid', 'first_name', 'last_name', 'email')
            ->whereNotIn('id', $group->users->pluck('id'))
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user): array => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        // Load meetings with appointments
        $meetings = $group->meetings()
            ->with([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                        ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($meeting): array {
                $appointment = $meeting->appointment;

                return [
                    'uuid' => $meeting->uuid,
                    'is_mandatory' => $meeting->is_mandatory,
                    'notify_all_members' => $meeting->notify_all_members,
                    'notes' => $meeting->notes,
                    'notified_at' => $meeting->notified_at?->toISOString(),
                    'created_at' => $meeting->created_at->toISOString(),
                    'creator' => $meeting->creator ? [
                        'id' => $meeting->creator->id,
                        'uuid' => $meeting->creator->uuid,
                        'name' => $meeting->creator->name,
                    ] : null,
                    'appointment' => $appointment ? [
                        'uuid' => $appointment->uuid,
                        'title' => $appointment->title,
                        'description' => $appointment->description,
                        'type' => $appointment->type,
                        'status' => $appointment->status,
                        'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                        'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                        'location' => $appointment->location,
                        'formatted_time_range' => $appointment->formatted_time_range,
                        'participants_count' => $appointment->participants_count,
                        'organizer' => $appointment->organizer ? [
                            'id' => $appointment->organizer->id,
                            'uuid' => $appointment->organizer->uuid,
                            'name' => $appointment->organizer->name,
                        ] : null,
                    ] : null,
                ];
            });

        // Load appointments (standalone, not from meetings)
        $appointmentIds = $group->meetings()->pluck('appointment_id');
        $appointments = Appointment::where('appointmentable_type', Group::class)
            ->where('appointmentable_id', $group->id)
            ->whereNotIn('id', $appointmentIds)
            ->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
            ->withCount('participants')
            ->orderBy('start_datetime')
            ->get()
            ->map(fn ($appointment): array => [
                'uuid' => $appointment->uuid,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'type' => $appointment->type,
                'status' => $appointment->status,
                'start_datetime' => $appointment->start_datetime->toISOString(),
                'end_datetime' => $appointment->end_datetime->toISOString(),
                'location' => $appointment->location,
                'formatted_time_range' => $appointment->formatted_time_range,
                'participants_count' => $appointment->participants_count,
                'organizer' => $appointment->organizer ? [
                    'id' => $appointment->organizer->id,
                    'uuid' => $appointment->organizer->uuid,
                    'name' => $appointment->organizer->name,
                ] : null,
            ]);

        // Load activities for planning tab
        $activities = $group->groupActivities()
            ->with(['assignee:id,uuid,first_name,last_name,email', 'creator:id,uuid,first_name,last_name,email'])
            ->orderBy('activity_date', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($activity): array => [
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'activity_date' => $activity->activity_date->format('Y-m-d'),
                'start_time' => $activity->start_time?->format('H:i'),
                'end_time' => $activity->end_time?->format('H:i'),
                'status' => $activity->status,
                'type' => $activity->type,
                'location' => $activity->location,
                'notes' => $activity->notes,
                'assignee' => $activity->assignee ? [
                    'id' => $activity->assignee->id,
                    'uuid' => $activity->assignee->uuid,
                    'name' => $activity->assignee->name,
                ] : null,
                'creator' => $activity->creator ? [
                    'id' => $activity->creator->id,
                    'uuid' => $activity->creator->uuid,
                    'name' => $activity->creator->name,
                ] : null,
                'created_at' => $activity->created_at->toISOString(),
            ]);

        // Compute statistics
        $statistics = $this->getGroupStatistics($group);

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
                'users' => $group->users->map(fn ($user): array => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'joined_at' => $user->pivot->joined_at,
                ]),
                'members_count' => $group->users->count(),
                'can_join' => $group->canJoin(),
                'is_at_capacity' => $group->isAtCapacity(),
            ],
            'availableUsers' => $availableUsers,
            'canManage' => $canManage,
            'meetings' => $meetings,
            'appointments' => $appointments,
            'activities' => $activities,
            'statistics' => $statistics,
            'visitorsCount' => rescue(fn () => $group->visitorVisits()->count(), 0, false),
            'pendingSuggestionsCount' => $canManage ? rescue(fn () => \App\Models\IntegrationSuggestion::whereHas('visitorVisit', function ($q) use ($group): void {
                $q->where('visitable_type', \App\Models\Group::class)
                    ->where('visitable_id', $group->id);
            })->pending()->count(), 0, false) : 0,
        ]);
    }

    protected function getGroupStatistics(Group $group): array
    {
        $todos = GroupTodo::with('assignee')->where('group_id', $group->id)->get();

        $total = $todos->count();
        $completed = $todos->where('status', 'completed')->count();
        $inProgress = $todos->where('status', 'in_progress')->count();
        $pending = $todos->where('status', 'pending')->count();
        $overdue = $todos->filter(fn ($todo) => $todo->isOverdue())->count();

        $byPriority = [
            'critical' => $todos->where('priority', 'critical')->count(),
            'high' => $todos->where('priority', 'high')->count(),
            'medium' => $todos->where('priority', 'medium')->count(),
            'low' => $todos->where('priority', 'low')->count(),
        ];

        // Velocity: completed tasks this month vs last month
        $completedThisMonth = GroupTodo::where('group_id', $group->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->startOfMonth())
            ->count();

        $completedLastMonth = GroupTodo::where('group_id', $group->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();

        $velocityChange = $completedLastMonth > 0
            ? (($completedThisMonth - $completedLastMonth) / $completedLastMonth) * 100
            : ($completedThisMonth > 0 ? 100 : 0);

        // Average completion time (days)
        $completedWithDates = $todos->where('status', 'completed')
            ->filter(fn ($t) => $t->completed_at && $t->created_at);
        $avgCompletionDays = $completedWithDates->count() > 0
            ? round($completedWithDates->avg(fn ($t) => $t->created_at->diffInDays($t->completed_at)), 1)
            : 0;

        // Tasks by member
        $tasksByMember = $todos->groupBy('assigned_to')->map(function ($memberTodos, $userId) {
            $user = $userId ? $memberTodos->first()->assignee : null;

            return [
                'uuid' => $user?->uuid,
                'name' => $user ? $user->name : 'Non assigné',
                'total' => $memberTodos->count(),
                'completed' => $memberTodos->where('status', 'completed')->count(),
                'in_progress' => $memberTodos->where('status', 'in_progress')->count(),
                'pending' => $memberTodos->where('status', 'pending')->count(),
                'overdue' => $memberTodos->filter(fn ($t) => $t->isOverdue())->count(),
                'completion_rate' => $memberTodos->count() > 0
                    ? round(($memberTodos->where('status', 'completed')->count() / $memberTodos->count()) * 100, 1)
                    : 0,
            ];
        })->values()->toArray();

        // Determine available years from group creation
        $groupCreatedYear = $group->created_at ? $group->created_at->year : now()->year;
        $currentYear = now()->year;
        $availableYears = range($groupCreatedYear, $currentYear);

        // Build task evolution for all granularities
        $taskEvolution = $this->buildTaskEvolution($group->id, $availableYears);

        // Preload users with pivot and visitor visits for growth calculations
        if (! $group->relationLoaded('users')) {
            $group->load('users');
        }
        if (! $group->relationLoaded('visitorVisits')) {
            $group->load('visitorVisits');
        }

        // Build member growth for all granularities
        $memberGrowth = $this->buildMemberGrowth($group, $availableYears);

        return [
            'members' => [
                'total' => $group->users->count(),
                'has_leader' => $group->leader_id !== null,
            ],
            'todos' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $pending,
                'overdue' => $overdue,
                'by_priority' => $byPriority,
            ],
            'performance' => [
                'collective' => [
                    'total_tasks' => $total,
                    'completed_tasks' => $completed,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'overdue_tasks' => $overdue,
                    'overdue_rate' => $total > 0 ? round(($overdue / $total) * 100, 1) : 0,
                    'velocity_this_month' => $completedThisMonth,
                    'velocity_last_month' => $completedLastMonth,
                    'velocity_change' => round($velocityChange, 1),
                    'avg_completion_days' => $avgCompletionDays,
                ],
            ],
            'tasks_by_member' => $tasksByMember,
            'member_distribution' => $this->buildMemberDistribution($group),
            'available_years' => $availableYears,
            'task_evolution' => $taskEvolution,
            'member_growth' => $memberGrowth,
        ];
    }

    protected function buildTaskEvolution(int $groupId, array $years): array
    {
        $result = [];
        $dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

        foreach ($years as $year) {
            $yearStart = \Carbon\Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = \Carbon\Carbon::create($year, 12, 31)->endOfYear();

            // Weekly: array of weeks, each week contains 7 days
            $weekly = [];
            $weekStart = $yearStart->copy()->startOfWeek();
            while ($weekStart->year <= $year) {
                if ($weekStart->isAfter(now())) {
                    break;
                }
                $weekDays = [];
                for ($d = 0; $d < 7; $d++) {
                    $day = $weekStart->copy()->addDays($d);
                    $dayEnd = $day->copy()->endOfDay();
                    $weekDays[] = $this->buildEvolutionPeriod($groupId, $day, $dayEnd, $dayNames[$d].' '.$day->format('d/m'));
                }
                $weekly[] = [
                    'week_number' => $weekStart->weekOfYear,
                    'label' => 'S'.$weekStart->weekOfYear,
                    'start_date' => $weekStart->format('d/m'),
                    'end_date' => $weekStart->copy()->addDays(6)->format('d/m'),
                    'days' => $weekDays,
                ];
                $weekStart->addWeek();
            }

            // Monthly: always 12 months
            $monthly = [];
            for ($m = 1; $m <= 12; $m++) {
                $start = \Carbon\Carbon::create($year, $m, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $monthly[] = $this->buildEvolutionPeriod($groupId, $start, $end, $start->translatedFormat('M'));
            }

            // Quarterly
            $quarterly = [];
            for ($q = 1; $q <= 4; $q++) {
                $start = \Carbon\Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfMonth();
                $end = $start->copy()->addMonths(2)->endOfMonth();
                $quarterly[] = $this->buildEvolutionPeriod($groupId, $start, $end, 'T'.$q);
            }

            // Semester
            $semester = [];
            for ($s = 1; $s <= 2; $s++) {
                $start = \Carbon\Carbon::create($year, ($s - 1) * 6 + 1, 1)->startOfMonth();
                $end = $start->copy()->addMonths(5)->endOfMonth();
                $semester[] = $this->buildEvolutionPeriod($groupId, $start, $end, 'S'.$s);
            }

            // Yearly
            $yearlyData = [$this->buildEvolutionPeriod($groupId, $yearStart, $yearEnd, (string) $year)];

            $result[$year] = [
                'weekly' => $weekly,
                'monthly' => $monthly,
                'quarterly' => $quarterly,
                'semester' => $semester,
                'yearly' => $yearlyData,
            ];
        }

        return $result;
    }

    protected function buildEvolutionPeriod(int $groupId, $start, $end, string $label): array
    {
        return [
            'label' => $label,
            'period' => $start->format('Y-m-d'),
            'created' => GroupTodo::where('group_id', $groupId)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'completed' => GroupTodo::where('group_id', $groupId)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])
                ->count(),
        ];
    }

    protected function buildMemberGrowth(Group $group, array $years): array
    {
        $result = [];
        $dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

        foreach ($years as $year) {
            $yearStart = \Carbon\Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = \Carbon\Carbon::create($year, 12, 31)->endOfYear();

            // Weekly: array of weeks, each with 7 days
            $weekly = [];
            $weekStart = $yearStart->copy()->startOfWeek();
            while ($weekStart->year <= $year) {
                if ($weekStart->isAfter(now())) {
                    break;
                }
                $weekDays = [];
                for ($d = 0; $d < 7; $d++) {
                    $day = $weekStart->copy()->addDays($d);
                    $dayEnd = $day->copy()->endOfDay();
                    $weekDays[] = $this->buildGrowthPeriod($group, $day, $dayEnd, $dayNames[$d].' '.$day->format('d/m'));
                }
                $weekly[] = [
                    'week_number' => $weekStart->weekOfYear,
                    'label' => 'S'.$weekStart->weekOfYear,
                    'start_date' => $weekStart->format('d/m'),
                    'end_date' => $weekStart->copy()->addDays(6)->format('d/m'),
                    'days' => $weekDays,
                ];
                $weekStart->addWeek();
            }

            // Monthly: always 12 months
            $monthly = [];
            for ($m = 1; $m <= 12; $m++) {
                $start = \Carbon\Carbon::create($year, $m, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $monthly[] = $this->buildGrowthPeriod($group, $start, $end, $start->translatedFormat('M'));
            }

            // Quarterly
            $quarterly = [];
            for ($q = 1; $q <= 4; $q++) {
                $start = \Carbon\Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfMonth();
                $end = $start->copy()->addMonths(2)->endOfMonth();
                $quarterly[] = $this->buildGrowthPeriod($group, $start, $end, 'T'.$q);
            }

            // Semester
            $semester = [];
            for ($s = 1; $s <= 2; $s++) {
                $start = \Carbon\Carbon::create($year, ($s - 1) * 6 + 1, 1)->startOfMonth();
                $end = $start->copy()->addMonths(5)->endOfMonth();
                $semester[] = $this->buildGrowthPeriod($group, $start, $end, 'S'.$s);
            }

            // Yearly
            $yearlyData = [$this->buildGrowthPeriod($group, $yearStart, $yearEnd, (string) $year)];

            $result[$year] = [
                'weekly' => $weekly,
                'monthly' => $monthly,
                'quarterly' => $quarterly,
                'semester' => $semester,
                'yearly' => $yearlyData,
            ];
        }

        return $result;
    }

    protected function buildGrowthPeriod(Group $group, $start, $end, string $label): array
    {
        $users = $group->users;
        $visits = $group->visitorVisits;

        return [
            'label' => $label,
            'period' => $start->format('Y-m-d'),
            'new_members' => $users->filter(fn ($u) => $u->pivot->joined_at !== null
                && $u->pivot->joined_at >= $start
                && $u->pivot->joined_at <= $end
            )->count(),
            'total_members' => $users->filter(fn ($u) => $u->pivot->joined_at !== null
                && $u->pivot->joined_at <= $end
            )->count(),
            'new_visitors' => $visits->filter(fn ($v) => $v->first_visited_at !== null
                && $v->first_visited_at >= $start
                && $v->first_visited_at <= $end
            )->count(),
            'total_visitors' => $visits->filter(fn ($v) => $v->first_visited_at !== null
                && $v->first_visited_at <= $end
            )->count(),
        ];
    }

    protected function buildMemberDistribution(Group $group): array
    {
        $users = $group->users;
        $visitors = $group->visitorVisits;

        // By status
        $byStatus = [
            'active_members' => $users->filter(fn ($u) => $u->is_active)->count(),
            'inactive_members' => $users->filter(fn ($u) => ! $u->is_active)->count(),
            'visitors' => $visitors->count(),
        ];

        // By gender (members + visitors combined)
        $memberGenders = $users->groupBy('gender')->map->count();
        $visitorGenders = $visitors->load('visitor')->pluck('visitor')->filter()->groupBy('gender')->map->count();

        $byGender = [
            'male' => ($memberGenders->get('male', 0)) + ($visitorGenders->get('male', 0)),
            'female' => ($memberGenders->get('female', 0)) + ($visitorGenders->get('female', 0)),
            'other' => ($memberGenders->get('other', 0)) + ($visitorGenders->get('other', 0)),
            'unknown' => ($memberGenders->get('', 0) + $memberGenders->get(null, 0))
                + ($visitorGenders->get('', 0) + $visitorGenders->get(null, 0)),
        ];

        // By age range (members + visitors combined)
        $ageRanges = [
            '0-17' => [0, 17],
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '56-65' => [56, 65],
            '65+' => [65, 999],
        ];

        $byAge = array_fill_keys(array_keys($ageRanges), 0);
        $byAge['unknown'] = 0;

        $allBirthDates = $users->pluck('birth_date')
            ->merge($visitors->load('visitor')->pluck('visitor')->filter()->pluck('date_of_birth'));

        foreach ($allBirthDates as $birthDate) {
            if (! $birthDate) {
                $byAge['unknown']++;

                continue;
            }
            $age = $birthDate->age;
            $placed = false;
            foreach ($ageRanges as $rangeLabel => [$min, $max]) {
                if ($age >= $min && $age <= $max) {
                    $byAge[$rangeLabel]++;
                    $placed = true;

                    break;
                }
            }
            if (! $placed) {
                $byAge['unknown']++;
            }
        }

        return [
            'by_status' => $byStatus,
            'by_gender' => $byGender,
            'by_age' => $byAge,
        ];
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
            $validated['image'] = 'groups/'.$request->image;
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
