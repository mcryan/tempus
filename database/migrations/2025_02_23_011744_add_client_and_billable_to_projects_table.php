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
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'client_id')) {
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'is_billable')) {
                $table->boolean('is_billable')->default(true);
            }
            if (!Schema::hasColumn('projects', 'billable_rate')) {
                $table->decimal('billable_rate', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'is_billable', 'billable_rate']);
        });
    }
};
