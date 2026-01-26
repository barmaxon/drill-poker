<?php

namespace App\Rules;

use App\Models\Scenario;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueScenarioRule implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     */
    protected array $data = [];

    /**
     * The scenario ID to exclude (for updates).
     */
    protected ?int $excludeId;

    /**
     * The user ID to check against.
     */
    protected int $userId;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $userId, ?int $excludeId = null)
    {
        $this->userId = $userId;
        $this->excludeId = $excludeId;
    }

    /**
     * Set the data under validation.
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * Two scenarios conflict if ALL of these match:
     * - Same name (case-insensitive)
     * - Same stack_depth
     * - ANY position overlap in positions arrays
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $positions = $value;
        $name = $this->data['name'] ?? null;
        $stackDepth = $this->data['stackDepth'] ?? $this->data['stack_depth'] ?? null;

        if (!$name || !$stackDepth || empty($positions)) {
            return; // Let other validation rules handle missing fields
        }

        // Find scenarios with same name and stack depth
        $query = Scenario::where('created_by', $this->userId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('stack_depth', $stackDepth);

        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        $existingScenarios = $query->get();

        // Check for position overlap
        foreach ($existingScenarios as $existing) {
            $existingPositions = $existing->positions;
            $overlap = array_intersect($positions, $existingPositions);

            if (!empty($overlap)) {
                $overlapStr = implode(', ', $overlap);
                $fail("A scenario with name \"{$existing->name}\" at {$stackDepth}bb already includes position(s): {$overlapStr}");
                return;
            }
        }
    }
}
