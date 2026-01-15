<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\SwapRequestStatus;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftSwapRequestFactory extends Factory
{
    protected $model = ShiftSwapRequest::class;

    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'target_user_id' => User::factory(),
            'requested_shift_id' => Shift::factory(),
            'offered_shift_id' => $this->faker->optional(0.5)->passthrough(Shift::factory()),
            'status' => SwapRequestStatus::PENDING_COLLEAGUE,
            'reason' => $this->faker->optional()->sentence(),
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'expires_at' => now()->addDays(3),
        ];
    }

    public function pendingColleague(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::PENDING_COLLEAGUE,
        ]);
    }

    public function pendingManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::PENDING_MANAGER,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejectedByColleague(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::REJECTED_COLLEAGUE,
        ]);
    }

    public function rejectedByManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::REJECTED_MANAGER,
            'rejection_reason' => $this->faker->sentence(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::CANCELLED,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SwapRequestStatus::EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withOfferedShift(Shift $shift = null): static
    {
        return $this->state(fn (array $attributes) => [
            'offered_shift_id' => $shift?->id ?? Shift::factory(),
        ]);
    }

    public function withoutOfferedShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'offered_shift_id' => null,
        ]);
    }
}
