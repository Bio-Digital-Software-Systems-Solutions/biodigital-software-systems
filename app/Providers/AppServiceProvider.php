<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\CareService;
use App\Models\Project;
use App\Models\Quiz;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Task;
use App\Models\VisitorAttendance;
use App\Observers\AppointmentObserver;
use App\Observers\CareServiceObserver;
use App\Observers\DepartmentTodoObserver;
use App\Observers\ProjectObserver;
use App\Observers\QuizObserver;
use App\Observers\TaskObserver;
use App\Observers\VisitorAttendanceObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        \App\Models\Event::class => \App\Policies\EventPolicy::class,
        \App\Models\Article::class => \App\Policies\ArticlePolicy::class,
        \App\Models\Book::class => \App\Policies\BookPolicy::class,
        \App\Models\Training::class => \App\Policies\TrainingPolicy::class,
        \App\Models\BookRental::class => \App\Policies\BookRentalPolicy::class,
        \App\Models\ChatRoom::class => \App\Policies\ChatRoomPolicy::class,
        \App\Models\CareService::class => \App\Policies\CareServicePolicy::class,
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\Scheduling\DepartmentTodo::class => \App\Policies\DepartmentTodoPolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
        \App\Models\Agile\Epic::class => \App\Policies\Agile\EpicPolicy::class,
        \App\Models\Agile\UserStory::class => \App\Policies\Agile\UserStoryPolicy::class,
        \App\Models\Agile\AcceptanceCriterion::class => \App\Policies\Agile\AcceptanceCriterionPolicy::class,
        \App\Models\Agile\TestScenario::class => \App\Policies\Agile\TestScenarioPolicy::class,
        \App\Models\Sprint::class => \App\Policies\Agile\SprintPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register observers
        Task::observe(TaskObserver::class);
        Quiz::observe(QuizObserver::class);
        Project::observe(ProjectObserver::class);
        DepartmentTodo::observe(DepartmentTodoObserver::class);
        Appointment::observe(AppointmentObserver::class);
        CareService::observe(CareServiceObserver::class);
        VisitorAttendance::observe(VisitorAttendanceObserver::class);

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Configure rate limiting
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        \Illuminate\Support\Facades\RateLimiter::for('login', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip()));

        \Illuminate\Support\Facades\RateLimiter::for('register', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perHour(3)->by($request->ip()));

        \Illuminate\Support\Facades\RateLimiter::for('uploads', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        \Illuminate\Support\Facades\RateLimiter::for('chat', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->user()->id));
    }
}
