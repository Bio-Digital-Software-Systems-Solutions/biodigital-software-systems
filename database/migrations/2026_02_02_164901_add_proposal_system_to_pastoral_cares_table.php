<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the status ENUM to include 'proposed'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pastoral_cares MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show', 'proposed') DEFAULT 'pending'");
        }

        Schema::table('pastoral_cares', function (Blueprint $table): void {
            // Proposal tracking columns
            $table->boolean('is_proposal')->default(false)->after('status');
            $table->text('proposal_reason')->nullable()->after('is_proposal');

            // Counter-proposal from MLR
            $table->date('counter_proposed_date')->nullable()->after('proposal_reason');
            $table->time('counter_proposed_time')->nullable()->after('counter_proposed_date');
            $table->text('counter_proposal_message')->nullable()->after('counter_proposed_time');

            // Proposal response tracking
            $table->enum('proposal_response_status', ['pending', 'accepted', 'rejected', 'counter_proposed'])->nullable()->after('counter_proposal_message');
            $table->text('proposal_rejection_reason')->nullable()->after('proposal_response_status');
            $table->string('proposal_token', 64)->nullable()->unique()->after('proposal_rejection_reason');

            // MLR agent who handled the proposal
            $table->foreignId('mlr_agent_id')->nullable()->after('proposal_token')->constrained('users')->onDelete('set null');

            // Timestamps for proposal workflow
            $table->timestamp('proposal_submitted_at')->nullable()->after('mlr_agent_id');
            $table->timestamp('proposal_reviewed_at')->nullable()->after('proposal_submitted_at');
            $table->timestamp('counter_proposal_sent_at')->nullable()->after('proposal_reviewed_at');
            $table->timestamp('client_responded_at')->nullable()->after('counter_proposal_sent_at');

            // Index for efficient querying of proposals
            $table->index(['is_proposal', 'proposal_response_status']);
            $table->index('proposal_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table): void {
            $table->dropIndex(['is_proposal', 'proposal_response_status']);
            $table->dropIndex(['proposal_token']);

            $table->dropForeign(['mlr_agent_id']);

            $table->dropColumn([
                'is_proposal',
                'proposal_reason',
                'counter_proposed_date',
                'counter_proposed_time',
                'counter_proposal_message',
                'proposal_response_status',
                'proposal_rejection_reason',
                'proposal_token',
                'mlr_agent_id',
                'proposal_submitted_at',
                'proposal_reviewed_at',
                'counter_proposal_sent_at',
                'client_responded_at',
            ]);
        });

        // Revert status ENUM
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pastoral_cares MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending'");
        }
    }
};
