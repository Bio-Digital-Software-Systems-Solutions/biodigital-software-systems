<?php

namespace App\Models;

use App\Enums\Form\FormFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FormField extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'form_id',
        'parent_field_id',
        'name',
        'label',
        'description',
        'type',
        'order',
        'step',
        'placeholder',
        'help_text',
        'default_value',
        'options',
        'validation',
        'conditional_logic',
        'config',
        'is_required',
        'is_readonly',
        'is_hidden',
        'column_span',
    ];

    protected $casts = [
        'type' => FormFieldType::class,
        'options' => 'array',
        'validation' => 'array',
        'conditional_logic' => 'array',
        'config' => 'array',
        'is_required' => 'boolean',
        'is_readonly' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DepartmentForm::class, 'form_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_field_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_field_id')->orderBy('order');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isContainer(): bool
    {
        return in_array($this->type, [
            FormFieldType::GROUP,
            FormFieldType::REPEATER,
            FormFieldType::SECTION,
            FormFieldType::COLUMNS,
            FormFieldType::TABS,
            FormFieldType::ACCORDION,
        ]);
    }

    public function isInput(): bool
    {
        return $this->type->isInput();
    }

    public function hasOptions(): bool
    {
        return $this->type->hasOptions();
    }

    public function getCategory(): string
    {
        return $this->type->category();
    }

    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Add type-specific default validation
        $defaultRules = $this->type->defaultValidation();
        $rules = array_merge($rules, $defaultRules);

        // Add custom validation rules
        if (!empty($this->validation)) {
            foreach ($this->validation as $rule => $value) {
                if ($value === true) {
                    $rules[] = $rule;
                } elseif (!empty($value)) {
                    $rules[] = "$rule:$value";
                }
            }
        }

        return $rules;
    }

    public function evaluateConditionalLogic(array $formData): bool
    {
        if (empty($this->conditional_logic)) {
            return true; // No conditions, always visible
        }

        $conditions = $this->conditional_logic['conditions'] ?? [];
        $logic = $this->conditional_logic['logic'] ?? 'and'; // 'and' or 'or'
        $action = $this->conditional_logic['action'] ?? 'show'; // 'show' or 'hide'

        if (empty($conditions)) {
            return true;
        }

        $results = [];
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            $fieldValue = $formData[$field] ?? null;
            $results[] = $this->evaluateCondition($fieldValue, $operator, $value);
        }

        $conditionMet = $logic === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);

        return $action === 'show' ? $conditionMet : !$conditionMet;
    }

    protected function evaluateCondition($fieldValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals' => $fieldValue == $expectedValue,
            'not_equals' => $fieldValue != $expectedValue,
            'contains' => str_contains((string) $fieldValue, (string) $expectedValue),
            'not_contains' => !str_contains((string) $fieldValue, (string) $expectedValue),
            'starts_with' => str_starts_with((string) $fieldValue, (string) $expectedValue),
            'ends_with' => str_ends_with((string) $fieldValue, (string) $expectedValue),
            'greater_than' => $fieldValue > $expectedValue,
            'less_than' => $fieldValue < $expectedValue,
            'greater_or_equal' => $fieldValue >= $expectedValue,
            'less_or_equal' => $fieldValue <= $expectedValue,
            'is_empty' => empty($fieldValue),
            'is_not_empty' => !empty($fieldValue),
            'in' => in_array($fieldValue, (array) $expectedValue),
            'not_in' => !in_array($fieldValue, (array) $expectedValue),
            default => true,
        };
    }

    public function getDefaultValue()
    {
        if ($this->default_value === null) {
            return null;
        }

        // Parse default value based on field type
        return match ($this->type) {
            FormFieldType::CHECKBOX, FormFieldType::TOGGLE => (bool) $this->default_value,
            FormFieldType::NUMBER, FormFieldType::SLIDER, FormFieldType::RATING => (float) $this->default_value,
            FormFieldType::CHECKBOX_GROUP, FormFieldType::MULTI_SELECT, FormFieldType::TAGS => json_decode($this->default_value, true) ?? [],
            default => $this->default_value,
        };
    }
}
