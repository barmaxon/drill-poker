<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHandStat extends Model
{
    protected $fillable = [
        'user_id',
        'scenario_id',
        'hand',
        'total_attempts',
        'correct_attempts',
        'normal_mistakes',
        'border_mistakes',
        'current_weight',
        'last_shown_at',
    ];

    protected $casts = [
        'total_attempts' => 'integer',
        'correct_attempts' => 'integer',
        'normal_mistakes' => 'integer',
        'border_mistakes' => 'integer',
        'current_weight' => 'float',
        'last_shown_at' => 'datetime',
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
