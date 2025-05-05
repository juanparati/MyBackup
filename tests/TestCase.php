<?php

namespace Tests;

use Illuminate\Support\Collection;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Cache that contains all the test traits.
     *
     * @var Collection
     */
    private static Collection $testTraitsCache;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        self::$testTraitsCache = static::collectTestTraits();
        self::runTestTraitsMethods('setUpBefore');
    }

    public static function tearDownAfterClass(): void
    {
        self::runTestTraitsMethods('tearDownAfter');
        parent::tearDownAfterClass();
    }


    /**
     * Collect all the test traits.
     *
     * @return Collection
     */
    private static function collectTestTraits() : Collection {
        return collect(class_uses_recursive(static::class))
            ->keys()
            ->filter(fn($r) => str($r)->startsWith('Tests\\Concerns\\'))
            ->unique()
            ->values();
    }


    /**
     * Call all common methods on test traits.
     *
     * @param string $bootPrefix
     * @return void
     */
    private static function runTestTraitsMethods(string $bootPrefix) : void {
        self::$testTraitsCache
            ->filter(fn($r) => method_exists($r, $bootPrefix . class_basename($r)))
            ->each(fn($r) => call_user_func([static::class, $bootPrefix . class_basename($r)]));
    }
}
