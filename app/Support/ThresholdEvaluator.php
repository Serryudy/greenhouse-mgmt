<?php

namespace App\Support;

use App\Models\Threshold;

class ThresholdEvaluator
{
    /**
     * Classify a value against a threshold.
     *
     * @return string 'normal' | 'warning' | 'critical' | 'unknown'
     */
    public static function status($value, ?Threshold $threshold): string
    {
        if ($value === null || $threshold === null) {
            return 'unknown';
        }

        $value = (float) $value;

        if (($threshold->critical_min !== null && $value < $threshold->critical_min)
            || ($threshold->critical_max !== null && $value > $threshold->critical_max)) {
            return 'critical';
        }

        if (($threshold->warning_min !== null && $value < $threshold->warning_min)
            || ($threshold->warning_max !== null && $value > $threshold->warning_max)) {
            return 'warning';
        }

        return 'normal';
    }
}
