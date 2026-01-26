<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('range_construction_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('scenario_id')->constrained()->onDelete('cascade');
            $table->json('user_grid');
            $table->decimal('accuracy', 5, 2);
            $table->unsignedSmallInteger('correct_cells');
            $table->unsignedSmallInteger('incorrect_cells');
            $table->unsignedSmallInteger('time_seconds')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_range_construction_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('scenario_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('total_attempts')->default(0);
            $table->decimal('best_accuracy', 5, 2)->default(0);
            $table->decimal('avg_accuracy', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'scenario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_range_construction_stats');
        Schema::dropIfExists('range_construction_sessions');
    }
};
