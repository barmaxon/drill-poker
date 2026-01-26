<?php

namespace App\Services;

class MistakeCategorizerService
{
    public function __construct(
        private BorderHandDetectorService $borderDetector
    ) {}

    public function categorize(string $hand, array $grid): string
    {
        $borderHands = $this->borderDetector->detect($grid);
        return in_array($hand, $borderHands) ? 'border' : 'normal';
    }
}
