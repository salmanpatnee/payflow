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
        Schema::create('client_access_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_collection_id')->constrained('payment_collections')->onDelete('cascade');
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('access_token', 32)->index();
            $table->string('ip_address');
            $table->timestamp('accessed_at');
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Composite index for access history queries
            $table->index(['payment_collection_id', 'accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_access_records');
    }
};
