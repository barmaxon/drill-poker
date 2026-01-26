<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hand_strengths', function (Blueprint $table) {
            $table->id();
            $table->string('hand', 4)->unique(); // e.g., 'AKs', 'AA', 'T9o'
            $table->unsignedTinyInteger('rank'); // 1 = strongest (AA), 169 = weakest (72o)

            $table->index('rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hand_strengths');
    }
};
