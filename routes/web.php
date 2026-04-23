<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $heroSlides = \App\Models\HeroSlide::active()->get();
    $globalStats = \App\Models\SiteSetting::getGlobalStats();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'heroSlides' => $heroSlides,
        'globalStats' => $globalStats,
    ]);
});

// Legal pages (public access)
Route::get('/privacy-policy', fn () => Inertia::render('Legal/PrivacyPolicy'))->name('privacy-policy');

Route::get('/terms-of-service', fn () => Inertia::render('Legal/TermsOfService'))->name('terms-of-service');

// CAPTCHA generation (public access for registration)
Route::get('/captcha', [App\Http\Controllers\CaptchaController::class, 'generate'])->name('captcha.generate');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'restrict.member'])
    ->name('dashboard');

// User dashboard for members
Route::get('/user/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('user.dashboard');

// Hero Slides routes
Route::resource('hero-slides', App\Http\Controllers\HeroSlideController::class);

// Contact routes (public create/store, admin for the rest)
Route::get('contact', [App\Http\Controllers\ContactController::class, 'create'])->name('contacts.create');
Route::post('contact', [App\Http\Controllers\ContactController::class, 'store'])->name('contacts.store');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Contact management routes (admin only)
    Route::resource('contacts', App\Http\Controllers\ContactController::class)->except(['create', 'store']);
    Route::get('/profile/{user:uuid}', [ProfileController::class, 'publicShow'])->name('profile.public');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Accounting routes
    Route::get('accounting', [App\Http\Controllers\AccountingController::class, 'index'])
        ->name('accounting.index')
        ->middleware('can:view accounting');

    // Event routes
    Route::resource('events', App\Http\Controllers\EventController::class);
    Route::post('events/{event}/toggle-participation', [App\Http\Controllers\EventController::class, 'toggleParticipation'])
        ->name('events.toggle-participation');
    Route::post('events/{event}/join', [App\Http\Controllers\EventController::class, 'join'])
        ->name('events.join');
    Route::delete('events/{event}/leave', [App\Http\Controllers\EventController::class, 'leave'])
        ->name('events.leave');

    // Event Media routes
    Route::get('events/{event}/media', [App\Http\Controllers\EventMediaController::class, 'index'])
        ->name('events.media.index');
    Route::post('events/{event}/media', [App\Http\Controllers\EventMediaController::class, 'store'])
        ->name('events.media.store');
    Route::post('events/{event}/media/tus', [App\Http\Controllers\EventMediaController::class, 'storeFromTus'])
        ->name('events.media.store-tus');
    Route::put('events/{event}/media/{media}', [App\Http\Controllers\EventMediaController::class, 'update'])
        ->name('events.media.update');
    Route::delete('events/{event}/media/{media}', [App\Http\Controllers\EventMediaController::class, 'destroy'])
        ->name('events.media.destroy');
    Route::post('events/{event}/media/reorder', [App\Http\Controllers\EventMediaController::class, 'reorder'])
        ->name('events.media.reorder');
    Route::post('events/{event}/media/{media}/set-banner', [App\Http\Controllers\EventMediaController::class, 'setBanner'])
        ->name('events.media.set-banner');
    Route::post('events/{event}/media/{media}/set-featured', [App\Http\Controllers\EventMediaController::class, 'setFeatured'])
        ->name('events.media.set-featured');

    // Event Programme routes
    Route::post('events/{event}/programme', [App\Http\Controllers\EventProgrammeController::class, 'store'])
        ->name('events.programme.store');
    Route::delete('events/{event}/programme', [App\Http\Controllers\EventProgrammeController::class, 'destroy'])
        ->name('events.programme.destroy');
    Route::post('events/{event}/programme/share-link', [App\Http\Controllers\EventProgrammeController::class, 'generateShareLink'])
        ->name('events.programme.generate-share-link');
    Route::post('events/{event}/programme/renew-link', [App\Http\Controllers\EventProgrammeController::class, 'renewShareLink'])
        ->name('events.programme.renew-share-link');
    Route::post('events/{event}/programme/revoke-link', [App\Http\Controllers\EventProgrammeController::class, 'revokeShareLink'])
        ->name('events.programme.revoke-share-link');

    // Book routes
    Route::resource('books', App\Http\Controllers\BookController::class);
    Route::post('books/{book}/rent', [App\Http\Controllers\BookController::class, 'rent'])
        ->name('books.rent');

    // Book rental routes
    Route::get('my-rentals', [App\Http\Controllers\BookRentalController::class, 'index'])
        ->name('book-rentals.index');
    Route::get('my-rentals/{rental}', [App\Http\Controllers\BookRentalController::class, 'show'])
        ->name('book-rentals.show');
    Route::post('my-rentals/{rental}/return', [App\Http\Controllers\BookRentalController::class, 'returnBook'])
        ->name('book-rentals.return');
    Route::post('my-rentals/{rental}/extend', [App\Http\Controllers\BookRentalController::class, 'extendRental'])
        ->name('book-rentals.extend');

    // Admin book rental routes
    Route::get('admin/book-rentals', [App\Http\Controllers\BookRentalController::class, 'adminIndex'])
        ->name('admin.book-rentals.index')
        ->middleware('can:manage library');
    Route::delete('admin/book-rentals/{rental}', [App\Http\Controllers\BookRentalController::class, 'destroy'])
        ->name('admin.book-rentals.destroy')
        ->middleware('can:manage library');

    // Article routes - order matters: specific routes before generic ones
    Route::get('articles', [App\Http\Controllers\ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/create', [App\Http\Controllers\ArticleController::class, 'create'])->name('articles.create');
    Route::get('articles/{article}', [App\Http\Controllers\ArticleController::class, 'show'])->name('articles.show');
    Route::get('articles/{article}/edit', [App\Http\Controllers\ArticleController::class, 'edit'])->name('articles.edit');
    Route::delete('articles/{article}', [App\Http\Controllers\ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('articles/{article}/like', [App\Http\Controllers\MarkController::class, 'toggleLike'])
        ->name('articles.like');
    Route::post('articles/{article}/favorite', [App\Http\Controllers\MarkController::class, 'toggleBookmark'])
        ->name('articles.favorite');

    // Apply rate limiting to article store/update
    Route::post('articles', [App\Http\Controllers\ArticleController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('articles.store');
    Route::put('articles/{article}', [App\Http\Controllers\ArticleController::class, 'update'])
        ->middleware('throttle:uploads')
        ->name('articles.update');

    // Tag routes
    Route::resource('tags', App\Http\Controllers\TagController::class);

    // Task routes
    Route::resource('tasks', App\Http\Controllers\TaskController::class);
    Route::patch('tasks/{task}/toggle-complete', [App\Http\Controllers\TaskController::class, 'toggleComplete'])
        ->name('tasks.toggle-complete');
    Route::post('tasks/bulk-toggle-complete', [App\Http\Controllers\TaskController::class, 'bulkToggleComplete'])
        ->name('tasks.bulk-toggle-complete');
    Route::patch('tasks/{task}/progress', [App\Http\Controllers\TaskController::class, 'updateProgress'])
        ->name('tasks.update-progress');
    Route::patch('tasks/{task}/inline-update', [App\Http\Controllers\TaskController::class, 'inlineUpdate'])
        ->name('tasks.inline-update');

    // Task Participants
    Route::post('tasks/{task}/participants', [App\Http\Controllers\TaskController::class, 'addParticipant'])
        ->name('tasks.participants.add');
    Route::patch('tasks/{task}/participants/{participant}', [App\Http\Controllers\TaskController::class, 'updateParticipant'])
        ->name('tasks.participants.update');
    Route::delete('tasks/{task}/participants/{participant}', [App\Http\Controllers\TaskController::class, 'removeParticipant'])
        ->name('tasks.participants.remove');

    // Task Comments
    Route::post('tasks/{task}/comments', [App\Http\Controllers\TaskController::class, 'addComment'])
        ->name('tasks.comments.add');
    Route::patch('tasks/{task}/comments/{comment}', [App\Http\Controllers\TaskController::class, 'updateComment'])
        ->name('tasks.comments.update');
    Route::delete('tasks/{task}/comments/{comment}', [App\Http\Controllers\TaskController::class, 'deleteComment'])
        ->name('tasks.comments.delete');

    // Subtasks
    Route::post('tasks/{task}/subtasks', [App\Http\Controllers\TaskController::class, 'storeSubtask'])
        ->name('tasks.subtasks.store');
    Route::delete('tasks/{task}/subtasks/{subtask}', [App\Http\Controllers\TaskController::class, 'destroySubtask'])
        ->name('tasks.subtasks.destroy');

    // Task Attachments
    Route::post('tasks/{task}/attachments', [App\Http\Controllers\TaskController::class, 'addAttachment'])
        ->name('tasks.attachments.add');
    Route::delete('tasks/{task}/attachments/{attachment}', [App\Http\Controllers\TaskController::class, 'deleteAttachment'])
        ->name('tasks.attachments.delete');
    Route::get('tasks/{task}/attachments/{attachment}/download', [App\Http\Controllers\TaskController::class, 'downloadAttachment'])
        ->name('tasks.attachments.download');

    // Program routes
    Route::resource('programs', App\Http\Controllers\ProgramController::class)
        ->middleware('can:view programs');

    // Program Step routes
    Route::post('programs/{program}/steps', [App\Http\Controllers\ProgramStepController::class, 'store'])
        ->middleware('can:create program steps')
        ->name('programs.steps.store');
    Route::patch('programs/{program}/steps/{step}', [App\Http\Controllers\ProgramStepController::class, 'update'])
        ->middleware('can:edit programs')
        ->name('programs.steps.update');
    Route::delete('programs/{program}/steps/{step}', [App\Http\Controllers\ProgramStepController::class, 'destroy'])
        ->middleware('can:delete programs')
        ->name('programs.steps.destroy');
    Route::post('programs/{program}/steps/{step}/participants', [App\Http\Controllers\ProgramStepController::class, 'attachParticipant'])
        ->middleware('can:edit programs')
        ->name('programs.steps.participants.attach');
    Route::delete('programs/{program}/steps/{step}/participants/{user}', [App\Http\Controllers\ProgramStepController::class, 'detachParticipant'])
        ->middleware('can:edit programs')
        ->name('programs.steps.participants.detach');

    // Program Step Task routes
    Route::post('programs/{program}/steps/{step}/tasks', [App\Http\Controllers\ProgramStepTaskController::class, 'store'])
        ->middleware('can:create tasks')
        ->name('programs.steps.tasks.store');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}', [App\Http\Controllers\ProgramStepTaskController::class, 'update'])
        ->middleware('can:edit tasks')
        ->name('programs.steps.tasks.update');
    Route::delete('programs/{program}/steps/{step}/tasks/{task}', [App\Http\Controllers\ProgramStepTaskController::class, 'destroy'])
        ->middleware('can:delete tasks')
        ->name('programs.steps.tasks.destroy');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}/status', [App\Http\Controllers\ProgramStepTaskController::class, 'updateStatus'])
        ->middleware('can:edit tasks')
        ->name('programs.steps.tasks.update-status');

    // Stock routes
    Route::resource('stocks', App\Http\Controllers\StockController::class)
        ->middleware('can:view stocks');

    // Employee routes
    Route::resource('employees', App\Http\Controllers\EmployeeController::class);
    Route::post('employees/{employee}/terminate', [App\Http\Controllers\EmployeeController::class, 'terminate'])
        ->name('employees.terminate');
    Route::post('employees/{employee}/activate', [App\Http\Controllers\EmployeeController::class, 'activate'])
        ->name('employees.activate');
    Route::post('employees/{employee}/on-leave', [App\Http\Controllers\EmployeeController::class, 'setOnLeave'])
        ->name('employees.on-leave');
    Route::post('employees/{employee}/reset-leave', [App\Http\Controllers\EmployeeController::class, 'resetLeave'])
        ->name('employees.reset-leave');
    Route::get('employees-export', [App\Http\Controllers\EmployeeController::class, 'export'])
        ->name('employees.export');

    // Star (Volunteers) routes
    Route::resource('stars', App\Http\Controllers\StarController::class);
    Route::post('stars/{star}/activate', [App\Http\Controllers\StarController::class, 'activate'])
        ->name('stars.activate');
    Route::post('stars/{star}/deactivate', [App\Http\Controllers\StarController::class, 'deactivate'])
        ->name('stars.deactivate');
    Route::post('stars/{star}/on-break', [App\Http\Controllers\StarController::class, 'setOnBreak'])
        ->name('stars.on-break');
    Route::post('stars/{star}/graduate', [App\Http\Controllers\StarController::class, 'graduate'])
        ->name('stars.graduate');
    Route::post('stars/{star}/suspend', [App\Http\Controllers\StarController::class, 'suspend'])
        ->name('stars.suspend');
    Route::post('stars/{star}/add-points', [App\Http\Controllers\StarController::class, 'addPoints'])
        ->name('stars.add-points');
    Route::post('stars/{star}/toggle-featured', [App\Http\Controllers\StarController::class, 'toggleFeatured'])
        ->name('stars.toggle-featured');
    Route::post('stars/{star}/renew', [App\Http\Controllers\StarController::class, 'renew'])
        ->name('stars.renew');
    Route::get('stars-export', [App\Http\Controllers\StarController::class, 'export'])
        ->name('stars.export');

    // Group routes
    Route::resource('groups', App\Http\Controllers\GroupController::class);
    Route::post('groups/{group}/join', [App\Http\Controllers\GroupController::class, 'join'])
        ->name('groups.join');
    Route::delete('groups/{group}/leave', [App\Http\Controllers\GroupController::class, 'leave'])
        ->name('groups.leave');
    Route::post('groups/{group}/add-member', [App\Http\Controllers\GroupController::class, 'addMember'])
        ->name('groups.add-member');
    Route::delete('groups/{group}/users/{user}', [App\Http\Controllers\GroupController::class, 'removeMember'])
        ->name('groups.remove-member');

    // Group Visitors routes
    Route::prefix('groups/{group:uuid}/visitors')->name('groups.visitors.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\GroupVisitorController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\GroupVisitorController::class, 'store'])->name('store');
        Route::post('/attendance', [App\Http\Controllers\GroupVisitorController::class, 'bulkRecordAttendance'])->name('bulk-attendance');
        Route::post('/{visitor:uuid}/attendance', [App\Http\Controllers\GroupVisitorController::class, 'recordAttendance'])->name('record-attendance')->withoutScopedBindings();
        Route::delete('/{visitor:uuid}', [App\Http\Controllers\GroupVisitorController::class, 'removeVisitor'])->name('remove')->withoutScopedBindings();
        Route::get('/dashboard', [App\Http\Controllers\GroupVisitorController::class, 'integrationDashboard'])->name('dashboard');
    });

    // Visitor CRUD routes
    Route::resource('visitors', App\Http\Controllers\VisitorController::class);

    // Integration Pathways routes
    Route::prefix('integration-pathways')->name('integration-pathways.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\IntegrationPathwayController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\IntegrationPathwayController::class, 'store'])->name('store');
        Route::get('/{template:uuid}', [App\Http\Controllers\IntegrationPathwayController::class, 'show'])->name('show');
        Route::put('/{template:uuid}', [App\Http\Controllers\IntegrationPathwayController::class, 'update'])->name('update');
        Route::delete('/{template:uuid}', [App\Http\Controllers\IntegrationPathwayController::class, 'destroy'])->name('destroy');
        Route::post('/{template:uuid}/steps', [App\Http\Controllers\IntegrationPathwayController::class, 'addStep'])->name('steps.store');
        Route::put('/{template:uuid}/steps/{step:uuid}', [App\Http\Controllers\IntegrationPathwayController::class, 'updateStep'])->name('steps.update');
        Route::delete('/{template:uuid}/steps/{step:uuid}', [App\Http\Controllers\IntegrationPathwayController::class, 'removeStep'])->name('steps.destroy');
        Route::post('/{template:uuid}/steps/reorder', [App\Http\Controllers\IntegrationPathwayController::class, 'reorderSteps'])->name('steps.reorder');
    });

    // Integration Suggestions routes
    Route::get('/integration-suggestions', [App\Http\Controllers\IntegrationSuggestionController::class, 'index'])
        ->name('integration-suggestions.index');
    Route::post('/integration-suggestions/{suggestion:uuid}/respond', [App\Http\Controllers\IntegrationSuggestionController::class, 'respond'])
        ->name('integration-suggestions.respond');

    // Department routes
    Route::resource('departments', App\Http\Controllers\DepartmentController::class);
    Route::post('departments/{department}/assign-user', [App\Http\Controllers\DepartmentController::class, 'assignUser'])
        ->name('departments.assign-user');
    Route::delete('departments/{department}/users/{user}', [App\Http\Controllers\DepartmentController::class, 'removeUser'])
        ->name('departments.remove-user');

    // Department Position Nominations
    Route::prefix('departments/{department}/nominations')->name('departments.nominations.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\DepartmentPositionNominationController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\DepartmentPositionNominationController::class, 'store'])->name('store');
        Route::put('/{nomination}', [App\Http\Controllers\DepartmentPositionNominationController::class, 'update'])->name('update');
        Route::delete('/{nomination}', [App\Http\Controllers\DepartmentPositionNominationController::class, 'destroy'])->name('destroy');
    });

    // Department Positions
    Route::prefix('departments/{department}/positions')->name('departments.positions.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\DepartmentPositionController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\DepartmentPositionController::class, 'store'])->name('store');
        Route::put('/{position}', [App\Http\Controllers\DepartmentPositionController::class, 'update'])->name('update');
        Route::delete('/{position}', [App\Http\Controllers\DepartmentPositionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [App\Http\Controllers\DepartmentPositionController::class, 'reorder'])->name('reorder');
    });

    // ============================================
    // Department Scheduling Routes
    // ============================================
    Route::prefix('departments/{department}/schedule')->name('departments.schedule.')->group(function (): void {
        // Weekly Schedule routes
        Route::get('/', [App\Http\Controllers\Scheduling\ScheduleController::class, 'index'])
            ->name('index');
        Route::post('/', [App\Http\Controllers\Scheduling\ScheduleController::class, 'store'])
            ->name('store');
        Route::get('/{schedule}', [App\Http\Controllers\Scheduling\ScheduleController::class, 'show'])
            ->name('show');
        Route::delete('/{schedule}', [App\Http\Controllers\Scheduling\ScheduleController::class, 'destroy'])
            ->name('destroy');
        Route::post('/{schedule}/publish', [App\Http\Controllers\Scheduling\ScheduleController::class, 'publish'])
            ->name('publish');
        Route::post('/{schedule}/lock', [App\Http\Controllers\Scheduling\ScheduleController::class, 'lock'])
            ->name('lock');
        Route::post('/{schedule}/copy', [App\Http\Controllers\Scheduling\ScheduleController::class, 'copy'])
            ->name('copy');
        Route::post('/{schedule}/auto-assign', [App\Http\Controllers\Scheduling\ScheduleController::class, 'autoAssign'])
            ->name('auto-assign');
        Route::get('/{schedule}/stats', [App\Http\Controllers\Scheduling\ScheduleController::class, 'stats'])
            ->name('stats');

        // Shift routes
        Route::get('/{schedule}/shifts', [App\Http\Controllers\Scheduling\ShiftController::class, 'index'])
            ->name('shifts.index');
        Route::get('/{schedule}/shifts/create', [App\Http\Controllers\Scheduling\ShiftController::class, 'create'])
            ->name('shifts.create');
        Route::post('/{schedule}/shifts', [App\Http\Controllers\Scheduling\ShiftController::class, 'store'])
            ->name('shifts.store');
        Route::post('/{schedule}/shifts/bulk', [App\Http\Controllers\Scheduling\ShiftController::class, 'bulkStore'])
            ->name('shifts.bulk-store');
        Route::delete('/{schedule}/shifts/bulk', [App\Http\Controllers\Scheduling\ShiftController::class, 'bulkDestroy'])
            ->name('shifts.bulk-destroy');
        Route::get('/{schedule}/shifts/{shift}', [App\Http\Controllers\Scheduling\ShiftController::class, 'show'])
            ->name('shifts.show');
        Route::get('/{schedule}/shifts/{shift}/edit', [App\Http\Controllers\Scheduling\ShiftController::class, 'edit'])
            ->name('shifts.edit');
        Route::put('/{schedule}/shifts/{shift}', [App\Http\Controllers\Scheduling\ShiftController::class, 'update'])
            ->name('shifts.update');
        Route::delete('/{schedule}/shifts/{shift}', [App\Http\Controllers\Scheduling\ShiftController::class, 'destroy'])
            ->name('shifts.destroy');
        Route::post('/{schedule}/shifts/{shift}/assign', [App\Http\Controllers\Scheduling\ShiftController::class, 'assign'])
            ->name('shifts.assign');
        Route::delete('/{schedule}/shifts/{shift}/unassign', [App\Http\Controllers\Scheduling\ShiftController::class, 'unassign'])
            ->name('shifts.unassign');
        Route::post('/{schedule}/shifts/{shift}/check-in', [App\Http\Controllers\Scheduling\ShiftController::class, 'checkIn'])
            ->name('shifts.check-in');
        Route::post('/{schedule}/shifts/{shift}/check-out', [App\Http\Controllers\Scheduling\ShiftController::class, 'checkOut'])
            ->name('shifts.check-out');
        Route::post('/{schedule}/shifts/{shift}/cancel', [App\Http\Controllers\Scheduling\ShiftController::class, 'cancel'])
            ->name('shifts.cancel');
        Route::get('/{schedule}/shifts/{shift}/available-employees', [App\Http\Controllers\Scheduling\ShiftController::class, 'availableEmployees'])
            ->name('shifts.available-employees');
        Route::post('/{schedule}/shifts/{shift}/add-user', [App\Http\Controllers\Scheduling\ShiftController::class, 'addUser'])
            ->name('shifts.add-user');
        Route::delete('/{schedule}/shifts/{shift}/remove-user', [App\Http\Controllers\Scheduling\ShiftController::class, 'removeUser'])
            ->name('shifts.remove-user');
    });

    // Availability routes (under department)
    Route::prefix('departments/{department}/availability')->name('departments.availability.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'index'])
            ->name('index');
        Route::get('/my', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'myAvailability'])
            ->name('my');
        Route::post('/my', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'storeMyAvailability'])
            ->name('my.store');
        Route::post('/', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'store'])
            ->name('store');
        Route::post('/weekly', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'storeWeekly'])
            ->name('store-weekly');
        Route::post('/bulk', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'bulkStore'])
            ->name('bulk-store');
        Route::delete('/{date}', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'destroy'])
            ->name('destroy');
        Route::delete('/weekly/{dayOfWeek}', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'destroyWeekly'])
            ->name('destroy-weekly');
        Route::get('/date/{date}', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'getForDate'])
            ->name('for-date');
        Route::get('/member/{user}', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'getMemberWeekAvailability'])
            ->name('member-week');
        Route::get('/available-employees', [App\Http\Controllers\Scheduling\AvailabilityController::class, 'getAvailableEmployees'])
            ->name('available-employees');
    });

    // Absence routes (under department)
    Route::prefix('departments/{department}/absences')->name('departments.absences.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\Scheduling\AbsenceController::class, 'index'])
            ->name('index');
        Route::get('/my', [App\Http\Controllers\Scheduling\AbsenceController::class, 'myAbsences'])
            ->name('my');
        Route::get('/create', [App\Http\Controllers\Scheduling\AbsenceController::class, 'create'])
            ->name('create');
        Route::get('/pending-count', [App\Http\Controllers\Scheduling\AbsenceController::class, 'pendingCount'])
            ->name('pending-count');
        Route::get('/calendar', [App\Http\Controllers\Scheduling\AbsenceController::class, 'calendar'])
            ->name('calendar');
        Route::get('/search-interim', [App\Http\Controllers\Scheduling\AbsenceController::class, 'searchInterimCandidates'])
            ->name('search-interim');
        Route::post('/', [App\Http\Controllers\Scheduling\AbsenceController::class, 'store'])
            ->name('store');
        Route::get('/{absence}', [App\Http\Controllers\Scheduling\AbsenceController::class, 'show'])
            ->name('show');
        Route::get('/{absence}/edit', [App\Http\Controllers\Scheduling\AbsenceController::class, 'edit'])
            ->name('edit');
        Route::put('/{absence}', [App\Http\Controllers\Scheduling\AbsenceController::class, 'update'])
            ->name('update');
        Route::post('/{absence}/approve', [App\Http\Controllers\Scheduling\AbsenceController::class, 'approve'])
            ->name('approve');
        Route::post('/{absence}/reject', [App\Http\Controllers\Scheduling\AbsenceController::class, 'reject'])
            ->name('reject');
        Route::post('/{absence}/cancel', [App\Http\Controllers\Scheduling\AbsenceController::class, 'cancel'])
            ->name('cancel');
        Route::delete('/{absence}', [App\Http\Controllers\Scheduling\AbsenceController::class, 'destroy'])
            ->name('destroy');
    });

    // Shift Swap routes (under department)
    Route::prefix('departments/{department}/swap-requests')->name('departments.swap-requests.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'index'])
            ->name('index');
        Route::get('/my', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'mySwapRequests'])
            ->name('my');
        Route::get('/create', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'create'])
            ->name('create');
        Route::post('/', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'store'])
            ->name('store');
        Route::get('/{swapRequest}', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'show'])
            ->name('show');
        Route::post('/{swapRequest}/accept-colleague', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'acceptByColleague'])
            ->name('accept-colleague');
        Route::post('/{swapRequest}/reject-colleague', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'rejectByColleague'])
            ->name('reject-colleague');
        Route::post('/{swapRequest}/approve-manager', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'approveByManager'])
            ->name('approve-manager');
        Route::post('/{swapRequest}/reject-manager', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'rejectByManager'])
            ->name('reject-manager');
        Route::post('/{swapRequest}/cancel', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'cancel'])
            ->name('cancel');
        Route::delete('/{swapRequest}', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'destroy'])
            ->name('destroy');
        Route::get('/pending-count', [App\Http\Controllers\Scheduling\ShiftSwapController::class, 'pendingCount'])
            ->name('pending-count');
    });

    // Routine routes (under department)
    Route::prefix('departments/{department}/routines')->name('departments.routines.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\RoutineController::class, 'index'])
            ->name('index');
        Route::get('/create', [App\Http\Controllers\RoutineController::class, 'create'])
            ->name('create');
        Route::post('/', [App\Http\Controllers\RoutineController::class, 'store'])
            ->name('store');
        Route::get('/{routine}', [App\Http\Controllers\RoutineController::class, 'show'])
            ->name('show');
        Route::get('/{routine}/edit', [App\Http\Controllers\RoutineController::class, 'edit'])
            ->name('edit');
        Route::put('/{routine}', [App\Http\Controllers\RoutineController::class, 'update'])
            ->name('update');
        Route::delete('/{routine}', [App\Http\Controllers\RoutineController::class, 'destroy'])
            ->name('destroy');

        // Status transitions
        Route::post('/{routine}/submit', [App\Http\Controllers\RoutineController::class, 'submitForApproval'])
            ->name('submit');
        Route::post('/{routine}/approve', [App\Http\Controllers\RoutineController::class, 'approve'])
            ->name('approve');
        Route::post('/{routine}/reject', [App\Http\Controllers\RoutineController::class, 'reject'])
            ->name('reject');
        Route::post('/{routine}/activate', [App\Http\Controllers\RoutineController::class, 'activate'])
            ->name('activate');
        Route::post('/{routine}/archive', [App\Http\Controllers\RoutineController::class, 'archive'])
            ->name('archive');

        // Steps
        Route::post('/{routine}/steps', [App\Http\Controllers\RoutineStepController::class, 'store'])
            ->name('steps.store');
        Route::put('/{routine}/steps/{step}', [App\Http\Controllers\RoutineStepController::class, 'update'])
            ->name('steps.update');
        Route::delete('/{routine}/steps/{step}', [App\Http\Controllers\RoutineStepController::class, 'destroy'])
            ->name('steps.destroy');
        Route::post('/{routine}/steps/reorder', [App\Http\Controllers\RoutineStepController::class, 'reorder'])
            ->name('steps.reorder');
        Route::post('/{routine}/steps/{step}/validate', [App\Http\Controllers\RoutineStepController::class, 'validateStep'])
            ->name('steps.validate');
        Route::post('/{routine}/steps/{step}/reject', [App\Http\Controllers\RoutineStepController::class, 'rejectStep'])
            ->name('steps.reject');

        // SOPs
        Route::post('/{routine}/sops', [App\Http\Controllers\RoutineController::class, 'storeSop'])
            ->name('sops.store');
        Route::get('/{routine}/sops/{sop}/download', [App\Http\Controllers\RoutineController::class, 'downloadSop'])
            ->name('sops.download');
        Route::put('/{routine}/sops/{sop}/status', [App\Http\Controllers\RoutineController::class, 'updateSopStatus'])
            ->name('sops.update-status');
        Route::delete('/{routine}/sops/{sop}', [App\Http\Controllers\RoutineController::class, 'destroySop'])
            ->name('sops.destroy');

        // Assignees
        Route::post('/{routine}/assignees', [App\Http\Controllers\RoutineController::class, 'addAssignee'])
            ->name('assignees.store');
        Route::delete('/{routine}/assignees/{assignee}', [App\Http\Controllers\RoutineController::class, 'removeAssignee'])
            ->name('assignees.destroy');
    });

    // Department TODOs routes
    Route::prefix('departments/{department}/todos')->name('departments.todos.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'index'])
            ->name('index');
        Route::post('/', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'store'])
            ->name('store');
        Route::get('/{todo}', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'show'])
            ->name('show');
        Route::put('/{todo}', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'update'])
            ->name('update');
        Route::delete('/{todo}', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'destroy'])
            ->name('destroy');
        Route::patch('/{todo}/inline-update', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'inlineUpdate'])
            ->name('inline-update');
        Route::post('/{todo}/toggle-complete', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'toggleComplete'])
            ->name('toggle-complete');
        Route::post('/{todo}/status', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'updateStatus'])
            ->name('update-status');
        Route::post('/{todo}/assign', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'assign'])
            ->name('assign');
        Route::get('/shift/{shift}', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'forShift'])
            ->name('for-shift');
        Route::post('/bulk', [App\Http\Controllers\Scheduling\DepartmentTodoController::class, 'bulkUpdate'])
            ->name('bulk-update');
    });

    // Library routes (for library management)
    Route::resource('libraries', App\Http\Controllers\LibraryController::class);

    // Message routes
    Route::resource('messages', App\Http\Controllers\MessageController::class);
    Route::patch('messages/{message}/mark-as-read', [App\Http\Controllers\MessageController::class, 'markAsRead'])
        ->name('messages.mark-as-read');
    Route::get('messages-unread-count', [App\Http\Controllers\MessageController::class, 'unreadCount'])
        ->name('messages.unread-count');
    Route::get('messages-search-recipients', [App\Http\Controllers\MessageController::class, 'searchRecipients'])
        ->name('messages.search-recipients');
    Route::get('messages/attachments/{attachment:uuid}/download', [App\Http\Controllers\MessageController::class, 'downloadAttachment'])
        ->name('messages.attachments.download');

    // Settings routes
    Route::get('settings', [App\Http\Controllers\SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('settings', [App\Http\Controllers\SettingsController::class, 'update'])
        ->name('settings.update');

    // Homepage Settings routes (Hero Slides management)
    Route::get('settings/homepage', [App\Http\Controllers\SettingsController::class, 'homepage'])
        ->name('settings.homepage')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/slides', [App\Http\Controllers\SettingsController::class, 'storeSlide'])
        ->name('settings.homepage.slides.store')
        ->middleware('can:manage hero slides');
    // Reorder route must come BEFORE parameterized routes to avoid conflict
    Route::post('settings/homepage/slides/reorder', [App\Http\Controllers\SettingsController::class, 'reorderSlides'])
        ->name('settings.homepage.slides.reorder')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/slides/{heroSlide}', [App\Http\Controllers\SettingsController::class, 'updateSlide'])
        ->name('settings.homepage.slides.update')
        ->middleware('can:manage hero slides');
    Route::delete('settings/homepage/slides/{heroSlide}', [App\Http\Controllers\SettingsController::class, 'deleteSlide'])
        ->name('settings.homepage.slides.destroy')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/global-stats', [App\Http\Controllers\SettingsController::class, 'updateGlobalStats'])
        ->name('settings.homepage.global-stats.update')
        ->middleware('can:manage hero slides');

    // Church management routes
    Route::post('settings/homepage/churches', [App\Http\Controllers\SettingsController::class, 'storeChurch'])
        ->name('settings.homepage.churches.store')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/churches/{church}', [App\Http\Controllers\SettingsController::class, 'updateChurch'])
        ->name('settings.homepage.churches.update')
        ->middleware('can:manage hero slides');
    Route::delete('settings/homepage/churches/{church}', [App\Http\Controllers\SettingsController::class, 'destroyChurch'])
        ->name('settings.homepage.churches.destroy')
        ->middleware('can:manage hero slides');

    // Chat routes
    Route::get('chat', [App\Http\Controllers\ChatController::class, 'index'])
        ->name('chat.index');
    Route::post('chat/rooms', [App\Http\Controllers\ChatController::class, 'createRoom'])
        ->name('chat.rooms.create');
    Route::get('chat/rooms/{room}/messages', [App\Http\Controllers\ChatController::class, 'getMessages'])
        ->name('chat.rooms.messages');
    Route::post('chat/rooms/{room}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage'])
        ->middleware('throttle:chat')
        ->name('chat.rooms.send');
    Route::get('chat/unread-count', [App\Http\Controllers\ChatController::class, 'getUnreadCount'])
        ->name('chat.unread-count');
    Route::delete('chat/rooms/{room}/leave', [App\Http\Controllers\ChatController::class, 'leaveRoom'])
        ->name('chat.rooms.leave');

    // Activity log routes
    Route::get('activity', [App\Http\Controllers\ActivityController::class, 'index'])
        ->name('activity.index');

    // Project management routes
    Route::get('projects/all', [App\Http\Controllers\ProjectController::class, 'list'])
        ->name('projects.list');
    Route::get('projects/{project}/board', [App\Http\Controllers\ProjectController::class, 'board'])
        ->name('projects.board');
    Route::get('projects/{project}/gantt', [App\Http\Controllers\ProjectController::class, 'gantt'])
        ->name('projects.gantt');
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

    // Kanban, Gantt, Sprints, Epics views
    Route::get('kanban', [App\Http\Controllers\KanbanController::class, 'index'])->name('kanban.index');
    Route::patch('kanban/tasks/{task}/status', [App\Http\Controllers\KanbanController::class, 'updateStatus'])->name('kanban.tasks.update-status');

    Route::get('gantt', [App\Http\Controllers\GanttController::class, 'index'])->name('gantt.index');

    Route::get('sprints', [App\Http\Controllers\SprintController::class, 'index'])->name('sprints.index');
    Route::post('sprints', [App\Http\Controllers\SprintController::class, 'store'])->name('sprints.store');
    Route::patch('sprints/{sprint}', [App\Http\Controllers\SprintController::class, 'update'])->name('sprints.update');
    Route::delete('sprints/{sprint}', [App\Http\Controllers\SprintController::class, 'destroy'])->name('sprints.destroy');

    Route::get('epics', [App\Http\Controllers\EpicController::class, 'index'])->name('epics.index');
    Route::post('epics', [App\Http\Controllers\EpicController::class, 'store'])->name('epics.store');
    Route::patch('epics/{epic}', [App\Http\Controllers\EpicController::class, 'update'])->name('epics.update');
    Route::delete('epics/{epic}', [App\Http\Controllers\EpicController::class, 'destroy'])->name('epics.destroy');

    // API routes for user selection
    Route::get('api/users', [App\Http\Controllers\Api\UserController::class, 'index'])
        ->name('api.users.index');

    // Task routes (web view) - now using unified Task model
    Route::get('project-tasks', [App\Http\Controllers\TaskController::class, 'index'])
        ->name('project-tasks.index');
    Route::post('project-tasks/bulk-update', [App\Http\Controllers\TaskController::class, 'bulkUpdate'])
        ->name('project-tasks.bulk-update');
    Route::get('project-tasks/{task}', [App\Http\Controllers\TaskController::class, 'show'])
        ->name('project-tasks.show');

    // Attachment routes
    Route::post('attachments', [App\Http\Controllers\AttachmentController::class, 'store'])
        ->name('attachments.store');
    Route::delete('attachments/{attachment}', [App\Http\Controllers\AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [App\Http\Controllers\AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Notification routes
    Route::get('notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])
        ->name('notifications.unread-count');

    // Training routes (authenticated - Admin)
    Route::get('trainings', [App\Http\Controllers\TrainingController::class, 'adminIndex'])
        ->middleware('restrict.member')
        ->name('trainings.index');
    Route::get('trainings/create', [App\Http\Controllers\TrainingController::class, 'create'])
        ->middleware('restrict.member')
        ->name('trainings.create');
    Route::post('trainings', [App\Http\Controllers\TrainingController::class, 'store'])
        ->middleware(['throttle:uploads', 'restrict.member'])
        ->name('trainings.store');
    Route::get('trainings/{training}/edit', [App\Http\Controllers\TrainingController::class, 'edit'])
        ->middleware('restrict.member')
        ->name('trainings.edit');
    Route::put('trainings/{training}', [App\Http\Controllers\TrainingController::class, 'update'])
        ->middleware(['throttle:uploads', 'restrict.member'])
        ->name('trainings.update');
    Route::delete('trainings/{training}', [App\Http\Controllers\TrainingController::class, 'destroy'])
        ->middleware('restrict.member')
        ->name('trainings.destroy');

    // Training Access Management routes
    Route::get('trainings/{training}/access', [App\Http\Controllers\TrainingController::class, 'accessIndex'])
        ->middleware('restrict.member')
        ->name('trainings.access');
    Route::post('trainings/{training}/access/users', [App\Http\Controllers\TrainingController::class, 'grantUserAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.grant-users');
    Route::delete('trainings/{training}/access/users', [App\Http\Controllers\TrainingController::class, 'revokeUserAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.revoke-users');
    Route::post('trainings/{training}/access/roles', [App\Http\Controllers\TrainingController::class, 'grantRoleAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.grant-roles');
    Route::delete('trainings/{training}/access/roles', [App\Http\Controllers\TrainingController::class, 'revokeRoleAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.revoke-roles');

    // Training Share Link routes
    Route::post('trainings/{training}/share-link', [App\Http\Controllers\TrainingController::class, 'generateShareLink'])
        ->middleware('restrict.member')
        ->name('trainings.generate-share-link');
    Route::post('trainings/{training}/revoke-link', [App\Http\Controllers\TrainingController::class, 'revokeShareLink'])
        ->middleware('restrict.member')
        ->name('trainings.revoke-share-link');

    // Public shared training routes (no auth required)
    Route::get('/t/{token}', [App\Http\Controllers\TrainingController::class, 'showShared'])->name('trainings.shared')->withoutMiddleware(['auth', 'verified']);
    Route::post('/t/{token}/enroll', [App\Http\Controllers\TrainingController::class, 'enrollViaShareLink'])->name('trainings.shared.enroll')->withoutMiddleware(['auth', 'verified']);

    // Student Training routes (authenticated)
    Route::get('student/dashboard', [App\Http\Controllers\TrainingController::class, 'studentDashboard'])
        ->middleware(['restrict.member', 'can:access student dashboard'])
        ->name('student.dashboard');
    Route::post('trainings/{training}/enroll', [App\Http\Controllers\TrainingController::class, 'enroll'])
        ->name('trainings.enroll');

    Route::get('trainings/{training}', [App\Http\Controllers\TrainingController::class, 'show'])
        ->name('trainings.show');

    // Teacher Dashboard routes
    Route::get('teacher/dashboard', [App\Http\Controllers\TrainingController::class, 'teacherDashboard'])
        ->middleware(['restrict.member', 'can:access teacher dashboard'])
        ->name('teacher.dashboard');
    Route::get('teacher/formations', [App\Http\Controllers\TrainingController::class, 'teacherFormations'])
        ->middleware('restrict.member')
        ->name('teacher.formations');
    Route::get('teacher/students', [App\Http\Controllers\TrainingController::class, 'teacherStudents'])
        ->middleware('restrict.member')
        ->name('teacher.students');
    Route::get('teacher/attendance', [App\Http\Controllers\TrainingController::class, 'teacherAttendance'])
        ->middleware('restrict.member')
        ->name('teacher.attendance');
    Route::post('teacher/attendance/{class}/mark', [App\Http\Controllers\TrainingController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('teacher.attendance.mark');
    Route::get('teacher/evaluations', [App\Http\Controllers\TrainingController::class, 'teacherEvaluations'])
        ->middleware('restrict.member')
        ->name('teacher.evaluations');
    Route::post('teacher/evaluations/{student}/grade', [App\Http\Controllers\TrainingController::class, 'gradeStudent'])
        ->middleware('restrict.member')
        ->name('teacher.evaluations.grade');

    // Teacher and Student CRUD routes
    Route::resource('teachers', App\Http\Controllers\TeacherController::class)
        ->middleware('restrict.member');
    Route::resource('students', App\Http\Controllers\StudentController::class)
        ->middleware('restrict.member');

    // TrainingClass Management routes
    Route::get('training-classes', [App\Http\Controllers\TrainingClassController::class, 'index'])
        ->middleware('restrict.member')
        ->name('training-classes.index');
    Route::get('training-classes/schedules', [App\Http\Controllers\TrainingClassController::class, 'schedules'])
        ->middleware('restrict.member')
        ->name('training-classes.schedules');
    Route::get('training-classes/statistics', [App\Http\Controllers\TrainingClassController::class, 'statistics'])
        ->middleware('restrict.member')
        ->name('training-classes.statistics');
    Route::get('training-classes/{trainingClass}', [App\Http\Controllers\TrainingClassController::class, 'show'])
        ->middleware('restrict.member')
        ->name('training-classes.show');
    Route::get('training-classes/{trainingClass}/schedules', [App\Http\Controllers\TrainingClassController::class, 'getClassSchedules'])
        ->middleware('restrict.member')
        ->name('training-classes.class-schedules');
    Route::post('training-classes', [App\Http\Controllers\TrainingClassController::class, 'store'])
        ->middleware('restrict.member')
        ->name('training-classes.store');
    Route::put('training-classes/{trainingClass}', [App\Http\Controllers\TrainingClassController::class, 'update'])
        ->middleware('restrict.member')
        ->name('training-classes.update');
    Route::delete('training-classes/{trainingClass}', [App\Http\Controllers\TrainingClassController::class, 'destroy'])
        ->middleware('restrict.member')
        ->name('training-classes.destroy');
    Route::post('training-classes/{trainingClass}/archive', [App\Http\Controllers\TrainingClassController::class, 'archive'])
        ->middleware('restrict.member')
        ->name('training-classes.archive');
    Route::post('training-classes/{trainingClass}/unarchive', [App\Http\Controllers\TrainingClassController::class, 'unarchive'])
        ->middleware('restrict.member')
        ->name('training-classes.unarchive');
    Route::post('training-classes/{trainingClass}/duplicate', [App\Http\Controllers\TrainingClassController::class, 'duplicate'])
        ->middleware('restrict.member')
        ->name('training-classes.duplicate');
    Route::get('training-classes/{trainingClass}/students', [App\Http\Controllers\TrainingClassController::class, 'students'])
        ->middleware('restrict.member')
        ->name('training-classes.students');
    Route::post('training-classes/{trainingClass}/attendance', [App\Http\Controllers\TrainingClassController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('training-classes.attendance');
    Route::get('training-classes/training/{training}/students', [App\Http\Controllers\TrainingClassController::class, 'trainingStudents'])
        ->middleware('restrict.member')
        ->name('training-classes.training-students');
    Route::get('training-classes/student/{student}/training/{training}/history', [App\Http\Controllers\TrainingClassController::class, 'studentAttendanceHistory'])
        ->middleware('restrict.member')
        ->name('training-classes.student-attendance-history');
    Route::get('training-classes/{trainingClass}/week-schedule', [App\Http\Controllers\TrainingClassController::class, 'weekSchedule'])
        ->middleware('restrict.member')
        ->name('training-classes.week-schedule');

    // TrainingClassSchedule routes
    Route::post('training-class-schedules', [App\Http\Controllers\TrainingClassController::class, 'storeSchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.store');
    Route::put('training-class-schedules/{schedule}', [App\Http\Controllers\TrainingClassController::class, 'updateSchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.update');
    Route::delete('training-class-schedules/{schedule}', [App\Http\Controllers\TrainingClassController::class, 'destroySchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.destroy');
    Route::get('training-class-schedules/{trainingClassSchedule}/attendance', [App\Http\Controllers\TrainingClassScheduleController::class, 'attendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.attendance');
    Route::post('training-class-schedules/{trainingClassSchedule}/mark-attendance', [App\Http\Controllers\TrainingClassScheduleController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.mark-attendance');

    // Training Class Materials routes (for teachers)
    Route::prefix('training-classes/{trainingClass}/materials')->middleware('restrict.member')->group(function (): void {
        Route::get('/', [App\Http\Controllers\TrainingClassMaterialController::class, 'index'])
            ->name('training-classes.materials.index');
        Route::post('/', [App\Http\Controllers\TrainingClassMaterialController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('training-classes.materials.store');
        Route::get('/{material}', [App\Http\Controllers\TrainingClassMaterialController::class, 'show'])
            ->name('training-classes.materials.show');
        Route::put('/{material}', [App\Http\Controllers\TrainingClassMaterialController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('training-classes.materials.update');
        Route::delete('/{material}', [App\Http\Controllers\TrainingClassMaterialController::class, 'destroy'])
            ->name('training-classes.materials.destroy');
        Route::post('/reorder', [App\Http\Controllers\TrainingClassMaterialController::class, 'reorder'])
            ->name('training-classes.materials.reorder');
    });

    // Student access to class materials
    Route::get('student/training-classes/{trainingClass}/materials', [App\Http\Controllers\TrainingClassMaterialController::class, 'studentIndex'])
        ->name('student.training-classes.materials.index');

    // Download/stream material (accessible to both teachers and students)
    Route::get('training-class-materials/{material}/download', [App\Http\Controllers\TrainingClassMaterialController::class, 'download'])
        ->name('training-class-materials.download');

    // Quiz Management routes (for teachers/admins)
    Route::prefix('trainings/{training}/quizzes')->group(function (): void {
        Route::get('/', [App\Http\Controllers\QuizController::class, 'index'])
            ->name('trainings.quizzes.index');
        Route::get('/create', [App\Http\Controllers\QuizController::class, 'create'])
            ->name('trainings.quizzes.create');
        Route::post('/', [App\Http\Controllers\QuizController::class, 'store'])
            ->name('trainings.quizzes.store');
        Route::get('/{quiz}/edit', [App\Http\Controllers\QuizController::class, 'edit'])
            ->name('trainings.quizzes.edit');
        Route::put('/{quiz}', [App\Http\Controllers\QuizController::class, 'update'])
            ->name('trainings.quizzes.update');
        Route::patch('/{quiz}/toggle-status', [App\Http\Controllers\QuizController::class, 'toggleStatus'])
            ->name('trainings.quizzes.toggle-status');
        Route::delete('/{quiz}', [App\Http\Controllers\QuizController::class, 'destroy'])
            ->name('trainings.quizzes.destroy');
        Route::get('/{quiz}/results', [App\Http\Controllers\QuizController::class, 'results'])
            ->name('trainings.quizzes.results');
        Route::get('/{quiz}/export-csv', [App\Http\Controllers\QuizController::class, 'exportCSV'])
            ->name('trainings.quizzes.export-csv');

        // Quiz Class Assignment routes
        Route::get('/{quiz}/class-assignments', [App\Http\Controllers\QuizClassAssignmentController::class, 'show'])
            ->name('trainings.quizzes.class-assignments');
        Route::post('/{quiz}/assign-to-class/{trainingClass}', [App\Http\Controllers\QuizClassAssignmentController::class, 'assignToClass'])
            ->name('trainings.quizzes.assign-to-class');
        Route::delete('/{quiz}/remove-from-class/{trainingClass}', [App\Http\Controllers\QuizClassAssignmentController::class, 'removeFromClass'])
            ->name('trainings.quizzes.remove-from-class');
        Route::put('/{quiz}/update-class-assignment/{trainingClass}', [App\Http\Controllers\QuizClassAssignmentController::class, 'updateClassAssignment'])
            ->name('trainings.quizzes.update-class-assignment');
        Route::post('/{quiz}/assign-to-material/{material}', [App\Http\Controllers\QuizClassAssignmentController::class, 'assignToMaterial'])
            ->name('trainings.quizzes.assign-to-material');
        Route::delete('/{quiz}/remove-from-material/{material}', [App\Http\Controllers\QuizClassAssignmentController::class, 'removeFromMaterial'])
            ->name('trainings.quizzes.remove-from-material');
        Route::post('/{quiz}/bulk-assign-classes', [App\Http\Controllers\QuizClassAssignmentController::class, 'bulkAssignToClasses'])
            ->name('trainings.quizzes.bulk-assign-classes');
        Route::get('/{quiz}/stats', [App\Http\Controllers\QuizClassAssignmentController::class, 'getQuizStats'])
            ->name('trainings.quizzes.stats');
    });

    // Quiz Taking routes (for students)
    Route::get('quizzes/{quiz}/start', [App\Http\Controllers\QuizController::class, 'start'])
        ->name('quizzes.start');
    Route::post('quiz-attempts/{attempt}/submit', [App\Http\Controllers\QuizController::class, 'submit'])
        ->name('quiz-attempts.submit');
    Route::get('quiz-attempts/{attempt}', [App\Http\Controllers\QuizController::class, 'showAttempt'])
        ->name('quiz-attempts.show');

    // Quiz Teacher Dashboard
    Route::get('quizzes/teacher/dashboard', [App\Http\Controllers\QuizController::class, 'teacherDashboard'])
        ->name('quizzes.teacher-dashboard');

    // Training Enrollment Management routes
    Route::get('training-enrollments', [App\Http\Controllers\TrainingEnrollmentController::class, 'index'])
        ->name('training-enrollments.index');
    Route::post('training-enrollments/{id}/approve', [App\Http\Controllers\TrainingEnrollmentController::class, 'approve'])
        ->name('training-enrollments.approve');
    Route::post('training-enrollments/{id}/reject', [App\Http\Controllers\TrainingEnrollmentController::class, 'reject'])
        ->name('training-enrollments.reject');

    // User Management routes (SuperAdmin only)
    Route::get('user-management', [App\Http\Controllers\UserManagementController::class, 'index'])
        ->name('user-management.index');
    // Specific routes must come before the {user} parameter route
    Route::get('user-management/users', [App\Http\Controllers\UserManagementController::class, 'getUsers'])
        ->name('user-management.users');
    Route::get('user-management/blocked-login-attempts', [App\Http\Controllers\UserManagementController::class, 'blockedLoginAttempts'])
        ->name('user-management.blocked-login-attempts');
    Route::post('user-management/blocked-login-attempts/{attempt}/acknowledge', [App\Http\Controllers\UserManagementController::class, 'acknowledgeBlockedAttempt'])
        ->name('user-management.acknowledge-blocked-attempt');
    Route::post('user-management/blocked-login-attempts/acknowledge-multiple', [App\Http\Controllers\UserManagementController::class, 'acknowledgeMultipleBlockedAttempts'])
        ->name('user-management.acknowledge-multiple-blocked-attempts');
    // User show route after specific routes
    Route::get('user-management/{user}', [App\Http\Controllers\UserManagementController::class, 'show'])
        ->name('user-management.show');
    Route::get('user-management/users/{user}/blocked-attempts', [App\Http\Controllers\UserManagementController::class, 'userBlockedAttempts'])
        ->name('user-management.user-blocked-attempts');
    Route::post('user-management/users/{user}/roles', [App\Http\Controllers\UserManagementController::class, 'assignRoles'])
        ->name('user-management.assign-roles');
    Route::post('user-management/users/{user}/permissions', [App\Http\Controllers\UserManagementController::class, 'assignPermissions'])
        ->name('user-management.assign-permissions');
    Route::post('user-management/users/{user}/toggle-status', [App\Http\Controllers\UserManagementController::class, 'toggleStatus'])
        ->name('user-management.toggle-status');
    Route::delete('user-management/users/{user}', [App\Http\Controllers\UserManagementController::class, 'deleteUser'])
        ->name('user-management.delete-user');
    Route::post('user-management/roles', [App\Http\Controllers\UserManagementController::class, 'createRole'])
        ->name('user-management.create-role');
    Route::put('user-management/roles/{role}', [App\Http\Controllers\UserManagementController::class, 'updateRole'])
        ->name('user-management.update-role');
    Route::delete('user-management/roles/{role}', [App\Http\Controllers\UserManagementController::class, 'deleteRole'])
        ->name('user-management.delete-role');
    Route::post('user-management/permissions', [App\Http\Controllers\UserManagementController::class, 'createPermission'])
        ->name('user-management.create-permission');
    Route::delete('user-management/permissions/{permission}', [App\Http\Controllers\UserManagementController::class, 'deletePermission'])
        ->name('user-management.delete-permission');

    // Teacher management routes
    Route::post('user-management/users/{user}/add-teacher', [App\Http\Controllers\UserManagementController::class, 'addTeacher'])
        ->name('user-management.add-teacher');
    Route::delete('user-management/teachers/{teacher}', [App\Http\Controllers\UserManagementController::class, 'removeTeacher'])
        ->name('user-management.remove-teacher');
    Route::put('user-management/teachers/{teacher}', [App\Http\Controllers\UserManagementController::class, 'updateTeacher'])
        ->name('user-management.update-teacher');

    // Star (volunteer) management routes
    Route::post('user-management/users/{user}/add-star', [App\Http\Controllers\UserManagementController::class, 'addStar'])
        ->name('user-management.add-star');
    Route::delete('user-management/stars/{star}', [App\Http\Controllers\UserManagementController::class, 'removeStar'])
        ->name('user-management.remove-star');

    // Employee management routes
    Route::post('user-management/users/{user}/add-employee', [App\Http\Controllers\UserManagementController::class, 'addEmployee'])
        ->name('user-management.add-employee');
    Route::delete('user-management/employees/{employee}', [App\Http\Controllers\UserManagementController::class, 'removeEmployee'])
        ->name('user-management.remove-employee');

    // Appointment routes - specific routes must come before resource routes
    Route::get('appointments', [App\Http\Controllers\AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::get('appointments/create', [App\Http\Controllers\AppointmentController::class, 'create'])
        ->name('appointments.create');
    Route::get('appointments/calendar', [App\Http\Controllers\AppointmentController::class, 'calendar'])
        ->name('appointments.calendar');
    Route::post('appointments', [App\Http\Controllers\AppointmentController::class, 'store'])
        ->name('appointments.store');

    // Resource routes with UUID parameter binding
    Route::resource('appointments', App\Http\Controllers\AppointmentController::class)
        ->except(['index', 'create', 'store'])
        ->parameters(['appointments' => 'appointment:uuid']);

    // Appointment actions
    Route::patch('appointments/{appointment:uuid}/cancel', [App\Http\Controllers\AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
    Route::patch('appointments/{appointment:uuid}/confirm', [App\Http\Controllers\AppointmentController::class, 'confirm'])
        ->name('appointments.confirm');

    // Invitation management
    Route::patch('appointments/{appointment:uuid}/accept-invitation', [App\Http\Controllers\AppointmentController::class, 'acceptInvitation'])
        ->name('appointments.accept-invitation');
    Route::patch('appointments/{appointment:uuid}/decline-invitation', [App\Http\Controllers\AppointmentController::class, 'declineInvitation'])
        ->name('appointments.decline-invitation');

    // iCalendar export/import
    Route::get('appointments/{appointment:uuid}/export-ics', [App\Http\Controllers\AppointmentController::class, 'exportIcs'])
        ->name('appointments.export-ics');
    Route::get('appointments-export-ics', [App\Http\Controllers\AppointmentController::class, 'exportBulkIcs'])
        ->name('appointments.export-bulk-ics');
    Route::post('appointments/import-ics', [App\Http\Controllers\AppointmentController::class, 'importIcs'])
        ->name('appointments.import-ics');

    // API endpoints for appointments
    Route::get('api/appointments/available-slots', [App\Http\Controllers\AppointmentController::class, 'availableSlots'])
        ->name('api.appointments.available-slots');

    // Public agenda routes
    Route::get('users/{user:uuid}/agenda', [App\Http\Controllers\PublicAgendaController::class, 'show'])
        ->name('users.agenda.show');
    Route::get('api/users/{user:uuid}/available-slots', [App\Http\Controllers\PublicAgendaController::class, 'availableSlots'])
        ->name('api.users.available-slots');
    Route::get('api/users/{user:uuid}/schedule', [App\Http\Controllers\PublicAgendaController::class, 'schedule'])
        ->name('api.users.schedule');

    // Pastoral Care routes (authenticated pastors)
    Route::prefix('pastoral-care')->name('pastoral-care.')->group(function (): void {
        Route::resource('appointments', App\Http\Controllers\PastoralCareController::class)
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ])
            ->parameters(['appointments' => 'pastoralCare:uuid']);

        // Additional pastoral care actions
        Route::post('appointments/{pastoralCare:uuid}/confirm', [App\Http\Controllers\PastoralCareController::class, 'confirm'])
            ->name('confirm');
        Route::post('appointments/{pastoralCare:uuid}/cancel', [App\Http\Controllers\PastoralCareController::class, 'cancel'])
            ->name('cancel');
        Route::post('appointments/{pastoralCare:uuid}/complete', [App\Http\Controllers\PastoralCareController::class, 'complete'])
            ->name('complete');
        Route::post('appointments/{pastoralCare:uuid}/no-show', [App\Http\Controllers\PastoralCareController::class, 'noShow'])
            ->name('no-show');

        // Transfer appointment to another pastor/agent
        Route::post('appointments/{pastoralCare:uuid}/transfer', [App\Http\Controllers\PastoralCareController::class, 'transfer'])
            ->name('transfer');

        // MLR Dashboard routes
        Route::get('mlr', [App\Http\Controllers\PastoralCareController::class, 'mlr'])
            ->name('mlr');
        Route::get('mlr/statistics', [App\Http\Controllers\PastoralCareController::class, 'mlrStatistics'])
            ->name('mlr.statistics');

        // AJAX endpoint for available time slots
        Route::get('available-slots', [App\Http\Controllers\PastoralCareController::class, 'getAvailableSlots'])
            ->name('available-slots');
    });

    // Pastor Availability Management routes
    Route::prefix('pastoral-availability')->name('pastoral-availability.')->middleware('can:manage pastor availability')->group(function (): void {
        Route::resource('', App\Http\Controllers\PastorAvailabilityController::class)
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ])
            ->parameters(['' => 'availability']);

        // Additional availability actions
        Route::post('{availability}/toggle-status', [App\Http\Controllers\PastorAvailabilityController::class, 'toggleStatus'])
            ->name('toggle-status');

        // AJAX endpoint for time slot preview
        Route::post('preview-slots', [App\Http\Controllers\PastorAvailabilityController::class, 'previewSlots'])
            ->name('preview-slots');
    });

    // Pastoral Care booking for authenticated users
    Route::get('pastoral-care/book', function () {
        $user = auth()->user();
        $canSelectPastor = $user->can('select pastor for pastoral care');

        // Debug log
        \Log::info('Pastoral Care Book - User: '.$user->email.', canSelectPastor: '.($canSelectPastor ? 'true' : 'false'));

        return \Inertia\Inertia::render('PastoralCare/PublicBook', [
            'canSelectPastor' => $canSelectPastor,
        ]);
    })->name('pastoral-care.book');

    // ============================================
    // Workflow Builder Routes
    // ============================================
    Route::prefix('workflows')->name('workflows.')->middleware('can:view workflows')->group(function (): void {
        Route::get('/', [App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\WorkflowController::class, 'create'])->middleware('can:create workflows')->name('create');
        Route::post('/', [App\Http\Controllers\WorkflowController::class, 'store'])->middleware('can:create workflows')->name('store');
        Route::get('/{workflow}', [App\Http\Controllers\WorkflowController::class, 'show'])->name('show');
        Route::get('/{workflow}/edit', [App\Http\Controllers\WorkflowController::class, 'edit'])->middleware('can:edit workflows')->name('edit');
        Route::put('/{workflow}', [App\Http\Controllers\WorkflowController::class, 'update'])->middleware('can:edit workflows')->name('update');
        Route::delete('/{workflow}', [App\Http\Controllers\WorkflowController::class, 'destroy'])->middleware('can:delete workflows')->name('destroy');
        Route::post('/{workflow}/activate', [App\Http\Controllers\WorkflowController::class, 'activate'])->middleware('can:manage workflows')->name('activate');
        Route::post('/{workflow}/deprecate', [App\Http\Controllers\WorkflowController::class, 'deprecate'])->middleware('can:manage workflows')->name('deprecate');
        Route::post('/{workflow}/duplicate', [App\Http\Controllers\WorkflowController::class, 'duplicate'])->middleware('can:create workflows')->name('duplicate');
        Route::post('/{workflow}/steps', [App\Http\Controllers\WorkflowController::class, 'saveSteps'])->middleware('can:edit workflows')->name('save-steps');
        Route::post('/{workflow}/start', [App\Http\Controllers\WorkflowController::class, 'startInstance'])->middleware('can:execute workflows')->name('start');
        Route::get('/{workflow}/instances', [App\Http\Controllers\WorkflowController::class, 'instances'])->name('instances');
    });

    // Workflow Instance Routes
    Route::prefix('workflow-instances')->name('workflow-instances.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\WorkflowInstanceController::class, 'index'])->name('index');
        Route::get('/my-approvals', [App\Http\Controllers\WorkflowInstanceController::class, 'myApprovals'])->name('my-approvals');
        Route::get('/my-tasks', [App\Http\Controllers\WorkflowInstanceController::class, 'myTasks'])->name('my-tasks');
        Route::get('/{workflowInstance}', [App\Http\Controllers\WorkflowInstanceController::class, 'show'])->name('show');
        Route::post('/{workflowInstance}/cancel', [App\Http\Controllers\WorkflowInstanceController::class, 'cancel'])->name('cancel');
        Route::post('/{workflowInstance}/pause', [App\Http\Controllers\WorkflowInstanceController::class, 'pause'])->name('pause');
        Route::post('/{workflowInstance}/resume', [App\Http\Controllers\WorkflowInstanceController::class, 'resume'])->name('resume');
        Route::get('/{workflowInstance}/activity-log', [App\Http\Controllers\WorkflowInstanceController::class, 'activityLog'])->name('activity-log');
    });

    // Step Instance Routes
    Route::post('/step-instances/{stepInstance}/complete', [App\Http\Controllers\WorkflowInstanceController::class, 'completeStep'])
        ->name('step-instances.complete');
    Route::post('/step-instances/{stepInstance}/submit-form', [App\Http\Controllers\WorkflowInstanceController::class, 'submitForm'])
        ->name('step-instances.submit-form');

    // Approval Routes
    Route::post('/approvals/{approval}/submit', [App\Http\Controllers\WorkflowInstanceController::class, 'submitApproval'])
        ->name('approvals.submit');
    Route::post('/approvals/{approval}/delegate', [App\Http\Controllers\WorkflowInstanceController::class, 'delegateApproval'])
        ->name('approvals.delegate');

    // ============================================
    // Form Builder Routes
    // ============================================
    Route::prefix('forms')->name('forms.')->middleware('can:view forms')->group(function (): void {
        Route::get('/', [App\Http\Controllers\FormController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\FormController::class, 'create'])->middleware('can:create forms')->name('create');
        Route::post('/', [App\Http\Controllers\FormController::class, 'store'])->middleware('can:create forms')->name('store');
        Route::post('/import', [App\Http\Controllers\FormController::class, 'import'])->middleware('can:create forms')->name('import');
        Route::get('/{form}', [App\Http\Controllers\FormController::class, 'show'])->name('show');
        Route::get('/{form}/edit', [App\Http\Controllers\FormController::class, 'edit'])->middleware('can:edit forms')->name('edit');
        Route::put('/{form}', [App\Http\Controllers\FormController::class, 'update'])->middleware('can:edit forms')->name('update');
        Route::delete('/{form}', [App\Http\Controllers\FormController::class, 'destroy'])->middleware('can:delete forms')->name('destroy');
        Route::post('/{form}/publish', [App\Http\Controllers\FormController::class, 'publish'])->middleware('can:manage forms')->name('publish');
        Route::post('/{form}/unpublish', [App\Http\Controllers\FormController::class, 'unpublish'])->middleware('can:manage forms')->name('unpublish');
        Route::post('/{form}/archive', [App\Http\Controllers\FormController::class, 'archive'])->middleware('can:manage forms')->name('archive');
        Route::post('/{form}/duplicate', [App\Http\Controllers\FormController::class, 'duplicate'])->middleware('can:create forms')->name('duplicate');
        Route::post('/{form}/fields', [App\Http\Controllers\FormController::class, 'saveFields'])->middleware('can:edit forms')->name('save-fields');
        Route::get('/{form}/preview', [App\Http\Controllers\FormController::class, 'preview'])->name('preview');
        Route::get('/{form}/render', [App\Http\Controllers\FormController::class, 'renderForm'])->name('render');
        Route::post('/{form}/start-submission', [App\Http\Controllers\FormController::class, 'startSubmission'])->middleware('can:submit forms')->name('start-submission');
        Route::get('/{form}/export', [App\Http\Controllers\FormController::class, 'export'])->middleware('can:manage forms')->name('export');
        Route::get('/{form}/submissions', [App\Http\Controllers\FormController::class, 'submissions'])->middleware('can:process form submissions')->name('submissions');
        Route::post('/{form}/share-link', [App\Http\Controllers\FormController::class, 'generateShareLink'])->middleware('can:manage forms')->name('generate-share-link');
    });

    // Public shared form routes (no auth required)
    Route::get('/f/{token}', [App\Http\Controllers\FormController::class, 'renderSharedForm'])->name('forms.shared')->withoutMiddleware(['auth', 'verified']);
    Route::post('/f/{token}/submit', [App\Http\Controllers\FormController::class, 'submitSharedForm'])->name('forms.shared.submit')->withoutMiddleware(['auth', 'verified']);

    // Public shared programme routes (no auth required)
    Route::get('/p/{token}', [App\Http\Controllers\EventProgrammeController::class, 'showShared'])->name('events.programme.shared')->withoutMiddleware(['auth', 'verified']);
    Route::get('/p/{token}/download', [App\Http\Controllers\EventProgrammeController::class, 'downloadShared'])->name('events.programme.shared.download')->withoutMiddleware(['auth', 'verified']);

    // Form Submission Routes
    Route::prefix('form-submissions')->name('form-submissions.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\FormSubmissionController::class, 'index'])->name('index');
        Route::get('/{formSubmission}', [App\Http\Controllers\FormSubmissionController::class, 'show'])->name('show');
        Route::get('/{formSubmission}/edit', [App\Http\Controllers\FormSubmissionController::class, 'edit'])->name('edit');
        Route::put('/{formSubmission}', [App\Http\Controllers\FormSubmissionController::class, 'update'])->name('update');
        Route::post('/{formSubmission}/submit', [App\Http\Controllers\FormSubmissionController::class, 'submit'])->name('submit');
        Route::post('/{formSubmission}/next-step', [App\Http\Controllers\FormSubmissionController::class, 'nextStep'])->name('next-step');
        Route::post('/{formSubmission}/previous-step', [App\Http\Controllers\FormSubmissionController::class, 'previousStep'])->name('previous-step');
        Route::post('/{formSubmission}/validate-step', [App\Http\Controllers\FormSubmissionController::class, 'validateStep'])->name('validate-step');
        Route::post('/{formSubmission}/update-status', [App\Http\Controllers\FormSubmissionController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{formSubmission}', [App\Http\Controllers\FormSubmissionController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Department Need Management Routes
    // ============================================
    Route::prefix('needs')->name('needs.')->middleware('can:view needs')->group(function (): void {
        Route::get('/', [App\Http\Controllers\NeedController::class, 'index'])->name('index');
        Route::get('/kanban', [App\Http\Controllers\NeedController::class, 'kanban'])->name('kanban');
        Route::get('/my-needs', [App\Http\Controllers\NeedController::class, 'myNeeds'])->name('my-needs');
        Route::get('/stats', [App\Http\Controllers\NeedController::class, 'stats'])->name('stats');
        Route::get('/create', [App\Http\Controllers\NeedController::class, 'create'])->middleware('can:create needs')->name('create');
        Route::post('/', [App\Http\Controllers\NeedController::class, 'store'])->middleware('can:create needs')->name('store');
        Route::get('/{need}', [App\Http\Controllers\NeedController::class, 'show'])->name('show');
        Route::get('/{need}/edit', [App\Http\Controllers\NeedController::class, 'edit'])->middleware('can:edit needs')->name('edit');
        Route::put('/{need}', [App\Http\Controllers\NeedController::class, 'update'])->middleware('can:edit needs')->name('update');
        Route::delete('/{need}', [App\Http\Controllers\NeedController::class, 'destroy'])->middleware('can:delete needs')->name('destroy');

        // Status transitions
        Route::post('/{need}/submit', [App\Http\Controllers\NeedController::class, 'submit'])->name('submit');
        Route::post('/{need}/withdraw', [App\Http\Controllers\NeedController::class, 'withdraw'])->name('withdraw');
        Route::post('/{need}/start-review', [App\Http\Controllers\NeedController::class, 'startReview'])->middleware('can:manage needs')->name('start-review');
        Route::post('/{need}/approve', [App\Http\Controllers\NeedController::class, 'approve'])->middleware('can:approve needs')->name('approve');
        Route::post('/{need}/reject', [App\Http\Controllers\NeedController::class, 'reject'])->middleware('can:approve needs')->name('reject');
        Route::post('/{need}/order', [App\Http\Controllers\NeedController::class, 'markOrdered'])->middleware('can:manage needs')->name('order');
        Route::post('/{need}/deliver', [App\Http\Controllers\NeedController::class, 'markDelivered'])->middleware('can:manage needs')->name('deliver');
        Route::post('/{need}/complete', [App\Http\Controllers\NeedController::class, 'complete'])->middleware('can:manage needs')->name('complete');
        Route::post('/{need}/cancel', [App\Http\Controllers\NeedController::class, 'cancel'])->middleware('can:manage needs')->name('cancel');
        Route::post('/{need}/assign', [App\Http\Controllers\NeedController::class, 'assign'])->middleware('can:manage needs')->name('assign');
        Route::post('/{need}/duplicate', [App\Http\Controllers\NeedController::class, 'duplicate'])->middleware('can:create needs')->name('duplicate');
        Route::patch('/{need}/update-status', [App\Http\Controllers\NeedController::class, 'updateStatus'])->middleware('can:manage needs')->name('update-status');

        // Attachments
        Route::post('/{need}/attachments', [App\Http\Controllers\NeedController::class, 'uploadAttachment'])->name('attachments.upload');

        // Comments
        Route::get('/{need}/comments', [App\Http\Controllers\NeedController::class, 'comments'])->name('comments.list');
        Route::post('/{need}/comments', [App\Http\Controllers\NeedController::class, 'addComment'])->name('comments.add');

        // History
        Route::get('/{need}/history', [App\Http\Controllers\NeedController::class, 'history'])->name('history');
    });

    // Need Attachment Routes
    Route::delete('/need-attachments/{attachment}', [App\Http\Controllers\NeedController::class, 'deleteAttachment'])
        ->name('need-attachments.destroy');

    // Need Comment Routes
    Route::put('/need-comments/{comment}', [App\Http\Controllers\NeedController::class, 'updateComment'])
        ->name('need-comments.update');
    Route::delete('/need-comments/{comment}', [App\Http\Controllers\NeedController::class, 'deleteComment'])
        ->name('need-comments.destroy');

    // Department Reports
    Route::prefix('reports')->name('reports.')->middleware('can:view reports')->group(function (): void {
        Route::get('/', [App\Http\Controllers\DepartmentReportController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\DepartmentReportController::class, 'create'])->middleware('can:generate reports')->name('create');
        Route::post('/', [App\Http\Controllers\DepartmentReportController::class, 'store'])->middleware('can:generate reports')->name('store');
        Route::get('/{report}', [App\Http\Controllers\DepartmentReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [App\Http\Controllers\DepartmentReportController::class, 'edit'])->middleware('can:generate reports')->name('edit');
        Route::put('/{report}', [App\Http\Controllers\DepartmentReportController::class, 'update'])->middleware('can:generate reports')->name('update');
        Route::delete('/{report}', [App\Http\Controllers\DepartmentReportController::class, 'destroy'])->middleware('can:generate reports')->name('destroy');

        // Section management
        Route::put('/{report}/sections/{section}', [App\Http\Controllers\DepartmentReportController::class, 'updateSection'])->middleware('can:generate reports')->name('sections.update');

        // Report actions
        Route::post('/{report}/populate', [App\Http\Controllers\DepartmentReportController::class, 'populate'])->middleware('can:generate reports')->name('populate');
        Route::post('/{report}/submit', [App\Http\Controllers\DepartmentReportController::class, 'submit'])->middleware('can:generate reports')->name('submit');
        Route::post('/{report}/approve', [App\Http\Controllers\DepartmentReportController::class, 'approve'])->middleware('can:generate reports')->name('approve');
        Route::post('/{report}/publish', [App\Http\Controllers\DepartmentReportController::class, 'publish'])->middleware('can:generate reports')->name('publish');
        Route::post('/{report}/archive', [App\Http\Controllers\DepartmentReportController::class, 'archive'])->middleware('can:generate reports')->name('archive');
        Route::post('/{report}/duplicate', [App\Http\Controllers\DepartmentReportController::class, 'duplicate'])->middleware('can:generate reports')->name('duplicate');

        // PDF Export
        Route::get('/{report}/export', [App\Http\Controllers\DepartmentReportController::class, 'export'])->name('export');
        Route::post('/{report}/generate-pdf', [App\Http\Controllers\DepartmentReportController::class, 'generatePdf'])->middleware('can:generate reports')->name('generate-pdf');
        Route::get('/{report}/download-pdf', [App\Http\Controllers\DepartmentReportController::class, 'downloadPdf'])->name('download-pdf');
        Route::get('/{report}/stream-pdf', [App\Http\Controllers\DepartmentReportController::class, 'streamPdf'])->name('stream-pdf');
        Route::post('/{report}/regenerate-pdf', [App\Http\Controllers\DepartmentReportController::class, 'regeneratePdf'])->middleware('can:generate reports')->name('regenerate-pdf');
        Route::get('/{report}/preview', [App\Http\Controllers\DepartmentReportController::class, 'preview'])->name('preview');

        // Comments
        Route::post('/{report}/comments', [App\Http\Controllers\DepartmentReportController::class, 'addComment'])->name('comments.add');
        Route::post('/comments/{comment}/resolve', [App\Http\Controllers\DepartmentReportController::class, 'resolveComment'])->name('comments.resolve');

        // Attachments
        Route::post('/{report}/attachments', [App\Http\Controllers\DepartmentReportController::class, 'addAttachment'])->name('attachments.add');
        Route::delete('/{report}/attachments/{attachment}', [App\Http\Controllers\DepartmentReportController::class, 'removeAttachment'])->name('attachments.remove');

        // Versions
        Route::get('/{report}/versions/{version1}/compare/{version2}', [App\Http\Controllers\DepartmentReportController::class, 'compareVersions'])->name('versions.compare');
    });

    // Department generated reports API
    Route::get('/departments/{department}/generated-reports', [App\Http\Controllers\DepartmentReportController::class, 'listGeneratedReports'])
        ->name('departments.generated-reports');

});

// Public Training routes (outside auth middleware)

// Public appointment confirmation routes (accessible without authentication)
Route::get('appointments/{appointment:uuid}/confirm/{token}', [App\Http\Controllers\AppointmentParticipantController::class, 'confirm'])
    ->name('appointments.participant.confirm')
    ->missing(function (\Illuminate\Http\Request $request) {
        $appointmentId = $request->route('appointment');
        \Illuminate\Support\Facades\Log::warning('Appointment confirmation attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });
Route::get('appointments/{appointment:uuid}/decline/{token}', [App\Http\Controllers\AppointmentParticipantController::class, 'decline'])
    ->name('appointments.participant.decline')
    ->missing(function (\Illuminate\Http\Request $request) {
        $appointmentId = $request->route('appointment');
        \Illuminate\Support\Facades\Log::warning('Appointment decline attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });
Route::post('appointments/{appointment:uuid}/decline/{token}', [App\Http\Controllers\AppointmentParticipantController::class, 'decline'])
    ->name('appointments.participant.decline.submit')
    ->missing(function (\Illuminate\Http\Request $request) {
        $appointmentId = $request->route('appointment');
        \Illuminate\Support\Facades\Log::warning('Appointment decline submission attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });

// Public Pastoral Care routes (accessible without authentication)
Route::get('pastoral-care/appointments/{uuid}/confirm', function ($uuid) {
    $appointment = \App\Models\PastoralCare::where('uuid', $uuid)->first();

    if (! $appointment) {
        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return \Inertia\Inertia::render('PastoralCare/PublicConfirm', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('pastoral-care.public.confirm');

Route::post('pastoral-care/appointments/{uuid}/confirm', function (\Illuminate\Http\Request $request, $uuid) {
    $appointment = \App\Models\PastoralCare::where('uuid', $uuid)->first();

    if (! $appointment) {
        return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable'], 404);
    }

    try {
        $appointment->confirm();

        return redirect()->route('pastoral-care.public.success', ['uuid' => $uuid])
            ->with('success', 'Rendez-vous confirmé avec succès.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
})->name('pastoral-care.public.confirm.submit');

Route::get('pastoral-care/appointments/{uuid}/cancel', function ($uuid) {
    $appointment = \App\Models\PastoralCare::where('uuid', $uuid)->first();

    if (! $appointment) {
        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return \Inertia\Inertia::render('PastoralCare/PublicCancel', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('pastoral-care.public.cancel');

Route::post('pastoral-care/appointments/{uuid}/cancel', function (\Illuminate\Http\Request $request, $uuid) {
    $appointment = \App\Models\PastoralCare::where('uuid', $uuid)->first();

    if (! $appointment) {
        return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable'], 404);
    }

    $validated = $request->validate([
        'cancellation_reason' => 'nullable|string|max:500',
    ]);

    try {
        $appointment->cancel($validated['cancellation_reason'] ?? null);

        return redirect()->route('pastoral-care.public.success', ['uuid' => $uuid])
            ->with('success', 'Rendez-vous annulé avec succès.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
})->name('pastoral-care.public.cancel.submit');

Route::get('pastoral-care/appointments/{uuid}/success', function ($uuid) {
    $appointment = \App\Models\PastoralCare::where('uuid', $uuid)->first();

    if (! $appointment) {
        return \Inertia\Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return \Inertia\Inertia::render('PastoralCare/PublicSuccess', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('pastoral-care.public.success');

// Public Pastoral Care booking interface (moved to authenticated section)

// Sentry test routes (only available in local/development environments)
Route::middleware(['auth'])->group(function (): void {
    Route::get('sentry/test-error', [App\Http\Controllers\SentryTestController::class, 'testError'])
        ->name('sentry.test-error');
    Route::get('sentry/test-message', [App\Http\Controllers\SentryTestController::class, 'testMessage'])
        ->name('sentry.test-message');
    Route::get('sentry/test-breadcrumbs', [App\Http\Controllers\SentryTestController::class, 'testBreadcrumbs'])
        ->name('sentry.test-breadcrumbs');
});

require __DIR__.'/auth.php';
