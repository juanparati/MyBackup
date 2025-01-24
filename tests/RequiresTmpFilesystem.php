<?php

namespace Tests;

use Illuminate\Support\Facades\File;

trait RequiresTmpFilesystem
{

    use CreatesApplication;

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
        File::deleteDirectory(static::$tmpDir);
        parent::tearDownAfterClass();
    }
}
