<?php
/**
 * Grading sheet helper functions shared across professor pages.
 */

if (!function_exists('convertRawGradeToEquivalent')) {
    /**
     * Convert a raw percentage (0-100) into the campus grading equivalent.
     */
    function convertRawGradeToEquivalent(float $grade): string
    {
        if ($grade >= 97) {
            return '1.00';
        }
        if ($grade >= 94) {
            return '1.25';
        }
        if ($grade >= 91) {
            return '1.50';
        }
        if ($grade >= 88) {
            return '1.75';
        }
        if ($grade >= 85) {
            return '2.00';
        }
        if ($grade >= 82) {
            return '2.25';
        }
        if ($grade >= 79) {
            return '2.50';
        }
        if ($grade >= 76) {
            return '2.75';
        }
        if ($grade >= 75) {
            return '3.00';
        }

        return '5.00';
    }
}
