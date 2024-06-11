<?php

namespace Tests\Unit;

use App\Helpers\DeclarativeHumanDate;
use Carbon\CarbonInterface;
use Tests\TestCase;

class DeclarativeHumanDateTest extends TestCase
{
    const TIME_REFERENCES = [
        'minute',
        'hour',
        'day',
        'week',
        'month',
        'year',
    ];

    protected CarbonInterface $currentDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currentDate = now()->toImmutable();
    }

    public function testRelativeTime()
    {
        foreach (static::TIME_REFERENCES as $timeReference) {
            foreach ([$timeReference, str($timeReference)->plural()] as $k => $reference) {
                $diff = $k ? rand(2, 10) : 1;
                $exp = $diff.' '.$reference;
                $method = 'sub'.ucfirst($reference);

                $this->assertEquals(
                    DeclarativeHumanDate::relative($exp, $this->currentDate)->toDateTimeString(),
                    $k ? $this->currentDate->{$method}($diff) : $this->currentDate->{$method}(),
                    "Relative to $exp"
                );
            }
        }
    }
}
