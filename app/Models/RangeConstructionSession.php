<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangeConstructionSession extends Model
{
    protected $fillable = [
        'user_id',
        'scenario_id',
        'user_grid',
        'accuracy',
        'correct_cells',
        'incorrect_cells',
        'time_seconds',
    ];

    protected $casts = [
        'user_grid' => 'array',
        'accuracy' => 'decimal:2',
        'correct_cells' => 'integer',
        'incorrect_cells' => 'integer',
        'time_seconds' => 'integer',
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
