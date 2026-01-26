<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenario_border_hands', function (Blueprint $table) {
            $table->tinyInteger('border_distance')->unsigned()->default(0)->after('hand');
        });
    }

    public function down(): void
    {
        Schema::table('scenario_border_hands', function (Blueprint $table) {
            $table->dropColumn('border_distance');
        });
    }
};
