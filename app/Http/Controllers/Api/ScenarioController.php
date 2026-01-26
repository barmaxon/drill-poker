<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use App\Rules\UniqueScenarioRule;
use App\Services\BorderHandDetectorService;
use App\Services\ScenarioNameService;
use App\Services\SlugGeneratorService;
use Illuminate\Http\Request;

class ScenarioController extends Controller
{
    public function __construct(
        private BorderHandDetectorService $borderDetector,
        private SlugGeneratorService $slugGenerator,
        private ScenarioNameService $nameService
    ) {}

    public function index(Request $request)
    {
        $scenarios = Scenario::where('created_by', $request->user()->id)->get();
        return response()->json($scenarios);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'positions' => ['required', 'array', 'min:1', new UniqueScenarioRule($userId)],
            'positions.*' => ['string', 'in:UTG,UTG+1,UTG+2,LJ,HJ,CO,BTN,SB,BB'],
            'stackDepth' => ['required', 'integer', 'min:1', 'max:200'],
            'limpers' => ['nullable', 'integer', 'min:0', 'max:5'],
            'grid' => ['required', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        // Use canonical name case from existing scenarios
        $canonicalName = $this->nameService->getCanonicalName($validated['name'], $userId);

        // Generate unique slug
        $slug = $this->slugGenerator->generateUnique(
            $canonicalName,
            $validated['positions'],
            $validated['stackDepth']
        );

        $scenario = Scenario::create([
            'name' => $canonicalName,
            'positions' => $validated['positions'],
            'slug' => $slug,
            'stack_depth' => $validated['stackDepth'],
            'limpers' => $validated['limpers'] ?? 0,
            'grid' => $validated['grid'],
            'description' => $validated['description'] ?? null,
            'created_by' => $userId,
        ]);

        // Compute and store border hands
        $this->borderDetector->computeAndStore($scenario);

        return response()->json($scenario, 201);
    }

    public function show(Scenario $scenario)
    {
        $this->authorizeScenario($scenario);
        return response()->json($scenario);
    }

    public function update(Request $request, Scenario $scenario)
    {
        $this->authorizeScenario($scenario);
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'positions' => ['sometimes', 'array', 'min:1', new UniqueScenarioRule($userId, $scenario->id)],
            'positions.*' => ['string', 'in:UTG,UTG+1,UTG+2,LJ,HJ,CO,BTN,SB,BB'],
            'stackDepth' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'limpers' => ['nullable', 'integer', 'min:0', 'max:5'],
            'grid' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $updateData = [];

        // Handle name with canonical case
        if (isset($validated['name'])) {
            $updateData['name'] = $this->nameService->getCanonicalName($validated['name'], $userId);
        }

        if (isset($validated['positions'])) {
            $updateData['positions'] = $validated['positions'];
        }

        if (isset($validated['stackDepth'])) {
            $updateData['stack_depth'] = $validated['stackDepth'];
        }

        if (array_key_exists('limpers', $validated)) {
            $updateData['limpers'] = $validated['limpers'];
        }

        if (isset($validated['grid'])) {
            $updateData['grid'] = $validated['grid'];
        }

        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }

        // Regenerate slug if name, positions, or stack depth changed
        if (isset($updateData['name']) || isset($updateData['positions']) || isset($updateData['stack_depth'])) {
            $name = $updateData['name'] ?? $scenario->name;
            $positions = $updateData['positions'] ?? $scenario->positions;
            $stackDepth = $updateData['stack_depth'] ?? $scenario->stack_depth;

            $updateData['slug'] = $this->slugGenerator->generateUnique(
                $name,
                $positions,
                $stackDepth,
                $scenario->id
            );
        }

        $scenario->update($updateData);

        // Recompute border hands if grid changed
        if (isset($validated['grid'])) {
            $this->borderDetector->computeAndStore($scenario);
        }

        return response()->json($scenario);
    }

    public function destroy(Scenario $scenario)
    {
        $this->authorizeScenario($scenario);
        $scenario->delete();
        return response()->json(null, 204);
    }

    public function borderHands(Scenario $scenario)
    {
        $this->authorizeScenario($scenario);
        return response()->json($scenario->borderHands->pluck('hand'));
    }

    /**
     * Get name suggestions for autocomplete.
     */
    public function nameSuggestions(Request $request)
    {
        $userId = $request->user()->id;
        $suggestions = $this->nameService->getAvailableNames($userId);

        return response()->json($suggestions);
    }

    private function authorizeScenario(Scenario $scenario): void
    {
        if ($scenario->created_by !== request()->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
