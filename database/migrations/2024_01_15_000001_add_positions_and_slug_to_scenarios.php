<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            // Add new columns
            $table->json('positions')->after('name')->nullable();
            $table->string('slug')->after('positions')->nullable();
        });

        // Migrate existing position data to positions array
        $scenarios = DB::table('scenarios')->get();
        foreach ($scenarios as $scenario) {
            $positions = json_encode([$scenario->position]);
            $slug = $this->generateSlug($scenario->name, [$scenario->position], $scenario->stack_depth);

            DB::table('scenarios')
                ->where('id', $scenario->id)
                ->update([
                    'positions' => $positions,
                    'slug' => $slug,
                ]);
        }

        // Make positions and slug required, add unique constraint
        Schema::table('scenarios', function (Blueprint $table) {
            $table->json('positions')->nullable(false)->change();
            $table->string('slug')->nullable(false)->unique()->change();
        });

        // Drop old position column
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        // Update index
        Schema::table('scenarios', function (Blueprint $table) {
            $table->index('stack_depth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropIndex(['stack_depth']);
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->enum('position', ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'])
                ->after('name')
                ->nullable();
        });

        // Migrate positions back to position (use first position)
        $scenarios = DB::table('scenarios')->get();
        foreach ($scenarios as $scenario) {
            $positions = json_decode($scenario->positions, true);
            $position = $positions[0] ?? 'BTN';

            DB::table('scenarios')
                ->where('id', $scenario->id)
                ->update(['position' => $position]);
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->enum('position', ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'])
                ->nullable(false)
                ->change();
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['positions', 'slug']);
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->index(['position', 'stack_depth']);
        });
    }

    /**
     * Generate a slug for migration purposes.
     */
    private function generateSlug(string $name, array $positions, int $stackDepth): string
    {
        $nameSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($name)));
        $nameSlug = trim($nameSlug, '-');

        $positionGroup = $this->getPositionGroup($positions);

        return "{$nameSlug}-{$positionGroup}-{$stackDepth}bb";
    }

    /**
     * Get position group name for slug.
     */
    private function getPositionGroup(array $positions): string
    {
        sort($positions);

        $groups = [
            'early' => ['UTG', 'UTG+1', 'UTG+2'],
            'middle' => ['LJ', 'HJ'],
            'late' => ['CO', 'BTN'],
            'blinds' => ['SB', 'BB'],
        ];

        foreach ($groups as $groupName => $groupPositions) {
            sort($groupPositions);
            if ($positions === $groupPositions) {
                return $groupName;
            }
        }

        // Custom: join position slugs
        return strtolower(implode('-', array_map(function ($pos) {
            return str_replace('+', 'plus', $pos);
        }, $positions)));
    }
};
