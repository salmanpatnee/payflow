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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->unique()->constrained('payment_transactions')->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->json('receipt_data');
            $table->string('delivery_status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->text('delivery_error')->nullable();
            $table->timestamps();

            // Add indexes
            $table->index('payment_transaction_id');
            $table->index('receipt_number');
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
