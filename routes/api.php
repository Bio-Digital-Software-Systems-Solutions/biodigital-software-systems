<?php

use App\Http\Controllers\Api\DepartmentDocumentCategoryController;
use App\Http\Controllers\Api\DepartmentDocumentController;
use App\Http\Controllers\Api\DepartmentMeetingController;
use App\Http\Controllers\Api\Event\EventAnalyticsController;
use App\Http\Controllers\Api\Event\EventBadgeController;
use App\Http\Controllers\Api\Event\EventCheckInController;
use App\Http\Controllers\Api\Event\EventRegistrationController;
use App\Http\Controllers\Api\Event\EventTicketController;
use App\Http\Controllers\Api\PastoralCareController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectAppointmentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\TaskAppointmentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\TusUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');

// CSRF token refresh endpoint
Route::get('/csrf-token', fn() => response()->json(['csrf_token' => csrf_token()]))->middleware('auth:sanctum');

// TUS protocol file upload endpoints (authenticated)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::any('/files/{fileId?}', TusUploadController::class)->where('fileId', '.*');
    Route::get('/files/{fileId}/metadata', [TusUploadController::class, 'metadata']);
});

// Public API for trainings list (used by landing page)
Route::get('/trainings', [App\Http\Controllers\TrainingController::class, 'index'])
    ->name('api.trainings.index');

// Public Pastoral Care API endpoints (for public booking interface)
Route::prefix('pastoral-care')->name('api.pastoral-care.')->group(function (): void {
    // Get list of available pastors
    Route::get('/pastors', [PastoralCareController::class, 'getPastors'])
        ->name('pastors');

    // Get list of available themes for booking
    Route::get('/themes', [PastoralCareController::class, 'getThemes'])
        ->name('themes');

    // Get available days for a pastor within a date range
    Route::get('/available-days', [PastoralCareController::class, 'getAvailableDays'])
        ->name('available-days');

    // Get available days from ALL pastors (for users who cannot select a pastor)
    Route::get('/all-available-days', [PastoralCareController::class, 'getAllAvailableDays'])
        ->name('all-available-days');

    // Get available time slots for a pastor on a specific date
    Route::get('/available-slots', [PastoralCareController::class, 'getAvailableSlots'])
        ->name('available-slots');

    // Get available time slots from ALL pastors on a specific date (for users who cannot select a pastor)
    Route::get('/all-available-slots', [PastoralCareController::class, 'getAllAvailableSlots'])
        ->name('all-available-slots');

    // Book a new appointment (public endpoint)
    Route::post('/appointments', [PastoralCareController::class, 'store'])
        ->name('appointments.store');

    // Show appointment details by UUID (for confirmation emails)
    Route::get('/appointments/{uuid}', [PastoralCareController::class, 'show'])
        ->name('appointments.show');

    // Confirm appointment via UUID (public endpoint)
    Route::post('/appointments/{uuid}/confirm', [PastoralCareController::class, 'confirm'])
        ->name('appointments.confirm');

    // Cancel appointment via UUID (public endpoint)
    Route::post('/appointments/{uuid}/cancel', [PastoralCareController::class, 'cancel'])
        ->name('appointments.cancel');

    // Get confirmation status by UUID (public endpoint)
    Route::get('/appointments/{uuid}/confirmation-status', [PastoralCareController::class, 'getConfirmationStatus'])
        ->name('appointments.confirmation-status');

    // Dual confirmation endpoints (token-based, public)
    Route::post('/confirm-by-client', [PastoralCareController::class, 'confirmByClient'])
        ->name('confirm-by-client');

    Route::post('/confirm-by-pastor', [PastoralCareController::class, 'confirmByPastor'])
        ->name('confirm-by-pastor');

    // Proposal system public endpoints
    Route::post('/proposals', [PastoralCareController::class, 'submitProposal'])
        ->name('proposals.submit');

    Route::get('/proposals/show', [PastoralCareController::class, 'showProposal'])
        ->name('proposals.show');

    Route::post('/proposals/accept-counter', [PastoralCareController::class, 'acceptCounterProposal'])
        ->name('proposals.accept-counter');

    Route::post('/proposals/reject-counter', [PastoralCareController::class, 'rejectCounterProposal'])
        ->name('proposals.reject-counter');
});

Route::middleware('auth:sanctum')->group(function (): void {
    // Profile API - Manage user's languages, interests, skills, privacy
    Route::prefix('profile')->name('api.profile.')->group(function (): void {
        // Languages
        Route::get('/languages', [ProfileController::class, 'getLanguages'])->name('languages.index');
        Route::put('/languages', [ProfileController::class, 'updateLanguages'])->name('languages.update');

        // Interests
        Route::get('/interests', [ProfileController::class, 'getInterests'])->name('interests.index');
        Route::put('/interests', [ProfileController::class, 'updateInterests'])->name('interests.update');
        Route::post('/interests', [ProfileController::class, 'createInterest'])->name('interests.store');

        // Skills
        Route::get('/skills', [ProfileController::class, 'getSkills'])->name('skills.index');
        Route::put('/skills', [ProfileController::class, 'updateSkills'])->name('skills.update');
        Route::post('/skills', [ProfileController::class, 'createSkill'])->name('skills.store');

        // Privacy Settings
        Route::get('/privacy', [ProfileController::class, 'getPrivacySettings'])->name('privacy.index');
        Route::put('/privacy', [ProfileController::class, 'updatePrivacySettings'])->name('privacy.update');
    });

    // Projects API
    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'api.projects.index',
        'store' => 'api.projects.store',
        'show' => 'api.projects.show',
        'update' => 'api.projects.update',
        'destroy' => 'api.projects.destroy',
    ]);
    Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks'])->name('api.projects.tasks');
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('api.projects.storeTask');
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('api.projects.updateStatus');

    // Project participants
    Route::post('projects/{project}/participants', [ProjectController::class, 'addParticipant'])->name('api.projects.addParticipant');
    Route::delete('projects/{project}/participants/{participant}', [ProjectController::class, 'removeParticipant'])->name('api.projects.removeParticipant');

    // Project comments
    Route::post('projects/{project}/comments', [ProjectController::class, 'storeComment'])->name('api.projects.storeComment');
    Route::delete('projects/{project}/comments/{comment}', [ProjectController::class, 'deleteComment'])->name('api.projects.deleteComment');
    Route::get('projects/{project}/mentionable-users', [ProjectController::class, 'getMentionableUsers'])->name('api.projects.mentionableUsers');

    // Project attachments
    Route::post('projects/{project}/attachments', [ProjectController::class, 'uploadAttachment'])->name('api.projects.uploadAttachment');
    Route::delete('projects/{project}/attachments/{attachment}', [ProjectController::class, 'deleteAttachment'])->name('api.projects.deleteAttachment');

    // Tasks API (unified Task model)
    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'api.tasks.index',
        'store' => 'api.tasks.store',
        'show' => 'api.tasks.show',
        'update' => 'api.tasks.update',
        'destroy' => 'api.tasks.destroy',
    ]);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('api.tasks.updateStatus');
    Route::patch('tasks/{task}/progress', [TaskController::class, 'updateProgress'])->name('api.tasks.updateProgress');

    // Task comments
    Route::post('tasks/{task}/comments', [TaskController::class, 'storeComment'])->name('api.tasks.storeComment');
    Route::delete('tasks/{task}/comments/{comment}', [TaskController::class, 'deleteComment'])->name('api.tasks.deleteComment');
    Route::get('tasks/{task}/mentionable-users', [TaskController::class, 'getMentionableUsers'])->name('api.tasks.mentionableUsers');

    // Task participants
    Route::post('tasks/{task}/participants', [TaskController::class, 'addParticipant'])->name('api.tasks.addParticipant');
    Route::delete('tasks/{task}/participants/{user}', [TaskController::class, 'removeParticipant'])->name('api.tasks.removeParticipant');

    // Task attachments
    Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('api.tasks.uploadAttachment');
    Route::delete('tasks/{task}/attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('api.tasks.deleteAttachment');

    // Task activities (history)
    Route::get('tasks/{task}/activities', [TaskController::class, 'getActivities'])->name('api.tasks.activities');

    // Sprints API - Burn-down/Burn-up charts
    Route::get('sprints/{sprint}/burndown', [SprintController::class, 'burndownChart'])->name('api.sprints.burndown');

    // Project Appointments API
    Route::prefix('projects/{project:uuid}/appointments')->name('api.projects.appointments.')->group(function (): void {
        Route::get('/', [ProjectAppointmentController::class, 'index'])->name('index');
        Route::get('/month', [ProjectAppointmentController::class, 'month'])->name('month');
        Route::post('/', [ProjectAppointmentController::class, 'store'])->name('store');
        Route::patch('/{appointment}', [ProjectAppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [ProjectAppointmentController::class, 'destroy'])->name('destroy');
    });

    // Task Appointments API
    Route::prefix('tasks/{task:uuid}/appointments')->name('api.tasks.appointments.')->group(function (): void {
        Route::get('/', [TaskAppointmentController::class, 'index'])->name('index');
        Route::get('/month', [TaskAppointmentController::class, 'month'])->name('month');
        Route::post('/', [TaskAppointmentController::class, 'store'])->name('store');
        Route::patch('/{appointment}', [TaskAppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [TaskAppointmentController::class, 'destroy'])->name('destroy');
    });

    // Department Meetings API
    Route::prefix('departments/{department:uuid}/meetings')->name('api.departments.meetings.')->group(function (): void {
        Route::get('/', [DepartmentMeetingController::class, 'index'])->name('index');
        Route::get('/month', [DepartmentMeetingController::class, 'byMonth'])->name('month');
        Route::post('/', [DepartmentMeetingController::class, 'store'])->name('store');
        Route::get('/{meeting:uuid}', [DepartmentMeetingController::class, 'show'])->name('show');
        Route::patch('/{meeting:uuid}', [DepartmentMeetingController::class, 'update'])->name('update');
        Route::delete('/{meeting:uuid}', [DepartmentMeetingController::class, 'destroy'])->name('destroy');
    });

    // Department Documents API
    Route::prefix('departments/{department:uuid}/documents')->name('api.departments.documents.')->group(function (): void {
        Route::get('/', [DepartmentDocumentController::class, 'index'])->name('index');
        Route::get('/search', [DepartmentDocumentController::class, 'search'])->name('search');
        Route::get('/year/{year}', [DepartmentDocumentController::class, 'byYear'])->name('year');
        Route::get('/year/{year}/month/{month}', [DepartmentDocumentController::class, 'byMonth'])->name('month');
        Route::post('/', [DepartmentDocumentController::class, 'store'])->name('store');
        Route::get('/{document:uuid}', [DepartmentDocumentController::class, 'show'])->name('show');
        Route::patch('/{document:uuid}', [DepartmentDocumentController::class, 'update'])->name('update');
        Route::delete('/{document:uuid}', [DepartmentDocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document:uuid}/download', [DepartmentDocumentController::class, 'download'])->name('download');
        Route::get('/{document:uuid}/preview', [DepartmentDocumentController::class, 'preview'])->name('preview');
    });

    // Department Document Categories API (subfolders)
    Route::prefix('departments/{department:uuid}/document-categories')->name('api.departments.document-categories.')->group(function (): void {
        Route::get('/', [DepartmentDocumentCategoryController::class, 'index'])->name('index');
        Route::post('/', [DepartmentDocumentCategoryController::class, 'store'])->name('store');
        Route::patch('/{category:uuid}', [DepartmentDocumentCategoryController::class, 'update'])->name('update');
        Route::delete('/{category:uuid}', [DepartmentDocumentCategoryController::class, 'destroy'])->name('destroy');
    });

    // Authenticated Pastoral Care API endpoints (for pastors)
    Route::prefix('pastoral-care')->name('api.pastoral-care.')->group(function (): void {
        // List appointments for authenticated pastor
        Route::get('/appointments', [PastoralCareController::class, 'index'])
            ->name('appointments.index');

        // Update appointment (authenticated pastor only)
        Route::patch('/appointments/{uuid}', [PastoralCareController::class, 'update'])
            ->name('appointments.update');

        // Delete appointment (authenticated pastor only)
        Route::delete('/appointments/{uuid}', [PastoralCareController::class, 'destroy'])
            ->name('appointments.destroy');

        // Mark appointment as completed (authenticated pastor only)
        Route::post('/appointments/{uuid}/complete', [PastoralCareController::class, 'complete'])
            ->name('appointments.complete');

        // Mark appointment as no-show (authenticated pastor only)
        Route::post('/appointments/{uuid}/no-show', [PastoralCareController::class, 'noShow'])
            ->name('appointments.no-show');

        // Note: confirm and cancel routes are defined in the PUBLIC section above (lines 70-76)
        // to allow unauthenticated access from confirmation emails

        // Create follow-up appointment (authenticated pastor only)
        Route::post('/appointments/{uuid}/follow-up', [PastoralCareController::class, 'createFollowUp'])
            ->name('appointments.follow-up');

        // Generate report for appointment (authenticated pastor only)
        Route::get('/appointments/{uuid}/report', [PastoralCareController::class, 'generateReport'])
            ->name('appointments.report');

        // Proposal management endpoints (MLR/Admin only)
        Route::get('/proposals', [PastoralCareController::class, 'getPendingProposals'])
            ->name('proposals.index');

        Route::post('/proposals/{uuid}/accept', [PastoralCareController::class, 'acceptProposal'])
            ->name('proposals.accept');

        Route::post('/proposals/{uuid}/reject', [PastoralCareController::class, 'rejectProposal'])
            ->name('proposals.reject');
    });

    // ========================================
    // Event Management API
    // ========================================

    // Event Tickets
    Route::prefix('events/{event}')->name('api.events.')->group(function (): void {
        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function (): void {
            Route::get('/', [EventTicketController::class, 'index'])->name('index');
            Route::get('/available', [EventTicketController::class, 'available'])->name('available');
            Route::post('/', [EventTicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [EventTicketController::class, 'show'])->name('show');
            Route::patch('/{ticket}', [EventTicketController::class, 'update'])->name('update');
            Route::delete('/{ticket}', [EventTicketController::class, 'destroy'])->name('destroy');
            Route::post('/{ticket}/availability', [EventTicketController::class, 'checkAvailability'])->name('availability');
            Route::post('/{ticket}/price', [EventTicketController::class, 'calculatePrice'])->name('price');
        });
        Route::post('/promo-code/validate', [EventTicketController::class, 'validatePromoCode'])->name('promo-code.validate');
        Route::post('/tickets/duplicate', [EventTicketController::class, 'duplicate'])->name('tickets.duplicate');
        Route::post('/tickets/reorder', [EventTicketController::class, 'reorder'])->name('tickets.reorder');

        // Registrations
        Route::prefix('registrations')->name('registrations.')->group(function (): void {
            Route::get('/', [EventRegistrationController::class, 'index'])->name('index');
            Route::post('/', [EventRegistrationController::class, 'register'])->name('store');
            Route::get('/stats', [EventRegistrationController::class, 'stats'])->name('stats');
            Route::get('/export', [EventRegistrationController::class, 'export'])->name('export');
            Route::post('/bulk-confirm', [EventRegistrationController::class, 'bulkConfirm'])->name('bulk-confirm');
            Route::post('/bulk-cancel', [EventRegistrationController::class, 'bulkCancel'])->name('bulk-cancel');
            Route::get('/{registration}', [EventRegistrationController::class, 'show'])->name('show');
            Route::post('/{registration}/confirm', [EventRegistrationController::class, 'confirm'])->name('confirm');
            Route::post('/{registration}/cancel', [EventRegistrationController::class, 'cancel'])->name('cancel');
            Route::post('/{registration}/waitlist', [EventRegistrationController::class, 'moveToWaitlist'])->name('waitlist');
            Route::post('/{registration}/promote', [EventRegistrationController::class, 'promoteFromWaitlist'])->name('promote');
            Route::post('/{registration}/transfer', [EventRegistrationController::class, 'transfer'])->name('transfer');
            Route::post('/{registration}/payment', [EventRegistrationController::class, 'recordPayment'])->name('payment');
        });

        // Check-in
        Route::prefix('checkin')->name('checkin.')->group(function (): void {
            Route::get('/', [EventCheckInController::class, 'index'])->name('index');
            Route::get('/stats', [EventCheckInController::class, 'stats'])->name('stats');
            Route::get('/recent', [EventCheckInController::class, 'recent'])->name('recent');
            Route::get('/live', [EventCheckInController::class, 'liveFeed'])->name('live');
            Route::get('/search', [EventCheckInController::class, 'search'])->name('search');
            Route::post('/qr', [EventCheckInController::class, 'checkInByQR'])->name('qr');
            Route::post('/number', [EventCheckInController::class, 'checkInByNumber'])->name('number');
            Route::post('/{registration}', [EventCheckInController::class, 'checkInManual'])->name('manual');
            Route::post('/{registration}/checkout', [EventCheckInController::class, 'checkOut'])->name('checkout');
            Route::delete('/{checkin}', [EventCheckInController::class, 'undoCheckIn'])->name('undo');
            Route::get('/{registration}/history', [EventCheckInController::class, 'history'])->name('history');
            Route::get('/session/{session}', [EventCheckInController::class, 'sessionAttendance'])->name('session');
            Route::post('/no-shows', [EventCheckInController::class, 'markNoShows'])->name('no-shows');
        });

        // Badges
        Route::prefix('badges')->name('badges.')->group(function (): void {
            Route::get('/', [EventBadgeController::class, 'index'])->name('index');
            Route::get('/stats', [EventBadgeController::class, 'stats'])->name('stats');
            Route::get('/templates', [EventBadgeController::class, 'templates'])->name('templates');
            Route::get('/pending-print', [EventBadgeController::class, 'pendingPrint'])->name('pending-print');
            Route::get('/pending-collection', [EventBadgeController::class, 'pendingCollection'])->name('pending-collection');
            Route::get('/search', [EventBadgeController::class, 'search'])->name('search');
            Route::post('/find-qr', [EventBadgeController::class, 'findByQR'])->name('find-qr');
            Route::post('/generate-bulk', [EventBadgeController::class, 'generateBulk'])->name('generate-bulk');
            Route::post('/mark-printed-bulk', [EventBadgeController::class, 'markBulkPrinted'])->name('mark-printed-bulk');
            Route::post('/print-data-bulk', [EventBadgeController::class, 'bulkPrintData'])->name('print-data-bulk');
            Route::post('/{registration}/generate', [EventBadgeController::class, 'generate'])->name('generate');
            Route::get('/{badge}', [EventBadgeController::class, 'show'])->name('show');
            Route::patch('/{badge}', [EventBadgeController::class, 'update'])->name('update');
            Route::post('/{badge}/printed', [EventBadgeController::class, 'markPrinted'])->name('printed');
            Route::post('/{badge}/collected', [EventBadgeController::class, 'markCollected'])->name('collected');
            Route::post('/{badge}/lost', [EventBadgeController::class, 'reportLost'])->name('lost');
            Route::get('/{badge}/print-data', [EventBadgeController::class, 'printData'])->name('print-data');
        });

        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function (): void {
            Route::get('/dashboard', [EventAnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/overview', [EventAnalyticsController::class, 'overview'])->name('overview');
            Route::get('/registrations', [EventAnalyticsController::class, 'registrations'])->name('registrations');
            Route::get('/revenue', [EventAnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/feedback', [EventAnalyticsController::class, 'feedback'])->name('feedback');
            Route::get('/trends', [EventAnalyticsController::class, 'trends'])->name('trends');
            Route::get('/sessions', [EventAnalyticsController::class, 'sessions'])->name('sessions');
            Route::get('/sponsors', [EventAnalyticsController::class, 'sponsors'])->name('sponsors');
            Route::get('/export', [EventAnalyticsController::class, 'export'])->name('export');
            Route::get('/realtime', [EventAnalyticsController::class, 'realtime'])->name('realtime');
            Route::post('/clear-cache', [EventAnalyticsController::class, 'clearCache'])->name('clear-cache');
        });
    });

    // User's registrations
    Route::get('/my-registrations', [EventRegistrationController::class, 'myRegistrations'])
        ->name('api.my-registrations');

    // Compare events
    Route::post('/events/compare', [EventAnalyticsController::class, 'compare'])
        ->name('api.events.compare');
});
