<?php

namespace App\Services;

use App\Models\DrillSession;
use App\Models\Scenario;
use App\Models\ScenarioBorderHand;
use App\Models\ScenarioGroup;
use App\Models\UserHandStat;
use App\Models\UserScenarioStat;

class HandRandomizerService
{
    private const SUITS = ['s', 'h', 'd', 'c'];

    public function __construct(
        private BorderHandDetectorService $borderDetector
    ) {}

    public function getNextHand(DrillSession $session): ?array
    {
        $scenarios = $this->getSessionScenarios($session);
        if (empty($scenarios)) {
            return null;
        }

        // Pick a random scenario
        $scenario = $scenarios[array_rand($scenarios)];

        if (!$scenario->grid) {
            return null;
        }

        // Get weighted random hand
        $hand = $this->selectWeightedHand(
            $session->user_id,
            $scenario->id,
            $scenario->grid
        );

        // Generate random cards for the hand
        $cards = $this->getRandomCardsForHand($hand);

        // Pick a random position from the scenario's positions
        $positions = $scenario->positions;
        $position = $positions[array_rand($positions)];

        return [
            'hand' => [
                'notation' => $hand,
                'card1' => $cards['card1'],
                'card2' => $cards['card2'],
            ],
            'scenario' => [
                'id' => $scenario->id,
                'name' => $scenario->name,
                'description' => $scenario->description,
                'position' => $position,
                'positions' => $scenario->positions,
                'stackDepth' => $scenario->stack_depth,
                'limpers' => $scenario->limpers,
            ],
        ];
    }

    private function getSessionScenarios(DrillSession $session): array
    {
        $config = $session->config;

        if ($config['type'] === 'scenario') {
            $scenario = Scenario::find($config['scenarioId']);
            return $scenario ? [$scenario] : [];
        }

        if ($config['type'] === 'global') {
            // Get scenarios from all ACTIVE groups, deduplicated
            $scenarios = Scenario::whereHas('groups', fn($q) =>
                $q->where('is_active', true)
            )->get()->unique('id');

            // Prioritize by lowest accuracy
            return $this->prioritizeByAccuracy($session->user_id, $scenarios);
        }

        // Group
        return Scenario::whereHas('groups', fn($q) => $q->where('scenario_groups.id', $config['groupId']))
            ->get()
            ->all();
    }

    /**
     * Prioritize scenarios by lowest accuracy (weighted random).
     */
    private function prioritizeByAccuracy(int $userId, $scenarios): array
    {
        if ($scenarios->isEmpty()) {
            return [];
        }

        // Get user stats for these scenarios
        $stats = UserScenarioStat::where('user_id', $userId)
            ->whereIn('scenario_id', $scenarios->pluck('id'))
            ->get()
            ->keyBy('scenario_id');

        $weightedScenarios = [];
        $totalWeight = 0;

        foreach ($scenarios as $scenario) {
            $stat = $stats->get($scenario->id);
            $accuracy = 100; // Default for never-attempted scenarios (lower priority)

            if ($stat && $stat->total_attempts > 0) {
                $accuracy = ($stat->correct_attempts / $stat->total_attempts) * 100;
            } else {
                // Never attempted - give moderate priority
                $accuracy = 50;
            }

            // Weight inversely proportional to accuracy (lower accuracy = higher weight)
            $weight = max(1, 101 - $accuracy);
            $totalWeight += $weight;
            $weightedScenarios[] = ['scenario' => $scenario, 'weight' => $weight];
        }

        // Weighted random selection to build prioritized list
        // Pick one scenario with weighted probability
        $random = mt_rand() / mt_getrandmax() * $totalWeight;
        foreach ($weightedScenarios as $item) {
            $random -= $item['weight'];
            if ($random <= 0) {
                return [$item['scenario']];
            }
        }

        return [$scenarios->first()];
    }

    private function selectWeightedHand(int $userId, int $scenarioId, array $grid): string
    {
        $allHands = config('poker.hand_strength_order');
        $weights = config('poker.weights');
        $baseMultipliers = config('poker.distance_multipliers');

        // Get user's accuracy for this scenario
        $scenarioStat = UserScenarioStat::where('user_id', $userId)
            ->where('scenario_id', $scenarioId)
            ->first();

        $accuracy = 0;
        if ($scenarioStat && $scenarioStat->total_attempts > 0) {
            $accuracy = ($scenarioStat->correct_attempts / $scenarioStat->total_attempts) * 100;
        }

        // Adjust distance multipliers based on accuracy
        $distanceMultipliers = $this->getAdjustedMultipliers($baseMultipliers, $accuracy);

        // Get border distances from database (pre-computed)
        $borderDistances = ScenarioBorderHand::where('scenario_id', $scenarioId)
            ->get()
            ->pluck('border_distance', 'hand')
            ->toArray();

        // If no border distances stored, fall back to detection
        if (empty($borderDistances)) {
            $borderDistances = $this->borderDetector->calculateBorderDistances($grid);
        }

        // Get user stats for these hands
        $stats = UserHandStat::where('user_id', $userId)
            ->where('scenario_id', $scenarioId)
            ->get()
            ->keyBy('hand');

        $weightedHands = [];
        $totalWeight = 0;

        foreach ($allHands as $hand) {
            $stat = $stats->get($hand);
            $baseWeight = $stat ? $stat->current_weight : $weights['default'];

            // Apply distance-based multiplier
            $distance = $borderDistances[$hand] ?? 99;
            $distanceMultiplier = $distanceMultipliers[$distance]
                ?? $distanceMultipliers['default']
                ?? 1.0;

            $finalWeight = $baseWeight * $distanceMultiplier;

            $totalWeight += $finalWeight;
            $weightedHands[] = ['hand' => $hand, 'weight' => $finalWeight];
        }

        // Random selection based on weights
        $random = mt_rand() / mt_getrandmax() * $totalWeight;
        foreach ($weightedHands as $item) {
            $random -= $item['weight'];
            if ($random <= 0) {
                return $item['hand'];
            }
        }

        return $allHands[0];
    }

    /**
     * Adjust distance multipliers based on scenario accuracy.
     * Higher accuracy = more focus on border hands, less on easy hands.
     */
    private function getAdjustedMultipliers(array $baseMultipliers, float $accuracy): array
    {
        // Base increase of 50% for border hands
        $multipliers = [
            0 => $baseMultipliers[0] * 1.5,      // 3.0 -> 4.5
            1 => $baseMultipliers[1] * 1.5,      // 2.0 -> 3.0
            2 => $baseMultipliers[2] * 1.5,      // 1.5 -> 2.25
            3 => $baseMultipliers[3] * 1.5,      // 1.2 -> 1.8
            'default' => $baseMultipliers['default'],  // 1.0 stays
        ];

        // If accuracy > 90%, make deep-in-range hands unlikely
        if ($accuracy > 90) {
            $multipliers[0] = $baseMultipliers[0] * 2.5;  // 3.0 -> 7.5
            $multipliers[1] = $baseMultipliers[1] * 2.0;  // 2.0 -> 4.0
            $multipliers[2] = $baseMultipliers[2] * 1.5;  // 1.5 -> 2.25
            $multipliers[3] = $baseMultipliers[3] * 1.2;  // 1.2 -> 1.44
            $multipliers['default'] = 0.35;               // 1.0 -> 0.35 (~10% of hands)
        } elseif ($accuracy > 80) {
            // Moderate adjustment for 80-90% accuracy
            $multipliers['default'] = 0.5;
        }

        return $multipliers;
    }

    private function getRandomCardsForHand(string $hand): array
    {
        $rank1 = $hand[0];
        $rank2 = $hand[1];
        $isPair = $rank1 === $rank2;
        $isSuited = ($hand[2] ?? '') === 's';

        $availableSuits = self::SUITS;
        shuffle($availableSuits);

        $suit1 = $availableSuits[0];

        if ($isPair || !$isSuited) {
            $suit2 = $availableSuits[1];
        } else {
            $suit2 = $suit1;
        }

        return [
            'card1' => ['rank' => $rank1, 'suit' => $suit1],
            'card2' => ['rank' => $rank2, 'suit' => $suit2],
        ];
    }
}
