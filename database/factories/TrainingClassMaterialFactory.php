<?php

namespace Database\Factories;

use App\Models\TrainingClass;
use App\Models\TrainingMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingClassMaterial>
 */
class TrainingClassMaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_class_id' => TrainingClass::factory(),
            'training_material_id' => TrainingMaterial::factory(),
            'teacher_id' => null,
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forClass(TrainingClass $class): self
    {
        return $this->state(fn () => ['training_class_id' => $class->id]);
    }

    public function forMaterial(TrainingMaterial $material): self
    {
        return $this->state(fn () => ['training_material_id' => $material->id]);
    }

    public function assignedBy(User $user): self
    {
        return $this->state(fn () => ['teacher_id' => $user->id]);
    }
}
