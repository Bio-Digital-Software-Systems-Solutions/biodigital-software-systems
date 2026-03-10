<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tables that need UUID added
        $tables = [
            'libraries',
            'categories',
            'statuses',
            'tags',
            'messages',
            'sprints',
            'teachers',
            'students',
            'training_class_schedules',
            'quizzes',
            'quiz_attempts',
            'attachments',
            'hero_slides',
        ];

        // Add nullable UUID columns
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }
        }

        // Generate UUIDs for existing records
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $modelClass = $this->getModelClass($table);
                if (class_exists($modelClass)) {
                    $modelClass::whereNull('uuid')->each(function ($model): void {
                        $model->uuid = (string) Str::uuid();
                        $model->save();
                    });
                }
            }
        }

        // Make UUID unique and not nullable
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->uuid('uuid')->unique()->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'libraries',
            'categories',
            'statuses',
            'tags',
            'messages',
            'sprints',
            'teachers',
            'students',
            'training_class_schedules',
            'quizzes',
            'quiz_attempts',
            'attachments',
            'hero_slides',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->dropColumn('uuid');
                });
            }
        }
    }

    /**
     * Get the model class name from table name.
     */
    private function getModelClass(string $table): string
    {
        $modelMap = [
            'libraries' => \App\Models\Library::class,
            'categories' => \App\Models\Category::class,
            'statuses' => \App\Models\Status::class,
            'tags' => \App\Models\Tag::class,
            'messages' => \App\Models\Message::class,
            'sprints' => \App\Models\Sprint::class,
            'teachers' => \App\Models\Teacher::class,
            'students' => \App\Models\Student::class,
            'training_class_schedules' => \App\Models\TrainingClassSchedule::class,
            'quizzes' => \App\Models\Quiz::class,
            'quiz_attempts' => \App\Models\QuizAttempt::class,
            'attachments' => \App\Models\Attachment::class,
            'hero_slides' => \App\Models\HeroSlide::class,
        ];

        return $modelMap[$table] ?? '';
    }
};
