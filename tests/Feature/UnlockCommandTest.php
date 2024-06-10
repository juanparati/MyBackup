<?php

namespace Tests\Feature;

use Tests\RequiresTmpFilesystem;
use Tests\TestCase;

class UnlockCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function testUnlockCommandOnMissing(): void {
        $this->artisan(
            'init',
            [
                'config_file' => static::$tmpDir . 'config.yaml',
                '--snapshot_path' => static::$tmpDir,
                '--catalog_path' => static::$tmpDir,
            ]
        )->assertOk();

        $this->artisan(
            'unlock',
            ['config_file' => static::$tmpDir . 'config.yaml']
        )->assertOk();
    }
}
