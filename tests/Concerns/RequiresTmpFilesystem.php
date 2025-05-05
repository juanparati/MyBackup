<?php

namespace Tests\Concerns;

use Illuminate\Filesystem\Filesystem;
use Tests\CreatesApplication;

trait RequiresTmpFilesystem
{

    use CreatesApplication;

    protected static string $tmpDir = '/tmp/mybackup_test/';

    public static function setUpBeforeRequiresTmpFilesystem(): void
    {
        static::$tmpDir = sys_get_temp_dir().'/mybackup_test/';

        if (! file_exists(static::$tmpDir)) {
            mkdir(static::$tmpDir);
        }
    }

    public static function tearDownAfterRequiresTmpFilesystem(): void
    {
        (new Filesystem)->deleteDirectory(static::$tmpDir);
    }
}
