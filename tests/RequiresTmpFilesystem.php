<?php

namespace Tests;

use Illuminate\Support\Facades\File;

trait RequiresTmpFilesystem
{
    protected static string $tmpDir = '/tmp/mybackup_test/';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$tmpDir = sys_get_temp_dir().'/mybackup_test/';

        if (! file_exists(static::$tmpDir)) {
            mkdir(static::$tmpDir);
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            File::deleteDirectory(static::$tmpDir);
        } catch (\Exception $e) {
            echo '\nUnable to delete temporal test directory ' . static::$tmpDir;
            echo '\nReason: ' . $e->getMessage();
        }

        parent::tearDownAfterClass();
    }
}
