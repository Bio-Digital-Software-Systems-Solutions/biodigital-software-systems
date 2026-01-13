<?php

namespace App\Jobs;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use App\Services\Workflow\StepExecutorService;
use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\WorkflowInstanceStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWorkflowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public WorkflowStepInstance $stepInstance
    ) {}

    public function handle(StepExecutorService $executor): void
    {
        $stepInstance = $this->stepInstance->fresh(['step', 'workflowInstance']);

        if (!$stepInstance) {
            Log::warning('Step instance not found', ['id' => $this->stepInstance->id]);
            return;
        }

        // Check if already processed
        if ($stepInstance->status !== StepInstanceStatus::PENDING &&
            $stepInstance->status !== StepInstanceStatus::ACTIVE) {
            Log::info('Step already processed', [
                'step_instance_id' => $stepInstance->id,
                'status' => $stepInstance->status->value,
            ]);
            return;
        }

        // Check if workflow is still running
        $workflowInstance = $stepInstance->workflowInstance;
        if ($workflowInstance->status !== WorkflowInstanceStatus::RUNNING) {
            Log::info('Workflow not running', [
                'workflow_instance_id' => $workflowInstance->id,
                'status' => $workflowInstance->status->value,
            ]);
            return;
        }

        try {
            // Mark as active
            $stepInstance->update([
                'status' => StepInstanceStatus::ACTIVE,
                'started_at' => now(),
            ]);

            // Execute the step
            $result = $executor->execute($stepInstance);

            if ($result['success']) {
                // Mark as completed
                $stepInstance->update([
                    'status' => StepInstanceStatus::COMPLETED,
                    'completed_at' => now(),
                    'output_data' => $result['output'] ?? null,
                ]);

                // Dispatch transition job to find and process next steps
                ExecuteWorkflowTransition::dispatch($stepInstance);

                Log::info('Step completed', [
                    'step_instance_id' => $stepInstance->id,
                    'step_type' => $stepInstance->step->type->value,
                ]);
            } elseif ($result['waiting']) {
                // Step is waiting for external input (e.g., approval)
                $stepInstance->update([
                    'status' => StepInstanceStatus::WAITING,
                ]);

                Log::info('Step waiting for input', [
                    'step_instance_id' => $stepInstance->id,
                    'step_type' => $stepInstance->step->type->value,
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }
        } catch (\Throwable $e) {
            Log::error('Step execution failed', [
                'step_instance_id' => $stepInstance->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $stepInstance->update([
                'status' => StepInstanceStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            // Check if we should retry or fail the workflow
            if ($this->attempts() >= $this->tries) {
                $workflowInstance->update([
                    'status' => WorkflowInstanceStatus::FAILED,
                    'error_message' => "Step '{$stepInstance->step->name}' failed: {$e->getMessage()}",
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWorkflowStep job failed permanently', [
            'step_instance_id' => $this->stepInstance->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
