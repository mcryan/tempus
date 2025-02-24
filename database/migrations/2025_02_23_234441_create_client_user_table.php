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
        if (!Schema::hasTable('client_user')) {
            Schema::create('client_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['client_id', 'user_id']);
            });
        } else {
            Schema::table('client_user', function (Blueprint $table) {
                if (!Schema::hasColumn('client_user', 'client_id')) {
                    $table->foreignId('client_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('client_user', 'user_id')) {
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('client_user', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_user');
    }
};
