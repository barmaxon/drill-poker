<?php

namespace App\Services;

use App\Models\Scenario;
use Illuminate\Support\Facades\DB;

class ScenarioNameService
{
    /**
     * All available positions.
     */
    private const ALL_POSITIONS = ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'];

    /**
     * Get the canonical (existing) case for a scenario name.
     * Returns the user's input if no existing scenario with that name exists.
     */
    public function getCanonicalName(string $name, int $userId): string
    {
        $existing = Scenario::where('created_by', $userId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        return $existing ? $existing->name : $name;
    }

    /**
     * Get all unique scenario names for a user.
     */
    public function getUniqueNames(int $userId): array
    {
        return Scenario::where('created_by', $userId)
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get name suggestions with available position information.
     * Returns names that have unfilled position variations.
     */
    public function getAvailableNames(int $userId): array
    {
        // Get all scenarios grouped by name (case-insensitive)
        $scenarios = Scenario::where('created_by', $userId)
            ->get()
            ->groupBy(function ($scenario) {
                return strtolower($scenario->name);
            });

        $suggestions = [];

        foreach ($scenarios as $lowerName => $group) {
            // Use the first scenario's actual name (canonical case)
            $canonicalName = $group->first()->name;

            // Collect all used positions for this name across all stack depths
            $usedPositions = [];
            $stackDepths = [];
            $lastUpdated = null;

            foreach ($group as $scenario) {
                $positions = $scenario->positions;
                foreach ($positions as $position) {
                    $usedPositions[$position] = true;
                }
                $stackDepths[$scenario->stack_depth] = true;

                // Track most recent update
                if ($lastUpdated === null || $scenario->updated_at > $lastUpdated) {
                    $lastUpdated = $scenario->updated_at;
                }
            }

            // Calculate available positions
            $availablePositions = array_values(array_filter(
                self::ALL_POSITIONS,
                fn($pos) => !isset($usedPositions[$pos])
            ));

            $suggestions[] = [
                'name' => $canonicalName,
                'usedPositions' => array_keys($usedPositions),
                'availablePositions' => $availablePositions,
                'stackDepths' => array_keys($stackDepths),
                'scenarioCount' => $group->count(),
                'lastUpdated' => $lastUpdated?->toISOString(),
            ];
        }

        // Sort by most recently updated (descending)
        usort($suggestions, fn($a, $b) => strcmp($b['lastUpdated'] ?? '', $a['lastUpdated'] ?? ''));

        return $suggestions;
    }

    /**
     * Check if a position is available for a given name and stack depth.
     */
    public function isPositionAvailable(string $name, string $position, int $stackDepth, int $userId, ?int $excludeId = null): bool
    {
        $query = Scenario::where('created_by', $userId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('stack_depth', $stackDepth)
            ->whereJsonContains('positions', $position);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get positions that overlap with existing scenarios.
     */
    public function getOverlappingPositions(string $name, array $positions, int $stackDepth, int $userId, ?int $excludeId = null): array
    {
        $overlapping = [];

        foreach ($positions as $position) {
            if (!$this->isPositionAvailable($name, $position, $stackDepth, $userId, $excludeId)) {
                $overlapping[] = $position;
            }
        }

        return $overlapping;
    }
}
