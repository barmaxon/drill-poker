<?php

namespace App\Services;

use App\Models\DrillAnswer;
use App\Models\DrillSession;
use App\Models\Scenario;
use App\Models\ScenarioGroup;
use App\Models\User;
use App\Models\UserHandStat;
use App\Models\UserScenarioStat;

class DrillService
{
    public function __construct(
        private MistakeCategorizerService $mistakeCategorizer,
        private BorderHandDetectorService $borderDetector
    ) {}

    public function startSession(User $user, array $config): DrillSession
    {
        // Capture pre-drill stats for comparison later
        $scenarioIds = $this->getScenarioIdsFromConfig($config);
        $preDrillStats = $this->captureStats($user->id, $scenarioIds);

        return DrillSession::create([
            'user_id' => $user->id,
            'config' => $config,
            'pre_drill_stats' => $preDrillStats,
            'use_timer' => $config['useTimer'] ?? false,
            'timer_seconds' => $config['timerSeconds'] ?? null,
            'started_at' => now(),
        ]);
    }

    /**
     * Get scenario IDs from drill config.
     */
    private function getScenarioIdsFromConfig(array $config): array
    {
        if ($config['type'] === 'scenario') {
            return [$config['scenarioId']];
        }

        if ($config['type'] === 'global') {
            return Scenario::whereHas('groups', fn($q) =>
                $q->where('is_active', true)
            )->pluck('id')->toArray();
        }

        // Group
        return Scenario::whereHas('groups', fn($q) =>
            $q->where('scenario_groups.id', $config['groupId'])
        )->pluck('id')->toArray();
    }

    /**
     * Capture current stats for scenarios.
     */
    private function captureStats(int $userId, array $scenarioIds): array
    {
        if (empty($scenarioIds)) {
            return ['overall' => 0, 'scenarios' => []];
        }

        $stats = UserScenarioStat::where('user_id', $userId)
            ->whereIn('scenario_id', $scenarioIds)
            ->get();

        $totalAttempts = 0;
        $totalCorrect = 0;
        $scenarioStats = [];

        foreach ($stats as $stat) {
            $totalAttempts += $stat->total_attempts;
            $totalCorrect += $stat->correct_attempts;
            $scenarioStats[$stat->scenario_id] = [
                'total' => $stat->total_attempts,
                'correct' => $stat->correct_attempts,
                'accuracy' => $stat->total_attempts > 0
                    ? round(($stat->correct_attempts / $stat->total_attempts) * 100)
                    : 0,
            ];
        }

        return [
            'overall' => $totalAttempts > 0 ? round(($totalCorrect / $totalAttempts) * 100) : 0,
            'totalAttempts' => $totalAttempts,
            'totalCorrect' => $totalCorrect,
            'scenarios' => $scenarioStats,
        ];
    }

    public function submitAnswer(
        DrillSession $session,
        string $hand,
        int $scenarioId,
        string $userAction
    ): array {
        $scenario = Scenario::findOrFail($scenarioId);
        $grid = $scenario->grid;

        $correctAction = $grid[$hand] ?? 'fold';
        $isCorrect = $userAction === $correctAction;

        $mistakeType = null;
        if (!$isCorrect) {
            $mistakeType = $this->mistakeCategorizer->categorize($hand, $scenario->grid);
        }

        // Record the answer
        DrillAnswer::create([
            'drill_session_id' => $session->id,
            'scenario_id' => $scenarioId,
            'hand' => $hand,
            'user_action' => $userAction,
            'correct_action' => $correctAction,
            'is_correct' => $isCorrect,
            'mistake_type' => $mistakeType,
        ]);

        // Update user stats
        $this->updateStats($session->user_id, $scenarioId, $hand, $isCorrect, $mistakeType);

        return [
            'correct' => $isCorrect,
            'userAction' => $userAction,
            'correctAction' => $correctAction,
            'mistakeType' => $mistakeType,
        ];
    }

    private function updateStats(
        int $userId,
        int $scenarioId,
        string $hand,
        bool $isCorrect,
        ?string $mistakeType
    ): void {
        $weights = config('poker.weights');

        // Update scenario stats
        $scenarioStat = UserScenarioStat::firstOrCreate(
            ['user_id' => $userId, 'scenario_id' => $scenarioId],
            ['total_attempts' => 0, 'correct_attempts' => 0]
        );

        $scenarioStat->total_attempts++;
        if ($isCorrect) {
            $scenarioStat->correct_attempts++;
        }
        $scenarioStat->save();

        // Update hand stats
        $handStat = UserHandStat::firstOrCreate(
            ['user_id' => $userId, 'scenario_id' => $scenarioId, 'hand' => $hand],
            [
                'total_attempts' => 0,
                'correct_attempts' => 0,
                'normal_mistakes' => 0,
                'border_mistakes' => 0,
                'current_weight' => $weights['default'],
            ]
        );

        $handStat->total_attempts++;
        $handStat->last_shown_at = now();

        if ($isCorrect) {
            $handStat->correct_attempts++;
            $handStat->current_weight = max(
                $weights['min'],
                $handStat->current_weight + $weights['correct_adjustment']
            );
        } else {
            if ($mistakeType === 'border') {
                $handStat->border_mistakes++;
                $handStat->current_weight = min(
                    $weights['max'],
                    $handStat->current_weight + $weights['wrong_border_adjustment']
                );
            } else {
                $handStat->normal_mistakes++;
                $handStat->current_weight = min(
                    $weights['max'],
                    $handStat->current_weight + $weights['wrong_normal_adjustment']
                );
            }
        }

        $handStat->save();
    }

    public function endSession(DrillSession $session): array
    {
        $session->ended_at = now();
        $session->save();

        $answers = $session->answers;
        $totalHands = $answers->count();
        $correctCount = $answers->where('is_correct', true)->count();
        $borderMistakes = $answers->where('mistake_type', 'border')->count();
        $accuracy = $totalHands > 0 ? round(($correctCount / $totalHands) * 100) : 0;

        $mistakes = $answers->where('is_correct', false)->map(fn($a) => [
            'hand' => $a->hand,
            'scenario' => $a->scenario->name,
            'positions' => $a->scenario->positions,
            'stackDepth' => $a->scenario->stack_depth,
            'userAction' => $a->user_action,
            'correctAction' => $a->correct_action,
            'isBorder' => $a->mistake_type === 'border',
        ])->values();

        $result = [
            'totalHands' => $totalHands,
            'correctCount' => $correctCount,
            'incorrectCount' => $totalHands - $correctCount,
            'accuracy' => $accuracy,
            'borderMistakes' => $borderMistakes,
            'mistakes' => $mistakes,
        ];

        // Add comparison if we have pre-drill stats
        if ($session->pre_drill_stats) {
            $scenarioIds = $this->getScenarioIdsFromConfig($session->config);
            $postDrillStats = $this->captureStats($session->user_id, $scenarioIds);
            $preDrillStats = $session->pre_drill_stats;

            $result['comparison'] = [
                'overallBefore' => $preDrillStats['overall'] ?? 0,
                'overallAfter' => $postDrillStats['overall'],
                'change' => $postDrillStats['overall'] - ($preDrillStats['overall'] ?? 0),
                'attemptsBefore' => $preDrillStats['totalAttempts'] ?? 0,
                'attemptsAfter' => $postDrillStats['totalAttempts'],
            ];
        }

        return $result;
    }
}
