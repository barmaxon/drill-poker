<?php

namespace Database\Seeders;

use App\Models\HandStrength;
use Illuminate\Database\Seeder;

class HandStrengthSeeder extends Seeder
{
    public function run(): void
    {
        $handOrder = config('poker.hand_strength_order');

        foreach ($handOrder as $rank => $hand) {
            HandStrength::updateOrCreate(
                ['hand' => $hand],
                ['rank' => $rank + 1]
            );
        }
    }
}
