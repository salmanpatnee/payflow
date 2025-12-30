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
        Schema::table('payment_collections', function (Blueprint $table) {
            $table->string('payment_link_token', 32)->nullable()->unique();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->index('payment_link_token');
            $table->index('payment_link_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_collections', function (Blueprint $table) {
            $table->dropIndex('payment_collections_payment_link_token_index');
            $table->dropIndex('payment_collections_payment_link_expires_at_index');
            $table->dropUnique('payment_collections_payment_link_token_unique');
            $table->dropColumn('payment_link_token');
            $table->dropColumn('payment_link_expires_at');
        });
    }
};
