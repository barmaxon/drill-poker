<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandStrength extends Model
{
    protected $fillable = [
        'hand',
        'rank',
    ];

    public $timestamps = false;
}
