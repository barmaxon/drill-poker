<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrillAnswer extends Model
{
    protected $fillable = [
        'drill_session_id',
        'scenario_id',
        'hand',
        'user_action',
        'correct_action',
        'is_correct',
        'mistake_type',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DrillSession::class, 'drill_session_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }
}
