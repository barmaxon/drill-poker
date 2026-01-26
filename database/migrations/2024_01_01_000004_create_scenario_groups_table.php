<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Pivot table
        Schema::create('group_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('scenario_groups')->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'scenario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_scenarios');
        Schema::dropIfExists('scenario_groups');
    }
};
