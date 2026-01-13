<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentDocument>
 */
class DepartmentDocumentFactory extends Factory
{
    protected $model = DepartmentDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'png']);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        $fileName = fake()->uuid() . '.' . $extension;

        return [
            'department_id' => Department::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => fake()->words(3, true) . '.' . $extension,
            'file_name' => $fileName,
            'file_path' => 'department_documents/1/' . now()->year . '/' . now()->month . '/' . $fileName,
            'mime_type' => $mimeTypes[$extension],
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'extension' => $extension,
            'year' => now()->year,
            'month' => now()->month,
            'title' => fake()->optional()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->optional()->randomElement(['report', 'meeting', 'policy', 'template', 'other']),
        ];
    }

    /**
     * Set document for specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
            'file_path' => 'department_documents/' . $department->id . '/' . now()->year . '/' . now()->month . '/' . $attributes['file_name'],
        ]);
    }

    /**
     * Set document uploader.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $user->id,
        ]);
    }

    /**
     * Set document year and month.
     */
    public function forPeriod(int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Create a PDF document.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'original_name' => fake()->words(3, true) . '.pdf',
        ]);
    }

    /**
     * Create a Word document.
     */
    public function word(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'original_name' => fake()->words(3, true) . '.docx',
        ]);
    }

    /**
     * Create an Excel document.
     */
    public function excel(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'original_name' => fake()->words(3, true) . '.xlsx',
        ]);
    }

    /**
     * Create an image document.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'original_name' => fake()->words(3, true) . '.jpg',
        ]);
    }
}
