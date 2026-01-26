<?php

namespace Database\Seeders;

use App\Models\Scenario;
use App\Models\User;
use App\Services\BorderHandDetectorService;
use App\Services\SlugGeneratorService;
use Illuminate\Database\Seeder;

class OpenRaiseScenarioSeeder extends Seeder
{
    /**
     * All 169 hands in grid order.
     */
    private const HANDS = [
        // Row 1: AA row
        'AA', 'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
        // Row 2: KK row
        'AKo', 'KK', 'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s', 'K4s', 'K3s', 'K2s',
        // Row 3: QQ row
        'AQo', 'KQo', 'QQ', 'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s', 'Q6s', 'Q5s', 'Q4s', 'Q3s', 'Q2s',
        // Row 4: JJ row
        'AJo', 'KJo', 'QJo', 'JJ', 'JTs', 'J9s', 'J8s', 'J7s', 'J6s', 'J5s', 'J4s', 'J3s', 'J2s',
        // Row 5: TT row
        'ATo', 'KTo', 'QTo', 'JTo', 'TT', 'T9s', 'T8s', 'T7s', 'T6s', 'T5s', 'T4s', 'T3s', 'T2s',
        // Row 6: 99 row
        'A9o', 'K9o', 'Q9o', 'J9o', 'T9o', '99', '98s', '97s', '96s', '95s', '94s', '93s', '92s',
        // Row 7: 88 row
        'A8o', 'K8o', 'Q8o', 'J8o', 'T8o', '98o', '88', '87s', '86s', '85s', '84s', '83s', '82s',
        // Row 8: 77 row
        'A7o', 'K7o', 'Q7o', 'J7o', 'T7o', '97o', '87o', '77', '76s', '75s', '74s', '73s', '72s',
        // Row 9: 66 row
        'A6o', 'K6o', 'Q6o', 'J6o', 'T6o', '96o', '86o', '76o', '66', '65s', '64s', '63s', '62s',
        // Row 10: 55 row
        'A5o', 'K5o', 'Q5o', 'J5o', 'T5o', '95o', '85o', '75o', '65o', '55', '54s', '53s', '52s',
        // Row 11: 44 row
        'A4o', 'K4o', 'Q4o', 'J4o', 'T4o', '94o', '84o', '74o', '64o', '54o', '44', '43s', '42s',
        // Row 12: 33 row
        'A3o', 'K3o', 'Q3o', 'J3o', 'T3o', '93o', '83o', '73o', '63o', '53o', '43o', '33', '32s',
        // Row 13: 22 row
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
            'UTG+1' => $this->getUtgPlus1Range(),
            'UTG+2' => $this->getUtgPlus2Range(),
            'LJ' => $this->getLjRange(),
            'HJ' => $this->getHjRange(),
            'CO' => $this->getCoRange(),
            'BTN' => $this->getBtnRange(),
        ];

        $scenarioName = 'Open Raise';
        $stackDepth = 100;

        foreach ($positions as $position => $raiseHands) {
            $grid = $this->buildGrid($raiseHands);

            $slug = $slugGenerator->generateUnique($scenarioName, [$position], $stackDepth);

            $scenario = Scenario::create([
                'name' => $scenarioName,
                'positions' => [$position],
                'slug' => $slug,
                'stack_depth' => $stackDepth,
                'limpers' => 0,
                'grid' => $grid,
                'description' => "Standard {$position} open raise range at {$stackDepth}bb",
                'created_by' => $user->id,
            ]);

            $borderDetector->computeAndStore($scenario);

            $handCount = count($raiseHands);
            $percentage = round(($handCount / 169) * 100);
            $this->command->info("Created {$position} open raise scenario ({$percentage}% - {$handCount} hands)");
        }

        $this->command->info('Open raise scenarios created successfully!');
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
     * UTG+1 / EP+1 range (~16%)
     */
    private function getUtgPlus1Range(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s',
            // Suited Broadway
            'KQs', 'KJs', 'KTs', 'QJs', 'QTs', 'JTs',
            // Suited Connectors
            'T9s',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'KQo',
        ];
    }

    /**
     * UTG+2 / EP+2 range (~18%)
     */
    private function getUtgPlus2Range(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s',
            // Suited Broadway
            'KQs', 'KJs', 'KTs', 'K9s', 'QJs', 'QTs', 'JTs',
            // Suited Connectors
            'T9s', '98s',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'KQo',
        ];
    }

    /**
     * LJ / MP range (~22%)
     */
    private function getLjRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // Suited Broadway
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'QJs', 'QTs', 'Q9s', 'JTs', 'J9s',
            // Suited Connectors
            'T9s', '98s', '87s', '76s',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'KQo', 'KJo',
        ];
    }

    /**
     * HJ range (~27%)
     */
    private function getHjRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44', '33', '22',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // Suited Kings
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s',
            // Suited Queens
            'QJs', 'QTs', 'Q9s', 'Q8s',
            // Suited Jacks
            'JTs', 'J9s', 'J8s',
            // Suited Connectors
            'T9s', 'T8s', '98s', '97s', '87s', '86s', '76s', '75s', '65s', '54s',
            // Offsuit
            'AKo', 'AQo', 'AJo', 'ATo', 'KQo', 'KJo', 'QJo',
        ];
    }

    /**
     * CO range (~37%)
     */
    private function getCoRange(): array
    {
        return [
            // Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44', '33', '22',
            // Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // Suited Kings
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s', 'K4s',
            // Suited Queens
            'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s', 'Q6s',
            // Suited Jacks
            'JTs', 'J9s', 'J8s', 'J7s',
            // Suited Tens
            'T9s', 'T8s', 'T7s',
            // Suited Connectors
            '98s', '97s', '96s', '87s', '86s', '85s', '76s', '75s', '65s', '64s', '54s', '53s',
            // Offsuit Aces
            'AKo', 'AQo', 'AJo', 'ATo', 'A9o', 'A8o', 'A7o',
            // Offsuit Broadway
            'KQo', 'KJo', 'KTo', 'QJo', 'QTo', 'JTo',
        ];
    }

    /**
     * BTN / BU range (~54%)
     */
    private function getBtnRange(): array
    {
        return [
            // All Pairs
            'AA', 'KK', 'QQ', 'JJ', 'TT', '99', '88', '77', '66', '55', '44', '33', '22',
            // All Suited Aces
            'AKs', 'AQs', 'AJs', 'ATs', 'A9s', 'A8s', 'A7s', 'A6s', 'A5s', 'A4s', 'A3s', 'A2s',
            // All Suited Kings
            'KQs', 'KJs', 'KTs', 'K9s', 'K8s', 'K7s', 'K6s', 'K5s', 'K4s', 'K3s', 'K2s',
            // Suited Queens
            'QJs', 'QTs', 'Q9s', 'Q8s', 'Q7s', 'Q6s', 'Q5s', 'Q4s', 'Q3s',
            // Suited Jacks
            'JTs', 'J9s', 'J8s', 'J7s', 'J6s', 'J5s', 'J4s',
            // Suited Tens
            'T9s', 'T8s', 'T7s', 'T6s', 'T5s', 'T4s',
            // Suited Nines
            '98s', '97s', '96s', '95s',
            // Suited Eights
            '87s', '86s', '85s',
            // Suited Sevens
            '76s', '75s', '74s',
            // Suited Low
            '65s', '64s', '54s', '53s', '43s',
            // Offsuit Aces
            'AKo', 'AQo', 'AJo', 'ATo', 'A9o', 'A8o', 'A7o', 'A6o', 'A5o', 'A4o', 'A3o', 'A2o',
            // Offsuit Kings
            'KQo', 'KJo', 'KTo', 'K9o', 'K8o', 'K7o',
            // Offsuit Queens
            'QJo', 'QTo', 'Q9o', 'Q8o',
            // Offsuit Jacks
            'JTo', 'J9o', 'J8o',
            // Offsuit Tens
            'T9o', 'T8o',
            // Offsuit Nines
            '98o',
        ];
    }
}
