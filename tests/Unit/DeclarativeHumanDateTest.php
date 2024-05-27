<?php

use App\Helpers\DeclarativeHumanDate;

$current = now()->toImmutable();

test('relative to days', function () use ($current) {
    expect(DeclarativeHumanDate::relative('1 day', $current)->toDateTimeString())
        ->toBe($current->subDay()->toDateTimeString())
        ->and(DeclarativeHumanDate::relative('2 days', $current)->toDateTimeString())
        ->toBe($current->subDays(2)->toDateTimeString());
});

test('relative to minutes', function () use ($current) {
    expect(DeclarativeHumanDate::relative('1 minute', $current)->toDateTimeString())
        ->toBe($current->subMinute()->toDateTimeString())
        ->and(DeclarativeHumanDate::relative('2 minutes', $current)->toDateTimeString())
        ->toBe($current->subMinutes(2)->toDateTimeString());
});

test('relative to months', function () use ($current) {
    expect(DeclarativeHumanDate::relative('1 month', $current)->toDateTimeString())
        ->toBe($current->subMonth()->toDateTimeString())
        ->and(DeclarativeHumanDate::relative('2 months', $current)->toDateTimeString())
        ->toBe($current->subMonths(2)->toDateTimeString());
});

test('relative to years', function () use ($current) {
    expect(DeclarativeHumanDate::relative('1 year', $current)->toDateTimeString())
        ->toBe($current->subYear()->toDateTimeString())
        ->and(DeclarativeHumanDate::relative('2 years', $current)->toDateTimeString())
        ->toBe($current->subYears(2)->toDateTimeString());
});
