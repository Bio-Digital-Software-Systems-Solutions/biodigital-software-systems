<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Event;
use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Upcoming events (next 10) - Optimized with eager loading
        $upcomingEvents = Event::where('start_date', '>', Carbon::now())
            ->withWhereHas('participants', function ($query) use ($user): void {
                $query->where('users.id', $user->id);
            })
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get()
            ->map(fn($event): array => [
                'id' => $event->id,
                'uuid' => $event->uuid,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'location' => $event->location,
                'is_participating' => $event->participants->isNotEmpty(),
            ]);

        // Recent published articles (last 10)
        $recentArticles = Article::whereNotNull('published_at')
            ->with('user:id,first_name,last_name')
            ->orderBy('published_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($article): array => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'published_at' => $article->published_at,
                'author' => $article->user ? $article->user->first_name.' '.$article->user->last_name : 'Auteur inconnu',
                'featured_image' => $article->featured_image,
            ]);

        // Available trainings (active ones) - Optimized with eager loading
        $availableTrainings = Training::active()
            ->with(['enrollments' => function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($training): array => [
                'id' => $training->id,
                'uuid' => $training->uuid,
                'title' => $training->title,
                'description' => $training->description,
                'category' => $training->category ?? 'Non catégorisé',
                'duration' => $training->duration,
                'is_enrolled' => $training->enrollments->isNotEmpty(),
            ]);

        // User's enrolled trainings
        $myTrainings = $user->trainings()
            ->wherePivot('status', '!=', 'rejected')
            ->take(5)
            ->get()
            ->map(fn($training): array => [
                'id' => $training->id,
                'uuid' => $training->uuid,
                'title' => $training->title,
                'status' => $training->pivot->status,
                'progress' => $training->pivot->progress ?? 0,
                'category' => $training->category ?? 'Non catégorisé',
            ]);

        // User's participating events
        $myEvents = $user->participatingEvents()
            ->where('start_date', '>', Carbon::now())
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get()
            ->map(fn($event): array => [
                'id' => $event->id,
                'uuid' => $event->uuid,
                'title' => $event->title,
                'start_date' => $event->start_date,
                'location' => $event->location,
            ]);

        // Quick stats
        $stats = [
            'totalEvents' => Event::where('start_date', '>', Carbon::now())->count(),
            'myEvents' => $user->participatingEvents()->where('start_date', '>', Carbon::now())->count(),
            'totalArticles' => Article::whereNotNull('published_at')->count(),
            'totalTrainings' => Training::active()->count(),
            'myTrainings' => $user->trainings()->wherePivot('status', '!=', 'rejected')->count(),
        ];

        return Inertia::render('UserDashboard', [
            'upcomingEvents' => $upcomingEvents,
            'recentArticles' => $recentArticles,
            'availableTrainings' => $availableTrainings,
            'myTrainings' => $myTrainings,
            'myEvents' => $myEvents,
            'stats' => $stats,
        ]);
    }
}
