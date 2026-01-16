<?php

namespace Database\Factories;

use App\Enums\Form\FormFieldType;
use App\Models\DepartmentForm;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'form_id' => DepartmentForm::factory(),
            'parent_field_id' => null,
            'name' => $this->faker->unique()->slug(2),
            'label' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'type' => FormFieldType::TEXT,
            'order' => 0,
            'step' => 1,
            'placeholder' => $this->faker->optional()->sentence(3),
            'help_text' => $this->faker->optional()->sentence(),
            'default_value' => null,
            'options' => null,
            'validation' => null,
            'conditional_logic' => null,
            'config' => null,
            'is_required' => false,
            'is_readonly' => false,
            'is_hidden' => false,
            'column_span' => 12,
        ];
    }

    /**
     * Indicate that the field is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Indicate that the field is readonly.
     */
    public function readonly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_readonly' => true,
        ]);
    }

    /**
     * Indicate that the field is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    /**
     * Set the field type.
     */
    public function type(FormFieldType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set options for select/radio/checkbox fields.
     */
    public function withOptions(array $options): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => $options,
        ]);
    }

    /**
     * Set validation rules.
     */
    public function withValidation(array $validation): static
    {
        return $this->state(fn (array $attributes) => [
            'validation' => $validation,
        ]);
    }

    /**
     * Set conditional logic.
     */
    public function withConditionalLogic(array $logic): static
    {
        return $this->state(fn (array $attributes) => [
            'conditional_logic' => $logic,
        ]);
    }
}
