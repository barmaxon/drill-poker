<?php

namespace App\Services;

use App\Models\Scenario;

class SlugGeneratorService
{
    /**
     * Position group definitions.
     */
    private const POSITION_GROUPS = [
        'early' => ['UTG', 'UTG+1', 'UTG+2'],
        'middle' => ['LJ', 'HJ'],
        'late' => ['CO', 'BTN'],
        'blinds' => ['SB', 'BB'],
    ];

    /**
     * Generate a slug for a scenario.
     *
     * Format: {name-slug}-{position-group}-{stack}bb
     */
    public function generate(string $name, array $positions, int $stackDepth): string
    {
        $nameSlug = $this->slugifyName($name);
        $positionGroup = $this->getPositionGroupName($positions);

        return "{$nameSlug}-{$positionGroup}-{$stackDepth}bb";
    }

    /**
     * Generate a unique slug, appending a suffix if necessary.
     */
    public function generateUnique(string $name, array $positions, int $stackDepth, ?int $excludeId = null): string
    {
        $baseSlug = $this->generate($name, $positions, $stackDepth);
        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $suffix++;
            $slug = "{$baseSlug}-{$suffix}";
        }

        return $slug;
    }

    /**
     * Convert scenario name to URL-friendly slug.
     */
    private function slugifyName(string $name): string
    {
        // Convert to lowercase
        $slug = strtolower($name);

        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');

        return $slug ?: 'scenario';
    }

    /**
     * Get the position group name for the given positions.
     */
    public function getPositionGroupName(array $positions): string
    {
        // Sort positions for consistent comparison
        $sortedPositions = $positions;
        sort($sortedPositions);

        // Check if positions match a predefined group
        foreach (self::POSITION_GROUPS as $groupName => $groupPositions) {
            $sortedGroup = $groupPositions;
            sort($sortedGroup);

            if ($sortedPositions === $sortedGroup) {
                return $groupName;
            }
        }

        // Custom: join position slugs
        return strtolower(implode('-', array_map(function ($position) {
            // Convert UTG+1 to utg1, UTG+2 to utg2, etc.
            return str_replace('+', '', strtolower($position));
        }, $sortedPositions)));
    }

    /**
     * Check if a slug already exists.
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Scenario::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
