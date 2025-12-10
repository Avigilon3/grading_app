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
        if ($grade >= 98) {
            return '1.00';
        }
        if ($grade >= 95) {
            return '1.25';
        }
        if ($grade >= 92) {
            return '1.50';
        }
        if ($grade >= 89) {
            return '1.75';
        }
        if ($grade >= 86) {
            return '2.00';
        }
        if ($grade >= 83) {
            return '2.25';
        }
        if ($grade >= 80) {
            return '2.50';
        }
        if ($grade >= 77) {
            return '2.75';
        }
        if ($grade >= 75) {
            return '3.00';
        }
        if ($grade >= 70) {
            return '4.00';
        }

        return '5.00';
    }
}
