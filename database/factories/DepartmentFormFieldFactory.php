<?php

namespace Database\Factories;

use App\Models\DepartmentFormField;
use App\Models\DepartmentForm;
use App\Enums\Form\FieldType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentFormField>
 */
class DepartmentFormFieldFactory extends Factory
{
    protected $model = DepartmentFormField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);

        return [
            'uuid' => Str::uuid(),
            'form_id' => DepartmentForm::factory(),
            'parent_field_id' => null,
            'name' => $name,
            'label' => ucwords(str_replace('-', ' ', $name)),
            'type' => FieldType::Text,
            'order' => $this->faker->numberBetween(1, 20),
            'step' => 1,
            'placeholder' => $this->faker->sentence(3),
            'helper_text' => null,
            'description' => null,
            'default_value' => null,
            'options' => null,
            'validation' => [],
            'conditional_logic' => null,
            'settings' => [],
            'is_required' => false,
            'is_readonly' => false,
            'is_hidden' => false,
            'width' => 'full',
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
     * Set the field type to text.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Text,
        ]);
    }

    /**
     * Set the field type to email.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Email,
            'name' => 'email',
            'label' => 'Email Address',
        ]);
    }

    /**
     * Set the field type to textarea.
     */
    public function textarea(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Textarea,
        ]);
    }

    /**
     * Set the field type to number.
     */
    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Number,
        ]);
    }

    /**
     * Set the field type to select.
     */
    public function select(array $options = []): static
    {
        $defaultOptions = [
            ['label' => 'Option 1', 'value' => 'option_1'],
            ['label' => 'Option 2', 'value' => 'option_2'],
            ['label' => 'Option 3', 'value' => 'option_3'],
        ];

        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Select,
            'options' => $options ?: $defaultOptions,
        ]);
    }

    /**
     * Set the field type to checkbox.
     */
    public function checkbox(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Checkbox,
        ]);
    }

    /**
     * Set the field type to date.
     */
    public function date(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::Date,
        ]);
    }

    /**
     * Set the field type to file.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FieldType::File,
        ]);
    }

    /**
     * Set a half-width field.
     */
    public function halfWidth(): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => 'half',
        ]);
    }
}
