<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;

class DeclarativeHumanDate
{
    /**
     * Return the relative datetime base on a human expression.
     */
    public static function relative(string $expression, ?\DateTimeInterface $date = null): CarbonImmutable
    {
        $date = $date ? now()->parse($date) : now();

        $numberUnit = explode(' ', strtolower($expression));

        $number = (int) $numberUnit[0];
        $unit = $numberUnit[1] ?? 'days';
        unset($numberUnit);

        switch ($unit) {
            case 'minute':
            case 'minutes':
                $date->subMinutes($number);
                break;

            case 'hour':
            case 'hours':
                $date->subHours($number);
                break;

            case 'week':
            case 'weeks':
                $date->subWeeks($number);
                break;

            case 'month':
            case 'months':
                $date->subMonths($number);
                break;

            case 'year':
            case 'years':
                $date->subYears($number);
                break;

            default:
                $date->subDays($number);
                break;
        }

        return $date->toImmutable();
    }
}
