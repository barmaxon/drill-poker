<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    protected $fillable = [
        'name',
        'positions',
        'slug',
        'stack_depth',
        'limpers',
        'grid',
        'description',
        'created_by',
    ];

    protected $casts = [
        'positions' => 'array',
        'stack_depth' => 'integer',
        'limpers' => 'integer',
        'grid' => 'array',
    ];

    /**
     * Get position group name for display purposes.
     */
    public function getPositionGroupName(): string
    {
        $positions = $this->positions;
        sort($positions);

        $groups = [
            'early' => ['UTG', 'UTG+1'],
            'middle' => ['UTG+2', 'LJ'],
            'late' => ['HJ', 'CO', 'BTN'],
            'blinds' => ['SB', 'BB'],
        ];

        foreach ($groups as $groupName => $groupPositions) {
            sort($groupPositions);
            if ($positions === $groupPositions) {
                return ucfirst($groupName);
            }
        }

        return implode(', ', $positions);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ScenarioGroup::class, 'group_scenarios', 'scenario_id', 'group_id');
    }

    public function borderHands(): HasMany
    {
        return $this->hasMany(ScenarioBorderHand::class);
    }

    public function drillSessions(): HasMany
    {
        return $this->hasMany(DrillSession::class);
    }
}
