<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    /**
     * Display a listing of all activities.
     */
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // Filter by type/log_name
        if ($request->filled('type')) {
            $query->where('log_name', $request->type);
        }

        // Filter by causer (user who performed the action)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                ->where('causer_type', \App\Models\User::class);
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $activities = $query->paginate(20)->through($this->formatActivity(...));

        // Get unique log names for filter dropdown
        $logNames = Activity::distinct('log_name')
            ->whereNotNull('log_name')
            ->pluck('log_name')
            ->sort()
            ->values();

        // Get stats
        $stats = $this->getActivityStats();

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'logNames' => $logNames,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type,
                'causer_id' => $request->causer_id,
                'from' => $request->from,
                'to' => $request->to,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Format an activity for the frontend.
     */
    private function formatActivity(Activity $activity): array
    {
        $subjectName = $this->getSubjectName($activity);
        $icon = $this->getActivityIcon($activity);
        $url = $this->getActivityUrl($activity);

        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event ?? 'default',
            'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'subject_name' => $subjectName,
            'subject_id' => $activity->subject_id,
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
                'avatar' => $activity->causer->avatar_url ?? null,
            ] : null,
            'properties' => $activity->properties ? $activity->properties->toArray() : [],
            'icon' => $icon,
            'url' => $url,
            'created_at' => $activity->created_at->toISOString(),
            'time_ago' => $activity->created_at->diffForHumans(),
        ];
    }

    /**
     * Get the subject name from the activity.
     */
    private function getSubjectName(Activity $activity): ?string
    {
        if (!$activity->subject) {
            // Try to get from properties if subject was deleted
            $attributes = $activity->properties ? $activity->properties->get('attributes', []) : [];
            return $attributes['title'] ?? $attributes['name'] ?? $attributes['subject'] ?? null;
        }

        // Try common name attributes
        return $activity->subject->title
            ?? $activity->subject->name
            ?? $activity->subject->subject
            ?? null;
    }

    /**
     * Get icon for the activity based on subject type.
     */
    private function getActivityIcon(Activity $activity): string
    {
        $subjectType = $activity->subject_type ? class_basename($activity->subject_type) : null;

        $iconMap = [
            'Event' => 'CalendarDaysIcon',
            'Article' => 'PencilSquareIcon',
            'Book' => 'BookOpenIcon',
            'BookRental' => 'BookOpenIcon',
            'ChatMessage' => 'ChatBubbleLeftRightIcon',
            'ChatRoom' => 'ChatBubbleLeftRightIcon',
            'User' => 'UserIcon',
            'Department' => 'BuildingOfficeIcon',
            'DepartmentDocument' => 'DocumentIcon',
            'DepartmentDocumentCategory' => 'FolderIcon',
            'Task' => 'ClipboardDocumentListIcon',
            'Project' => 'BriefcaseIcon',
            'Training' => 'AcademicCapIcon',
            'Quiz' => 'QuestionMarkCircleIcon',
            'Group' => 'UsersIcon',
            'Video' => 'VideoCameraIcon',
            'Stock' => 'CubeIcon',
            'Program' => 'ListBulletIcon',
            'DepartmentReport' => 'DocumentTextIcon',
            'DepartmentNeed' => 'ExclamationCircleIcon',
            'DepartmentMeeting' => 'CalendarIcon',
            'Appointment' => 'CalendarIcon',
            'WorkflowInstance' => 'ArrowPathIcon',
        ];

        return $iconMap[$subjectType] ?? 'InformationCircleIcon';
    }

    /**
     * Get URL for the activity's subject.
     */
    private function getActivityUrl(Activity $activity): ?string
    {
        if (!$activity->subject) {
            return null;
        }

        $subjectType = class_basename($activity->subject_type);

        try {
            switch ($subjectType) {
                case 'Event':
                    return route('events.show', $activity->subject->uuid);
                case 'Article':
                    return route('articles.show', $activity->subject->slug);
                case 'Book':
                    return route('books.show', $activity->subject->uuid);
                case 'Department':
                    return route('departments.show', $activity->subject->uuid);
                case 'Task':
                    return route('tasks.show', $activity->subject->uuid);
                case 'Project':
                    return route('projects.show', $activity->subject->uuid);
                case 'Training':
                    return route('trainings.show', $activity->subject->uuid);
                case 'Group':
                    return route('groups.show', $activity->subject->uuid);
                case 'User':
                    return route('users.show', $activity->subject->uuid);
                default:
                    return null;
            }
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get activity statistics.
     */
    private function getActivityStats(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => Activity::where('created_at', '>=', $today)->count(),
            'this_week' => Activity::where('created_at', '>=', $thisWeek)->count(),
            'this_month' => Activity::where('created_at', '>=', $thisMonth)->count(),
            'total' => Activity::count(),
        ];
    }
}
