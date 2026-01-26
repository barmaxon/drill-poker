<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioBorderHand extends Model
{
    protected $fillable = [
        'scenario_id',
        'hand',
        'border_distance',
    ];

    protected $casts = [
        'border_distance' => 'integer',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }
}
