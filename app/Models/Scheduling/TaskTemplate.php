<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\ShiftTaskPriority;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property ShiftTaskPriority $priority
 * @property-read Department|null $department
 * @method static Builder<static>|TaskTemplate active()
 * @method static Builder<static>|TaskTemplate byPriority(\App\Enums\Scheduling\ShiftTaskPriority $priority)
 * @method static Builder<static>|TaskTemplate forDepartment(int $departmentId)
 * @method static Builder<static>|TaskTemplate newModelQuery()
 * @method static Builder<static>|TaskTemplate newQuery()
 * @method static Builder<static>|TaskTemplate query()
 * @mixin \Eloquent
 */
class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'department_id',
        'title',
        'description',
        'priority',
        'estimated_duration',
        'checklist_template',
        'required_skills',
        'is_active',
    ];

    protected $casts = [
        'priority' => ShiftTaskPriority::class,
        'estimated_duration' => 'integer',
        'checklist_template' => 'array',
        'required_skills' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority(Builder $query, ShiftTaskPriority $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    // Methods
    /**
     * Create a ShiftTask from this template
     */
    public function createTask(Shift $shift, ?int $order = null): ShiftTask
    {
        return ShiftTask::create([
            'shift_id' => $shift->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => 'todo',
            'estimated_duration' => $this->estimated_duration,
            'order' => $order ?? 0,
            'checklist' => $this->prepareChecklist(),
        ]);
    }

    /**
     * Prepare checklist from template
     */
    protected function prepareChecklist(): ?array
    {
        if (!$this->checklist_template) {
            return null;
        }

        return array_map(fn($item): array => [
            'text' => $item,
            'completed' => false,
            'completed_at' => null,
        ], $this->checklist_template);
    }

    /**
     * Add item to checklist template
     */
    public function addChecklistItem(string $text): bool
    {
        $checklist = $this->checklist_template ?? [];
        $checklist[] = $text;

        $this->update(['checklist_template' => $checklist]);
        return true;
    }

    /**
     * Remove item from checklist template
     */
    public function removeChecklistItem(int $index): bool
    {
        $checklist = $this->checklist_template ?? [];

        if (!isset($checklist[$index])) {
            return false;
        }

        array_splice($checklist, $index, 1);
        $this->update(['checklist_template' => array_values($checklist)]);
        return true;
    }
}
