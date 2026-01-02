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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('stripe_event_id')->nullable()->after('payment_item_id');
            $table->string('stripe_event_type')->nullable()->after('stripe_event_id');
            $table->json('payload')->nullable()->after('stripe_event_type');
            $table->timestamp('processed_at')->nullable()->after('payload');
            $table->string('processing_status')->default('pending')->after('processed_at');
            $table->integer('processing_attempts')->default(0)->after('processing_status');
            $table->text('processing_error')->nullable()->after('processing_attempts');
            $table->timestamp('updated_at')->after('created_at');

            // Add indexes
            $table->index('payment_item_id');
            $table->index('processing_status');

            // Rename existing columns to match new schema
            $table->renameColumn('stripe_response', 'stripe_response_legacy');
            $table->renameColumn('status', 'status_legacy');
            $table->renameColumn('error_message', 'error_message_legacy');
        });

        // Populate stripe_event_id for existing records using UUID
        DB::statement('UPDATE payment_transactions SET stripe_event_id = UUID() WHERE stripe_event_id IS NULL');

        // Now add unique constraint
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unique('stripe_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Rename columns back
            $table->renameColumn('stripe_response_legacy', 'stripe_response');
            $table->renameColumn('status_legacy', 'status');
            $table->renameColumn('error_message_legacy', 'error_message');

            // Drop indexes
            $table->dropIndex(['stripe_event_id']);
            $table->dropIndex(['payment_item_id']);
            $table->dropIndex(['processing_status']);

            // Drop new columns
            $table->dropColumn([
                'stripe_event_id',
                'stripe_event_type',
                'payload',
                'processed_at',
                'processing_status',
                'processing_attempts',
                'processing_error',
                'updated_at',
            ]);
        });
    }
};
