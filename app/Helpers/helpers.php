<?php

use App\Helpers\GradeHelper;

if (!function_exists('format_score')) {
    /**
     * Format a score, removing .00 for whole numbers but keeping decimals for non-whole numbers
     * 
     * @param float|int|null $value
     * @param int $decimals Number of decimal places to show for non-whole numbers
     * @return string
     */
    function format_score($value, $decimals = 2)
    {
        return GradeHelper::formatScore($value, $decimals);
    }
}
