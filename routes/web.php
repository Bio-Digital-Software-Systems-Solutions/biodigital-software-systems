<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $heroSlides = \App\Models\HeroSlide::active()->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'heroSlides' => $heroSlides,
    ]);
});

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

Route::middleware(['auth', 'verified'])->group(function () {
    // Contact management routes (admin only)
    Route::resource('contacts', App\Http\Controllers\ContactController::class)->except(['create', 'store']);
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Event routes
    Route::resource('events', App\Http\Controllers\EventController::class);
    Route::post('events/{event}/toggle-participation', [App\Http\Controllers\EventController::class, 'toggleParticipation'])
        ->name('events.toggle-participation');

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

    // Article routes (excluding store/update to apply throttle middleware separately)
    Route::resource('articles', App\Http\Controllers\ArticleController::class)->except(['store', 'update']);
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

    // Program routes
    Route::resource('programs', App\Http\Controllers\ProgramController::class);

    // Program Step routes
    Route::post('programs/{program}/steps', [App\Http\Controllers\ProgramStepController::class, 'store'])
        ->name('programs.steps.store');
    Route::patch('programs/{program}/steps/{step}', [App\Http\Controllers\ProgramStepController::class, 'update'])
        ->name('programs.steps.update');
    Route::delete('programs/{program}/steps/{step}', [App\Http\Controllers\ProgramStepController::class, 'destroy'])
        ->name('programs.steps.destroy');
    Route::post('programs/{program}/steps/{step}/participants', [App\Http\Controllers\ProgramStepController::class, 'attachParticipant'])
        ->name('programs.steps.participants.attach');
    Route::delete('programs/{program}/steps/{step}/participants/{user}', [App\Http\Controllers\ProgramStepController::class, 'detachParticipant'])
        ->name('programs.steps.participants.detach');

    // Program Step Task routes
    Route::post('programs/{program}/steps/{step}/tasks', [App\Http\Controllers\ProgramStepTaskController::class, 'store'])
        ->name('programs.steps.tasks.store');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}', [App\Http\Controllers\ProgramStepTaskController::class, 'update'])
        ->name('programs.steps.tasks.update');
    Route::delete('programs/{program}/steps/{step}/tasks/{task}', [App\Http\Controllers\ProgramStepTaskController::class, 'destroy'])
        ->name('programs.steps.tasks.destroy');
    Route::patch('programs/{program}/steps/{step}/tasks/{task}/status', [App\Http\Controllers\ProgramStepTaskController::class, 'updateStatus'])
        ->name('programs.steps.tasks.update-status');

    // Stock routes
    Route::resource('stocks', App\Http\Controllers\StockController::class);

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

    // Department routes
    Route::resource('departments', App\Http\Controllers\DepartmentController::class);
    Route::post('departments/{department}/assign-user', [App\Http\Controllers\DepartmentController::class, 'assignUser'])
        ->name('departments.assign-user');
    Route::delete('departments/{department}/users/{user}', [App\Http\Controllers\DepartmentController::class, 'removeUser'])
        ->name('departments.remove-user');

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

    // Settings routes
    Route::get('settings', [App\Http\Controllers\SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('settings', [App\Http\Controllers\SettingsController::class, 'update'])
        ->name('settings.update');

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

    // Project Task routes (web view)
    Route::get('project-tasks', [App\Http\Controllers\ProjectTaskController::class, 'index'])
        ->name('project-tasks.index');
    Route::post('project-tasks/bulk-update', [App\Http\Controllers\ProjectTaskController::class, 'bulkUpdate'])
        ->name('project-tasks.bulk-update');
    Route::get('project-tasks/{task}', [App\Http\Controllers\ProjectTaskController::class, 'show'])
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

    // Student Training routes (authenticated)
    Route::get('student/dashboard', [App\Http\Controllers\TrainingController::class, 'studentDashboard'])
        ->middleware('restrict.member')
        ->name('student.dashboard');
    Route::post('trainings/{training}/enroll', [App\Http\Controllers\TrainingController::class, 'enroll'])
        ->name('trainings.enroll');

    Route::get('trainings/{training}', [App\Http\Controllers\TrainingController::class, 'show'])
        ->name('trainings.show');

    // Teacher Dashboard routes
    Route::get('teacher/dashboard', [App\Http\Controllers\TrainingController::class, 'teacherDashboard'])
        ->middleware('restrict.member')
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
    Route::get('training-class-schedules/{schedule}/attendance', [App\Http\Controllers\TrainingClassController::class, 'scheduleAttendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.attendance');
    Route::post('training-class-schedules/{schedule}/mark-attendance', [App\Http\Controllers\TrainingClassController::class, 'markScheduleAttendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.mark-attendance');

    // Quiz routes
    Route::get('quizzes/{quiz}/start', [App\Http\Controllers\QuizController::class, 'start'])
        ->name('quizzes.start');
    Route::post('quiz-attempts/{attempt}/submit', [App\Http\Controllers\QuizController::class, 'submit'])
        ->name('quiz-attempts.submit');

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
    Route::get('user-management/{user}', [App\Http\Controllers\UserManagementController::class, 'show'])
        ->name('user-management.show');
    Route::get('user-management/users', [App\Http\Controllers\UserManagementController::class, 'getUsers'])
        ->name('user-management.users');
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

});

// Public Training routes (outside auth middleware)

// Sentry test routes (only available in local/development environments)
Route::middleware(['auth'])->group(function () {
    Route::get('sentry/test-error', [App\Http\Controllers\SentryTestController::class, 'testError'])
        ->name('sentry.test-error');
    Route::get('sentry/test-message', [App\Http\Controllers\SentryTestController::class, 'testMessage'])
        ->name('sentry.test-message');
    Route::get('sentry/test-breadcrumbs', [App\Http\Controllers\SentryTestController::class, 'testBreadcrumbs'])
        ->name('sentry.test-breadcrumbs');
});

require __DIR__.'/auth.php';
