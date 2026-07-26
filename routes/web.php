<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Agile\AcceptanceCriterionController;
use App\Http\Controllers\Agile\StoryTaskController;
use App\Http\Controllers\Agile\TestScenarioController;
use App\Http\Controllers\Agile\UserStoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentParticipantController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookRentalController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\CareServiceAvailabilityController;
use App\Http\Controllers\CareServiceController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentPositionController;
use App\Http\Controllers\DepartmentPositionNominationController;
use App\Http\Controllers\DepartmentReportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EpicController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventMediaController;
use App\Http\Controllers\EventProgrammeController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\GanttController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupVisitorController;
use App\Http\Controllers\HeroSlideController;
use App\Http\Controllers\HomepageSectionController;
use App\Http\Controllers\IntegrationPathwayController;
use App\Http\Controllers\IntegrationSuggestionController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgramStepController;
use App\Http\Controllers\ProgramStepTaskController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicAgendaController;
use App\Http\Controllers\QuizClassAssignmentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoutineStepController;
use App\Http\Controllers\Scheduling\AbsenceController;
use App\Http\Controllers\Scheduling\AvailabilityController;
use App\Http\Controllers\Scheduling\DepartmentTodoController;
use App\Http\Controllers\Scheduling\ScheduleController;
use App\Http\Controllers\Scheduling\ShiftController;
use App\Http\Controllers\Scheduling\ShiftSwapController;
use App\Http\Controllers\SentryTestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TrainingClassController;
use App\Http\Controllers\TrainingClassMaterialController;
use App\Http\Controllers\TrainingClassScheduleController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingEnrollmentController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowInstanceController;
use App\Models\CareService;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\SiteSetting;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $heroSlides = HeroSlide::active()->get();
    $globalStats = SiteSetting::getGlobalStats();
    $sections = HomepageSection::with(['subsections' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
        ->active()
        ->get();
    $hasConfiguredSections = HomepageSection::query()->exists();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'heroSlides' => $heroSlides,
        'globalStats' => $globalStats,
        'sections' => $sections,
        'hasConfiguredSections' => $hasConfiguredSections,
    ]);
});

// Legal pages (public access)
Route::get('/privacy-policy', fn () => Inertia::render('Legal/PrivacyPolicy'))->name('privacy-policy');

Route::get('/terms-of-service', fn () => Inertia::render('Legal/TermsOfService'))->name('terms-of-service');

// CAPTCHA generation (public access for registration)
Route::get('/captcha', [CaptchaController::class, 'generate'])->name('captcha.generate');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'restrict.member'])
    ->name('dashboard');

// User dashboard for members
Route::get('/user/dashboard', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('user.dashboard');

// Hero Slides routes
Route::resource('hero-slides', HeroSlideController::class);

// Contact routes (public create/store, admin for the rest)
Route::get('contact', [ContactController::class, 'create'])->name('contacts.create');
Route::post('contact', [ContactController::class, 'store'])->name('contacts.store');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Contact management routes (admin only)
    Route::resource('contacts', ContactController::class)->except(['create', 'store']);
    Route::get('/profile/{user:uuid}', [ProfileController::class, 'publicShow'])->name('profile.public');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Accounting routes
    Route::get('accounting', [AccountingController::class, 'index'])
        ->name('accounting.index')
        ->middleware('can:view accounting');

    // Event routes
    Route::resource('events', EventController::class);
    Route::post('events/{event}/toggle-participation', [EventController::class, 'toggleParticipation'])
        ->name('events.toggle-participation');
    Route::post('events/{event}/join', [EventController::class, 'join'])
        ->name('events.join');
    Route::delete('events/{event}/leave', [EventController::class, 'leave'])
        ->name('events.leave');

    // Event Media routes
    Route::get('events/{event}/media', [EventMediaController::class, 'index'])
        ->name('events.media.index');
    Route::post('events/{event}/media', [EventMediaController::class, 'store'])
        ->name('events.media.store');
    Route::post('events/{event}/media/tus', [EventMediaController::class, 'storeFromTus'])
        ->name('events.media.store-tus');
    Route::put('events/{event}/media/{media}', [EventMediaController::class, 'update'])
        ->name('events.media.update');
    Route::delete('events/{event}/media/{media}', [EventMediaController::class, 'destroy'])
        ->name('events.media.destroy');
    Route::post('events/{event}/media/reorder', [EventMediaController::class, 'reorder'])
        ->name('events.media.reorder');
    Route::post('events/{event}/media/{media}/set-banner', [EventMediaController::class, 'setBanner'])
        ->name('events.media.set-banner');
    Route::post('events/{event}/media/{media}/set-featured', [EventMediaController::class, 'setFeatured'])
        ->name('events.media.set-featured');

    // Event Programme routes
    Route::post('events/{event}/programme', [EventProgrammeController::class, 'store'])
        ->name('events.programme.store');
    Route::delete('events/{event}/programme', [EventProgrammeController::class, 'destroy'])
        ->name('events.programme.destroy');
    Route::post('events/{event}/programme/share-link', [EventProgrammeController::class, 'generateShareLink'])
        ->name('events.programme.generate-share-link');
    Route::post('events/{event}/programme/renew-link', [EventProgrammeController::class, 'renewShareLink'])
        ->name('events.programme.renew-share-link');
    Route::post('events/{event}/programme/revoke-link', [EventProgrammeController::class, 'revokeShareLink'])
        ->name('events.programme.revoke-share-link');

    // Book routes
    Route::resource('books', BookController::class);
    Route::post('books/{book}/rent', [BookController::class, 'rent'])
        ->name('books.rent');

    // Book rental routes
    Route::get('my-rentals', [BookRentalController::class, 'index'])
        ->name('book-rentals.index');
    Route::get('my-rentals/{rental}', [BookRentalController::class, 'show'])
        ->name('book-rentals.show');
    Route::post('my-rentals/{rental}/return', [BookRentalController::class, 'returnBook'])
        ->name('book-rentals.return');
    Route::post('my-rentals/{rental}/extend', [BookRentalController::class, 'extendRental'])
        ->name('book-rentals.extend');

    // Admin book rental routes
    Route::get('admin/book-rentals', [BookRentalController::class, 'adminIndex'])
        ->name('admin.book-rentals.index')
        ->middleware('can:manage library');
    Route::delete('admin/book-rentals/{rental}', [BookRentalController::class, 'destroy'])
        ->name('admin.book-rentals.destroy')
        ->middleware('can:manage library');

    // Article routes - order matters: specific routes before generic ones
    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('articles/{article}/like', [MarkController::class, 'toggleLike'])
        ->name('articles.like');
    Route::post('articles/{article}/favorite', [MarkController::class, 'toggleBookmark'])
        ->name('articles.favorite');

    // Apply rate limiting to article store/update
    Route::post('articles', [ArticleController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('articles.store');
    Route::put('articles/{article}', [ArticleController::class, 'update'])
        ->middleware('throttle:uploads')
        ->name('articles.update');

    // Tag routes
    Route::resource('tags', TagController::class);

    // Task routes
    Route::resource('tasks', TaskController::class);
    Route::patch('tasks/{task}/toggle-complete', [TaskController::class, 'toggleComplete'])
        ->name('tasks.toggle-complete');
    Route::post('tasks/bulk-toggle-complete', [TaskController::class, 'bulkToggleComplete'])
        ->name('tasks.bulk-toggle-complete');
    Route::patch('tasks/{task}/progress', [TaskController::class, 'updateProgress'])
        ->name('tasks.update-progress');
    Route::patch('tasks/{task}/inline-update', [TaskController::class, 'inlineUpdate'])
        ->name('tasks.inline-update');

    // Task Participants
    Route::post('tasks/{task}/participants', [TaskController::class, 'addParticipant'])
        ->name('tasks.participants.add');
    Route::patch('tasks/{task}/participants/{participant}', [TaskController::class, 'updateParticipant'])
        ->name('tasks.participants.update');
    Route::delete('tasks/{task}/participants/{participant}', [TaskController::class, 'removeParticipant'])
        ->name('tasks.participants.remove');

    // Task Comments
    Route::post('tasks/{task}/comments', [TaskController::class, 'addComment'])
        ->name('tasks.comments.add');
    Route::patch('tasks/{task}/comments/{comment}', [TaskController::class, 'updateComment'])
        ->name('tasks.comments.update');
    Route::delete('tasks/{task}/comments/{comment}', [TaskController::class, 'deleteComment'])
        ->name('tasks.comments.delete');

    // Subtasks
    Route::post('tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])
        ->name('tasks.subtasks.store');
    Route::delete('tasks/{task}/subtasks/{subtask}', [TaskController::class, 'destroySubtask'])
        ->name('tasks.subtasks.destroy');

    // Task Attachments
    Route::post('tasks/{task}/attachments', [TaskController::class, 'addAttachment'])
        ->name('tasks.attachments.add');
    Route::delete('tasks/{task}/attachments/{attachment}', [TaskController::class, 'deleteAttachment'])
        ->name('tasks.attachments.delete');
    Route::get('tasks/{task}/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])
        ->name('tasks.attachments.download');

    // Program routes
    Route::resource('programs', ProgramController::class)
        ->middleware('can:view programs');

    // Program Step routes
    Route::post('programs/{program}/steps', [ProgramStepController::class, 'store'])
        ->middleware('can:create program steps')
        ->name('programs.steps.store');
    Route::patch('programs/{program}/steps/{step}', [ProgramStepController::class, 'update'])
        ->middleware('can:edit programs')
        ->name('programs.steps.update');
    Route::delete('programs/{program}/steps/{step}', [ProgramStepController::class, 'destroy'])
        ->middleware('can:delete programs')
        ->name('programs.steps.destroy');
    Route::post('programs/{program}/steps/{step}/participants', [ProgramStepController::class, 'attachParticipant'])
        ->middleware('can:edit programs')
        ->name('programs.steps.participants.attach');
    Route::delete('programs/{program}/steps/{step}/participants/{user}', [ProgramStepController::class, 'detachParticipant'])
        ->middleware('can:edit programs')
        ->name('programs.steps.participants.detach');

    // Program Step Task routes
    Route::post('programs/{program}/steps/{step}/tasks', [ProgramStepTaskController::class, 'store'])
        ->middleware('can:create tasks')
        ->name('programs.steps.tasks.store');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}', [ProgramStepTaskController::class, 'update'])
        ->middleware('can:edit tasks')
        ->name('programs.steps.tasks.update');
    Route::delete('programs/{program}/steps/{step}/tasks/{task}', [ProgramStepTaskController::class, 'destroy'])
        ->middleware('can:delete tasks')
        ->name('programs.steps.tasks.destroy');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}/status', [ProgramStepTaskController::class, 'updateStatus'])
        ->middleware('can:edit tasks')
        ->name('programs.steps.tasks.update-status');

    // Stock routes
    Route::resource('stocks', StockController::class)
        ->middleware('can:view stocks');

    // Employee routes
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])
        ->name('employees.terminate');
    Route::post('employees/{employee}/activate', [EmployeeController::class, 'activate'])
        ->name('employees.activate');
    Route::post('employees/{employee}/on-leave', [EmployeeController::class, 'setOnLeave'])
        ->name('employees.on-leave');
    Route::post('employees/{employee}/reset-leave', [EmployeeController::class, 'resetLeave'])
        ->name('employees.reset-leave');
    Route::get('employees-export', [EmployeeController::class, 'export'])
        ->name('employees.export');

    // Volunteer (Volunteers) routes
    Route::resource('volunteers', VolunteerController::class);
    Route::post('volunteers/{volunteer}/activate', [VolunteerController::class, 'activate'])
        ->name('volunteers.activate');
    Route::post('volunteers/{volunteer}/deactivate', [VolunteerController::class, 'deactivate'])
        ->name('volunteers.deactivate');
    Route::post('volunteers/{volunteer}/on-break', [VolunteerController::class, 'setOnBreak'])
        ->name('volunteers.on-break');
    Route::post('volunteers/{volunteer}/graduate', [VolunteerController::class, 'graduate'])
        ->name('volunteers.graduate');
    Route::post('volunteers/{volunteer}/suspend', [VolunteerController::class, 'suspend'])
        ->name('volunteers.suspend');
    Route::post('volunteers/{volunteer}/add-points', [VolunteerController::class, 'addPoints'])
        ->name('volunteers.add-points');
    Route::post('volunteers/{volunteer}/toggle-featured', [VolunteerController::class, 'toggleFeatured'])
        ->name('volunteers.toggle-featured');
    Route::post('volunteers/{volunteer}/renew', [VolunteerController::class, 'renew'])
        ->name('volunteers.renew');
    Route::get('volunteers-export', [VolunteerController::class, 'export'])
        ->name('volunteers.export');

    // Group routes
    Route::resource('groups', GroupController::class);
    Route::post('groups/{group}/join', [GroupController::class, 'join'])
        ->name('groups.join');
    Route::delete('groups/{group}/leave', [GroupController::class, 'leave'])
        ->name('groups.leave');
    Route::post('groups/{group}/add-member', [GroupController::class, 'addMember'])
        ->name('groups.add-member');
    Route::delete('groups/{group}/users/{user}', [GroupController::class, 'removeMember'])
        ->name('groups.remove-member');

    // Group Visitors routes
    Route::prefix('groups/{group:uuid}/visitors')->name('groups.visitors.')->group(function (): void {
        Route::get('/', [GroupVisitorController::class, 'index'])->name('index');
        Route::post('/', [GroupVisitorController::class, 'store'])->name('store');
        Route::post('/attendance', [GroupVisitorController::class, 'bulkRecordAttendance'])->name('bulk-attendance');
        Route::post('/{visitor:uuid}/attendance', [GroupVisitorController::class, 'recordAttendance'])->name('record-attendance')->withoutScopedBindings();
        Route::delete('/{visitor:uuid}', [GroupVisitorController::class, 'removeVisitor'])->name('remove')->withoutScopedBindings();
        Route::get('/dashboard', [GroupVisitorController::class, 'integrationDashboard'])->name('dashboard');
    });

    // Visitor CRUD routes
    Route::resource('visitors', VisitorController::class);

    // Integration Pathways routes
    Route::prefix('integration-pathways')->name('integration-pathways.')->group(function (): void {
        Route::get('/', [IntegrationPathwayController::class, 'index'])->name('index');
        Route::post('/', [IntegrationPathwayController::class, 'store'])->name('store');
        Route::get('/{template:uuid}', [IntegrationPathwayController::class, 'show'])->name('show');
        Route::put('/{template:uuid}', [IntegrationPathwayController::class, 'update'])->name('update');
        Route::delete('/{template:uuid}', [IntegrationPathwayController::class, 'destroy'])->name('destroy');
        Route::post('/{template:uuid}/steps', [IntegrationPathwayController::class, 'addStep'])->name('steps.store');
        Route::put('/{template:uuid}/steps/{step:uuid}', [IntegrationPathwayController::class, 'updateStep'])->name('steps.update');
        Route::delete('/{template:uuid}/steps/{step:uuid}', [IntegrationPathwayController::class, 'removeStep'])->name('steps.destroy');
        Route::post('/{template:uuid}/steps/reorder', [IntegrationPathwayController::class, 'reorderSteps'])->name('steps.reorder');
    });

    // Integration Suggestions routes
    Route::get('/integration-suggestions', [IntegrationSuggestionController::class, 'index'])
        ->name('integration-suggestions.index');
    Route::post('/integration-suggestions/{suggestion:uuid}/respond', [IntegrationSuggestionController::class, 'respond'])
        ->name('integration-suggestions.respond');

    // Department routes
    Route::resource('departments', DepartmentController::class);
    Route::post('departments/{department}/assign-user', [DepartmentController::class, 'assignUser'])
        ->name('departments.assign-user');
    Route::delete('departments/{department}/users/{user}', [DepartmentController::class, 'removeUser'])
        ->name('departments.remove-user');

    // Department Position Nominations
    Route::prefix('departments/{department}/nominations')->name('departments.nominations.')->group(function (): void {
        Route::get('/', [DepartmentPositionNominationController::class, 'index'])->name('index');
        Route::post('/', [DepartmentPositionNominationController::class, 'store'])->name('store');
        Route::put('/{nomination}', [DepartmentPositionNominationController::class, 'update'])->name('update');
        Route::delete('/{nomination}', [DepartmentPositionNominationController::class, 'destroy'])->name('destroy');
    });

    // Department Positions
    Route::prefix('departments/{department}/positions')->name('departments.positions.')->group(function (): void {
        Route::get('/', [DepartmentPositionController::class, 'index'])->name('index');
        Route::post('/', [DepartmentPositionController::class, 'store'])->name('store');
        Route::put('/{position}', [DepartmentPositionController::class, 'update'])->name('update');
        Route::delete('/{position}', [DepartmentPositionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [DepartmentPositionController::class, 'reorder'])->name('reorder');
    });

    // ============================================
    // Department Scheduling Routes
    // ============================================
    Route::prefix('departments/{department}/schedule')->name('departments.schedule.')->group(function (): void {
        // Weekly Schedule routes
        Route::get('/', [ScheduleController::class, 'index'])
            ->name('index');
        Route::post('/', [ScheduleController::class, 'store'])
            ->name('store');
        Route::get('/{schedule}', [ScheduleController::class, 'show'])
            ->name('show');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])
            ->name('destroy');
        Route::post('/{schedule}/publish', [ScheduleController::class, 'publish'])
            ->name('publish');
        Route::post('/{schedule}/lock', [ScheduleController::class, 'lock'])
            ->name('lock');
        Route::post('/{schedule}/copy', [ScheduleController::class, 'copy'])
            ->name('copy');
        Route::post('/{schedule}/auto-assign', [ScheduleController::class, 'autoAssign'])
            ->name('auto-assign');
        Route::get('/{schedule}/stats', [ScheduleController::class, 'stats'])
            ->name('stats');

        // Shift routes
        Route::get('/{schedule}/shifts', [ShiftController::class, 'index'])
            ->name('shifts.index');
        Route::get('/{schedule}/shifts/create', [ShiftController::class, 'create'])
            ->name('shifts.create');
        Route::post('/{schedule}/shifts', [ShiftController::class, 'store'])
            ->name('shifts.store');
        Route::post('/{schedule}/shifts/bulk', [ShiftController::class, 'bulkStore'])
            ->name('shifts.bulk-store');
        Route::delete('/{schedule}/shifts/bulk', [ShiftController::class, 'bulkDestroy'])
            ->name('shifts.bulk-destroy');
        Route::get('/{schedule}/shifts/{shift}', [ShiftController::class, 'show'])
            ->name('shifts.show');
        Route::get('/{schedule}/shifts/{shift}/edit', [ShiftController::class, 'edit'])
            ->name('shifts.edit');
        Route::put('/{schedule}/shifts/{shift}', [ShiftController::class, 'update'])
            ->name('shifts.update');
        Route::delete('/{schedule}/shifts/{shift}', [ShiftController::class, 'destroy'])
            ->name('shifts.destroy');
        Route::post('/{schedule}/shifts/{shift}/assign', [ShiftController::class, 'assign'])
            ->name('shifts.assign');
        Route::delete('/{schedule}/shifts/{shift}/unassign', [ShiftController::class, 'unassign'])
            ->name('shifts.unassign');
        Route::post('/{schedule}/shifts/{shift}/check-in', [ShiftController::class, 'checkIn'])
            ->name('shifts.check-in');
        Route::post('/{schedule}/shifts/{shift}/check-out', [ShiftController::class, 'checkOut'])
            ->name('shifts.check-out');
        Route::post('/{schedule}/shifts/{shift}/cancel', [ShiftController::class, 'cancel'])
            ->name('shifts.cancel');
        Route::get('/{schedule}/shifts/{shift}/available-employees', [ShiftController::class, 'availableEmployees'])
            ->name('shifts.available-employees');
        Route::post('/{schedule}/shifts/{shift}/add-user', [ShiftController::class, 'addUser'])
            ->name('shifts.add-user');
        Route::delete('/{schedule}/shifts/{shift}/remove-user', [ShiftController::class, 'removeUser'])
            ->name('shifts.remove-user');
    });

    // Availability routes (under department)
    Route::prefix('departments/{department}/availability')->name('departments.availability.')->group(function (): void {
        Route::get('/', [AvailabilityController::class, 'index'])
            ->name('index');
        Route::get('/my', [AvailabilityController::class, 'myAvailability'])
            ->name('my');
        Route::post('/my', [AvailabilityController::class, 'storeMyAvailability'])
            ->name('my.store');
        Route::post('/', [AvailabilityController::class, 'store'])
            ->name('store');
        Route::post('/weekly', [AvailabilityController::class, 'storeWeekly'])
            ->name('store-weekly');
        Route::post('/bulk', [AvailabilityController::class, 'bulkStore'])
            ->name('bulk-store');
        Route::delete('/{date}', [AvailabilityController::class, 'destroy'])
            ->name('destroy');
        Route::delete('/weekly/{dayOfWeek}', [AvailabilityController::class, 'destroyWeekly'])
            ->name('destroy-weekly');
        Route::get('/date/{date}', [AvailabilityController::class, 'getForDate'])
            ->name('for-date');
        Route::get('/member/{user}', [AvailabilityController::class, 'getMemberWeekAvailability'])
            ->name('member-week');
        Route::get('/available-employees', [AvailabilityController::class, 'getAvailableEmployees'])
            ->name('available-employees');
    });

    // Absence routes (under department)
    Route::prefix('departments/{department}/absences')->name('departments.absences.')->group(function (): void {
        Route::get('/', [AbsenceController::class, 'index'])
            ->name('index');
        Route::get('/my', [AbsenceController::class, 'myAbsences'])
            ->name('my');
        Route::get('/create', [AbsenceController::class, 'create'])
            ->name('create');
        Route::get('/pending-count', [AbsenceController::class, 'pendingCount'])
            ->name('pending-count');
        Route::get('/calendar', [AbsenceController::class, 'calendar'])
            ->name('calendar');
        Route::get('/search-interim', [AbsenceController::class, 'searchInterimCandidates'])
            ->name('search-interim');
        Route::post('/', [AbsenceController::class, 'store'])
            ->name('store');
        Route::get('/{absence}', [AbsenceController::class, 'show'])
            ->name('show');
        Route::get('/{absence}/edit', [AbsenceController::class, 'edit'])
            ->name('edit');
        Route::put('/{absence}', [AbsenceController::class, 'update'])
            ->name('update');
        Route::post('/{absence}/approve', [AbsenceController::class, 'approve'])
            ->name('approve');
        Route::post('/{absence}/reject', [AbsenceController::class, 'reject'])
            ->name('reject');
        Route::post('/{absence}/cancel', [AbsenceController::class, 'cancel'])
            ->name('cancel');
        Route::delete('/{absence}', [AbsenceController::class, 'destroy'])
            ->name('destroy');
    });

    // Shift Swap routes (under department)
    Route::prefix('departments/{department}/swap-requests')->name('departments.swap-requests.')->group(function (): void {
        Route::get('/', [ShiftSwapController::class, 'index'])
            ->name('index');
        Route::get('/my', [ShiftSwapController::class, 'mySwapRequests'])
            ->name('my');
        Route::get('/create', [ShiftSwapController::class, 'create'])
            ->name('create');
        Route::post('/', [ShiftSwapController::class, 'store'])
            ->name('store');
        Route::get('/{swapRequest}', [ShiftSwapController::class, 'show'])
            ->name('show');
        Route::post('/{swapRequest}/accept-colleague', [ShiftSwapController::class, 'acceptByColleague'])
            ->name('accept-colleague');
        Route::post('/{swapRequest}/reject-colleague', [ShiftSwapController::class, 'rejectByColleague'])
            ->name('reject-colleague');
        Route::post('/{swapRequest}/approve-manager', [ShiftSwapController::class, 'approveByManager'])
            ->name('approve-manager');
        Route::post('/{swapRequest}/reject-manager', [ShiftSwapController::class, 'rejectByManager'])
            ->name('reject-manager');
        Route::post('/{swapRequest}/cancel', [ShiftSwapController::class, 'cancel'])
            ->name('cancel');
        Route::delete('/{swapRequest}', [ShiftSwapController::class, 'destroy'])
            ->name('destroy');
        Route::get('/pending-count', [ShiftSwapController::class, 'pendingCount'])
            ->name('pending-count');
    });

    // Routine routes (under department)
    Route::prefix('departments/{department}/routines')->name('departments.routines.')->group(function (): void {
        Route::get('/', [RoutineController::class, 'index'])
            ->name('index');
        Route::get('/create', [RoutineController::class, 'create'])
            ->name('create');
        Route::post('/', [RoutineController::class, 'store'])
            ->name('store');
        Route::get('/{routine}', [RoutineController::class, 'show'])
            ->name('show');
        Route::get('/{routine}/edit', [RoutineController::class, 'edit'])
            ->name('edit');
        Route::put('/{routine}', [RoutineController::class, 'update'])
            ->name('update');
        Route::delete('/{routine}', [RoutineController::class, 'destroy'])
            ->name('destroy');

        // Status transitions
        Route::post('/{routine}/submit', [RoutineController::class, 'submitForApproval'])
            ->name('submit');
        Route::post('/{routine}/approve', [RoutineController::class, 'approve'])
            ->name('approve');
        Route::post('/{routine}/reject', [RoutineController::class, 'reject'])
            ->name('reject');
        Route::post('/{routine}/activate', [RoutineController::class, 'activate'])
            ->name('activate');
        Route::post('/{routine}/archive', [RoutineController::class, 'archive'])
            ->name('archive');

        // Steps
        Route::post('/{routine}/steps', [RoutineStepController::class, 'store'])
            ->name('steps.store');
        Route::put('/{routine}/steps/{step}', [RoutineStepController::class, 'update'])
            ->name('steps.update');
        Route::delete('/{routine}/steps/{step}', [RoutineStepController::class, 'destroy'])
            ->name('steps.destroy');
        Route::post('/{routine}/steps/reorder', [RoutineStepController::class, 'reorder'])
            ->name('steps.reorder');
        Route::post('/{routine}/steps/{step}/validate', [RoutineStepController::class, 'validateStep'])
            ->name('steps.validate');
        Route::post('/{routine}/steps/{step}/reject', [RoutineStepController::class, 'rejectStep'])
            ->name('steps.reject');

        // SOPs
        Route::post('/{routine}/sops', [RoutineController::class, 'storeSop'])
            ->name('sops.store');
        Route::get('/{routine}/sops/{sop}/download', [RoutineController::class, 'downloadSop'])
            ->name('sops.download');
        Route::put('/{routine}/sops/{sop}/status', [RoutineController::class, 'updateSopStatus'])
            ->name('sops.update-status');
        Route::delete('/{routine}/sops/{sop}', [RoutineController::class, 'destroySop'])
            ->name('sops.destroy');

        // Assignees
        Route::post('/{routine}/assignees', [RoutineController::class, 'addAssignee'])
            ->name('assignees.store');
        Route::delete('/{routine}/assignees/{assignee}', [RoutineController::class, 'removeAssignee'])
            ->name('assignees.destroy');
    });

    // Department TODOs routes
    Route::prefix('departments/{department}/todos')->name('departments.todos.')->group(function (): void {
        Route::get('/', [DepartmentTodoController::class, 'index'])
            ->name('index');
        Route::post('/', [DepartmentTodoController::class, 'store'])
            ->name('store');
        Route::get('/{todo}', [DepartmentTodoController::class, 'show'])
            ->name('show');
        Route::put('/{todo}', [DepartmentTodoController::class, 'update'])
            ->name('update');
        Route::delete('/{todo}', [DepartmentTodoController::class, 'destroy'])
            ->name('destroy');
        Route::patch('/{todo}/inline-update', [DepartmentTodoController::class, 'inlineUpdate'])
            ->name('inline-update');
        Route::post('/{todo}/toggle-complete', [DepartmentTodoController::class, 'toggleComplete'])
            ->name('toggle-complete');
        Route::post('/{todo}/status', [DepartmentTodoController::class, 'updateStatus'])
            ->name('update-status');
        Route::post('/{todo}/assign', [DepartmentTodoController::class, 'assign'])
            ->name('assign');
        Route::get('/shift/{shift}', [DepartmentTodoController::class, 'forShift'])
            ->name('for-shift');
        Route::post('/bulk', [DepartmentTodoController::class, 'bulkUpdate'])
            ->name('bulk-update');
    });

    // Library routes (for library management)
    Route::resource('libraries', LibraryController::class);

    // Message routes
    Route::resource('messages', MessageController::class);
    Route::patch('messages/{message}/mark-as-read', [MessageController::class, 'markAsRead'])
        ->name('messages.mark-as-read');
    Route::get('messages-unread-count', [MessageController::class, 'unreadCount'])
        ->name('messages.unread-count');
    Route::get('messages-search-recipients', [MessageController::class, 'searchRecipients'])
        ->name('messages.search-recipients');
    Route::get('messages/attachments/{attachment:uuid}/download', [MessageController::class, 'downloadAttachment'])
        ->name('messages.attachments.download');

    // Settings routes
    Route::get('settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])
        ->name('settings.update');

    // Homepage Settings routes (Hero Slides management)
    Route::get('settings/homepage', [SettingsController::class, 'homepage'])
        ->name('settings.homepage')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/slides', [SettingsController::class, 'storeSlide'])
        ->name('settings.homepage.slides.store')
        ->middleware('can:manage hero slides');
    // Reorder route must come BEFORE parameterized routes to avoid conflict
    Route::post('settings/homepage/slides/reorder', [SettingsController::class, 'reorderSlides'])
        ->name('settings.homepage.slides.reorder')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/slides/{heroSlide}', [SettingsController::class, 'updateSlide'])
        ->name('settings.homepage.slides.update')
        ->middleware('can:manage hero slides');
    Route::delete('settings/homepage/slides/{heroSlide}', [SettingsController::class, 'deleteSlide'])
        ->name('settings.homepage.slides.destroy')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/global-stats', [SettingsController::class, 'updateGlobalStats'])
        ->name('settings.homepage.global-stats.update')
        ->middleware('can:manage hero slides');

    // Church management routes
    Route::post('settings/homepage/churches', [SettingsController::class, 'storeChurch'])
        ->name('settings.homepage.churches.store')
        ->middleware('can:manage hero slides');
    Route::post('settings/homepage/churches/{church}', [SettingsController::class, 'updateChurch'])
        ->name('settings.homepage.churches.update')
        ->middleware('can:manage hero slides');
    Route::delete('settings/homepage/churches/{church}', [SettingsController::class, 'destroyChurch'])
        ->name('settings.homepage.churches.destroy')
        ->middleware('can:manage hero slides');

    // Homepage Sections management
    Route::get('settings/homepage/sections', [HomepageSectionController::class, 'index'])
        ->name('settings.homepage.sections.index')
        ->middleware('can:manage homepage sections');
    Route::post('settings/homepage/sections', [HomepageSectionController::class, 'store'])
        ->name('settings.homepage.sections.store')
        ->middleware('can:manage homepage sections');
    // Reorder must come BEFORE parameterized routes to avoid {homepageSection} matching "reorder"
    Route::post('settings/homepage/sections/reorder', [HomepageSectionController::class, 'reorder'])
        ->name('settings.homepage.sections.reorder')
        ->middleware('can:manage homepage sections');
    Route::put('settings/homepage/sections/{homepageSection}', [HomepageSectionController::class, 'update'])
        ->name('settings.homepage.sections.update')
        ->middleware('can:manage homepage sections');
    Route::delete('settings/homepage/sections/{homepageSection}', [HomepageSectionController::class, 'destroy'])
        ->name('settings.homepage.sections.destroy')
        ->middleware('can:manage homepage sections');

    // Nested subsections (reorder before parameterized)
    Route::post('settings/homepage/sections/{homepageSection}/subsections/reorder', [HomepageSectionController::class, 'reorderSubsections'])
        ->name('settings.homepage.sections.subsections.reorder')
        ->middleware('can:manage homepage sections');
    Route::post('settings/homepage/sections/{homepageSection}/subsections', [HomepageSectionController::class, 'storeSubsection'])
        ->name('settings.homepage.sections.subsections.store')
        ->middleware('can:manage homepage sections');
    Route::put('settings/homepage/sections/{homepageSection}/subsections/{homepageSubsection}', [HomepageSectionController::class, 'updateSubsection'])
        ->name('settings.homepage.sections.subsections.update')
        ->middleware('can:manage homepage sections');
    Route::delete('settings/homepage/sections/{homepageSection}/subsections/{homepageSubsection}', [HomepageSectionController::class, 'destroySubsection'])
        ->name('settings.homepage.sections.subsections.destroy')
        ->middleware('can:manage homepage sections');

    // Chat routes
    Route::get('chat', [ChatController::class, 'index'])
        ->name('chat.index');
    Route::post('chat/rooms', [ChatController::class, 'createRoom'])
        ->name('chat.rooms.create');
    Route::get('chat/rooms/{room}/messages', [ChatController::class, 'getMessages'])
        ->name('chat.rooms.messages');
    Route::post('chat/rooms/{room}/messages', [ChatController::class, 'sendMessage'])
        ->middleware('throttle:chat')
        ->name('chat.rooms.send');
    Route::get('chat/unread-count', [ChatController::class, 'getUnreadCount'])
        ->name('chat.unread-count');
    Route::delete('chat/rooms/{room}/leave', [ChatController::class, 'leaveRoom'])
        ->name('chat.rooms.leave');

    // Activity log routes
    Route::get('activity', [ActivityController::class, 'index'])
        ->name('activity.index');

    // Project management routes
    Route::get('projects/all', [ProjectController::class, 'list'])
        ->name('projects.list');
    Route::get('projects/{project}/board', [ProjectController::class, 'board'])
        ->name('projects.board');
    Route::get('projects/{project}/gantt', [ProjectController::class, 'gantt'])
        ->name('projects.gantt');
    Route::resource('projects', ProjectController::class);

    // Kanban, Gantt, Sprints, Epics views
    Route::get('kanban', [KanbanController::class, 'index'])->name('kanban.index');
    Route::patch('kanban/tasks/{task}/status', [KanbanController::class, 'updateStatus'])->name('kanban.tasks.update-status');

    Route::get('gantt', [GanttController::class, 'index'])->name('gantt.index');

    Route::get('sprints', [SprintController::class, 'index'])->name('sprints.index');
    Route::post('sprints', [SprintController::class, 'store'])->name('sprints.store');
    Route::patch('sprints/{sprint}', [SprintController::class, 'update'])->name('sprints.update');
    Route::delete('sprints/{sprint}', [SprintController::class, 'destroy'])->name('sprints.destroy');

    Route::get('epics', [EpicController::class, 'index'])->name('epics.index');
    Route::post('epics', [EpicController::class, 'store'])->name('epics.store');
    Route::patch('epics/{epic}', [EpicController::class, 'update'])->name('epics.update');
    Route::delete('epics/{epic}', [EpicController::class, 'destroy'])->name('epics.destroy');

    // API routes for user selection
    Route::get('api/users', [UserController::class, 'index'])
        ->name('api.users.index');

    // Task routes (web view) - now using unified Task model
    Route::get('project-tasks', [TaskController::class, 'index'])
        ->name('project-tasks.index');
    Route::post('project-tasks/bulk-update', [TaskController::class, 'bulkUpdate'])
        ->name('project-tasks.bulk-update');
    Route::get('project-tasks/{task}', [TaskController::class, 'show'])
        ->name('project-tasks.show');

    // Attachment routes
    Route::post('attachments', [AttachmentController::class, 'store'])
        ->name('attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Notification routes
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount'])
        ->name('notifications.unread-count');

    // Training routes (authenticated - Admin)
    Route::get('trainings', [TrainingController::class, 'adminIndex'])
        ->middleware('restrict.member')
        ->name('trainings.index');
    Route::get('trainings/create', [TrainingController::class, 'create'])
        ->middleware('restrict.member')
        ->name('trainings.create');
    Route::post('trainings', [TrainingController::class, 'store'])
        ->middleware(['throttle:uploads', 'restrict.member'])
        ->name('trainings.store');
    Route::get('trainings/{training}/edit', [TrainingController::class, 'edit'])
        ->middleware('restrict.member')
        ->name('trainings.edit');
    Route::put('trainings/{training}', [TrainingController::class, 'update'])
        ->middleware(['throttle:uploads', 'restrict.member'])
        ->name('trainings.update');
    Route::delete('trainings/{training}', [TrainingController::class, 'destroy'])
        ->middleware('restrict.member')
        ->name('trainings.destroy');

    // Training Access Management routes
    Route::get('trainings/{training}/access', [TrainingController::class, 'accessIndex'])
        ->middleware('restrict.member')
        ->name('trainings.access');
    Route::post('trainings/{training}/access/users', [TrainingController::class, 'grantUserAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.grant-users');
    Route::delete('trainings/{training}/access/users', [TrainingController::class, 'revokeUserAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.revoke-users');
    Route::post('trainings/{training}/access/roles', [TrainingController::class, 'grantRoleAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.grant-roles');
    Route::delete('trainings/{training}/access/roles', [TrainingController::class, 'revokeRoleAccess'])
        ->middleware('restrict.member')
        ->name('trainings.access.revoke-roles');

    // Training Share Link routes
    Route::post('trainings/{training}/share-link', [TrainingController::class, 'generateShareLink'])
        ->middleware('restrict.member')
        ->name('trainings.generate-share-link');
    Route::post('trainings/{training}/revoke-link', [TrainingController::class, 'revokeShareLink'])
        ->middleware('restrict.member')
        ->name('trainings.revoke-share-link');

    // Public shared training routes (no auth required)
    Route::get('/t/{token}', [TrainingController::class, 'showShared'])->name('trainings.shared')->withoutMiddleware(['auth', 'verified']);
    Route::post('/t/{token}/enroll', [TrainingController::class, 'enrollViaShareLink'])->name('trainings.shared.enroll')->withoutMiddleware(['auth', 'verified']);

    // Student Training routes (authenticated)
    Route::get('student/dashboard', [TrainingController::class, 'studentDashboard'])
        ->middleware(['restrict.member', 'can:access student dashboard'])
        ->name('student.dashboard');
    Route::post('trainings/{training}/enroll', [TrainingController::class, 'enroll'])
        ->name('trainings.enroll');

    Route::get('trainings/{training}', [TrainingController::class, 'show'])
        ->name('trainings.show');

    // Teacher Dashboard routes
    Route::get('teacher/dashboard', [TrainingController::class, 'teacherDashboard'])
        ->middleware(['restrict.member', 'can:access teacher dashboard'])
        ->name('teacher.dashboard');
    Route::get('teacher/formations', [TrainingController::class, 'teacherFormations'])
        ->middleware('restrict.member')
        ->name('teacher.formations');
    Route::get('teacher/students', [TrainingController::class, 'teacherStudents'])
        ->middleware('restrict.member')
        ->name('teacher.students');
    Route::get('teacher/attendance', [TrainingController::class, 'teacherAttendance'])
        ->middleware('restrict.member')
        ->name('teacher.attendance');
    Route::post('teacher/attendance/{class}/mark', [TrainingController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('teacher.attendance.mark');
    Route::get('teacher/evaluations', [TrainingController::class, 'teacherEvaluations'])
        ->middleware('restrict.member')
        ->name('teacher.evaluations');
    Route::post('teacher/evaluations/{student}/grade', [TrainingController::class, 'gradeStudent'])
        ->middleware('restrict.member')
        ->name('teacher.evaluations.grade');

    // Teacher and Student CRUD routes
    Route::resource('teachers', TeacherController::class)
        ->middleware('restrict.member');
    Route::resource('students', StudentController::class)
        ->middleware('restrict.member');

    // TrainingClass Management routes
    Route::get('training-classes', [TrainingClassController::class, 'index'])
        ->middleware('restrict.member')
        ->name('training-classes.index');
    Route::get('training-classes/schedules', [TrainingClassController::class, 'schedules'])
        ->middleware('restrict.member')
        ->name('training-classes.schedules');
    Route::get('training-classes/statistics', [TrainingClassController::class, 'statistics'])
        ->middleware('restrict.member')
        ->name('training-classes.statistics');
    Route::get('training-classes/{trainingClass}', [TrainingClassController::class, 'show'])
        ->middleware('restrict.member')
        ->name('training-classes.show');
    Route::get('training-classes/{trainingClass}/schedules', [TrainingClassController::class, 'getClassSchedules'])
        ->middleware('restrict.member')
        ->name('training-classes.class-schedules');
    Route::post('training-classes', [TrainingClassController::class, 'store'])
        ->middleware('restrict.member')
        ->name('training-classes.store');
    Route::put('training-classes/{trainingClass}', [TrainingClassController::class, 'update'])
        ->middleware('restrict.member')
        ->name('training-classes.update');
    Route::delete('training-classes/{trainingClass}', [TrainingClassController::class, 'destroy'])
        ->middleware('restrict.member')
        ->name('training-classes.destroy');
    Route::post('training-classes/{trainingClass}/archive', [TrainingClassController::class, 'archive'])
        ->middleware('restrict.member')
        ->name('training-classes.archive');
    Route::post('training-classes/{trainingClass}/unarchive', [TrainingClassController::class, 'unarchive'])
        ->middleware('restrict.member')
        ->name('training-classes.unarchive');
    Route::post('training-classes/{trainingClass}/duplicate', [TrainingClassController::class, 'duplicate'])
        ->middleware('restrict.member')
        ->name('training-classes.duplicate');
    Route::get('training-classes/{trainingClass}/students', [TrainingClassController::class, 'students'])
        ->middleware('restrict.member')
        ->name('training-classes.students');
    Route::post('training-classes/{trainingClass}/attendance', [TrainingClassController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('training-classes.attendance');
    Route::get('training-classes/training/{training}/students', [TrainingClassController::class, 'trainingStudents'])
        ->middleware('restrict.member')
        ->name('training-classes.training-students');
    Route::get('training-classes/student/{student}/training/{training}/history', [TrainingClassController::class, 'studentAttendanceHistory'])
        ->middleware('restrict.member')
        ->name('training-classes.student-attendance-history');
    Route::get('training-classes/{trainingClass}/week-schedule', [TrainingClassController::class, 'weekSchedule'])
        ->middleware('restrict.member')
        ->name('training-classes.week-schedule');

    // TrainingClassSchedule routes
    Route::post('training-class-schedules', [TrainingClassController::class, 'storeSchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.store');
    Route::put('training-class-schedules/{schedule}', [TrainingClassController::class, 'updateSchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.update');
    Route::delete('training-class-schedules/{schedule}', [TrainingClassController::class, 'destroySchedule'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.destroy');
    Route::get('training-class-schedules/{trainingClassSchedule}/attendance', [TrainingClassScheduleController::class, 'attendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.attendance');
    Route::post('training-class-schedules/{trainingClassSchedule}/mark-attendance', [TrainingClassScheduleController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.mark-attendance');

    // Training Class Materials routes (for teachers)
    Route::prefix('training-classes/{trainingClass}/materials')->middleware('restrict.member')->group(function (): void {
        Route::get('/', [TrainingClassMaterialController::class, 'index'])
            ->name('training-classes.materials.index');
        Route::post('/', [TrainingClassMaterialController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('training-classes.materials.store');
        Route::get('/{material}', [TrainingClassMaterialController::class, 'show'])
            ->name('training-classes.materials.show');
        Route::put('/{material}', [TrainingClassMaterialController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('training-classes.materials.update');
        Route::delete('/{material}', [TrainingClassMaterialController::class, 'destroy'])
            ->name('training-classes.materials.destroy');
        Route::post('/reorder', [TrainingClassMaterialController::class, 'reorder'])
            ->name('training-classes.materials.reorder');
        Route::post('/attach', [TrainingClassMaterialController::class, 'attach'])
            ->name('training-classes.materials.attach');
        Route::patch('/{material}/toggle-active', [TrainingClassMaterialController::class, 'toggleActive'])
            ->name('training-classes.materials.toggle-active');
    });

    // Student access to class materials
    Route::get('student/training-classes/{trainingClass}/materials', [TrainingClassMaterialController::class, 'studentIndex'])
        ->name('student.training-classes.materials.index');

    // Download/stream material (accessible to both teachers and students)
    Route::get('training-class-materials/{material}/download', [TrainingClassMaterialController::class, 'download'])
        ->name('training-class-materials.download');

    // Quiz Management routes (for teachers/admins)
    Route::prefix('trainings/{training}/quizzes')->group(function (): void {
        Route::get('/', [QuizController::class, 'index'])
            ->name('trainings.quizzes.index');
        Route::get('/create', [QuizController::class, 'create'])
            ->name('trainings.quizzes.create');
        Route::post('/', [QuizController::class, 'store'])
            ->name('trainings.quizzes.store');
        Route::get('/{quiz}/edit', [QuizController::class, 'edit'])
            ->name('trainings.quizzes.edit');
        Route::put('/{quiz}', [QuizController::class, 'update'])
            ->name('trainings.quizzes.update');
        Route::patch('/{quiz}/toggle-status', [QuizController::class, 'toggleStatus'])
            ->name('trainings.quizzes.toggle-status');
        Route::delete('/{quiz}', [QuizController::class, 'destroy'])
            ->name('trainings.quizzes.destroy');
        Route::get('/{quiz}/results', [QuizController::class, 'results'])
            ->name('trainings.quizzes.results');
        Route::get('/{quiz}/export-csv', [QuizController::class, 'exportCSV'])
            ->name('trainings.quizzes.export-csv');

        // Quiz Class Assignment routes
        Route::get('/{quiz}/class-assignments', [QuizClassAssignmentController::class, 'show'])
            ->name('trainings.quizzes.class-assignments');
        Route::post('/{quiz}/assign-to-class/{trainingClass}', [QuizClassAssignmentController::class, 'assignToClass'])
            ->name('trainings.quizzes.assign-to-class');
        Route::delete('/{quiz}/remove-from-class/{trainingClass}', [QuizClassAssignmentController::class, 'removeFromClass'])
            ->name('trainings.quizzes.remove-from-class');
        Route::put('/{quiz}/update-class-assignment/{trainingClass}', [QuizClassAssignmentController::class, 'updateClassAssignment'])
            ->name('trainings.quizzes.update-class-assignment');
        Route::post('/{quiz}/assign-to-material/{material}', [QuizClassAssignmentController::class, 'assignToMaterial'])
            ->name('trainings.quizzes.assign-to-material');
        Route::delete('/{quiz}/remove-from-material/{material}', [QuizClassAssignmentController::class, 'removeFromMaterial'])
            ->name('trainings.quizzes.remove-from-material');
        Route::post('/{quiz}/bulk-assign-classes', [QuizClassAssignmentController::class, 'bulkAssignToClasses'])
            ->name('trainings.quizzes.bulk-assign-classes');
        Route::get('/{quiz}/stats', [QuizClassAssignmentController::class, 'getQuizStats'])
            ->name('trainings.quizzes.stats');
    });

    // Quiz Taking routes (for students)
    Route::get('quizzes/{quiz}/start', [QuizController::class, 'start'])
        ->name('quizzes.start');
    Route::post('quiz-attempts/{attempt}/submit', [QuizController::class, 'submit'])
        ->name('quiz-attempts.submit');
    Route::get('quiz-attempts/{attempt}', [QuizController::class, 'showAttempt'])
        ->name('quiz-attempts.show');

    // Quiz Teacher Dashboard
    Route::get('quizzes/teacher/dashboard', [QuizController::class, 'teacherDashboard'])
        ->name('quizzes.teacher-dashboard');

    // Training Enrollment Management routes
    Route::get('training-enrollments', [TrainingEnrollmentController::class, 'index'])
        ->name('training-enrollments.index');
    Route::post('training-enrollments/{id}/approve', [TrainingEnrollmentController::class, 'approve'])
        ->name('training-enrollments.approve');
    Route::post('training-enrollments/{id}/reject', [TrainingEnrollmentController::class, 'reject'])
        ->name('training-enrollments.reject');

    // User Management routes (SuperAdmin only)
    Route::get('user-management', [UserManagementController::class, 'index'])
        ->name('user-management.index');
    // Specific routes must come before the {user} parameter route
    Route::get('user-management/users', [UserManagementController::class, 'getUsers'])
        ->name('user-management.users');
    Route::get('user-management/blocked-login-attempts', [UserManagementController::class, 'blockedLoginAttempts'])
        ->name('user-management.blocked-login-attempts');
    Route::post('user-management/blocked-login-attempts/{attempt}/acknowledge', [UserManagementController::class, 'acknowledgeBlockedAttempt'])
        ->name('user-management.acknowledge-blocked-attempt');
    Route::post('user-management/blocked-login-attempts/acknowledge-multiple', [UserManagementController::class, 'acknowledgeMultipleBlockedAttempts'])
        ->name('user-management.acknowledge-multiple-blocked-attempts');
    // User show route after specific routes
    Route::get('user-management/{user}', [UserManagementController::class, 'show'])
        ->name('user-management.show');
    Route::get('user-management/users/{user}/blocked-attempts', [UserManagementController::class, 'userBlockedAttempts'])
        ->name('user-management.user-blocked-attempts');
    Route::post('user-management/users/{user}/roles', [UserManagementController::class, 'assignRoles'])
        ->name('user-management.assign-roles');
    Route::post('user-management/users/{user}/permissions', [UserManagementController::class, 'assignPermissions'])
        ->name('user-management.assign-permissions');
    Route::post('user-management/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
        ->name('user-management.toggle-status');
    Route::delete('user-management/users/{user}', [UserManagementController::class, 'deleteUser'])
        ->name('user-management.delete-user');
    Route::post('user-management/roles', [UserManagementController::class, 'createRole'])
        ->name('user-management.create-role');
    Route::put('user-management/roles/{role}', [UserManagementController::class, 'updateRole'])
        ->name('user-management.update-role');
    Route::delete('user-management/roles/{role}', [UserManagementController::class, 'deleteRole'])
        ->name('user-management.delete-role');
    Route::post('user-management/permissions', [UserManagementController::class, 'createPermission'])
        ->name('user-management.create-permission');
    Route::delete('user-management/permissions/{permission}', [UserManagementController::class, 'deletePermission'])
        ->name('user-management.delete-permission');

    // Teacher management routes
    Route::post('user-management/users/{user}/add-teacher', [UserManagementController::class, 'addTeacher'])
        ->name('user-management.add-teacher');
    Route::delete('user-management/teachers/{teacher}', [UserManagementController::class, 'removeTeacher'])
        ->name('user-management.remove-teacher');
    Route::put('user-management/teachers/{teacher}', [UserManagementController::class, 'updateTeacher'])
        ->name('user-management.update-teacher');

    // Volunteer (volunteer) management routes
    Route::post('user-management/users/{user}/add-volunteer', [UserManagementController::class, 'addVolunteer'])
        ->name('user-management.add-volunteer');
    Route::delete('user-management/volunteers/{volunteer}', [UserManagementController::class, 'removeVolunteer'])
        ->name('user-management.remove-volunteer');

    // Employee management routes
    Route::post('user-management/users/{user}/add-employee', [UserManagementController::class, 'addEmployee'])
        ->name('user-management.add-employee');
    Route::delete('user-management/employees/{employee}', [UserManagementController::class, 'removeEmployee'])
        ->name('user-management.remove-employee');

    // Appointment routes - specific routes must come before resource routes
    Route::get('appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::get('appointments/create', [AppointmentController::class, 'create'])
        ->name('appointments.create');
    Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])
        ->name('appointments.calendar');
    Route::post('appointments', [AppointmentController::class, 'store'])
        ->name('appointments.store');

    // Resource routes with UUID parameter binding
    Route::resource('appointments', AppointmentController::class)
        ->except(['index', 'create', 'store'])
        ->parameters(['appointments' => 'appointment:uuid']);

    // Appointment actions
    Route::patch('appointments/{appointment:uuid}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
    Route::patch('appointments/{appointment:uuid}/confirm', [AppointmentController::class, 'confirm'])
        ->name('appointments.confirm');

    // Invitation management
    Route::patch('appointments/{appointment:uuid}/accept-invitation', [AppointmentController::class, 'acceptInvitation'])
        ->name('appointments.accept-invitation');
    Route::patch('appointments/{appointment:uuid}/decline-invitation', [AppointmentController::class, 'declineInvitation'])
        ->name('appointments.decline-invitation');

    // iCalendar export/import
    Route::get('appointments/{appointment:uuid}/export-ics', [AppointmentController::class, 'exportIcs'])
        ->name('appointments.export-ics');
    Route::get('appointments-export-ics', [AppointmentController::class, 'exportBulkIcs'])
        ->name('appointments.export-bulk-ics');
    Route::post('appointments/import-ics', [AppointmentController::class, 'importIcs'])
        ->name('appointments.import-ics');

    // API endpoints for appointments
    Route::get('api/appointments/available-slots', [AppointmentController::class, 'availableSlots'])
        ->name('api.appointments.available-slots');

    // Public agenda routes
    Route::get('users/{user:uuid}/agenda', [PublicAgendaController::class, 'show'])
        ->name('users.agenda.show');
    Route::get('api/users/{user:uuid}/available-slots', [PublicAgendaController::class, 'availableSlots'])
        ->name('api.users.available-slots');
    Route::get('api/users/{user:uuid}/schedule', [PublicAgendaController::class, 'schedule'])
        ->name('api.users.schedule');

    // Care Service routes (authenticated pastors)
    Route::prefix('care-service')->name('care-service.')->group(function (): void {
        Route::resource('appointments', CareServiceController::class)
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ])
            ->parameters(['appointments' => 'careService:uuid']);

        // Additional care service actions
        Route::post('appointments/{careService:uuid}/confirm', [CareServiceController::class, 'confirm'])
            ->name('confirm');
        Route::post('appointments/{careService:uuid}/cancel', [CareServiceController::class, 'cancel'])
            ->name('cancel');
        Route::post('appointments/{careService:uuid}/complete', [CareServiceController::class, 'complete'])
            ->name('complete');
        Route::post('appointments/{careService:uuid}/no-show', [CareServiceController::class, 'noShow'])
            ->name('no-show');

        // Transfer appointment to another pastor/agent
        Route::post('appointments/{careService:uuid}/transfer', [CareServiceController::class, 'transfer'])
            ->name('transfer');

        // Care Service Dashboard routes
        Route::get('dashboard', [CareServiceController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('dashboard/statistics', [CareServiceController::class, 'dashboardStatistics'])
            ->name('dashboard.statistics');

        // AJAX endpoint for available time slots
        Route::get('available-slots', [CareServiceController::class, 'getAvailableSlots'])
            ->name('available-slots');
    });

    // Care Service Availability Management routes
    Route::prefix('care-service-availability')->name('care-service-availability.')->middleware('can:manage care service availability')->group(function (): void {
        Route::resource('', CareServiceAvailabilityController::class)
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
        Route::post('{availability}/toggle-status', [CareServiceAvailabilityController::class, 'toggleStatus'])
            ->name('toggle-status');

        // AJAX endpoint for time slot preview
        Route::post('preview-slots', [CareServiceAvailabilityController::class, 'previewSlots'])
            ->name('preview-slots');
    });

    // Care Service booking for authenticated users
    Route::get('care-service/book', function () {
        $user = auth()->user();
        $canSelectPastor = $user->can('select pastor for care service');

        // Debug log
        Log::info('Care Service Book - User: '.$user->email.', canSelectPastor: '.($canSelectPastor ? 'true' : 'false'));

        return Inertia::render('CareService/PublicBook', [
            'canSelectPastor' => $canSelectPastor,
        ]);
    })->name('care-service.book');

    // ============================================
    // Workflow Builder Routes
    // ============================================
    Route::prefix('workflows')->name('workflows.')->middleware('can:view workflows')->group(function (): void {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [WorkflowController::class, 'create'])->middleware('can:create workflows')->name('create');
        Route::post('/', [WorkflowController::class, 'store'])->middleware('can:create workflows')->name('store');
        Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('show');
        Route::get('/{workflow}/edit', [WorkflowController::class, 'edit'])->middleware('can:edit workflows')->name('edit');
        Route::put('/{workflow}', [WorkflowController::class, 'update'])->middleware('can:edit workflows')->name('update');
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])->middleware('can:delete workflows')->name('destroy');
        Route::post('/{workflow}/activate', [WorkflowController::class, 'activate'])->middleware('can:manage workflows')->name('activate');
        Route::post('/{workflow}/deprecate', [WorkflowController::class, 'deprecate'])->middleware('can:manage workflows')->name('deprecate');
        Route::post('/{workflow}/duplicate', [WorkflowController::class, 'duplicate'])->middleware('can:create workflows')->name('duplicate');
        Route::post('/{workflow}/steps', [WorkflowController::class, 'saveSteps'])->middleware('can:edit workflows')->name('save-steps');
        Route::post('/{workflow}/start', [WorkflowController::class, 'startInstance'])->middleware('can:execute workflows')->name('start');
        Route::get('/{workflow}/instances', [WorkflowController::class, 'instances'])->name('instances');
    });

    // Workflow Instance Routes
    Route::prefix('workflow-instances')->name('workflow-instances.')->group(function (): void {
        Route::get('/', [WorkflowInstanceController::class, 'index'])->name('index');
        Route::get('/my-approvals', [WorkflowInstanceController::class, 'myApprovals'])->name('my-approvals');
        Route::get('/my-tasks', [WorkflowInstanceController::class, 'myTasks'])->name('my-tasks');
        Route::get('/{workflowInstance}', [WorkflowInstanceController::class, 'show'])->name('show');
        Route::post('/{workflowInstance}/cancel', [WorkflowInstanceController::class, 'cancel'])->name('cancel');
        Route::post('/{workflowInstance}/pause', [WorkflowInstanceController::class, 'pause'])->name('pause');
        Route::post('/{workflowInstance}/resume', [WorkflowInstanceController::class, 'resume'])->name('resume');
        Route::get('/{workflowInstance}/activity-log', [WorkflowInstanceController::class, 'activityLog'])->name('activity-log');
    });

    // Step Instance Routes
    Route::post('/step-instances/{stepInstance}/complete', [WorkflowInstanceController::class, 'completeStep'])
        ->name('step-instances.complete');
    Route::post('/step-instances/{stepInstance}/submit-form', [WorkflowInstanceController::class, 'submitForm'])
        ->name('step-instances.submit-form');

    // Approval Routes
    Route::post('/approvals/{approval}/submit', [WorkflowInstanceController::class, 'submitApproval'])
        ->name('approvals.submit');
    Route::post('/approvals/{approval}/delegate', [WorkflowInstanceController::class, 'delegateApproval'])
        ->name('approvals.delegate');

    // ============================================
    // Form Builder Routes
    // ============================================
    Route::prefix('forms')->name('forms.')->middleware('can:view forms')->group(function (): void {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/create', [FormController::class, 'create'])->middleware('can:create forms')->name('create');
        Route::post('/', [FormController::class, 'store'])->middleware('can:create forms')->name('store');
        Route::post('/import', [FormController::class, 'import'])->middleware('can:create forms')->name('import');
        Route::get('/{form}', [FormController::class, 'show'])->name('show');
        Route::get('/{form}/edit', [FormController::class, 'edit'])->middleware('can:edit forms')->name('edit');
        Route::put('/{form}', [FormController::class, 'update'])->middleware('can:edit forms')->name('update');
        Route::delete('/{form}', [FormController::class, 'destroy'])->middleware('can:delete forms')->name('destroy');
        Route::post('/{form}/publish', [FormController::class, 'publish'])->middleware('can:manage forms')->name('publish');
        Route::post('/{form}/unpublish', [FormController::class, 'unpublish'])->middleware('can:manage forms')->name('unpublish');
        Route::post('/{form}/archive', [FormController::class, 'archive'])->middleware('can:manage forms')->name('archive');
        Route::post('/{form}/duplicate', [FormController::class, 'duplicate'])->middleware('can:create forms')->name('duplicate');
        Route::post('/{form}/fields', [FormController::class, 'saveFields'])->middleware('can:edit forms')->name('save-fields');
        Route::get('/{form}/preview', [FormController::class, 'preview'])->name('preview');
        Route::get('/{form}/render', [FormController::class, 'renderForm'])->name('render');
        Route::post('/{form}/start-submission', [FormController::class, 'startSubmission'])->middleware('can:submit forms')->name('start-submission');
        Route::get('/{form}/export', [FormController::class, 'export'])->middleware('can:manage forms')->name('export');
        Route::get('/{form}/submissions', [FormController::class, 'submissions'])->middleware('can:process form submissions')->name('submissions');
        Route::post('/{form}/share-link', [FormController::class, 'generateShareLink'])->middleware('can:manage forms')->name('generate-share-link');
    });

    // Public shared form routes (no auth required)
    Route::get('/f/{token}', [FormController::class, 'renderSharedForm'])->name('forms.shared')->withoutMiddleware(['auth', 'verified']);
    Route::post('/f/{token}/submit', [FormController::class, 'submitSharedForm'])->name('forms.shared.submit')->withoutMiddleware(['auth', 'verified']);

    // Public shared programme routes (no auth required)
    Route::get('/p/{token}', [EventProgrammeController::class, 'showShared'])->name('events.programme.shared')->withoutMiddleware(['auth', 'verified']);
    Route::get('/p/{token}/download', [EventProgrammeController::class, 'downloadShared'])->name('events.programme.shared.download')->withoutMiddleware(['auth', 'verified']);

    // Form Submission Routes
    Route::prefix('form-submissions')->name('form-submissions.')->group(function (): void {
        Route::get('/', [FormSubmissionController::class, 'index'])->name('index');
        Route::get('/{formSubmission}', [FormSubmissionController::class, 'show'])->name('show');
        Route::get('/{formSubmission}/edit', [FormSubmissionController::class, 'edit'])->name('edit');
        Route::put('/{formSubmission}', [FormSubmissionController::class, 'update'])->name('update');
        Route::post('/{formSubmission}/submit', [FormSubmissionController::class, 'submit'])->name('submit');
        Route::post('/{formSubmission}/next-step', [FormSubmissionController::class, 'nextStep'])->name('next-step');
        Route::post('/{formSubmission}/previous-step', [FormSubmissionController::class, 'previousStep'])->name('previous-step');
        Route::post('/{formSubmission}/validate-step', [FormSubmissionController::class, 'validateStep'])->name('validate-step');
        Route::post('/{formSubmission}/update-status', [FormSubmissionController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{formSubmission}', [FormSubmissionController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Department Need Management Routes
    // ============================================
    Route::prefix('needs')->name('needs.')->middleware('can:view needs')->group(function (): void {
        Route::get('/', [NeedController::class, 'index'])->name('index');
        Route::get('/kanban', [NeedController::class, 'kanban'])->name('kanban');
        Route::get('/my-needs', [NeedController::class, 'myNeeds'])->name('my-needs');
        Route::get('/stats', [NeedController::class, 'stats'])->name('stats');
        Route::get('/create', [NeedController::class, 'create'])->middleware('can:create needs')->name('create');
        Route::post('/', [NeedController::class, 'store'])->middleware('can:create needs')->name('store');
        Route::get('/{need}', [NeedController::class, 'show'])->name('show');
        Route::get('/{need}/edit', [NeedController::class, 'edit'])->middleware('can:edit needs')->name('edit');
        Route::put('/{need}', [NeedController::class, 'update'])->middleware('can:edit needs')->name('update');
        Route::delete('/{need}', [NeedController::class, 'destroy'])->middleware('can:delete needs')->name('destroy');

        // Status transitions
        Route::post('/{need}/submit', [NeedController::class, 'submit'])->name('submit');
        Route::post('/{need}/withdraw', [NeedController::class, 'withdraw'])->name('withdraw');
        Route::post('/{need}/start-review', [NeedController::class, 'startReview'])->middleware('can:manage needs')->name('start-review');
        Route::post('/{need}/approve', [NeedController::class, 'approve'])->middleware('can:approve needs')->name('approve');
        Route::post('/{need}/reject', [NeedController::class, 'reject'])->middleware('can:approve needs')->name('reject');
        Route::post('/{need}/order', [NeedController::class, 'markOrdered'])->middleware('can:manage needs')->name('order');
        Route::post('/{need}/deliver', [NeedController::class, 'markDelivered'])->middleware('can:manage needs')->name('deliver');
        Route::post('/{need}/complete', [NeedController::class, 'complete'])->middleware('can:manage needs')->name('complete');
        Route::post('/{need}/cancel', [NeedController::class, 'cancel'])->middleware('can:manage needs')->name('cancel');
        Route::post('/{need}/assign', [NeedController::class, 'assign'])->middleware('can:manage needs')->name('assign');
        Route::post('/{need}/duplicate', [NeedController::class, 'duplicate'])->middleware('can:create needs')->name('duplicate');
        Route::patch('/{need}/update-status', [NeedController::class, 'updateStatus'])->middleware('can:manage needs')->name('update-status');

        // Attachments
        Route::post('/{need}/attachments', [NeedController::class, 'uploadAttachment'])->name('attachments.upload');

        // Comments
        Route::get('/{need}/comments', [NeedController::class, 'comments'])->name('comments.list');
        Route::post('/{need}/comments', [NeedController::class, 'addComment'])->name('comments.add');

        // History
        Route::get('/{need}/history', [NeedController::class, 'history'])->name('history');
    });

    // Need Attachment Routes
    Route::delete('/need-attachments/{attachment}', [NeedController::class, 'deleteAttachment'])
        ->name('need-attachments.destroy');

    // Need Comment Routes
    Route::put('/need-comments/{comment}', [NeedController::class, 'updateComment'])
        ->name('need-comments.update');
    Route::delete('/need-comments/{comment}', [NeedController::class, 'deleteComment'])
        ->name('need-comments.destroy');

    // Department Reports
    Route::prefix('reports')->name('reports.')->middleware('can:view reports')->group(function (): void {
        Route::get('/', [DepartmentReportController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentReportController::class, 'create'])->middleware('can:generate reports')->name('create');
        Route::post('/', [DepartmentReportController::class, 'store'])->middleware('can:generate reports')->name('store');
        Route::get('/{report}', [DepartmentReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [DepartmentReportController::class, 'edit'])->middleware('can:generate reports')->name('edit');
        Route::put('/{report}', [DepartmentReportController::class, 'update'])->middleware('can:generate reports')->name('update');
        Route::delete('/{report}', [DepartmentReportController::class, 'destroy'])->middleware('can:generate reports')->name('destroy');

        // Section management
        Route::put('/{report}/sections/{section}', [DepartmentReportController::class, 'updateSection'])->middleware('can:generate reports')->name('sections.update');

        // Report actions
        Route::post('/{report}/populate', [DepartmentReportController::class, 'populate'])->middleware('can:generate reports')->name('populate');
        Route::post('/{report}/submit', [DepartmentReportController::class, 'submit'])->middleware('can:generate reports')->name('submit');
        Route::post('/{report}/approve', [DepartmentReportController::class, 'approve'])->middleware('can:generate reports')->name('approve');
        Route::post('/{report}/publish', [DepartmentReportController::class, 'publish'])->middleware('can:generate reports')->name('publish');
        Route::post('/{report}/archive', [DepartmentReportController::class, 'archive'])->middleware('can:generate reports')->name('archive');
        Route::post('/{report}/duplicate', [DepartmentReportController::class, 'duplicate'])->middleware('can:generate reports')->name('duplicate');

        // PDF Export
        Route::get('/{report}/export', [DepartmentReportController::class, 'export'])->name('export');
        Route::post('/{report}/generate-pdf', [DepartmentReportController::class, 'generatePdf'])->middleware('can:generate reports')->name('generate-pdf');
        Route::get('/{report}/download-pdf', [DepartmentReportController::class, 'downloadPdf'])->name('download-pdf');
        Route::get('/{report}/stream-pdf', [DepartmentReportController::class, 'streamPdf'])->name('stream-pdf');
        Route::post('/{report}/regenerate-pdf', [DepartmentReportController::class, 'regeneratePdf'])->middleware('can:generate reports')->name('regenerate-pdf');
        Route::get('/{report}/preview', [DepartmentReportController::class, 'preview'])->name('preview');

        // Comments
        Route::post('/{report}/comments', [DepartmentReportController::class, 'addComment'])->name('comments.add');
        Route::post('/comments/{comment}/resolve', [DepartmentReportController::class, 'resolveComment'])->name('comments.resolve');

        // Attachments
        Route::post('/{report}/attachments', [DepartmentReportController::class, 'addAttachment'])->name('attachments.add');
        Route::delete('/{report}/attachments/{attachment}', [DepartmentReportController::class, 'removeAttachment'])->name('attachments.remove');

        // Versions
        Route::get('/{report}/versions/{version1}/compare/{version2}', [DepartmentReportController::class, 'compareVersions'])->name('versions.compare');
    });

    // Department generated reports API
    Route::get('/departments/{department}/generated-reports', [DepartmentReportController::class, 'listGeneratedReports'])
        ->name('departments.generated-reports');

});

// Public Training routes (outside auth middleware)

// Public appointment confirmation routes (accessible without authentication)
Route::get('appointments/{appointment:uuid}/confirm/{token}', [AppointmentParticipantController::class, 'confirm'])
    ->name('appointments.participant.confirm')
    ->missing(function (Request $request) {
        $appointmentId = $request->route('appointment');
        Illuminate\Support\Facades\Log::warning('Appointment confirmation attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });
Route::get('appointments/{appointment:uuid}/decline/{token}', [AppointmentParticipantController::class, 'decline'])
    ->name('appointments.participant.decline')
    ->missing(function (Request $request) {
        $appointmentId = $request->route('appointment');
        Illuminate\Support\Facades\Log::warning('Appointment decline attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });
Route::post('appointments/{appointment:uuid}/decline/{token}', [AppointmentParticipantController::class, 'decline'])
    ->name('appointments.participant.decline.submit')
    ->missing(function (Request $request) {
        $appointmentId = $request->route('appointment');
        Illuminate\Support\Facades\Log::warning('Appointment decline submission attempted for non-existent appointment', [
            'appointment_uuid' => $appointmentId,
            'token' => $request->route('token'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $appointmentId,
        ]);
    });

// Public Care Service routes (accessible without authentication)
Route::get('care-service/appointments/{uuid}/confirm', function ($uuid) {
    $appointment = CareService::where('uuid', $uuid)->first();

    if (! $appointment) {
        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return Inertia::render('CareService/PublicConfirm', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('care-service.public.confirm');

Route::post('care-service/appointments/{uuid}/confirm', function (Request $request, $uuid) {
    $appointment = CareService::where('uuid', $uuid)->first();

    if (! $appointment) {
        return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable'], 404);
    }

    try {
        $appointment->confirm();

        return redirect()->route('care-service.public.success', ['uuid' => $uuid])
            ->with('success', 'Rendez-vous confirmé avec succès.');
    } catch (Exception $e) {
        return back()->with('error', $e->getMessage());
    }
})->name('care-service.public.confirm.submit');

Route::get('care-service/appointments/{uuid}/cancel', function ($uuid) {
    $appointment = CareService::where('uuid', $uuid)->first();

    if (! $appointment) {
        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return Inertia::render('CareService/PublicCancel', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('care-service.public.cancel');

Route::post('care-service/appointments/{uuid}/cancel', function (Request $request, $uuid) {
    $appointment = CareService::where('uuid', $uuid)->first();

    if (! $appointment) {
        return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable'], 404);
    }

    $validated = $request->validate([
        'cancellation_reason' => 'nullable|string|max:500',
    ]);

    try {
        $appointment->cancel($validated['cancellation_reason'] ?? null);

        return redirect()->route('care-service.public.success', ['uuid' => $uuid])
            ->with('success', 'Rendez-vous annulé avec succès.');
    } catch (Exception $e) {
        return back()->with('error', $e->getMessage());
    }
})->name('care-service.public.cancel.submit');

Route::get('care-service/appointments/{uuid}/success', function ($uuid) {
    $appointment = CareService::where('uuid', $uuid)->first();

    if (! $appointment) {
        return Inertia::render('Appointments/AppointmentNotFound', [
            'appointmentId' => $uuid,
        ]);
    }

    return Inertia::render('CareService/PublicSuccess', [
        'appointment' => $appointment->load('pastor'),
    ]);
})->name('care-service.public.success');

// Public Care Service booking interface (moved to authenticated section)

// Sentry test routes (only available in local/development environments)
Route::middleware(['auth'])->group(function (): void {
    Route::get('sentry/test-error', [SentryTestController::class, 'testError'])
        ->name('sentry.test-error');
    Route::get('sentry/test-message', [SentryTestController::class, 'testMessage'])
        ->name('sentry.test-message');
    Route::get('sentry/test-breadcrumbs', [SentryTestController::class, 'testBreadcrumbs'])
        ->name('sentry.test-breadcrumbs');
});

// Agile module — Epic / UserStory / AcceptanceCriterion / TestScenario / StoryTask
Route::middleware(['auth', 'verified'])->prefix('agile')->name('agile.')->group(function (): void {
    Route::resource('epics', App\Http\Controllers\Agile\EpicController::class)
        ->parameters(['epics' => 'epic']);

    Route::resource('user-stories', UserStoryController::class)
        ->parameters(['user-stories' => 'userStory']);

    Route::get('user-stories/{userStory}/acceptance-criteria', [AcceptanceCriterionController::class, 'index'])
        ->name('user-stories.acceptance-criteria.index');
    Route::post('user-stories/{userStory}/acceptance-criteria', [AcceptanceCriterionController::class, 'store'])
        ->name('user-stories.acceptance-criteria.store');
    Route::resource('acceptance-criteria', AcceptanceCriterionController::class)
        ->only(['show', 'update', 'destroy'])
        ->parameters(['acceptance-criteria' => 'acceptanceCriterion']);

    Route::get('acceptance-criteria/{acceptanceCriterion}/test-scenarios', [TestScenarioController::class, 'index'])
        ->name('acceptance-criteria.test-scenarios.index');
    Route::post('acceptance-criteria/{acceptanceCriterion}/test-scenarios', [TestScenarioController::class, 'store'])
        ->name('acceptance-criteria.test-scenarios.store');
    Route::resource('test-scenarios', TestScenarioController::class)
        ->only(['show', 'update', 'destroy'])
        ->parameters(['test-scenarios' => 'testScenario']);

    Route::get('user-stories/{userStory}/story-tasks', [StoryTaskController::class, 'index'])
        ->name('user-stories.story-tasks.index');
    Route::post('user-stories/{userStory}/story-tasks', [StoryTaskController::class, 'store'])
        ->name('user-stories.story-tasks.store');
    Route::resource('story-tasks', StoryTaskController::class)
        ->only(['show', 'update', 'destroy'])
        ->parameters(['story-tasks' => 'storyTask']);
});

require __DIR__.'/auth.php';
