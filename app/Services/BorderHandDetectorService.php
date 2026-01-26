<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\ScenarioBorderHand;

class BorderHandDetectorService
{
    private const RANKS = ['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'];

    private const ADJACENT_OFFSETS = [
        [-1, -1], [-1, 0], [-1, 1],
        [0, -1],          [0, 1],
        [1, -1], [1, 0], [1, 1],
    ];

    /**
     * Detect border hands in a grid.
     * Returns array of hand notations.
     */
    public function detect(array $grid): array
    {
        $borderHands = [];

        for ($row = 0; $row < 13; $row++) {
            for ($col = 0; $col < 13; $col++) {
                $hand = $this->getHandAtPosition($row, $col);
                if ($this->isBorderHand($hand, $grid, $row, $col)) {
                    $borderHands[] = $hand;
                }
            }
        }

        return $borderHands;
    }

    /**
     * Calculate distance from border for all hands using BFS.
     * Returns array of [hand => distance] where distance 0 = border hand.
     */
    public function calculateBorderDistances(array $grid): array
    {
        $distances = [];
        $queue = [];

        // Initialize: find all border hands (distance 0)
        for ($row = 0; $row < 13; $row++) {
            for ($col = 0; $col < 13; $col++) {
                $hand = $this->getHandAtPosition($row, $col);
                if ($this->isBorderHand($hand, $grid, $row, $col)) {
                    $distances[$hand] = 0;
                    $queue[] = [$row, $col, 0];
                }
            }
        }

        // BFS to calculate distances from border
        $visited = [];
        foreach ($distances as $hand => $dist) {
            $pos = $this->getPositionForHand($hand);
            $visited[$pos['row'] . ',' . $pos['col']] = true;
        }

        while (!empty($queue)) {
            [$row, $col, $distance] = array_shift($queue);
            $currentHand = $this->getHandAtPosition($row, $col);
            $currentAction = $grid[$currentHand] ?? 'fold';

            foreach (self::ADJACENT_OFFSETS as [$dRow, $dCol]) {
                $newRow = $row + $dRow;
                $newCol = $col + $dCol;
                $key = $newRow . ',' . $newCol;

                if ($newRow >= 0 && $newRow < 13 && $newCol >= 0 && $newCol < 13 && !isset($visited[$key])) {
                    $adjacentHand = $this->getHandAtPosition($newRow, $newCol);
                    $adjacentAction = $grid[$adjacentHand] ?? 'fold';

                    // Only propagate within same action (distance from border within a region)
                    if ($adjacentAction === $currentAction) {
                        $visited[$key] = true;
                        $newDistance = $distance + 1;
                        $distances[$adjacentHand] = $newDistance;
                        $queue[] = [$newRow, $newCol, $newDistance];
                    }
                }
            }
        }

        // Fill in any hands not reached (isolated regions, though rare)
        for ($row = 0; $row < 13; $row++) {
            for ($col = 0; $col < 13; $col++) {
                $hand = $this->getHandAtPosition($row, $col);
                if (!isset($distances[$hand])) {
                    // Far from any border
                    $distances[$hand] = 99;
                }
            }
        }

        return $distances;
    }

    /**
     * Get position (row, col) for a hand notation.
     */
    private function getPositionForHand(string $hand): array
    {
        $rank1 = $hand[0];
        $rank2 = $hand[1];
        $suffix = $hand[2] ?? '';

        $row1 = array_search($rank1, self::RANKS);
        $row2 = array_search($rank2, self::RANKS);

        if ($rank1 === $rank2) {
            // Pair
            return ['row' => $row1, 'col' => $row1];
        } elseif ($suffix === 's') {
            // Suited: higher rank is row, lower rank is col
            return ['row' => min($row1, $row2), 'col' => max($row1, $row2)];
        } else {
            // Offsuit: lower rank is row, higher rank is col
            return ['row' => max($row1, $row2), 'col' => min($row1, $row2)];
        }
    }

    private function isBorderHand(string $hand, array $grid, int $row, int $col): bool
    {
        $currentAction = $grid[$hand] ?? 'fold';

        foreach (self::ADJACENT_OFFSETS as [$dRow, $dCol]) {
            $newRow = $row + $dRow;
            $newCol = $col + $dCol;

            if ($newRow >= 0 && $newRow < 13 && $newCol >= 0 && $newCol < 13) {
                $adjacentHand = $this->getHandAtPosition($newRow, $newCol);
                $adjacentAction = $grid[$adjacentHand] ?? 'fold';

                if ($adjacentAction !== $currentAction) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getHandAtPosition(int $row, int $col): string
    {
        $rank1 = self::RANKS[$row];
        $rank2 = self::RANKS[$col];

        if ($row === $col) {
            return $rank1 . $rank2;
        } elseif ($row < $col) {
            return $rank1 . $rank2 . 's';
        } else {
            return $rank2 . $rank1 . 'o';
        }
    }

    /**
     * Compute border hands with distances for a scenario and store them in the database.
     */
    public function computeAndStore(Scenario $scenario): void
    {
        // Delete existing border hands
        $scenario->borderHands()->delete();

        // Calculate distances for all hands
        $distances = $this->calculateBorderDistances($scenario->grid);

        // Store hands with distances (only store hands with meaningful distance)
        foreach ($distances as $hand => $distance) {
            // Store all hands with distance info (for weighted selection)
            ScenarioBorderHand::create([
                'scenario_id' => $scenario->id,
                'hand' => $hand,
                'border_distance' => min($distance, 255), // Cap at max tinyint
            ]);
        }
    }
}
