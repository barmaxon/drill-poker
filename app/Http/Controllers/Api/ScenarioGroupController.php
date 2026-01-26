<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScenarioGroup;
use Illuminate\Http\Request;

class ScenarioGroupController extends Controller
{
    public function index()
    {
        $groups = ScenarioGroup::with('scenarios')->get();
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $group = ScenarioGroup::create([
            'name' => $validated['name'],
            'is_active' => $validated['isActive'] ?? true,
        ]);

        return response()->json($group, 201);
    }

    public function show(ScenarioGroup $group)
    {
        return response()->json($group->load('scenarios'));
    }

    public function update(Request $request, ScenarioGroup $group)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['isActive'])) {
            $updateData['is_active'] = $validated['isActive'];
        }

        $group->update($updateData);
        return response()->json($group->load('scenarios'));
    }

    public function destroy(ScenarioGroup $group)
    {
        $group->delete();
        return response()->json(null, 204);
    }

    public function syncScenarios(Request $request, ScenarioGroup $group)
    {
        $validated = $request->validate([
            'scenarioIds' => ['required', 'array'],
            'scenarioIds.*' => ['exists:scenarios,id'],
        ]);

        $group->scenarios()->sync($validated['scenarioIds']);
        return response()->json($group->load('scenarios'));
    }
}
