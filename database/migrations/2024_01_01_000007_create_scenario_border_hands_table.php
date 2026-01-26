<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-computed border hands for each scenario
        Schema::create('scenario_border_hands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->string('hand', 4); // e.g., 'AKs', 'QTo', '77'
            $table->timestamps();

            $table->unique(['scenario_id', 'hand']);
            $table->index('scenario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_border_hands');
    }
};
