<?php

namespace App\Helpers;

class GradeHelper
{
    /**
     * Format a number, removing .00 for whole numbers but keeping decimals for non-whole numbers
     * 
     * @param float|int|null $value
     * @param int $decimals Number of decimal places to show for non-whole numbers
     * @return string
     */
    public static function formatScore($value, $decimals = 2)
    {
        if ($value === null || $value === '') {
            return '-';
        }
        
        // Convert to float
        $value = (float) $value;
        
        // Check if it's a whole number
        if (floor($value) == $value) {
            return (string) (int) $value;
        }
        
        // Has decimals, format with specified decimal places and remove trailing zeros
        return rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.');
    }
}
