<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('position', ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB']);
            $table->unsignedSmallInteger('stack_depth'); // in big blinds
            $table->unsignedTinyInteger('limpers')->default(0);
            $table->json('grid'); // 169 hands with actions (fold/call/raise)
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['position', 'stack_depth']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};
