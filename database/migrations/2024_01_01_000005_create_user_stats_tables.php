<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-scenario aggregate stats
        Schema::create('user_scenario_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('correct_attempts')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'scenario_id']);
        });

        // Per-hand granular stats
        Schema::create('user_hand_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->string('hand', 4);
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('correct_attempts')->default(0);
            $table->unsignedInteger('normal_mistakes')->default(0);
            $table->unsignedInteger('border_mistakes')->default(0);
            $table->decimal('current_weight', 4, 2)->default(1.0); // 0.10 to 10.00
            $table->timestamp('last_shown_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scenario_id', 'hand']);
            $table->index(['user_id', 'current_weight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_hand_stats');
        Schema::dropIfExists('user_scenario_stats');
    }
};
