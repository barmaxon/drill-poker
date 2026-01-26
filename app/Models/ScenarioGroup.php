<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScenarioGroup extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scenarios(): BelongsToMany
    {
        return $this->belongsToMany(Scenario::class, 'group_scenarios', 'group_id', 'scenario_id');
    }
}
