<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drill_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('config'); // stores scenario/group selection, timer settings
            $table->boolean('use_timer')->default(false);
            $table->unsignedTinyInteger('timer_seconds')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
        });

        Schema::create('drill_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained();
            $table->string('hand', 4);
            $table->enum('user_action', ['fold', 'call', 'raise']);
            $table->enum('correct_action', ['fold', 'call', 'raise']);
            $table->boolean('is_correct');
            $table->enum('mistake_type', ['normal', 'border'])->nullable();
            $table->timestamps();

            $table->index(['drill_session_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drill_answers');
        Schema::dropIfExists('drill_sessions');
    }
};
