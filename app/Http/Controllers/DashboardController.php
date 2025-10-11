<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Book;
use App\Models\BookRental;
use App\Models\ChatMessage;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect members to user dashboard
        if ($user->hasRole('member') && ! $user->hasAnyRole(['admin', 'project_manager', 'event_manager', 'writer'])) {
            return redirect()->route('user.dashboard');
        }
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Upcoming events (using start_date)
        $upcomingEvents = Event::where('start_date', '>', $now)->count();
        $upcomingEventsLastMonth = Event::whereBetween('start_date', [$lastMonth, $now])->count();
        $eventsChange = $this->calculatePercentageChange($upcomingEventsLastMonth, $upcomingEvents);

        // Published articles (articles with published_at not null)
        $publishedArticles = Article::whereNotNull('published_at')->count();
        $publishedArticlesLastMonth = Article::whereNotNull('published_at')
            ->where('published_at', '<=', $lastMonth)
            ->count();
        $articlesChange = $this->calculatePercentageChange($publishedArticlesLastMonth, $publishedArticles);

        // Available books - sum of stock quantities minus currently rented books
        $totalStock = Book::sum('stock_quantity');
        $currentlyRented = BookRental::whereNull('return_date')->count();
        $availableBooks = $totalStock - $currentlyRented;

        // Calculate last month's available books
        $totalStockLastMonth = Book::sum('stock_quantity'); // Assuming stock hasn't changed
        $rentedLastMonth = BookRental::whereNull('return_date')
            ->where('rental_date', '<=', $lastMonth)
            ->count();
        $availableBooksLastMonth = $totalStockLastMonth - $rentedLastMonth;
        $booksChange = $this->calculatePercentageChange($availableBooksLastMonth, $availableBooks);

        // Unread messages (from chat) - simplified count of recent messages in user's rooms
        $unreadMessages = ChatMessage::whereHas('room.participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
            ->where('sender_id', '!=', $user->id)
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->count();

        $unreadMessagesLastMonth = ChatMessage::whereHas('room.participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
            ->where('sender_id', '!=', $user->id)
            ->whereBetween('created_at', [Carbon::now()->subMonth()->subDays(7), Carbon::now()->subMonth()])
            ->count();
        $messagesChange = $this->calculatePercentageChange($unreadMessagesLastMonth, $unreadMessages);

        // Recent activities
        $recentActivities = $this->getRecentActivities($user);

        // Performance metrics
        $participationRate = $this->getParticipationRate($user);
        $articlesViewedThisMonth = $this->getArticlesViewedThisMonth();
        $booksBorrowed = $this->getBooksBorrowedThisMonth($user);

        return Inertia::render('Dashboard', [
            'stats' => [
                'upcomingEvents' => [
                    'value' => $upcomingEvents,
                    'change' => $eventsChange,
                ],
                'publishedArticles' => [
                    'value' => $publishedArticles,
                    'change' => $articlesChange,
                ],
                'availableBooks' => [
                    'value' => $availableBooks,
                    'change' => $booksChange,
                ],
                'unreadMessages' => [
                    'value' => $unreadMessages,
                    'change' => $messagesChange,
                ],
            ],
            'recentActivities' => $recentActivities,
            'performance' => [
                'participationRate' => $participationRate,
                'articlesViewedThisMonth' => $articlesViewedThisMonth,
                'booksBorrowed' => $booksBorrowed,
            ],
        ]);
    }

    private function calculatePercentageChange($old, $new): array
    {
        if ($old == 0) {
            return [
                'value' => $new > 0 ? '+100%' : '0%',
                'type' => $new > 0 ? 'increase' : 'stable',
            ];
        }

        $change = (($new - $old) / $old) * 100;

        return [
            'value' => ($change > 0 ? '+' : '').number_format($change, 1).'%',
            'type' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'stable'),
        ];
    }

    private function getRecentActivities($user): array
    {
        $activities = collect();

        // Recent events
        $recentEvents = Event::latest()->take(2)->get();
        foreach ($recentEvents as $event) {
            $activities->push([
                'id' => 'event-'.$event->id,
                'type' => 'event',
                'title' => 'Événement: '.$event->title,
                'description' => $event->description,
                'time' => $event->created_at->diffForHumans(),
                'icon' => 'CalendarDaysIcon',
                'url' => route('events.show', $event->id),
            ]);
        }

        // Recent articles
        $recentArticles = Article::whereNotNull('published_at')
            ->latest('published_at')
            ->take(2)
            ->with('user')
            ->get();
        foreach ($recentArticles as $article) {
            $activities->push([
                'id' => 'article-'.$article->id,
                'type' => 'article',
                'title' => 'Article publié: '.$article->title,
                'description' => 'Par '.($article->user->name ?? 'Auteur inconnu'),
                'time' => $article->published_at?->diffForHumans() ?? $article->created_at->diffForHumans(),
                'icon' => 'PencilSquareIcon',
                'url' => route('articles.show', $article->id),
            ]);
        }

        // Recent book rentals
        $recentRentals = BookRental::with(['book', 'user'])
            ->latest()
            ->take(1)
            ->get();
        foreach ($recentRentals as $rental) {
            $activities->push([
                'id' => 'rental-'.$rental->id,
                'type' => 'book',
                'title' => 'Livre emprunté: '.($rental->book->title ?? 'Livre inconnu'),
                'description' => 'Par '.($rental->user->name ?? 'Utilisateur inconnu'),
                'time' => $rental->created_at->diffForHumans(),
                'icon' => 'BookOpenIcon',
                'url' => route('book-rentals.show', $rental->id),
            ]);
        }

        // Recent messages
        $recentMessages = ChatMessage::whereHas('room.participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
            ->with(['sender'])
            ->latest()
            ->take(1)
            ->get();

        foreach ($recentMessages as $message) {
            $activities->push([
                'id' => 'message-'.$message->id,
                'type' => 'message',
                'title' => 'Message de '.($message->sender->name ?? 'Utilisateur inconnu'),
                'description' => substr($message->content, 0, 50).(strlen($message->content) > 50 ? '...' : ''),
                'time' => $message->created_at->diffForHumans(),
                'icon' => 'ChatBubbleLeftRightIcon',
                'url' => route('chat.index'),
            ]);
        }

        return $activities->sortByDesc('id')->take(4)->values()->toArray();
    }

    private function getParticipationRate($user): int
    {
        // Count all events (past and future)
        $totalEvents = Event::count();

        if ($totalEvents == 0) {
            return 0;
        }

        // Count events where user is a participant
        $participatedEvents = Event::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
            ->count();

        return (int) round(($participatedEvents / $totalEvents) * 100);
    }

    private function getArticlesViewedThisMonth(): int
    {
        // Return total published articles (not just this month)
        return Article::whereNotNull('published_at')->count();
    }

    private function getBooksBorrowedThisMonth($user): int
    {
        // Return total books borrowed by user (all time)
        return BookRental::where('user_id', $user->id)->count();
    }
}
