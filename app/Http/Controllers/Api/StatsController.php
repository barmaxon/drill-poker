<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use App\Models\ScenarioGroup;
use App\Models\UserHandStat;
use App\Models\UserScenarioStat;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();

        $stats = UserScenarioStat::where('user_id', $user->id)->get();

        $totalHands = $stats->sum('total_attempts');
        $correctHands = $stats->sum('correct_attempts');
        $accuracy = $totalHands > 0 ? round(($correctHands / $totalHands) * 100) : 0;

        $handStats = UserHandStat::where('user_id', $user->id)->get();
        $totalMistakes = $totalHands - $correctHands;
        $borderMistakes = $handStats->sum('border_mistakes');
        $sessions = $user->drillSessions()->count();

        return response()->json([
            'totalHands' => $totalHands,
            'accuracy' => $accuracy,
            'totalMistakes' => $totalMistakes,
            'borderMistakes' => $borderMistakes,
            'sessions' => $sessions,
        ]);
    }

    public function scenarioStats(Request $request)
    {
        $user = $request->user();

        $scenarios = Scenario::where('created_by', $user->id)->get();

        $stats = $scenarios->map(function ($scenario) use ($user) {
            $scenarioStat = UserScenarioStat::where('user_id', $user->id)
                ->where('scenario_id', $scenario->id)
                ->first();

            $handStats = UserHandStat::where('user_id', $user->id)
                ->where('scenario_id', $scenario->id)
                ->get();

            $totalHands = $scenarioStat?->total_attempts ?? 0;
            $correct = $scenarioStat?->correct_attempts ?? 0;
            $totalMistakes = $totalHands - $correct;
            $borderMistakes = $handStats->sum('border_mistakes');

            return [
                'id' => $scenario->id,
                'name' => $scenario->name,
                'positions' => $scenario->positions,
                'stackDepth' => $scenario->stack_depth,
                'totalHands' => $totalHands,
                'accuracy' => $totalHands > 0 ? round(($correct / $totalHands) * 100) : 0,
                'totalMistakes' => $totalMistakes,
                'borderMistakes' => $borderMistakes,
            ];
        });

        return response()->json($stats);
    }

    public function scenarioDetail(Request $request, Scenario $scenario)
    {
        $user = $request->user();

        // Authorization
        if ($scenario->created_by !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Summary
        $scenarioStat = UserScenarioStat::where('user_id', $user->id)
            ->where('scenario_id', $scenario->id)
            ->first();

        $handStats = UserHandStat::where('user_id', $user->id)
            ->where('scenario_id', $scenario->id)
            ->get()
            ->keyBy('hand');

        $totalHands = $scenarioStat?->total_attempts ?? 0;
        $correct = $scenarioStat?->correct_attempts ?? 0;
        $totalMistakes = $totalHands - $correct;
        $borderMistakes = $handStats->sum('border_mistakes');

        // Heatmap data
        $heatmap = [];
        foreach ($handStats as $hand => $stat) {
            $accuracy = $stat->total_attempts > 0
                ? round(($stat->correct_attempts / $stat->total_attempts) * 100)
                : 0;
            $heatmap[$hand] = [
                'total' => $stat->total_attempts,
                'correct' => $stat->correct_attempts,
                'accuracy' => $accuracy,
            ];
        }

        // Problem hands (must have at least 1 mistake)
        $problemHands = $handStats
            ->filter(fn($stat) => $stat->total_attempts >= 3 && $stat->correct_attempts < $stat->total_attempts)
            ->map(function ($stat) {
                $accuracy = $stat->total_attempts > 0
                    ? round(($stat->correct_attempts / $stat->total_attempts) * 100)
                    : 0;
                return [
                    'hand' => $stat->hand,
                    'accuracy' => $accuracy,
                    'mistakes' => $stat->total_attempts - $stat->correct_attempts,
                    'total' => $stat->total_attempts,
                ];
            })
            ->sortBy('accuracy')
            ->take(10)
            ->values();

        return response()->json([
            'summary' => [
                'totalHands' => $totalHands,
                'accuracy' => $totalHands > 0 ? round(($correct / $totalHands) * 100) : 0,
                'totalMistakes' => $totalMistakes,
                'borderMistakes' => $borderMistakes,
            ],
            'heatmap' => $heatmap,
            'problemHands' => $problemHands,
        ]);
    }

    public function groupStats(Request $request)
    {
        $user = $request->user();

        // Get groups that contain user's scenarios
        $userScenarioIds = Scenario::where('created_by', $user->id)->pluck('id');
        $groups = ScenarioGroup::with(['scenarios' => function ($query) use ($userScenarioIds) {
            $query->whereIn('scenarios.id', $userScenarioIds);
        }])
        ->whereHas('scenarios', function ($query) use ($userScenarioIds) {
            $query->whereIn('scenarios.id', $userScenarioIds);
        })
        ->get();

        $stats = $groups->map(function ($group) use ($user) {
            $scenarioIds = $group->scenarios->pluck('id');

            $scenarioStats = UserScenarioStat::where('user_id', $user->id)
                ->whereIn('scenario_id', $scenarioIds)
                ->get();

            $handStats = UserHandStat::where('user_id', $user->id)
                ->whereIn('scenario_id', $scenarioIds)
                ->get();

            $totalHands = $scenarioStats->sum('total_attempts');
            $correct = $scenarioStats->sum('correct_attempts');
            $totalMistakes = $totalHands - $correct;
            $borderMistakes = $handStats->sum('border_mistakes');

            $scenarios = $group->scenarios->map(function ($scenario) use ($user) {
                $stat = UserScenarioStat::where('user_id', $user->id)
                    ->where('scenario_id', $scenario->id)
                    ->first();

                $total = $stat?->total_attempts ?? 0;
                $corr = $stat?->correct_attempts ?? 0;

                return [
                    'id' => $scenario->id,
                    'name' => $scenario->name,
                    'positions' => $scenario->positions,
                    'stackDepth' => $scenario->stack_depth,
                    'accuracy' => $total > 0 ? round(($corr / $total) * 100) : 0,
                ];
            });

            return [
                'id' => $group->id,
                'name' => $group->name,
                'scenarioCount' => $group->scenarios->count(),
                'totalHands' => $totalHands,
                'accuracy' => $totalHands > 0 ? round(($correct / $totalHands) * 100) : 0,
                'totalMistakes' => $totalMistakes,
                'borderMistakes' => $borderMistakes,
                'scenarios' => $scenarios,
            ];
        });

        return response()->json($stats);
    }

    public function problemHands(Request $request)
    {
        $user = $request->user();

        $hands = UserHandStat::where('user_id', $user->id)
            ->where('total_attempts', '>=', 5)
            ->whereColumn('correct_attempts', '<', 'total_attempts')  // Must have at least 1 mistake
            ->get()
            ->map(function ($stat) {
                $accuracy = $stat->total_attempts > 0
                    ? round(($stat->correct_attempts / $stat->total_attempts) * 100)
                    : 0;
                return [
                    'hand' => $stat->hand,
                    'accuracy' => $accuracy,
                    'mistakes' => $stat->total_attempts - $stat->correct_attempts,
                    'total' => $stat->total_attempts,
                ];
            })
            ->sortBy('accuracy')
            ->take(10)
            ->values();

        return response()->json($hands);
    }
}
