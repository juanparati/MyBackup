<?php

namespace Tests\Unit;

use App\Helpers\DeclarativeHumanDate;
use Carbon\CarbonInterface;
use Tests\TestCase;

class DeclarativeHumanDateTest extends TestCase
{

    protected CarbonInterface $currentDate;


    protected function setUp(): void
    {
        parent::setUp();
        $this->currentDate = now()->toImmutable();
    }


    public function testRelativeToMinutes()
    {
        $declarative = DeclarativeHumanDate::relative('1 minute', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subMinute()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' minutes', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subMinutes($diff)->toDateTimeString());
    }


    public function testRelativeToHours()
    {
        $declarative = DeclarativeHumanDate::relative('1 hour', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subHour()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' hours', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subHours($diff)->toDateTimeString());
    }


    public function testRelativeToDays()
    {
        $declarative = DeclarativeHumanDate::relative('1 day', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subDay()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' days', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subDays($diff)->toDateTimeString());
    }


    public function testRelativeToWeeks()
    {
        $declarative = DeclarativeHumanDate::relative('1 week', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subWeek()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' weeks', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subWeeks($diff)->toDateTimeString());
    }


    public function testRelativeToMonths()
    {
        $declarative = DeclarativeHumanDate::relative('1 month', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subMonth()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' months', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subMonths($diff)->toDateTimeString());
    }


    public function testRelativeToYears()
    {
        $declarative = DeclarativeHumanDate::relative('1 year', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subYear()->toDateTimeString());

        $diff = rand(2, 10);

        $declarative = DeclarativeHumanDate::relative($diff .' years', $this->currentDate)
            ->toDateTimeString();

        $this->assertEquals($declarative, $this->currentDate->subYears($diff)->toDateTimeString());
    }

}
