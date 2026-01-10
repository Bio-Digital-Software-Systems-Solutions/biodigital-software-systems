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

// Legal pages (public access)
Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return Inertia::render('Legal/TermsOfService');
})->name('terms-of-service');

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
    Route::post('events/{event}/join', [App\Http\Controllers\EventController::class, 'join'])
        ->name('events.join');
    Route::delete('events/{event}/leave', [App\Http\Controllers\EventController::class, 'leave'])
        ->name('events.leave');

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

    // Task Attachments
    Route::post('tasks/{task}/attachments', [App\Http\Controllers\TaskController::class, 'addAttachment'])
        ->name('tasks.attachments.add');
    Route::delete('tasks/{task}/attachments/{attachment}', [App\Http\Controllers\TaskController::class, 'deleteAttachment'])
        ->name('tasks.attachments.delete');
    Route::get('tasks/{task}/attachments/{attachment}/download', [App\Http\Controllers\TaskController::class, 'downloadAttachment'])
        ->name('tasks.attachments.download');

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
    Route::get('messages/attachments/{attachment:uuid}/download', [App\Http\Controllers\MessageController::class, 'downloadAttachment'])
        ->name('messages.attachments.download');

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
    Route::get('training-class-schedules/{trainingClassSchedule}/attendance', [App\Http\Controllers\TrainingClassScheduleController::class, 'attendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.attendance');
    Route::post('training-class-schedules/{trainingClassSchedule}/mark-attendance', [App\Http\Controllers\TrainingClassScheduleController::class, 'markAttendance'])
        ->middleware('restrict.member')
        ->name('training-class-schedules.mark-attendance');

    // Training Class Materials routes (for teachers)
    Route::prefix('training-classes/{trainingClass}/materials')->middleware('restrict.member')->group(function () {
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
    Route::prefix('trainings/{training}/quizzes')->group(function () {
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
    Route::prefix('pastoral-care')->name('pastoral-care.')->group(function () {
        Route::resource('appointments', App\Http\Controllers\PastoralCareController::class)
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy'
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

        // AJAX endpoint for available time slots
        Route::get('available-slots', [App\Http\Controllers\PastoralCareController::class, 'getAvailableSlots'])
            ->name('available-slots');
    });

    // Pastor Availability Management routes
    Route::prefix('pastoral-availability')->name('pastoral-availability.')->group(function () {
        Route::resource('', App\Http\Controllers\PastorAvailabilityController::class)
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy'
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
        \Log::info('Pastoral Care Book - User: ' . $user->email . ', canSelectPastor: ' . ($canSelectPastor ? 'true' : 'false'));

        return \Inertia\Inertia::render('PastoralCare/PublicBook', [
            'canSelectPastor' => $canSelectPastor,
        ]);
    })->name('pastoral-care.book');


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

    if (!$appointment) {
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

    if (!$appointment) {
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

    if (!$appointment) {
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

    if (!$appointment) {
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

    if (!$appointment) {
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
Route::middleware(['auth'])->group(function () {
    Route::get('sentry/test-error', [App\Http\Controllers\SentryTestController::class, 'testError'])
        ->name('sentry.test-error');
    Route::get('sentry/test-message', [App\Http\Controllers\SentryTestController::class, 'testMessage'])
        ->name('sentry.test-message');
    Route::get('sentry/test-breadcrumbs', [App\Http\Controllers\SentryTestController::class, 'testBreadcrumbs'])
        ->name('sentry.test-breadcrumbs');
});

require __DIR__.'/auth.php';
