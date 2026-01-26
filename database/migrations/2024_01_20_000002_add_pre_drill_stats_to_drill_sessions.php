<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drill_sessions', function (Blueprint $table) {
            $table->json('pre_drill_stats')->nullable()->after('config');
        });
    }

    public function down(): void
    {
        Schema::table('drill_sessions', function (Blueprint $table) {
            $table->dropColumn('pre_drill_stats');
        });
    }
};
