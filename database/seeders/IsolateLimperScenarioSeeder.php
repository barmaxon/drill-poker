<?php

namespace Database\Seeders;

use App\Models\Scenario;
use App\Models\User;
use App\Services\BorderHandDetectorService;
use App\Services\SlugGeneratorService;
use Illuminate\Database\Seeder;

class IsolateLimperScenarioSeeder extends Seeder
{
    /**
     * All 169 hands in grid order.
     */
    private const HANDS = [
        'AA', 'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
        'AKo', 'KK', 'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s', 'K4s', 'K3s', 'K2s',
        'AQo', 'KQo', 'QQ', 'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s', 'Q6s', 'Q5s', 'Q4s', 'Q3s', 'Q2s',
        'AJo', 'KJo', 'QJo', 'JJ', 'JTs', 'J9s', 'J8s', 'J7s', 'J6s', 'J5s', 'J4s', 'J3s', 'J2s',
        'ATo', 'KTo', 'QTo', 'JTo', 'TT', 'T9s', 'T8s', 'T7s', 'T6s', 'T5s', 'T4s', 'T3s', 'T2s',
        'A9o', 'K9o', 'Q9o', 'J9o', 'T9o', '99', '98s', '97s', '96s', '95s', '94s', '93s', '92s',
        'A8o', 'K8o', 'Q8o', 'J8o', 'T8o', '98o', '88', '87s', '86s', '85s', '84s', '83s', '82s',
        'A7o', 'K7o', 'Q7o', 'J7o', 'T7o', '97o', '87o', '77', '76s', '75s', '74s', '73s', '72s',
        'A6o', 'K6o', 'Q6o', 'J6o', 'T6o', '96o', '86o', '76o', '66', '65s', '64s', '63s', '62s',
        'A5o', 'K5o', 'Q5o', 'J5o', 'T5o', '95o', '85o', '75o', '65o', '55', '54s', '53s', '52s',
        'A4o', 'K4o', 'Q4o', 'J4o', 'T4o', '94o', '84o', '74o', '64o', '54o', '44', '43s', '42s',
        'A3o', 'K3o', 'Q3o', 'J3o', 'T3o', '93o', '83o', '73o', '63o', '53o', '43o', '33', '32s',
        'A2o', 'K2o', 'Q2o', 'J2o', 'T2o', '92o', '82o', '72o', '62o', '52o', '42o', '32o', '22',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('No user found. Please create a user first.');
            return;
        }

        $slugGenerator = app(SlugGeneratorService::class);
        $borderDetector = app(BorderHandDetectorService::class);

        $positions = [
            'CO' => $this->getCoRange(),
            'BTN' => $this->getBtnRange(),
            'SB' => $this->getSbRange(),
            'BB' => $this->getBbRange(),
        ];

        $scenarioName = 'Isolate 1 Limper';
        $stackDepth = 100;

        foreach ($positions as $position => $raiseHands) {
            $grid = $this->buildGrid($raiseHands);

            $slug = $slugGenerator->generateUnique($scenarioName, [$position], $stackDepth);

            $scenario = Scenario::create([
                'name' => $scenarioName,
                'positions' => [$position],
                'slug' => $slug,
                'stack_depth' => $stackDepth,
                'limpers' => 1,
                'grid' => $grid,
                'description' => "{$position} isolate range vs 1 limper at {$stackDepth}bb",
                'created_by' => $user->id,
            ]);

            $borderDetector->computeAndStore($scenario);

            $handCount = count($raiseHands);
            $percentage = round(($handCount / 169) * 100, 1);
            $this->command->info("Created {$position} isolate 1 limper scenario ({$percentage}% - {$handCount} hands)");
        }

        $this->command->info('Isolate 1 limper scenarios created successfully!');
    }

    /**
     * Build a grid from a list of raise hands.
     */
    private function buildGrid(array $raiseHands): array
    {
        $grid = [];
        foreach (self::HANDS as $hand) {
            $grid[$hand] = in_array($hand, $raiseHands) ? 'raise' : 'fold';
        }
        return $grid;
    }

    /**
     * CO vs 1 limper range (~27.5%)
     */
    private function getCoRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44', '33', '22',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // Suited Kings
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s',
            // Suited Queens
            'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s',
            // Suited Jacks
            'JTs', 'J9s', 'J8s', 'J7s',
            // Suited Tens
            'T9s', 'T8s', 'T7s',
            // Suited Connectors
            '98s', '97s', '87s',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'A9o', 'A8o',
            'KQo', 'KJo', 'KTo', 'K9o',
            'QJo', 'QTo', 'Q9o',
            'JTo', 'J9o',
            'T9o',
        ];
    }

    /**
     * BTN vs 1 limper range (~37.5%)
     */
    private function getBtnRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44', '33', '22',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // Suited Kings
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s', 'K4s', 'K3s',
            // Suited Queens
            'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s', 'Q6s', 'Q5s',
            // Suited Jacks
            'JTs', 'J9s', 'J8s', 'J7s', 'J6s',
            // Suited Tens
            'T9s', 'T8s', 'T7s', 'T6s',
            // Suited Connectors
            '98s', '97s', '96s', '87s', '86s', '76s', '65s',
            // Offsuit Aces
            'AKo', 'AQo', 'AJo', 'ATo', 'A9o', 'A8o', 'A7o', 'A6o', 'A5o', 'A4o',
            // Offsuit Kings
            'KQo', 'KJo', 'KTo', 'K9o',
            // Offsuit Queens
            'QJo', 'QTo', 'Q9o',
            // Offsuit Jacks
            'JTo', 'J9o',
            // Offsuit Tens
            'T9o',
        ];
    }

    /**
     * SB vs 1 limper range (~12%)
     */
    private function getSbRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s',
            // Suited Kings
            'KQs', 'KJs', 'KTs',
            // Suited Queens
            'QJs', 'QTs',
            // Suited Jacks
            'JTs',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo',
            'KQo', 'KJo',
        ];
    }

    /**
     * BB vs 1 limper range (~14.5%)
     */
    private function getBbRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s',
            // Suited Kings
            'KQs', 'KJs', 'KTs',
            // Suited Queens
            'QJs', 'QTs',
            // Suited Jacks
            'JTs',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'A9o',
            'KQo', 'KJo',
            'QJo',
        ];
    }
}
