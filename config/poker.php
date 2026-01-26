<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hand Strength Order
    |--------------------------------------------------------------------------
    |
    | Complete ranking of all 169 starting hands from strongest (1) to weakest (169).
    | Used for the range slider feature and seeding the hand_strengths table.
    |
    */
    'hand_strength_order' => [
        'AA', 'KK', 'QQ', 'AKs', 'JJ', 'AQs', 'KQs', 'AJs', 'KJs', 'TT',
        'AKo', 'ATs', 'QJs', 'KTs', 'QTs', 'JTs', '99', 'AQo', 'A9s', 'KQo',
        '88', 'K9s', 'T9s', 'A8s', 'Q9s', 'J9s', 'AJo', 'A5s', '77', 'A7s',
        'KJo', 'A4s', 'A3s', 'A6s', 'QJo', '66', 'K8s', 'T8s', 'A2s', '98s',
        'J8s', 'ATo', 'Q8s', 'K7s', 'KTo', '55', 'JTo', '87s', 'QTo', '44',
        '22', '33', 'K6s', '97s', 'K5s', '76s', 'T7s', 'K4s', 'K3s', 'K2s',
        'Q7s', '86s', '65s', 'J7s', '54s', 'Q6s', '75s', '96s', 'Q5s', '64s',
        'Q4s', 'Q3s', 'T6s', '53s', 'Q2s', 'J6s', '85s', 'J5s', 'J4s', 'J3s',
        '95s', 'J2s', '63s', 'T5s', '52s', 'T4s', 'T3s', '43s', 'T2s', '74s',
        '94s', '84s', '42s', '93s', '92s', '83s', '73s', '82s', '72s', '62s',
        // Off-suit hands lower half
        'A9o', 'K9o', 'Q9o', 'J9o', 'T9o', 'A8o', 'K8o', 'Q8o', 'J8o', 'T8o',
        '98o', 'A7o', 'K7o', 'Q7o', 'J7o', 'T7o', '97o', '87o', 'A6o', 'K6o',
        'Q6o', 'J6o', 'T6o', '96o', '86o', '76o', 'A5o', 'K5o', 'Q5o', 'J5o',
        'T5o', '95o', '85o', '75o', '65o', 'A4o', 'K4o', 'Q4o', 'J4o', 'T4o',
        '94o', '84o', '74o', '64o', '54o', 'A3o', 'K3o', 'Q3o', 'J3o', 'T3o',
        '93o', '83o', '73o', '63o', '53o', '43o', 'A2o', 'K2o', 'Q2o', 'J2o',
        'T2o', '92o', '82o', '72o', '62o', '52o', '42o', '32o',
    ],

    /*
    |--------------------------------------------------------------------------
    | Positions
    |--------------------------------------------------------------------------
    */
    'positions' => ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'actions' => ['fold', 'call', 'raise'],

    /*
    |--------------------------------------------------------------------------
    | Weight Configuration
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'min' => 0.1,
        'max' => 10.0,
        'default' => 1.0,
        'correct_adjustment' => -0.3,
        'wrong_normal_adjustment' => 0.5,
        'wrong_border_adjustment' => 0.8,
        'border_multiplier' => 2.0,
        'error_rate_multiplier' => 3.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Distance-Based Multipliers
    |--------------------------------------------------------------------------
    |
    | Multipliers based on distance from the border (action boundary).
    | Distance 0 = on the border (adjacent to different action)
    | Distance 1 = one cell away from border
    | Distance 2+ = further inside the range
    |
    */
    'distance_multipliers' => [
        0 => 3.0,       // Border hands - highest priority
        1 => 2.0,       // One step from border
        2 => 1.5,       // Two steps from border
        3 => 1.2,       // Three steps from border
        'default' => 1.0,  // Further away or no border info
    ],
];
