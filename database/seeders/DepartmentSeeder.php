<?php

namespace Database\Seeders;

use App\Enums\Form\FormFieldType;
use App\Enums\Form\FormStatus;
use App\Enums\Need\NeedCategory;
use App\Enums\Need\NeedPriority;
use App\Enums\Need\NeedStatus;
use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\ShiftType;
use App\Enums\Scheduling\TodoPriority;
use App\Enums\Workflow\StepType;
use App\Enums\Workflow\TransitionConditionType;
use App\Enums\Workflow\WorkflowScope;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\WorkflowTriggerType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentDocumentCategory;
use App\Models\DepartmentForm;
use App\Models\DepartmentMeeting;
use App\Models\DepartmentNeed;
use App\Models\DepartmentWorkflow;
use App\Models\FormField;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Responsible for managing technology infrastructure, software development, and digital solutions.',
                'budget' => 500000,
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Manages employee relations, recruitment, training, and organizational development.',
                'budget' => 250000,
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Handles financial planning, budgeting, accounting, and financial reporting.',
                'budget' => 300000,
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Develops marketing strategies, brand management, and customer engagement initiatives.',
                'budget' => 400000,
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Oversees daily operations, process improvement, and operational efficiency.',
                'budget' => 350000,
                'is_active' => true,
            ],
            [
                'name' => 'Research & Development',
                'code' => 'RND',
                'description' => 'Focuses on innovation, product development, and research initiatives.',
                'budget' => 600000,
                'is_active' => true,
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CS',
                'description' => 'Provides customer support, handles inquiries, and maintains customer satisfaction.',
                'budget' => 200000,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::firstOrCreate(
                ['code' => $departmentData['code']],
                $departmentData
            );
        }

        // Assign department heads from existing users
        $allDepartments = Department::all();
        $adminUsers = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'project-manager', 'super-admin']);
        })->get();

        if ($adminUsers->count() > 0) {
            foreach ($allDepartments->take($adminUsers->count()) as $index => $department) {
                $department->update(['head_of_department' => $adminUsers[$index]->id]);
            }
        }

        // Get all available users for member assignment
        $allUsers = User::all();
        if ($allUsers->isEmpty()) {
            return;
        }

        foreach ($allDepartments as $department) {
            $this->seedMembers($department, $allUsers);
            $this->seedShifts($department);
            $this->seedTodos($department, $allUsers);
            $this->seedWorkflows($department, $allUsers);
            $this->seedForms($department, $allUsers);
            $this->seedDocuments($department, $allUsers);
            $this->seedAgenda($department, $allUsers);
            $this->seedNeeds($department);
        }
    }

    /**
     * Assign members to a department.
     */
    private function seedMembers(Department $department, $allUsers): void
    {
        $memberCount = random_int(3, min(6, $allUsers->count()));
        $members = $allUsers->random($memberCount);

        // Always include the head if set
        if ($department->head_of_department) {
            $memberIds = $members->pluck('id')->push($department->head_of_department)->unique()->toArray();
        } else {
            $memberIds = $members->pluck('id')->toArray();
        }

        $department->users()->syncWithoutDetaching($memberIds);
    }

    /**
     * Seed shifts for the department.
     */
    private function seedShifts(Department $department): void
    {
        $departmentMembers = $department->users;
        if ($departmentMembers->isEmpty()) {
            return;
        }

        $shiftConfigs = [
            ['title' => 'Schicht Morgen', 'type' => ShiftType::MORNING, 'start' => '06:00', 'end' => '14:00'],
            ['title' => 'Schicht Nachmittag', 'type' => ShiftType::AFTERNOON, 'start' => '14:00', 'end' => '22:00'],
            ['title' => 'Ganztags', 'type' => ShiftType::FULL_DAY, 'start' => '09:00', 'end' => '17:00'],
        ];

        $startDate = Carbon::now()->startOfWeek();
        $creator = $departmentMembers->first();

        $weeklySchedule = WeeklySchedule::firstOrCreate(
            [
                'department_id' => $department->id,
                'week_start' => $startDate->toDateString(),
            ],
            [
                'uuid' => Str::uuid(),
                'week_end' => $startDate->copy()->endOfWeek()->toDateString(),
                'status' => 'published',
                'created_by' => $creator->id,
                'published_by' => $creator->id,
                'published_at' => now(),
            ]
        );

        foreach ($shiftConfigs as $i => $config) {
            $user = $departmentMembers->random();
            $date = $startDate->copy()->addDays($i);

            Shift::create([
                'uuid' => Str::uuid(),
                'weekly_schedule_id' => $weeklySchedule->id,
                'department_id' => $department->id,
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'start_time' => $config['start'],
                'end_time' => $config['end'],
                'break_duration' => 30,
                'type' => $config['type'],
                'status' => ShiftStatus::PUBLISHED,
                'title' => $config['title'].' - '.$department->name,
                'notes' => 'Auto-generated shift for '.$department->code,
            ]);
        }
    }

    /**
     * Seed todos for the department.
     */
    private function seedTodos(Department $department, $allUsers): void
    {
        $departmentMembers = $department->users;
        $creator = $departmentMembers->isNotEmpty()
            ? $departmentMembers->first()
            : $allUsers->first();

        $todos = [
            ['title' => 'Wochenbericht vorbereiten', 'priority' => TodoPriority::HIGH, 'status' => ShiftTaskStatus::TODO, 'due_days' => 2, 'minutes' => 60],
            ['title' => 'Team-Meeting organisieren', 'priority' => TodoPriority::MEDIUM, 'status' => ShiftTaskStatus::IN_PROGRESS, 'due_days' => 5, 'minutes' => 30],
            ['title' => 'Dokumentation aktualisieren', 'priority' => TodoPriority::LOW, 'status' => ShiftTaskStatus::TODO, 'due_days' => 7, 'minutes' => 120],
            ['title' => 'Materialbestellung prüfen', 'priority' => TodoPriority::URGENT, 'status' => ShiftTaskStatus::TODO, 'due_days' => 1, 'minutes' => 45],
            ['title' => 'Quartalsziele überprüfen', 'priority' => TodoPriority::HIGH, 'status' => ShiftTaskStatus::COMPLETED, 'due_days' => -2, 'minutes' => 90],
        ];

        foreach ($todos as $i => $todoData) {
            $assignee = $departmentMembers->isNotEmpty() ? $departmentMembers->random() : null;

            DepartmentTodo::create([
                'uuid' => Str::uuid(),
                'department_id' => $department->id,
                'assigned_to' => $assignee?->id,
                'created_by' => $creator->id,
                'title' => $todoData['title'],
                'description' => 'Aufgabe für Abteilung '.$department->name,
                'status' => $todoData['status'],
                'priority' => $todoData['priority'],
                'due_date' => Carbon::now()->addDays($todoData['due_days'])->toDateString(),
                'sort_order' => $i,
                'estimated_minutes' => $todoData['minutes'],
                'completed_by' => $todoData['status'] === ShiftTaskStatus::COMPLETED ? $assignee?->id : null,
                'completed_at' => $todoData['status'] === ShiftTaskStatus::COMPLETED ? now() : null,
            ]);
        }
    }

    /**
     * Seed workflows for the department.
     */
    private function seedWorkflows(Department $department, $allUsers): void
    {
        $creator = $department->users->first() ?? $allUsers->first();

        // Workflow 1: Leave Request Approval
        $workflow = DepartmentWorkflow::create([
            'uuid' => Str::uuid(),
            'department_id' => $department->id,
            'created_by' => $creator->id,
            'name' => 'Urlaubsantrag Genehmigung',
            'description' => 'Workflow zur Genehmigung von Urlaubsanträgen in der Abteilung '.$department->name,
            'status' => WorkflowStatus::ACTIVE,
            'trigger_type' => WorkflowTriggerType::FORM_SUBMISSION,
            'scope' => WorkflowScope::DEPARTMENT,
            'version' => 1,
            'activated_at' => now(),
        ]);

        $startStep = WorkflowStep::create([
            'uuid' => Str::uuid(),
            'workflow_id' => $workflow->id,
            'name' => 'Antrag eingereicht',
            'type' => StepType::START,
            'order' => 1,
            'is_start' => true,
            'is_end' => false,
            'position_x' => 100,
            'position_y' => 200,
        ]);

        $approvalStep = WorkflowStep::create([
            'uuid' => Str::uuid(),
            'workflow_id' => $workflow->id,
            'name' => 'Vorgesetzte Genehmigung',
            'type' => StepType::APPROVAL,
            'order' => 2,
            'is_start' => false,
            'is_end' => false,
            'position_x' => 300,
            'position_y' => 200,
            'timeout_hours' => 48,
        ]);

        $endStep = WorkflowStep::create([
            'uuid' => Str::uuid(),
            'workflow_id' => $workflow->id,
            'name' => 'Abgeschlossen',
            'type' => StepType::END,
            'order' => 3,
            'is_start' => false,
            'is_end' => true,
            'position_x' => 500,
            'position_y' => 200,
        ]);

        WorkflowTransition::create([
            'uuid' => Str::uuid(),
            'workflow_id' => $workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $approvalStep->id,
            'name' => 'Weiterleiten',
            'condition_type' => TransitionConditionType::ALWAYS,
            'is_default' => true,
        ]);

        WorkflowTransition::create([
            'uuid' => Str::uuid(),
            'workflow_id' => $workflow->id,
            'from_step_id' => $approvalStep->id,
            'to_step_id' => $endStep->id,
            'name' => 'Genehmigt',
            'condition_type' => TransitionConditionType::APPROVAL_RESULT,
            'condition_config' => ['result' => 'approved'],
            'is_default' => true,
        ]);

        // Workflow 2: Purchase Request (draft)
        DepartmentWorkflow::create([
            'uuid' => Str::uuid(),
            'department_id' => $department->id,
            'created_by' => $creator->id,
            'name' => 'Einkaufsanfrage',
            'description' => 'Workflow für Einkaufsanfragen und Budgetgenehmigung',
            'status' => WorkflowStatus::DRAFT,
            'trigger_type' => WorkflowTriggerType::MANUAL,
            'scope' => WorkflowScope::DEPARTMENT,
            'version' => 1,
        ]);
    }

    /**
     * Seed forms for the department.
     */
    private function seedForms(Department $department, $allUsers): void
    {
        $creator = $department->users->first() ?? $allUsers->first();

        // Form 1: Feedback form (published)
        $feedbackForm = DepartmentForm::create([
            'uuid' => Str::uuid(),
            'department_id' => $department->id,
            'created_by' => $creator->id,
            'name' => 'Mitarbeiter-Feedback',
            'description' => 'Formular für allgemeines Feedback an die Abteilung '.$department->name,
            'status' => FormStatus::PUBLISHED,
            'is_multi_step' => false,
            'version' => 1,
            'published_at' => now(),
            'success_message' => 'Vielen Dank für Ihr Feedback!',
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $feedbackForm->id,
            'name' => 'subject',
            'label' => 'Betreff',
            'type' => FormFieldType::TEXT,
            'order' => 1,
            'is_required' => true,
            'placeholder' => 'Geben Sie einen Betreff ein',
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $feedbackForm->id,
            'name' => 'category',
            'label' => 'Kategorie',
            'type' => FormFieldType::SELECT,
            'order' => 2,
            'is_required' => true,
            'options' => [
                ['label' => 'Verbesserung', 'value' => 'improvement'],
                ['label' => 'Problem', 'value' => 'issue'],
                ['label' => 'Lob', 'value' => 'praise'],
                ['label' => 'Sonstiges', 'value' => 'other'],
            ],
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $feedbackForm->id,
            'name' => 'message',
            'label' => 'Nachricht',
            'type' => FormFieldType::TEXTAREA,
            'order' => 3,
            'is_required' => true,
            'placeholder' => 'Beschreiben Sie Ihr Feedback...',
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $feedbackForm->id,
            'name' => 'rating',
            'label' => 'Bewertung',
            'type' => FormFieldType::NUMBER,
            'order' => 4,
            'is_required' => false,
            'config' => ['min' => 1, 'max' => 5],
        ]);

        // Form 2: Leave request (draft)
        $leaveForm = DepartmentForm::create([
            'uuid' => Str::uuid(),
            'department_id' => $department->id,
            'created_by' => $creator->id,
            'name' => 'Urlaubsantrag',
            'description' => 'Formular zur Beantragung von Urlaub',
            'status' => FormStatus::DRAFT,
            'is_multi_step' => false,
            'version' => 1,
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $leaveForm->id,
            'name' => 'start_date',
            'label' => 'Startdatum',
            'type' => FormFieldType::DATE,
            'order' => 1,
            'is_required' => true,
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $leaveForm->id,
            'name' => 'end_date',
            'label' => 'Enddatum',
            'type' => FormFieldType::DATE,
            'order' => 2,
            'is_required' => true,
        ]);

        FormField::create([
            'uuid' => Str::uuid(),
            'form_id' => $leaveForm->id,
            'name' => 'reason',
            'label' => 'Begründung',
            'type' => FormFieldType::TEXTAREA,
            'order' => 3,
            'is_required' => false,
        ]);
    }

    /**
     * Seed documents for the department.
     */
    private function seedDocuments(Department $department, $allUsers): void
    {
        $uploader = $department->users->first() ?? $allUsers->first();
        $now = Carbon::now();

        // Create document categories
        DepartmentDocumentCategory::firstOrCreate(
            [
                'department_id' => $department->id,
                'year' => $now->year,
                'month' => $now->month,
                'slug' => 'berichte',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Berichte',
                'is_system' => false,
                'sort_order' => 0,
                'created_by' => $uploader->id,
            ]
        );

        DepartmentDocumentCategory::firstOrCreate(
            [
                'department_id' => $department->id,
                'year' => $now->year,
                'month' => $now->month,
                'slug' => 'vorlagen',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Vorlagen',
                'is_system' => false,
                'sort_order' => 1,
                'created_by' => $uploader->id,
            ]
        );

        // Seed placeholder documents (no actual files)
        $documents = [
            ['title' => 'Monatsbericht '.$now->format('F Y'), 'original_name' => 'monatsbericht.pdf', 'category' => 'berichte', 'mime' => 'application/pdf', 'ext' => 'pdf'],
            ['title' => 'Teamprotokoll', 'original_name' => 'teamprotokoll.pdf', 'category' => 'berichte', 'mime' => 'application/pdf', 'ext' => 'pdf'],
            ['title' => 'Budgetplan', 'original_name' => 'budgetplan.xlsx', 'category' => 'vorlagen', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ext' => 'xlsx'],
        ];

        foreach ($documents as $doc) {
            DepartmentDocument::create([
                'uuid' => Str::uuid(),
                'department_id' => $department->id,
                'uploaded_by' => $uploader->id,
                'original_name' => $doc['original_name'],
                'file_name' => Str::uuid().'.'.$doc['ext'],
                'file_path' => 'departments/'.$department->id.'/documents/'.Str::uuid().'.'.$doc['ext'],
                'mime_type' => $doc['mime'],
                'file_size' => random_int(10000, 500000),
                'extension' => $doc['ext'],
                'year' => $now->year,
                'month' => $now->month,
                'title' => $doc['title'],
                'description' => 'Dokument für Abteilung '.$department->name,
                'category' => $doc['category'],
            ]);
        }
    }

    /**
     * Seed agenda (meetings/appointments) for the department.
     */
    private function seedAgenda(Department $department, $allUsers): void
    {
        $creator = $department->users->first() ?? $allUsers->first();
        $departmentMembers = $department->users;

        $meetings = [
            [
                'title' => 'Wöchentliches Team-Meeting - '.$department->name,
                'description' => 'Regelmäßiges Team-Meeting zur Besprechung aktueller Themen.',
                'days_ahead' => 3,
                'start_hour' => 10,
                'duration_hours' => 1,
                'is_mandatory' => true,
            ],
            [
                'title' => 'Quartalsplanung - '.$department->name,
                'description' => 'Planung der Ziele und Aufgaben für das nächste Quartal.',
                'days_ahead' => 14,
                'start_hour' => 14,
                'duration_hours' => 2,
                'is_mandatory' => true,
            ],
            [
                'title' => 'Brainstorming Session',
                'description' => 'Offene Brainstorming-Runde für neue Ideen.',
                'days_ahead' => 7,
                'start_hour' => 15,
                'duration_hours' => 1,
                'is_mandatory' => false,
            ],
        ];

        foreach ($meetings as $meetingData) {
            $startDatetime = Carbon::now()
                ->addDays($meetingData['days_ahead'])
                ->setHour($meetingData['start_hour'])
                ->setMinute(0)
                ->setSecond(0);

            $appointment = Appointment::create([
                'uuid' => Str::uuid(),
                'title' => $meetingData['title'],
                'description' => $meetingData['description'],
                'start_datetime' => $startDatetime,
                'end_datetime' => $startDatetime->copy()->addHours($meetingData['duration_hours']),
                'status' => 'confirmed',
                'type' => 'meeting',
                'visibility' => 'private',
                'user_id' => $creator->id,
                'appointmentable_type' => Department::class,
                'appointmentable_id' => $department->id,
            ]);

            DepartmentMeeting::create([
                'uuid' => Str::uuid(),
                'department_id' => $department->id,
                'appointment_id' => $appointment->id,
                'created_by' => $creator->id,
                'notify_all_members' => $meetingData['is_mandatory'],
                'is_mandatory' => $meetingData['is_mandatory'],
                'notes' => $meetingData['description'],
            ]);

            // Add department members as participants
            if ($departmentMembers->isNotEmpty()) {
                $participants = $meetingData['is_mandatory']
                    ? $departmentMembers->pluck('id')->toArray()
                    : $departmentMembers->random(min(3, $departmentMembers->count()))->pluck('id')->toArray();

                $appointment->participants()->syncWithoutDetaching($participants);
            }
        }
    }

    /**
     * Seed needs/requests for the department.
     */
    private function seedNeeds(Department $department): void
    {
        $departmentMembers = $department->users;
        if ($departmentMembers->isEmpty()) {
            return;
        }

        $needs = [
            [
                'title' => 'Neue Laptops für Team',
                'category' => NeedCategory::EQUIPMENT,
                'priority' => NeedPriority::HIGH,
                'status' => NeedStatus::SUBMITTED,
                'cost' => 3500.00,
                'quantity' => 3,
                'unit' => 'Stück',
                'justification' => 'Aktuelle Geräte sind über 4 Jahre alt und entsprechen nicht mehr den Anforderungen.',
            ],
            [
                'title' => 'Büromaterial nachbestellen',
                'category' => NeedCategory::SUPPLIES,
                'priority' => NeedPriority::MEDIUM,
                'status' => NeedStatus::APPROVED,
                'cost' => 250.00,
                'quantity' => 1,
                'unit' => 'Paket',
                'justification' => 'Regelmäßige Nachbestellung von Büromaterial.',
            ],
            [
                'title' => 'Fortbildung Projektmanagement',
                'category' => NeedCategory::TRAINING,
                'priority' => NeedPriority::MEDIUM,
                'status' => NeedStatus::UNDER_REVIEW,
                'cost' => 1200.00,
                'quantity' => 2,
                'unit' => 'Teilnehmer',
                'justification' => 'Weiterbildung für neue Teammitglieder im Bereich Projektmanagement.',
            ],
            [
                'title' => 'Software-Lizenz Erneuerung',
                'category' => NeedCategory::SOFTWARE,
                'priority' => NeedPriority::CRITICAL,
                'status' => NeedStatus::ORDERED,
                'cost' => 800.00,
                'quantity' => 5,
                'unit' => 'Lizenzen',
                'justification' => 'Bestehende Lizenzen laufen nächsten Monat ab.',
            ],
        ];

        foreach ($needs as $needData) {
            $requester = $departmentMembers->random();

            DepartmentNeed::create([
                'uuid' => Str::uuid(),
                'department_id' => $department->id,
                'requester_id' => $requester->id,
                'title' => $needData['title'],
                'description' => $needData['justification'],
                'category' => $needData['category'],
                'priority' => $needData['priority'],
                'status' => $needData['status'],
                'estimated_cost' => $needData['cost'],
                'quantity' => $needData['quantity'],
                'unit' => $needData['unit'],
                'justification' => $needData['justification'],
                'currency' => 'EUR',
                'needed_by' => Carbon::now()->addDays(random_int(7, 30)),
                'submitted_at' => $needData['status'] !== NeedStatus::DRAFT ? now()->subDays(random_int(1, 5)) : null,
                'approved_at' => in_array($needData['status'], [NeedStatus::APPROVED, NeedStatus::ORDERED, NeedStatus::DELIVERED, NeedStatus::COMPLETED])
                    ? now()->subDays(random_int(1, 3))
                    : null,
                'approved_by' => in_array($needData['status'], [NeedStatus::APPROVED, NeedStatus::ORDERED, NeedStatus::DELIVERED, NeedStatus::COMPLETED])
                    ? ($department->head_of_department ?? $departmentMembers->first()->id)
                    : null,
                'ordered_at' => $needData['status'] === NeedStatus::ORDERED ? now()->subDay() : null,
            ]);
        }
    }
}
