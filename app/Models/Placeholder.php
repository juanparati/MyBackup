<?php

namespace App\Models;

class Placeholder
{
    /**
     * Constructor.
     */
    public function __construct(protected array $dictionary = [])
    {
    }

    /**
     * Add/Replace value from dictionary.
     *
     * @return $this
     */
    public function addDictionary(string $key, string|int|float $value): static
    {
        $this->dictionary[$key] = $value;

        return $this;
    }

    /**
     * Replace where placeholders.
     *
     * @param  string  $where
     */
    public function replace(string $string): string
    {
        // Replace elements from dictionary
        foreach ($this->dictionary as $key => $value) {
            $string = str_replace('{{'.$key.'}}', $value, $string);
        }

        // Filesystem related placeholders
        if (preg_match_all('/{{basename:(.*)}}/m', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $string = str_replace($match[0], basename($match[1]), $string);
            }
        }

        // Datetime related placeholders
        $regDatetime = '(:([0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})?))?';
        $dateConvertFnc = function (string $string, array $matches, callable $callback): string {
            foreach ($matches as $match) {
                $date = now();

                if ($match[2] ?? null) {
                    $date = now()->parse($match[2]);
                }

                $string = str_replace($match[0], $callback($date), $string);
            }

            return $string;
        };

        // Replace by current time -/+ interval (Ex: {{date_calc:-10days}})
        if (preg_match_all('/{{date_calc:([-+])([0-9]+)(minute|minutes|hour|hours|day|days|month|months|year|years)?}}/m', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $date = match ($match[3] ?? 'hour') {
                    'minute', 'minutes' => now()->subMinutes($match[2]),
                    'hour'  , 'hours' => now()->subHours($match[2]),
                    'day'   , 'days' => now()->subDays($match[2]),
                    'month' , 'months' => now()->subMonths($match[2]),
                    'year'  , 'years' => now()->subYears($match[2]),
                };

                $string = str_replace($match[0], $date->toDateTimeString(), $string);
            }
        }

        // Replace by UUID 7 based on a specific time (Ex: {{uuid:2024-02-01}})
        if (preg_match_all('/{{uuid'.$regDatetime.'}}/m', $string, $matches, PREG_SET_ORDER)) {
            $string = $dateConvertFnc(
                $string,
                $matches,
                fn ($date) => substr(Uuid::uuid7($date), 0, 12).'0-0000-0000-000000000000');
        }

        // Replaced by date format (Ex: {{date_format:2024-02-01 13:00:00}}
        if (preg_match_all('/{{date'.$regDatetime.'}}/m', $string, $matches, PREG_SET_ORDER)) {
            $string = $dateConvertFnc($string, $matches, fn ($date) => $date->toDateString());
        }

        // Replaced by datetime format (Ex: {{datetime_format:2024-02-01}}
        if (preg_match_all('/{{datetime'.$regDatetime.'}}/m', $string, $matches, PREG_SET_ORDER)) {
            $string = $dateConvertFnc($string, $matches, fn ($date) => $date->toDateTimeString());
        }

        // Replaced by datetime format (Ex: {{date_format:2024-02-01}}
        if (preg_match_all('/{{timestamp'.$regDatetime.'}}/m', $string, $matches, PREG_SET_ORDER)) {
            $string = $dateConvertFnc($string, $matches, fn ($date) => $date->timestamp);
        }

        if (preg_match_all('/{{numeric:(.*)}}/m', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $string = str_replace(
                    $match[0],
                    preg_replace("~\D~", '', $match[1]),
                    $string
                );
            }
        }

        return $string;
    }
}
