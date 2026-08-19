<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_outbox_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->restrictOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('recipient');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            // A stale lock is reclaimed by the dispatcher after ten minutes.
            // Delivery is durable at-least-once. A transport that cannot deduplicate
            // the stable idempotency_key may still send a duplicate after a crash
            // between external delivery and processed_at being committed.
            $table->uuid('claim_token')->nullable()->unique();
            $table->timestamp('locked_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_outbox_events');
    }
};
