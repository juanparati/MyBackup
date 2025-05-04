<?php

namespace Tests\Feature;

use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class UnlockCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function test_unlock_command_on_missing(): void
    {
        $this->artisan(
            'init',
            [
                'config_file' => static::$tmpDir.'config.yaml',
                '--snapshot_path' => static::$tmpDir,
                '--catalog_path' => static::$tmpDir,
            ]
        )->assertOk();

        $this->artisan(
            'unlock',
            ['config_file' => static::$tmpDir.'config.yaml']
        )->assertOk();
    }
}
