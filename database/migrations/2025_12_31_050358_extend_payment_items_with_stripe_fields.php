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
        Schema::table('payment_items', function (Blueprint $table) {
            // Add Stripe payment processing fields
            $table->decimal('amount', 10, 2)->nullable()->after('description')->comment('Payment amount');
            $table->string('currency', 3)->default('usd')->after('amount')->comment('Currency code (e.g., usd, eur, gbp)');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('currency')
                ->comment('Payment status');
            $table->string('stripe_payment_intent_id')->nullable()->unique()->after('status')->comment('Stripe PaymentIntent ID');
            $table->timestamp('paid_at')->nullable()->after('stripe_payment_intent_id')->comment('Payment completion timestamp');

            // Add indexes for frequently queried fields
            $table->index('status', 'payment_items_status_index');
        });

        // Copy price to amount for existing records and set default for new records
        \DB::statement('UPDATE payment_items SET amount = price WHERE amount IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_items', function (Blueprint $table) {
            $table->dropIndex('payment_items_status_index');
            $table->dropColumn([
                'amount',
                'currency',
                'status',
                'stripe_payment_intent_id',
                'paid_at',
            ]);
        });
    }
};
