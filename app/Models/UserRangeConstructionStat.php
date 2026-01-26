<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRangeConstructionStat extends Model
{
    protected $fillable = [
        'user_id',
        'scenario_id',
        'total_attempts',
        'best_accuracy',
        'avg_accuracy',
    ];

    protected $casts = [
        'total_attempts' => 'integer',
        'best_accuracy' => 'decimal:2',
        'avg_accuracy' => 'decimal:2',
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
