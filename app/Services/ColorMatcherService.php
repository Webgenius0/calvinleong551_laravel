<?php

namespace App\Services;

class ColorMatcherService
{
    /**
     * Generates matching colors for season.
     * 
     * @param array $colors Base colors
     * @param string $season Season name
     * @return array Matched colors
     */
    public function match(array $colors, string $season): array
    {
        $complements = [
            'spring' => ['#98FB98', '#FFB6C1'],
            'summer' => ['#FFD700', '#87CEEB'],
            'autumn' => ['#D2691E', '#CD853F'],
            'winter' => ['#E0FFFF', '#B0E0E6'],
        ];

        return array_unique(array_merge($colors, $complements[$season] ?? ['#ffffff']));
    }
}