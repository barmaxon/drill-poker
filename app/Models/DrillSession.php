<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrillSession extends Model
{
    protected $fillable = [
        'user_id',
        'scenario_id',
        'config',
        'pre_drill_stats',
        'use_timer',
        'timer_seconds',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'config' => 'array',
        'pre_drill_stats' => 'array',
        'use_timer' => 'boolean',
        'timer_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DrillAnswer::class);
    }
}
