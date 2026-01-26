<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrillAnswer;
use App\Models\DrillSession;
use App\Models\Scenario;
use App\Models\UserScenarioStat;
use App\Services\DrillService;
use App\Services\HandRandomizerService;
use App\Services\MistakeCategorizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrillController extends Controller
{
    public function __construct(
        private DrillService $drillService,
        private HandRandomizerService $randomizer,
        private MistakeCategorizerService $mistakeCategorizer
    ) {}

    public function start(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:scenario,group,global'],
            'scenarioId' => ['nullable', 'required_if:type,scenario', 'exists:scenarios,id'],
            'groupId' => ['nullable', 'required_if:type,group', 'exists:scenario_groups,id'],
            'useTimer' => ['boolean'],
            'timerSeconds' => ['integer', 'min:1', 'max:60'],
            'handLimit' => ['nullable', 'integer', 'min:1'],
        ]);

        $session = $this->drillService->startSession(
            $request->user(),
            $validated
        );

        return response()->json([
            'sessionId' => $session->id,
        ]);
    }

    public function nextHand(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'exists:drill_sessions,id'],
        ]);

        $session = DrillSession::findOrFail($validated['sessionId']);
        $result = $this->randomizer->getNextHand($session);

        if (!$result) {
            return response()->json(['complete' => true]);
        }

        return response()->json($result);
    }

    public function answer(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'exists:drill_sessions,id'],
            'hand' => ['required', 'string'],
            'scenarioId' => ['required', 'exists:scenarios,id'],
            'action' => ['required', 'string', 'in:fold,call,raise'],
        ]);

        $session = DrillSession::findOrFail($validated['sessionId']);
        $result = $this->drillService->submitAnswer(
            $session,
            $validated['hand'],
            $validated['scenarioId'],
            $validated['action']
        );

        return response()->json($result);
    }

    public function end(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'exists:drill_sessions,id'],
        ]);

        $session = DrillSession::findOrFail($validated['sessionId']);
        $summary = $this->drillService->endSession($session);

        return response()->json($summary);
    }

    /**
     * Get drill suggestions for the user.
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();
        $suggestions = [];

        // 1. Declining: Compare last 10 attempts vs previous 10
        $declining = $this->getDecliningScenarios($user->id, 2);
        foreach ($declining as $item) {
            $suggestions[] = [
                'type' => 'declining',
                'scenario' => $this->formatScenarioWithGroups($item['scenario']),
                'accuracy' => $item['currentAccuracy'],
                'previousAccuracy' => $item['previousAccuracy'],
                'reason' => $item['previousAccuracy'] . '% → ' . $item['currentAccuracy'] . '% (last 100 hands)',
            ];
        }

        // 2. Low stats: Accuracy < 70% with 10+ attempts
        $lowStats = $this->getLowStatsScenarios($user->id, 2);
        foreach ($lowStats as $stat) {
            $suggestions[] = [
                'type' => 'low',
                'scenario' => $this->formatScenarioWithGroups($stat->scenario),
                'accuracy' => round(($stat->correct_attempts / $stat->total_attempts) * 100),
                'reason' => 'Needs practice',
            ];
        }

        // 3. New: Never attempted
        $new = $this->getNewScenarios($user->id, 1);
        foreach ($new as $scenario) {
            $suggestions[] = [
                'type' => 'new',
                'scenario' => $this->formatScenarioWithGroups($scenario),
                'reason' => 'Not tried yet',
            ];
        }

        return response()->json(array_slice($suggestions, 0, 5));
    }

    /**
     * Get scenarios where recent accuracy dropped vs previous.
     */
    private function getDecliningScenarios(int $userId, int $limit): array
    {
        $declining = [];

        // Get scenarios the user has practiced
        $scenarioIds = DrillAnswer::whereHas('session', fn($q) =>
            $q->where('user_id', $userId)
        )->distinct()->pluck('scenario_id');

        foreach ($scenarioIds as $scenarioId) {
            $answers = DrillAnswer::whereHas('session', fn($q) =>
                $q->where('user_id', $userId)
            )
                ->where('scenario_id', $scenarioId)
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get();

            if ($answers->count() < 100) continue;

            $recent = $answers->take(50);
            $previous = $answers->skip(50)->take(50);

            $recentAccuracy = $recent->count() > 0
                ? round(($recent->where('is_correct', true)->count() / $recent->count()) * 100)
                : 0;

            $previousAccuracy = $previous->count() > 0
                ? round(($previous->where('is_correct', true)->count() / $previous->count()) * 100)
                : 0;

            // If accuracy dropped by at least 10%
            if ($previousAccuracy - $recentAccuracy >= 10) {
                $declining[] = [
                    'scenario' => Scenario::find($scenarioId),
                    'currentAccuracy' => $recentAccuracy,
                    'previousAccuracy' => $previousAccuracy,
                    'drop' => $previousAccuracy - $recentAccuracy,
                ];
            }
        }

        // Sort by largest drop
        usort($declining, fn($a, $b) => $b['drop'] <=> $a['drop']);

        return array_slice($declining, 0, $limit);
    }

    /**
     * Get scenarios with low accuracy.
     */
    private function getLowStatsScenarios(int $userId, int $limit)
    {
        return UserScenarioStat::where('user_id', $userId)
            ->where('total_attempts', '>=', 10)
            ->whereRaw('(correct_attempts * 100.0 / total_attempts) < 70')
            ->with('scenario.groups')
            ->orderByRaw('correct_attempts * 1.0 / total_attempts ASC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get scenarios never attempted by user.
     */
    private function getNewScenarios(int $userId, int $limit)
    {
        $attemptedIds = UserScenarioStat::where('user_id', $userId)
            ->pluck('scenario_id');

        return Scenario::whereNotIn('id', $attemptedIds)
            ->where('created_by', $userId)
            ->with('groups')
            ->limit($limit)
            ->get();
    }

    /**
     * Format scenario with groups for response.
     */
    private function formatScenarioWithGroups(Scenario $scenario): array
    {
        return [
            'id' => $scenario->id,
            'name' => $scenario->name,
            'positions' => $scenario->positions,
            'stack_depth' => $scenario->stack_depth,
            'groups' => $scenario->groups->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
            ])->values(),
        ];
    }
}
