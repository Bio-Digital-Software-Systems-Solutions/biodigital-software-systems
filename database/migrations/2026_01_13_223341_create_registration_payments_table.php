<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registration_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->string('status')->default('pending'); // pending, completed, failed, refunded, partial, cancelled
            $table->string('payment_method')->nullable(); // stripe, paypal, bank_transfer, cash, free
            $table->string('payment_provider')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->json('provider_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamps();

            $table->index('payment_number');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_payments');
    }
};
