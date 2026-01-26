<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RangeConstructionSession;
use App\Models\Scenario;
use App\Models\UserRangeConstructionStat;
use Illuminate\Http\Request;

class RangeConstructionController extends Controller
{
    /**
     * Start a range construction drill session.
     * Returns scenario info without the grid.
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'scenarioId' => ['required', 'exists:scenarios,id'],
        ]);

        $scenario = Scenario::where('id', $validated['scenarioId'])
            ->where('created_by', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'scenario' => [
                'id' => $scenario->id,
                'name' => $scenario->name,
                'positions' => $scenario->positions,
                'stackDepth' => $scenario->stack_depth,
                'limpers' => $scenario->limpers,
                'description' => $scenario->description,
            ],
        ]);
    }

    /**
     * Submit a completed range and get results.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'scenarioId' => ['required', 'exists:scenarios,id'],
            'userGrid' => ['required', 'array'],
            'timeSeconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $scenario = Scenario::where('id', $validated['scenarioId'])
            ->where('created_by', $user->id)
            ->firstOrFail();

        $correctGrid = $scenario->grid;
        $userGrid = $validated['userGrid'];

        // Compare grids
        $allHands = config('poker.hand_strength_order');
        $correctCount = 0;
        $incorrectCount = 0;
        $differences = [];

        foreach ($allHands as $hand) {
            $userAction = $userGrid[$hand] ?? 'fold';
            $correctAction = $correctGrid[$hand] ?? 'fold';

            if ($userAction === $correctAction) {
                $correctCount++;
            } else {
                $incorrectCount++;
                $differences[] = [
                    'hand' => $hand,
                    'userAction' => $userAction,
                    'correctAction' => $correctAction,
                ];
            }
        }

        $totalCells = count($allHands);
        $accuracy = $totalCells > 0 ? round(($correctCount / $totalCells) * 100, 2) : 0;

        // Save the session
        $session = RangeConstructionSession::create([
            'user_id' => $user->id,
            'scenario_id' => $scenario->id,
            'user_grid' => $userGrid,
            'accuracy' => $accuracy,
            'correct_cells' => $correctCount,
            'incorrect_cells' => $incorrectCount,
            'time_seconds' => $validated['timeSeconds'] ?? null,
        ]);

        // Update user stats
        $stat = UserRangeConstructionStat::firstOrCreate(
            ['user_id' => $user->id, 'scenario_id' => $scenario->id],
            ['total_attempts' => 0, 'best_accuracy' => 0, 'avg_accuracy' => 0]
        );

        $newTotal = $stat->total_attempts + 1;
        $newAvg = (($stat->avg_accuracy * $stat->total_attempts) + $accuracy) / $newTotal;

        $stat->update([
            'total_attempts' => $newTotal,
            'best_accuracy' => max($stat->best_accuracy, $accuracy),
            'avg_accuracy' => round($newAvg, 2),
        ]);

        return response()->json([
            'sessionId' => $session->id,
            'accuracy' => $accuracy,
            'correctCount' => $correctCount,
            'incorrectCount' => $incorrectCount,
            'totalCells' => $totalCells,
            'differences' => $differences,
            'correctGrid' => $correctGrid,
            'stats' => [
                'totalAttempts' => $stat->total_attempts,
                'bestAccuracy' => $stat->best_accuracy,
                'avgAccuracy' => $stat->avg_accuracy,
            ],
        ]);
    }

    /**
     * Get user's range construction stats.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = UserRangeConstructionStat::where('user_id', $user->id)
            ->with('scenario:id,name,positions,stack_depth')
            ->get()
            ->map(fn($s) => [
                'scenario' => [
                    'id' => $s->scenario->id,
                    'name' => $s->scenario->name,
                    'positions' => $s->scenario->positions,
                    'stackDepth' => $s->scenario->stack_depth,
                ],
                'totalAttempts' => $s->total_attempts,
                'bestAccuracy' => $s->best_accuracy,
                'avgAccuracy' => $s->avg_accuracy,
            ]);

        // Overall stats
        $totalAttempts = $stats->sum('totalAttempts');
        $avgAccuracy = $stats->count() > 0
            ? round($stats->avg('avgAccuracy'), 2)
            : 0;

        return response()->json([
            'overall' => [
                'totalAttempts' => $totalAttempts,
                'avgAccuracy' => $avgAccuracy,
                'scenariosAttempted' => $stats->count(),
            ],
            'scenarios' => $stats,
        ]);
    }
}
