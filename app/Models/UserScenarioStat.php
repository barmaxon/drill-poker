<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScenarioStat extends Model
{
    protected $fillable = [
        'user_id',
        'scenario_id',
        'total_attempts',
        'correct_attempts',
    ];

    protected $casts = [
        'total_attempts' => 'integer',
        'correct_attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }
}
